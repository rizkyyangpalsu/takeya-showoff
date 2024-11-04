<?php

namespace App\Http\Routes\Api\Logistic;

use App\Http\Controllers\Api\Logistic\PriceController;
use Dentro\Yalr\BaseRoute;

class PriceRoute extends BaseRoute
{
    protected string $name = 'api.logistic.price';
    protected string $prefix = '/logistic/price';
    protected array|string $middleware = 'permission:manage logistic prices';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix(), [
            'as'    =>  $this->name,
            'uses'  =>  $this->uses('index'),
        ]);

        $this->router->get($this->prefix('search-type'), [
            'as'    =>  $this->name('search-type'),
            'uses'  =>  $this->uses('searchType'),
        ]);

        $this->router->get($this->prefix('{logistic_price_hash}'), [
            'as'    =>  $this->name('show'),
            'uses'  =>  $this->uses('show'),
        ]);

        $this->router->post($this->prefix(), [
            'as'    =>  $this->name('store'),
            'uses'  =>  $this->uses('store'),
        ]);

        $this->router->put($this->prefix('{logistic_price_hash}'), [
            'as'    =>  $this->name('update'),
            'uses'  =>  $this->uses('update'),
        ]);

        $this->router->delete($this->prefix('{logistic_price_hash}'), [
            'as' => $this->name('destroy'),
            'uses' => $this->uses('destroy'),
        ]);
    }

    public function controller(): string
    {
        return PriceController::class;
    }
}
