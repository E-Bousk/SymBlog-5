<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UserCreatedFromDiscordOauthEvent extends Event
{
    public const SEND_EMAIL_WITH_PASSWORD = 'user_created_from_discord_oauth_event.send_email_with_password';
    
    private string $email;
    private string $randomPassword;

    public function __construct(string $email, string $randomPassword)
    {
        $this->email = $email;
        $this->randomPassword = $randomPassword;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRandomPassword(): string
    {
        return $this->randomPassword;
    }
}
