<?php

namespace App\Providers;

use App\Services\NewsAdapters\GuardianAdapter;
use App\Services\NewsAdapters\NewsApiAdapter;
use App\Services\NewsAdapters\NytimesAdapter;
use Illuminate\Support\ServiceProvider;

class NewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NewsApiAdapter::class);
        $this->app->singleton(GuardianAdapter::class);
        $this->app->singleton(NytimesAdapter::class);
    }

    public function boot(): void
    {
        //
    }
}
