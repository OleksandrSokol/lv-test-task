<?php

namespace App\Providers;

use App\Modules\Core\Contracts\RecordsActivity;
use App\Modules\Core\Listeners\WriteActivityLog;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Listeners\SendOrderStatusLetter;
use Illuminate\Support\Facades\Event;
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
        Event::listen(RecordsActivity::class, WriteActivityLog::class);
        Event::listen(OrderStatusChanged::class, SendOrderStatusLetter::class);
    }
}
