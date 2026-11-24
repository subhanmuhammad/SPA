<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->super = User::factory()->superAdmin()->create();
        $this->branch = Branch::factory()->create();
    }

    public function test_admin_cannot_reach_super_admin_pages(): void
    {
        $admin = User::factory()->create();
        $admin->branches()->attach($this->branch);
        $this->actingAs($admin);

        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.branches.index'))->assertForbidden();
        $this->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->post(route('admin.users.store'), ['name' => 'X', 'email' => 'x@x.id', 'password' => 'abc12345', 'password_confirmation' => 'abc12345', 'role' => 'super_admin'])->assertForbidden();
        $this->assertFalse(User::where('email', 'x@x.id')->exists());
    }

    public function test_super_admin_creates_admin_with_branch_access(): void
    {
        $this->actingAs($this->super)->post(route('admin.users.store'), [
            'name' => 'Admin Cabang', 'email' => 'admin@zenova.test', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
            'role' => 'admin', 'is_active' => '1', 'branches' => [$this->branch->id],
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'admin@zenova.test')->sole();
        $this->assertSame(Role::Admin, $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertTrue($user->canAccessBranch($this->branch));
        $this->assertStringNotContainsString('rahasia123', json_encode(AuditLog::where('action', 'user.created')->sole()->getAttributes()));
    }

    public function test_admin_role_requires_a_branch_and_strong_password(): void
    {
        $this->actingAs($this->super)->post(route('admin.users.store'), [
            'name' => 'A', 'email' => 'a@zenova.test', 'password' => 'short', 'password_confirmation' => 'short', 'role' => 'admin',
        ])->assertSessionHasErrors(['branches', 'password']);
    }

    public function test_super_admin_cannot_demote_or_deactivate_self(): void
    {
        $this->actingAs($this->super)->put(route('admin.users.update', $this->super), [
            'name' => $this->super->name, 'email' => $this->super->email, 'role' => 'admin', 'is_active' => '1', 'branches' => [$this->branch->id],
        ])->assertSessionHasErrors('role');

        $this->assertTrue($this->super->fresh()->isSuperAdmin());
    }

    public function test_deactivating_admin_ends_sessions_and_is_audited(): void
    {
        $admin = User::factory()->create();
        $admin->branches()->attach($this->branch);
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($this->super)->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'admin', 'branches' => [$this->branch->id],
        ])->assertSessionHasNoErrors();

        $this->assertFalse($admin->fresh()->is_active);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $admin->id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.updated', 'subject_id' => $admin->id]);
    }

    public function test_new_branch_gets_unique_slug_and_active_treatments(): void
    {
        $active = Treatment::factory()->create();
        Treatment::factory()->create(['is_active' => false]);
        $payload = ['name' => 'Zenova Palu', 'opening_time' => '09:00', 'closing_time' => '21:00', 'is_active' => '1'];

        $this->actingAs($this->super)->post(route('admin.branches.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('admin.branches.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('admin.branches.store'), [...$payload, 'closing_time' => '08:00'])->assertSessionHasErrors('closing_time');

        $this->assertSame(['zenova-palu', 'zenova-palu-2'], Branch::where('name', 'Zenova Palu')->orderBy('id')->pluck('slug')->all());
        $this->assertSame([$active->id], Branch::where('slug', 'zenova-palu')->sole()->treatments()->pluck('treatments.id')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'branch.created']);
    }

    public function test_audit_log_page_filters_by_user(): void
    {
        AuditLog::create(['action' => 'auth.login', 'user_id' => $this->super->id]);

        $this->actingAs($this->super)->get(route('admin.audit-logs.index', ['user' => $this->super->id]))->assertOk()->assertSee('auth.login');
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeaderMissing('Content-Security-Policy');

        $this->app['env'] = 'production';
        $this->get('/')->assertHeader('Content-Security-Policy');
    }
}
