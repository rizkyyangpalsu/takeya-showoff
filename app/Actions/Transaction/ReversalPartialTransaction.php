<?php

namespace App\Actions\Transaction;

use App\Events\Transaction\TransactionReversalCreated;
use App\Models\Customer\Transaction;
use App\Models\Customer\Transaction\Passenger;
use App\Models\Office;
use App\Models\Schedule\Reservation\Trip;
use App\Models\User;
use App\Support\Transaction\Reversal\PassengerFee;
use App\Support\Transaction\Reversal\TransactionPassengersRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Concerns\Request\UserOfficeResolver;
use App\Concerns\BasicResponse;
use Lorisleiva\Actions\ActionRequest;

class ReversalPartialTransaction
{
    use AsAction, UserOfficeResolver, BasicResponse;

    /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
    public function rules(): array
    {
        return [
            'ticket_codes' => ['required', 'array', 'min:1'],
            'ticket_codes.*' => ['required', 'exists:transaction_passengers,ticket_code'],
        ];
    }

    public function handle(Collection $passengers)
    {
        $transactionPassengersRepo = $passengers
            ->groupBy(fn (Passenger $passenger) => $passenger->transaction_id)
            ->map(function (Collection $passengers, int $transactionId) {
                /** @var Transaction $transaction */
                $transaction = Transaction::query()->where('type', Transaction::TYPE_PAYMENT)->where('id', $transactionId)->firstOr(fn () => throw ValidationException::withMessages(['seat_codes' => ['Transaction not found.']]));
                return new TransactionPassengersRepository(
                    $transaction,
                    $passengers,
                );
            })
            ->values();

        $transactionPassengersRepo->each(function (TransactionPassengersRepository $repo) {
            $oldOfficeId = $repo->bookingTransaction->office()->id;
            $oldUserId = $repo->bookingTransaction->user()->id;
            $passengerFees = $repo->getPassengerFees();

            $newTransaction = new Transaction();

            $newTransaction->type = Transaction::TYPE_REFUND;
            $newTransaction->status = Transaction::STATUS_COMPLETE;

            $newTransaction->reference_id = $repo->bookingTransaction->id;
            $newTransaction->reservation()->associate($repo->bookingTransaction->reservation);
            $newTransaction->office()->associate($this->getOffice() ?? $oldOfficeId);
            $newTransaction->user()->associate($this->getUser() ?? $oldUserId);
            $newTransaction->total_passenger = count($repo->deletingPassengers);
            $newTransaction->code = $this->generateTransactionCode();
            $newTransaction->total_price = -$passengerFees->sum(fn (PassengerFee $passengerFee) => $passengerFee->seatFee);
            $newTransaction->expired_at = null;
            $newTransaction->save();

            // create transaction items and passengers
            $passengerFees->each(static function (PassengerFee $passengerFee) use ($newTransaction) {
                $newPassenger = $passengerFee->passenger->replicate(['transaction_id']);
                $newPassenger->transaction()->associate($newTransaction);
                $newPassenger->save();

                $newTransaction->items()->create([
                    'name' => 'Reversal kursi '.($newPassenger->seat_code ?? ''),
                    'quantity' => 1,
                    'amount' => -$passengerFee->seatFee,
                    'total_amount' => -$passengerFee->seatFee,
                ]);
            });

            // clone trip for transaction
            $repo->bookingTransaction->trips()->cursor()->each(fn (Trip $trip) => $newTransaction->trips()->attach($trip->id));
            $office = $this->getOffice();
            if (! $office) {
                $office = Office::query()->where('id', $oldOfficeId)->first();
            }
            $user = $this->getUser();
            if (! $user) {
                $user = User::query()->where('id', $oldUserId)->first();
            }

            event(new TransactionReversalCreated($newTransaction, $this->getUser() ?? $user, $this->getOffice() ?? $office));
        });
    }

    /**
     * @param ActionRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function asController(ActionRequest $request): JsonResponse
    {
        $request->validate();

        $passengers = Passenger::query()->whereIn('ticket_code', $request->input('ticket_codes'))->get();
        $this->handle($passengers);

        return $this->success();
    }

    private function generateTransactionCode(): string
    {
        $now = now();

        return 'RVL/'.$now->format('ymd/his/u');
    }
}
