<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{from:string,to:string,threats:int,critical:int,active:int,resolved:int,servers_online:int,servers_offline:int}  $stats
     */
    public function __construct(public array $stats)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $settings = $notifiable->organizations->first()?->notificationSetting;
        if ($settings && $settings->email_enabled
            && in_array('weekly_digest', $settings->notify_on ?? ['weekly_digest'], true)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->stats;
        return (new MailMessage)
            ->subject('Torex Guard weekly digest — '.$s['from'].' to '.$s['to'])
            ->line('Threats detected: '.$s['threats'])
            ->line('Critical: '.$s['critical'])
            ->line('Still active: '.$s['active'])
            ->line('Resolved: '.$s['resolved'])
            ->line('Servers online: '.$s['servers_online'].' / offline: '.$s['servers_offline'])
            ->action('Open dashboard', url('/'));
    }

    public function toArray(object $notifiable): array
    {
        return array_merge(['type' => 'weekly_digest'], $this->stats);
    }
}
