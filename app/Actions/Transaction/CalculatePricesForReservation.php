<?php

namespace App\Actions\Transaction;

use App\Concerns\BasicResponse;
use App\Concerns\Request\UserOfficeResolver;
use App\Models\Customer\Transaction;
use App\Models\User;
use App\Support\Schedule\Item;
use App\Support\SeatConfigurator;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;
use JetBrains\PhpStorm\Pure;
use App\Models\Fleet\Layout;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class CalculatePricesForReservation
{
    use AsAction, BasicResponse, UserOfficeResolver;

    protected ?Carbon $date = null;
    protected ?Item $item = null;
    protected ?User $customActor = null;

    #[Pure]
    public function rules(): array
    {
        return [
            'hash' => ['required'],
            'actor_hash' => ['nullable', new ExistsByHash(User::class)],
            // this is index of group of seats from inquiry seats,
            // group of seats will be more than 1 if $scheduleSetting->fleet > 1
            // null will be interpreted as first fleet
            'reservation_index' => ['nullable', 'integer'],
            'passengers' => ['required', 'array', 'min:1'],
            'passengers.*.seat_hash' => ['required', 'distinct', new ExistsByHash(Layout\Seat::class)],
        ];
    }

    /**
     * @param Validator $validator
     * @param ActionRequest $request
     * @throws Exception
     */
    public function afterValidator(Validator $validator, ActionRequest $request): void
    {
        if ($validator->errors()->hasAny([
            'hash',
            'actor_hash',
            'reservation_index',
            'passengers',
            'passengers.*.seat_hash'
        ])) {
            return;
        }

        if ($request->has('actor_hash') && $request->input('actor_hash')) {
            abort_if(! in_array($request->user()->user_type, [User::USER_TYPE_SUPER_ADMIN, User::USER_TYPE_ADMIN]), 403);

            $this->customActor = User::byHash($request->input('actor_hash'));
        }

        try {
            $this->item = Item::fromHash($request->input('hash'));
            $this->date = Carbon::createFromFormat('Y-m-d', $this->item->getDate()->format('Y-m-d'));
        } catch (DecryptException) {
            $validator->errors()->add('hash', __('cannot decrypt hash.'));
            return;
        }

        if (! $this->item) {
            $validator->errors()->add('schedule', __('layout and departure not found'));

            return;
        }

        $reservationIndex = Arr::get($validator->getData(), 'reservation_index', 0);
        $seatConfiguratorGroup = $this->item->getSeatConfigurations();

        if ($reservationIndex > $seatConfiguratorGroup->count() - 1) {
            $validator->errors()->add('reservation_index', __('reservation index not found'));

            return;
        }

        /** @var SeatConfigurator $seatConfigurator */
        $seatConfigurator = $seatConfiguratorGroup[$reservationIndex];
        $unavailableSeats = $seatConfigurator->getUnavailable();

        collect(Arr::get($validator->getData(), 'passengers', []))
            ->each(function (array $passenger, $index) use ($validator, $unavailableSeats) {
                if (in_array(Layout\Seat::hashToId($passenger['seat_hash']), $unavailableSeats, true)) {
                    $validator
                        ->errors()
                        ->add(
                            'seats.'.$index,
                            __('seat :name unavailable', ['name' => Layout\Seat::byHash($passenger['seat_hash'])->name])
                        );
                }
            });
    }

    public function handle(ActionRequest $request): JsonResponse
    {
        $createNewTransaction = new CreateNewTransaction();

        return $this->success([
            'available_payment_methods' => Transaction::getEligiblePaymentMethod($this->getUser()),
            'items' => $createNewTransaction->processPrices($this->item, count($request->input('passengers'))),
        ]);
    }
}
