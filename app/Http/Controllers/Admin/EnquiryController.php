<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentAudit;
use App\Models\SiteContent;
use App\Support\AdEnquiries;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    public function __construct(private readonly AdEnquiries $enquiries) {}

    public function review(int $id)
    {
        $enquiry = $this->enquiry($id);

        return view('admin.enquiries.review', [
            'enquiry' => $enquiry,
            'ad' => $this->linkedAd($enquiry),
            'reviewStates' => $this->enquiries->counts(),
        ]);
    }

    public function approve(Request $request, int $id)
    {
        $enquiry = $this->enquiry($id);

        $validated = $request->validate([
            'ad_title' => ['required', 'string', 'max:255'],
            'ad_subtitle' => ['nullable', 'string', 'max:255'],
            'ad_blurb' => ['nullable', 'string', 'max:1000'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:today'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'publish_mode' => ['nullable', Rule::in(['published', 'draft'])],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $ad = DB::transaction(function () use ($enquiry, $validated) {
            $ad = $this->storeAd($enquiry, $validated);

            $this->markReviewed($enquiry, 'approved', [
                'ad_id' => $ad->id,
                'rejection_reason' => null,
            ]);

            $this->audit($enquiry, 'enquiry_approved', [
                'ad_id' => $ad->id,
                'published' => $ad->is_published,
                'ends_at' => data_get($ad->data, 'ends_at'),
            ]);

            return $ad;
        });

        return redirect()
            ->route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries', 'review' => 'approved'])
            ->with('status', $ad->is_published
                ? 'Enquiry approved — advert is live in Sponsored Ads.'
                : 'Enquiry approved — advert saved as a draft card.');
    }

    public function reject(Request $request, int $id)
    {
        $enquiry = $this->enquiry($id);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        DB::transaction(function () use ($enquiry, $validated) {
            $ad = $this->linkedAd($enquiry);

            if ($ad?->is_published) {
                $ad->update(['is_published' => false, 'published_at' => null]);
                $this->audit($ad, 'unpublished', ['reason' => 'Enquiry rejected']);
            }

            $this->markReviewed($enquiry, 'rejected', [
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            $this->audit($enquiry, 'enquiry_rejected', [
                'reason' => $validated['rejection_reason'],
                'ad_id' => $ad?->id,
            ]);
        });

        return redirect()
            ->route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries', 'review' => 'rejected'])
            ->with('status', 'Enquiry rejected and removed from the sponsored board.');
    }

    public function renew(Request $request, int $id)
    {
        $enquiry = $this->enquiry($id);

        $validated = $request->validate([
            'ends_at' => ['required', 'date', 'after:today'],
        ]);

        $endsAt = Carbon::parse($validated['ends_at'])->toDateString();

        DB::transaction(function () use ($enquiry, $endsAt) {
            $ad = $this->linkedAd($enquiry);
            abort_unless((bool) $ad, 404);

            $data = $enquiry->data ?? [];
            $data['ends_at'] = $endsAt;
            $data['status'] = 'approved';
            $enquiry->update(['data' => $data]);

            $adData = $ad->data ?? [];
            $adData['ends_at'] = $endsAt;
            $ad->update(['data' => $adData]);

            $this->audit($enquiry, 'ad_renewed', ['ad_id' => $ad->id, 'ends_at' => $endsAt]);
        });

        return redirect()
            ->route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries', 'review' => 'approved'])
            ->with('status', 'Advert renewed until '.Carbon::parse($endsAt)->format('j M Y').'.');
    }

    private function enquiry(int $id): SiteContent
    {
        return $this->enquiries->query()->findOrFail($id);
    }

    private function linkedAd(SiteContent $enquiry): ?SiteContent
    {
        $adId = data_get($enquiry->data, 'ad_id');

        return $adId ? SiteContent::where('section', 'advertise')->find($adId) : null;
    }

    private function storeAd(SiteContent $enquiry, array $validated): SiteContent
    {
        $reference = data_get($enquiry->data, 'reference_number') ?: ('AD-'.$enquiry->id);
        $published = ($validated['publish_mode'] ?? 'published') === 'published';

        $attributes = [
            'section' => 'advertise',
            'type' => 'card',
            'category' => 'Sponsored Ads',
            'title' => $validated['ad_title'],
            'subtitle' => $validated['ad_subtitle'] ?? null,
            'description' => $validated['ad_blurb'] ?? null,
            'accent_color' => 'green',
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $published,
            'published_at' => $published ? now() : null,
            'data' => array_filter([
                'is_featured' => (bool) ($validated['is_featured'] ?? false),
                'ends_at' => isset($validated['ends_at']) ? Carbon::parse($validated['ends_at'])->toDateString() : null,
                'reference_number' => $reference,
                'submission_id' => $enquiry->id,
                'source' => 'enquiry',
            ], fn ($value) => ! is_null($value)),
        ];

        $artwork = data_get($enquiry->data, 'artwork');

        if (is_array($artwork) && str_starts_with((string) ($artwork['mime_type'] ?? ''), 'image/')) {
            $attributes['image_path'] = $artwork['path'] ?? null;
            $attributes['media_asset_id'] = $artwork['media_asset_id'] ?? null;
            $attributes['alt_text'] = $validated['ad_title'];
        }

        $ad = $this->linkedAd($enquiry);

        if ($ad) {
            $ad->update($attributes);
            $this->audit($ad, 'updated', ['submission_id' => $enquiry->id]);

            return $ad;
        }

        $ad = SiteContent::create($attributes + [
            'slug' => $this->uniqueAdSlug($validated['ad_title'], $reference),
            'preview_token' => Str::random(40),
        ]);

        $this->audit($ad, 'created', ['submission_id' => $enquiry->id, 'source' => 'enquiry']);

        return $ad;
    }

    private function uniqueAdSlug(string $title, string $reference): string
    {
        $base = Str::slug($title.'-'.$reference) ?: Str::slug($reference);
        $slug = $base;
        $suffix = 2;

        while (SiteContent::where('section', 'advertise')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function markReviewed(SiteContent $enquiry, string $status, array $extra = []): void
    {
        $data = $enquiry->data ?? [];
        $data['status'] = $status;
        $data['reviewed_at'] = now()->toIso8601String();
        $data['reviewed_by'] = Auth::id();
        $data['reviewed_by_name'] = Auth::user()?->name;

        foreach ($extra as $key => $value) {
            if (is_null($value)) {
                unset($data[$key]);

                continue;
            }

            $data[$key] = $value;
        }

        $enquiry->update(['data' => $data]);
    }

    private function audit(SiteContent $content, string $action, ?array $changes = null): void
    {
        ContentAudit::create([
            'site_content_id' => $content->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'changes' => $changes,
        ]);
    }
}
