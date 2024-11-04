<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Rule;
use App\Rules\Contexts\Request;
use App\Jobs\Rule\CreateNewRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     * @throws \Throwable
     */
    public function testRuleAssertionTrueWithAnd()
    {
        $createNewRule = new CreateNewRule(null, [
            'logical_operator' => 'AND',
        ]);

        dispatch_now($createNewRule);

        $createNewRule->rule->items()->createMany([
            [
                'context' => 'request',
                'context_property' => 'day',
                'operator' => 'EQUAL_TO',
                'value' => 'Sunday',
                'value_type' => 'string',
            ],
            [
                'context' => 'request',
                'context_property' => 'day',
                'operator' => 'NOT_EQUAL_TO',
                'value' => 'Monday',
                'value_type' => 'string',
            ],
        ]);

        /** @var Rule $rule */
        $rule = Rule::query()->first();

        request()->merge(['day' => 'Sunday']);

        $this->assertEquals(true, $rule->assert(new Request()));
    }

    /**
     * A basic feature test example.
     *
     * @return void
     * @throws \Throwable
     */
    public function testRuleAssertionFalseWithXor()
    {
        $createNewRule = new CreateNewRule(null, [
            'logical_operator' => 'XOR',
        ]);

        dispatch_now($createNewRule);

        $createNewRule->rule->items()->createMany([
            [
                'context' => 'request',
                'context_property' => 'day',
                'operator' => 'EQUAL_TO',
                'value' => 'Sunday',
                'value_type' => 'string',
            ],
            [
                'context' => 'request',
                'context_property' => 'day',
                'operator' => 'NOT_EQUAL_TO',
                'value' => 'Monday',
                'value_type' => 'string',
            ],
        ]);

        /** @var Rule $rule */
        $rule = Rule::query()->first();

        request()->merge(['day' => 'Sunday']);

        $this->assertEquals(false, $rule->assert(new Request()));
    }
}
