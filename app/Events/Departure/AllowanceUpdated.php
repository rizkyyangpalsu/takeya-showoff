<?php

namespace App\Events\Departure;

use App\Models\Departure;
use App\Models\Office\Staff;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class AllowanceUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Departure model instance.
     *
     * @var Departure
     */
    public Departure $departure;

    /**
     * @var Staff
     */
    public Staff $staff;

    /**
     * @var Departure\Allowance
     */
    public Departure\Allowance $allowance;

    /**
     * Create a new event instance.
     *
     * @param Departure $departure
     * @param Departure\Allowance $allowance
     * @param Staff $staff
     */
    public function __construct(Departure $departure, Departure\Allowance $allowance, Staff $staff)
    {
        $this->departure = $departure;
        $this->staff = $staff;
        $this->allowance = $allowance;
    }
}
