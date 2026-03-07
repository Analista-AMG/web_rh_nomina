<?php

namespace App\Providers;

use App\Listeners\EquipoAutoCarryOnLogin;
use App\Services\JerarquiaService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(JerarquiaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useTailwind();
        Event::listen(Login::class, EquipoAutoCarryOnLogin::class);
    }
}
