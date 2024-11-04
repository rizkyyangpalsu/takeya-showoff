<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Constant.
 *
 * @property string $name
 * @property string $value
 * @property-read string $hash
 */
class Constant extends Model
{
    use HasFactory, HashableId;

    public const NAME_PAYMENT_METHOD = 'payment_method';

    protected $fillable = [
        'name',
        'value',
    ];

    protected $hidden = ['id', 'pivot'];

    protected $appends = ['hash'];
}
