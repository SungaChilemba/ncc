<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'CMS') - {{ config('app.name') }}</title>
    <script>
        // Apply the saved theme and sidebar state before first paint to avoid a flash.
        (function () {
            try {
                var storedTheme = window.localStorage.getItem('ncc-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (storedTheme === 'dark' || (storedTheme !== 'light' && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
                if (window.localStorage.getItem('ncc-sidebar-collapsed') === 'true') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) { /* storage unavailable — fall back to defaults */ }
        })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100">
    @php
        $adminUser = auth()->user();
        $showCms = $adminUser?->canManageCms() ?? false;
        $showCases = $adminUser?->hasCaseAccess() ?? false;
        $showUsers = $adminUser?->hasAnyPermission('admin.users.view', 'admin.users.manage') ?? false;
        $showRoles = $adminUser?->hasPermission('admin.roles.manage') ?? false;
        $showAudit = $adminUser?->hasPermission('admin.audit.view') ?? false;
        $showAccess = $showUsers || $showRoles || $showAudit;
        $caseQueues = $showCases && ! request()->routeIs('admin.cases.totp.*')
            ? \App\Support\CaseQueues::forUser($adminUser)
            : null;

        $homeRoute = match (true) {
            $showCms => route('admin.dashboard'),
            $showCases => route('admin.cases.index'),
            $showUsers => route('admin.access.users.index'),
            $showRoles => route('admin.access.roles.index'),
            default => route('admin.access.audit'),
        };
        $areaLabel = $showCms ? 'Content Studio' : ($showCases ? 'Case Management' : 'Administration');

        $navLinkBase = 'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900';
        $navLinkIdle = 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white';
        $navLinkActive = 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-300';
        $navIconBase = 'w-5 shrink-0 text-center text-sm';
        $navIconIdle = 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300';
        $navIconActive = 'text-green-700 dark:text-green-400';
        $navGroup = 'mt-6 px-3 pb-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500';
    @endphp

    <div
        x-data="{
            collapsed: false,
            mobileOpen: false,
            init() {
                try {
                    this.collapsed = window.localStorage.getItem('ncc-sidebar-collapsed') === 'true';
                } catch (e) { /* storage unavailable */ }
                this.syncSidebar();
            },
            syncSidebar() {
                document.documentElement.classList.toggle('sidebar-collapsed', this.collapsed);
            },
            toggleSidebar() {
                this.collapsed = !this.collapsed;
                try {
                    window.localStorage.setItem('ncc-sidebar-collapsed', this.collapsed ? 'true' : 'false');
                } catch (e) { /* storage unavailable */ }
                this.syncSidebar();
            },
        }"
        x-effect="document.body.classList.toggle('overflow-hidden', mobileOpen)"
        @keydown.escape.window="mobileOpen = false"
        @resize.window="if (window.innerWidth >= 1024) mobileOpen = false"
        class="admin-shell min-h-screen"
    >
        <div
            x-show="mobileOpen"
            x-cloak
            x-transition.opacity
            @click="mobileOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
            aria-hidden="true"
        ></div>

        <div class="lg:flex">
            <aside
                id="admin-sidebar"
                aria-label="Admin navigation"
                :class="mobileOpen && 'is-open'"
                class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col border-r border-slate-200 bg-white shadow-xl transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:max-w-none lg:shadow-none"
            >
                <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-3 dark:border-slate-800">
                    <a href="{{ $homeRoute }}" class="flex min-w-0 items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-700 text-[11px] font-black tracking-wide text-white shadow-sm dark:bg-green-600">NCC</span>
                        <span data-nav-label class="min-w-0">
                            <span class="block truncate text-sm font-bold text-slate-950 dark:text-white">{{ $areaLabel }}</span>
                            <span class="block truncate text-[11px] font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">Admin portal</span>
                        </span>
                    </a>

                    <button
                        type="button"
                        @click="mobileOpen = false"
                        class="ml-auto inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white lg:hidden"
                        aria-label="Close navigation"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <button
                    type="button"
                    @click="toggleSidebar()"
                    :aria-expanded="(! collapsed).toString()"
                    aria-controls="admin-sidebar"
                    title="Toggle sidebar"
                    class="absolute -right-3 top-5 z-20 hidden h-6 w-6 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 shadow-sm transition hover:text-green-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:text-green-300 lg:flex"
                >
                    <i class="fa-solid fa-chevron-left text-[10px] transition-transform duration-300" :class="collapsed && 'rotate-180'"></i>
                    <span class="sr-only">Collapse or expand sidebar</span>
                </button>

                <nav class="flex flex-1 flex-col gap-1 overflow-y-auto px-3 py-4">
                    @if ($showCms)
                        <a href="{{ route('admin.dashboard') }}" data-nav-link aria-label="Dashboard" title="Dashboard" class="{{ $navLinkBase }} {{ request()->routeIs('admin.dashboard') ? $navLinkActive : $navLinkIdle }}">
                            <i data-nav-icon class="fa-solid fa-gauge-high {{ $navIconBase }} {{ request()->routeIs('admin.dashboard') ? $navIconActive : $navIconIdle }}"></i>
                            <span data-nav-label class="truncate">Dashboard</span>
                        </a>
                    @endif

                    @if ($showCases && $caseQueues)
                        @php
                            $onCaseIndex = request()->routeIs('admin.cases.index');
                            $activeStatus = $onCaseIndex ? (string) request()->query('status', '') : '';
                            $primaryActive = $onCaseIndex && $activeStatus === '';
                            $countBadgeIdle = 'shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400';
                            $countBadgeActive = 'shrink-0 rounded-full bg-green-700 px-2 py-0.5 text-[11px] font-semibold text-white dark:bg-green-600';
                        @endphp

                        <p data-nav-group class="{{ $navGroup }}">Case Queues</p>

                        <a href="{{ route('admin.cases.index', request()->except('status', 'page')) }}"
                           data-nav-link aria-label="{{ $caseQueues['primary']['label'] }}, {{ $caseQueues['primary']['count'] }} {{ \Illuminate\Support\Str::plural('case', $caseQueues['primary']['count']) }}" title="{{ $caseQueues['primary']['label'] }}"
                           @if ($primaryActive) aria-current="page" @endif
                           class="{{ $navLinkBase }} {{ $primaryActive ? $navLinkActive : $navLinkIdle }}">
                            <span data-nav-icon class="{{ $navIconBase }} {{ $primaryActive ? $navIconActive : $navIconIdle }}"><i class="fa-solid fa-inbox"></i></span>
                            <span data-nav-label class="min-w-0 flex-1 truncate">{{ $caseQueues['primary']['label'] }}</span>
                            <span aria-hidden="true" class="{{ $primaryActive ? $countBadgeActive : $countBadgeIdle }}">{{ $caseQueues['primary']['count'] }}</span>
                        </a>

                        @foreach ($caseQueues['groups'] as $group)
                            <p data-nav-group class="{{ $navGroup }}">{{ $group['label'] }}</p>
                            @foreach ($group['items'] as $item)
                                @php
                                    $itemActive = $onCaseIndex && $activeStatus === $item['key'];
                                @endphp
                                <a href="{{ route('admin.cases.index', $item['params'] + request()->except('status', 'page')) }}"
                                   data-nav-link aria-label="{{ $item['label'] }}, {{ $item['count'] }} {{ \Illuminate\Support\Str::plural('case', $item['count']) }}" title="{{ $item['label'] }}"
                                   @if ($itemActive) aria-current="page" @endif
                                   class="{{ $navLinkBase }} {{ $itemActive ? $navLinkActive : $navLinkIdle }}">
                                    <span data-nav-icon class="{{ $navIconBase }}"><span class="inline-block h-2 w-2 rounded-full {{ $item['dot'] }}"></span></span>
                                    <span data-nav-label class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                    <span aria-hidden="true" class="{{ $itemActive ? $countBadgeActive : $countBadgeIdle }}">{{ $item['count'] }}</span>
                                </a>
                            @endforeach
                        @endforeach

                        @if (! empty($caseQueues['shortcuts']))
                            <p data-nav-group class="{{ $navGroup }}">Shortcuts</p>
                            @foreach ($caseQueues['shortcuts'] as $shortcut)
                                @php
                                    $shortcutActive = $onCaseIndex && collect($shortcut['params'])->every(
                                        fn (string $value, string $key): bool => (string) request()->query($key, '') === $value
                                    );
                                @endphp
                                <a href="{{ route('admin.cases.index', $shortcut['params'] + request()->except('status', 'page')) }}"
                                   data-nav-link aria-label="{{ $shortcut['label'] }}, {{ $shortcut['count'] }} {{ \Illuminate\Support\Str::plural('case', $shortcut['count']) }}" title="{{ $shortcut['label'] }}"
                                   @if ($shortcutActive) aria-current="page" @endif
                                   class="{{ $navLinkBase }} {{ $shortcutActive ? $navLinkActive : $navLinkIdle }}">
                                    <span data-nav-icon class="{{ $navIconBase }} {{ $shortcutActive ? $navIconActive : $navIconIdle }}"><i class="fa-solid {{ $shortcut['icon'] }}"></i></span>
                                    <span data-nav-label class="min-w-0 flex-1 truncate">{{ $shortcut['label'] }}</span>
                                    <span aria-hidden="true" class="{{ $shortcutActive ? $countBadgeActive : $countBadgeIdle }}">{{ $shortcut['count'] }}</span>
                                </a>
                            @endforeach
                        @endif
                    @endif

                    @if ($showCms)
                        <a href="{{ route('admin.media.index') }}" data-nav-link aria-label="Media Library" title="Media Library" class="{{ $navLinkBase }} {{ request()->routeIs('admin.media.*') ? $navLinkActive : $navLinkIdle }}">
                            <i data-nav-icon class="fa-solid fa-photo-film {{ $navIconBase }} {{ request()->routeIs('admin.media.*') ? $navIconActive : $navIconIdle }}"></i>
                            <span data-nav-label class="truncate">Media Library</span>
                        </a>
                        <a href="{{ route('admin.newsletter.index') }}" data-nav-link aria-label="Newsletter" title="Newsletter" class="{{ $navLinkBase }} {{ request()->routeIs('admin.newsletter.*') ? $navLinkActive : $navLinkIdle }}">
                            <i data-nav-icon class="fa-solid fa-envelope-open-text {{ $navIconBase }} {{ request()->routeIs('admin.newsletter.*') ? $navIconActive : $navIconIdle }}"></i>
                            <span data-nav-label class="truncate">Newsletter</span>
                        </a>

                        <p data-nav-group class="{{ $navGroup }}">Website pages</p>
                        @foreach (config('cms.sections') as $key => $managedSection)
                            @php($pageActive = request()->route('section') === $key)
                            <a href="{{ route('admin.contents.index', ['section' => $key]) }}" data-nav-link aria-label="{{ $managedSection['label'] }}" title="{{ $managedSection['label'] }}" class="{{ $navLinkBase }} {{ $pageActive ? $navLinkActive : $navLinkIdle }}">
                                <i data-nav-icon class="fa-solid {{ $managedSection['icon'] ?? 'fa-file' }} {{ $navIconBase }} {{ $pageActive ? $navIconActive : $navIconIdle }}"></i>
                                <span data-nav-label class="truncate">{{ $managedSection['label'] }}</span>
                            </a>
                        @endforeach
                    @endif

                    @if ($showAccess)
                        <p data-nav-group class="{{ $navGroup }}">Access control</p>

                        @if ($showUsers)
                            <a href="{{ route('admin.access.users.index') }}" data-nav-link aria-label="Users" title="Users" class="{{ $navLinkBase }} {{ request()->routeIs('admin.access.users.*') ? $navLinkActive : $navLinkIdle }}">
                                <i data-nav-icon class="fa-solid fa-users {{ $navIconBase }} {{ request()->routeIs('admin.access.users.*') ? $navIconActive : $navIconIdle }}"></i>
                                <span data-nav-label class="truncate">Users</span>
                            </a>
                        @endif

                        @if ($showRoles)
                            <a href="{{ route('admin.access.roles.index') }}" data-nav-link aria-label="Roles and permissions" title="Roles &amp; permissions" class="{{ $navLinkBase }} {{ request()->routeIs('admin.access.roles.*') ? $navLinkActive : $navLinkIdle }}">
                                <i data-nav-icon class="fa-solid fa-user-shield {{ $navIconBase }} {{ request()->routeIs('admin.access.roles.*') ? $navIconActive : $navIconIdle }}"></i>
                                <span data-nav-label class="truncate">Roles &amp; permissions</span>
                            </a>
                        @endif

                        @if ($showAudit)
                            <a href="{{ route('admin.access.audit') }}" data-nav-link aria-label="Access audit" title="Access audit" class="{{ $navLinkBase }} {{ request()->routeIs('admin.access.audit') ? $navLinkActive : $navLinkIdle }}">
                                <i data-nav-icon class="fa-solid fa-clipboard-list {{ $navIconBase }} {{ request()->routeIs('admin.access.audit') ? $navIconActive : $navIconIdle }}"></i>
                                <span data-nav-label class="truncate">Access audit</span>
                            </a>
                        @endif
                    @endif

                    @if ($showCases && $adminUser?->caseRoleLabel())
                        <p data-nav-group class="{{ $navGroup }}">{{ $adminUser->caseRoleLabel() }}</p>
                    @endif
                </nav>

                <div class="shrink-0 border-t border-slate-200 p-3 dark:border-slate-800 lg:hidden">
                    <p class="px-3 pb-2 text-xs text-slate-500 dark:text-slate-400">{{ $adminUser?->name }}</p>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                            <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex min-h-screen min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900/90">
                    <div class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-start gap-3">
                            <button
                                type="button"
                                @click="mobileOpen = true"
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-green-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white lg:hidden"
                                aria-label="Open navigation"
                                aria-controls="admin-sidebar"
                            >
                                <i class="fa-solid fa-bars"></i>
                            </button>
                            <div class="min-w-0">
                                <h1 class="truncate text-xl font-bold text-slate-950 dark:text-white sm:text-2xl">@yield('heading', 'CMS')</h1>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">@yield('subheading')</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                            <button
                                type="button"
                                id="theme-toggle"
                                aria-label="Toggle dark mode"
                                aria-pressed="false"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-green-300 dark:focus:ring-offset-slate-900"
                            >
                                <svg class="h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                                </svg>
                                <svg class="hidden h-5 w-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                                </svg>
                            </button>

                            <a href="{{ route('home') }}" target="_blank" class="hidden items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-green-600 hover:text-green-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-green-500 dark:hover:text-green-300 sm:inline-flex">
                                View site
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                            </a>

                            <form method="POST" action="{{ route('admin.logout') }}" class="hidden sm:block">
                                @csrf
                                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">Logout</button>
                            </form>
                        </div>
                    </div>
                </header>

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('status'))
                        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-300">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
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
    </div>

    <script>
        (function () {
            var toggle = document.getElementById('theme-toggle');
            if (!toggle) { return; }

            var sync = function () {
                toggle.setAttribute('aria-pressed', document.documentElement.classList.contains('dark') ? 'true' : 'false');
            };

            toggle.addEventListener('click', function () {
                var isDark = document.documentElement.classList.toggle('dark');
                try { window.localStorage.setItem('ncc-theme', isDark ? 'dark' : 'light'); } catch (e) { /* storage unavailable */ }
                sync();
            });

            sync();
        })();
    </script>

    @stack('scripts')
</body>
</html>
