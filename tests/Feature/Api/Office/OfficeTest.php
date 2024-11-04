<?php

namespace Tests\Feature\Api\Office;

use Tests\TestCase;
use App\Models\Office;
use App\Models\Geo\Regency;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OfficeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public const MODEL_STRUCTURE = [
        'hash',
        'slug',
        'name',
        'email',
        'phone',
        'address',
        'has_workshop',
        'has_warehouse',
        'parent',
        'regency',
        'province',
    ];

    public bool $seed = true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCanGetOffice()
    {
        $this->actingAs($this->getUser());

        $response = $this->get(route('api.office'));

        $response->assertJsonStructureIsFullPaginate();
    }

    public function testCanCreateNewOffice()
    {
        $this->actingAs($this->getUser());

        Office::query()->delete();

        $parentOffice = Office::factory()->create();

        /** @var Regency $regency */
        $regency = Regency::query()->inRandomOrder()->first();

        $payload = [
            'name' => $this->faker->company,
            'email' => $this->faker->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'office_slug' => $parentOffice->slug,
            'has_workshop' => false,
            'has_warehouse' => false,
            'parent_hash' => $parentOffice->getAttribute('hash'),
            'create_accounting_accounts' => true,
            'regency_hash' => $regency->hash,
        ];

        $this->actingAs($this->getUser());

        $response = $this->postJson(route('api.office.store'), $payload, [
            'Accept' => 'application/json',
        ]);

        $response->assertActionSuccess();

        $response->assertJsonStructure([
            'data' => self::MODEL_STRUCTURE,
        ]);
    }

    public function testCanUpdateOffice()
    {
        $this->actingAs($this->getUser());

        $parentOffice = Office::factory()->create();

        /** @var Office $target */
        $target = Office::factory()->create([
            'office_id' => $parentOffice->id,
        ]);

        // changing name to be always unique from database
        $target->name = 'khayangan';

        /** @var Regency $regency */
        $regency = Regency::query()->inRandomOrder()->first();

        // Changing
        $payload = [
            'name' => $target->name,
            'email' => $target->email,
            'phone' => $target->phone,
            'address' => $this->faker->address,
            'office_slug' => Str::slug($target->name),
            'has_workshop' => true,
            'has_warehouse' => true,
            'regency_hash' => $regency->hash,
        ];

        $response = $this->putJson(route('api.office.update', ['office_slug' => $target->slug]), $payload, [
            'Accept' => 'application/json',
        ]);

        $response->assertJsonStructure([
            'data' => self::MODEL_STRUCTURE,
        ]);

        // Make sure the content is exactly the same with updated data
        self::assertJsonStringEqualsJsonString(
            json_encode($response->json('data'), JSON_THROW_ON_ERROR),
            json_encode(array_merge($target->load('parent')->toArray(), Arr::except($payload, ['office_slug', 'regency_hash']), [
                'regency' => $regency->toArray(),
                'province' => $regency->province->toArray(),
            ]), JSON_THROW_ON_ERROR)
        );
    }

    public function testCanDeleteOffice()
    {
        $this->actingAs($this->getUser());

        Office::query()->delete();

        /** @var Office $target */
        $parentOffice = Office::factory()->create();

        /** @var Office $target */
        $target = Office::factory()->create([
            'office_id' => $parentOffice->id,
        ]);

        $response = $this->deleteJson(route('api.office.destroy', ['office_slug' => $target->slug]), [], [
            'Accept' => 'application/json',
        ]);

        $response->assertJsonStructure([
            'data' => self::MODEL_STRUCTURE,
        ]);

        $this->assertSoftDeleted('offices', [
            'id' => $target->id,
            'slug' => $target->slug,
        ]);
    }
}
