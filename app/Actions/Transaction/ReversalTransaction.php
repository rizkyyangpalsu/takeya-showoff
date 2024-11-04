<?php

namespace App\Actions\Transaction;

use App\Concerns\BasicResponse;
use App\Concerns\Request\UserOfficeResolver;
use App\Events\Transaction\TransactionCanceled;
use App\Models\Customer\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Lorisleiva\Actions\Concerns\AsAction;

class ReversalTransaction
{
    use AsAction, UserOfficeResolver, BasicResponse;

    public function asController(Transaction $transaction): JsonResponse
    {
        $transaction->load(['items', 'passengers', 'reservation.route.tracks']);
        $transaction->reservation->makeHidden(['layout']);

        $this->handle($transaction);

        return $this->success($transaction->toArray());
    }

    private function handle(Transaction $transaction): void
    {
        abort_if($this->getUser()->user_type !== User::USER_TYPE_SUPER_ADMIN, Response::HTTP_FORBIDDEN);

        $transaction->canceled_at = now();
        $transaction->status = Transaction::STATUS_REVERSAL;
        $transaction->save();

        event(new TransactionCanceled($transaction, $this->getUser(), $this->getOffice()));
    }
}
