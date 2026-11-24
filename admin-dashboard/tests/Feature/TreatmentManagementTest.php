<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TreatmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->branch = Branch::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->branches()->attach($this->branch);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'treatment_category_id' => TreatmentCategory::firstOrCreate(['slug' => 'pijat'], ['name' => 'Pijat'])->id,
            'name' => 'Pijat Refleksi',
            'service_type' => 'in_store',
            'duration_minutes' => 60,
            'price' => 120000,
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_guest_cannot_open_treatment_admin(): void
    {
        $this->get(route('admin.treatments.index'))->assertRedirect(route('login'));
    }

    public function test_admin_creates_treatment_with_photo_and_components(): void
    {
        $sauna = Treatment::factory()->create(['name' => 'Sauna']);

        $this->actingAs($this->admin)->post(route('admin.treatments.store'), $this->payload([
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'items' => [['name' => 'Sauna', 'treatment_id' => $sauna->id], ['name' => 'Keretek', 'treatment_id' => ''], ['name' => '', 'treatment_id' => '']],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $treatment = Treatment::where('name', 'Pijat Refleksi')->sole();
        $this->assertSame('pijat-refleksi', $treatment->slug);
        Storage::disk('public')->assertExists($treatment->photo_path);
        $this->assertNotSame('foto.jpg', basename($treatment->photo_path));
        $this->assertSame(['Sauna', 'Keretek'], $treatment->items->pluck('name')->all());
        $this->assertTrue($this->branch->treatments()->whereKey($treatment->id)->exists());
        $this->assertDatabaseHas('audit_logs', ['action' => 'treatment.created', 'subject_id' => $treatment->id]);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.treatments.store'), $this->payload([
            'photo' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
        ]))->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('treatments', 0);
    }

    public function test_normal_price_must_exceed_promo_price(): void
    {
        $this->actingAs($this->admin)->post(route('admin.treatments.store'), $this->payload(['normal_price' => 100000]))
            ->assertSessionHasErrors('normal_price');
    }

    public function test_price_change_is_audited_with_old_and_new_value(): void
    {
        $treatment = Treatment::factory()->create(['price' => 100000]);

        $this->actingAs($this->admin)->put(route('admin.treatments.update', $treatment), $this->payload(['name' => $treatment->name, 'price' => 125000]));

        $this->assertSame(125000, $treatment->fresh()->price);
        $log = AuditLog::where('action', 'treatment.updated')->sole();
        $this->assertSame(['from' => 100000, 'to' => 125000], $log->data['price']);
    }

    public function test_admin_cannot_change_config_of_other_branch(): void
    {
        $other = Branch::factory()->create();
        $treatment = Treatment::factory()->create();
        $treatment->branches()->attach([$this->branch->id, $other->id]);

        $this->actingAs($this->admin)->put(route('admin.treatments.branches.update', $treatment), [
            'branches' => [
                $this->branch->id => ['is_available' => '1', 'price' => 90000],
                $other->id => ['is_available' => '0', 'price' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('branch_treatments', ['branch_id' => $this->branch->id, 'treatment_id' => $treatment->id, 'price' => 90000]);
        $this->assertDatabaseHas('branch_treatments', ['branch_id' => $other->id, 'treatment_id' => $treatment->id, 'price' => null, 'is_available' => true]);
    }

    public function test_search_with_sql_wildcards_is_safe(): void
    {
        Treatment::factory()->create(['name' => 'Lulur']);

        $this->actingAs($this->admin)->get(route('admin.treatments.index', ['q' => "%' OR 1=1 --"]))
            ->assertOk()->assertDontSee('Lulur');
    }
}
