<?php

namespace App\Models\Rule;

use Throwable;
use Ruler\Variable;
use RuntimeException;
use Illuminate\Support\Collection;
use Ruler\Operator\VariableOperator;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Item.
 *
 * @property  int  id
 * @property  string  context
 * @property  string  context_property
 * @property  string  operator
 * @property  string  value
 * @property  string  value_type
 * @property  string  assertion
 */
class Item extends Model
{
    use HasFactory, HashableId;

    protected $table = 'rule_items';

    protected $fillable = [
        'context',
        'context_property',
        'operator',
        'value',
        'value_type',
        'assertion',
    ];

    protected $casts = [
        'assertion' => 'boolean',
    ];

    protected $hidden = [
        'id',
    ];

    protected $appends = [
        'hash',
    ];

    /**
     * @param $originalValue
     * @return bool|Collection|int|mixed|string|null
     * @noinspection PhpUnused
     */
    public function getValueAttribute($originalValue)
    {
        $this->casts['value'] = $this->getRawOriginal('value_type');

        return $this->castAttribute('value', $originalValue);
    }

    /**
     * @param Variable $expected
     * @param Variable $actual
     * @return VariableOperator
     * @throws Throwable
     * @noinspection PhpParamsInspection
     */
    public function resolveOperator(Variable $expected, Variable $actual): VariableOperator
    {
        $operator = config(
            'rules.operators.'.$this->operator,
            fn () => class_exists($this->operator) ? $this->operator : null
        );

        throw_if(empty($operator), RuntimeException::class, "Operator {$this->operator} not found!.");

        return new $operator($expected, $actual);
    }
}
