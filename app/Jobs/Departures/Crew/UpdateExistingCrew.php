<?php

namespace App\Jobs\Departures\Crew;

use App\Models\Departure;
use App\Models\Office\Staff;
use App\Models\Departure\Crew;
use Illuminate\Validation\Rule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateExistingCrew
{
    use SerializesModels, Dispatchable;

    /**
     * Filtered attributes.
     *
     * @var array
     */
    public array $attributes;

    /**
     * Crew instance.
     *
     * @var \App\Models\Departure\Crew
     */
    public Crew $crew;

    /**
     * UpdateExistingCrew constructor.
     *
     * @param \App\Models\Departure $departure
     * @param \App\Models\Departure\Crew $crew
     * @param array $inputs
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(Departure $departure, Crew $crew, $inputs = [])
    {
        if (array_key_exists('staff_hash', $inputs)) {
            $inputs['staff_id'] = Staff::hashToId($inputs['staff_hash']);
            unset($inputs['staff_hash']);
        }

        $this->attributes = Validator::make(array_merge([
            'departure_id' => $departure->id ?? null,
            'staff_id' => $crew->staff_id ?? null,
        ], $inputs), [
            'departure_id' => ['required'],
            'staff_id' => ['required'],
            'stipend' => ['nullable', 'numeric'],
            'role' => ['nullable', Rule::in(Crew::getCrewRoles())],
        ])->validate();

        $this->crew = $crew;
    }

    /**
     * Handle update existing crew.
     *
     * @return bool
     */
    public function handle(): bool
    {
        $this->crew->fill($this->attributes);

        return $this->crew->save();
    }
}
