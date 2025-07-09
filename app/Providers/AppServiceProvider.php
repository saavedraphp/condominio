<?php

namespace App\Providers;

use App\Models\HouseMonthlyCharge;
use App\Models\HousePayment;
use App\Observers\HouseMonthlyChargeObserver;
use App\Observers\HousePaymentObserver;
use App\Repositories\House\EloquentHouseRepository;
use App\Repositories\House\HouseRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HouseRepositoryInterface::class, EloquentHouseRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        HousePayment::observe(HousePaymentObserver::class);
        HouseMonthlyCharge::observe(HouseMonthlyChargeObserver::class);
    }
}
