<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\LandingPageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LandingPageController extends Controller
{
    public function edit()
    {
        $landingPage = LandingPage::query()->firstOrFail();
        $visits = LandingPageVisit::query();

        $stats = [
            'total' => (clone $visits)->count(),
            'unique' => (clone $visits)->distinct()->count('ip_address'),
            'today' => (clone $visits)->whereDate('visited_at', today())->count(),
            'month' => (clone $visits)->whereBetween('visited_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];

        $recentVisitors = LandingPageVisit::query()
            ->select('ip_address', DB::raw('COUNT(*) as total'), DB::raw('MAX(visited_at) as last_visit'))
            ->groupBy('ip_address')
            ->orderByDesc('last_visit')
            ->limit(20)
            ->get();

        return view('pages.platform.landing_page.edit', compact('landingPage', 'stats', 'recentVisitors') + [
            'page' => 'landing-page',
            'icon' => 'fa fa-globe',
        ]);
    }

    public function update(Request $request)
    {
        $landingPage = LandingPage::query()->firstOrFail();
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:150'],
            'meta_title' => ['required', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],
            'html_content' => ['required', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($request->boolean('remove_logo') && $landingPage->logo_path) {
            Storage::disk('public')->delete($landingPage->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($landingPage->logo_path) {
                Storage::disk('public')->delete($landingPage->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('landing-page', 'public');
        }

        unset($data['logo'], $data['remove_logo']);
        $landingPage->update($data);

        return redirect()->route('platform.landing-page.edit')->with('success', 'Landing page berhasil diperbarui.');
    }
}
