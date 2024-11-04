<?php

namespace App\Jobs\Rule\Item;

use App\Models\Rule;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule as ValidationRule;

class CreateNewRuleItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Rule
     */
    private Rule $rule;

    private array $attributes;

    /**
     * Create a new job instance.
     *
     * @param Rule $rule
     * @param array $attributes
     * @throws ValidationException
     */
    public function __construct(Rule $rule, array $attributes)
    {
        $this->attributes = Validator::make($attributes, [
            'context' => 'required',
            'context_property' => 'required',
            'operator' => ['required', ValidationRule::in(array_keys(config('rules.operators')))],
            'value' => 'required',
            'value_type' => ['required', ValidationRule::in(config('rules.datatype'))],
            'assertion' => 'required|boolean',
        ])->validate();

        $this->rule = $rule;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->rule->items()->create($this->attributes);
    }
}
