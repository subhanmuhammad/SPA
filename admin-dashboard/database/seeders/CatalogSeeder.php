<?php

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Models\Branch;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    private const CATALOG = [
        'pijat' => ['Pijat', ServiceType::InStore, [
            ['Pijat 60 Menit', '5 titik.', 60, 115000],
            ['Pijat 90 Menit', 'Full body + totok wajah + pijat kepala.', 90, 150000],
            ['Pijat 120 Menit', 'Full body + totok wajah + pijat kepala.', 120, 190000],
        ]],
        'perawatan-tubuh' => ['Perawatan Tubuh', ServiceType::InStore, [
            ['Lulur', null, null, 100000],
            ['Creambath', 'Termasuk pijat kepala, bahu & tangan + gratis hair dryer.', null, 100000],
            ['Totok Wajah + Masker', null, null, 70000],
            ['Ear Candle', 'Terapi telinga.', null, 45000],
            ['Callus + Foot Spa', 'Membersihkan kapalan kaki yang tebal + kuku + pijatan telapak kaki hingga lutut + scrub.', null, 145000],
            ['Foot Spa', 'Pijatan telapak kaki hingga lutut + scrub.', null, 80000],
            ['Sauna', null, null, 75000],
        ]],
        'paket-combo' => ['Paket Combo', ServiceType::InStore, [
            ['Lulur + Sauna', null, 90, 165000, ['Lulur' => 'Lulur', 'Sauna' => 'Sauna']],
            ['Lulur + Pijat 90 Menit', null, null, 200000, ['Lulur' => 'Lulur', 'Pijat 90 Menit' => 'Pijat 90 Menit']],
            ['Lulur + Pijat 120 Menit', null, null, 240000, ['Lulur' => 'Lulur', 'Pijat 120 Menit' => 'Pijat 120 Menit']],
        ]],
        'paket-tambahan' => ['Paket Tambahan', ServiceType::InStore, [
            ['Pijat 60 Menit + Keretek', null, null, 150000, ['Pijat 60 Menit' => 'Pijat 60 Menit', 'Keretek' => null]],
            ['Pijat 90 Menit + Keretek', null, null, 240000, ['Pijat 90 Menit' => 'Pijat 90 Menit', 'Keretek' => null]],
            ['Pijat 120 Menit + Keretek + Ear Candle', null, null, 325000, ['Pijat 120 Menit' => 'Pijat 120 Menit', 'Keretek' => null, 'Ear Candle' => 'Ear Candle']],
        ]],
        TreatmentCategory::PROMO => ['Paket Promo Zenova', ServiceType::InStore, [
            ['Paket Healing', null, 100, 180000, ['Pijat' => null, 'Ear Candle' => 'Ear Candle', 'Sauna' => 'Sauna'], 225000],
            ['Paket Me Time', null, 120, 195000, ['Foot Spa' => 'Foot Spa', 'Sauna' => 'Sauna', 'Creambath' => 'Creambath'], 250000],
            ['Paket Sultan', null, 150, 270000, ['Pijat' => null, 'Totok Wajah + Masker' => 'Totok Wajah + Masker', 'Lulur' => 'Lulur', 'Sauna' => 'Sauna'], 295000],
            ['Paket Relaksasi Premium', null, 180, 305000, ['Pijat' => null, 'Lulur' => 'Lulur', 'Sauna' => 'Sauna', 'Creambath' => 'Creambath'], 455000],
        ]],
        'home-care' => ['Home Care', ServiceType::HomeCare, [
            ['Home Care Pijat 90 Menit', 'Full body + totok wajah.', 90, 160000],
            ['Home Care Pijat 120 Menit', 'Full body + totok wajah.', 120, 200000],
            ['Home Care Lulur + Pijat 120 Menit', null, 120, 265000, ['Lulur' => 'Home Care Lulur', 'Pijat 120 Menit' => 'Home Care Pijat 120 Menit']],
            ['Home Care Lulur', null, null, 120000],
            ['Home Care Totok Wajah + Masker', null, null, 85000],
            ['Home Care Ear Candle', null, null, 50000],
        ]],
    ];

    public function run(): void
    {
        $categoryOrder = 0;
        $packages = [];

        foreach (self::CATALOG as $slug => [$categoryName, $type, $rows]) {
            $category = TreatmentCategory::updateOrCreate(['slug' => $slug], [
                'name' => $categoryName,
                'sort_order' => ++$categoryOrder,
                ...($slug === TreatmentCategory::PROMO ? [
                    'tagline' => 'Relaks • Segar • Percaya Diri',
                    'description' => 'Nikmati rangkaian perawatan pilihan untuk membantu tubuh lebih rileks, pikiran lebih tenang, dan kulit tampak lebih sehat.',
                ] : []),
            ]);

            foreach ($rows as $order => $row) {
                $treatment = Treatment::firstOrNew(['name' => $row[0], 'treatment_category_id' => $category->id]);
                $treatment->fill([
                    'description' => $row[1],
                    'service_type' => $type,
                    'duration_minutes' => $row[2],
                    'price' => $row[3],
                    'normal_price' => $row[5] ?? null,
                    'sort_order' => $order + 1,
                ])->save();

                if (isset($row[4])) {
                    $packages[] = [$treatment, $row[4]];
                }
            }
        }

        $byName = Treatment::pluck('id', 'name');
        foreach ($packages as [$package, $items]) {
            $package->items()->delete();
            $order = 0;
            foreach ($items as $label => $treatmentName) {
                $package->items()->create([
                    'name' => $label,
                    'treatment_id' => $treatmentName ? $byName[$treatmentName] : null,
                    'sort_order' => ++$order,
                ]);
            }
        }

        Branch::each(fn (Branch $branch) => $branch->treatments()->syncWithoutDetaching($byName->values()));
    }
}
