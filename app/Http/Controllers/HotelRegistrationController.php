<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\HotelRegistrationApprovalService;
use Illuminate\Http\Request;

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
            ->with(['reviewer', 'hotel', 'adminUser.profile'])
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

    public function update(Request $request, Registration $registration, HotelRegistrationApprovalService $approvalService)
    {
        $data = $request->validate([
            'status' => ['required', 'in:confirmed,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === Registration::STATUS_CONFIRMED) {
            $approvalService->approve($registration, $request->user(), $data['admin_notes'] ?? null);
        } else {
            abort_unless($registration->status === Registration::STATUS_PENDING, 422, 'Registrasi ini sudah pernah diproses.');
            $registration->update([
                'status' => Registration::STATUS_REJECTED,
                'admin_notes' => $data['admin_notes'] ?? null,
                'confirmed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        return back()->with('success', __('platform.registration.review_saved'));
    }
}
