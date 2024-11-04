<?php

namespace App\Jobs\Schedule\Setting\Detail;

use App\Models\Schedule\Setting\Detail;

class DeleteExistingSettingDetail
{
    /**
     * @var Detail
     */
    public Detail $settingDetail;

    /**
     * DeleteExistingSettingDetail constructor.
     * @param Detail $detail
     */
    public function __construct(Detail $detail)
    {
        $this->settingDetail = $detail;
    }

    public function handle()
    {
        // TODO: check constrains later...

        $this->settingDetail->delete();
    }
}
