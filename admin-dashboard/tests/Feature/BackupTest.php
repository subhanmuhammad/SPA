<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BackupTest extends TestCase
{
    public function test_backup_requires_strong_passphrase(): void
    {
        config(['backup.passphrase' => 'pendek']);

        $this->artisan('backup:database')->assertFailed();
    }

    public function test_backup_is_encrypted_and_restorable(): void
    {
        $dump = '/Applications/XAMPP/xamppfiles/bin/mysqldump';
        if (! is_executable($dump)) {
            $this->markTestSkipped('mysqldump XAMPP tidak tersedia.');
        }
        $dir = storage_path('framework/testing/backups');
        File::deleteDirectory($dir);
        config(['backup.mysqldump' => $dump, 'backup.passphrase' => 'kata-sandi-uji-yang-panjang', 'backup.path' => $dir]);

        $this->artisan('backup:database')->assertSuccessful();

        $file = File::files($dir)[0]->getPathname();
        $this->assertStringNotContainsString('CREATE TABLE', File::get($file));
        $decrypt = Process::fromShellCommandline('openssl enc -d -aes-256-cbc -pbkdf2 -pass env:P -in "$F" | gunzip', null, ['P' => 'kata-sandi-uji-yang-panjang', 'F' => $file]);
        $decrypt->mustRun();
        $this->assertStringContainsString('CREATE TABLE `bookings`', $decrypt->getOutput());

        File::deleteDirectory($dir);
    }
}
