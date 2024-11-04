<?php

namespace App\Http\Controllers\Api\Departure;

use App\Http\Controllers\Controller;
use App\Jobs\Departures\Combined\CalculateExistingDepartureCombined;
use App\Jobs\Departures\Combined\CreateNewDepartureCombined;
use App\Jobs\Departures\Combined\DeleteExistingDepartureCombined;
use App\Jobs\Departures\Combined\UpdateExistingDepartureCombined;
use App\Models\Accounting\Account;
use App\Models\Departure\Combined;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class CombinedController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Combined::query()->with('departures', 'user', 'office')->orderByDesc('created_at');

        $likeClause = $this->getMatchLikeClause($query);

        $query->when(
            $request->input('keyword'),
            fn (Builder $builder, string $keyword) => $builder->where('name', $likeClause, "%$keyword%")
        )->when(
            $request->input('office_hash'),
            fn (Builder $builder, string $office_hash) => $builder->where(
                'office_id',
                Office::hashToId($office_hash)
            )
        );

        return $query->paginate($request->input('per_page', 30));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $job = new CreateNewDepartureCombined($request->all());

        $this->dispatch($job);

        return $this->success($job->combined->toArray())->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param Combined $combined
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Combined $combined): \Illuminate\Http\JsonResponse
    {
        $combined->load(['departures' => function ($query) {
            $query->with('allowances', 'origin', 'destination', 'crews');
        }, 'user', 'office']);
        $combined->departures->map(function ($departure) {
            $incomes = $departure->journals()
                ->with(['entries.author', 'entries.account', 'office'])
                ->whereHas(
                    'entries',
                    fn (Builder $builder) => $builder->whereHas(
                        'account',
                        fn (Builder $builder) => $builder->whereIn('code', [
                            config('tiara.accounting.unearned_revenue'),
                            config('tiara.accounting.revenue'),
                        ])
                    )
                )
                ->latest()
                ->get();

            $costs = $departure->journals()
                ->with(['entries.author', 'entries.account', 'office'])
                ->whereHas(
                    'entries',
                    fn (Builder $builder) => $builder->whereHas(
                        'account',
                        fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE)
                    )
                )
                ->latest()
                ->get();

            $departure->setRelation('incomes', $incomes);
            $departure->setRelation('costs', $costs);
            return $departure;
        });

        $combined->costs = $combined->journals()->with(['entries.author', 'entries.account', 'office'])
            ->whereHas(
                'entries',
                fn (Builder $builder) => $builder->whereHas(
                    'account',
                    fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE)
                )
            )
            ->latest()
            ->get();

        $combined->incomes = $combined->journals()->with(['entries.author', 'entries.account', 'office'])
            ->whereHas(
                'entries',
                fn (Builder $builder) => $builder->whereHas(
                    'account',
                    fn (Builder $builder) => $builder->whereIn('code', [
                        config('tiara.accounting.unearned_revenue'),
                        config('tiara.accounting.revenue'),
                    ])
                )
            )
            ->latest()
            ->get();

        return $this->success($combined);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Combined $combined
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Combined $combined): \Illuminate\Http\JsonResponse
    {
        $job = new UpdateExistingDepartureCombined($combined, $request->all());

        $this->dispatch($job);

        return $this->success($job->combined->toArray());
    }

    /**
     * @param Combined $combined
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculate(Combined $combined): \Illuminate\Http\JsonResponse
    {
        $job = new CalculateExistingDepartureCombined($combined);

        $this->dispatch($job);

        return $this->success($job->combined->toArray());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Combined $combined
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Combined $combined): \Illuminate\Http\JsonResponse
    {
        $job = new DeleteExistingDepartureCombined($combined);

        $this->dispatch($job);

        return $this->success($job->combined->toArray());
    }
}
