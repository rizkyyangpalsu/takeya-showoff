<?php

namespace App\Jobs\Schedule\Setting;

use Exception;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;

class DeactiveExistingScheduleSetting
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
    public function handle(): void
    {
        DB::transaction(function () {
            $this->setting->forceDelete();
        });
    }
}
