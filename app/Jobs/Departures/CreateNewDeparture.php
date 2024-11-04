<?php

namespace App\Jobs\Departures;

use App\Models\Fleet;
use App\Models\Departure;
use Exception;
use Illuminate\Support\Str;
use App\Models\Office\Staff;
use Illuminate\Bus\Queueable;
use Illuminate\Validation\Rule;
use App\Models\Route\Track\Point;
use App\Models\Schedule\Reservation;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;
use Veelasky\LaravelHashId\Rules\ExistsByHash;
use App\Actions\Reservation\UpdateExistingReservation;

class CreateNewDeparture
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Departure model.
     *
     * @var \App\Models\Departure
     */
    public Departure $departure;

    /**
     * Filtered array.
     *
     * @var array
     */
    public array $attributes;

    /**
     * CreateNewDeparture constructor.
     *
     * @param \App\Models\Schedule\Reservation|null $reservation
     * @param \App\Models\Fleet|null                $fleet
     * @param \App\Models\Route\Track\Point|null    $origin
     * @param \App\Models\Route\Track\Point|null    $destination
     * @param array $inputs
     *
     * @throws \Illuminate\Validation\ValidationException|\Throwable
     */
    public function __construct(
        ?Reservation $reservation,
        ?Fleet $fleet,
        ?Point $origin,
        ?Point $destination,
        array $inputs = []
    ) {
        if ($reservation !== null) {
            if (! $origin) {
                $inputs['origin_id'] = $reservation->trips->first()->origin_id;
            }

            if (! $destination) {
                $inputs['destination_id'] = $reservation->trips->last()->destination_id;
            }

            $inputs['departure_time'] = $reservation->departure_schedule->format('Y-m-d H:i:s');
        }

        $this->attributes = Validator::make(array_merge([
            'reservation_id' => $reservation->id ?? null,
            'fleet_id' => $fleet->id ?? null,
            'origin_id' => $origin->id ?? null,
            'destination_id' => $destination->id ?? null,
        ], $inputs), [
            'reservation_id' => [
                Rule::requiredIf(fn () => ($inputs['type'] ?? null) === Departure::DEPARTURE_TYPE_SCHEDULED),
                'exists:schedule_reservations,id'
            ],
            'fleet_id' => 'nullable|exists:fleets,id',
            'origin_id' => 'required|exists:points,id',
            'destination_id' => 'required|exists:points,id',
            'name' => 'required_without:reservation_id',
            'type' => ['required', Rule::in(Departure::getDepartureTypes())],
            'status' => ['required', Rule::in(Departure::getDepartureStatus())],
            'distance' => 'nullable|numeric',
            'departure_time' => ['nullable', 'date_format:Y-m-d\ H\:i\:s'],
            'arrival_time' => ['nullable','date_format:Y-m-d\ H\:i\:s'],
            'crews' => ['nullable', 'array'],
            'crews.*.staff_hash' => ['nullable', new ExistsByHash(Staff::class)],
            'crews.*.staff_id' => 'required_without:crews.*.staff_hash|exists:users,id',
            'crews.*.stipend' => ['nullable', 'numeric'],
            'crews.*.role' => ['required', Rule::in(Departure\Crew::getCrewRoles())],
        ])->validate();

        if (empty($this->attributes['name'])) {
            $this->attributes['name'] = Str::upper(__('depart').'/'.$reservation->departure_schedule->format('Y-m-d H:i').'/'.__($this->attributes['type']));
        }

        $this->chain([
            UpdateExistingReservation::makeJob($reservation, ['fleet_id' => $this->attributes['fleet_id']]),
        ]);

        $this->departure = new Departure();
    }

    /**
     * Create new departure.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws Exception
     */
    public function handle(): void
    {
        $this->departure->fill($this->attributes);
        $saved = $this->departure->save();

        if (array_key_exists('crews', $this->attributes) && $saved) {
            try {
                foreach ($this->attributes['crews'] as $crew) {
                    /** @var Staff|null $staff */
                    $staff = ! empty($crew['staff_hash']) ? Staff::byHashOrFail($crew['staff_hash']) : null;
                    $job = new Crew\AssignCrewForDeparture(
                        $this->departure,
                        $staff,
                        $crew
                    );
                    $job->handle();
                }
            } catch (Exception $e) {
                $this->departure->crews()->delete();
                $this->departure->delete();

                throw $e;
            }
        }
    }
}
