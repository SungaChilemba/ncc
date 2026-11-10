@extends('admin.layouts.app')

@section('title', 'Two-factor challenge')
@section('heading', 'Two-factor authentication')
@section('subheading', 'Confirm your identity before continuing to the admin area.')

@section('content')
    <div class="max-w-xl">
        <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <p class="text-sm text-slate-600 dark:text-slate-400">Enter the current 6-digit code from your authenticator app. You can also use one of the recovery codes you saved during setup.</p>

            <form method="POST" action="{{ route('admin.cases.totp.verify') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="code" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">Authenticator or recovery code</label>
                    <input id="code" name="code" type="text" autocomplete="one-time-code" maxlength="32" required autofocus
                           class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 p-2 font-mono text-lg tracking-[0.3em] focus:border-green-700 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                    @error('code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="rounded-md bg-green-700 dark:bg-green-600 px-5 py-2 font-semibold text-white hover:bg-green-800 dark:hover:bg-green-500">Verify and continue</button>
            </form>
        </section>

        <form method="POST" action="{{ route('admin.logout') }}" class="mt-5">
            @csrf
            <button type="submit" class="text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-red-700 dark:hover:text-red-300">Sign out instead</button>
        </form>
    </div>
@endsection
