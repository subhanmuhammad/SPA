<?php

namespace App\Http\Requests;

use App\Models\TherapistSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TherapistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::in($this->user()->accessibleBranches()->pluck('branches.id'))],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'treatments' => ['array'],
            'treatments.*' => ['integer', Rule::exists('treatments', 'id')->whereNull('deleted_at')],
            'schedules' => ['array'],
            'schedules.*.active' => ['boolean'],
            'schedules.*.start_time' => ['nullable', 'required_if:schedules.*.active,true', 'date_format:H:i'],
            'schedules.*.end_time' => ['nullable', 'required_if:schedules.*.active,true', 'date_format:H:i', 'after:schedules.*.start_time'],
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_id' => 'cabang',
            'name' => 'nama',
            'phone' => 'nomor HP',
            'schedules.*.start_time' => 'jam mulai',
            'schedules.*.end_time' => 'jam selesai',
        ];
    }

    protected function prepareForValidation(): void
    {
        $schedules = collect($this->input('schedules', []))
            ->only(array_keys(TherapistSchedule::DAYS))
            ->map(fn ($row) => [...(array) $row, 'active' => filter_var($row['active'] ?? false, FILTER_VALIDATE_BOOLEAN)]);

        $this->merge(['is_active' => $this->boolean('is_active'), 'schedules' => $schedules->all()]);
    }

    public function scheduleRows(): array
    {
        return collect($this->validated('schedules', []))
            ->filter(fn ($row) => $row['active'])
            ->map(fn ($row, $day) => ['day_of_week' => $day, 'start_time' => $row['start_time'], 'end_time' => $row['end_time']])
            ->values()
            ->all();
    }
}
