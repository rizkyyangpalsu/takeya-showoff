<?php

namespace App\Jobs\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappSendMessage
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $parameters = [];

    public string $phone_id;

    public string $template_name;

    public array $otherComponent;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($phone_id, $parameters, $template_name, $otherComponent=[])
    {
        $this->phone_id = $phone_id;
        $this->parameters = $parameters;
        $this->template_name = $template_name;
        $this->otherComponent = $otherComponent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->phone_id) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.env('FB_DEV_ACCESS_TOKEN', 'EAAPdpVrApcABAD8WCZAH9oE5mCPOOvLp7GQLGpqENvPxHGYZAGOlpXftFxyeqWFIoPQ8aKkaN1JkGFszt073ZABWZAGpZCPJikuBmUDjpmbB5q0GusPy2B8XuVHscnBEOLWAwOGZCwB4R87DphDe1dQiqys7k01shiAZBUGdarK1zclPZCB9rlFZBjJ0nyeNW2Ky6mLxv3lQv4wZDZD'),
                'Content-Type' => 'application/json'
            ])->post('https://graph.facebook.com/v13.0/'.env('WA_ACCOUNT_ID', '114441364610128').'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $this->phone_id,
                'type' => 'template',
                'template' => [
                    'name' => $this->template_name,
                    'language' => [
                        'code' => 'id'
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => $this->parameters
                        ],
                        $this->otherComponent
                    ]
                ],
            ]);

            if ($response->failed()) {
                Log::error($response);
            }
        }
    }
}
