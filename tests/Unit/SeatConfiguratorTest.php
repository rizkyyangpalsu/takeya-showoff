<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Support\SeatConfigurator;

class SeatConfiguratorTest extends TestCase
{
    protected array $existing = [];

    public function setUp(): void
    {
        parent::setUp();

        $seats = [];
        for ($i = 1;$i <= 20;$i++) {
            $seats['id-'.$i] = 'seat-'.$i;
        }

        $this->existing = [
            'seats' => $seats,
        ];
    }

    public function test_new_configurator()
    {
        $c = new SeatConfigurator(null, $this->existing);
        $this->assertIsArray($c->getSeats());
        $this->assertArrayHasKey('id-'.rand(1, 20), $c->getSeats());
    }

    public function test_book_seats()
    {
        $c = new SeatConfigurator(null, $this->existing);

        // single book
        $c->bookSeat('id-3');
        $this->assertContains('id-3', $c->getBooked());
        $this->assertContains('id-3', $c->getUnavailable());
        $this->assertNotContains('id-3', $c->getAvailable());

        // multi books.
        $c->bookSeat('id-3', 'id-4');
        $this->assertContains('id-3', $c->getBooked());
        $this->assertContains('id-3', $c->getUnavailable());
        $this->assertNotContains('id-3', $c->getAvailable());

        $this->assertContains('id-4', $c->getBooked());
        $this->assertContains('id-4', $c->getUnavailable());
        $this->assertNotContains('id-4', $c->getAvailable());

        // reset.
        $c->makeAvailable(['id-3', 'id-4']);

        $this->assertContains('id-3', $c->getAvailable());
        $this->assertNotContains('id-3', $c->getUnavailable());
        $this->assertNotContains('id-3', $c->getBooked());

        $this->assertContains('id-4', $c->getAvailable());
        $this->assertNotContains('id-4', $c->getUnavailable());
        $this->assertNotContains('id-4', $c->getBooked());
    }

    public function test_reserved_seats()
    {
        $c = new SeatConfigurator(null, $this->existing);

        // single reserved
        $c->reserveSeat('id-3');
        $this->assertContains('id-3', $c->getReserved());
        $this->assertContains('id-3', $c->getUnavailable());
        $this->assertNotContains('id-3', $c->getAvailable());

        // multi reserved.
        $c->reserveSeat('id-3', 'id-4');
        $this->assertContains('id-3', $c->getReserved());
        $this->assertContains('id-3', $c->getUnavailable());
        $this->assertNotContains('id-3', $c->getAvailable());

        $this->assertContains('id-4', $c->getReserved());
        $this->assertContains('id-4', $c->getUnavailable());
        $this->assertNotContains('id-4', $c->getAvailable());

        // reset.
        $c->makeAvailable(['id-3', 'id-4']);

        $this->assertContains('id-3', $c->getAvailable());
        $this->assertNotContains('id-3', $c->getUnavailable());
        $this->assertNotContains('id-3', $c->getReserved());

        $this->assertContains('id-4', $c->getAvailable());
        $this->assertNotContains('id-4', $c->getUnavailable());
        $this->assertNotContains('id-4', $c->getReserved());
    }

    public function test_occupied_seats()
    {
        $c = new SeatConfigurator(null, $this->existing);

        // single occupy
        $c->occupySeat('id-3');
        $this->assertContains('id-3', $c->getOccupied());
        $this->assertContains('id-3', $c->getUnavailable());
        $this->assertNotContains('id-3', $c->getAvailable());

        // multi occupy.
        $c->occupySeat('id-3', 'id-4');
        $this->assertContains('id-3', $c->getOccupied());
        $this->assertContains('id-3', $c->getUnavailable());
        $this->assertNotContains('id-3', $c->getAvailable());

        $this->assertContains('id-4', $c->getOccupied());
        $this->assertContains('id-4', $c->getUnavailable());
        $this->assertNotContains('id-4', $c->getAvailable());

        // reset.
        $c->makeAvailable(['id-3', 'id-4']);

        $this->assertContains('id-3', $c->getAvailable());
        $this->assertNotContains('id-3', $c->getUnavailable());
        $this->assertNotContains('id-3', $c->getOccupied());

        $this->assertContains('id-4', $c->getAvailable());
        $this->assertNotContains('id-4', $c->getUnavailable());
        $this->assertNotContains('id-4', $c->getOccupied());
    }

    public function test_override_data()
    {
        $existing = array_merge($this->existing, [
            'booked' => ['id-3', 'id-4'],
            'reserved' => ['id-5'],
            'occupied' => ['id-1', 'id-2'],
        ]);

        $c = new SeatConfigurator(null, $existing);

        $this->assertContains('id-3', $c->getBooked());
        $this->assertContains('id-5', $c->getReserved());
        $this->assertContains('id-1', $c->getOccupied());
        $this->assertContains('id-2', $c->getOccupied());
        // loop 1 to 5
        for ($i = 1;$i <= 5;$i++) {
            $this->assertContains('id-'.$i, $c->getUnavailable());
        }
        // loop 6 to 20;
        for ($i = 6;$i <= 20;$i++) {
            $this->assertContains('id-'.$i, $c->getAvailable());
        }

        $newSeats = [];
        for ($i = 1;$i <= 5;$i++) {
            $newSeats['new-id-'.$i] = 'New Seat: '.$i;
        }
        $c->setSeats($newSeats);

        // loop 6 to 20;
        for ($i = 1;$i <= 20;$i++) {
            $this->assertNotContains('id-'.$i, $c->getAvailable());
            $this->assertNotContains('id-'.$i, $c->getUnavailable());
            $this->assertNotContains('id-'.$i, $c->getOccupied());
            $this->assertNotContains('id-'.$i, $c->getReserved());
            $this->assertNotContains('id-'.$i, $c->getBooked());

            if ($i < 5) {
                $this->assertContains('new-id-'.$i, $c->getAvailable());
            }
        }
    }
}
