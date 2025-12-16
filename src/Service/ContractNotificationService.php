<?php

namespace App\Service;

use App\Entity\SponsorContract;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

class ContractNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly NotifierInterface $notifier,
        private readonly LoggerInterface $logger,
        private readonly string $adminEmail,
        private readonly ?string $adminPhone,
        private readonly ?string $adminName = 'Administrateur',
    ) {
    }

    /**
     * Envoie des notifications email pour les contrats expirant bientôt.
     *
     * @param SponsorContract[] $contracts
     * @param int $days
     */
    public function notifyExpiringContracts(array $contracts, int $days): void
    {
        if (empty($contracts)) {
            return;
        }

        // Préparer le contenu de l'email
        $emailContent = $this->buildEmailContent($contracts, $days);
        
        // Envoyer l'email
        $this->sendEmail($emailContent, $days);

        // Envoyer le SMS si le numéro est configuré (non vide)
        if ($this->adminPhone && $this->adminPhone !== '') {
            $this->sendSms($contracts, $days);
        }

        // Logger l'action
        $this->logger->info(sprintf(
            'Notifications envoyées pour %d contrat(s) expirant dans %d jour(s)',
            \count($contracts),
            $days
        ));
    }

    /**
     * Construit le contenu de l'email.
     */
    private function buildEmailContent(array $contracts, int $days): string
    {
        $lines = [];
        $lines[] = sprintf('Bonjour %s,', $this->adminName);
        $lines[] = '';
        $lines[] = sprintf(
            'Vous avez %d contrat(s) de sponsoring qui expirent dans moins de %d jour(s) :',
            \count($contracts),
            $days
        );
        $lines[] = '';

        foreach ($contracts as $contract) {
            $sponsor = $contract->getSponsor();
            $lines[] = sprintf(
                '• Contrat %s - Sponsor: %s (%s) - Expire le %s',
                $contract->getContractNumber(),
                $sponsor->getName(),
                $sponsor->getEmail(),
                $contract->getExpiresAt()->format('d/m/Y')
            );
        }

        $lines[] = '';
        $lines[] = 'Veuillez prendre les mesures nécessaires pour renouveler ces contrats.';
        $lines[] = '';
        $lines[] = 'Cordialement,';
        $lines[] = 'Système ARTEDU';

        return implode("\n", $lines);
    }

    /**
     * Envoie l'email de notification.
     */
    private function sendEmail(string $content, int $days): void
    {
        try {
            $email = (new Email())
                ->from('no-reply@artedu.com')
                ->to($this->adminEmail)
                ->subject(sprintf('⚠️ %d contrat(s) expirent bientôt', $days))
                ->text($content)
                ->html($this->buildHtmlEmail($content));

            $this->mailer->send($email);
            $this->logger->info(sprintf('Email de notification envoyé à %s', $this->adminEmail));
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Construit la version HTML de l'email.
     */
    private function buildHtmlEmail(string $textContent): string
    {
        $html = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
        $html .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';
        $html .= '<h2 style="color: #d9534f;">⚠️ Contrats expirant bientôt</h2>';
        $html .= '<div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">';
        $html .= nl2br(htmlspecialchars($textContent));
        $html .= '</div>';
        $html .= '</div></body></html>';
        
        return $html;
    }

    /**
     * Envoie un SMS de notification.
     */
    private function sendSms(array $contracts, int $days): void
    {
        try {
            $contractsCount = \count($contracts);
            $message = sprintf(
                'ARTEDU: %d contrat(s) expirent dans %d jour(s). Contrats: %s',
                $contractsCount,
                $days,
                implode(', ', array_map(fn($c) => $c->getContractNumber(), $contracts))
            );

            // Limiter à 160 caractères pour SMS
            if (\strlen($message) > 160) {
                $message = sprintf(
                    'ARTEDU: %d contrat(s) expirent dans %d jour(s). Vérifiez votre email.',
                    $contractsCount,
                    $days
                );
            }

            $notification = (new Notification($message, ['sms/vonage']))
                ->importance(Notification::IMPORTANCE_HIGH);

            $this->notifier->send($notification, new Recipient($this->adminPhone));
            
            $this->logger->info(sprintf('SMS de notification envoyé à %s', $this->adminPhone));
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du SMS: ' . $e->getMessage());
            // Ne pas bloquer si le SMS échoue, l'email est déjà envoyé
        }
    }
}

