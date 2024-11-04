<?php

namespace App\Models\Logistic;

use App\Models\Fleet;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;

class Manifest extends Model
{
    use HasFactory;
    use HashableId {
        getHashAttribute as originalGetHashAttribute;
    }

    protected $table = 'logistic_manifests';

    protected $fillable = [
        'origin_office_id',
        'destination_office_id',
        'driver_id',
        'fleet_id',
        'pickup_time',
        'status',
        'code'
    ];

    protected $appends = [
        'hash',
    ];

    protected $hidden = [
        'id',
        'origin_office_id',
        'destination_office_id',
        'pivot',
        'fleet_id',
        'driver_id'
    ];

    public function originOffice()
    {
        return $this->belongsTo(Office::class, 'origin_office_id');
    }

    public function destinationOffice()
    {
        return $this->belongsTo(Office::class, 'destination_office_id');
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class, 'fleet_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'logistic_manifest_item', 'logistic_manifest_id', 'logistic_item_id');
    }

    public function getHashAttribute(): ?string
    {
        if (! $this->getKey()) {
            return null;
        }
        return $this->originalGetHashAttribute();
    }
}
