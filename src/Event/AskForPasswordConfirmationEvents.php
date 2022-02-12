<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class AskForPasswordConfirmationEvents extends Event
{
    public const MODAL_DISPLAY = 'ask_for_password_confirmation_events.modal_display';
    public const PASSWORD_INVALID = 'ask_for_password_confirmation_events.password_invalid';
    public const SESSION_INVALIDATE = 'ask_for_password_confirmation_events.session_invalidate';
}