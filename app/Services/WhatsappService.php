<?php

namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Log;

class WhatsappService
{
	const ACTIVE = 1;
	const INACTIVE = 0;

	public function sendMessage($number, $message)
    {   
        $response = Http::post('http://localhost:4300/send-message', [
                    'number' => $number,
                    'message' => $message,
                ]);

        return $response;
    }

    public function sendGroupMessage($number, $message)
    {   
        $response = Http::post('http://localhost:4300/send-group-message', [
                    'name' => "keluarga setia",
                    'message' => $message,
                ]);

        return $response;
    }

}