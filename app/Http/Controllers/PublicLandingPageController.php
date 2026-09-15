<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\LandingPageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicLandingPageController extends Controller
{
    public function show(Request $request)
    {
        $landingPage = LandingPage::query()->first();

        if (! $landingPage || ! $landingPage->is_active) {
            return $request->user() ? $this->authenticatedRedirect($request) : redirect()->route('login');
        }

        try {
            LandingPageVisit::query()->create([
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
                'referrer' => mb_substr((string) $request->headers->get('referer'), 0, 2000) ?: null,
                'visited_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Tidak dapat mencatat kunjungan landing page.', ['message' => $exception->getMessage()]);
        }

        $logoUrl = $landingPage->logo_path ? asset('storage/'.$landingPage->logo_path) : null;
        $html = strtr($landingPage->html_content, [
            '{{LOGIN_URL}}' => route('login'),
            '{{REGISTER_URL}}' => route('register'),
            '{{CSRF_TOKEN}}' => csrf_token(),
            '{{CSRF_FIELD}}' => '<input type="hidden" name="_token" value="'.e(csrf_token()).'">',
            '{{LOGO_URL}}' => $logoUrl ?: '',
            '{{BASE_URL}}' => url('/'),
            '{{SITE_NAME}}' => e($landingPage->site_name),
        ]);

        return response()->view('pages.landing.show', compact('landingPage', 'logoUrl', 'html'));
    }

    private function authenticatedRedirect(Request $request)
    {
        return $request->user()->hasRoleCategory('master', 'superadmin')
            ? redirect()->route('platform.dashboard')
            : redirect()->route('dashboard.index');
    }
}
