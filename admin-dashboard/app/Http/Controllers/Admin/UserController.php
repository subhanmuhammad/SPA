<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::with('branches:id,name')->orderBy('name')->paginate(25)]);
    }

    public function create(): View
    {
        return $this->form(new User);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = new User($request->safe()->only(['name', 'email', 'password']));
            $this->applyAccess($request, $user);
            AuditLog::record('user.created', $user, ['email' => $user->email, 'role' => $user->role->value, 'branches' => $user->branches()->pluck('branches.id')]);

            return $user;
        });

        return redirect()->route('admin.users.edit', $user)->with('status', 'Akun dibuat.');
    }

    public function edit(User $user): View
    {
        return $this->form($user->load('branches:id'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $user->fill($request->safe()->only(['name', 'email']));
            $passwordChanged = filled($request->validated('password'));
            if ($passwordChanged) {
                $user->password = $request->validated('password');
            }
            $before = ['role' => $user->role->value, 'is_active' => $user->is_active, 'branches' => $user->branches()->pluck('branches.id')->all()];
            $changed = array_keys(collect($user->getDirty())->except(['password', 'updated_at'])->all());

            $this->applyAccess($request, $user);

            if ($passwordChanged || ! $user->is_active || $before['role'] !== $user->role->value) {
                $this->endSessions($user);
            }

            AuditLog::record('user.updated', $user, [
                'fields' => $changed,
                'password_changed' => $passwordChanged,
                'before' => $before,
                'after' => ['role' => $user->role->value, 'is_active' => $user->is_active, 'branches' => $user->branches()->pluck('branches.id')->all()],
            ]);
        });

        return redirect()->route('admin.users.edit', $user)->with('status', 'Akun disimpan.');
    }

    private function applyAccess(UserRequest $request, User $user): void
    {
        $user->role = Role::from($request->validated('role'));
        $user->is_active = $request->validated('is_active');
        $user->save();
        $user->branches()->sync($user->isSuperAdmin() ? [] : $request->validated('branches', []));
    }

    private function endSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
    }

    private function form(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'selected' => $user->exists ? $user->branches->pluck('id')->all() : [],
        ]);
    }
}
