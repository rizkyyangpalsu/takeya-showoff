<?php

namespace App\Actions\Template;

class DeleteExistingTemplate
{
    public function destroy($template)
    {
        $template->delete();

        return $template;
    }
}
