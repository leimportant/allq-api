<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class WhatsappServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function sendMessage($number, $message)
    {   
        $response = Http::get('http://localhost:4300/send-message', [
                    'number' => $number,
                    'message' => $message,
                ]);

        return $response;
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
