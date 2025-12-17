<?php

namespace App\Command;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-art-database',
    description: 'Teste directement la connexion à la base de données "art".'
)]
class TestArtDatabaseCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Test de connexion à la base de données "art"');

        // Connexion directe à la base "art"
        $connectionParams = [
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'dbname' => 'art',
            'user' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ];

        try {
            $connection = DriverManager::getConnection($connectionParams);
            
            // Tester la connexion en exécutant une requête simple
            $connection->executeQuery('SELECT 1');
            $io->success('✓ Connexion à la base "art" réussie');

            // Vérifier si la table existe
            $tableExists = $connection->executeQuery(
                "SELECT COUNT(*) FROM information_schema.tables 
                 WHERE table_schema = 'art' 
                 AND table_name = 'sponsor_contract'"
            )->fetchOne();

            if ($tableExists > 0) {
                $io->success('✓ La table sponsor_contract existe dans la base "art"');

                // Compter les contrats
                $count = $connection->executeQuery('SELECT COUNT(*) FROM sponsor_contract')->fetchOne();
                $io->info(sprintf('Nombre de contrats trouvés: %d', $count));

                if ($count > 0) {
                    // Lister tous les contrats
                    $contracts = $connection->executeQuery(
                        'SELECT id, contract_number, signed_at, expires_at, level, sponsor_id 
                         FROM sponsor_contract 
                         ORDER BY id DESC'
                    )->fetchAllAssociative();

                    $now = new \DateTime('today');
                    $rows = [];

                    foreach ($contracts as $contract) {
                        $expiresAt = $contract['expires_at'] ? new \DateTime($contract['expires_at']) : null;
                        $daysUntilExpiry = $expiresAt ? $now->diff($expiresAt)->days : 'N/A';
                        
                        if ($expiresAt && $expiresAt < $now) {
                            $daysUntilExpiry = '- ' . abs($daysUntilExpiry) . ' jours (expiré)';
                        } elseif ($expiresAt) {
                            $daysUntilExpiry = $daysUntilExpiry . ' jours';
                        }

                        $rows[] = [
                            $contract['id'],
                            $contract['contract_number'],
                            $contract['signed_at'] ?? 'N/A',
                            $contract['expires_at'] ?? 'N/A',
                            $contract['level'] ?? 'N/A',
                            $contract['sponsor_id'],
                            $daysUntilExpiry,
                        ];
                    }

                    $io->table(
                        ['ID', 'N° Contrat', 'Date signature', 'Date expiration', 'Niveau', 'Sponsor ID', 'Jours restants'],
                        $rows
                    );

                    // Tester la requête pour les contrats expirant dans 7 jours
                    $limit = (new \DateTime('today'))->modify('+7 days')->setTime(23, 59, 59);
                    $expiringContracts = $connection->executeQuery(
                        'SELECT id, contract_number, expires_at 
                         FROM sponsor_contract 
                         WHERE expires_at >= :now 
                         AND expires_at <= :limit
                         ORDER BY expires_at ASC',
                        [
                            'now' => $now->format('Y-m-d H:i:s'),
                            'limit' => $limit->format('Y-m-d H:i:s'),
                        ]
                    )->fetchAllAssociative();

                    $io->section('Contrats expirant dans les 7 prochains jours');
                    if (empty($expiringContracts)) {
                        $io->warning('Aucun contrat n\'expire dans les 7 prochains jours');
                    } else {
                        $io->info(sprintf('Trouvé %d contrat(s)', count($expiringContracts)));
                        foreach ($expiringContracts as $contract) {
                            $io->text(sprintf(
                                '  • %s - Expire le %s',
                                $contract['contract_number'],
                                (new \DateTime($contract['expires_at']))->format('d/m/Y')
                            ));
                        }
                    }
                }
            } else {
                $io->warning('La table sponsor_contract n\'existe pas dans la base "art"');
            }

            $connection->close();
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

