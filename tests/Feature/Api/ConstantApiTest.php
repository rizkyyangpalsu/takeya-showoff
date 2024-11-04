<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConstantApiTest extends TestCase
{
    use RefreshDatabase;

    public bool $seed = true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCanGetConstant()
    {
        $this->actingAs($this->getUser());

        $response = $this->get(route('api.constant'), [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            [
                'name',
                'value',
                'created_at',
                'updated_at',
                'hash',
            ],
        ]);
    }
}
