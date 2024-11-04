<?php

namespace App\Actions\Transaction;

use Illuminate\Http\Request;
use App\Concerns\BasicResponse;
use Illuminate\Http\JsonResponse;
use App\Models\Customer\Transaction;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Events\Transaction\TransactionPaid;
use App\Concerns\Request\UserOfficeResolver;
use Illuminate\Validation\ValidationException;

class PurchaseReservation
{
    use AsAction, UserOfficeResolver, BasicResponse;

    /**
     * @param \App\Models\Customer\Transaction $transaction
     * @param Request $request
     * @return \App\Models\Customer\Transaction
     * @throws \Throwable
     */
    public function handle(Transaction $transaction, Request $request): Transaction
    {
        throw_if($transaction->status !== Transaction::STATUS_PENDING, ValidationException::withMessages([
            'transaction' => __('transaction has been processed in the past!'),
        ]));

        throw_if(now()->isAfter($transaction->expired_at) && $transaction->attachments()->count() === 0, ValidationException::withMessages([
            'transaction' => __('transaction expired!'),
        ]));

        $transaction->paid_at = now();
        $transaction->status = Transaction::STATUS_PAID;
        $transaction->save();

        event(new TransactionPaid($transaction, $transaction?->user ?? $this->getUser(), $transaction?->user?->office ?? $this->getOffice()));

        return $transaction;
    }

    /**
     * @param \App\Models\Customer\Transaction $transaction
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function asController(Transaction $transaction, Request $request): JsonResponse
    {
        $transaction->load(['items', 'passengers', 'reservation.route.tracks']);
        $transaction->reservation->makeHidden(['layout']);

        $this->handle($transaction, $request);

        return $this->success($transaction->toArray());
    }
}
