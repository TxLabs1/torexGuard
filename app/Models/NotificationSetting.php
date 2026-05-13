<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'email_enabled',
        'slack_enabled',
        'slack_webhook',
        'webhook_enabled',
        'webhook_url',
        'notify_on',
        'severity_threshold',
    ];

    protected $casts = [
        'email_enabled'    => 'boolean',
        'slack_enabled'    => 'boolean',
        'webhook_enabled'  => 'boolean',
        'notify_on'        => 'array',
        'severity_threshold' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
