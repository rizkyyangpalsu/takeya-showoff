<?php

namespace App\Models\Departure;

use App\Models\Office;
use App\Models\Departure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Dentro\Accounting\Concerns\HasJournals;
use Dentro\Accounting\Contracts\Recordable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Allowance.
 *
 * @property  string  name
 * @property  string  description
 * @property  float  amount
 * @property-read  Departure  departure
 * @property-read  Office  office
 * @property-read  \App\Models\Office\Staff  executor
 * @property-read  \App\Models\Office\Staff  receiver
 * @property  int|string  departure_id
 * @property  int  id
 */
class Allowance extends Model implements Recordable
{
    use HasFactory, HashableId, HasJournals;

    protected $table = 'departure_allowances';

    protected $fillable = [
        'name',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected $hidden = [
        'id',
        'office_id',
        'departure_id',
        'executor_id',
        'receiver_id',
    ];

    protected $with = [
        'departure', 'office', 'executor', 'receiver',
    ];

    protected $appends = [
        'hash',
    ];

    public function departure(): Relations\BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    public function office(): Relations\BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function executor(): Relations\BelongsTo
    {
        return $this->belongsTo(Office\Staff::class, 'executor_id');
    }

    public function receiver(): Relations\BelongsTo
    {
        return $this->belongsTo(Office\Staff::class, 'receiver_id');
    }
}
