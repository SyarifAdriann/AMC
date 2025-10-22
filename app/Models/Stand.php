<?php

namespace App\Models;

class Stand extends Model
{
    public function id(): ?int
    {
        $value = $this->get('id');
        return $value !== null ? (int) $value : null;
    }

    public function name(): string
    {
        return (string) $this->get('stand_name', '');
    }

    public function capacity(): int
    {
        return (int) $this->get('capacity', 0);
    }

    public function isActive(): bool
    {
        $value = $this->get('is_active', 1);
        return (bool) $value;
    }
}
