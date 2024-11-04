<?php

namespace App\Http\Response\Fortify;

use App\Concerns\BasicResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

class BasicOverrideResponse implements
    RegisterResponseContract,
    SuccessfulPasswordResetLinkRequestResponse
{
    use BasicResponse;

    public function __construct(
        public ?string $status = null,
    ) {
    }

    public function toResponse($request)
    {
        return $request->wantsJson()
            ? $this->success([], $this->status ? trans($this->status) : 'SUCCESS')
            : back()->with('status', $this->status ? trans($this->status) : 'SUCCESS');
    }
}
