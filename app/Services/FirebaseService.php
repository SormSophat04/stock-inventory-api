<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FirebaseService
{
    public static function sendNotification($user, $title, $message)
    {
        if (!$user->fcm_token) return;

        $serverKey = env('FIREBASE_SERVER_KEY');
        $url = "https://fcm.googleapis.com/fcm/send";

        $data = [
            "to" => $user->fcm_token,
            "notification" => [
                "title" => $title,
                "body" => $message,
                "sound" => "default"
            ]
        ];

        Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type'  => 'application/json'
        ])->post($url, $data);
    }
}
