<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport;

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
        // Railway blocks all outbound SMTP ports, so mail is sent via Brevo's
        // HTTPS API instead of SMTP — see App\Providers\AppServiceProvider.
        Mail::extend('brevo', function () {
            return Transport::fromDsn(sprintf(
                'brevo+api://%s@default',
                config('services.brevo.key')
            ));
        });
    }
}
