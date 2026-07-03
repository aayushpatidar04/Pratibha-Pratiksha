<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\WhatsappMessage;
use App\Models\WhatsappSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhatsappController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Whatsapp/Index', [
            'recentMessages' => WhatsappMessage::orderByDesc('sent_at')->limit(20)->get(),
            'setting' => WhatsappSetting::where('is_active', true)->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_type' => 'required|in:individual,all_residents,all_parents',
            'recipient_phone' => 'nullable|string|max:20',
            'content' => 'required|string',
        ]);

        if ($validated['recipient_type'] === 'individual') {
            WhatsappMessage::create([
                'recipient_type' => 'resident',
                'recipient_phone' => $validated['recipient_phone'],
                'content' => $validated['content'],
                'status' => 'sent',
                'created_by' => $request->user()?->id,
            ]);
        } else {
            $isParents = $validated['recipient_type'] === 'all_parents';
            $residents = Resident::where('status', 'active')->get();
            foreach ($residents as $resident) {
                WhatsappMessage::create([
                    'recipient_type' => $isParents ? 'parent' : 'resident',
                    'recipient_id' => $resident->id,
                    'recipient_phone' => $isParents ? ($resident->father_phone ?? $resident->mother_phone ?? '') : $resident->whatsapp_number,
                    'content' => $validated['content'],
                    'status' => 'sent',
                    'created_by' => $request->user()?->id,
                ]);
            }
        }

        return back()->with('success', 'Message sent successfully.');
    }
}