<?php

return [
    'mysqldump' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
    'passphrase' => env('BACKUP_PASSPHRASE'),
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),
    'path' => storage_path('app/backups'),
];
