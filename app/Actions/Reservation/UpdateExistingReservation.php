<?php

namespace App\Actions\Reservation;

use App\Models\Schedule\Reservation;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateExistingReservation
{
    use AsAction;

    public function handle(Reservation $reservation, array $attributes)
    {
        $reservation->fill($attributes);

        if (! empty($attributes['fleet_id'])) {
            $reservation->fleet()->associate($attributes['fleet_id']);
        }

        $reservation->save();
    }

    public function asJob(Reservation $reservation, $attributes)
    {
        $this->handle($reservation, $attributes);
    }
}
