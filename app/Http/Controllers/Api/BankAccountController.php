<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\BankAccount\CreateNewBankAccount;
use App\Jobs\BankAccount\DeleteExistingBankAccount;
use App\Jobs\BankAccount\UpdateExistingBankAccount;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BankAccountController extends Controller
{
    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = BankAccount::query()->orderBy('name');

        $likeClause = $this->getMatchLikeClause($query);

        $query->when(
            $request->input('keyword'),
            fn (Builder $builder, string $keyword) => $builder->where(
                fn (Builder $builder) => $query
                ->orWhere('name', $likeClause, "%$keyword%")
                ->orWhere('account', $likeClause, "%$keyword%")
            )
        );

        return $query->paginate($request->input('per_page', 30));
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $job = new CreateNewBankAccount($request->all());
        $this->dispatch($job);

        return $this->success($job->bankAccount->toArray())->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param BankAccount $bankAccount
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(BankAccount $bankAccount, Request $request): \Illuminate\Http\JsonResponse
    {
        $job = new UpdateExistingBankAccount($bankAccount, $request->all());
        $this->dispatch($job);

        return $this->success($job->bankAccount->toArray());
    }

    /**
     * @param BankAccount $bankAccount
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(BankAccount $bankAccount): \Illuminate\Http\JsonResponse
    {
        $job = new DeleteExistingBankAccount($bankAccount);
        $this->dispatch($job);

        return $this->success($job->bankAccount->toArray());
    }
}
