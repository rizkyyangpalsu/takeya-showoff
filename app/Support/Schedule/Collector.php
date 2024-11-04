<?php

namespace App\Support\Schedule;

use Illuminate\Support\Carbon;
use App\Models\Schedule\Setting;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Support\Arrayable;

class Collector implements Arrayable
{
    use Collector\SettingResolver;
    use Collector\PriceModifierResolver;
    use SerializesModels;

    /** @var \Illuminate\Support\Collection<Setting> $settings */
    private Collection $settings;

    private Carbon $date;
    private int $departureId;
    private int $destinationId;

    private Collection $collection;

    public function __construct(Carbon $date, int $departureId, int $destinationId)
    {
        $this->settings = $this->resolveSettings($date, $departureId, $destinationId);
        $this->date = $date;
        $this->departureId = $departureId;
        $this->destinationId = $destinationId;

        $this->collection = new Collection();

        $this->loadSettingDetails();
        $this->validateDateOfItem();
    }

    public function loadSettingDetails(): void
    {
        $this->settings->each(function (Setting $setting) {
            // make sure priceModifiers and rules has been created
            // is because we make sure that we have shown to user is all asserted to rule
            // and rules is queued in process of creation priceModifier
            $setting->details()->whereHas('priceModifiers.rules')->cursor()->filter(function (Setting\Detail $settingDetail) {
                $this->loadRelationPriceModifierFor($settingDetail, $this->departureId, $this->destinationId);

                return $settingDetail->priceModifiers->count() > 0;
            })->each(function (Setting\Detail $settingDetail) use ($setting) {
                $settingDetail->setRelation('setting', $setting);
                // get real nominal
                // from Collection.php line 42 we know that items only contains one value
                // that match with departureId and destinationId
                // nominal is match result from select join from Collector@loadSettingDetails above
                $nominal = (float) $settingDetail->priceModifiers->first()->items->first()->nominal;
                $priceModifiers = $settingDetail->priceModifiers;
                $this->collection->push(new Item(
                    $settingDetail,
                    clone $this->date,
                    $this->departureId,
                    $this->destinationId,
                    $nominal,
                    $priceModifiers,
                ));
            });
        });
    }

    public function exists(string $firstDepart, int $layoutId): bool
    {
        return $this->collection->some(fn (Item $item) => $item->settingDetail->departure === $firstDepart && (int) $item->settingDetail->layout_id === $layoutId);
    }

    public function filter(string $firstDepart, int $layoutId): self
    {
        $this->collection = $this->collection->filter(fn (Item $item) => $item->settingDetail->departure === $firstDepart && (int) $item->settingDetail->layout_id === $layoutId);

        return $this;
    }

    public function first(): ?Item
    {
        return $this->collection->first();
    }

    public function visible(...$attributes): self
    {
        $this->collection->each(fn (Item $item) => $item->makeVisible(...$attributes));

        return $this;
    }

    public function toArray(): array
    {
        return $this->collection->values()->toArray();
    }

    /**
     * @param DateInterval|DateTimeInterface|int|null $ttl
     * @throws \Exception
     */
    public function cache(DateInterval|DateTimeInterface|int $ttl = null): void
    {
        Cache::tags(['schedule'])->put(
            'schedule:'.$this->date->format('Y-m-d').':'.$this->departureId.':'.$this->destinationId,
            $this,
            $ttl
        );
    }

    /**
     * get result from cache or it solve new instance.
     *
     * @param Carbon $date
     * @param $departureId
     * @param $destinationId
     *
     * @return static
     * @throws \Exception
     */
    public static function fromCache(Carbon $date, $departureId, $destinationId): self
    {
        if (config('app.debug')) {
            $collector = new static($date, $departureId, $destinationId);
            // $collector->cache();

            return $collector;
        }

        return Cache::tags(['schedule'])->get(
            'schedule:'.$date->format('Y-m-d').':'.$departureId.':'.$destinationId,
            function () use ($date, $departureId, $destinationId) {
                $collector = new static($date, $departureId, $destinationId);
                // $collector->cache();

                return $collector;
            }
        );
    }

    public static function forgetCache(): bool
    {
        return Cache::tags(['schedule'])->flush();
    }

    private function validateDateOfItem(): void
    {
        $this->collection = $this->collection->filter(function (Item $item) {
            $etd = $item->getDeparturePoint()->etd;

            $diffInDays = $this->date->diffInDays($etd);
            if ($diffInDays === 0) {
                return true;
            }

            // check if this item eligible with the original input date
            $setting = $item->settingDetail->setting;
            $expectedFirstDepartureDate = (clone $this->date)->subDays($diffInDays);
            $isItemValid = in_array($expectedFirstDepartureDate->dayOfWeekIso, $setting->options->getDays(), true);
            // todo filter by date, reposition can causing ambiguity with the query match day on SettingResolver@filterActive

            if ($isItemValid) {
                $item->setDepartureSchedule($expectedFirstDepartureDate);
            }

            return $isItemValid;
        });
    }
}
