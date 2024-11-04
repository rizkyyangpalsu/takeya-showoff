<?php

namespace App\Models\Accounting;

use App\Models\Office;
use Illuminate\Database\Eloquent\Builder;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Dentro\Accounting\Entities\Account as BaseAccount;

class Account extends BaseAccount
{
    use HashableId;

    protected $hidden = [
        'id', 'debit', 'credit',
    ];

    protected $appends = [
        'hash', 'balance',
    ];

    protected $casts = [
        'debit' => 'int',
        'credit' => 'int',
    ];

    public function office()
    {
        return $this->belongsTo(Office::class, 'group_code', 'id');
    }

    public function scopeSearch(Builder $builder, $keyword)
    {
        return $builder
            ->where(function (Builder $query) use ($keyword) {
                $query
                    ->where('description', 'ilike', "%{$keyword}%")
                    ->when(is_numeric($keyword), function (Builder $query2) use ($keyword) {
                        // it's intended to search from the left to right
                        $query2->orWhere('code', 'like', "{$keyword}%");
                    });
            });
    }

    public function scopeHierarchy(Builder $builder, $scope)
    {
        switch ($scope) {
            case 'global': return $builder->whereNull('group_code');
            case 'central': return $builder->whereIn('group_code', Office::query()->whereNull('office_id')->pluck('id'));
            case 'branch': return $builder->whereIn('group_code', Office::query()->whereNotNull('office_id')->pluck('id'));
        }
        // default
        return $builder->whereNull('group_code');
    }

    public function scopeGroupCode(Builder $builder, $groupCode)
    {
        return $builder->where('group_code', $groupCode);
    }
}
