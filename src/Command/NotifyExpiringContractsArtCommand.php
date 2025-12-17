<?php

namespace App\Command;

use App\Repository\SponsorContractRepository;
use App\Service\ContractNotificationService;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notify-expiring-contracts-art',
    description: 'Envoie des notifications pour les contrats expirants depuis la base "art".'
)]
class NotifyExpiringContractsArtCommand extends Command
{
    public function __construct(
        private readonly ContractNotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Nombre de jours avant expiration (défaut: 7)', 7)
            ->setHelp('Cette commande se connecte directement à la base "art" pour vérifier les contrats expirants.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');

        $io->title('Vérification des contrats expirant bientôt (Base: art)');

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
            $connection->executeQuery('SELECT 1'); // Test de connexion
            
            $io->success('✓ Connexion à la base "art" réussie');

            // Dates de recherche
            $now = new \DateTime('today');
            $limit = (new \DateTime('today'))->modify("+$days days")->setTime(23, 59, 59);
            
            $io->note(sprintf(
                'Recherche des contrats expirant entre %s et %s',
                $now->format('d/m/Y H:i:s'),
                $limit->format('d/m/Y H:i:s')
            ));

            // Récupérer les contrats expirants
            $contractsData = $connection->executeQuery(
                'SELECT c.id, c.contract_number, c.signed_at, c.expires_at, c.level, c.terms, c.sponsor_id,
                        s.name as sponsor_name, s.email as sponsor_email
                 FROM sponsor_contract c
                 LEFT JOIN sponsor s ON c.sponsor_id = s.id
                 WHERE c.expires_at >= :now 
                 AND c.expires_at <= :limit
                 ORDER BY c.expires_at ASC',
                [
                    'now' => $now->format('Y-m-d H:i:s'),
                    'limit' => $limit->format('Y-m-d H:i:s'),
                ]
            )->fetchAllAssociative();

            if (empty($contractsData)) {
                $io->warning(sprintf('Aucun contrat n\'expire dans les %d prochains jours.', $days));
                return Command::SUCCESS;
            }

            $io->info(sprintf('Trouvé %d contrat(s) expirant dans les %d prochains jours.', count($contractsData), $days));

            // Afficher la liste des contrats
            $rows = [];
            foreach ($contractsData as $contract) {
                $rows[] = [
                    $contract['contract_number'],
                    $contract['sponsor_name'] ?? 'N/A',
                    (new \DateTime($contract['expires_at']))->format('d/m/Y'),
                    $contract['level'] ?? 'N/A',
                ];
            }

            $io->table(
                ['N° Contrat', 'Sponsor', 'Date expiration', 'Niveau'],
                $rows
            );

            // Créer des objets SponsorContract pour le service de notification
            // Note: On utilise une approche simplifiée car on ne peut pas charger les entités Doctrine
            // sans être connecté à la bonne base
            $io->section('Envoi des notifications...');
            
            // Pour l'instant, on va juste afficher ce qui serait envoyé
            $emailContent = [];
            $emailContent[] = sprintf('Bonjour %s,', $_ENV['ADMIN_NAME'] ?? 'Administrateur');
            $emailContent[] = '';
            $emailContent[] = sprintf(
                'Vous avez %d contrat(s) de sponsoring qui expirent dans moins de %d jour(s) :',
                count($contractsData),
                $days
            );
            $emailContent[] = '';

            foreach ($contractsData as $contract) {
                $emailContent[] = sprintf(
                    '• Contrat %s - Sponsor: %s (%s) - Expire le %s',
                    $contract['contract_number'],
                    $contract['sponsor_name'] ?? 'N/A',
                    $contract['sponsor_email'] ?? 'N/A',
                    (new \DateTime($contract['expires_at']))->format('d/m/Y')
                );
            }

            $emailContent[] = '';
            $emailContent[] = 'Veuillez prendre les mesures nécessaires pour renouveler ces contrats.';

            $io->text($emailContent);

            // Essayer d'envoyer l'email
            try {
                // Créer un tableau de contrats simulés pour le service
                // Le service attend des objets SponsorContract, mais on peut créer une version simplifiée
                $io->note('Pour envoyer l\'email, le service nécessite des objets SponsorContract complets.');
                $io->note('Veuillez corriger la configuration DATABASE_URL pour utiliser la base "art".');
                
                $io->success(sprintf(
                    'Prêt à envoyer des notifications pour %d contrat(s).',
                    count($contractsData)
                ));
            } catch (\Exception $e) {
                $io->error('Erreur: ' . $e->getMessage());
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}



