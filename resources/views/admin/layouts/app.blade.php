<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'CMS') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="min-h-screen lg:grid lg:grid-cols-[17rem_1fr]">
        <aside class="border-b border-slate-200 bg-white lg:min-h-screen lg:border-b-0 lg:border-r">
            <div class="flex items-center justify-between px-5 py-4 lg:block">
                <a href="{{ route('admin.dashboard') }}" class="block">
                    <p class="text-xs font-bold uppercase tracking-wider text-green-700">NCC Website</p>
                    <p class="text-lg font-bold text-slate-950">Content Studio</p>
                </a>
                <form method="POST" action="{{ route('admin.logout') }}" class="lg:hidden">
                    @csrf
                    <button type="submit" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Logout</button>
                </form>
            </div>

            <nav class="flex gap-2 overflow-x-auto px-5 pb-4 lg:block lg:space-y-1 lg:overflow-visible">
                <a href="{{ route('admin.dashboard') }}" class="block rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-green-50 text-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">Dashboard</a>
                <a href="{{ route('admin.media.index') }}" class="block rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('admin.media.*') ? 'bg-green-50 text-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">Media Library</a>
                <a href="{{ route('admin.newsletter.index') }}" class="block rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('admin.newsletter.*') ? 'bg-green-50 text-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">Newsletter</a>
                @foreach (config('cms.sections') as $key => $managedSection)
                    <a href="{{ route('admin.contents.index', ['section' => $key]) }}" class="block whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold {{ request()->route('section') === $key ? 'bg-green-50 text-green-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                        {{ $managedSection['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <div>
            <header class="border-b border-slate-200 bg-white">
                <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-950">@yield('heading', 'CMS')</h1>
                        <p class="mt-1 text-sm text-slate-500">@yield('subheading')</p>
                    </div>
                    <div class="hidden items-center gap-3 lg:flex">
                        <a href="{{ route('home') }}" target="_blank" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">View site</a>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Logout</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="px-5 py-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <p class="font-semibold">Please fix the highlighted fields.</p>
                        <ul class="mt-2 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
