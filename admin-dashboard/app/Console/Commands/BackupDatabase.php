<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Dump the MySQL database, gzip it and encrypt it with AES-256 (openssl).';

    public function handle(): int
    {
        $passphrase = config('backup.passphrase');
        if (blank($passphrase) || strlen($passphrase) < 16) {
            $this->error('Set BACKUP_PASSPHRASE (minimal 16 karakter) di .env.');

            return self::FAILURE;
        }

        $db = config('database.connections.'.config('database.default'));
        File::ensureDirectoryExists(config('backup.path'), 0700);
        $file = config('backup.path').'/zenova-'.now()->format('Ymd-His').'.sql.gz.enc';

        $command = sprintf(
            '%s --single-transaction --no-tablespaces -h %s -P %s -u %s %s | gzip | openssl enc -aes-256-cbc -pbkdf2 -salt -pass env:BACKUP_PASSPHRASE -out %s',
            escapeshellarg(config('backup.mysqldump')),
            escapeshellarg($db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg($db['username']),
            escapeshellarg($db['database']),
            escapeshellarg($file),
        );

        $process = new Process(['bash', '-o', 'pipefail', '-c', $command], null, [
            'MYSQL_PWD' => (string) $db['password'],
            'BACKUP_PASSPHRASE' => $passphrase,
        ], null, 3600);
        $process->run();

        if (! $process->isSuccessful()) {
            File::delete($file);
            $this->error('Backup gagal: '.trim($process->getErrorOutput()));

            return self::FAILURE;
        }

        chmod($file, 0600);
        $cutoff = now()->subDays(config('backup.keep_days'))->getTimestamp();
        collect(File::files(config('backup.path')))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.sql.gz.enc') && $f->getMTime() < $cutoff)
            ->each(fn ($f) => File::delete($f->getPathname()));

        $this->info('Backup tersimpan: '.$file);

        return self::SUCCESS;
    }
}
