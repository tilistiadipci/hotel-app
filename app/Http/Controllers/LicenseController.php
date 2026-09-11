<?php

namespace App\Http\Controllers;

use App\Tenancy\TenantContext;

class LicenseController extends Controller
{
    protected $page = 'license';
    protected $icon = 'fa fa-key';

    public function index()
    {
        $hotel = app(TenantContext::class)->hotel();
        $license = $hotel?->licenses()->latest('starts_at')->first();
        $expiresAt = $license?->expires_at;
        $isValid = $license?->isUsable() ?? true;

        $appDetails = [
            [
                'label' => 'Status',
                'value' => $isValid ? 'License is valid' : 'License is not active',
                'meta' => $isValid ? 'Valid' : 'Invalid',
                'meta_class' => $isValid ? 'is-valid' : 'is-invalid',
            ],
            [
                'label' => 'License',
                'value' => $license ? strtoupper($license->plan_code).' - '.ucfirst($license->status) : 'Platform License',
            ],
            [
                'label' => 'App Name',
                'value' => 'BIO - Hotel TV Application System',
            ],
            [
                'label' => 'Custom Name & Domain',
                'value' => 'BIO | Hotel TV Application System (bio.com)',
            ],
            [
                'label' => 'Customer Name',
                'value' => $hotel?->name ?? 'Platform Administrator',
            ],
            [
                'label' => 'Expiration Date',
                'value' => $expiresAt
                    ? $expiresAt->format('d/m/Y').' ('.max(0, now()->diffInDays($expiresAt, false)).' days remaining)'
                    : 'No expiration date',
            ],
        ];

        $coreFeatures = [

            [
                'name' => 'Alarm & Notification',
                'description' => 'Alarm triggers, notifications, and alert management',
                'status' => 'Enabled',
            ],
            [
                'name' => 'Device Offline Monitoring',
                'description' => 'Detect disconnected or unreachable trigger devices automatically.',
                'status' => 'Enabled',
            ],
            [
                'name' => 'Alert Escalation',
                'description' => 'Route unresolved alarm events to the next response level.',
                'status' => 'Enabled',
            ],
            // [
            //     'name' => 'Tenant Catalog',
            //     'description' => 'Browse tenants, categories, and available menu items.',
            //     'status' => 'Enabled',
            // ],
            // [
            //     'name' => 'Order Placement',
            //     'description' => 'Create shopping or pantry orders directly from the application.',
            //     'status' => 'Enabled',
            // ],
            // [
            //     'name' => 'Transaction Tracking',
            //     'description' => 'Review order progress and transaction history in one place.',
            //     'status' => 'Enabled',
            // ],
            // [
            //     'name' => 'Automatic Charge Calculation',
            //     'description' => 'Apply tax and service charge settings to every transaction.',
            //     'status' => 'Enabled',
            // ],
            [
                'name' => 'Movie Library',
                'description' => 'Manage and publish video-on-demand content collections.',
                'status' => 'Enabled',
            ],
            [
                'name' => 'Category Browsing',
                'description' => 'Organize VOD content into categories for easier discovery.',
                'status' => 'Enabled',
            ],
            [
                'name' => 'Player Streaming',
                'description' => 'Deliver movies to connected player devices with stream support.',
                'status' => 'Enabled',
            ],
            [
                'name' => 'Song Library',
                'description' => 'Store and manage music tracks in the application library.',
                'status' => 'Enabled',
            ],
            [
                'name' => 'Player Sync',
                'description' => 'Send music playback to registered player devices seamlessly.',
                'status' => 'Enabled',
            ],
        ];

        return view('pages.license.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'appDetails' => $appDetails,
            'coreFeatures' => $coreFeatures,
        ]);
    }
}
