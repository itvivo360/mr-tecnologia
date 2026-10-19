<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Campo trampa invisible: los bots lo completan, las personas no.
        if (filled($request->input('website'))) {
            return back()->with('success', 'Recibimos tu mensaje. Te responderemos pronto.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        ContactMessage::create([...$data, 'ip_address' => $request->ip()]);

        return back()->with('success', 'Recibimos tu mensaje. Te responderemos pronto.');
    }
}
