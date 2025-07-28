<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
        private readonly string $fromEmail = 'noreply@citoyennote.fr'
    ) {}

    public function sendWelcomeEmail(User $user, string $tempPassword): bool
    {
        try {
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($user->getEmail())
                ->subject('Bienvenue sur CitoyenNote')
                ->html($this->twig->render('emails/welcome.html.twig', [
                    'user' => $user,
                    'tempPassword' => $tempPassword,
                    'loginUrl' => 'https://votre-site.com/login'
                ]));

            $this->mailer->send($email);
            $this->logger->info('Email de bienvenue envoyé', ['user_id' => $user->getId()]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email de bienvenue', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}
