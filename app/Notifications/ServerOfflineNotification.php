<?php

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerOfflineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Server $server)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $settings = $notifiable->organizations->first()?->notificationSetting;
        if ($settings && $settings->email_enabled
            && in_array('server_offline', $settings->notify_on ?? ['server_offline'], true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Server offline: '.$this->server->hostname)
            ->line('Heartbeat has gone stale for '.$this->server->hostname.'.')
            ->line('Last seen: '.optional($this->server->last_heartbeat_at)->diffForHumans())
            ->action('Open server', url('/servers/'.$this->server->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'server_offline',
            'server_id' => $this->server->id,
            'hostname' => $this->server->hostname,
            'url' => '/servers/'.$this->server->id,
        ];
    }
}
