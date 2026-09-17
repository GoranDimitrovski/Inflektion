<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvitationReceived extends Notification
{
    public function __construct(
        private readonly Invitation $invitation,
        private readonly string $plainTextToken,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('services.frontend_url'), '/');
        $url = "{$frontendUrl}/invitations/{$this->plainTextToken}";

        return (new MailMessage)
            ->subject("You've been invited to join {$this->invitation->account->name}")
            ->line("You've been invited to join {$this->invitation->account->name} as {$this->invitation->role->value}.")
            ->action('Accept invitation', $url)
            ->line('This invitation expires in 7 days.');
    }
}
