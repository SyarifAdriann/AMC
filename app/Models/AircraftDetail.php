<?php

namespace App\Models;

class AircraftDetail extends Model
{
    public function registration(): string
    {
        return (string) $this->get('registration', '');
    }

    public function aircraftType(): ?string
    {
        $value = $this->get('aircraft_type');
        return $value !== null ? (string) $value : null;
    }

    public function operatorAirline(): ?string
    {
        $value = $this->get('operator_airline');
        return $value !== null ? (string) $value : null;
    }

    public function category(): ?string
    {
        $value = $this->get('category');
        return $value !== null ? (string) $value : null;
    }

    public function notes(): ?string
    {
        $value = $this->get('notes');
        return $value !== null ? (string) $value : null;
    }
}