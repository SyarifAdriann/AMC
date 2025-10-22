<?php

namespace App\Models;

class DailyStaffRoster extends Model
{
    public function id(): ?int
    {
        $value = $this->get('id');
        return $value !== null ? (int) $value : null;
    }

    public function date(): string
    {
        return (string) $this->get('roster_date', '');
    }

    public function shift(): string
    {
        return (string) $this->get('shift', '');
    }

    public function aerodromeCode(): string
    {
        return (string) $this->get('aerodrome_code', '');
    }

    public function dayShiftStaff(): array
    {
        return array_values(array_filter([
            $this->get('day_shift_staff_1'),
            $this->get('day_shift_staff_2'),
            $this->get('day_shift_staff_3'),
        ], static fn($value) => $value !== null && $value !== ''));
    }

    public function nightShiftStaff(): array
    {
        return array_values(array_filter([
            $this->get('night_shift_staff_1'),
            $this->get('night_shift_staff_2'),
            $this->get('night_shift_staff_3'),
        ], static fn($value) => $value !== null && $value !== ''));
    }

    public function updatedByUserId(): ?int
    {
        $value = $this->get('updated_by_user_id');
        return $value !== null ? (int) $value : null;
    }

    public function updatedAt(): ?string
    {
        $value = $this->get('updated_at');
        return $value !== null ? (string) $value : null;
    }
}
