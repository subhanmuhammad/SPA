<?php

namespace App\Http\Requests;

use App\Enums\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $treatment = $this->route('treatment');

        return [
            'treatment_category_id' => ['required', 'integer', Rule::exists('treatment_categories', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'service_type' => ['required', Rule::enum(ServiceType::class)],
            'duration_minutes' => ['nullable', 'integer', 'between:5,600'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'normal_price' => ['nullable', 'integer', 'gt:price', 'max:100000000'],
            'promo_starts_on' => ['nullable', 'date'],
            'promo_ends_on' => ['nullable', 'date', 'after_or_equal:promo_starts_on'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['boolean'],
            'items' => ['array', 'max:10'],
            'items.*.name' => ['nullable', 'string', 'max:100', 'required_with:items.*.treatment_id'],
            'items.*.treatment_id' => [
                'nullable', 'integer',
                Rule::exists('treatments', 'id')->whereNull('deleted_at'),
                Rule::notIn(array_filter([$treatment?->id])),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'treatment_category_id' => 'kategori',
            'name' => 'nama layanan',
            'description' => 'deskripsi',
            'service_type' => 'jenis layanan',
            'duration_minutes' => 'durasi',
            'price' => 'harga',
            'normal_price' => 'harga normal',
            'promo_starts_on' => 'tanggal mulai promo',
            'promo_ends_on' => 'tanggal akhir promo',
            'photo' => 'foto',
            'items.*.name' => 'nama komponen',
            'items.*.treatment_id' => 'layanan komponen',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_photo' => $this->boolean('remove_photo'),
        ]);
    }

    public function treatmentData(): array
    {
        return $this->safe()->except(['photo', 'remove_photo', 'items']);
    }

    public function packageItems(): array
    {
        return collect($this->validated('items', []))
            ->filter(fn (array $item) => filled($item['name'] ?? null))
            ->values()
            ->map(fn (array $item, int $i) => [
                'name' => $item['name'],
                'treatment_id' => $item['treatment_id'] ?? null,
                'sort_order' => $i + 1,
            ])
            ->all();
    }
}
