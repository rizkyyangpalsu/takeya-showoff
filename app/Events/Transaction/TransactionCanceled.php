<?php

namespace App\Events\Transaction;

use App\Models\User;
use App\Models\Office;
use App\Models\Customer\Transaction;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class TransactionCanceled
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
