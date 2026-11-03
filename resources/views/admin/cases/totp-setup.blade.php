@extends('admin.layouts.app')

@section('title', 'Set up two-factor authentication')
@section('heading', 'Set up two-factor authentication')
@section('subheading', 'Your role requires an authenticator code on every new session.')

@section('content')
    <div class="max-w-2xl space-y-6">
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            <p class="font-semibold">You must enable two-factor authentication before continuing to the admin area.</p>
            <p class="mt-2">Use any TOTP authenticator app (for example Aegis, 1Password, Microsoft Authenticator, or Google Authenticator).</p>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">1. Add the account</h2>
            <p class="mt-2 text-sm text-slate-600">Open your authenticator app and add a new account using this setup key:</p>

            <p class="mt-4 select-all break-all rounded-md bg-slate-100 p-4 font-mono text-lg font-bold tracking-wider text-slate-900">{{ $formattedSecret }}</p>

            <details class="mt-4 text-sm text-slate-600">
                <summary class="cursor-pointer font-semibold text-green-700">Advanced: use the otpauth link</summary>
                <p class="mt-2 break-all rounded-md bg-slate-50 p-3 font-mono text-xs text-slate-700">{{ $otpauthUri }}</p>
            </details>

            <p class="mt-4 text-xs text-slate-500">Type: time-based (TOTP), 6 digits, 30 second period, SHA1. The setup key is shown only while this page is open.</p>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">2. Confirm the code</h2>
            <p class="mt-2 text-sm text-slate-600">Enter the current 6-digit code from your app to finish setup.</p>

            <form method="POST" action="{{ route('admin.cases.totp.confirm') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label for="code" class="block text-sm font-semibold text-slate-800">Authenticator code</label>
                    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus
                           class="mt-1 w-full rounded-md border border-slate-300 p-2 font-mono text-lg tracking-[0.4em] focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="rounded-md bg-green-700 px-5 py-2 font-semibold text-white hover:bg-green-800">Enable and continue</button>
            </form>
        </section>

        <p class="text-sm text-slate-500">If you lose your authenticator, another administrator must reset your two-factor authentication before you can sign in again.</p>
    </div>
@endsection
