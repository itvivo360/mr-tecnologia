<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['status' => ['nullable', 'in:unread,read']]);

        return Inertia::render('Admin/Messages', [
            'messages' => ContactMessage::query()
                ->when(($filters['status'] ?? null) === 'unread', fn ($q) => $q->unread())
                ->when(($filters['status'] ?? null) === 'read', fn ($q) => $q->whereNotNull('read_at'))
                ->latest('id')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (ContactMessage $m) => [
                    ...$m->only(['id', 'name', 'email', 'phone', 'message']),
                    'read' => $m->read_at !== null,
                    'created_at' => $m->created_at->toIso8601String(),
                ]),
            'filters' => (object) $filters,
            'unread' => ContactMessage::unread()->count(),
        ]);
    }

    public function toggleRead(ContactMessage $message): RedirectResponse
    {
        $message->update(['read_at' => $message->read_at ? null : now()]);

        return back();
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('success', 'Mensaje eliminado.');
    }
}
