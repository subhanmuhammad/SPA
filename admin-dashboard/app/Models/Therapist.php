<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'phone', 'specialization', 'is_active'])]
class Therapist extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class, 'therapist_treatments');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TherapistSchedule::class)->orderBy('day_of_week');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(TherapistLeave::class)->orderBy('date');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function blockingBookings(): HasMany
    {
        return $this->bookings()->whereIn('status', BookingStatus::blocking());
    }
}
