<?php

namespace App\Contracts;

use App\Models\Fleet\Layout;

interface HasLayout
{
    /**
     * Has layout for configuration.
     *
     * @return \App\Models\Fleet\Layout
     */
    public function getLayout(): Layout;
}
