<?php

namespace App\Listeners\Notifications;

use App\Jobs\Notifications\WhatsappSendMessage;
use App\Models\User;
use App\Notifications\TransactionPaidBroadcast;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class PaymentConfirmed
{
    use asAction;
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
        $user = $event->user;
        $office = $event->office;

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
                'text' => ' - '
            ],
            [
                'type' => 'text',
                'text' => $transaction->user->name.' ('.$transaction->user->user_type.')'
            ],
        ];
        $otherComponent = [
            'type' => 'header',
            'parameters' => [
                [
                    'type' => 'text',
                    'text' => 'Mobile'
                ],
            ]
        ];

        $users = $this->getNotifableUser();
        $user = User::query()->find(9);
//        $user->notify(new TransactionPaidBroadcast($transaction, $parameters));
//        $users->each(fn($user) => $user->notify(new TransactionPaidBroadcast($transaction, $parameters)));
//        WhatsappSendMessage::dispatch($phone_id, $parameters, 'trm_payment_confirmed', $otherComponent);
    }

    private function getNotifableUser(): \Illuminate\Database\Eloquent\Collection|array
    {
        $users = User::query()
            ->where('user_type', User::USER_TYPE_SUPER_ADMIN)
            ->get();

        return $users;
    }
}
