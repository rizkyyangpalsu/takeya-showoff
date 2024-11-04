<?php

namespace App\Http\Routes\Api\Departure;

use App\Actions\Departure\Transaction\GetTransactionFromDeparture;
use Dentro\Yalr\BaseRoute;

class TransactionRoute extends BaseRoute
{
    protected string $prefix = 'departure/{departure_hash}/transaction';

    protected string $name = 'api.departure.transaction';

    public function register(): void
    {
        $this->router->get($this->prefix, [
            'as' => $this->name,
            'uses' => GetTransactionFromDeparture::class,
        ]);
    }
}
