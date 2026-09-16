<?php

namespace App\Http\Controllers;

use App\Services\WilayahIndonesiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahIndonesiaController extends Controller
{
    public function search(Request $request, WilayahIndonesiaService $wilayah): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'results' => $wilayah->search((string) ($validated['q'] ?? '')),
        ]);
    }
}
