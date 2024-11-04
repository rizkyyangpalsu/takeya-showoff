<?php

namespace App\Listeners\Notifications;

use App\Jobs\Notifications\WhatsappSendMessage;
use App\Models\Customer\Transaction;
use App\Models\User;
use App\Notifications\TransactionCreatedBroadcast;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class TransactionCreated
{
    use AsAction;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $transaction = $event->transaction->load('passengers');
        $item = $event->item;

        $phone_id = '6281292077721';
        $parameters = [
            [
                'type' => 'text',
                'text' => Carbon::make($transaction->created_at)->format('l, d-M-Y H:i')
            ],
            [
                'type' => 'text',
                'text' => $transaction->passengers[0]->layout_name
            ],
            [
                'type' => 'text',
                'text' => $transaction->total_passenger
            ],
            [
                'type' => 'text',
                'text' => $transaction->passengers->pluck('seat_code')->join(',')
            ],
            [
                'type' => 'text',
                'text' => $transaction->passengers->first()->name
            ],
            [
                'type' => 'text',
                'text' => $transaction->passengers->first()->additional_data['phone'] ?? '-'
            ],
            [
                'type' => 'text',
                'text' => $transaction->trips->first()->origin?->name
            ],
            [
                'type' => 'text',
                'text' => $transaction->trips->last()->origin?->terminal
            ],
            [
                'type' => 'text',
                'text' => $transaction->code
            ],
            [
                'type' => 'text',
                'text' => $transaction->user->name.' ('.$transaction->user->user_type.')'
            ],
        ];

        $users = $this->getNotifableUser();
        $users->each(fn ($user) => $user->notify(new TransactionCreatedBroadcast($transaction, $parameters)));
//        WhatsappSendMessage::dispatch($phone_id, $parameters, 'trm_transaction_created');
    }

    private function getNotifableUser()
    {
        $users = User::query()
            ->where('user_type', User::USER_TYPE_SUPER_ADMIN)
            ->get();

        return $users;
    }
}
