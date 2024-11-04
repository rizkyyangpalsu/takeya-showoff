<?php

namespace App\Listeners\Notifications;

use App\Jobs\Notifications\WhatsappSendMessage;
use App\Models\User;
use App\Notifications\PaymentRequestedBroadcast;

class PaymentRequested
{
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
        $transaction = $event->transaction;

        $phone_id = '6281292077721';
        $parameters = [
            [
                'type' => 'text',
                'text' => $transaction->code
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
                'text' => 'Rp'.number_format($transaction->total_price, 0, ',', '.').',-'
            ],
            [
                'type' => 'text',
                'text' => $transaction->user->name.' ('.$transaction->user->user_type.')'
            ],
        ];

        $users = $this->getNotifableUser();
        $users->each(fn ($user) => $user->notify(new PaymentRequestedBroadcast($transaction, $parameters)));
//        WhatsappSendMessage::dispatch($phone_id, $parameters, 'trm_payment_confirmation');
    }

    private function getNotifableUser(): \Illuminate\Database\Eloquent\Collection|array
    {
        $users = User::query()
            ->where('user_type', User::USER_TYPE_SUPER_ADMIN)
            ->get();

        return $users;
    }
}
