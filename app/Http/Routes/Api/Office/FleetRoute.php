<?php

namespace App\Http\Routes\Api\Office;

use App\Http\Controllers\Api\Office\FleetController;
use App\Http\Routes\Api\Fleet\FleetRoute as BaseFleetRoute;

class FleetRoute extends BaseFleetRoute
{
    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.office.fleet';

    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/office/{office_slug}/fleet';

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return FleetController::class;
    }
}
