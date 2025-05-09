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
        // Mengatasi masalah insertOrIgnore pada MongoDB
        \Illuminate\Database\Query\Builder::macro('insertOrIgnore', function (array $values) {
            if (empty($values)) {
                return true;
            }
            
            // Konversi insertOrIgnore menjadi regular insert dengan try-catch
            try {
                return $this->insert($values);
            } catch (\Exception $e) {
                // Abaikan error duplicate key
                return true;
            }
        });
    }
}