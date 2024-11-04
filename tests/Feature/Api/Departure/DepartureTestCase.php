<?php

namespace Tests\Feature\Api\Departure;

use Tests\TestCase;
use App\Models\User;
use App\Models\Office;
use App\Models\Departure;
use Database\Seeders\DeparturesTableSeeder;
use Database\Seeders\DepartureExpenseSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DepartureTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    public bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DeparturesTableSeeder::class);
        $this->seed(DepartureExpenseSeeder::class);
        $this->actingAs($this->getStaff());
    }

    protected function getDeparture(): Departure
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Departure::query()->first();
    }

    protected function getOffice(): Office
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Office::query()->inRandomOrder()->first();
    }

    protected function getStaff($excepts = []): User
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return User::query()->inRandomOrder()->whereNotIn('id', $excepts)
            ->whereIn('user_type', Office\Staff::getStaffTypes())
            ->first();
    }

    protected function getStaffAdmin($excepts = []): User
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return User::query()->inRandomOrder()->whereNotIn('id', $excepts)
            ->where('user_type', User::USER_TYPE_SUPER_ADMIN)
            ->first();
    }
}
