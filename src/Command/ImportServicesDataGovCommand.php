<?php

namespace App\Command;

use App\Service\ServicePublicManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:import-services-publics',
    description: 'Importe les services publics depuis un fichier JSON ou ZIP',
    aliases: ['import:services-publics']
)]
class ImportServicesDataGovCommand extends Command
{
    protected static $defaultName = 'app:import-services-publics';

    public function __construct(
        private readonly ServicePublicManager $manager,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('fichier', InputArgument::REQUIRED, 'Chemin vers le fichier JSON ou ZIP')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Nombre max de services à importer')
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Taille des lots (défaut: 100)', 100)
            ->addOption('log-errors', null, InputOption::VALUE_OPTIONAL, 'Fichier de log des erreurs', 'var/log/import_services_errors.log')
            ->addOption('skip-existing', 's', InputOption::VALUE_NONE, 'Ignorer les services existants')
            ->addOption('start-from', null, InputOption::VALUE_OPTIONAL, 'Commencer à partir du service N', 0)
            ->addOption('memory-limit', 'm', InputOption::VALUE_OPTIONAL, 'Limite mémoire en MB (défaut: 1024)', 1024)
            ->addOption('clear-cache-every', 'c', InputOption::VALUE_OPTIONAL, 'Vider le cache Doctrine tous les X lots (défaut: 10)', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $chemin = $input->getArgument('fichier');
        $limit = $input->getOption('limit') ? (int) $input->getOption('limit') : null;
        $batchSize = (int) $input->getOption('batch-size');
        $startFrom = (int) $input->getOption('start-from');
        $logPath = $input->getOption('log-errors');
        $skipExisting = $input->getOption('skip-existing');
        $memoryLimit = (int) $input->getOption('memory-limit');
        $clearCacheEvery = (int) $input->getOption('clear-cache-every');

        // Configuration mémoire optimisée
        ini_set('memory_limit', $memoryLimit . 'M');
        ini_set('max_execution_time', 0); // Pas de limite de temps

        $fs = new Filesystem();

        if (!$fs->exists($chemin)) {
            $io->error("Fichier introuvable : $chemin");
            return Command::FAILURE;
        }

        $io->title('Import des Services Publics (Version Optimisée)');

        try {
            $fichierTraite = $this->preparerFichier($chemin, $io, $fs);

            if (!$fichierTraite) {
                return Command::FAILURE;
            }

            $tailleFichier = filesize($fichierTraite);
            $io->note("Taille du fichier : " . $this->formatBytes($tailleFichier));
            $io->note("Taille des lots : $batchSize services");
            $io->note("Nettoyage cache Doctrine tous les $clearCacheEvery lots");

            $resultats = $this->traiterFichierJsonStreamOptimise(
                $fichierTraite,
                $limit,
                $batchSize,
                $startFrom,
                $logPath,
                $skipExisting,
                $clearCacheEvery,
                $io
            );

            // Nettoyage
            if ($fichierTraite !== $chemin && $fs->exists($fichierTraite)) {
                $fs->remove($fichierTraite);
            }

            $this->afficherResultats($resultats, $io, $logPath);
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'import : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function traiterFichierJsonStreamOptimise(
        string $chemin,
        ?int $limit,
        int $batchSize,
        int $startFrom,
        string $logPath,
        bool $skipExisting,
        int $clearCacheEvery,
        SymfonyStyle $io
    ): array {
        $io->note('Analyse du fichier JSON...');

        // Ouvrir le fichier et préparer le log
        $handle = fopen($chemin, 'r');
        if (!$handle) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier");
        }

        $logHandle = fopen($logPath, 'w');
        fwrite($logHandle, "Index;Service ID;Erreur;Nom Service\n");

        $stats = [
            'total' => 0,
            'succes' => 0,
            'erreurs' => 0,
            'ignores' => 0,
            'lots_traites' => 0
        ];

        try {
            $io->note('Recherche du début du tableau services...');
            $this->chercherDebutServices($handle, $io);
            $io->note('Début du parsing des services...');

            // Parser les services un par un
            $parser = new StreamingJsonServiceParser($handle);

            $batch = [];
            $serviceCount = 0;
            $totalProcessed = 0;
            $lotCount = 0;

            // Progress bar
            $progress = new ProgressBar($io);
            $progress->setFormat(' %current% services traités - %elapsed% - %memory% - %message%');
            $progress->setMessage('Démarrage...');
            $progress->start();

            foreach ($parser->parseServices() as $service) {
                $totalProcessed++;

                // Skip si on commence à partir d'un index spécifique
                if ($totalProcessed <= $startFrom) {
                    continue;
                }

                // Vérifier la limite
                if ($limit && $serviceCount >= $limit) {
                    break;
                }

                try {
                    if (!$this->validerService($service)) {
                        $stats['ignores']++;
                        $progress->advance();
                        $progress->setMessage("Services: {$stats['succes']}, Erreurs: {$stats['erreurs']}, Ignorés: {$stats['ignores']}");
                        continue;
                    }

                    $serviceTransforme = $this->transformer($service);
                    $batch[] = $serviceTransforme;

                    // Traitement par lot optimisé
                    if (count($batch) >= $batchSize) {
                        $resultatsLot = $this->traiterLotOptimise($batch, $skipExisting, $logHandle, $totalProcessed);
                        $stats['succes'] += $resultatsLot['succes'];
                        $stats['erreurs'] += $resultatsLot['erreurs'];
                        $stats['lots_traites']++;
                        $batch = [];
                        $lotCount++;

                        // Nettoyage mémoire périodique
                        if ($lotCount % $clearCacheEvery === 0) {
                            $this->nettoyerMemoire($io);
                        }
                    }

                } catch (\Exception $e) {
                    $stats['erreurs']++;
                    $serviceId = $service['id'] ?? 'unknown';
                    $serviceName = $service['nom'] ?? 'unknown';
                    fwrite($logHandle, "$totalProcessed;$serviceId;\"{$e->getMessage()}\";\"$serviceName\"\n");
                }

                $serviceCount++;
                $stats['total']++;

                $progress->advance();
                $progress->setMessage(sprintf(
                    "Lot %d - Succès: %d, Erreurs: %d, Mémoire: %s",
                    $lotCount,
                    $stats['succes'],
                    $stats['erreurs'],
                    $this->formatBytes(memory_get_usage(true))
                ));

                // Affichage périodique détaillé
                if ($serviceCount % ($batchSize * 10) === 0) {
                    $io->writeln('');
                    $io->note(sprintf(
                        "Traité %d services (%d lots) - Mémoire: %s (pic: %s)",
                        $serviceCount,
                        $lotCount,
                        $this->formatBytes(memory_get_usage(true)),
                        $this->formatBytes(memory_get_peak_usage(true))
                    ));
                }
            }

            // Traiter le dernier lot
            if (!empty($batch)) {
                $resultatsLot = $this->traiterLotOptimise($batch, $skipExisting, $logHandle, $totalProcessed);
                $stats['succes'] += $resultatsLot['succes'];
                $stats['erreurs'] += $resultatsLot['erreurs'];
                $stats['lots_traites']++;
            }

            // Nettoyage final
            $this->nettoyerMemoire($io, true);
            $progress->finish();

        } finally {
            fclose($handle);
            fclose($logHandle);
        }

        return $stats;
    }

    /**
     * Traitement optimisé par lot avec une seule transaction
     */
    private function traiterLotOptimise(array $services, bool $skipExisting, $logHandle, int $currentIndex): array
    {
        $stats = ['succes' => 0, 'erreurs' => 0];

        try {
            // Commencer une transaction pour tout le lot
            $this->entityManager->beginTransaction();

            foreach ($services as $service) {
                try {
                    // Version optimisée sans flush individuel
                    $this->manager->sauvegarder($service, false); // On ajoute un paramètre pour éviter le flush
                    $stats['succes']++;
                } catch (\Exception $e) {
                    $stats['erreurs']++;
                    $serviceId = $service['id_gouv'] ?? 'unknown';
                    $serviceName = $service['nom'] ?? 'unknown';
                    fwrite($logHandle, "$currentIndex;$serviceId;\"{$e->getMessage()}\";\"$serviceName\"\n");
                }
            }

            // Flush une seule fois pour tout le lot
            $this->entityManager->flush();
            $this->entityManager->commit();

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $stats;
    }

    /**
     * Nettoyage mémoire optimisé avec gestion du cache catégories
     */
    private function nettoyerMemoire(SymfonyStyle $io, bool $final = false): void
    {
        $memoryBefore = memory_get_usage(true);

        // Nettoyer le contexte du manager (EntityManager + cache catégories)
        $this->manager->nettoyerContexte();

        // Forcer le garbage collector
        gc_collect_cycles();

        $memoryAfter = memory_get_usage(true);
        $memoryFreed = $memoryBefore - $memoryAfter;

        if ($final || $memoryFreed > 0) {
            $io->note(sprintf(
                "Mémoire nettoyée: %s libérés (avant: %s, après: %s)",
                $this->formatBytes($memoryFreed),
                $this->formatBytes($memoryBefore),
                $this->formatBytes($memoryAfter)
            ));
        }
    }

    private function preparerFichier(string $chemin, SymfonyStyle $io, Filesystem $fs): ?string
    {
        if (!str_ends_with(strtolower($chemin), '.zip')) {
            return $chemin;
        }

        $io->note('Extraction de l\'archive ZIP...');

        $zip = new \ZipArchive();
        if ($zip->open($chemin) !== true) {
            $io->error("Impossible d'ouvrir l'archive ZIP");
            return null;
        }

        $tempFile = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (preg_match('/\.json$/i', $entry) && !str_contains($entry, '__MACOSX')) {
                $tempFile = sys_get_temp_dir() . '/import_services_' . uniqid() . '.json';
                $io->note("Extraction de : $entry");

                if (copy("zip://$chemin#$entry", $tempFile)) {
                    break;
                }
            }
        }
        $zip->close();

        return $tempFile;
    }

    private function chercherDebutServices($handle, SymfonyStyle $io): void
    {
        $buffer = '';
        $found = false;
        $bytesRead = 0;

        while (!feof($handle) && !$found) {
            $chunk = fread($handle, 8192);
            $buffer .= $chunk;
            $bytesRead += strlen($chunk);

            if (strpos($buffer, '"service"') !== false) {
                $pos = strpos($buffer, '"service"');
                $remaining = substr($buffer, $pos);

                $arrayStart = strpos($remaining, '[');
                if ($arrayStart !== false) {
                    $totalPos = $bytesRead - strlen($buffer) + $pos + $arrayStart + 1;
                    fseek($handle, $totalPos);
                    $found = true;
                    $io->note("Tableau services trouvé à la position $totalPos");
                }
            }

            if (strlen($buffer) > 8192) {
                $buffer = substr($buffer, -1000);
            }

            if ($bytesRead % (1024 * 1024) === 0) {
                $io->note("Recherche... " . $this->formatBytes($bytesRead) . " analysés");
            }
        }

        if (!$found) {
            throw new \RuntimeException("Impossible de trouver le tableau 'service' dans le JSON");
        }
    }

    private function validerService(array $service): bool
    {
        return !empty($service['nom']) && !empty($service['adresse']);
    }

    private function transformer(array $service): array
    {
        $adresse = $service['adresse'][0] ?? [];

        return [
            'id_gouv' => $service['id'] ?? null,
            'nom' => $service['nom'] ?? '',
            'siret' => $service['siret'] ?? null,
            'adresse' => $this->construireAdresse($adresse),
            'code_postal' => $adresse['code_postal'] ?? '',
            'ville' => $adresse['nom_commune'] ?? '',
            'latitude' => $this->convertirCoordonnee($adresse['latitude'] ?? null),
            'longitude' => $this->convertirCoordonnee($adresse['longitude'] ?? null),
            'telephone' => $this->extraireTelephone($service['telephone'] ?? []),
            'email' => $this->extraireEmail($service['adresse_courriel'] ?? []),
            'site_internet' => $this->extraireSiteInternet($service['site_internet'] ?? []),
            'type_service' => $service['pivot'][0]['type_service_local'] ?? null,
            'code_insee' => $service['code_insee_commune'] ?? ($service['pivot'][0]['code_insee_commune'][0] ?? null),
            'horaires' => $this->transformerHoraires($service['plage_ouverture'] ?? []),
            'description' => $service['information_complementaire'] ?? '',
            'date_modification' => $this->convertirDate($service['date_modification'] ?? null),
        ];
    }

    private function construireAdresse(array $adresse): string
    {
        return trim(implode(', ', array_filter([
            $adresse['numero_voie'] ?? '',
          //  $adresse['complement1'] ?? '',
          //  $adresse['complement2'] ?? ''
        ])));
    }

    private function convertirCoordonnee(?string $coord): ?float
    {
        if (!$coord || $coord === '' || $coord === '0') return null;
        $float = (float) $coord;
        return $float === 0.0 ? null : $float;
    }

    private function extraireTelephone(array $telephones): ?string
    {
        return $telephones[0]['valeur'] ?? null;
    }

    private function extraireEmail(array $emails): ?string
    {
        return $emails[0] ?? null;
    }

    private function extraireSiteInternet(array $sites): ?string
    {
        return $sites[0]['valeur'] ?? null;
    }

    private function convertirDate(?string $date): ?\DateTime
    {
        if (!$date) return null;
        try {
            return \DateTime::createFromFormat('d/m/Y H:i:s', $date) ?: null;
        } catch (\Exception) {
            return null;
        }
    }

    private function transformerHoraires(array $plages): array
    {
        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $resultat = [];

        // Initialisation : tous fermés
        foreach ($jours as $jour) {
            $resultat[$jour] = ['ouvert' => false, 'creneaux' => []];
        }

        foreach ($plages as $plage) {
            $jourDebut = $this->trouverIndexJour($plage['nom_jour_debut'] ?? '');
            $jourFin   = $this->trouverIndexJour($plage['nom_jour_fin']   ?? $plage['nom_jour_debut'] ?? '');

            if ($jourDebut === false) {
                continue;
            }
            if ($jourFin === false) {
                $jourFin = $jourDebut;
            }

            for ($i = $jourDebut; $i <= $jourFin; $i++) {
                $jour = $jours[$i];
                $resultat[$jour]['ouvert'] = true;

                // On boucle sur les deux créneaux possibles
                for ($slot = 1; $slot <= 2; $slot++) {
                    $debutKey = "valeur_heure_debut_{$slot}";
                    $finKey   = "valeur_heure_fin_{$slot}";

                    if (!empty($plage[$debutKey]) && !empty($plage[$finKey])) {
                        $resultat[$jour]['creneaux'][] = [
                            'ouverture' => substr($plage[$debutKey], 0, 5),
                            'fermeture' => substr($plage[$finKey],   0, 5),
                        ];
                    }
                }
            }
        }

        return $resultat;
    }

    private function trouverIndexJour(string $nomJour): int|false
    {
        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        return array_search(strtolower($nomJour), $jours);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes >= 1024 && $i < 3; $i++) $bytes /= 1024;
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function afficherResultats(array $stats, SymfonyStyle $io, string $logPath): void
    {
        $io->newLine(2);
        $io->success('Import terminé !');

        $io->table(['Métrique', 'Valeur'], [
            ['Total traité', number_format($stats['total'])],
            ['Succès', number_format($stats['succes'])],
            ['Erreurs', number_format($stats['erreurs'])],
            ['Ignorés', number_format($stats['ignores'])],
            ['Lots traités', number_format($stats['lots_traites'])],
            ['Mémoire pic', $this->formatBytes(memory_get_peak_usage(true))]
        ]);

        if ($stats['erreurs'] > 0) {
            $io->warning("Erreurs enregistrées dans : $logPath");
        }

        // Calcul des performances
        if ($stats['lots_traites'] > 0) {
            $servicesParLot = round($stats['succes'] / $stats['lots_traites'], 2);
            $io->note("Performance: $servicesParLot services par lot en moyenne");
        }
    }
}

// Parser streaming optimisé (inchangé)
class StreamingJsonServiceParser
{
    private $handle;
    private $buffer = '';
    private $depth = 0;
    private $inService = false;
    private $serviceBuffer = '';
    private $braceCount = 0;

    public function __construct($handle)
    {
        $this->handle = $handle;
    }

    public function parseServices(): \Generator
    {
        while (!feof($this->handle)) {
            $chunk = fread($this->handle, 4096);

            for ($i = 0; $i < strlen($chunk); $i++) {
                $char = $chunk[$i];

                if ($char === '{' && !$this->inService) {
                    $this->inService = true;
                    $this->serviceBuffer = '{';
                    $this->braceCount = 1;
                } elseif ($this->inService) {
                    $this->serviceBuffer .= $char;

                    if ($char === '{') {
                        $this->braceCount++;
                    } elseif ($char === '}') {
                        $this->braceCount--;

                        if ($this->braceCount === 0) {
                            try {
                                $service = json_decode($this->serviceBuffer, true, 512, JSON_THROW_ON_ERROR);
                                yield $service;
                            } catch (\JsonException $e) {
                                // Ignorer les services malformés
                            }

                            $this->inService = false;
                            $this->serviceBuffer = '';
                        }
                    }
                }
            }

            if (strlen($this->serviceBuffer) > 1024 * 1024) {
                $this->inService = false;
                $this->serviceBuffer = '';
            }
        }
    }
}