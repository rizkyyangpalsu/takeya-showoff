<?php

namespace App\Jobs\Schedule\Setting\Detail\PriceModifier;

use Illuminate\Validation\ValidationException;
use App\Models\Schedule\Setting\Detail\PriceModifier;

class DeleteExistingPriceModifier
{
    /**
     * @var PriceModifier
     */
    public PriceModifier $priceModifier;

    /**
     * DeleteExistingPriceModifier constructor.
     *
     * @param \App\Models\Schedule\Setting\Detail\PriceModifier $priceModifier
     * @throws \Throwable
     */
    public function __construct(PriceModifier $priceModifier)
    {
        $this->priceModifier = $priceModifier;

        throw_if($priceModifier->priority == 1, ValidationException::withMessages(['priority' => __('cannot delete primary price modifier')]));
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->priceModifier->delete();
    }
}
