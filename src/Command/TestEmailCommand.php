<?php

namespace App\Command;

use App\Service\ContractNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-email',
    description: 'Teste l\'envoi d\'un email de notification.'
)]
class TestEmailCommand extends Command
{
    public function __construct(
        private readonly ContractNotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test d\'envoi d\'email');

        try {
            $io->info('Envoi d\'un email de test...');
            
            $this->notificationService->testEmail();
            
            $io->success('Email de test envoyé avec succès !');
            $io->note('Vérifiez votre boîte de réception (et les spams).');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
            $io->note('Vérifiez votre configuration MAILER_DSN dans le fichier .env');
            return Command::FAILURE;
        }
    }
}

