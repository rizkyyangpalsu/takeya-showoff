<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Support\Schedule\Collector;
use App\Support\Schedule\Item;
use Database\Seeders\ScheduleSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestCollectorItemSerializeTest extends TestCase
{
    use RefreshDatabase;

    public bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ScheduleSettingSeeder::class);
        $this->actingAs($this->getUser());
    }

    /**
     * @throws \Exception
     */
    public function test_can_serialize_and_unserialize_items(): void
    {
        $date = now()->addDays(2);

        /** @var Route $route */
        $route = Route::query()->first();

        $departurePoint = $route->tracks->first()->origin;
        $destinationPoint = $route->tracks->get(random_int(1, $route->tracks->count() - 1))->destination;

        $collector = new Collector($date, $departurePoint->id, $destinationPoint->id);

        $item = $collector->first();

        $serializeData = serialize($item);

        self::assertNotNull($serializeData);

        $itemUnserialize = unserialize($serializeData);

        self::assertInstanceOf(Item::class, $itemUnserialize);
        self::assertEquals($item, $itemUnserialize);

        $responseData = $item->toArray();

        $itemFromHash = Item::fromHash($responseData['hash']);
        // run toArray to make sure condition is the same as $item
        $itemFromHash->toArray();

        self::assertEquals($item, $itemFromHash);
    }
}
