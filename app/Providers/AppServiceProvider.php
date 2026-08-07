<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Validator::extend('required_without_all', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData();
            foreach ($parameters as $field) {
                if (!empty($data[$field])) {
                    return true;
                }
            }
            return !empty($value);
        });
    }
}
