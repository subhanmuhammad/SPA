<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL');
        $password = env('SUPERADMIN_PASSWORD');
        if (! $email || ! $password) {
            throw new RuntimeException('Set SUPERADMIN_EMAIL and SUPERADMIN_PASSWORD in .env before seeding.');
        }

        $superAdmin = User::firstOrNew(['email' => $email]);
        $superAdmin->fill(['name' => env('SUPERADMIN_NAME', 'Super Admin'), 'password' => $password]);
        $superAdmin->role = Role::SuperAdmin;
        $superAdmin->save();

        Branch::firstOrCreate(['slug' => 'cabang-utama'], [
            'name' => 'Zenova Spa [Nama Cabang]',
            'address' => '[Alamat cabang]',
            'phone' => '[Nomor telepon]',
        ]);

        $this->call([CatalogSeeder::class, PaymentMethodSeeder::class]);

        if (app()->isLocal()) {
            $this->call(DemoTherapistSeeder::class);
        }
    }
}
