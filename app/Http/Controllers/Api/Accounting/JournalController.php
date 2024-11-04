<?php

namespace App\Http\Controllers\Api\Accounting;

use Carbon\Carbon;
use App\Models\Accounting\Journal;
use App\Http\Controllers\Controller;
use App\Jobs\Accounting\Journal\CreateNewJournal;
use App\Jobs\Accounting\Journal\DeleteExistingJournal;
use App\Jobs\Accounting\Journal\UpdateExistingJournal;

class JournalController extends Controller
{
    /**
     * Journal list
     * Route Path       : /v1/accounting/journal/
     * Route Name       : api.accounting.journal
     * Route Path       : GET.
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function index()
    {
        $startDate = request('date.0', Carbon::now()->startOfMonth()->toDateString());
        $endDate = request('date.1', Carbon::now()->endOfMonth()->toDateString());

        $query = Journal::query()
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        return $query->paginate(request('per_page', 15));
    }

    /**
     * Create manual journal.
     * Route Path       : /v1/accounting/journal/
     * Route Name       : api.accounting.journal.store
     * Route Path       : POST.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store()
    {
        $job = new CreateNewJournal(request()->all(), request()->user());
        $this->dispatch($job);

        return $this->success($job->journal->toArray());
    }

    /**
     * Journal detail
     * Route Path       : /v1/accounting/journal/{journal_hash}
     * Route Name       : api.accounting.journal.show
     * Route Path       : GET.
     *
     * @param Journal $journal
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Journal $journal)
    {
        return $this->success($journal->load('entries')->toArray());
    }

    /**
     * Update manual journal.
     * Route Path       : /v1/accounting/journal/
     * Route Name       : api.accounting.journal.update
     * Route Path       : PUT.
     *
     * @param Journal $journal
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Journal $journal)
    {
        $job = new UpdateExistingJournal(request()->all(), $journal, request()->user());
        $this->dispatch($job);

        return $this->success($job->journal->toArray());
    }

    /**
     * Delete manual journal.
     * Route Path       : /v1/accounting/journal/
     * Route Name       : api.accounting.journal.update
     * Route Path       : PUT.
     *
     * @param Journal $journal
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Journal $journal)
    {
        $job = new DeleteExistingJournal($journal);
        $this->dispatch($job);

        return $this->success($job->journal->toArray());
    }
}
