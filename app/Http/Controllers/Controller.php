<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\PostgresConnection;
use Illuminate\Http\Request;
use App\Concerns\BasicResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use JetBrains\PhpStorm\Pure;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, BasicResponse;

    /**
     * Resolve current user.
     *
     * @return User
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public function user(): User
    {
        return auth()->user();
    }

    protected function applySort(Builder $query, Request $request): void
    {
        $inputs = $request->validate([
            'order' => 'nullable|array',
            'order.*.field' => 'required',
            'order.*.direction' => 'nullable|in:asc,desc',
        ]);

        $orderInput = collect($inputs['order'] ?? []);

        $query->when(
            $orderInput->isNotEmpty(),
            fn (Builder $builder) => $orderInput->each(
                fn (array $data) => $builder->orderBy($data['field'], $data['direction'] ?? 'asc')
            ),
            fn (Builder $builder) => $builder->orderByDesc('created_at')
        );
    }

    #[Pure]
    protected function getMatchLikeClause(Builder|Relation $query): string
    {
        return match (true) {
            $query->getConnection() instanceof PostgresConnection => 'ilike',
            default => 'like',
        };
    }
}
