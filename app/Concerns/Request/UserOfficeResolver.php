<?php

namespace App\Concerns\Request;

use App\Models\Customer\Transaction;
use App\Models\User;
use App\Models\Office;
use Illuminate\Http\Request;

trait UserOfficeResolver
{
    protected function getRequest(): Request
    {
        return app(Request::class);
    }

    protected function getUser(?string $guard = null): User
    {
        return $this->getRequest()->user($guard);
    }

    protected function getOffice(?User $scopedUser = null): ?Office
    {
        /** @var Request $request */
        $request = app(Request::class);

        /** @var User $user */
        $user = $scopedUser ?? $request->user();

        $officeHash = $request->header('OfficeHash');

        if (! $officeHash ||
            ($user->user_type === User::USER_TYPE_SUPER_ADMIN && $user->original_offices()->exists()) ||
            ($user->user_type === User::USER_TYPE_CUSTOMER && $user->original_offices()->doesntExist())) {
            return null;
        }

        return Office::byHash($officeHash);
    }

    protected function getPrimaryOffice(): Office
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Office::query()->whereNull('office_id')->firstOrFail();
    }

    protected function getEligiblePrimaryPaymentMethod(?User $scopedUser = null): string
    {
        return match ($scopedUser?->user_type ?? $this->getUser()->user_type) {
            User::USER_TYPE_CUSTOMER => Transaction::PAYMENT_METHOD_TRANSFER,
            default => Transaction::PAYMENT_METHOD_AGENT,
        };
    }
}
