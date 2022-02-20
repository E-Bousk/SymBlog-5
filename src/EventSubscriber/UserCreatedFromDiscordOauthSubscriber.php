<?php

namespace App\EventSubscriber;

use App\Service\SendEmail;
use Psr\Log\LoggerInterface;
use App\Event\UserCreatedFromDiscordOauthEvent;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserCreatedFromDiscordOauthSubscriber implements EventSubscriberInterface
{
    private SendEmail $sendEmail;
    private LoggerInterface $discordOauthLogger;
    private UserRepository $userRepository;

    public function __construct(SendEmail $sendEmail, LoggerInterface $discordOauthLogger, UserRepository $userRepository)
    {
        $this->sendEmail = $sendEmail;
        $this->discordOauthLogger = $discordOauthLogger;
        $this->userRepository = $userRepository;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedFromDiscordOauthEvent::SEND_EMAIL_WITH_PASSWORD => 'sendEmailWithPassword'
        ];
    }

    public function sendEmailWithPassword(UserCreatedFromDiscordOauthEvent $event): void
    {
        $email = $event->getEmail();
        $randomPassord = $event->getRandomPassword();
        
        $user = $this->userRepository->findOneBy([
            'email' => $email
        ]);

        $this->sendEmail->send([
            'recipient_email'     => $email,
            'subject'             => 'Compte utilisateur créé suite à une authentification vis Discord OAuth',
            'html_template'       => 'registration/register_email_discord_oauth.html.twig',
            'context' => [
                'randomPassword'  => $randomPassord,
                'discordUsername' => $user->getDiscordUsername()
            ]
        ]);

        $this->discordOauthLogger->info("L'utilisateur ayant l'adresse e-mail « {$email} » s'est inscrit via Discord OAuth. Un mot de passe aléatoire à modifier lui a été envoyé par e-mail.");
    }
}
