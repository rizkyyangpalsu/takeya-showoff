<?php

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Customer\Transaction;
use App\Models\Departure;
use Illuminate\Database\Eloquent\Builder;
use Jalameta\Patcher\Patch;

class FixNoteOfJournals extends Patch
{
    /**
     * Run patch script.
     *
     * @return void
     */
    public function patch()
    {
        Departure::query()->cursor()->each(function (Departure $departure) {
            $departure->journals()
                ->whereHas(
                    'entries',
                    fn (Builder $builder) => $builder->whereHas(
                        'account',
                        fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE)
                    )
                )->cursor()->each(
                    fn (Journal $journal) => $journal
                    ->setAttribute('note', trans('tiara.note.departure.cost', [
                        'name' => $departure->name,
                    ]))
                    ->save()
                );

            $departure->journals()
                ->whereHas(
                    'entries',
                    fn (Builder $builder) => $builder->whereHas(
                        'account',
                        fn (Builder $builder) => $builder->whereIn('code', [
                            config('tiara.accounting.unearned_revenue'),
                            config('tiara.accounting.revenue'),
                        ])
                    )
                )->cursor()->each(
                    fn (Journal $journal) => $journal
                    ->setAttribute('note', trans('tiara.note.departure.income', [
                        'name' => $departure->name,
                    ]))
                    ->save()
                );
        });

        Transaction::query()
            ->whereNotNull('paid_at')->whereHas('journals')
            ->cursor()->each(function (Transaction $transaction) {
                $transaction->journals()
                    ->cursor()->each(fn (Journal $journal) => $journal
                        ->setAttribute('note', trans('tiara.note.transaction', [
                            'name' => $transaction?->user?->name,
                            'user_type' => $transaction?->user?->user_type,
                        ]))->save());
            });
    }
}
