<?php

namespace App\Providers;

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
        //
    }
}
