<?php

namespace App\Command;

use App\Repository\SponsorContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-database-contracts',
    description: 'Teste la lecture de la base de données pour les contrats expirants.'
)]
class TestDatabaseContractsCommand extends Command
{
    public function __construct(
        private readonly SponsorContractRepository $contractRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Test de lecture de la base de données - Contrats');

        // 1. Vérifier la connexion à la base de données
        $io->section('1. Vérification de la connexion à la base de données');
        try {
            $connection = $this->entityManager->getConnection();
            // Tester la connexion en exécutant une requête simple
            $connection->executeQuery('SELECT 1');
            $io->success('✓ Connexion à la base de données réussie');
        } catch (\Exception $e) {
            $io->error('✗ Erreur de connexion: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 2. Compter tous les contrats
        $io->section('2. Nombre total de contrats dans la base');
        try {
            $totalContracts = $this->contractRepository->count([]);
            $io->info(sprintf('Nombre total de contrats: %d', $totalContracts));
        } catch (\Exception $e) {
            $io->error('✗ Erreur lors du comptage: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 3. Lister tous les contrats avec leurs dates
        $io->section('3. Liste de tous les contrats');
        try {
            $allContracts = $this->contractRepository->findAllWithSponsor();
            
            if (empty($allContracts)) {
                $io->warning('Aucun contrat trouvé dans la base de données.');
                $io->note('Créez un contrat via l\'interface web pour tester.');
                return Command::SUCCESS;
            }

            $now = new \DateTime('today');
            $rows = [];
            
            foreach ($allContracts as $contract) {
                $expiresAt = $contract->getExpiresAt();
                $signedAt = $contract->getSignedAt();
                
                // Calculer les jours jusqu'à expiration
                $daysUntilExpiry = $now->diff($expiresAt)->days;
                if ($expiresAt < $now) {
                    $daysUntilExpiry = -$daysUntilExpiry; // Négatif si déjà expiré
                }
                
                $status = 'Actif';
                if ($expiresAt < $now) {
                    $status = '⚠️ Expiré';
                } elseif ($daysUntilExpiry <= 7) {
                    $status = '🔴 Expire bientôt';
                } elseif ($daysUntilExpiry <= 30) {
                    $status = '🟡 Expire dans 30 jours';
                }

                $rows[] = [
                    $contract->getId(),
                    $contract->getContractNumber(),
                    $contract->getSponsor()?->getName() ?? 'N/A',
                    $signedAt ? $signedAt->format('d/m/Y') : 'N/A',
                    $expiresAt ? $expiresAt->format('d/m/Y') : 'N/A',
                    $daysUntilExpiry >= 0 ? $daysUntilExpiry . ' jours' : 'Expiré il y a ' . abs($daysUntilExpiry) . ' jours',
                    $status,
                ];
            }

            $io->table(
                ['ID', 'N° Contrat', 'Sponsor', 'Date signature', 'Date expiration', 'Jours restants', 'Statut'],
                $rows
            );
        } catch (\Exception $e) {
            $io->error('✗ Erreur lors de la récupération des contrats: ' . $e->getMessage());
            $io->note('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        // 4. Tester la requête findExpiringWithinDays avec différentes valeurs
        $io->section('4. Test de la requête findExpiringWithinDays');
        
        $testDays = [7, 30, 60, 90];
        $now = new \DateTime('today');
        
        foreach ($testDays as $days) {
            $limit = (new \DateTime('today'))->modify("+$days days")->setTime(23, 59, 59);
            
            $io->note(sprintf(
                'Recherche des contrats expirant entre %s et %s (%d jours)',
                $now->format('d/m/Y H:i:s'),
                $limit->format('d/m/Y H:i:s'),
                $days
            ));
            
            try {
                $expiringContracts = $this->contractRepository->findExpiringWithinDays($days);
                $io->info(sprintf('  → Trouvé %d contrat(s)', count($expiringContracts)));
                
                if (!empty($expiringContracts)) {
                    foreach ($expiringContracts as $contract) {
                        $io->text(sprintf(
                            '    • %s - %s (Expire le %s)',
                            $contract->getContractNumber(),
                            $contract->getSponsor()?->getName() ?? 'N/A',
                            $contract->getExpiresAt()->format('d/m/Y')
                        ));
                    }
                }
            } catch (\Exception $e) {
                $io->error(sprintf('  ✗ Erreur pour %d jours: %s', $days, $e->getMessage()));
            }
        }

        // 5. Vérifier la requête SQL générée
        $io->section('5. Requête SQL générée');
        try {
            $now = new \DateTime('today');
            $limit = (new \DateTime('today'))->modify('+7 days')->setTime(23, 59, 59);
            
            $query = $this->contractRepository->createQueryBuilder('c')
                ->leftJoin('c.sponsor', 's')
                ->addSelect('s')
                ->andWhere('c.expiresAt >= :now')
                ->andWhere('c.expiresAt <= :limit')
                ->setParameter('now', $now)
                ->setParameter('limit', $limit)
                ->orderBy('c.expiresAt', 'ASC')
                ->getQuery();
            
            $sql = $query->getSQL();
            $io->text('SQL:');
            $io->text($sql);
            $io->text('');
            $io->text('Paramètres:');
            $io->text(sprintf('  now: %s', $now->format('Y-m-d H:i:s')));
            $io->text(sprintf('  limit: %s', $limit->format('Y-m-d H:i:s')));
        } catch (\Exception $e) {
            $io->error('✗ Erreur lors de la génération de la requête SQL: ' . $e->getMessage());
        }

        $io->success('Test terminé avec succès!');
        return Command::SUCCESS;
    }
}

