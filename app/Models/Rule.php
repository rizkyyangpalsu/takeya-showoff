<?php

namespace App\Models;

use Throwable;
use Carbon\Carbon;
use Ruler\Context;
use LogicException;
use Ruler\Variable;
use RuntimeException;
use App\Contracts\RuleContext;
use Illuminate\Support\Collection;
use Ruler\Operator\LogicalOperator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Rule.
 *
 * @property  string name
 * @property  string logical_operator
 * @property  bool assertion
 * @property  bool is_active
 * @property  Carbon expired_at
 * @property-read  Collection items
 */
class Rule extends Model
{
    use HasFactory, HashableId;

    protected $table = 'rules';

    protected $fillable = [
        'name',
        'logical_operator',
        'assertion',
        'is_active',
        'expired_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assertion' => 'boolean',
        'expired_at' => 'datetime',
    ];

    protected $hidden = [
        'id',
        'applicable_type',
        'applicable_id',
    ];

    protected $with = [
        'items',
    ];

    protected $appends = [
        'hash',
    ];

    public function applicable(): Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function items(): Relations\HasMany
    {
        return $this->hasMany(Rule\Item::class);
    }

    /**
     * Assert the rule with the given contexts.
     *
     * @param mixed ...$contexts
     * @return bool
     * @throws Throwable
     */
    public function assert(...$contexts): bool
    {
        $givenContexts = collect($contexts)->reject(fn ($context) => ! $context instanceof RuleContext);

        // return default assertion when contexts is empty and/or has no rule items.
        if ($givenContexts->count() === 0 || $this->items->isEmpty()) {
            return $this->assertion;
        }

        return (new \Ruler\Rule(
            $this->resolveLogicalOperator(
                $this->items
                    ->map(fn (Rule\Item $item) => $item->resolveOperator(
                        new Variable('expected-item-'.$item->id, $item->value),
                        new Variable('actual-given-'.$item->id)
                    ))
            )
        ))->evaluate(new Context(
            $this->items
                ->keyBy(fn (Rule\Item $item) => 'actual-given-'.$item->id)
                ->map(function (Rule\Item $item) use ($givenContexts) {
                    /** @var RuleContext|null $entityContext */
                    $entityContext = $givenContexts->first(
                        fn (RuleContext $context) => $context->getContextName() === $item->context
                    );

                    throw_if(
                        $entityContext === null,
                        LogicException::class,
                        "Missing context: $item->context in provided contexts"
                    );

                    return $entityContext->getValueForContext($item->context_property);
                })
                ->toArray()
        ));
    }

    /**
     * Resolve logical operator.
     *
     * @param Collection $itemOperators
     * @return LogicalOperator
     * @throws Throwable
     */
    public function resolveLogicalOperator(Collection $itemOperators): LogicalOperator
    {
        $operator = config(
            'rules.operators.'.$this->logical_operator,
            fn () => class_exists($this->logical_operator) ? $this->logical_operator : null
        );

        throw_if(empty($operator), RuntimeException::class, "Operator {$this->logical_operator} not found!.");

        return new $operator($itemOperators->toArray());
    }
}
