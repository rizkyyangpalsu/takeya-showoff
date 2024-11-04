<?php

namespace App\Jobs\Departures\Combined;

use App\Models\Accounting\Account;
use App\Models\Departure\Combined;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateExistingDepartureCombined
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Combined
     */
    public Combined $combined;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Combined $combined)
    {
        $this->combined = $combined;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // convert array of hash to array of id
        $departures = $this->combined->departures()->with('allowances')->get();

        $allowance_amounts = 0;
        $income_amounts = $this->combined->journals()->with(['entries.author', 'entries.account', 'office'])
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
            ->get()
            ->sum('amount');

        $cost_amounts = $this->combined->journals()->with(['entries.author', 'entries.account', 'office'])
            ->whereHas(
                'entries',
                fn (Builder $builder) => $builder->whereHas(
                    'account',
                    fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE)
                )
            )
            ->latest()
            ->get()
            ->sum('amount');

        foreach ($departures as $departure) {
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

            $allowance_amounts += $departure->allowances->sum('amount');
            $income_amounts += $incomes->sum('amount');
            $cost_amounts += $costs->sum('amount');
        }

        $attributes = [];
        $attributes['total_allowances'] = $allowance_amounts;
        $attributes['total_incomes'] = $income_amounts;
        $attributes['total_costs'] = $cost_amounts;

        // create new combined
        $this->combined->fill($attributes);
        $this->combined->save();
    }
}
