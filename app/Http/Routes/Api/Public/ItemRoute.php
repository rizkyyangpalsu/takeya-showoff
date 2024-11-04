<?php

namespace App\Http\Routes\Api\Public;

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
        $this->router->get($this->prefix('check-receipt'), [
            'as'    =>  $this->name('check-receipt'),
            'uses'  =>  $this->uses('checkReceipt'),
        ]);
    }

    public function controller(): string
    {
        return ItemController::class;
    }
}
