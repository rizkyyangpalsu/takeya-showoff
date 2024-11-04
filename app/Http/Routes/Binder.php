<?php

namespace App\Http\Routes;

use Dentro\Yalr\BaseRoute;
use App\Models\Template;
use App\Models\BankAccount;
use App\Models\Attachment;
use App\Models\Customer\Transaction;
use App\Models\Departure;
use App\Models\Fleet\Layout;
use App\Models\Schedule\Reservation;
use App\Models\Schedule\Reservation\Trip;
use App\Models\Schedule\Setting\Detail;
use App\Models\Schedule\Setting\Detail\PriceModifier;
use App\Models\User;
use App\Models\Fleet;
use App\Models\Route;
use App\Models\Office;
use App\Models\Office\Staff;
use App\Models\Departure\Crew;
use App\Models\Schedule\Setting;
use App\Models\Route\Track\Point;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Departure\Allowance;
use App\Models\Logistic\Delivery;
use App\Models\Logistic\Manifest;
use App\Models\Logistic\Price;
use App\Models\Logistic\Service;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class Binder extends BaseRoute
{
    public function register(): void
    {
        $this->registerRateLimiter();
        $this->registerBindings();
    }
    private function registerRateLimiter(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(360)->by($request->user()?->email.$request->ip());
        });
    }

    /**
     * Register binding.
     *
     * @return void
     */
    private function registerBindings(): void
    {
        $this->router->bind('fleet_hash', fn ($value) => Fleet::byHashOrFail($value));
        $this->router->bind('office_slug', fn ($value) => Office::query()->where('slug', $value)->firstOrFail());
        $this->router->bind('user_hash', fn ($value) => User::byHashOrFail($value));
        $this->router->bind('account_hash', fn ($value) => Account::byHashOrFail($value));
        $this->router->bind('journal_hash', fn ($value) => Journal::byHashOrFail($value));
        $this->router->bind('setting_hash', fn ($value) => Setting::withTrashed()->where('id', Setting::hashToId($value))->firstOrFail());
        $this->router->bind('route_hash', fn ($value) => Route::byHashOrFail($value));
        $this->router->bind('point_hash', fn ($value) => Point::byHashOrFail($value));
        $this->router->bind('crew_hash', fn ($value) => Crew::byHashOrFail($value));
        $this->router->bind('staff_hash', fn ($value) => Staff::byHashOrFail($value));
        $this->router->bind('allowance_hash', fn ($hash) => Allowance::byHashOrFail($hash));
        $this->router->bind('trip_hash', fn ($hash) => Trip::byHashOrFail($hash));
        $this->router->bind('bank_account_hash', fn ($hash) => BankAccount::byHashOrFail($hash));
        $this->router->bind('attachment_hash', fn ($hash) => Attachment::byHashOrFail($hash));
        $this->router->bind('transaction_hash', fn ($value) => Transaction::byHashOrFail($value));
        $this->router->bind('layout_hash', fn ($value) => Layout::byHashOrFail($value));
        $this->router->bind('setting_detail_hash', fn ($value) => Detail::byHashOrFail($value));
        $this->router->bind('departure_hash', fn ($value) => Departure::byHashOrFail($value));
        $this->router->bind('reservation_hash', fn (string $hash) => Reservation::byHashOrFail($hash));
        $this->router->bind('price_modifier_hash', fn ($hash) => PriceModifier::byHashOrFail($hash));
        $this->router->bind('logistic_service_hash', fn ($hash) => Service::byHashOrFail($hash));
        $this->router->bind('logistic_price_hash', fn ($hash) => Price::byHashOrFail($hash));
        $this->router->bind('logistic_delivery_hash', fn ($hash) => Delivery::byHashOrFail($hash));
        $this->router->bind('logistic_manifest_hash', fn ($hash) => Manifest::byHashOrFail($hash));
        $this->router->bind('template_hash', fn ($hash) => Template::byHashOrFail($hash));
        $this->router->bind('combined_hash', fn ($hash) => Departure\Combined::byHashOrFail($hash));
    }
}
