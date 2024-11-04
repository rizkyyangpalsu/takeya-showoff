<?php

namespace App\Http\Controllers\Api\Accounting;

use Carbon\Carbon;
use App\Models\Office;
use App\Models\Accounting\Account;
use App\Http\Controllers\Controller;
use App\Jobs\Accounting\CreateNewAccount;
use App\Jobs\Accounting\DeleteExistingAccount;
use App\Jobs\Accounting\UpdateExistingAccount;
use Dentro\Accounting\Ledger\ChartOfAccount\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    /**
     * @var Builder
     */
    public Builder $accountBuilder;

    /**
     * AccountController constructor.
     * @param Builder $accountBuilder
     */
    public function __construct(Builder $accountBuilder)
    {
        $this->accountBuilder = $accountBuilder;
    }

    /**
     * Account list
     * Route Path       : /v1/accounting/account
     * Route Name       : api.accounting.account
     * Route Path       : GET.
     *
     * @param Request $request
     * @return Paginator
     */
    public function index(Request $request): Paginator
    {
        return $this->accountBuilder
            ->getQuery()
            ->with('office')
            ->when($request->filled('scope'), function ($query) {
                $query->hierarchy(request('scope'));
            })->when($request->filled('office_hash'), function ($query) {
                $query->where(fn (QueryBuilder $query) => $query->whereNull('group_code')
                    ->orWhere('group_code', Office::hashToId(request('office_hash'))));
            })->when($request->filled('type_code'), function ($query) {
                if (is_array('type_code')) {
                    $query->whereIn('type_code', request('type_code'));
                } else {
                    $query->where('type_code', request('type_code'));
                }
            })->when($request->filled('keyword'), function ($query) {
                $query->search(request('keyword'));
            })->orderBy('group_code')->orderBy('code')->paginate(request('per_page', 15));
    }

    /**
     * Account list
     * Route Path       : /v1/accounting/account/{account_hash}
     * Route Name       : api.accounting.account.show
     * Route Path       : GET.
     *
     * @param Account $account
     * @return JsonResponse
     */
    public function show(Account $account): JsonResponse
    {
        return $this->success($account->toArray());
    }

    /**
     * Account list
     * Route Path       : /v1/accounting/account/
     * Route Name       : api.accounting.account.store
     * Route Path       : POST.
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store()
    {
        $job = new CreateNewAccount(request()->all());

        $this->dispatch($job);

        return $this->success($job->account->fresh()->toArray());
    }

    /**
     * Account list
     * Route Path       : /v1/accounting/account/{account_hash}
     * Route Name       : api.accounting.account.update
     * Route Path       : PUT.
     *
     * @param Account $account
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Account $account)
    {
        $job = new UpdateExistingAccount($account, request()->all());

        $this->dispatchSync($job);

        return $this->success($job->account->fresh()->toArray());
    }

    /**
     * Account list
     * Route Path       : /v1/accounting/account/{account_hash}
     * Route Name       : api.accounting.account.destroy
     * Route Path       : DELETE.
     *
     * @param Account $account
     * @return JsonResponse
     */
    public function destroy(Account $account)
    {
        $job = new DeleteExistingAccount($account);

        $this->dispatch($job);

        return $this->success($job->account->toArray());
    }

    /**
     * Get account entries.
     * Route Path       : /v1/accounting/account/{account_hash}/entries
     * Route Name       : api.accounting.account.entry
     * Route Path       : GET.
     *
     * @param Account $account
     * @return Paginator
     */
    public function entries(Account $account): Paginator
    {
        $startDate = request('date.0', Carbon::now()->startOfMonth()->toDateString());
        $endDate = request('date.1', Carbon::now()->endOfMonth()->toDateString());

        $query = $account->entries()
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        return $query->simplePaginate(request('per_page', 15));
    }
}
