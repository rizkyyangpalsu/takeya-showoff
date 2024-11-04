<?php

namespace App\Jobs\Rule;

use App\Models\Rule;
use App\Concerns\HasRules;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule as ValidationRule;
use App\Models\Schedule\Setting\Detail\PriceModifier;

class CreateNewRule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Rule|null
     */
    public ?Rule $rule = null;

    private array $attributes;

    /**
     * @var Model|PriceModifier|null
     */
    private ?Model $applicable;

    /**
     * Create a new job instance.
     *
     * @param Model|null $applicable
     * @param array $attributes
     * @throws ValidationException
     */
    public function __construct(?Model $applicable = null, array $attributes = [])
    {
        $this->attributes = Validator::make($attributes, [
            'logical_operator' => 'required',
            'assertion' => 'nullable|bool',
            'is_active' => 'nullable|bool',
            'expired_at' => 'nullable|datetime',
            'items' => 'nullable|array',
            'items.*.context' => 'required',
            'items.*.context_property' => 'required',
            'items.*.operator' => ['required', ValidationRule::in(array_keys(config('rules.operators')))],
            'items.*.value' => 'required',
            'items.*.value_type' => ['required', ValidationRule::in(config('datatype'))],
            'items.*.assertion' => 'required|boolean',
        ])->validate();

        $this->applicable = $applicable;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function handle()
    {
        if ($this->applicable && in_array(HasRules::class, (new \ReflectionClass($this->applicable))->getTraitNames())) {
            return $this->applicable->rules()->create($this->attributes);
        }

        $this->rule = new Rule();

        $this->rule->fill($this->attributes);

        $this->rule->save();
    }
}
