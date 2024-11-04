<?php

namespace App\Http\Routes\Api;

use App\Actions\Transaction\CalculatePricesForReservation;
use App\Actions\Transaction\ReversalPartialTransaction;
use App\Actions\Transaction\ReversalTransaction;
use Dentro\Yalr\BaseRoute;
use App\Actions\Transaction\PurchaseAttachment;
use App\Actions\Transaction\GetAvailableSeats;
use App\Actions\Transaction\PurchaseReservation;
use App\Actions\Transaction\GetAvailableSchedule;
use App\Http\Controllers\Api\TransactionController;
use App\Actions\Transaction\ReserveSeatFromSchedule;

class TransactionRoute extends BaseRoute
{
    protected string $prefix = 'transaction';

    protected string $name = 'api.transaction';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('inquiry'), [
            'as' => $this->name('inquiry'),
            'uses' => GetAvailableSchedule::class,
        ])->withoutMiddleware('auth:sanctum');

        $this->router->get($this->prefix('inquiry-auth'), [
            'as' => $this->name('inquiry-auth'),
            'uses' => GetAvailableSchedule::class,
        ]);

        $this->router->get($this->prefix('inquiry/seats'), [
            'as' => $this->name('inquiry.seat'),
            'uses' => GetAvailableSeats::class,
            'middleware' => 'permission:inquiry',
        ]);

        $this->router->post($this->prefix('inquiry/pricing'), [
            'as' => $this->name('pricing'),
            'uses' => CalculatePricesForReservation::class,
        ]);

        $this->router->post($this->prefix('reserve'), [
            'as' => $this->name('reserve'),
            'uses' => ReserveSeatFromSchedule::class,
            'middleware' => ['permission:reserve'],
        ]);

        $this->router->post($this->prefix('purchase/{transaction_hash}'), [
            'as' => $this->name('purchase'),
            'uses' => PurchaseReservation::class,
            'middleware' => 'permission:manage transaction',
        ]);

        $this->router->post($this->prefix('purchase/{transaction_hash}/attachment'), [
            'as' => $this->name('purchase.attachment'),
            'uses' => PurchaseAttachment::class,
        ]);

        $this->router->patch($this->prefix('reversal/partial'), [
            'as' => $this->name('reversal-partial'),
            'uses' => ReversalPartialTransaction::class,
        ]);

        $this->router->patch($this->prefix('reversal/{transaction_hash}'), [
            'as' => $this->name('reversal'),
            'uses' => ReversalTransaction::class,
        ]);

        $this->router->get($this->prefix('history'), [
            'as' => $this->name('history'),
            'uses' => $this->uses('index'),
        ]);

        $this->router->get($this->prefix('history/summary'), [
            'as' => $this->name('history.summary'),
            'uses' => $this->uses('summary'),
        ]);

        $this->router->get($this->prefix('history/overview'), [
            'as' => $this->name('history.overview'),
            'uses' => $this->uses('overview'),
        ]);

        $this->router->get($this->prefix('history/{transaction_hash}'), [
            'as' => $this->name('history.show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->get($this->prefix('history/{transaction_hash}/print'), [
            'as' => $this->name('history.print'),
            'uses' => $this->uses('print'),
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return TransactionController::class;
    }
}
