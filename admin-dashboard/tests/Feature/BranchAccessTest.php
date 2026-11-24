<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class BranchAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_only_sees_assigned_branches(): void
    {
        [$mine, $other] = Branch::factory()->count(2)->create();
        $admin = User::factory()->create();
        $admin->branches()->attach($mine);

        $this->assertTrue($admin->canAccessBranch($mine));
        $this->assertFalse($admin->canAccessBranch($other));
        $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSee($other->name);
        $this->get('/admin?branch='.$other->id)->assertSessionHasErrors('branch');
    }

    public function test_super_admin_sees_every_branch(): void
    {
        [$a, $b] = Branch::factory()->count(2)->create();
        $super = User::factory()->superAdmin()->create();

        $this->assertTrue($super->canAccessBranch($b));
        $this->assertTrue(Gate::forUser($super)->allows('super-admin'));
        $this->actingAs($super)->get('/admin')->assertSee($a->name)->assertSee($b->name);
    }

    public function test_admin_is_not_super_admin(): void
    {
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('super-admin'));
    }

    public function test_role_and_active_flag_cannot_be_mass_assigned(): void
    {
        $user = new User(['name' => 'x', 'email' => 'x@x.test', 'password' => 'secret123']);

        $this->assertFalse($user->isFillable('role'));
        $this->assertFalse($user->isFillable('is_active'));
        $this->assertFalse($user->isSuperAdmin());
    }
}
