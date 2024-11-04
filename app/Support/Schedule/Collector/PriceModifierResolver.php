<?php

namespace App\Support\Schedule\Collector;

use App\Models\Rule;
use App\Rules\Contexts;
use App\Models\Schedule\Setting\Detail;
use Illuminate\Database\Query\JoinClause;

trait PriceModifierResolver
{
    protected function loadRelationPriceModifierFor(Detail $detail, $departureId, $destinationId): void
    {
        $filteredPriceModifiers = $detail->priceModifiers->filter(function (Detail\PriceModifier $priceModifier) use ($departureId, $destinationId) {
            $priceModifierItemsQuery = $priceModifier->items()->join(
                'prices',
                fn (JoinClause $clause) => $clause
                    ->on('price_modifier_items.price_id', '=', 'prices.id')
                    ->select(['price_modifier_items.*', 'prices.nominal'])
                    ->where('prices.origin_id', $departureId)
                    ->where('prices.destination_id', $destinationId)
                    ->where('prices.nominal', '>', 0)
            );

            if ($priceModifierItemsQuery->doesntExist() || ! $priceModifier->rules->every(fn (Rule $rule) => $rule->assert(new Contexts\Request()))) {
                return false;
            }

            // manually set relations of priceModifier items to be specific for given departure and destination
            $priceModifier->setRelation('items', $priceModifierItemsQuery->cursor());

            return true;
        });

        // override priceModifiers to only filtered data
        $detail->setRelation('priceModifiers', $filteredPriceModifiers);
    }
}
