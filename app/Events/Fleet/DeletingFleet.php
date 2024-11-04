<?php

namespace App\Events\Fleet;

use App\Models\Fleet;

class DeletingFleet
{
    /**
     * Fleet instance.
     *
     * @var \App\Models\Fleet
     */
    public Fleet $fleet;

    /**
     * DeletingFleet constructor.
     *
     * @param \App\Models\Fleet $fleet
     */
    public function __construct(Fleet $fleet)
    {
        $this->fleet = $fleet;
    }
}
