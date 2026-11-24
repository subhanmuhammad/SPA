<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['boolean'],
            'branches' => [Rule::requiredIf($this->input('role') === Role::Admin->value), 'array'],
            'branches.*' => ['integer', Rule::exists('branches', 'id')->whereNull('deleted_at')],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'role' => 'peran', 'branches' => 'akses cabang'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');
            if ($user && $user->is($this->user()) && ($this->input('role') !== $user->role->value || ! $this->boolean('is_active'))) {
                $validator->errors()->add('role', 'Anda tidak bisa mengubah peran atau menonaktifkan akun sendiri.');
            }
        });
    }
}
