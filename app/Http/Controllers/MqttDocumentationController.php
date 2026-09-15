<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class MqttDocumentationController extends Controller
{
    public function __invoke(): View
    {
        $path = base_path('topic mqtt.md');

        abort_unless(is_file($path) && is_readable($path), 404, 'Dokumentasi MQTT tidak ditemukan.');

        return view('pages.docs.mqtt', [
            'content' => Str::markdown((string) file_get_contents($path), [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ]);
    }
}
