<?php

namespace App\Http\Requests;

use App\Enums\ServiceType;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cabang' => ['required', 'string', 'max:120'],
            'tipe' => ['required', Rule::enum(ServiceType::class)],
            'layanan' => ['required', 'string', 'max:170'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(config('booking.max_days_ahead'))->toDateString()],
            'jam' => ['required', 'date_format:H:i'],
            'terapis' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', Rule::in(['female', 'male'])],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'address' => [Rule::requiredIf($this->input('tipe') === ServiceType::HomeCare->value), 'nullable', 'string', 'max:500'],
            'address_notes' => ['nullable', 'string', 'max:255'],
            'area' => [Rule::requiredIf($this->input('tipe') === ServiceType::HomeCare->value), 'nullable', 'integer'],
            'location_url' => ['nullable', 'url:https', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tanggal' => 'tanggal',
            'jam' => 'jam',
            'name' => 'nama lengkap',
            'phone' => 'nomor HP',
            'birth_date' => 'tanggal lahir',
            'address' => 'alamat lengkap',
            'address_notes' => 'patokan lokasi',
            'area' => 'area layanan',
            'location_url' => 'link lokasi',
            'notes' => 'catatan',
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
