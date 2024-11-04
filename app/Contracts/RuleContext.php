<?php

namespace App\Contracts;

interface RuleContext
{
    /**
     * Get available property that will be used for assertions,.
     *
     * @return array
     */
    public function getContextProperties(): array;

    /**
     * Get value for the context.
     *
     * @param $property
     *
     * @return mixed
     */
    public function getValueForContext($property);

    /**
     * Get context name.
     *
     * @return string
     */
    public function getContextName(): string;
}
