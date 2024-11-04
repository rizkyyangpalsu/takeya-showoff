<?php

namespace App\Http\Controllers\Api\Accounting;

use Carbon\Carbon;
use App\Models\Office;
use Carbon\CarbonPeriod;
use App\Http\Controllers\Controller;
use Dentro\Accounting\Ledger\Report;

class StatementController extends Controller
{
    /**
     * @var Report
     */
    public Report $report;

    /**
     * StatementController constructor.
     * @param Report $report
     */
    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    /**
     * Balance Sheet statement
     * Route Path       : /v1/accounting/statement/balance-sheet
     * Route Name       : api.accounting.statement.balance-sheet
     * Route Path       : GET.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Dentro\Accounting\Exceptions\StatementNotFoundException
     */
    public function balanceSheet(): \Illuminate\Http\JsonResponse
    {
        return $this->success($this->getStatement('balance_sheet'));
    }

    /**
     * Cash Flow statement
     * Route Path       : /v1/accounting/statement/cash-flow
     * Route Name       : api.accounting.statement.cash-flow
     * Route Path       : GET.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Dentro\Accounting\Exceptions\StatementNotFoundException
     */
    public function cashFlow(): \Illuminate\Http\JsonResponse
    {
        return $this->success($this->getStatement('cash_flow'));
    }

    /**
     * Income statement
     * Route Path       : /v1/accounting/statement/income
     * Route Name       : api.accounting.statement.income
     * Route Path       : GET.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Dentro\Accounting\Exceptions\StatementNotFoundException
     */
    public function income(): \Illuminate\Http\JsonResponse
    {
        return $this->success($this->getStatement('income'));
    }

    /**
     * Get statement by key.
     *
     * @param $type
     * @return array
     * @throws \Dentro\Accounting\Exceptions\StatementNotFoundException
     */
    protected function getStatement($type): array
    {
        $startDate = request('date.0', Carbon::now()->startOfMonth()->toDateString());
        $endDate = request('date.1', Carbon::now()->endOfMonth()->toDateString());

        $groupCode = null;

        if (request()->filled('office_hash')) {
            $groupCode = Office::hashToId(request('office_hash'));
        }

        return $this->report->getStatement($type, $groupCode, CarbonPeriod::create($startDate, $endDate));
    }
}
