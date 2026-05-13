<?php

return [
    'backup' => [
        'name' => env('APP_NAME', 'torex-guard'),

        'source' => [
            'files' => [
                'include' => [
                    base_path('storage/app'),
                    base_path('storage/quarantine'),
                    base_path('database/geoip'),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => true,
                'relative_path' => null,
            ],

            'databases' => ['mysql'],
        ],

        'database_dump_compressor'         => Spatie\DbDumper\Compressors\GzipCompressor::class,
        'database_dump_file_extension'     => '',
        'database_dump_file_timestamp_format' => null,

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level'  => 9,

            'filename_prefix' => env('BACKUP_FILENAME_PREFIX', 'torex-'),

            // 'local' writes to storage/app/<backup name>/. The 's3' disk is the
            // off-site target (configured under filesystems.disks.s3) -- in
            // production we run with destinations: ['s3'] only.
            'disks' => array_values(array_filter([
                'local',
                env('BACKUP_S3_DISK', 'backups'),
            ])),
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',

        'tries' => 1,
        'retry_delay' => 0,
    ],

    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class           => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class   => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class          => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class       => [],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class     => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class      => [],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,
        'mail' => [
            'to'   => env('BACKUP_NOTIFICATION_EMAIL') ?: (env('MAIL_FROM_ADDRESS') ?: 'ops@example.com'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS') ?: 'ops@example.com',
                'name'    => env('MAIL_FROM_NAME', 'Torex Guard'),
            ],
        ],
        'slack' => [
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK', ''),
            'channel'     => null,
            'username'    => null,
            'icon'        => null,
        ],
        'discord' => [
            'webhook_url' => '',
            'username'    => '',
            'avatar_url'  => '',
        ],
    ],

    'monitor_backups' => [
        [
            'name'              => env('APP_NAME', 'torex-guard'),
            'disks'             => array_values(array_filter([env('BACKUP_S3_DISK', 'backups')])),
            'health_checks'     => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class           => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class  => 50_000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days'             => 7,
            'keep_daily_backups_for_days'           => 16,
            'keep_weekly_backups_for_weeks'         => 8,
            'keep_monthly_backups_for_months'       => 4,
            'keep_yearly_backups_for_years'         => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 50_000,
        ],

        'tries'        => 5,
        'retry_delay'  => 30,
    ],
];
