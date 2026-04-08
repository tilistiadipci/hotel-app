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
        // Always use the latest stored setting for access checks.
        // Session can be stale when another user changes feature flags.
        $value = (string) $this->settingRepository->getValueByKey($key, 'active');

        $settings = session('settings', []);
        if (($settings[$key] ?? null) !== $value) {
            $settings[$key] = $value;
            session(['settings' => $settings]);
        }

        if ($value !== 'active') {
            abort(403);
        }

        return $next($request);
    }
}
