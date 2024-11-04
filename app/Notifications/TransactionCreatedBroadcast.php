<?php

namespace App\Notifications;

use App\Models\Customer\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionCreatedBroadcast extends Notification
{
    use Queueable;

    public \Illuminate\Support\Collection $parameters;

    public Transaction $transaction;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Transaction $transaction, $parameters)
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
            'title' => 'Pembelian '.$this->parameters[2].' kursi '.$this->parameters[3].' oleh '.$this->parameters[9]
                .' untuk pemberangkatan '.$this->parameters[6].' - '.$this->parameters[7].' pada hari '.$this->parameters[1].', lihat lebih detail.',
            'body' => 'Tanggal: '.$this->parameters[0].'<br>'.
                'Jenis Bus: '.$this->parameters[1].'<br>'.
                'Penumpang: '.$this->parameters[2].' orang<br>'.
                'No. Kursi: '.$this->parameters[3].'<br>'.
                'Nama Penumpang: '.$this->parameters[4].'<br>'.
                'No. Telpon: '.$this->parameters[5].'<br>'.
                'Naik dari: '.$this->parameters[6].'<br>'.
                'Turun di: '.$this->parameters[7].'<br>'.
                'Kode Transaksi: '.$this->parameters[8].'<br><br>'.

                'Dipesan oleh: '.$this->parameters[9]
            ,
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
            'Pembelian '.$this->parameters[2].' kursi '.$this->parameters[3].' oleh '.$this->parameters[9]
            .' untuk pemberangkatan '.$this->parameters[6].' - '.$this->parameters[7].' pada hari '.$this->parameters[1].', lihat lebih detail.'
        ]);
    }
}
