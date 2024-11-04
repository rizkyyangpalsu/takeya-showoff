<?php

namespace App\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;

trait BasicResponse
{
    /**
     * Success REST response.
     *
     * @param array|\Illuminate\Contracts\Support\Arrayable|null $data
     * @param string $message
     *
     * @return JsonResponse
     */
    public function success(null|array|Arrayable $data = [], string $message = 'SUCCESS'): JsonResponse
    {
        return response()->json([
            'code' => 0,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Success NO CONTENT response.
     *
     * @return JsonResponse
     */
    public function noContent(): JsonResponse
    {
        return response()->json([
            'code' => 0,
            'message' => 'NO_CONTENT',
            'data' => null,
        ], 204);
    }
}
