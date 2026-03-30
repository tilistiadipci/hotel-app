<?php

namespace App\Http\Middleware;

use App\Repositories\SettingRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSettingIsActive
{
    public function __construct(protected SettingRepository $settingRepository)
    {
    }

    public function handle(Request $request, Closure $next, string $key): Response
    {
        $value = (string) session("settings.$key", $this->settingRepository->getValueByKey($key, 'active'));

        if ($value !== 'active') {
            abort(403);
        }

        return $next($request);
    }
}
