<?php

namespace App\Http\Routes\Api;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\DepartureController;
use App\Actions\Departure\UpdateExistingDepartureStatus;

class DepartureRoute extends BaseRoute
{
    protected string $prefix = '/departure';

    protected string $name = 'api.departure';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix(), [
            'as' => $this->name,
            'uses' => $this->uses('index'),
        ]);

        $this->router->get($this->prefix('{departure_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->post($this->prefix(), [
            'as' => $this->name('store'),
            'uses' => $this->uses('store'),
            'middleware' => 'permission:create departure',
        ]);

        $this->router->put($this->prefix('{departure_hash}'), [
            'as' => $this->name('update'),
            'uses' => $this->uses('update'),
            'middleware' => 'permission:update departure',
        ]);

        $this->router->delete($this->prefix('{departure_hash}'), [
            'as' => $this->name('destroy'),
            'uses' => $this->uses('destroy'),
            'middleware' => 'permission:delete departure',
        ]);

        $this->router->patch($this->prefix('{departure_hash}/status'), [
            'as' => $this->name('update.status'),
            'uses' => UpdateExistingDepartureStatus::class,
            'middleware' => 'permission:update departure',
        ]);

        $this->router->get($this->prefix('{departure_hash}/summary'), [
            'as' => $this->name('summary'),
            'uses' => $this->uses('summary'),
        ]);

        $this->router->get($this->prefix('{departure_hash}/summary-detail'), [
            'as' => $this->name('summary.detail'),
            'uses' => $this->uses('summaryDetail'),
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return DepartureController::class;
    }
}
