<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;

class Item extends Model
{
    use HasFactory;
    use HashableId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'logistic_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'logistic_delivery_id',
        'description',
        'price_id',
        'weight',
        'volume',
        'value',
        'shipping_cost',
        'status',
        'receipt',
        'price_title_type',
        'price_calc_type',
        'price_calc_type_value'
    ];

    protected $appends = [
        'hash',
    ];

    protected $hidden = [
        'id',
        'logistic_delivery_id',
        'price_id',
        'last_manifest',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'logistic_delivery_id');
    }

    public function price()
    {
        return $this->belongsTo(Price::class, 'price_id');
    }

    public function manifests()
    {
        return $this->belongsToMany(Manifest::class, 'logistic_manifest_item', 'logistic_item_id', 'logistic_manifest_id');
    }

    public function lastManifest()
    {
        return $this->belongsTo(Manifest::class, 'last_manifest');
    }
}
