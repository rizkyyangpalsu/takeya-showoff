<?php

namespace App\Notifications;

use App\Models\Customer\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionCancalledBroadcast extends Notification
{
    use Queueable;

    public \Illuminate\Support\Collection $parameters;

    public Transaction $transaction;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($transaction, $parameters)
    {
        $this->transaction = $transaction;
        $this->parameters = collect($parameters)->pluck('text');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => 'Pembatalan kursi ('.$this->transaction->passengers->pluck('seat_code')->join(',').') pada
                '.$this->transaction->canceled_at.' untuk pemberangkatan'.$this->transaction->trips->first()->origin?->name.'
                - terminal '.$this->transaction->trips->first()->origin?->terminal.' , lihat lebih detail.',
            'body' => 'Kode Transaksi: '.$this->parameters[0].'<br>'.
                'Kode Tiket: '.$this->parameters[1].'<br>'.
                'Kursi: '.$this->transaction->passengers->pluck('seat_code')->join(',').'<br>'.
                'Nama Pelanggan: '.$this->parameters[2].'<br>'.
                'No. Telpon: '.$this->parameters[3].'<br><br>'.

                'Dipesan oleh: '.$this->parameters[4].'<br>'.
                'Dibatalkan pada: '.$this->parameters[5],
            'transaction' => $this->transaction
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Pembelian '.$this->parameters[2]['text'].' kursi '.$this->parameters[3]['text'].' oleh '.$this->parameters[9]['text']
            .' untuk pemberangkatan '.$this->parameters[6]['text'].' - '.$this->parameters[7]['text'].' pada hari '.$this->parameters[1]['text'].', lihat lebih detail.'
        ]);
    }
}
