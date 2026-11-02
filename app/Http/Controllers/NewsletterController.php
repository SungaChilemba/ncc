<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        // Simple honeypot: real visitors never fill the hidden field.
        if ($request->filled('website')) {
            return redirect()->back()->with('newsletter_status', 'Thanks! You are subscribed to NCC updates.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        NewsletterSubscriber::updateOrCreate(
            ['email' => strtolower($validated['email'])],
            ['ip_address' => $request->ip(), 'source' => 'footer'],
        );

        return redirect()->back()->with('newsletter_status', 'Thanks! You are subscribed to NCC updates.');
    }
}
