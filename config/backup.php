<?php

return [

    'backup' => [

        'name' => 'backups',

        'source' => [

            // ── Fichiers : aucun (base de données uniquement) ──────────────────
            'files' => [
                'include' => [],
                'exclude' => [],
                'follow_links'              => false,
                'ignore_unreadable_dirs'    => false,
                'relative_path'             => null,
            ],

            // ── Base de données : connexion par défaut ─────────────────────────
            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        // ── Chemin mysqldump (requis sur Windows si non présent dans le PATH) ──
        'database_dump_compressor' => null,
        'database_dump_file_extension' => '',

        // Chemin vers le répertoire contenant mysqldump (Windows : doit se terminer par \)
        'database_dump_binary_path' => env('MYSQLDUMP_PATH', ''),

        'destination' => [
            'filename_prefix' => '',
            'disks' => [
                'google',
            ],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD', null),
        'encryption' => 'default',

        'tries' => 1,
        'retry_delay' => 0,
    ],

    // ── Notifications ─────────────────────────────────────────────────────────
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class         => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class        => [],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class     => [],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class   => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class    => [],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFY_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@example.com')),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'admin@example.com'),
                'name'    => env('MAIL_FROM_NAME', 'Magic Hotels Backup'),
            ],
        ],
        'slack' => [
            'webhook_url' => '',
            'channel'     => null,
            'username'    => null,
            'icon'        => null,
        ],
        'discord' => ['webhook_url' => ''],
    ],

    // ── Monitoring ────────────────────────────────────────────────────────────
    'monitor_backups' => [
        [
            'name'          => 'backups',
            'disks'         => ['google'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class          => 2,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 20480,
            ],
        ],
    ],

    // ── Cleanup : DÉSACTIVÉ — aucune suppression automatique ─────────────────
    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days'                    => 36500,
            'keep_daily_backups_for_days'                  => 36500,
            'keep_weekly_backups_for_weeks'                => 999999,
            'keep_monthly_backups_for_months'              => 999999,
            'keep_yearly_backups_for_years'                => 999999,
            'delete_oldest_backups_when_using_more_megabytes_than' => 999999999,
        ],
    ],

];
