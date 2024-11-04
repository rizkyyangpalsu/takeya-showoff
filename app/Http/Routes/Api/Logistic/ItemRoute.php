<?php

namespace App\Http\Routes\Api\Logistic;

use App\Http\Controllers\Api\Logistic\ItemController;
use Dentro\Yalr\BaseRoute;

class ItemRoute extends BaseRoute
{
    protected string $name = 'api.logistic.item';
    protected string $prefix = '/logistic/item';

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
    }

    public function controller(): string
    {
        return ItemController::class;
    }
}
