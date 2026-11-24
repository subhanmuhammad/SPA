<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu konfirmasi',
            self::Confirmed => 'Dikonfirmasi',
            self::CheckedIn => 'Check-in',
            self::InProgress => 'Sedang dirawat',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::NoShow => 'Tidak datang',
        };
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::Confirmed => 'Konfirmasi',
            self::CheckedIn => 'Check-in',
            self::InProgress => 'Mulai perawatan',
            self::Completed => 'Selesaikan',
            self::Cancelled => 'Batalkan',
            self::NoShow => 'Tandai tidak datang',
            default => $this->label(),
        };
    }

    public function next(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::CheckedIn, self::Cancelled, self::NoShow],
            self::CheckedIn => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed],
            default => [],
        };
    }

    public function canMoveTo(self $status): bool
    {
        return in_array($status, $this->next(), true);
    }

    public function blocksSchedule(): bool
    {
        return ! in_array($this, [self::Cancelled, self::NoShow], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    public static function blocking(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s->blocksSchedule()));
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
