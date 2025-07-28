<?php
// src/Command/ImportServicesCommand.php

namespace App\Command;

use App\Service\ServicePublicManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-services',
    description: 'Importer les services publics depuis un fichier CSV'
)]
class ImportServicesCommand extends Command
{
    public function __construct(private ServicePublicManager $serviceManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('fichier', InputArgument::REQUIRED, 'Chemin vers le fichier CSV');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fichier = $input->getArgument('fichier');

        if (!file_exists($fichier)) {
            $io->error("Le fichier {$fichier} n'existe pas");
            return Command::FAILURE;
        }

        $io->info("Import des services depuis {$fichier}...");

        $resultats = $this->serviceManager->importerDepuisCsv($fichier);

        $io->success("Import terminé: {$resultats['succes']} services importés");

        if (!empty($resultats['erreurs'])) {
            $io->warning(count($resultats['erreurs']) . " erreurs détectées");
            foreach ($resultats['erreurs'] as $erreur) {
                $io->error($erreur['erreur']);
            }
        }

        return Command::SUCCESS;
    }
}
