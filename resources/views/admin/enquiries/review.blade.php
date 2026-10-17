@extends('admin.layouts.app')

@php
    $enquiryData = $enquiry->data ?? [];
    $status = $enquiry->enquiry_status;
    $reference = $enquiryData['reference_number'] ?? ('#' . $enquiry->id);

    $pills = [
        'pending_review' => ['label' => 'Pending review', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
        'approved' => ['label' => 'Approved', 'class' => 'border-green-200 bg-green-50 text-green-700'],
        'expired' => ['label' => 'Expired', 'class' => 'border-slate-200 bg-slate-100 text-slate-600'],
        'rejected' => ['label' => 'Rejected', 'class' => 'border-red-200 bg-red-50 text-red-700'],
    ];
    $pill = $pills[$status] ?? $pills['pending_review'];

    $artwork = is_array($enquiryData['artwork'] ?? null) ? $enquiryData['artwork'] : null;
    $artworkIsImage = $artwork && str_starts_with((string) ($artwork['mime_type'] ?? ''), 'image/');
    $artworkUrl = $artwork['url'] ?? null;
    if (! $artworkUrl && ($artwork['path'] ?? null)) {
        $artworkUrl = $artworkIsImage || str_starts_with((string) $artwork['path'], 'advertise-inquiries/')
            ? asset('storage/' . $artwork['path'])
            : null;
    }

    $submittedAt = filled($enquiryData['submitted_at'] ?? null)
        ? \Illuminate\Support\Carbon::parse($enquiryData['submitted_at'])
        : $enquiry->created_at;

    $currentEndsAt = $ad?->data['ends_at'] ?? ($enquiryData['ends_at'] ?? null);
    $prefillTitle = $ad?->title ?: ($enquiryData['company'] ?? $enquiry->title);
    $prefillSubtitle = $ad?->subtitle ?: ($enquiryData['industry'] ?? $enquiry->subtitle);
    $prefillBlurb = $ad?->description ?: \Illuminate\Support\Str::limit($enquiryData['objectives'] ?? $enquiry->description, 220);
    $defaultMode = old('_mode', $ad ? 'renew' : 'approve');
    $defaultPublishMode = old('publish_mode', $ad ? ($ad->is_published ? 'published' : 'draft') : 'published');
    $renewBase = \Illuminate\Support\Carbon::parse($currentEndsAt ?: now()->toDateString());
    $renewDefault = ($renewBase->isPast() ? now() : $renewBase)->addDays(30)->toDateString();
@endphp

@section('title', 'Enquiry ' . $reference)
@section('heading', 'Advert enquiry ' . $reference)
@section('subheading', ($enquiryData['company'] ?? $enquiry->title) . ' · ' . ($enquiryData['industry'] ?? $enquiry->subtitle))

@section('content')
    <div class="mb-5 flex flex-col gap-3" x-data="{ mode: @js($defaultMode) }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries']) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-green-700">
                <span aria-hidden="true">&larr;</span> Back to enquiries board
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $pill['class'] }}">{{ $pill['label'] }}</span>
                @if ($submittedAt)
                    <span class="text-xs text-slate-500">Received {{ $submittedAt->format('j M Y, g:ia') }}</span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            @foreach ($reviewStates as $stateKey => $stateCount)
                @if ($stateKey === 'all')
                    @continue
                @endif
                <a href="{{ route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries', 'review' => $stateKey]) }}"
                   class="rounded-full border border-slate-200 bg-white px-3 py-1 font-semibold text-slate-600 hover:border-green-600 hover:text-green-700">
                    {{ $stateCount }} {{ \App\Support\AdEnquiries::LABELS[$stateKey] ?? \Illuminate\Support\Str::headline($stateKey) }}
                </a>
            @endforeach
        </div>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div class="space-y-5">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Campaign brief</h2>
                    <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Organisation</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ $enquiryData['company'] ?? $enquiry->title }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Industry</dt>
                            <dd class="mt-1 text-sm text-slate-700">{{ $enquiryData['industry'] ?? $enquiry->subtitle ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Preferred start</dt>
                            <dd class="mt-1 text-sm text-slate-700">
                                {{ filled($enquiryData['start_date'] ?? null) ? \Illuminate\Support\Carbon::parse($enquiryData['start_date'])->format('j M Y') : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Budget</dt>
                            <dd class="mt-1 text-sm text-slate-700">{{ $enquiryData['budget'] ?? 'Not stated' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Objectives</dt>
                            <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $enquiryData['objectives'] ?? $enquiry->description ?: '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-lg border border-amber-200 bg-amber-50/50 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-base font-semibold text-slate-950">Private contact details</h2>
                        <span class="rounded-full border border-amber-300 bg-white px-2 py-0.5 text-[11px] font-semibold text-amber-700">Never shown publicly</span>
                    </div>
                    <dl class="mt-4 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contact name</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $enquiryData['contact_name'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Preferred channel</dt>
                            <dd class="mt-1 text-sm text-slate-800">{{ $enquiryData['preferred_contact'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</dt>
                            <dd class="mt-1 text-sm">
                                @if (filled($enquiryData['contact_email'] ?? null))
                                    <a href="mailto:{{ $enquiryData['contact_email'] }}" class="font-medium text-green-700 hover:underline">{{ $enquiryData['contact_email'] }}</a>
                                @else
                                    <span class="text-slate-600">—</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</dt>
                            <dd class="mt-1 text-sm">
                                @if (filled($enquiryData['contact_phone'] ?? null))
                                    <a href="tel:{{ $enquiryData['contact_phone'] }}" class="font-medium text-green-700 hover:underline">{{ $enquiryData['contact_phone'] }}</a>
                                @else
                                    <span class="text-slate-600">—</span>
                                @endif
                            </dd>
                        </div>
                        @if (filled($enquiryData['org_address'] ?? null))
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Address</dt>
                                <dd class="mt-1 text-sm text-slate-800">{{ $enquiryData['org_address'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Submitted artwork</h2>
                    @if ($artwork)
                        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
                            @if ($artworkIsImage && $artworkUrl)
                                <img src="{{ $artworkUrl }}" alt="{{ $artwork['original_name'] ?? 'Submitted artwork' }}" class="h-40 w-full rounded-md border border-slate-200 object-cover sm:w-64">
                            @else
                                <div class="flex h-40 w-full items-center justify-center rounded-md border border-dashed border-slate-300 bg-slate-50 px-4 text-center text-xs font-semibold text-slate-500 sm:w-64">
                                    {{ strtoupper(pathinfo($artwork['original_name'] ?? '', PATHINFO_EXTENSION) ?: 'file') }} preview not available
                                </div>
                            @endif
                            <div class="min-w-0 text-sm">
                                <p class="font-semibold text-slate-900 break-all">{{ $artwork['original_name'] ?? 'Artwork file' }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $artwork['mime_type'] ?? 'unknown type' }}
                                    @if (! empty($artwork['size']))
                                        · {{ round($artwork['size'] / 1024) }} KB
                                    @endif
                                </p>
                                @if ($artworkUrl)
                                    <a href="{{ $artworkUrl }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">Open file</a>
                                @endif
                                @if (! $artworkIsImage)
                                    <p class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                        This is not an image, so the sponsored card will be created without creative. Attach artwork on the card editor before publishing.
                                    </p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="mt-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">No artwork was submitted. You can still approve the enquiry and add creative from the card editor.</p>
                    @endif
                </section>

                @if ($ad)
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-base font-semibold text-slate-950">Linked sponsored advert</h2>
                            <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $status === 'expired' ? 'border-slate-300 bg-slate-100 text-slate-600' : ($ad->is_published ? 'border-green-200 bg-green-50 text-green-700' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
                                {{ $status === 'expired' ? 'Expired' : ($ad->is_published ? 'Published' : 'Draft') }}
                            </span>
                        </div>
                        <div class="mt-4 flex flex-col gap-4 sm:flex-row">
                            @if ($ad->image_url)
                                <img src="{{ $ad->image_url }}?v={{ $ad->updated_at->timestamp }}" alt="{{ $ad->alt_text ?: $ad->title }}" class="h-32 w-full rounded-md border border-slate-200 object-cover sm:w-52">
                            @endif
                            <div class="min-w-0 text-sm">
                                <p class="font-semibold text-slate-900">{{ $ad->title }}</p>
                                @if ($ad->subtitle)
                                    <p class="mt-1 text-xs text-slate-500">{{ $ad->subtitle }}</p>
                                @endif
                                @if ($ad->description)
                                    <p class="mt-2 text-xs leading-5 text-slate-600">{{ \Illuminate\Support\Str::limit($ad->description, 180) }}</p>
                                @endif
                                <p class="mt-2 text-xs text-slate-500">
                                    Runs until
                                    <span class="font-semibold {{ $status === 'expired' ? 'text-red-700' : 'text-slate-700' }}">
                                        {{ $currentEndsAt ? \Illuminate\Support\Carbon::parse($currentEndsAt)->format('j M Y') : 'no end date' }}
                                    </span>
                                </p>
                                <div class="mt-3 flex flex-wrap gap-3 text-xs font-semibold">
                                    <a href="{{ route('admin.contents.edit', ['section' => 'advertise', 'id' => $ad->id]) }}" class="text-green-700 hover:underline">Edit card</a>
                                    <a href="{{ route('admin.contents.preview', ['section' => 'advertise', 'id' => $ad->id]) }}" class="text-blue-700 hover:underline">Preview</a>
                                    <a href="{{ route('advertise') }}" target="_blank" rel="noopener" class="text-slate-600 hover:underline">View board</a>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                @if (! empty($enquiryData['reviewed_at']))
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">Review history</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ \Illuminate\Support\Str::headline($status) }} by {{ $enquiryData['reviewed_by_name'] ?? 'an administrator' }}
                            on {{ \Illuminate\Support\Carbon::parse($enquiryData['reviewed_at'])->format('j M Y, g:ia') }}.
                        </p>
                        @if (! empty($enquiryData['rejection_reason']))
                            <p class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ $enquiryData['rejection_reason'] }}</p>
                        @endif
                    </section>
                @endif
            </div>

            <aside class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-6">
                <h2 class="text-base font-semibold text-slate-950">Board decision</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">Approving creates a Sponsored Ads card on the public /advertise board. Contact details stay private.</p>

                <div class="mt-4 grid gap-2 rounded-md bg-slate-100 p-1 {{ $ad ? 'grid-cols-3' : 'grid-cols-2' }}">
                    <button type="button" @click="mode = 'approve'" :class="mode === 'approve' ? 'bg-white text-green-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="rounded px-2 py-1.5 text-xs font-semibold">
                        {{ $ad ? 'Edit ad' : 'Approve' }}
                    </button>
                    <button type="button" @click="mode = 'reject'" :class="mode === 'reject' ? 'bg-white text-red-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="rounded px-2 py-1.5 text-xs font-semibold">
                        Reject
                    </button>
                    @if ($ad)
                        <button type="button" @click="mode = 'renew'" :class="mode === 'renew' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="rounded px-2 py-1.5 text-xs font-semibold">
                            Renew
                        </button>
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.enquiries.approve', $enquiry->id) }}" class="mt-4 space-y-4" x-show="mode === 'approve'" x-cloak>
                    @csrf
                    <input type="hidden" name="_mode" value="approve">

                    <div>
                        <label for="ad_title" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Card headline</label>
                        <input id="ad_title" name="ad_title" type="text" required value="{{ old('ad_title', $prefillTitle) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                        @error('ad_title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="ad_subtitle" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Sub-headline</label>
                        <input id="ad_subtitle" name="ad_subtitle" type="text" value="{{ old('ad_subtitle', $prefillSubtitle) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                    </div>

                    <div>
                        <label for="ad_blurb" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Card copy</label>
                        <textarea id="ad_blurb" name="ad_blurb" rows="3" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">{{ old('ad_blurb', $prefillBlurb) }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="ends_at" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Runs until</label>
                            <input id="ends_at" name="ends_at" type="date" min="{{ now()->toDateString() }}" value="{{ old('ends_at', $currentEndsAt ?: now()->addDays(30)->toDateString()) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                        </div>
                        <div>
                            <label for="sort_order" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Board order</label>
                            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $ad?->sort_order ?? 0) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                        </div>
                    </div>
                    @error('ends_at')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="button_text" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Button label</label>
                            <input id="button_text" name="button_text" type="text" value="{{ old('button_text', $ad->button_text ?? 'Learn more') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                        </div>
                        <div>
                            <label for="button_url" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Button link</label>
                            <input id="button_url" name="button_url" type="text" value="{{ old('button_url', $ad?->button_url) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                        </div>
                    </div>

                    <label class="flex items-start gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $ad ? ($ad->data['is_featured'] ?? false) : false)) class="mt-0.5 rounded border-slate-300 text-green-700 focus:ring-green-600">
                        <span>Feature in the hero carousel</span>
                    </label>

                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wide text-slate-500">Publishing</legend>
                        <div class="mt-2 space-y-2 text-sm text-slate-700">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="publish_mode" value="published" @checked($defaultPublishMode === 'published') class="border-slate-300 text-green-700 focus:ring-green-600">
                                <span>Publish to the board now</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="publish_mode" value="draft" @checked($defaultPublishMode === 'draft') class="border-slate-300 text-green-700 focus:ring-green-600">
                                <span>Save as draft card</span>
                            </label>
                        </div>
                    </fieldset>

                    @if (! $artworkIsImage)
                        <p class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">No image artwork on file — the card will use the default placeholder until you upload creative.</p>
                    @endif

                    <button type="submit" class="w-full rounded-md bg-green-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-800">
                        {{ $ad ? 'Save advert changes' : 'Approve & publish advert' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.enquiries.reject', $enquiry->id) }}" class="mt-4 space-y-4" x-show="mode === 'reject'" x-cloak>
                    @csrf
                    <input type="hidden" name="_mode" value="reject">
                    <div>
                        <label for="rejection_reason" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Reason for rejection</label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="4" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">{{ old('rejection_reason', $enquiryData['rejection_reason'] ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">Stored on the enquiry record for audit. @if ($ad) The linked advert will be unpublished. @endif</p>
                        @error('rejection_reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full rounded-md bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800">Reject enquiry</button>
                </form>

                @if ($ad)
                    <form method="POST" action="{{ route('admin.enquiries.renew', $enquiry->id) }}" class="mt-4 space-y-4" x-show="mode === 'renew'" x-cloak>
                        @csrf
                        <input type="hidden" name="_mode" value="renew">
                        <div>
                            <label for="renew_ends_at" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Extend until</label>
                            <input id="renew_ends_at" name="ends_at" type="date" min="{{ now()->addDay()->toDateString() }}" value="{{ old('ends_at', $renewDefault) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <p class="mt-1 text-xs text-slate-500">
                                Current end date: {{ $currentEndsAt ? \Illuminate\Support\Carbon::parse($currentEndsAt)->format('j M Y') : 'not set' }}
                            </p>
                            @error('ends_at')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="w-full rounded-md bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Renew advert</button>
                    </form>
                @endif
            </aside>
        </div>
    </div>
@endsection
