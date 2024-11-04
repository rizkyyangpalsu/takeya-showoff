<?php

namespace App\Actions\Logistic\Service;

class DeleteExistingService
{
    public function create($service)
    {
        $service->delete();

        return $service;
    }
}
