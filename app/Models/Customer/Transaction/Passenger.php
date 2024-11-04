<?php

namespace App\Models\Customer\Transaction;

use App\Models\User;
use App\Models\Customer\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Passenger.
 *
 * @property  int $id
 * @property  string $seat_code
 * @property  string|null $ticket_code
 * @property  string $title
 * @property  string $name
 * @property  string $nik
 * @property  string $layout_name
 * @property-read  Transaction $transaction
 * @property-read  User $user
 * @property  int $seat_id
 * @property  string transaction_id
 */
class Passenger extends Model
{
    use HasFactory, HashableId;

    protected $table = 'transaction_passengers';

    protected $fillable = [
        'seat_id',
        'layout_name',
        'seat_code',
        'title',
        'name',
        'nik',
        'additional_data',
        'check_in'
    ];

    protected $hidden = [
        'id',
        'seat_id',
        'transaction_id',
        'laravel_through_key'
    ];

    protected $casts = [
        'additional_data' => 'array',
    ];

    public function transaction(): Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): Relations\HasOneThrough
    {
        return $this->hasOneThrough(User::class, Transaction::class, 'id', 'id', 'transaction_id', 'user_id');
    }
}
