<?php

namespace App\Http\Routes\Api;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\GeoController;

class GeoRoute extends BaseRoute
{
    protected string $prefix = 'geo';

    protected string $name = 'api.geo';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('{geo_name}'), [
            'as' => $this->name('index'),
            'uses' => $this->uses('index'),
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return GeoController::class;
    }
}
