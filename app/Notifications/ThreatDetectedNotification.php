<?php

namespace App\Notifications;

use App\Models\Threat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ThreatDetectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Threat $threat)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $settings = $notifiable->organizations->first()?->notificationSetting;
        if ($settings && $settings->email_enabled
            && $this->severityAllowed($settings, $this->threat->severity, 'threat_detected')) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/threats/'.$this->threat->id);
        return (new MailMessage)
            ->subject('['.strtoupper($this->threat->severity).'] Threat detected: '.$this->threat->title)
            ->greeting('Threat detected on '.($this->threat->server->hostname ?? 'unknown server'))
            ->line('Type: '.$this->threat->type)
            ->line('Severity: '.$this->threat->severity)
            ->line('File: '.($this->threat->file_path ?? 'n/a'))
            ->action('View threat', $url)
            ->line('Open the Torex Guard dashboard to triage.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'threat_detected',
            'threat_id' => $this->threat->threat_id,
            'severity'  => $this->threat->severity,
            'title'     => $this->threat->title,
            'project_id'=> $this->threat->project_id,
            'server_id' => $this->threat->server_id,
            'url'       => '/threats/'.$this->threat->id,
        ];
    }

    private function severityAllowed($settings, string $severity, string $event): bool
    {
        $notifyOn = $settings->notify_on ?? [];
        if (! empty($notifyOn) && ! in_array($event, $notifyOn, true)) {
            return false;
        }
        $threshold = $settings->severity_threshold[$event] ?? 'medium';
        $weights = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        return ($weights[$severity] ?? 0) >= ($weights[$threshold] ?? 2);
    }
}
