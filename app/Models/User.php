<?php

namespace App\Models;

class User extends Model
{
    public function id(): ?int
    {
        $value = $this->get('id');
        return $value !== null ? (int) $value : null;
    }

    public function username(): string
    {
        return (string) $this->get('username', '');
    }

    public function passwordHash(): string
    {
        return (string) $this->get('password_hash', '');
    }

    public function role(): string
    {
        return (string) $this->get('role', 'viewer');
    }

    public function status(): string
    {
        return (string) $this->get('status', 'inactive');
    }

    public function email(): ?string
    {
        $value = $this->get('email');
        return $value !== null ? (string) $value : null;
    }

    public function fullName(): ?string
    {
        $value = $this->get('full_name');
        return $value !== null ? (string) $value : null;
    }
}
