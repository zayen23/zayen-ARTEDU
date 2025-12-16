<?php

namespace App\Command;

use App\Repository\SponsorContractRepository;
use App\Service\ContractNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notify-expiring-contracts',
    description: 'Envoie des notifications email et SMS aux administrateurs pour les contrats qui expirent bientôt.'
)]
class NotifyExpiringContractsCommand extends Command
{
    public function __construct(
        private readonly SponsorContractRepository $contractRepository,
        private readonly ContractNotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Nombre de jours avant expiration (défaut: 7)', 7)
            ->setHelp('Cette commande vérifie les contrats qui expirent bientôt et envoie des notifications aux administrateurs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');

        $io->title('Vérification des contrats expirant bientôt');

        $contracts = $this->contractRepository->findExpiringWithinDays($days);

        if (\count($contracts) === 0) {
            $io->success(sprintf('Aucun contrat n\'expire dans les %d prochains jours.', $days));
            return Command::SUCCESS;
        }

        $io->info(sprintf('Trouvé %d contrat(s) expirant dans les %d prochains jours.', \count($contracts), $days));

        // Afficher la liste des contrats
        $rows = [];
        foreach ($contracts as $contract) {
            $rows[] = [
                $contract->getContractNumber(),
                $contract->getSponsor()->getName(),
                $contract->getExpiresAt()->format('d/m/Y'),
                $contract->getLevel()?->value ?? 'N/A',
            ];
        }

        $io->table(
            ['N° Contrat', 'Sponsor', 'Date expiration', 'Niveau'],
            $rows
        );

        // Envoyer les notifications
        $io->section('Envoi des notifications...');

        try {
            $this->notificationService->notifyExpiringContracts($contracts, $days);
            
            $io->success(sprintf(
                'Notifications envoyées avec succès pour %d contrat(s).',
                \count($contracts)
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'envoi des notifications: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

