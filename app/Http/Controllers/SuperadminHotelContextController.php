<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SuperadminHotelContextController extends Controller
{
    /**
     * Let superadmin "act as" a chosen hotel (or the Master hotel) so the
     * normal tenant-scoped screens (media library, themes, tv channels)
     * operate on that hotel's own data.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hotel_id' => ['required', 'string', 'exists:hotels,id'],
            'redirect' => ['nullable', 'string'],
        ]);

        $request->session()->put('active_hotel_id', $validated['hotel_id']);

        return redirect()->to($validated['redirect'] ?? route('media.index'));
    }
}
