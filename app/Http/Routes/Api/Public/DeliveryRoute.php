<?php

namespace App\Http\Routes\Api\Public;

use App\Http\Controllers\Api\Logistic\DeliveryController;
use Dentro\Yalr\BaseRoute;

class DeliveryRoute extends BaseRoute
{
    protected string $name = 'api.logistic.delivery';
    protected string $prefix = '/logistic/delivery';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('get-origin-office'), [
            'as'    =>  $this->name('get-origin-office'),
            'uses'  =>  $this->uses('getOriginOffice'),
        ]);

        $this->router->get($this->prefix('get-destination-office/{office_slug}'), [
            'as'    =>  $this->name('get-destination-office'),
            'uses'  =>  $this->uses('getDestinationOffice'),
        ]);

        $this->router->get($this->prefix('get-price-by-office'), [
            'as'    =>  $this->name('get-price-by-office'),
            'uses'  =>  $this->uses('getPriceByOffice'),
        ]);

        $this->router->post($this->prefix('check-price'), [
            'as'    =>  $this->name('check-price'),
            'uses'  =>  $this->uses('checkPrice'),
        ]);
    }

    public function controller(): string
    {
        return DeliveryController::class;
    }
}
