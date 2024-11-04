<?php

namespace App\Jobs\Departures\Crew;

use LogicException;
use App\Models\Departure;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class RemoveCrewFromDeparture
{
    use Dispatchable, SerializesModels;

    /**
     * Departure instance.
     *
     * @var \App\Models\Departure
     */
    public Departure $departure;

    /**
     * Departure crew.
     *
     * @var \App\Models\Departure\Crew
     */
    public Departure\Crew $crew;

    /**
     * RemoveCrewFromDeparture constructor.
     *
     * @param \App\Models\Departure      $departure
     * @param \App\Models\Departure\Crew $crew
     */
    public function __construct(Departure $departure, Departure\Crew $crew)
    {
        $this->departure = $departure;
        $this->crew = $crew;
    }

    /**
     * Remove crew from departure.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        /** @noinspection PhpParamsInspection */
        throw_if($this->crew->departure_id != $this->departure->id, LogicException::class, 'Crew is not part of departure');

        $this->crew->delete();
    }
}
