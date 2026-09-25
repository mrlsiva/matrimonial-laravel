<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('pages.contact', ['page' => CmsPage::where('slug', 'contact-us')->where('is_active', true)->first()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{7,20}$/'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'website' => ['prohibited'], // honeypot
        ]);

        unset($data['website']);
        ContactMessage::create($data + ['user_id' => $request->user()?->id]);

        return back()->with('success', 'Thank you! Our support team will get back to you shortly.');
    }
}
