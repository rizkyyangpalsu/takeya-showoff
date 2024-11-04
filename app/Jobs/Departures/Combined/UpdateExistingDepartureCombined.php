<?php

namespace App\Jobs\Departures\Combined;

use App\Models\Accounting\Account;
use App\Models\Departure;
use App\Models\Departure\Combined;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class UpdateExistingDepartureCombined
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Combined
     */
    public Combined $combined;

    /**
     * @var array
     */
    public array $attributes;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Combined $combined, array $attributes)
    {
        $this->attributes = Validator::make($attributes, [
            'departures' => ['required', 'array'],
            'departures.*' => [new ExistsByHash(Departure::class)],
            'name' => 'required',
        ])->validate();

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
        $departures = collect($this->attributes['departures'])->map(fn ($hash) => Departure::hashToId($hash))->toArray();

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

        foreach ($departures as $departure_id) {
            $departure = Departure::query()
                ->with('allowances')
                ->where('id', $departure_id)
                ->first();

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

        $this->attributes['total_allowances'] = $allowance_amounts;
        $this->attributes['total_incomes'] = $income_amounts;
        $this->attributes['total_costs'] = $cost_amounts;

        // create new combined
        $this->combined->fill($this->attributes);
        $this->combined->save();

        // sync relation departures
        $this->combined->departures()->sync($departures);
    }
}
