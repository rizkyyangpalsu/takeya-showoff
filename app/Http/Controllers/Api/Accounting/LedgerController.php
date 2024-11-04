<?php

namespace App\Http\Controllers\Api\Accounting;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Http\Controllers\Controller;
use Dentro\Accounting\Ledger\Poster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    /**
     * General ledger poster instance.
     *
     * @var Poster
     */
    private Poster $poster;

    /**
     * LedgerController constructor.
     * @param Poster $poster
     */
    public function __construct(Poster $poster)
    {
        $this->poster = $poster;
    }

    /**
     * Get general ledger.
     *
     * Route Path       : /v1/accounting/general-ledger
     * Route Name       : api.accounting.general-ledger
     * Route Path       : POST.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $startDate = request('date.0', Carbon::now()->startOfMonth()->toDateString());
        $endDate = request('date.1', Carbon::now()->endOfMonth()->toDateString());

        $periodParams = [
            $startDate, $endDate,
        ];

        if ($request->isNotFilled('date.0')) {
            $periodParams[] = CarbonPeriod::EXCLUDE_START_DATE;
        }

        $this->poster->period(CarbonPeriod::create($periodParams));

        return $this->success(
            request('by_account_type', true)
            ? $this->poster->summaryByAccountType()->get()->toArray()
            : $this->poster->summary()->get()->toArray()
        );
    }

    /**
     * Post a general ledger.
     * Route Path       : /v1/accounting/general-ledger
     * Route Name       : api.accounting.general-ledger.store
     * Route Path       : POST.
     *
     * @return JsonResponse
     */
    public function store()
    {
        $startDate = request('date.0', Carbon::now()->startOfMonth()->toDateString());
        $endDate = request('date.1', Carbon::now()->endOfMonth()->toDateString());

        $this->poster
            ->period(CarbonPeriod::create($startDate, $endDate))
            ->post();

        return $this->success();
    }
}
