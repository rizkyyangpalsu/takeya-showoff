<?php

namespace App\Actions\Transaction;

use App\Concerns\BasicResponse;
use App\Concerns\Request\UserOfficeResolver;
use App\Events\Transaction\TransactionPendingConfirmation;
use App\Models\Attachment;
use App\Models\Customer\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class PurchaseAttachment
{
    use AsAction, UserOfficeResolver, BasicResponse;

    public function rules(): array
    {
        return [
            'attachment' => ['required', 'file'],
        ];
    }

    /**
     * @param \App\Models\Customer\Transaction $transaction
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function handle(Transaction $transaction, Request $request)
    {
        throw_if($transaction->status !== Transaction::STATUS_PENDING, ValidationException::withMessages([
            'transaction' => __('transaction has been processed in the past!'),
        ]));

        throw_if(now()->isAfter($transaction->expired_at), ValidationException::withMessages([
            'transaction' => __('transaction expired!'),
        ]));

        $name = Str::orderedUuid();

        $path = $request->file('attachment')->storeAs(
            'attachments',
            substr($name, 0, 2).
            '/'.substr($name, 6, 2).
            '/'.$name
        );

        Attachment::query()->create([
            'transaction_id' => $transaction->id,
            'title' => $name,
            'size' => $request->file('attachment')->getSize(),
            'path' => $path,
            'mime' => $request->file('attachment')->getMimeType()
        ]);

        event(new TransactionPendingConfirmation($transaction, $transaction?->user ?? $this->getUser(), $transaction?->user?->office ?? $this->getOffice()));

        return $this->success($transaction);
    }
}
