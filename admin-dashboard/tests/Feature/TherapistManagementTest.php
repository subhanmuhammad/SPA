<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Therapist;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistManagementTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->branches()->attach($this->branch);
    }

    public function test_admin_creates_therapist_with_skills_and_schedule(): void
    {
        $treatment = Treatment::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.therapists.store'), [
            'branch_id' => $this->branch->id,
            'name' => 'Ayu',
            'is_active' => '1',
            'treatments' => [$treatment->id],
            'schedules' => [
                1 => ['active' => '1', 'start_time' => '09:00', 'end_time' => '17:00'],
                2 => ['active' => '0', 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ])->assertSessionHasNoErrors();

        $therapist = Therapist::sole();
        $this->assertSame([$treatment->id], $therapist->treatments()->pluck('treatments.id')->all());
        $this->assertSame([1], $therapist->schedules()->pluck('day_of_week')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'therapist.created']);
    }

    public function test_schedule_end_must_be_after_start(): void
    {
        $this->actingAs($this->admin)->post(route('admin.therapists.store'), [
            'branch_id' => $this->branch->id,
            'name' => 'Ayu',
            'schedules' => [1 => ['active' => '1', 'start_time' => '17:00', 'end_time' => '09:00']],
        ])->assertSessionHasErrors('schedules.1.end_time');
    }

    public function test_admin_cannot_place_or_edit_therapist_in_other_branch(): void
    {
        $other = Branch::factory()->create();
        $foreign = Therapist::factory()->at($other)->create();
        $this->actingAs($this->admin);

        $this->post(route('admin.therapists.store'), ['branch_id' => $other->id, 'name' => 'X'])->assertSessionHasErrors('branch_id');
        $this->get(route('admin.therapists.edit', $foreign))->assertForbidden();
        $this->post(route('admin.therapists.leaves.store', $foreign), ['date' => now()->addDay()->toDateString()])->assertForbidden();
        $this->get(route('admin.therapists.index'))->assertDontSee($foreign->name);
    }

    public function test_leave_can_be_added_and_removed(): void
    {
        $therapist = Therapist::factory()->at($this->branch)->create();
        $this->actingAs($this->admin);

        $this->post(route('admin.therapists.leaves.store', $therapist), ['date' => now()->addDay()->toDateString(), 'reason' => 'Cuti'])->assertSessionHasNoErrors();
        $leave = $therapist->leaves()->sole();

        $this->delete(route('admin.therapists.leaves.destroy', [$therapist, $leave]))->assertRedirect();
        $this->assertSame(0, $therapist->leaves()->count());
    }
}
