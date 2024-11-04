<?php

namespace App\Models\Schedule\Setting\Detail\PriceModifier;

use Carbon\Carbon;
use App\Models\Route\Price;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Item.
 *
 * @property  int price_modifier_id
 * @property  int amount
 * @property  Carbon created_at
 * @property  Carbon updated_at
 * @property-read  Price price
 * @property  int id
 * @property  int price_id
 */
class Item extends Model
{
    use HasFactory, HashableId;

    protected $fillable = [
        'price_id',
        'amount',
    ];

    protected $hidden = [
        'id',
        'price_id',
        'price_modifier_id',
    ];

    protected $appends = [
        'hash',
    ];

    protected $casts = [
        'amount' => 'int',
    ];

    protected $table = 'price_modifier_items';

    public function price(): Relations\BelongsTo
    {
        return $this->belongsTo(Price::class);
    }
}
