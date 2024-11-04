<?php

namespace App\Http\Routes\Api\Departure;

use App\Actions\Departure\Accounting\Cost\CreateNewDepartureCombinedCost;
use App\Actions\Departure\Accounting\Cost\DeleteExistingDepartureCombinedCost;
use App\Actions\Departure\Accounting\Income\CreateNewDepartureCombinedIncome;
use App\Actions\Departure\Accounting\Income\DeleteExistingDepartureCombinedIncome;
use App\Http\Controllers\Api\Departure\CombinedController;
use Dentro\Yalr\BaseRoute;

class CombinedRoute extends BaseRoute
{
    protected string $prefix = 'departure/combined';

    protected string $name = 'api.departure.combined';

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

        $this->router->group(['middleware' => 'permission:manage departure combined'], function () {
            $this->router->post($this->prefix('store'), [
                'as' => $this->name('store'),
                'uses' => $this->uses('store'),
            ]);

            $this->router->get($this->prefix('{combined_hash}'), [
                'as' => $this->name('show'),
                'uses' => $this->uses('show'),
            ]);

            $this->router->post($this->prefix('{combined_hash}/cost'), [
                'as' => $this->name('cost'),
                'uses' => CreateNewDepartureCombinedCost::class,
            ]);

            $this->router->post($this->prefix('{combined_hash}/income'), [
                'as' => $this->name('income'),
                'uses' => CreateNewDepartureCombinedIncome::class,
            ]);

            $this->router->put($this->prefix('{combined_hash}'), [
                'as' => $this->name('update'),
                'uses' => $this->uses('update'),
            ]);

            $this->router->put($this->prefix('{combined_hash}/calculate'), [
                'as' => $this->name('calculate'),
                'uses' => $this->uses('calculate'),
            ]);

            $this->router->delete($this->prefix('{combined_hash}'), [
                'as' => $this->name('destroy'),
                'uses' => $this->uses('destroy'),
            ]);

            $this->router->delete($this->prefix('{combined_hash}/cost/{journal_hash}'), [
                'as' => $this->name('cost.delete'),
                'uses' => DeleteExistingDepartureCombinedCost::class,
            ]);

            $this->router->delete($this->prefix('{combined_hash}/income/{journal_hash}'), [
                'as' => $this->name('income.delete'),
                'uses' => DeleteExistingDepartureCombinedIncome::class,
            ]);
        });
    }

    public function controller(): string
    {
        return CombinedController::class;
    }
}
