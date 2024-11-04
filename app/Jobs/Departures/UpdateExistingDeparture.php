<?php

namespace App\Jobs\Departures;

use App\Models\Fleet;
use App\Models\Departure;
use Illuminate\Validation\Rule;
use App\Models\Route\Track\Point;
use App\Models\Schedule\Reservation;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateExistingDeparture
{
    use Dispatchable, SerializesModels;

    /**
     * Departure instance.
     *
     * @var \App\Models\Departure
     */
    public Departure $departure;

    /**
     * Reservation instance.
     *
     * @var \App\Models\Schedule\Reservation|null
     */
    public ?Reservation $reservation;

    /**
     * Fleet instance.
     *
     * @var \App\Models\Fleet|null
     */
    public ?Fleet $fleet;

    /**
     * Origin instance.
     *
     * @var \App\Models\Route\Track\Point|null
     */
    public ?Point $origin;

    /**
     * Destination instance.
     *
     * @var \App\Models\Route\Track\Point|null
     */
    public ?Point $destination;

    /**
     * Filtered attributes.
     *
     * @var array
     */
    protected array $attributes;

    /**
     * UpdateExistingDeparture constructor.
     *
     * @param \App\Models\Departure                 $departure
     * @param \App\Models\Schedule\Reservation|null $reservation
     * @param \App\Models\Fleet|null                $fleet
     * @param \App\Models\Route\Track\Point|null    $origin
     * @param \App\Models\Route\Track\Point|null    $destination
     * @param array                                 $inputs
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(
        Departure $departure,
        ?Reservation $reservation,
        ?Fleet $fleet,
        ?Point $origin,
        ?Point $destination,
        $inputs = []
    ) {
        $this->attributes = Validator::make(array_merge([
            'reservation_id' => $reservation->id ?? null,
            'fleet_id' => $fleet->id ?? null,
            'origin_id' => $origin->id ?? null,
            'destination_id' => $destination->id ?? null,
        ], $inputs), [
            'reservation_id' => ['nullable'],
            'fleet_id' => ['nullable'],
            'origin_id' => ['nullable'],
            'destination_id' => ['nullable'],
            'type' => ['nullable', Rule::in(Departure::getDepartureTypes())],
            'status' => ['nullable', Rule::in(Departure::getDepartureStatus())],
            'distance' => 'nullable|numeric',
            'departure_time' => ['nullable', 'date_format:Y-m-d\ H\:i\:s'],
            'arrival_time' => ['nullable','date_format:Y-m-d\ H\:i\:s'],
        ])->validate();

        $this->departure = $departure;
        $this->reservation = $reservation;
        $this->fleet = $fleet;
        $this->origin = $origin;
        $this->destination = $destination;
    }

    public function handle(): void
    {
        $this->departure->fill($this->attributes);
        $this->departure->save();
    }
}
