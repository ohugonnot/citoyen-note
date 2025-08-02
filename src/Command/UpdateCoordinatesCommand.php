<?php
// src/Command/UpdateCoordinatesCommand.php

namespace App\Command;

use App\Entity\ServicePublic;
use App\Service\GeolocationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'app:update-coordinates',
    description: 'Met à jour les coordonnées des services publics avec l\'API Adresse Gouv'
)]
class UpdateCoordinatesCommand extends Command
{
    private const BATCH_SIZE = 100;
    private const DB_BATCH_SIZE = 500;
    private const DISTANCE_THRESHOLD_KM = 0.5;

    private SymfonyStyle $io;
    private string $logFilePath;
    private bool $isDryRun;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private GeolocationService $geolocationService,
        private LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Taille des lots API', self::BATCH_SIZE)
            ->addOption('db-batch-size', 'd', InputOption::VALUE_OPTIONAL, 'Taille des lots DB', self::DB_BATCH_SIZE)
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limiter le nombre d\'enregistrements')
            ->addOption('force-all', 'f', InputOption::VALUE_NONE, 'Forcer la mise à jour')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation')
            ->addOption('log-file', null, InputOption::VALUE_OPTIONAL, 'Fichier de log')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $batchSize = (int) $input->getOption('batch-size');
        $dbBatchSize = (int) $input->getOption('db-batch-size');
        $limit = $input->getOption('limit') ? (int) $input->getOption('limit') : null;
        $forceAll = $input->getOption('force-all');
        $this->isDryRun = $input->getOption('dry-run');
        $this->logFilePath = $input->getOption('log-file')
            ?: sprintf('var/logs/coordinates_update_%s.log', date('Y-m-d_H-i-s'));

        // Header dans le log
        if (! $this->isDryRun) {
            $dir = dirname($this->logFilePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            file_put_contents(
                $this->logFilePath,
                "=== DÉBUT MISE À JOUR DES COORDONNÉES " . date('Y-m-d H:i:s') . " ===\n",
                FILE_APPEND
            );
        }

        $this->io->title('Mise à jour des coordonnées des services publics');

        if ($this->isDryRun) {
            $this->io->warning('MODE SIMULATION');
        }

        $stats = [
            'total' => 0,
            'processed' => 0,
            'updated' => 0,
            'not_found' => 0,
            'errors' => 0,
            'distance_alerts' => 0,
            'skipped' => 0
        ];
        $distanceAlerts = [];

        try {
            $totalCount = $this->getTotalCount($forceAll);
            $stats['total'] = min($totalCount, $limit ?? $totalCount);

            if ($stats['total'] === 0) {
                $this->io->success('Aucun service à traiter.');
                return Command::SUCCESS;
            }

            $this->io->info(sprintf(
                'Traitement de %d services (lots DB: %d, lots API: %d)',
                $stats['total'], $dbBatchSize, $batchSize
            ));

            $progressBar = new ProgressBar($output, $stats['total']);
            $progressBar->setFormat('debug');
            $progressBar->start();

            $offset = 0;
            $processedTotal = 0;

            while ($processedTotal < $stats['total']) {
                $services = $this->getServicesBatch($forceAll, $dbBatchSize, $offset);
                if (empty($services)) {
                    break;
                }

                $this->processDatabaseBatch(
                    $services,
                    $batchSize,
                    $stats,
                    $distanceAlerts,
                    $progressBar
                );

                $this->cleanupMemory();

                $processedTotal += count($services);
                $offset += $dbBatchSize;

                if ($limit && $processedTotal >= $limit) {
                    break;
                }
            }

            $progressBar->finish();
            $this->io->newLine(2);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour des coordonnées', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getTotalCount(bool $forceAll): int
    {
        $qb = $this->entityManager->getRepository(ServicePublic::class)
            ->createQueryBuilder('sp')
            ->select('COUNT(sp.id)');
        if (! $forceAll) {
            $qb->where('sp.latitude IS NULL OR sp.longitude IS NULL');
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function getServicesBatch(bool $forceAll, int $limit, int $offset): array
    {
        $qb = $this->entityManager->getRepository(ServicePublic::class)
            ->createQueryBuilder('sp')
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        if (!$forceAll) {
            $qb->where('sp.latitude IS NULL OR sp.longitude IS NULL');
        }
        return $qb->getQuery()->getResult();
    }

    private function processDatabaseBatch(
        array $services,
        int $apiBatchSize,
        array &$stats,
        array &$distanceAlerts,
        ProgressBar $progressBar
    ): void {
        foreach ($services as $service) {
            $adresse = $this->buildAddressString($service);
            try {
                if (empty(trim($adresse))) {
                    $stats['skipped']++;
                } else {
                    $result = $this->geolocationService->geocodeAddressWithFallback($adresse);
                    if ($result && isset($result['lat'], $result['lng'])) {
                        $this->processApiResult(
                            $service,
                            $result,
                            $stats,
                            $distanceAlerts,
                            $this->isDryRun
                        );
                        $stats['updated']++;
  /*                      $line = sprintf(
                            "[%s] OK       – ID %s – %s → lat:%.6f, lng:%.6f, score:%.2f\n",
                            date('Y-m-d H:i:s'),
                            $service->getId()->toRfc4122(),
                            $adresse,
                            $result['lat'],
                            $result['lng'],
                            $result['score'] ?? 0
                        ); */
                    } else {
                        $stats['not_found']++;
                        $line = sprintf(
                            "[%s] NOTFOUND – ID %s – %s\n",
                            date('Y-m-d H:i:s'),
                            $service->getId()->toRfc4122(),
                            $adresse
                        );
                    }

                    if (!$this->isDryRun && !empty($line)) {
                        file_put_contents(
                            $this->logFilePath,
                            $line,
                            FILE_APPEND
                        );
                    }
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                $errorLine = sprintf(
                    "[%s] ERROR    – ID %s – %s – %s\n",
                    date('Y-m-d H:i:s'),
                    $service->getId()->toRfc4122(),
                    $adresse,
                    $e->getMessage()
                );
                if (! $this->isDryRun) {
                    file_put_contents(
                        $this->logFilePath,
                        $errorLine,
                        FILE_APPEND
                    );
                }
                $this->logger->error('Erreur lors du géocodage', [
                    'service_id' => $service->getId()->toRfc4122(),
                    'adresse'    => $adresse,
                    'error'      => $e->getMessage()
                ]);
            }

            $progressBar->advance(1);
        }

        if (!$this->isDryRun) {
            $this->entityManager->flush();
        }
    }

    private function buildAddressString($service): string
    {
        $ville      = trim($service->getVille() ?? '');
        $adresse    = trim($service->getAdresseComplete() ?? '');
        $codePostal = trim($service->getCodePostal() ?? '');

        if (!empty($ville) && empty($adresse) && empty($codePostal)) {
            return $ville;
        }
        if (!empty($adresse) && !empty($codePostal) && !empty($ville)) {
            return sprintf('%s, %s %s', $adresse, $codePostal, $ville);
        }
        $parts = array_filter([$adresse, $codePostal, $ville]);
        return implode(' ', $parts);
    }

    private function processApiResult(
        $service,
        array &$result,
        array &$stats,
        array &$distanceAlerts,
        bool $isDryRun
    ): void {
        $newLat = $result['lat'];
        $newLng = $result['lng'];
        $oldLat = $service->getLatitude();
        $oldLng = $service->getLongitude();

        if ($oldLat && $oldLng) {
            $distance = $this->calculateDistance($oldLat, $oldLng, $newLat, $newLng);
            if ($distance > self::DISTANCE_THRESHOLD_KM) {
                /** @var ServicePublic $service */
                file_put_contents(
                    $this->logFilePath,
                    sprintf(
                        "[%s] NOK       – ID %s → (api) lat:%.6f != %.6f (bdd), (api) lng:%.6f == %.6f (bdd), score:%.2f\n",
                        date('Y-m-d H:i:s'),
                        $service->getNom()." ".$service->getAdresseFormatee(),
                        $newLat,
                        $newLng,
                        $oldLat,
                        $oldLng,
                        $result['score'] ?? 0
                    ),
                    FILE_APPEND
                );
                $stats['distance_alerts']++;
                $distanceAlerts[] = [
                    'service_id'      => $service->getId()->toRfc4122(),
                    'nom'             => $service->getNom(),
                    'distance_km'     => $distance,
                    'old_coordinates' => ['lat' => $oldLat, 'lng' => $oldLng],
                    'new_coordinates' => ['lat' => $newLat, 'lng' => $newLng],
                    'score'           => $result['score'] ?? null
                ];
            }
        }

        if (!$isDryRun) {
            $service->setLatitude($newLat);
            $service->setLongitude($newLng);
            $this->entityManager->persist($service);
            $this->entityManager->flush();
        }
    }

    private function cleanupMemory(): void
    {
        $this->entityManager->clear();
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
