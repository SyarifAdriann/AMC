<?php

namespace App\Models;

class DailySnapshot extends Model
{
    public function id(): ?int
    {
        $value = $this->get('id');
        return $value !== null ? (int) $value : null;
    }

    public function snapshotDate(): string
    {
        return (string) $this->get('snapshot_date', '');
    }

    public function createdByUserId(): ?int
    {
        $value = $this->get('created_by_user_id');
        return $value !== null ? (int) $value : null;
    }

    public function createdByUsername(): ?string
    {
        $value = $this->get('created_by_username');
        return $value !== null ? (string) $value : null;
    }

    public function createdAt(): ?string
    {
        $value = $this->get('created_at');
        return $value !== null ? (string) $value : null;
    }

    public function data(): array
    {
        $data = $this->get('snapshot_data');

        if (is_array($data)) {
            return $data;
        }

        if (is_string($data) && $data !== '') {
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
