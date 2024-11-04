<?php

namespace App\Actions\Transaction;

use App\Models\User;
use App\Models\Fleet\Layout;
use App\Support\Schedule\Item;
use App\Models\Fleet\Layout\Seat;
use Illuminate\Support\Collection;
use App\Models\Customer\Transaction;
use App\Models\Schedule\Reservation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Concerns\Request\UserOfficeResolver;
use App\Events\Transaction\TransactionOccurred;
use App\Models\Schedule\Setting\Detail\PriceModifier;

class CreateNewTransaction
{
    use AsAction, UserOfficeResolver;

    /**
     * Method to be parsed to handle method.
     *
     * @param array $data
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validated(array $data): array
    {
        return Validator::make($data, [
            'payment_method' => 'nullable|string',
            'payment_data' => 'nullable',
            'additional_data' => 'nullable|array',
            'passengers' => 'required|array',
            'passengers.*.seat_hash' => 'required',
            'passengers.*.title' => 'nullable',
            'passengers.*.name' => 'nullable',
            'passengers.*.nik' => 'nullable',
            'passengers.*.additional_data' => 'nullable|array',
            'passengers.*.custom_price' => 'nullable|integer',
        ])->after(static function (\Illuminate\Validation\Validator $validator) use ($data) {
            /** @var User $user */
            $user = auth()->user();

            collect($data['passengers'])->each(fn ($passenger, $index) => $validator->errors()->addIf(
                ($passenger['custom_price'] ?? false) && ! in_array($user->user_type, [User::USER_TYPE_ADMIN, User::USER_TYPE_SUPER_ADMIN], true),
                'passenger.'.$index.'.custom_price',
                __('only admin and super admin can fill custom_price'),
            ));
        })->validated();
    }

    /**
     * @throws ValidationException
     */
    public function handle(Reservation $reservation, ?User $actor, Item $item, array $requestData): Transaction
    {
        $passengers = $this->processPassengers($requestData['passengers'], $reservation->layout);
        $items = $this->processPrices($item, count($passengers), $passengers);

        if (! empty($requestData['payment_method']) &&
            ! in_array($requestData['payment_method'], Transaction::getEligiblePaymentMethod($actor ?? $this->getUser()), true)) {
            throw ValidationException::withMessages([
                'payment_method' => [
                    __('not eligible.'),
                ],
            ]);
        }

        $requestData['payment_method'] ??= $this->getEligiblePrimaryPaymentMethod($actor);

        $transaction = new Transaction();
        $transaction->reservation()->associate($reservation);
        $transaction->user()->associate($actor);
        $transaction->code = $this->generateTransactionCode();
        $transaction->total_passenger = count($passengers);
        $transaction->total_price = $items->sum('total_amount');
        $transaction->payment_method = $requestData['payment_method'];
        $transaction->payment_data = $requestData['payment_data'] ?? null;
        $transaction->expired_at = now()->addMinutes(10);
        $transaction->status = Transaction::STATUS_PENDING;
        $transaction->additional_data = $requestData['additional_data'] ?? null;

        $transaction->office()->associate($this->getOffice($actor) ?? $this->getPrimaryOffice());

        $transaction->save();

        $transaction->items()->createMany($items);
        $transaction->passengers()->createMany($passengers);

        event(new TransactionOccurred($transaction, $item, $actor));

        return $transaction;
    }

    /**
     *
     * @param \App\Support\Schedule\Item $item
     * @param int $quantity
     * @param array|null $passengers
     *
     * @return \Illuminate\Support\Collection
     */
    public function processPrices(Item $item, int $quantity, ?array $passengers = null): Collection
    {
        $price = $item->getPrice();

        $items = collect();

        if (! $passengers) {
            $items->add([
                'name' => __('ticket'),
                'quantity' => $quantity,
                'amount' => $price->getNominal(),
                'total_amount' => $quantity * $price->getNominal(),
            ]);
        } else {
            // add separate items for each passenger
            collect($passengers)->each(fn ($passenger) => $items->add([
                'name' => 'Tiket kursi '.($passenger['seat_code'] ?? ''),
                'quantity' => 1,
                'amount' => $passenger['custom_price'] ?? $price->getNominal(),
                'total_amount' => $passenger['custom_price'] ?? $price->getNominal(),
            ]));
        }

        $this->processOtherPriceModifier($item, $quantity)->each(static function (PriceModifier $modifier) {
            // todo to something with modifier
        });

        return $items;
    }

    private function generateTransactionCode(): string
    {
        $now = now();

        return 'TRX/'.$now->format('ymd/his/u');
    }

    private function processPassengers(array $passengers, Layout $layout): array
    {
        return collect($passengers)->map(function (array $passenger) use ($layout) {
            if (array_key_exists('seat_hash', $passenger)) {
                /** @var Seat $seat */
                $seat = Seat::byHash($passenger['seat_hash']);
                $passenger['seat_id'] = $seat->id;
                $passenger['seat_code'] = $seat->name;
                unset($passenger['seat_hash']);
            }

            $passenger['layout_name'] = $layout->name;

            return $passenger;
        })->toArray();
    }

    /**
     * This is where discount group with passengers fetched.
     *
     * @param \App\Support\Schedule\Item $item
     * @param int $quantity
     * @return \Illuminate\Support\Collection
     */
    private function processOtherPriceModifier(Item $item, int $quantity): Collection
    {
        // todo implement this method
        return new Collection();
    }
}
