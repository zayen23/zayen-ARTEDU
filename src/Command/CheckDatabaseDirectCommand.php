<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-database-direct',
    description: 'Vérifie directement la base de données avec des requêtes SQL.'
)]
class CheckDatabaseDirectCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Vérification directe de la base de données');

        $connection = $this->entityManager->getConnection();

        // 1. Afficher les informations de connexion
        $io->section('1. Informations de connexion');
        $params = $connection->getParams();
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['Host', $params['host'] ?? 'N/A'],
                ['Port', $params['port'] ?? 'N/A'],
                ['Database', $params['dbname'] ?? 'N/A'],
                ['User', $params['user'] ?? 'N/A'],
            ]
        );

        // 2. Vérifier si la table existe
        $io->section('2. Vérification de la table sponsor_contract');
        try {
            $tableExists = $connection->executeQuery(
                "SELECT COUNT(*) as count FROM information_schema.tables 
                 WHERE table_schema = DATABASE() 
                 AND table_name = 'sponsor_contract'"
            )->fetchOne();
            
            if ($tableExists > 0) {
                $io->success('✓ La table sponsor_contract existe');
            } else {
                $io->error('✗ La table sponsor_contract n\'existe pas');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 3. Compter les contrats avec SQL direct
        $io->section('3. Nombre de contrats (requête SQL directe)');
        try {
            $count = $connection->executeQuery('SELECT COUNT(*) FROM sponsor_contract')->fetchOne();
            $io->info(sprintf('Nombre de contrats trouvés: %d', $count));
        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 4. Lister tous les contrats avec SQL direct
        $io->section('4. Liste de tous les contrats (requête SQL directe)');
        try {
            $contracts = $connection->executeQuery(
                'SELECT id, contract_number, signed_at, expires_at, level, sponsor_id 
                 FROM sponsor_contract 
                 ORDER BY id DESC'
            )->fetchAllAssociative();

            if (empty($contracts)) {
                $io->warning('Aucun contrat trouvé avec la requête SQL directe');
            } else {
                $rows = [];
                $now = new \DateTime('today');
                
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
            }
        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 5. Tester la requête Doctrine
        $io->section('5. Test avec Doctrine Repository');
        try {
            $repository = $this->entityManager->getRepository(\App\Entity\SponsorContract::class);
            $allContracts = $repository->findAll();
            $io->info(sprintf('Doctrine Repository trouve: %d contrat(s)', count($allContracts)));
            
            if (!empty($allContracts)) {
                foreach ($allContracts as $contract) {
                    $io->text(sprintf(
                        '  • ID: %d, N°: %s, Expire: %s',
                        $contract->getId(),
                        $contract->getContractNumber(),
                        $contract->getExpiresAt()?->format('d/m/Y') ?? 'N/A'
                    ));
                }
            }
        } catch (\Exception $e) {
            $io->error('Erreur Doctrine: ' . $e->getMessage());
        }

        // 6. Tester findExpiringWithinDays
        $io->section('6. Test de findExpiringWithinDays(7)');
        try {
            $repository = $this->entityManager->getRepository(\App\Entity\SponsorContract::class);
            if (method_exists($repository, 'findExpiringWithinDays')) {
                $expiringContracts = $repository->findExpiringWithinDays(7);
                $io->info(sprintf('Contrats expirant dans 7 jours: %d', count($expiringContracts)));
            } else {
                $io->warning('La méthode findExpiringWithinDays n\'existe pas dans le repository');
            }
        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}

