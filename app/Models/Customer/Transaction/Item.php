<?php

namespace App\Models\Customer\Transaction;

use App\Models\Customer\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property  string name
 * @property  float  amount
 */
class Item extends Model
{
    use HasFactory, HashableId;

    protected $table = 'transaction_items';

    protected $fillable = [
        'transaction_id',
        'quantity',
        'name',
        'amount',
        'total_amount',
        'type'
    ];

    protected $casts = [
        'quantity' => 'int',
        'amount' => 'float',
        'total_amount' => 'float',
    ];

    protected $hidden = [
        'id',
        'transaction_id',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
