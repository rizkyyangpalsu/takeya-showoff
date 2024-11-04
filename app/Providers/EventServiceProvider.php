<?php

namespace App\Providers;

use App\Events\Transaction\TransactionCanceled;
use App\Events\Transaction\TransactionPendingConfirmation;
use App\Events\Transaction\TransactionReversalCreated;
use App\Events\User\UserUpdated;
use App\Listeners\Accounting;
use App\Actions\Reservation\Trip;
use App\Actions\Transaction\Passenger;
use App\Listeners\Notifications\PaymentConfirmed;
use App\Listeners\Notifications\PaymentRequested;
use App\Listeners\Notifications\TransactionCancelled;
use App\Listeners\Notifications\TransactionCreated;
use Illuminate\Auth\Events\Registered;
use App\Events\Departure\AllowanceAdded;
use App\Events\Departure\AllowanceWillBeDeleted;
use App\Events\Departure\AllowanceUpdated;
use App\Events\Transaction\TransactionPaid;
use App\Events\Transaction\TransactionOccurred;
use App\Events\Transaction\TransactionExpired;
use App\Events\Departure\DepartureStatusChanged;
use App\Listeners\Accounting\RecordTransactionUnearnedRevenue;
use App\Actions\Transaction\AttachTransactionToReservationTrip;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use App\Listeners\Accounting\RecordTransactionRevenueRealization;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        UserUpdated::class => [
            //
        ],
        TransactionOccurred::class => [
            AttachTransactionToReservationTrip::class,
            Trip\UpdateSeatConfigurationStatus::class,
            TransactionCreated::class,
        ],
        TransactionPendingConfirmation::class => [
            PaymentRequested::class
        ],
        TransactionPaid::class => [
            Trip\UpdateSeatConfigurationStatus::class,
            Passenger\GenerateTicketCode::class,
            RecordTransactionUnearnedRevenue::class,
            PaymentConfirmed::class,
        ],
        TransactionReversalCreated::class => [
            Trip\UpdateSeatConfigurationStatus::class,
            Passenger\DegenerateTicketCode::class,
        ],
        TransactionExpired::class => [
            Trip\UpdateSeatConfigurationStatus::class,
        ],
        TransactionCanceled::class => [
            Trip\UpdateSeatConfigurationStatus::class,
            Accounting\RemoveCanceledTransactionFromRecord::class,
            TransactionCancelled::class
        ],
        DepartureStatusChanged::class => [
            RecordTransactionRevenueRealization::class,
            Accounting\BalanceDepartureCostWithAllowance::class,
        ],
        AllowanceAdded::class => [
            Accounting\RecordDepartureAllowance::class,
        ],
        AllowanceUpdated::class => [
            Accounting\AdjustDepartureAllowance::class,
        ],
        AllowanceWillBeDeleted::class => [
            Accounting\DeleteDepartureAllowance::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
