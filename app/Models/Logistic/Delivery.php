<?php

namespace App\Models\Logistic;

use App\Models\Geo\Regency;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;

class Delivery extends Model
{
    use HasFactory;
    use HashableId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'logistic_deliveries';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'origin_city_id',
        'destination_city_id',
        'origin_office_id',
        'destination_office_id',
        'logistic_service_id',
        'code',
        'sender_name',
        'sender_phone',
        'sender_address',
        'sender_postal_code',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'recipient_postal_code',
        'payment_method',
        'payment_by',
        'payment_deadline',
        'other_cost',
        'discount',
        'is_printed_receipt',
        'paid',
        'taken',
        'payment_deadline_date',
        'origin_city_name',
        'destination_city_name',
        'origin_office_name',
        'destination_office_name',
        'service_name',
        'service_etd',
        'service_price_weight',
        'service_min_weight',
        'service_price_volume',
        'service_min_volume',
        'service_credit'
    ];

    protected $appends = [
        'hash',
        'status',
        'statuses'
    ];

    protected $hidden = [
        'id',
        'origin_city_id',
        'destination_city_id',
        'origin_office_id',
        'destination_office_id',
        'logistic_service_id',
    ];

    public function getStatusesAttribute()
    {
        return $this->getStatus('all');
    }

    public function getStatusAttribute()
    {
        return $this->getStatus();
    }

    public function originCity()
    {
        return $this->belongsTo(Regency::class, 'origin_city_id');
    }

    public function destinationCity()
    {
        return $this->belongsTo(Regency::class, 'destination_city_id');
    }

    public function originOffice()
    {
        return $this->belongsTo(Office::class, 'origin_office_id');
    }

    public function destinationOffice()
    {
        return $this->belongsTo(Office::class, 'destination_office_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'logistic_service_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'logistic_delivery_id', 'id');
    }

    private function getStatus($type = null)
    {
        $statuses = $this->items()->pluck('status')->countBy();

        $orderedStatus = [
            'in_warehouse'  => 1,
            'in_manifest'   => 2,
            'expedition'    => 3,
            'arrived'       => 4,
            'lost'          => 5
        ];
        $sortStatus = $statuses
            ->map(fn ($value, $key) => ['name' => $key, 'count' => $value])
            ->sortBy([
                fn ($current, $next) => $orderedStatus[$current['name']] <=> $orderedStatus[$next['name']],
                fn ($current, $next) => $next['count'] <=> $current['count'],
            ]);

        $data = null;
        if ($type == 'all') {
            $data = $sortStatus;
        } else {
            if ($sortStatus->first()) {
                $data = $sortStatus->first();
            }
        }

        return $data;
    }
}
