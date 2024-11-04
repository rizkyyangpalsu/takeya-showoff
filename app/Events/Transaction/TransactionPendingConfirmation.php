<?php

namespace App\Events\Transaction;

use App\Models\Customer\Transaction;
use App\Models\Office;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionPendingConfirmation
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var \App\Models\Customer\Transaction
     */
    public Transaction $transaction;

    /**
     * @var User
     */
    public User $user;

    /**
     * @var Office|null
     */
    public ?Office $office;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Customer\Transaction $transaction
     * @param User $user
     * @param Office|null $office
     */
    public function __construct(Transaction $transaction, User $user, ?Office $office)
    {
        $this->transaction = $transaction;
        $this->user = $user;
        $this->office = $office;
    }
}
