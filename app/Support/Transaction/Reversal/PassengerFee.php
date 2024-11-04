<?php

namespace App\Support\Transaction\Reversal;

use App\Models\Customer\Transaction\Passenger;

final class PassengerFee
{
    public function __construct(
        public Passenger $passenger,
        public float $seatFee,
    ) {
    }
}
