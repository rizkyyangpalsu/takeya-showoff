<?php

namespace App\Support\Accounting;

use App\Models\Office;
use App\Models\Accounting\Account;
use Illuminate\Database\Eloquent\Builder;

trait AccountResolver
{
    /**
     * Search account by its code and scope.
     *
     * @param $code
     * @param Office|null $office
     * @return \Illuminate\Database\Concerns\BuildsQueries|Builder|\Illuminate\Database\Eloquent\Model|mixed
     */
    public function accountByCode($code, ?Office $office = null)
    {
        return Account::query()
            ->where('code', $code)
            ->when(! is_null($office), fn (Builder $query) => $query->where('group_code', $office->id))
            ->first();
    }
}
