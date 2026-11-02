@extends("layouts.app")

@section("title", "Advertise")

@section("content")
				@php
								$suppressCmsBlocks = true;
								$hero = $cmsHero ?? null;
								$allCards = $cmsCards ?? collect();
								$cardsByCat = $allCards->groupBy(fn($item) => $item->category ?: "Content");
								$ctas = $cmsCtas ?? collect();
								// Hero data passed from PageController::advertise() (heroAds, heroStat, sponsoredAds).
								$heroAds = collect($heroAds ?? []);
								$heroStat = $heroStat ?? null;
								// Helpers for specific group lookups (fallback to "Opportunities" seeder group + new admin categories)
								$whyPartnerCards = $cardsByCat
								    ->get("Why Partner With NCC", collect())
								    ->merge($cardsByCat->get("Why Partner", collect()))
								    ->merge($cardsByCat->get("Partnership", collect()))
								    ->merge($cardsByCat->get('Why Partner With the National Children\'s Commission?', collect()));
								$sponsorshipCards = $cardsByCat
								    ->get("Sponsorship Opportunities", collect())
								    ->merge($cardsByCat->get("Opportunities", collect()));
								$sponsoredAds = $sponsoredAds
								    ?? $cardsByCat->get("Sponsored Ads", collect())->reject(function ($ad) {
								        $endsAt = data_get($ad->data, "ends_at");
								        return $endsAt && $endsAt < now()->toDateString();
								    })->values();

								// Build the hero carousel: slide 0 is the default hero, followed by up to 3
								// sponsored adverts an admin explicitly chose for the hero. Sponsored slides are
								// artwork-only — the campaign copy is baked into the image, so no text overlays it.
								$defaultHeroImage = asset("images/newsbanner.png");
								$adFallbackImage = asset("images/ad-promo-3.png");

								// CTA copy is CMS-driven, with a neutral fallback label.
								$heroCta = [
								    "text" => $hero?->button_text ?: "Start partnership",
								    "url" => $hero?->button_url ?: "#inquiry-form",
								];

								$heroSlides = collect([
								    [
								        "type" => "hero",
								        "image" => $hero?->image_url
								            ? $hero->image_url . "?v=" . $hero->updated_at->timestamp
								            : $defaultHeroImage,
								        "fallback" => $defaultHeroImage,
								        "alt" => $hero?->title,
								        "eyebrow" => $hero?->subtitle,
								        "title" => $hero?->title,
								        "description" => $hero?->description,
								    ],
								    ...$heroAds->map(fn($ad) => [
								        "type" => "ad",
								        "image" => $ad->image_url
								            ? $ad->image_url . "?v=" . $ad->updated_at->timestamp
								            : $adFallbackImage,
								        "fallback" => $adFallbackImage,
								        "alt" => $ad->title,
								        "eyebrow" => null,
								        "title" => null,
								        "description" => null,
								    ])->values(),
								]);
				@endphp
				<style>
								html {
												scroll-behavior: smooth
								}
				</style>

				{{-- Hero carousel — default hero image, then up to 3 admin-chosen sponsored ads --}}
				@if ($heroSlides->isNotEmpty())
				<section
								x-data="{
												active: 0,
												total: {{ $heroSlides->count() }},
												paused: false,
												timer: null,
												reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
												init() {
																if (!this.reduced) this.start();
												},
												start() {
																if (this.total < 2 || this.reduced) return;
																this.stop();
																this.timer = setInterval(() => { if (!this.paused) this.next(); }, 6000);
												},
												stop() {
																if (this.timer) { clearInterval(this.timer); this.timer = null; }
												},
												next() { this.active = (this.active + 1) % this.total; },
												prev() { this.active = (this.active - 1 + this.total) % this.total; }
								}"
								@mouseenter="paused = true" @mouseleave="paused = false"
								@focusin="paused = true" @focusout="paused = false"
								class="relative min-h-[80vh] w-full overflow-hidden bg-slate-900"
								role="region" aria-roledescription="carousel" aria-label="Advertise with NCC"
				>
								@foreach ($heroSlides as $index => $slide)
												<div
																class="absolute inset-0 transition-opacity duration-700 ease-out motion-reduce:transition-none"
																:class="active === {{ $index }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
																:style="reduced ? 'transition-duration: 0ms' : ''"
																:aria-hidden="active === {{ $index }} ? 'false' : 'true'"
																role="group" aria-roledescription="slide"
																aria-label="{{ $index + 1 }} of {{ $heroSlides->count() }}"
												>
																<img src="{{ $slide['image'] }}" alt="{{ $slide['alt'] ?? '' }}"
																				class="absolute inset-0 h-full w-full object-cover"
																				loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
																				onerror="this.onerror=null; this.src='{{ $slide['fallback'] }}'">
																<div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/55 to-black/40"></div>

																<div class="relative z-10 flex h-full min-h-[80vh] flex-col items-center justify-center gap-6 px-6 pb-24 pt-16 text-center text-white">
																				@if (!empty($slide['eyebrow']))
																								<p class="text-xs font-bold uppercase tracking-[0.2em] text-yellow-400 sm:text-sm">{{ $slide['eyebrow'] }}</p>
																				@endif
																				@if (!empty($slide['title']))
																								<h1 class="max-w-4xl text-3xl font-bold sm:text-4xl md:text-5xl">{{ $slide['title'] }}</h1>
																				@endif
																				@if (!empty($slide['description']))
																								<p class="max-w-2xl text-base leading-relaxed sm:text-lg">{{ $slide['description'] }}</p>
																				@endif

																				{{-- Slide 0 only: the "Partners" start card beside the CTA. --}}
																				@if ($index === 0)
																								<div class="mt-2 flex flex-col items-center gap-4 sm:flex-col sm:justify-center">
																												@if ($heroStat && ($heroStat['label'] ?? '') !== '')
																																<div class="reveal"
																																x-data="{
																																				target: {{ (int) ($heroStat['value'] ?? 0) }},
																																				suffix: @js($heroStat['suffix'] ?? ''),
																																				display: 0,
																																				init() {
																																								this.display = this.target;
																																								if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
																																								this.display = 0;
																																								const obs = new IntersectionObserver((entries) => {
																																								entries.forEach((e) => { if (e.isIntersecting) { obs.disconnect(); this.run(); } });
																																								}, { threshold: 0.4 });
																																								obs.observe(this.$el);
																																				},
																																				run() {
																																								const start = performance.now();
																																								const duration = 1300;
																																								const tick = (now) => {
																																								const p = Math.min(1, (now - start) / duration);
																																								const eased = 1 - Math.pow(1 - p, 3);
																																								this.display = Math.round(this.target * eased);
																																								if (p < 1) requestAnimationFrame(tick); else this.display = this.target;
																																								};
																																								requestAnimationFrame(tick);
																																				}
																																}"
																																class="flex min-w-[15rem] items-center gap-4 rounded-xl border border-white/20 bg-white/10 p-5 text-left shadow-sm backdrop-blur"
																																>
																																<span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/15 text-white">
																																<i class="fas {{ $heroStat['icon'] }} text-xl" aria-hidden="true"></i>
																																</span>
																																<div>
																																<p class="text-3xl font-bold tabular-nums">
																																<span x-text="display.toLocaleString() + suffix">{{ number_format($heroStat['value'] ?? 0) }}{{ $heroStat['suffix'] ?? '' }}</span>
																																</p>
																																<p class="mt-0.5 text-sm font-medium text-white/80">{{ $heroStat['label'] }}</p>
																																</div>
																																</div>
																												@endif
																												<a href="{{ $heroCta['url'] }}"
																												class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 font-semibold text-white shadow transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-transparent">
																																{{ $heroCta['text'] }}
																												</a>
																								</div>
																				@else
																								<a href="{{ $heroCta['url'] }}"
																								class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 font-semibold text-white shadow transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-white">
																																{{ $heroCta['text'] }}
																								</a>
																				@endif
																</div>
												</div>
								@endforeach

								@if ($heroSlides->count() > 1)
												<button type="button" @click="prev()" aria-label="Previous slide"
																class="absolute left-4 top-1/2 z-50 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-white transition hover:bg-black/80 focus:outline-none focus:ring-2 focus:ring-white">
																<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
																				<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
																</svg>
												</button>
												<button type="button" @click="next()" aria-label="Next slide"
																class="absolute right-4 top-1/2 z-50 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-white transition hover:bg-black/80 focus:outline-none focus:ring-2 focus:ring-white">
																<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
																				<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
																</svg>
												</button>

												<div class="absolute bottom-6 left-1/2 z-50 flex -translate-x-1/2 gap-2">
																@foreach ($heroSlides as $index => $slide)
																				<button type="button" @click="active = {{ $index }}"
																:aria-current="active === {{ $index }} ? 'true' : 'false'"
																aria-label="Go to slide {{ $index + 1 }}"
																:class="active === {{ $index }} ? 'w-8 bg-white' : 'w-3 bg-white/70 hover:bg-white'"
																class="h-3 rounded-full transition-all duration-300 focus:outline-none shadow"></button>
																@endforeach
												</div>
								@endif
				</section>
				@endif

				{{-- Why Partner With NCC — CMS cards group "Why Partner" else fallback static 3 --}}
				<section class="w-full bg-white py-16 px-6 md:px-12">
								<div class="max-w-6xl mx-auto text-center">
												<h2 class="text-3xl md:text-4xl font-bold text-green-700 mb-6">Why Partner With the National Children's
																Commission?</h2>
												<p class="text-gray-700 max-w-3xl mx-auto mb-12">
																Partnering with NCC means aligning your brand with a mission that protects and empowers children across
																Malawi.
																Together, we create sustainable change through education, advocacy, and community engagement.
												</p>
												<div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
																@if ($whyPartnerCards->isNotEmpty())
																				@foreach ($whyPartnerCards->take(3) as $card)
																								<div class="bg-gray-50 rounded-lg shadow p-6 hover:shadow-lg transition">
																												@if ($card->image_url)
																																<img src="{{ $card->image_url }}?v={{ $card->updated_at->timestamp }}" alt=""
																																				class="w-full h-40 object-cover rounded mb-4">
																												@else
																																<i class="fas fa-hand-holding-heart text-green-600 text-3xl mb-4 block"></i>
																												@endif
																												<h3 class="text-xl font-semibold text-gray-800 mb-2">{{ $card->title }}</h3>
																												@if ($card->description)
																																<p class="text-gray-600 text-sm leading-6">{{ $card->description }}</p>
																												@endif
																												@if ($card->button_text && $card->button_link)
																																<a href="{{ $card->button_link }}"
																																				class="mt-3 inline-flex text-sm font-semibold text-green-700 hover:underline">{{ $card->button_text }}</a>
																												@endif
																								</div>
																				@endforeach
																@else
																				<div class="bg-gray-50 rounded-lg shadow p-6 hover:shadow-lg transition">
																								<i class="fas fa-hand-holding-heart text-green-600 text-3xl mb-4"></i>
																								<h3 class="text-xl font-semibold text-gray-800 mb-2">Social Impact</h3>
																								<p class="text-gray-600">Your sponsorship directly supports programs that improve child welfare and
																												community resilience.</p>
																				</div>
																				<div class="bg-gray-50 rounded-lg shadow p-6 hover:shadow-lg transition">
																								<i class="fas fa-users text-green-600 text-3xl mb-4"></i>
																								<h3 class="text-xl font-semibold text-gray-800 mb-2">Community Reach</h3>
																								<p class="text-gray-600">NCC's initiatives span 28 districts, connecting your brand with thousands
																												of families and local leaders.</p>
																				</div>
																				<div class="bg-gray-50 rounded-lg shadow p-6 hover:shadow-lg transition">
																								<i class="fas fa-shield-alt text-green-600 text-3xl mb-4"></i>
																								<h3 class="text-xl font-semibold text-gray-800 mb-2">Trusted Partnership</h3>
																								<p class="text-gray-600">We uphold strict ethical standards and transparency, ensuring every
																												collaboration reflects shared values.</p>
																				</div>
																@endif
												</div>
								</div>
				</section>

				<div class="flex justify-center my-6 px-6 md:px-12">
								<hr class="w-full max-w-6xl border-t-2 border-green-600">
				</div>

				{{-- Sponsorship Opportunities — CMS group "Sponsorship Opportunities" / "Opportunities" else fallback --}}
				<section class="w-full bg-white py-16 px-6 md:px-12">
								<div class="max-w-6xl mx-auto text-center">
												<h2 class="text-3xl md:text-4xl font-bold text-green-700 mb-6">Sponsorship Opportunities</h2>
												<p class="text-gray-700 max-w-3xl mx-auto mb-12">
																Choose from a range of advertising formats designed to maximize your brand's visibility while supporting
																child protection initiatives.
												</p>
												<div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
																@if ($sponsorshipCards->isNotEmpty())
																				@foreach ($sponsorshipCards->take(6) as $card)
																								<div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition border border-slate-100">
																												@if ($card->image_url)
																																<img src="{{ $card->image_url }}?v={{ $card->updated_at->timestamp }}" alt=""
																																				class="w-full h-40 object-cover rounded mb-4">
																												@else
																																<i class="fas fa-bullhorn text-green-600 text-3xl mb-4 block"></i>
																												@endif
																												<h3 class="text-xl font-semibold text-gray-800 mb-2">{{ $card->title }}</h3>
																												@if ($card->subtitle)
																																<p class="text-xs font-semibold uppercase text-green-700 mb-2">{{ $card->subtitle }}</p>
																												@endif
																												@if ($card->description)
																																<p class="text-gray-600 text-sm leading-6">{{ $card->description }}</p>
																												@endif
																												@if ($card->button_text && $card->button_link)
																																<a href="{{ $card->button_link }}"
																																				class="mt-3 inline-flex rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">{{ $card->button_text }}</a>
																												@endif
																								</div>
																				@endforeach
																@else
																				<div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition border border-slate-50">
																								<i class="fas fa-desktop text-green-600 text-3xl mb-4"></i>
																								<h3 class="text-xl font-semibold text-gray-800 mb-2">Digital Display</h3>
																								<p class="text-gray-600">Place your brand on select NCC news and article pages with banner ads that
																												reach engaged readers.</p>
																				</div>
																				<div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition border border-slate-50">
																								<i class="fas fa-envelope text-green-600 text-3xl mb-4"></i>
																								<h3 class="text-xl font-semibold text-gray-800 mb-2">Newsletter Sponsorship</h3>
																								<p class="text-gray-600">Feature your brand in our monthly stakeholder emails sent to partners,
																												families, and community leaders.</p>
																				</div>
																				<div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition border border-slate-50">
																								<i class="fas fa-users text-green-600 text-3xl mb-4"></i>
																								<h3 class="text-xl font-semibold text-gray-800 mb-2">Event & Campaign Sponsorship</h3>
																								<p class="text-gray-600">Gain visibility during major events such as the Day of the African Child
																												and Day of Families.</p>
																				</div>
																@endif
												</div>
								</div>
				</section>

				<div class="flex justify-center my-6 px-6 md:px-12">
								<hr class="w-full max-w-6xl border-t-2 border-green-600">
				</div>

				{{-- Inquiry + Ethical Guidelines — side-by-side on desktop (form left, guidelines right sticky) like reporting page grid --}}
				@php $guidelineCards = $cardsByCat->get('Guidelines', collect())->merge($cardsByCat->get('Ethical & Safeguarding Guidelines', collect())); @endphp
				<section class="bg-white px-4 py-12 sm:px-6 lg:px-8" id="inquiry-form">
								<div class="mx-auto max-w-7xl">
												<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_28rem] lg:items-start">
                {{-- Left: Inquiry / Application Form — wired to backend + dynamic --}}
                <div x-data="{
                    tab: 'campaign',
                    company: '{{ old('company') }}',
                    industry: '{{ old('industry') }}',
                    objectives: `{{ old('objectives') }}`,
                    start_date: '{{ old('start_date') }}',
                    budget: '{{ old('budget') }}',
                    contact_name: '{{ old('contact_name') }}',
                    contact_email: '{{ old('contact_email') }}',
                    contact_phone: '{{ old('contact_phone') }}',
                    org_address: `{{ old('org_address') }}`,
                    preferred_contact: '{{ old('preferred_contact') }}',
                    agreement: false,
                    submitting: false,
                    success: false,
                    reference: '',
                    previewUrl: '',
                    previewName: '',
                    errors: {},
                    async submit($event) {
                        this.submitting = true; this.errors = {};
                        const form = $event.target;
                        const data = new FormData(form);
                        if (!this.agreement) data.set('agreement','0'); else data.set('agreement','1');
                        try {
                            const res = await fetch('{{ route('advertise.inquire') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                body: data
                            });
                            const contentType = res.headers.get('content-type') || '';
                            if (!contentType.includes('application/json')) {
                                this.errors = { _form: ['The server returned an unexpected response (status ' + res.status + '). Please try again.'] };
                                this.submitting = false; return;
                            }
                            const json = await res.json();
                            if (!res.ok) {
                                if (json.errors) {
                                    this.errors = json.errors;
                                    const hasContact = ['contact_name','contact_email','contact_phone','org_address','preferred_contact','agreement'].some(k=>json.errors[k]);
                                    if(hasContact) this.tab='contact';
                                }
                                else this.errors = { _form: [json.message || 'Submission failed.'] };
                                this.submitting = false; return;
                            }
                            this.success = true; this.reference = json.reference || '';
                            this.errors = {}; this.tab='campaign';
                            form.reset(); this.previewUrl=''; this.previewName=''; this.agreement=false;
                            this.company='';this.industry='';this.objectives='';this.start_date='';this.budget='';this.contact_name='';this.contact_email='';this.contact_phone='';this.org_address='';this.preferred_contact='';
                        } catch(e) {
                            this.errors = { _form: ['Network error. Please try again.'] };
                        }
                        this.submitting = false;
                    },
                    onFile(e){
                        const f = e.target.files[0];
                        if(!f){ this.previewUrl=''; this.previewName=''; return; }
                        this.previewName = f.name + ' (' + Math.round(f.size/1024) + ' KB)';
                        if(f.type.startsWith('image/')) this.previewUrl = URL.createObjectURL(f); else this.previewUrl='';
                    }
                }">
                    <h2 class="text-3xl md:text-4xl font-bold text-green-700 mb-4">Submit Your Advertising Inquiry</h2>
                    <p class="text-gray-700 max-w-2xl mb-6">Complete the form below to apply for partnership. All submissions are reviewed by the NCC board to ensure compliance with our safeguarding standards.</p>

                    @if (session('inquiry_status'))
                        <div class="mb-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                            {{ session('inquiry_status') }}
                            @if (session('inquiry_reference'))
                                <strong class="ml-1">{{ session('inquiry_reference') }}</strong>
                            @endif
                        </div>
                    @endif
                    <div x-show="success" class="mb-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800" x-cloak>
                        <span x-text="'Inquiry received — reference ' + reference + '. NCC board will review within 5–7 days.'"></span>
                    </div>
                    <div x-show="errors._form" class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700" x-cloak>
                        <template x-for="msg in (errors._form || [])" :key="msg"><p x-text="msg"></p></template>
                    </div>

                    {{-- Tabs like reporting.blade.php:30 --}}
                    <div class="mb-6 flex flex-wrap gap-3">
                        <button type="button" @click="tab='campaign'" :class="tab==='campaign' ? 'bg-green-700 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:border-green-700 hover:text-green-700'" class="rounded-md px-4 py-2 text-sm font-semibold transition">Campaign Details</button>
                        <button type="button" @click="tab='contact'" :class="tab==='contact' ? 'bg-green-700 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:border-green-700 hover:text-green-700'" class="rounded-md px-4 py-2 text-sm font-semibold transition">Contact Details</button>
                    </div>

                    <form @submit.prevent="submit($event)" action="{{ route('advertise.inquire') }}" method="POST" enctype="multipart/form-data"
                        class="bg-white rounded-lg border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                        @csrf
                        {{-- Campaign Details Tab --}}
                        <div x-show="tab==='campaign'" x-transition class="space-y-6">
                        <div>
                            <label for="company" class="block text-sm font-semibold text-slate-800">Company / Organization Name</label>
                            <input type="text" id="company" name="company" x-model="company" required
                                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" :class="errors.company ? 'border-red-400' : ''">
                            <template x-if="errors.company"><p class="mt-1 text-sm text-red-600" x-text="errors.company[0]"></p></template>
                            @error('company')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="industry" class="block text-sm font-semibold text-slate-800">Industry / Sector</label>
                            <input type="text" id="industry" name="industry" x-model="industry" required
                                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" :class="errors.industry ? 'border-red-400' : ''">
                            <template x-if="errors.industry"><p class="mt-1 text-sm text-red-600" x-text="errors.industry[0]"></p></template>
                            @error('industry')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="objectives" class="block text-sm font-semibold text-slate-800">Campaign Objectives</label>
                            <textarea id="objectives" name="objectives" x-model="objectives" rows="4" required
                                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" :class="errors.objectives ? 'border-red-400' : ''"></textarea>
                            <p class="mt-1 text-xs text-slate-500" x-text="objectives.length + '/2000'"></p>
                            <template x-if="errors.objectives"><p class="mt-1 text-sm text-red-600" x-text="errors.objectives[0]"></p></template>
                            @error('objectives')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="start_date" class="block text-sm font-semibold text-slate-800">Target Start Date</label>
                            <input type="date" id="start_date" name="start_date" x-model="start_date" required
                                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" :class="errors.start_date ? 'border-red-400' : ''">
                            <template x-if="errors.start_date"><p class="mt-1 text-sm text-red-600" x-text="errors.start_date[0]"></p></template>
                            @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="budget" class="block text-sm font-semibold text-slate-800">Budget Scope</label>
                            <input type="text" id="budget" name="budget" x-model="budget" placeholder="e.g. MWK 500,000"
                                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" :class="errors.budget ? 'border-red-400' : ''">
                            <template x-if="errors.budget"><p class="mt-1 text-sm text-red-600" x-text="errors.budget[0]"></p></template>
                            @error('budget')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="artwork" class="block text-sm font-semibold text-slate-800">Upload Proposed Ad Artwork</label>
                            <input type="file" id="artwork" name="artwork" accept="image/*,application/pdf" @change="onFile($event)"
                                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-sm file:font-semibold file:text-slate-700" :class="errors.artwork ? 'border-red-400' : ''">
                            <template x-if="previewName"><p class="mt-2 text-xs font-semibold text-green-700" x-text="previewName"></p></template>
                            <template x-if="previewUrl"><img :src="previewUrl" alt="Preview" class="mt-2 h-24 w-auto rounded border"></template>
                            <template x-if="errors.artwork"><p class="mt-1 text-sm text-red-600" x-text="errors.artwork[0]"></p></template>
                            @error('artwork')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            <p class="mt-1 text-xs text-slate-500">JPG/PNG/WEBP/PDF, max 5 MB.</p>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" @click="tab='contact'" class="rounded-md border border-green-600 px-5 py-2 text-sm font-semibold text-green-700 hover:bg-green-50">Next: Contact Details →</button>
                        </div>
                        </div>

                        {{-- Contact Details Tab --}}
                        <div x-show="tab==='contact'" x-cloak x-transition class="space-y-6">
                            <p class="text-sm text-slate-500">Provide a reachable contact for NCC board follow-up within 5–7 days.</p>
                            <div>
                                <label for="contact_name" class="block text-sm font-semibold text-slate-800">Contact Person Name</label>
                                <input type="text" id="contact_name" name="contact_name" x-model="contact_name" required
                                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" :class="errors.contact_name ? 'border-red-400' : ''">
                                <template x-if="errors.contact_name"><p class="mt-1 text-sm text-red-600" x-text="errors.contact_name[0]"></p></template>
                                @error('contact_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="contact_email" class="block text-sm font-semibold text-slate-800">Email Address</label>
                                    <input type="email" id="contact_email" name="contact_email" x-model="contact_email" required
                                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" :class="errors.contact_email ? 'border-red-400' : ''">
                                    <template x-if="errors.contact_email"><p class="mt-1 text-sm text-red-600" x-text="errors.contact_email[0]"></p></template>
                                    @error('contact_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="contact_phone" class="block text-sm font-semibold text-slate-800">Phone Number</label>
                                    <input type="tel" id="contact_phone" name="contact_phone" x-model="contact_phone" placeholder="+265 ..." required
                                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" :class="errors.contact_phone ? 'border-red-400' : ''">
                                    <template x-if="errors.contact_phone"><p class="mt-1 text-sm text-red-600" x-text="errors.contact_phone[0]"></p></template>
                                    @error('contact_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <label for="org_address" class="block text-sm font-semibold text-slate-800">Organization Address</label>
                                <input type="text" id="org_address" name="org_address" x-model="org_address" placeholder="e.g. Area 47, Lilongwe"
                                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" :class="errors.org_address ? 'border-red-400' : ''">
                                <template x-if="errors.org_address"><p class="mt-1 text-sm text-red-600" x-text="errors.org_address[0]"></p></template>
                            </div>
                            <div>
                                <label for="preferred_contact" class="block text-sm font-semibold text-slate-800">Preferred Contact Method</label>
                                <select id="preferred_contact" name="preferred_contact" x-model="preferred_contact" required
                                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" :class="errors.preferred_contact ? 'border-red-400' : ''">
                                    <option value="">Select method</option>
                                    <option>Email</option>
                                    <option>Phone</option>
                                    <option>WhatsApp</option>
                                </select>
                                <template x-if="errors.preferred_contact"><p class="mt-1 text-sm text-red-600" x-text="errors.preferred_contact[0]"></p></template>
                                @error('preferred_contact')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="agreement" name="agreement" x-model="agreement" value="1" required
                                    class="h-5 w-5 rounded border-slate-300 text-green-600 focus:ring-green-600">
                                <label for="agreement" class="text-sm text-slate-700">I confirm my campaign complies with NCC's safeguarding guidelines.</label>
                            </div>
                            <template x-if="errors.agreement"><p class="text-sm text-red-600" x-text="errors.agreement[0]"></p></template>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <button type="button" @click="tab='campaign'" class="rounded-md border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Back to Campaign</button>
                                <button type="submit" :disabled="submitting || !agreement"
                                    class="rounded-md bg-green-600 px-6 py-2 font-semibold text-white transition hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed" x-text="submitting ? 'Submitting...' : 'Submit Inquiry'"></button>
                            </div>
                            <p class="text-sm text-gray-500 text-center">Reviewed by NCC board within 5–7 days.</p>
                        </div>
                    </form>
                </div>

																{{-- Right: Ethical & Safeguarding Guidelines (sticky on desktop like reporting Instructions) --}}
																<aside id="ethical-guidelines"
																				class="rounded-lg border border-slate-200 bg-red-50 p-5 lg:sticky lg:top-24">
																				<h2 class="text-xl font-bold text-red-700">Ethical & Safeguarding Guidelines</h2>
																				<p class="mt-2 text-sm leading-6 text-gray-700">NCC is committed to protecting children and ensuring
																								that all advertising partnerships align with our values of safety, education, and empowerment.</p>
																				@if ($guidelineCards->isNotEmpty())
																								<div class="mt-4 space-y-3">
																												@foreach ($guidelineCards as $g)
																																<div class="rounded-lg bg-white p-4 shadow-sm border-l-4 border-red-600">
																																				<h3 class="font-semibold text-gray-800 text-sm">{{ $g->title }}</h3>
																																				<p class="mt-1 text-sm leading-6 text-gray-600">{{ $g->description }}</p>
																																</div>
																												@endforeach
																								</div>
																				@else
																								<div class="mt-4 rounded-lg bg-white p-5 shadow-sm border-l-4 border-red-600">
																												<ul class="space-y-3 text-sm text-gray-800">
																																<li class="flex items-start"><i
																																								class="fas fa-ban text-red-600 mr-3 mt-1"></i><span>Prohibited ads: alcohol,
																																								tobacco, gambling, unhealthy foods, or any product harmful to children.</span></li>
																																<li class="flex items-start"><i
																																								class="fas fa-check-circle text-green-600 mr-3 mt-1"></i><span>All ads undergo NCC
																																								board review prior to publication.</span></li>
																																<li class="flex items-start"><i
																																								class="fas fa-shield-alt text-green-600 mr-3 mt-1"></i><span>Advertisers must
																																								comply with strict safeguarding standards and ethical criteria.</span></li>
																																<li class="flex items-start"><i class="fas fa-heart text-green-600 mr-3 mt-1"></i><span>We
																																								welcome brands that share our mission of protecting and empowering children.</span>
																																</li>
																												</ul>
																								</div>
																				@endif
																</aside>
												</div>
								</div>
				</section>

				{{-- Sponsored Ads (CMS-driven carousel, shared with /latest-news) --}}
				<x-sponsored-ads-carousel :ads="$sponsoredAds" />

				{{-- Fallback generic CMS groups not covered above (e.g. admin-added custom categories) --}}
				@php
								$renderedKeys = collect([
								    "Why Partner With NCC",
								    "Why Partner",
								    "Partnership",
								    'Why Partner With the National Children\'s Commission?',
								    "Sponsorship Opportunities",
								    "Opportunities",
								    "Sponsored Ads",
								    "Guidelines",
								    "Ethical & Safeguarding Guidelines",
								    "Content"
								]);
								$remaining = $cardsByCat->reject(fn($v, $k) => $renderedKeys->contains($k) || $k === "Opportunities");
				@endphp
				@foreach ($remaining as $group => $items)
								<section class="bg-white px-4 py-10 sm:px-6 lg:px-8">
												<div class="mx-auto max-w-7xl">
																<div class="mb-6 flex items-center gap-3"><span class="h-1 w-10 rounded-full bg-red-700"></span>
																				<h2 class="text-2xl font-bold text-gray-950">{{ $group }}</h2>
																</div>
																<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
																				@foreach ($items as $card)
																								<article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
																												@if ($card->image_url)
																																<img src="{{ $card->image_url }}?v={{ $card->updated_at->timestamp }}" alt=""
																																				class="h-56 w-full object-cover">
																												@endif
																												<div class="p-5">
																																@if ($card->subtitle)
																																				<p class="text-xs font-semibold uppercase text-green-700">{{ $card->subtitle }}</p>
																																@endif
																																<h3 class="mt-1 text-lg font-bold text-gray-950">{{ $card->title }}</h3>
																																@if ($card->description)
																																				<p class="mt-3 text-sm leading-6 text-gray-600">{{ $card->description }}</p>
																																@endif
																																@if ($card->button_text && $card->button_link)
																																				<a href="{{ $card->button_link }}"
																																								class="mt-4 inline-flex rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">{{ $card->button_text }}</a>
																																@endif
																												</div>
																								</article>
																				@endforeach
																</div>
												</div>
								</section>
				@endforeach

				@if ($ctas->isNotEmpty())
								<section class="bg-white px-4 py-10 sm:px-6 lg:px-8">
												<div class="mx-auto max-w-5xl rounded-lg bg-green-700 px-6 py-10 text-center text-white">
																@foreach ($ctas as $cta)
																				<h2 class="text-2xl font-bold">{{ $cta->title }}</h2>
																				@if ($cta->description)
																								<p class="mx-auto mt-3 max-w-3xl">{{ $cta->description }}</p>
																				@endif
																				@if ($cta->button_text && $cta->button_link)
																								<a href="{{ $cta->button_link }}"
																												class="mt-5 inline-flex rounded-md bg-red-600 px-5 py-3 font-semibold text-white hover:bg-red-700">{{ $cta->button_text }}</a>
																				@endif
																@endforeach
												</div>
								</section>
				@endif
@endsection
