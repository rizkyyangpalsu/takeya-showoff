<?php

namespace App\Models\Logistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;

class Service extends Model
{
    use HasFactory;
    use HashableId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'logistic_services';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'etd',
        'price_weight',
        'min_weight',
        'price_volume',
        'min_volume',
        'credit',
    ];

    protected $appends = [
        'hash',
    ];

    protected $hidden = [
        'id',
    ];
}
