<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Cash', 'Transfer Bank', 'QRIS'] as $i => $name) {
            PaymentMethod::firstOrCreate(['name' => $name], ['sort_order' => $i + 1]);
        }
    }
}
