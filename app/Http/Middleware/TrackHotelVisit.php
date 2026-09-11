<?php

namespace App\Http\Middleware;

use App\Models\HotelVisitLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TrackHotelVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if (! app()->runningUnitTests() && $request->isMethod('GET') && ! $request->ajax() && Schema::hasTable('hotel_visit_logs')) {
                HotelVisitLog::query()->create([
                    'hotel_id' => $request->user()?->hotel_id,
                    'user_id' => $request->user()?->id,
                    'ip_address' => $request->ip(),
                    'method' => $request->method(),
                    'url' => '/'.$request->path(),
                    'route_name' => $request->route()?->getName(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                    'visited_at' => now(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Tidak dapat mencatat kunjungan hotel.', ['message' => $exception->getMessage()]);
        }

        return $response;
    }
}
