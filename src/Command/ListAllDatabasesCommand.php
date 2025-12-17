<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-all-databases',
    description: 'Liste toutes les bases de données disponibles et vérifie où se trouve le contrat.'
)]
class ListAllDatabasesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Vérification des bases de données');

        $connection = $this->entityManager->getConnection();
        $params = $connection->getParams();

        // Afficher la base actuelle
        $io->section('Base de données actuellement utilisée');
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['Host', $params['host'] ?? 'N/A'],
                ['Port', $params['port'] ?? 'N/A'],
                ['Database', $params['dbname'] ?? 'N/A'],
                ['User', $params['user'] ?? 'N/A'],
            ]
        );

        // Lister toutes les bases de données
        $io->section('Toutes les bases de données disponibles');
        try {
            $databases = $connection->executeQuery('SHOW DATABASES')->fetchFirstColumn();
            
            $rows = [];
            foreach ($databases as $db) {
                // Vérifier si cette base contient la table sponsor_contract
                try {
                    $count = $connection->executeQuery(
                        "SELECT COUNT(*) FROM information_schema.tables 
                         WHERE table_schema = ? AND table_name = 'sponsor_contract'",
                        [$db]
                    )->fetchOne();
                    
                    $hasTable = $count > 0 ? '✓' : '';
                    
                    if ($hasTable) {
                        // Compter les contrats dans cette base
                        $originalDb = $params['dbname'];
                        $connection->executeStatement("USE `$db`");
                        $contractCount = $connection->executeQuery('SELECT COUNT(*) FROM sponsor_contract')->fetchOne();
                        $connection->executeStatement("USE `$originalDb`");
                        
                        $rows[] = [
                            $db,
                            $hasTable,
                            $contractCount > 0 ? $contractCount . ' contrat(s)' : '0 contrat',
                            $db === $params['dbname'] ? '← ACTUELLE' : ''
                        ];
                    } else {
                        $rows[] = [
                            $db,
                            '',
                            '',
                            $db === $params['dbname'] ? '← ACTUELLE' : ''
                        ];
                    }
                } catch (\Exception $e) {
                    $rows[] = [
                        $db,
                        'Erreur',
                        $e->getMessage(),
                        $db === $params['dbname'] ? '← ACTUELLE' : ''
                    ];
                }
            }

            $io->table(
                ['Base de données', 'Table sponsor_contract', 'Nombre de contrats', ''],
                $rows
            );
        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
        }

        $io->note([
            'L\'application se connecte actuellement à la base: ' . ($params['dbname'] ?? 'N/A'),
            'Si votre contrat est dans une autre base, vous devez soit:',
            '1. Modifier DATABASE_URL dans .env.local pour pointer vers la bonne base',
            '2. Créer le contrat via l\'interface web de l\'application',
        ]);

        return Command::SUCCESS;
    }
}

