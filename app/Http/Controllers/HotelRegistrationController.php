<?php

namespace App\Http\Controllers;

use App\Models\MasterPaket;
use App\Models\Registration;
use App\Services\HotelRegistrationApprovalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HotelRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $allowedStatuses = [
            Registration::STATUS_PENDING,
            Registration::STATUS_CONFIRMED,
            Registration::STATUS_REJECTED,
        ];

        $registrations = Registration::query()
            ->with(['reviewer', 'hotel', 'adminUser.profile', 'managerUser.profile'])
            ->when(in_array($status, $allowedStatuses, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.platform.registrations.index', [
            'page' => 'registrations',
            'icon' => 'fa fa-clipboard-check',
            'registrations' => $registrations,
            'status' => $status,
            'pendingCount' => Registration::pending()->count(),
        ]);
    }

    public function review(Registration $registration)
    {
        abort_unless($registration->status === Registration::STATUS_PENDING, 422, 'Registrasi ini sudah pernah diproses.');

        $trialPlan = MasterPaket::defaultRegistrasi();
        abort_unless($trialPlan, 422, 'Paket default registrasi belum diatur pada Master Paket.');
        $channels = $trialPlan->tvChannels()->withoutGlobalScope('hotel')
            ->where('tv_channels.is_active', true)->whereNull('tv_channels.deleted_at')
            ->orderBy('tv_channels.group_title')->orderBy('tv_channels.sort_order')->orderBy('tv_channels.name')->get();

        return view('pages.platform.registrations.review', [
            'page' => 'registrations',
            'icon' => 'fa fa-clipboard-check',
            'registration' => $registration,
            'trialPlan' => $trialPlan,
            'channels' => $channels,
        ]);
    }

    public function update(Request $request, Registration $registration, HotelRegistrationApprovalService $approvalService)
    {
        $baseRules = [
            'status' => ['required', Rule::in([Registration::STATUS_CONFIRMED, Registration::STATUS_REJECTED])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($request->input('status') === Registration::STATUS_CONFIRMED) {
            $baseRules += [
                'hotel_name' => ['required', 'string', 'max:180'],
                'hotel_address' => ['required', 'string', 'max:2000'],
            ];
        }

        $data = $request->validate($baseRules);

        if ($data['status'] === Registration::STATUS_CONFIRMED) {
            $approvalService->approve(
                $registration,
                $request->user(),
                $data['admin_notes'] ?? null,
                collect($data)->only(['hotel_name', 'hotel_address'])->all()
            );
        } else {
            abort_unless($registration->status === Registration::STATUS_PENDING, 422, 'Registrasi ini sudah pernah diproses.');
            $registration->update([
                'status' => Registration::STATUS_REJECTED,
                'admin_notes' => $data['admin_notes'] ?? null,
                'confirmed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        return redirect()->route('platform.registrations.index')
            ->with('success', __('platform.registration.review_saved'));
    }
}
