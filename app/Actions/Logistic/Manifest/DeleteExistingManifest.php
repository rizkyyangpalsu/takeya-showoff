<?php

namespace App\Actions\Logistic\Manifest;

class DeleteExistingManifest
{
    public function delete($manifest)
    {
        $manifest->delete();

        return $manifest;
    }
}
