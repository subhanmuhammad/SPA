<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_homepage_is_public_and_lists_active_branches_only(): void
    {
        Branch::factory()->create(['name' => 'Cabang Aktif']);
        Branch::factory()->create(['name' => 'Cabang Tutup', 'is_active' => false]);

        $this->get('/')->assertOk()->assertSee('Cabang Aktif')->assertDontSee('Cabang Tutup');
    }

    public function test_guest_is_redirected_from_admin_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_admin_can_log_in_and_login_is_audited(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
    }

    public function test_session_is_regenerated_on_login(): void
    {
        $user = User::factory()->create();
        $this->startSession();
        $before = session()->getId();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_wrong_password_fails_and_is_audited_without_the_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'salah-sekali'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $log = AuditLog::where('action', 'auth.login_failed')->sole();
        $this->assertSame(['email' => $user->email], $log->data);
        $this->assertStringNotContainsString('salah-sekali', json_encode($log->getAttributes()));
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 5) as $_) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.logout', 'user_id' => $user->id]);
    }

    public function test_logged_in_user_visiting_login_goes_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get(route('login'))->assertRedirect('/admin');
    }
}
