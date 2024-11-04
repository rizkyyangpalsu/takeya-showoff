<?php

namespace App\Models\Route\Track;

use App\Concerns\Geo\HasGeoColumn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Point.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $terminal
 * @property-read object|null $detail
 * @property-read string $hash
 * @property-read Carbon|null eta only available while in App\Support\Schedule\Item class
 * @property-read Carbon|null etd only available while in App\Support\Schedule\Item class
 */
class Point extends Model
{
    use HasFactory, HashableId, HasGeoColumn;

    protected $fillable = [
        'code',
        'name',
        'terminal',
        'province_id',
        'regency_id',
    ];

    protected $hidden = [
        'id',
        'province_id',
        'regency_id',
    ];

    protected $appends = [
        'hash',
    ];
}
