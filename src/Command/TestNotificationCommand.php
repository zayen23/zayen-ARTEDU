<?php

namespace App\Command;

use App\Entity\SponsorContract;
use App\Repository\SponsorContractRepository;
use App\Service\ContractNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-notification',
    description: 'Teste l\'envoi de notifications pour un contrat spécifique.'
)]
class TestNotificationCommand extends Command
{
    public function __construct(
        private readonly SponsorContractRepository $contractRepository,
        private readonly ContractNotificationService $notificationService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de notification');

        // Afficher tous les contrats
        $allContracts = $this->contractRepository->findAllWithSponsor();
        
        if (empty($allContracts)) {
            $io->warning('Aucun contrat trouvé dans la base de données.');
            $io->note('Créez d\'abord un contrat via l\'interface web.');
            return Command::SUCCESS;
        }

        $io->section('Contrats trouvés:');
        $rows = [];
        foreach ($allContracts as $contract) {
            $expiresAt = $contract->getExpiresAt();
            $now = new \DateTime('today');
            $daysUntilExpiry = $now->diff($expiresAt)->days;
            $rows[] = [
                $contract->getId(),
                $contract->getContractNumber(),
                $contract->getSponsor()?->getName() ?? 'N/A',
                $expiresAt->format('d/m/Y'),
                $daysUntilExpiry . ' jours',
            ];
        }
        $io->table(
            ['ID', 'N° Contrat', 'Sponsor', 'Date expiration', 'Jours restants'],
            $rows
        );

        // Trouver les contrats expirant dans 7 jours
        $expiringContracts = $this->contractRepository->findExpiringWithinDays(7);
        
        if (empty($expiringContracts)) {
            $io->warning('Aucun contrat n\'expire dans les 7 prochains jours.');
            return Command::SUCCESS;
        }

        $io->section('Envoi des notifications...');
        
        try {
            $this->notificationService->notifyExpiringContracts($expiringContracts, 7);
            $io->success(sprintf(
                'Notifications envoyées avec succès pour %d contrat(s).',
                count($expiringContracts)
            ));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            $io->note('Vérifiez les logs pour plus de détails.');
            return Command::FAILURE;
        }
    }
}


