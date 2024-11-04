<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\QRCodeController;
use Illuminate\Support\Str;
use Tests\TestCase;

class QrCodeGeneratorTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_can_generate_qr_code()
    {
        $response = $this->get(action([QRCodeController::class, 'index'], [
            'code' => 'code',
        ]));

        $response->assertSuccessful();
        self::assertTrue(Str::startsWith($response->content(), '<svg'));
    }
}
