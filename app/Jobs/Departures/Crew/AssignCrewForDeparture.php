<?php

namespace App\Jobs\Departures\Crew;

use App\Models\Departure;
use App\Models\Office\Staff;
use Illuminate\Validation\Rule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;

class AssignCrewForDeparture
{
    use Dispatchable, SerializesModels;

    /**
     * Crew instance.
     *
     * @var \App\Models\Departure\Crew|null
     */
    public ?Departure\Crew $crew;

    /**
     * Filtered attributes.
     *
     * @var array
     */
    public array $attributes;

    /**
     * AssignCrewForDeparture constructor.
     *
     * @param \App\Models\Departure|null    $departure
     * @param \App\Models\Office\Staff|null $staff
     * @param array                         $inputs
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(?Departure $departure, ?Staff $staff, $inputs = [])
    {
        $this->attributes = Validator::make(array_merge([
            'departure_id' => $departure->id ?? null,
            'staff_id' => $staff->id ?? null,
        ], $inputs), [
            'departure_id' => ['required'],
            'staff_id' => ['required'],
            'stipend' => ['nullable', 'numeric'],
            'role' => ['required', Rule::in(Departure\Crew::getCrewRoles())],
        ])->validate();
    }

    public function handle()
    {
        $this->crew = new Departure\Crew();
        $this->crew->fill($this->attributes);

        $this->crew->save();
    }
}
