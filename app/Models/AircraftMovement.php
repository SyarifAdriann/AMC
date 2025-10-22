<?php

namespace App\Models;

class AircraftMovement extends Model
{
    public function id(): ?int
    {
        $value = $this->get('id');
        return $value !== null ? (int) $value : null;
    }

    public function movementDate(): string
    {
        return (string) $this->get('movement_date', '');
    }

    public function registration(): ?string
    {
        $value = $this->get('registration');
        return $value !== null ? (string) $value : null;
    }

    public function parkingStand(): ?string
    {
        $value = $this->get('parking_stand');
        return $value !== null ? (string) $value : null;
    }

    public function onBlockTime(): ?string
    {
        $value = $this->get('on_block_time');
        return $value !== null ? (string) $value : null;
    }

    public function offBlockTime(): ?string
    {
        $value = $this->get('off_block_time');
        return $value !== null ? (string) $value : null;
    }

    public function isRon(): bool
    {
        return (bool) $this->get('is_ron', false);
    }

    public function ronComplete(): bool
    {
        return (bool) $this->get('ron_complete', false);
    }
}
