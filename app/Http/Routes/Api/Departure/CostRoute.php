<?php

namespace App\Http\Routes\Api\Departure;

use Dentro\Yalr\BaseRoute;
use App\Actions\Departure\Accounting\Cost\GetCostsOfDeparture;
use App\Actions\Departure\Accounting\Cost\CreateNewDepartureCost;
use App\Actions\Departure\Accounting\Cost\DeleteExistingDepartureCost;
use App\Actions\Departure\Accounting\Cost\UpdateExistingDepartureCostForm;

class CostRoute extends BaseRoute
{
    protected string $prefix = 'departure/{departure_hash}/cost';

    protected string $name = 'api.departure.cost';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix, [
            'as' => $this->name,
            'uses' => GetCostsOfDeparture::class,
        ]);

        $this->router->group(['middleware' => 'permission:manage departure cost'], function () {
            $this->router->post($this->prefix, [
                'as' => $this->name('store'),
                'uses' => CreateNewDepartureCost::class,
            ]);

            $this->router->put($this->prefix('{journal_hash}'), [
                'as' => $this->name('update'),
                'uses' => UpdateExistingDepartureCostForm::class,
            ]);

            $this->router->delete($this->prefix('{journal_hash}'), [
                'as' => $this->name('destroy'),
                'uses' => DeleteExistingDepartureCost::class,
            ]);
        });
    }
}
