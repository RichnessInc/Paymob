<?php

namespace Richness\Paymob;

use Illuminate\Support\ServiceProvider;

class RichnessPaymobServiceProvider extends ServiceProvider {
    public function boot() {
        $this->publishes([
            __DIR__.'/config/paymobconfig.php' => config_path('paymobconfig.php')
        ], 'paymobconfig');
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/config/paymobconfig.php', 'paymobconfig');
    }
}