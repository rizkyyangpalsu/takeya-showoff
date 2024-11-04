<?php

namespace App\Http\Routes\Api\Departure;

use Dentro\Yalr\BaseRoute;
use App\Actions\Departure\Accounting\Income\GetIncomeOfDeparture;
use App\Actions\Departure\Accounting\Income\CreateNewDepartureIncome;
use App\Actions\Departure\Accounting\Income\DeleteExistingDepartureIncome;
use App\Actions\Departure\Accounting\Income\UpdateExistingDepartureIncome;

class IncomeRoute extends BaseRoute
{
    protected string $prefix = 'departure/{departure_hash}/income';

    protected string $name = 'api.departure.income';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->group(['middleware' => 'permission:manage departure income'], function () {
            $this->router->post($this->prefix, [
                'as' => $this->name('store'),
                'uses' => CreateNewDepartureIncome::class,
            ]);

            $this->router->put($this->prefix('{journal_hash}'), [
                'as' => $this->name('update'),
                'uses' => UpdateExistingDepartureIncome::class,
            ]);

            $this->router->delete($this->prefix('{journal_hash}'), [
                'as' => $this->name('destroy'),
                'uses' => DeleteExistingDepartureIncome::class,
            ]);
        });

        $this->router->get($this->prefix, [
            'as' => $this->name,
            'uses' => GetIncomeOfDeparture::class,
        ]);
    }
}
