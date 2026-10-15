<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">

				<head>
								<meta charset="utf-8">
								<meta name="viewport" content="width=device-width, initial-scale=1">
								<title>@yield("title", config("app.name", 'National Children\'s Commission'))</title>
								@vite(["resources/css/app.css", "resources/js/app.js"])
								<!-- Free Font Awesome 6 CDN -->
								<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
												crossorigin="anonymous" referrerpolicy="no-referrer" />
								<!-- Playful rounded display font for the Children's Corner activity cards -->
								<link rel="preconnect" href="https://fonts.googleapis.com">
								<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
								<link rel="stylesheet"
												href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400..700&display=swap">

				</head>

				<body class="min-h-screen bg-slate-50 text-slate-900">
								<header class="sticky top-0 z-40 bg-white shadow-sm" x-data="{ open: false, latestOpen: false }">
												<!-- Top Header Bar: brand + helpline left, action items right -->
												<div class="border-b border-emerald-800 bg-emerald-800">
																<div class="mx-auto flex max-w-8xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:gap-4 lg:px-8">
																				<!-- Brand + persistent helpline badge, shown on every page -->
																				<div class="flex min-w-0 items-center gap-2 sm:gap-3 lg:gap-4">
																								<a href="{{ route("home") }}" class="flex shrink-0 items-center text-lg font-semibold text-white">
																												<img src="{{ asset("images/NCClogo.png") }}" alt="NCC Logo" class="h-20 w-auto">
																								</a>

																								<a href="tel:116"
																												class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1.5 text-[11px] font-semibold tracking-wide text-brand-800 shadow-sm transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-emerald-800 sm:gap-2 sm:px-3 sm:text-xs lg:px-3.5 lg:text-sm">
																												<i class="fa-solid fa-phone text-brand-600" aria-hidden="true"></i>
																												<span class="whitespace-nowrap">Child Helpline: <span class="font-bold">116</span></span>
																								</a>
																				</div>

																				<!-- Desktop action items -->
																				<div class="hidden items-center gap-3 lg:flex">
																								<a href="{{ route("childrens-corner") }}"
																												class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold uppercase tracking-wide text-slate-800 shadow-sm transition hover:border-emerald-600 hover:text-emerald-700">
																												<i class="fa-solid fa-child mr-2 text-red-600"></i>
																												Child Rights Corner
																								</a>

																								<a href="{{ route("reporting") }}"
																												class="inline-flex items-center justify-center rounded-md bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
																												Report a Case Now
																								</a>

																								<a href="{{ route("donate") }}"
																												class="inline-flex items-center justify-center rounded-md bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
																												Donate
																								</a>
																				</div>

																				<!-- Mobile menu toggle -->
																				<div class="flex items-center lg:hidden">
																								<button type="button"
																												class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-emerald-700"
																												@click="open = !open" :aria-expanded="open" aria-label="Toggle navigation">
																												<i class="fa-solid text-xl" :class="open ? 'fa-xmark' : 'fa-bars'"></i>
																								</button>
																				</div>
																</div>
												</div>

												<!-- Navigation Menu Bar: full-width, directly below top header, right-aligned -->
												<div class="hidden border-b border-slate-200 bg-white lg:block">
																<div class="mx-auto flex max-w-8xl justify-end px-4 sm:px-6 lg:px-8">
																				<nav class="flex items-center justify-end gap-8 py-3 text-sm font-medium text-slate-800">
																								<a href="{{ route("home") }}" class="transition hover:text-emerald-700">Home</a>
																								<a href="{{ route("about") }}" class="transition hover:text-emerald-700">About</a>
																								<div class="relative">
																												<button @click="latestOpen = !latestOpen"
																																class="flex items-center gap-1 transition hover:text-emerald-700 focus:outline-none">
																																Latest <i class="fa-solid fa-chevron-down text-xs transition-transform"
																																				:class="latestOpen ? 'rotate-180' : ''"></i>
																												</button>
																												<div x-show="latestOpen" @click.away="latestOpen = false" x-transition
																																class="absolute top-full left-0 mt-3 min-w-48 rounded-lg border border-slate-200 bg-white py-2 shadow-lg z-40"
																																x-cloak>
																																<a href="{{ route("latest-news") }}"
																																				class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-emerald-700">News</a>
																																<a href="{{ route("news") }}"
																																				class="block px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-emerald-700">Stories</a>
																												</div>
																								</div>
																								<a href="{{ route("what-we-do") }}" class="transition hover:text-emerald-700">What we do</a>
																								<a href="{{ route("resources") }}" class="transition hover:text-emerald-700">Resources</a>
																								<a href="{{ route("advertise") }}" class="transition hover:text-emerald-700">Advertise</a>
																				</nav>
																</div>
												</div>

												<!-- Mobile collapsible menu -->
												<div class="border-t border-slate-200 bg-white px-4 pb-5 lg:hidden" x-show="open" x-transition x-cloak>
																<nav class="mt-4 flex flex-col gap-3 text-sm font-medium text-slate-700">
																				<a href="{{ route("home") }}" class="py-1 transition hover:text-emerald-700">Home</a>
																				<a href="{{ route("about") }}" class="py-1 transition hover:text-emerald-700">About</a>
																				<div>
																								<button @click="latestOpen = !latestOpen"
																												class="flex w-full items-center justify-between py-1 transition hover:text-emerald-700">
																												<span class="flex items-center gap-1">Latest <i
																																				class="fa-solid fa-chevron-down text-xs transition-transform"
																																				:class="latestOpen ? 'rotate-180' : ''"></i></span>
																								</button>
																								<div x-show="latestOpen" x-transition
																												class="ml-4 mt-2 flex flex-col gap-2 border-l-2 border-slate-100 pl-4" x-cloak>
																												<a href="{{ route("latest-news") }}"
																																class="py-1 transition hover:text-emerald-700">News</a>
																												<a href="{{ route("news") }}" class="py-1 transition hover:text-emerald-700">Stories</a>
																								</div>
																				</div>
																				<a href="{{ route("what-we-do") }}" class="py-1 transition hover:text-emerald-700">What we do</a>
																				<a href="{{ route("resources") }}" class="py-1 transition hover:text-emerald-700">Resources</a>
																				<a href="{{ route("advertise") }}" class="py-1 transition hover:text-emerald-700">Advertise</a>
																</nav>
																<div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4">
																				<a href="{{ route("childrens-corner") }}"
																								class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold uppercase tracking-wide text-slate-800 shadow-sm transition hover:border-emerald-600 hover:text-emerald-700">
																								<i class="fa-solid fa-child mr-2 text-red-600"></i>
																								Child Rights Corner
																				</a>
																				<a href="{{ route("reporting") }}"
																								class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
																								Report a Case Now
																				</a>
																				<a href="{{ route("donate") }}"
																								class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
																								Donate
																				</a>
																</div>
												</div>
								</header>

								<main class="mx-auto w-full max-w-8xl px-4 py-6 sm:px-6 lg:px-8">
												@yield("content")
												@if (!request()->routeIs("home") && empty($suppressCmsBlocks))
																@includeIf("partials.cms-public-blocks")
												@endif
								</main>

								<footer class="bg-green-700 text-white pt-12 pb-6">
												<div class="max-w-8xl mx-auto px-6 md:px-12 grid grid-cols-1 md:grid-cols-5 gap-8">
																<!-- Partnerships -->
																<div>
																				<h3 class="font-bold text-white mb-4">Partnerships & Stakeholders</h3>
																				<ul class="space-y-2">
																								<li>Unicef</li>
																								<li>NGOCCR</li>
																								<li>Save the Children</li>
																								<li>World Vision</li>
																								<li>Ministry of Gender</li>
																				</ul>
																</div>

																<!-- Quick Links -->
																<div>
																				<h3 class="font-bold text-white mb-4">Quick Links</h3>
																				<ul class="space-y-2">
																								<li><a href="{{ route("home") }}" class="hover:text-green-600">Home</a></li>
																								<li><a href="{{ route("about") }}" class="hover:text-green-600">About Us</a></li>
																								<li><a href="{{ route("what-we-do") }}" class="hover:text-green-600">What We Do</a></li>
																								<li><a href="{{ route("resources") }}" class="hover:text-green-600">Resources</a></li>
																								<li><a href="{{ route("advertise") }}" class="hover:text-green-600">Advertise</a></li>
																				</ul>
																</div>

																<!-- Help & Support -->
																<div>
																				<h3 class="font-bold text-white mb-4">Help & Support</h3>
																				<ul class="space-y-2">
																								<li><a href="{{ route("reporting") }}" class="hover:text-green-600">Report a Case</a></li>
																								<li><a href="{{ route("donate") }}" class="hover:text-green-600">Donate</a></li>
																								<li><a href="{{ route("news") }}" class="hover:text-green-600">Check Case Studies</a></li>
																								<li><a href="{{ route("resources") }}" class="hover:text-green-600">FAQs</a></li>
																								<li><a href="mailto:nccmalawi@gmail.com" class="hover:text-green-600">Contact Us</a></li>
																				</ul>
																</div>

																<!-- Contact -->
																<div>
																				<h3 class="font-bold text-white mb-4">Contact Us</h3>
																				<p>P.O Box 30346, Area 12<br> Lilongwe, Malawi</p>
																				<p class="mt-2">📞 +265 (0) 880‑268‑418</p>
																				<p>✉️ nccmalawi@gmail.com</p>
																</div>

																<!-- Find Us -->
																<div>
																				<h3 class="font-bold text-white mb-4">Find Us</h3>
																				<img src="{{ asset("images/map-placeholder.jpg") }}" alt="Map"
																								class="rounded shadow-md w-full h-32 object-cover">
																</div>
												</div>

												<!-- Bottom Section -->
												<div
																class="bg-slate-50 border-t border-green-200 mt-8  px-6 md:px-12 flex flex-col md:flex-row justify-between items-center gap-6">
																<!-- Logo & Newsletter -->
																<div class="flex flex-col md:flex-row items-center gap-6">
																				<img src="{{ asset("images/ncc-logo.png") }}" alt="NCC Logo" class="h-16 w-auto">
																				<form class="flex gap-2">
																								<input type="email" placeholder="Enter your email to subscribe"
																												class="border border-green-300 placeholder:text-green-700 text-black rounded px-3 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-green-500">
																								<button type="submit"
																												class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Subscribe</button>
																				</form>
																</div>

																<!-- Helpline & Social -->
																<div class="flex flex-col items-center gap-4 md:flex-row md:items-center md:justify-end md:text-right">
																				<div>
																								<p class="font-semibold text-green-700">Need Help? Call Our Helpline</p>
																				</div>
																				<div>
																								<p class="text-base font-bold text-green-800">116</p>
																				</div>
																				<div class="flex items-center gap-4 text-green-700">
																								<a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
																								<a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
																								<a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
																								<a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
																				</div>
																</div>
												</div>
								</footer>

								@stack("scripts")
				</body>

</html>
