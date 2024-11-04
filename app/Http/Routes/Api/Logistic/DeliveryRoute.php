<?php

namespace App\Http\Routes\Api\Logistic;

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
        $this->router->get($this->prefix('get-origin-city'), [
            'as'    =>  $this->name('get-origin-city'),
            'uses'  =>  $this->uses('getOriginCity'),
        ]);

        $this->router->get($this->prefix('get-destination-city/{hash}'), [
            'as'    =>  $this->name('get-destination-city'),
            'uses'  =>  $this->uses('getDestinationCity'),
        ]);

        $this->router->get($this->prefix('get-available-price'), [
            'as'    =>  $this->name('get-available-price'),
            'uses'  =>  $this->uses('getAvailablePrice'),
        ]);

        $this->router->post($this->prefix('get-available-service'), [
            'as'    =>  $this->name('get-available-service'),
            'uses'  =>  $this->uses('getAvailableService'),
        ]);

        $this->router->get($this->prefix('get-type-price'), [
            'as'    =>  $this->name('get-type-price'),
            'uses'  =>  $this->uses('getTypePriceByService'),
        ]);

        $this->router->get($this->prefix('receipt/{logistic_delivery_hash}'), [
            'as'    =>  $this->name('get-data-receipt'),
            'uses'  =>  $this->uses('getDataReceipt'),
        ]);

        $this->router->post($this->prefix('create-qrcode/{logistic_delivery_hash}'), [
            'as'    =>  $this->name('create-qrcode'),
            'uses'  =>  $this->uses('createQrcode'),
        ]);

        $this->router->get($this->prefix(), [
            'as'    =>  $this->name,
            'uses'  =>  $this->uses('index'),
        ]);

        $this->router->get($this->prefix('{logistic_delivery_hash}'), [
            'as'    =>  $this->name('show'),
            'uses'  =>  $this->uses('show'),
        ]);

        $this->router->post($this->prefix(), [
            'as'    =>  $this->name('store'),
            'uses'  =>  $this->uses('store'),
        ]);

        $this->router->put($this->prefix('{logistic_delivery_hash}'), [
            'as'    =>  $this->name('update'),
            'uses'  =>  $this->uses('update'),
        ]);

        $this->router->delete($this->prefix('{logistic_delivery_hash}'), [
            'as' => $this->name('destroy'),
            'uses' => $this->uses('destroy'),
        ]);

        $this->router->post($this->prefix('paid/{logistic_delivery_hash}'), [
            'as'    =>  $this->name('paid'),
            'uses'  =>  $this->uses('paid'),
        ]);

        $this->router->post($this->prefix('taken/{logistic_delivery_hash}'), [
            'as'    =>  $this->name('taken'),
            'uses'  =>  $this->uses('taken'),
        ]);
    }

    public function controller(): string
    {
        return DeliveryController::class;
    }
}
