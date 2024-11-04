<?php

namespace App\Jobs\Schedule\Setting;

use Exception;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;

class DeleteExistingScheduleSetting
{
    /**
     * @var Schedule\Setting
     */
    public Schedule\Setting $setting;

    public function __construct(Schedule\Setting $setting)
    {
        $this->setting = $setting;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        DB::transaction(function () {
            $this->setting->details->each(fn ($detail) => $detail->forceDelete());
            $this->setting->forceDelete();
        });
    }
}
