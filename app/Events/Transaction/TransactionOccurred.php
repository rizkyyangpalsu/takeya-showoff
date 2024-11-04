<?php

namespace App\Events\Transaction;

use App\Models\User;
use App\Support\Schedule\Item;
use App\Models\Customer\Transaction;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class TransactionOccurred implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var \App\Models\Customer\Transaction
     */
    public Transaction $transaction;

    /**
     * @var \App\Support\Schedule\Item
     */
    public Item $item;

    /**
     * @var User
     */
    public User $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param \App\Models\Customer\Transaction $transaction
     * @param \App\Support\Schedule\Item $item
     */
    public function __construct(Transaction $transaction, Item $item, User $user)
    {
        $this->user = $user;
        $this->transaction = $transaction;
        $this->item = $item;
    }
}
