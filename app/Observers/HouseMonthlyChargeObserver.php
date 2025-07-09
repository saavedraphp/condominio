<?php

namespace App\Observers;
use App\Models\House;
use App\Models\HouseMonthlyCharge;
use Illuminate\Support\Facades\Cache;

class HouseMonthlyChargeObserver
{
    /**
     * Handle the HouseMonthlyCharge "created" event.
     */
    public function created(HouseMonthlyCharge $houseMonthlyCharge): void
    {
        $this->invalidateOwnerDebtCache($houseMonthlyCharge->house);
    }

    /**
     * Handle the HouseMonthlyCharge "updated" event.
     */
    public function updated(HouseMonthlyCharge $houseMonthlyCharge): void
    {
        $this->invalidateOwnerDebtCache($houseMonthlyCharge->house);
    }

    /**
     * Handle the HouseMonthlyCharge "deleted" event.
     */
    public function deleted(HouseMonthlyCharge $houseMonthlyCharge): void
    {
        $this->invalidateOwnerDebtCache($houseMonthlyCharge->house);
    }

    /**
     * Handle the HouseMonthlyCharge "restored" event.
     */
    public function restored(HouseMonthlyCharge $houseMonthlyCharge): void
    {
        $this->invalidateOwnerDebtCache($houseMonthlyCharge->house);
    }

    /**
     * Handle the HouseMonthlyCharge "force deleted" event.
     */
    public function forceDeleted(HouseMonthlyCharge $houseMonthlyCharge): void
    {
        $this->invalidateOwnerDebtCache($houseMonthlyCharge->house);
    }

    protected function invalidateOwnerDebtCache(?House $house): void
    {
        if (!$house) {
            return;
        }

        foreach ($house->owners as $owner) {
            $cacheKey = "user.{$owner->id}.total_debt";
            Cache::forget($cacheKey);
        }
    }
}
