<?php

namespace App\Jobs\Departures;

use App\Models\Departure;
use Illuminate\Queue\SerializesModels;
use Veelasky\LaravelHashId\Eloquent\HashableId;

class DeleteExistingDeparture
{
    use HashableId, SerializesModels;

    /**
     * Departure instance.
     *
     * @var \App\Models\Departure
     */
    public Departure $departure;

    /**
     * DeleteExistingDeparture constructor.
     *
     * @param \App\Models\Departure $departure
     */
    public function __construct(Departure $departure)
    {
        $this->departure = $departure;
    }

    /**
     * Delete existing departure.
     *
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->departure->delete();
    }
}
