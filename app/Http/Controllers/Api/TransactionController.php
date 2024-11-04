<?php

namespace App\Http\Controllers\Api;

use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Customer\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Support\Print;

class TransactionController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $query = $user->transactions();

        if ($user->user_type === User::USER_TYPE_SUPER_ADMIN) {
            $query = Transaction::query();
        } elseif ($user->user_type === USER::USER_TYPE_ADMIN) {
            $query = Transaction::query()
                ->where(
                    fn ($query) => $query->where('payment_method', Transaction::PAYMENT_METHOD_AGENT)
                        ->whereHas('user', fn ($query) => $query->whereJsonContains('additional_data->online', true))
                        ->orWhere('user_id', $user->id)
                );
        }

        $params = $request->validate([
            'keyword' => 'nullable|string',
            'status' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (! is_array($value) && ! is_string($value)) {
                        $fail('The '.$attribute.' must be string or array.');
                        return;
                    }

                    if (is_string($value) && ! in_array($value, Transaction::getStatuses())) {
                        $fail(trans('validation.in', ['attribute' => $attribute]));
                    }
                },
            ],
            'status.*' => ['nullable', 'string', Rule::in(Transaction::getStatuses())],
            'is_active' => ['nullable', 'boolean'],
            'start_date' => 'nullable|string|date_format:Y-m-d',
            'end_date' => 'nullable|string|date_format:Y-m-d',
            'departure_start_date' => 'nullable|string|date_format:Y-m-d',
            'departure_end_date' => 'nullable|string|date_format:Y-m-d',
            'with' => [
                'nullable',
                fn ($attribute, $value, $fail) => (! is_array($value) && ! is_string($value)) ? $fail('The '.$attribute.' must be string or array.') : null,
            ],
            'with.*' => 'nullable|string',
        ]);

        $likeClause = $this->getMatchLikeClause($query);

        $query->when(
            $params['keyword'] ?? false,
            fn (Builder $builder, $keyword) => $builder->where(function (Builder $builder) use ($keyword, $likeClause) {
                $builder->where('code', $likeClause, '%'.$keyword.'%');
                $builder->orWhereHas('reservation', fn (Builder $builder) => $builder->where('code', $likeClause, '%'.$keyword.'%'));
                $builder->orWhereHas(
                    'trips',
                    fn (Builder $builder) => $builder->where(fn (Builder $builder) => $builder
                        ->where('origin', $likeClause, '%'.$keyword.'%')
                        ->orWhere('destination', $likeClause, '%'.$keyword.'%'))
                );
            })
        );

        $query->when($params['status'] ?? false, fn (Builder $builder, $status) => $builder->whereIn('status', Arr::wrap($status)));

        $query->when($params['start_date'] ?? false, fn (Builder $builder, $startDate) => $builder->whereDate('created_at', '>=', $startDate));

        $query->when($params['end_date'] ?? false, fn (Builder $builder, $endDate) => $builder->whereDate('created_at', '<=', $endDate));

        $query->when($params['departure_start_date'] ?? false, fn (Builder $builder, $startDate) => $builder->whereHas('trips', fn ($query) => $query->whereDate('departure', '>=', $startDate)));

        $query->when($params['departure_end_date'] ?? false, fn (Builder $builder, $startDate) => $builder->whereHas('trips', fn ($query) => $query->whereDate('arrival', '<=', $startDate)));

        $tripsQueryActive = fn (Builder $tripQuery) => $tripQuery->whereDate('arrival', '>=', now());

        $query->when($request->has('is_active'), fn (Builder $builder) => $request->boolean('is_active')
            ? $builder->whereHas('trips', $tripsQueryActive)
            : $builder->whereDoesntHave('trips', $tripsQueryActive));

        $query->with('attachments', 'trips');

        $query->when($params['with'] ?? false, fn (Builder $builder, array|string $with) => $builder->with(Arr::wrap($with)));

        return $query->orderByDesc('updated_at')->with(['reservation'])->paginate($request->input('per_page'));
    }

    public function summary(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $query = $user->transactions();

        if ($user->user_type === User::USER_TYPE_SUPER_ADMIN) {
            $query = Transaction::query();
        } elseif ($user->user_type === USER::USER_TYPE_ADMIN) {
            $query = Transaction::query()
                ->where(
                    fn ($query) => $query->where('payment_method', Transaction::PAYMENT_METHOD_AGENT)
                        ->whereHas('user', fn ($query) => $query->whereJsonContains('additional_data->online', true))
                        ->orWhere('user_id', $user->id)
                );
        }

        $params = $request->validate([
            'keyword' => 'nullable|string',
            'status' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (! is_array($value) && ! is_string($value)) {
                        $fail('The '.$attribute.' must be string or array.');
                        return;
                    }

                    if (is_string($value) && ! in_array($value, Transaction::getStatuses())) {
                        $fail(trans('validation.in', ['attribute' => $attribute]));
                    }
                },
            ],
            'status.*' => ['nullable', 'string', Rule::in(Transaction::getStatuses())],
            'is_active' => ['nullable', 'boolean'],
            'start_date' => 'nullable|string|date_format:Y-m-d',
            'end_date' => 'nullable|string|date_format:Y-m-d',
            'departure_start_date' => 'nullable|string|date_format:Y-m-d',
            'departure_end_date' => 'nullable|string|date_format:Y-m-d',
        ]);

        $likeClause = $this->getMatchLikeClause($query);

        $query->when(
            $params['keyword'] ?? false,
            fn (Builder $builder, $keyword) => $builder->where(function (Builder $builder) use ($keyword, $likeClause) {
                $builder->where('code', $likeClause, '%'.$keyword.'%');
                $builder->orWhereHas('reservation', fn (Builder $builder) => $builder->where('code', $likeClause, '%'.$keyword.'%'));
                $builder->orWhereHas(
                    'trips',
                    fn (Builder $builder) => $builder->where(fn (Builder $builder) => $builder
                        ->where('origin', $likeClause, '%'.$keyword.'%')
                        ->orWhere('destination', $likeClause, '%'.$keyword.'%'))
                );
            })
        );

        $query->when($params['status'] ?? false, fn (Builder $builder, $status) => $builder->whereIn('status', Arr::wrap($status)));

        $query->when($params['start_date'] ?? false, fn (Builder $builder, $startDate) => $builder->whereDate('created_at', '>=', $startDate));

        $query->when($params['end_date'] ?? false, fn (Builder $builder, $endDate) => $builder->whereDate('created_at', '<=', $endDate));

        $query->when($params['departure_start_date'] ?? false, fn (Builder $builder, $startDate) => $builder->whereHas('trips', fn ($query) => $query->whereDate('departure', '>=', $startDate)));

        $query->when($params['departure_end_date'] ?? false, fn (Builder $builder, $startDate) => $builder->whereHas('trips', fn ($query) => $query->whereDate('arrival', '<=', $startDate)));

        $tripsQueryActive = fn (Builder $tripQuery) => $tripQuery->whereDate('arrival', '>=', now());

        $query->when($request->has('is_active'), fn (Builder $builder) => $request->boolean('is_active')
            ? $builder->whereHas('trips', $tripsQueryActive)
            : $builder->whereDoesntHave('trips', $tripsQueryActive));

        $transactions = collect(
            [
                'total_ticket' => $query->sum('total_passenger'),
                'total_transaction' => $query->sum('total_price'),
                'total_paid' => $query->where('status', Transaction::STATUS_PAID)->sum('total_price'),
                'total_unpaid' => $query->where('status', Transaction::STATUS_PENDING)->sum('total_price'),
            ]
        );

        return $this->success($transactions);
    }

    public function overview(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = $user->transactions();

        if ($user->user_type === User::USER_TYPE_SUPER_ADMIN) {
            $query = Transaction::query();
        } elseif ($user->user_type === USER::USER_TYPE_ADMIN) {
            $query = Transaction::query()
                ->where(
                    fn ($query) => $query->where('payment_method', Transaction::PAYMENT_METHOD_AGENT)
                        ->whereHas('user', fn ($query) => $query->whereJsonContains('additional_data->online', true))
                        ->orWhere('user_id', $user->id)
                );
        }

        $params = $request->validate([
                'office_hash' => ['nullable']
            ]);

        $query->when($params['office_hash'] ?? false, function ($query, $val) {
            $query->where('office_id', Office::hashToId($val));
        });

        $overview = [
            'last_day' => $query->clone()->whereDate('created_at', Carbon::today()->subDay())->sum('total_price'),
            'today' => $query->clone()->whereDate('created_at', Carbon::today())->sum('total_price'),
            'month' => $query->clone()->whereDate('created_at', '>=', Carbon::today()->setDay(1))
                ->whereDate('created_at', '<=', Carbon::today()->endOfMonth())
                ->sum('total_price'),
        ];

        return $this->success($overview);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        return $this->success(
            array_merge(
                $transaction
                    ->load(['reservation.route', 'items', 'trips', 'passengers', 'user', 'attachments'])
                    ->append(['seats'])
                    ->toArray(),
                [
                    'trips' => $transaction->trips->map->only(['origin', 'destination', 'arrival', 'departure']),
                    'fleet_number_plate' => $transaction->reservation?->departure?->fleet?->license_plate
                ]
            )
        );
    }

    public function print(Transaction $transaction): JsonResponse
    {
        /** @var \App\Models\Schedule\Reservation\Trip $firstTrip */
        $firstTrip = $transaction->trips()->first();
        /** @var \App\Models\Schedule\Reservation\Trip $lastTrip */
        $lastTrip = $transaction->trips()->latest()->first();

        return $this->success($transaction->passengers->map(function (Transaction\Passenger $passenger) use ($transaction, $firstTrip, $lastTrip) {
            return collect([
                new Print\BlankLine(),
                new Print\PaperRow('PO. Tiara Mas', 3, 1),
                new Print\BlankLine(),
                new Print\PaperRow('Tanggal    : '.$firstTrip->departure->format('d/m/Y H:i')),
                new Print\PaperRow('Tiket No   : '.$passenger->ticket_code),
                new Print\DashLine(),
                new Print\PaperRow('Kelas      : '.$passenger->layout_name),
                new Print\PaperRow('No. Kursi  : '.$passenger->seat_code),
                new Print\DashLine(),
                new Print\PaperRow('Rute       : '.$transaction->reservation->route->name),
                new Print\BlankLine(),
                new Print\PaperRow('> '.$firstTrip->origin->terminal),
                new Print\BlankLine(),
                new Print\PaperRow('> '.$lastTrip->destination->terminal),
                new Print\PaperRow('*LUNAS*', 3, 1),
                new Print\BlankLine(),
            ]);
        }));
    }
}
