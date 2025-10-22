<?php

namespace App\Models;

class FlightReference extends Model
{
    public function id(): ?int
    {
        $value = $this->get('id');
        return $value !== null ? (int) $value : null;
    }

    public function flightNumber(): string
    {
        return (string) $this->get('flight_no', '');
    }

    public function defaultRoute(): string
    {
        return (string) $this->get('default_route', '');
    }
}
