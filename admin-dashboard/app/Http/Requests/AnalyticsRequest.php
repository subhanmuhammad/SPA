<?php

namespace App\Http\Requests;

use App\Support\Analytics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class AnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:'.CarbonImmutable::parse($this->input('from', today()->toDateString()))->addYear()->toDateString()],
            'branch' => ['nullable', 'integer', Rule::in($this->branches()->keys())],
            'grain' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
        ];
    }

    public function branches(): Collection
    {
        return $this->user()->accessibleBranches()->orderBy('name')->pluck('name', 'branches.id');
    }

    public function analytics(string $defaultFrom = 'first day of this month'): Analytics
    {
        $from = CarbonImmutable::parse($this->validated('from') ?? $defaultFrom);
        $to = CarbonImmutable::parse($this->validated('to') ?? 'today');
        $branchIds = $this->validated('branch') ? collect([(int) $this->validated('branch')]) : $this->branches()->keys();

        return new Analytics($branchIds, $from, $to);
    }

    public function grain(): string
    {
        return $this->validated('grain') ?? 'daily';
    }
}
