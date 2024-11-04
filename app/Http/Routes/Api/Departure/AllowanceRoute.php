<?php

namespace App\Http\Routes\Api\Departure;

use Dentro\Yalr\BaseRoute;
use App\Actions\Departure\Allowance\AddAllowanceToDeparture;
use App\Actions\Departure\Allowance\DeleteExistingAllowance;
use App\Actions\Departure\Allowance\UpdateExistingAllowance;
use App\Actions\Departure\Allowance\GetAllowancesFromDeparture;

class AllowanceRoute extends BaseRoute
{
    protected string $prefix = 'departure/{departure_hash}/allowance';

    protected string $name = 'api.departure.allowance';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix, [
            'as' => $this->name,
            'uses' => GetAllowancesFromDeparture::class,
        ]);

        $this->router->group(['middleware' => 'permission:manage departure allowance'], function () {
            $this->router->post($this->prefix, [
                'as' => $this->name('store'),
                'uses' => AddAllowanceToDeparture::class,
            ]);

            $this->router->put($this->prefix('{allowance_hash}/update'), [
                'as' => $this->name('update'),
                'uses' => UpdateExistingAllowance::class,
            ]);

            $this->router->delete($this->prefix('{allowance_hash}/delete'), [
                'as' => $this->name('destroy'),
                'uses' => DeleteExistingAllowance::class,
            ]);
        });
    }
}
