<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_catalog_shows_promo_with_strikethrough_and_savings(): void
    {
        $branch = Branch::factory()->create();
        $this->seed(CatalogSeeder::class);

        $this->assertSame(26, Treatment::count());
        $this->assertSame(26, $branch->treatments()->count());

        $this->get('/')->assertOk()
            ->assertSee('Paket Promo Zenova')
            ->assertSee('Paket Relaksasi Premium')
            ->assertSeeInOrder(['Rp', '455.000', 'Rp', '305.000', 'Hemat', '150.000'])
            ->assertSee('Home Care Pijat 90 Menit');
    }

    public function test_branch_price_override_is_shown(): void
    {
        $branch = Branch::factory()->create();
        $treatment = Treatment::factory()->create(['name' => 'Pijat Khusus', 'price' => 100000]);
        $treatment->branches()->attach($branch, ['price' => 135000]);

        $this->get('/')->assertSee('135.000')->assertDontSee('100.000');
        $this->get(route('treatments.show', $treatment->slug))->assertOk()->assertSee('135.000');
    }

    public function test_hidden_treatments_are_not_shown(): void
    {
        $branch = Branch::factory()->create();
        $inactive = Treatment::factory()->create(['name' => 'Layanan Nonaktif', 'is_active' => false]);
        $unavailable = Treatment::factory()->create(['name' => 'Tidak Tersedia']);
        $expired = Treatment::factory()->create(['name' => 'Promo Lewat', 'promo_ends_on' => now()->subDay()]);
        $future = Treatment::factory()->create(['name' => 'Promo Nanti', 'promo_starts_on' => now()->addDay()]);
        $branch->treatments()->attach([$inactive->id, $expired->id, $future->id]);
        $branch->treatments()->attach($unavailable, ['is_available' => false]);

        $this->get('/')->assertOk()
            ->assertDontSee('Layanan Nonaktif')->assertDontSee('Tidak Tersedia')
            ->assertDontSee('Promo Lewat')->assertDontSee('Promo Nanti');
        $this->get(route('treatments.show', $inactive->slug))->assertNotFound();
    }

    public function test_treatment_text_is_escaped(): void
    {
        $branch = Branch::factory()->create();
        $treatment = Treatment::factory()->create(['name' => '<script>alert(1)</script>']);
        $treatment->branches()->attach($branch);

        $this->get('/')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_photo_credits_live_on_their_own_page(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Kredit foto')->assertDontSee('Basile Morin');
        $this->get(route('photo-credits'))->assertOk()->assertSee('Basile Morin')->assertSee('CC BY-SA 4.0');
    }

    public function test_promo_category_has_tagline(): void
    {
        Branch::factory()->create();
        $this->seed(CatalogSeeder::class);

        $this->assertSame('Relaks • Segar • Percaya Diri', TreatmentCategory::where('slug', TreatmentCategory::PROMO)->value('tagline'));
    }
}
