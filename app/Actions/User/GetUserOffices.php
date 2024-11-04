<?php

namespace App\Actions\User;

use App\Models\User;
use App\Models\Office;
use Lorisleiva\Actions\Concerns\AsAction;

class GetUserOffices
{
    use AsAction;

    public function asController(User $user): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if ($user->user_type === User::USER_TYPE_SUPER_ADMIN) {
            return Office::query()->paginate();
        }

        return $user->original_offices()->paginate();
    }
}
