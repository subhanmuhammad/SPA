<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::in($this->user()->accessibleBranches()->pluck('branches.id'))],
            'treatment_id' => ['required', 'integer'],
            'area_id' => ['nullable', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'therapist_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'address_notes' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_id' => 'cabang', 'treatment_id' => 'layanan', 'area_id' => 'area', 'date' => 'tanggal', 'time' => 'jam',
            'therapist_id' => 'terapis', 'name' => 'nama', 'phone' => 'nomor HP', 'address' => 'alamat',
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Nomor HP harus nomor ponsel Indonesia, contoh 081234567890.'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => preg_replace('/[\s\-]/', '', (string) $this->input('phone'))]);
    }

    public function phone(): string
    {
        return Customer::normalizePhone($this->validated('phone'));
    }
}
