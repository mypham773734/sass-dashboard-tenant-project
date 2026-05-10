<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(
            \App\Services\Contracts\TenantServiceInterface::class, 
            \App\Services\Impl\TenantService::class
        ); 

        $this->app->bind(
            \App\Services\Contracts\ProjectServiceInterface::class, 
            \App\Services\Impl\ProjectService::class
        ); 

        $this->app->bind(
            \App\Services\Contracts\EnglishEgentServiceInterface::class, 
            \App\Services\Impl\EnglishAgentService::class
        ); 
    }
}
