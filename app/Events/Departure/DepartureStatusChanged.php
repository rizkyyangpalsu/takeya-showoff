<?php

namespace App\Events\Departure;

use App\Models\User;
use App\Models\Departure;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class DepartureStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Departure model instance.
     *
     * @var Departure
     */
    public Departure $departure;

    /**
     * @var User
     */
    public User $user;

    /**
     * Create a new event instance.
     *
     * @param Departure $departure
     * @param User $user
     */
    public function __construct(Departure $departure, User $user)
    {
        $this->departure = $departure;
        $this->user = $user;
    }
}
