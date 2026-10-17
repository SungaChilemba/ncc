<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Models\SiteContent;
use App\Support\CmsContent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class PageController extends Controller
{
    public function __construct(private readonly CmsContent $content) {}

    public function home()
    {
        $hero = $this->content->first('home', 'hero');
        $updates = $this->content->type('home', 'update');
        $news = $this->content->type('home', 'news');
        $latestNews = $this->content->publishedNews();

        return view('pages.home', [
            'cmsContent' => $this->content->forSection('home'),
            'hero' => $hero,
            'heroImage' => $this->content->heroImage($hero, 'images/NCChero.jpg'),
            'updates' => $updates,
            'featuredUpdate' => $updates->first(),
            'sidebarUpdate' => $updates->slice(1),
            'news' => $news,
            'latestNews' => $latestNews,
        ]);
    }

    public function about()
    {
        return $this->show('about', 'pages.about');
    }

    public function whatWeDo()
    {
        return $this->show('what-we-do', 'pages.what-we-do');
    }

    public function childrensCorner()
    {
        // Preserve section-specific blocks but ensure news cards use canonical global news when available
        $globalNews = $this->content->publishedNews();

        // Fallback to section-specific news for backwards compatibility when global news is empty (e.g. in tests)
        $cmsNews = $globalNews->isNotEmpty() ? $globalNews : $this->content->type('childrens-corner', 'news');

        return view('pages.childrens-corner', [
            'cmsContent' => $this->content->forSection('childrens-corner'),
            'cmsHero' => $this->content->first('childrens-corner', 'hero'),
            'cmsCards' => $this->content->type('childrens-corner', 'card'),
            'cmsCtas' => $this->content->type('childrens-corner', 'cta'),
            'cmsNews' => $cmsNews,
            'cmsResources' => $this->content->type('childrens-corner', 'resource'),
            'cmsGallery' => $this->content->type('childrens-corner', 'gallery'),
            'suppressCmsBlocks' => true,
        ]);
    }

    public function reporting()
    {
        return $this->show('reporting', 'pages.reporting');
    }

    public function storeReport(Request $request)
    {
        $validated = $request->validate([
            'child_age' => ['required', 'integer', 'min:0', 'max:17'],
            'child_name' => ['nullable', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:120'],
            'village' => ['nullable', 'string', 'max:160'],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'violation_type' => ['required', 'string', 'in:Abuse,Neglect,Child Marriage,Exploitation / Labour,Other'],
            'incident_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'min:10'],
            'reporter_name' => ['nullable', 'string', 'max:255'],
            'preferred_contact' => ['nullable', 'string', 'max:255'],
        ]);

        $reference = $this->newReportReference();

        SiteContent::create([
            'section' => 'reporting',
            'type' => 'submission',
            'slug' => Str::lower($reference),
            'title' => "Report {$reference}",
            'subtitle' => $validated['violation_type'],
            'category' => 'Case Submissions',
            'location' => $validated['district'],
            'description' => $validated['description'],
            'data' => [
                'reference_number' => $reference,
                'child_age' => $validated['child_age'],
                'child_name' => $validated['child_name'] ?? null,
                'district' => $validated['district'],
                'village' => $validated['village'] ?? null,
                'gender' => $validated['gender'],
                'violation_type' => $validated['violation_type'],
                'incident_date' => $validated['incident_date'] ?? null,
                'reporter_name' => $validated['reporter_name'] ?? null,
                'preferred_contact' => $validated['preferred_contact'] ?? null,
                'submitted_at' => now()->toIso8601String(),
                'status' => 'received',
            ],
            'accent_color' => 'red',
            'sort_order' => 0,
            'is_published' => false,
            'published_at' => null,
        ]);

        return redirect()
            ->route('reporting')
            ->with('report_status', 'Complaint captured.')
            ->with('report_reference', $reference);
    }

    public function storeEvidence(Request $request)
    {
        $validated = $request->validate([
            'reference_number' => ['required', 'string', 'max:32'],
            'evidence' => ['required', 'array', 'min:1', 'max:5'],
            'evidence.*' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'mp3', 'mp4', 'pdf'])->max('20mb'),
            ],
        ]);

        $reference = Str::upper($validated['reference_number']);
        $submission = SiteContent::where('section', 'reporting')
            ->where('type', 'submission')
            ->where('slug', Str::lower($reference))
            ->first();

        if (! $submission) {
            return back()
                ->withErrors(['reference_number' => 'No report was found for that reference number.'])
                ->withInput()
                ->with('report_tab', 'evidence');
        }

        $existingEvidence = $submission->data['evidence'] ?? [];
        $uploadedEvidence = [];

        foreach ($request->file('evidence', []) as $file) {
            $path = $file->store('evidence/'.Str::lower($reference), 'public');

            $asset = MediaAsset::create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'alt_text' => "Evidence for {$reference}",
            ]);

            $uploadedEvidence[] = [
                'media_asset_id' => $asset->id,
                'path' => $path,
                'url' => $asset->url,
                'original_name' => $asset->original_name,
                'mime_type' => $asset->mime_type,
                'size' => $asset->size,
                'uploaded_at' => now()->toIso8601String(),
            ];
        }

        $data = $submission->data ?? [];
        $data['evidence'] = array_values(array_merge($existingEvidence, $uploadedEvidence));
        $data['evidence_count'] = count($data['evidence']);
        $data['status'] = $data['status'] ?? 'received';
        $data['last_evidence_uploaded_at'] = now()->toIso8601String();

        $submission->update(['data' => $data]);

        return redirect()
            ->route('reporting')
            ->with('report_tab', 'evidence')
            ->with('evidence_status', count($uploadedEvidence).' evidence file(s) attached to '.$reference.'.')
            ->with('evidence_reference', $reference);
    }

    public function resources()
    {
        return $this->show('resources', 'pages.resources');
    }

    public function donate()
    {
        return $this->show('donate', 'pages.donate');
    }

    public function advertise()
    {
        return $this->show('advertise', 'pages.advertise', [
            'heroAds' => $this->content->heroSponsoredAds(3),
            'heroStats' => $this->advertiseHeroStats(),
            'sponsoredAds' => $this->content->sponsoredAds(),
        ]);
    }

    /**
     * Hero statistics for the advertise page. Admin-authored "stat" blocks
     * take precedence; otherwise the values are live counts derived from the
     * advertise section. Zero-value computed stats are dropped so the hero
     * renders nothing rather than empty placeholders.
     */
    private function advertiseHeroStats(): Collection
    {
        $cmsStats = $this->content->type('advertise', 'stat');

        if ($cmsStats->isNotEmpty()) {
            return $cmsStats->take(3)->map(fn (SiteContent $stat) => $this->normaliseHeroStat(
                (string) ($stat->title ?? ''),
                $stat->description,
                data_get($stat->data, 'icon'),
            ))->values();
        }

        $liveAds = $this->content->sponsoredAds();

        $enquiries = SiteContent::forSection('advertise')
            ->ofType('submission')
            ->where('category', 'Ad Inquiries');

        $enquiryCount = (clone $enquiries)->count();

        $approvedPartners = (clone $enquiries)
            ->where('data->status', 'approved')
            ->get()
            ->pluck('data.company')
            ->filter()
            ->unique()
            ->count();

        return collect([
            $this->normaliseHeroStat($liveAds->count(), 'Live partner adverts', 'fa-rectangle-ad'),
            $this->normaliseHeroStat($enquiryCount, 'Partner enquiries received', 'fa-envelope-open-text'),
            $this->normaliseHeroStat($approvedPartners, 'Approved partners', 'fa-handshake'),
        ])->filter(fn (array $stat) => $stat['value'] === null || $stat['value'] > 0)->values();
    }

    /**
     * Normalise a stat label/value into a count-up-friendly shape. "1,249+"
     * becomes value 1249 with suffix "+"; non-numeric values keep the raw text.
     */
    private function normaliseHeroStat(string|int $value, ?string $label, ?string $icon): array
    {
        $raw = trim((string) $value);
        $numeric = null;
        $suffix = '';

        if (preg_match('/^([0-9][0-9,\.]*)(.*)$/', $raw, $matches)) {
            $numeric = (int) str_replace([',', '.'], '', $matches[1]);
            $suffix = trim($matches[2]);
        }

        return [
            'value' => $numeric,
            'suffix' => $suffix,
            'raw' => $raw,
            'label' => filled($label) ? $label : $raw,
            'icon' => $icon ?: 'fa-circle-check',
        ];
    }

    public function storeAdvertiseInquiry(Request $request)
    {
        $validated = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:100'],
            'objectives' => ['required', 'string', 'min:10', 'max:2000'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'budget' => ['nullable', 'string', 'max:50'],
            'artwork' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max('5mb')],
            'agreement' => ['accepted'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9\s\-\(\)]+$/'],
            'org_address' => ['nullable', 'string', 'max:255'],
            'preferred_contact' => ['required', 'in:Email,Phone,WhatsApp'],
        ]);

        $reference = $this->newAdvertiseReference();
        $artworkData = null;

        if ($request->hasFile('artwork')) {
            $file = $request->file('artwork');
            $path = $file->store('advertise-inquiries/'.Str::lower($reference), 'public');
            $asset = MediaAsset::create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'alt_text' => "Artwork for {$validated['company']} ({$reference})",
            ]);
            $artworkData = [
                'media_asset_id' => $asset->id,
                'path' => $path,
                'url' => $asset->url,
                'original_name' => $asset->original_name,
                'mime_type' => $asset->mime_type,
                'size' => $asset->size,
            ];
        }

        SiteContent::create([
            'section' => 'advertise',
            'type' => 'submission',
            'slug' => Str::lower($reference),
            'title' => $validated['company']." — {$reference}",
            'subtitle' => $validated['industry'],
            'category' => 'Ad Inquiries',
            'description' => $validated['objectives'],
            'data' => array_filter([
                'reference_number' => $reference,
                'company' => $validated['company'],
                'industry' => $validated['industry'],
                'objectives' => $validated['objectives'],
                'start_date' => $validated['start_date'],
                'budget' => $validated['budget'] ?? null,
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'],
                'org_address' => $validated['org_address'] ?? null,
                'preferred_contact' => $validated['preferred_contact'],
                'agreement' => true,
                'artwork' => $artworkData,
                'submitted_at' => now()->toIso8601String(),
                'status' => 'pending_review',
            ], fn ($v) => ! is_null($v)),
            'accent_color' => 'green',
            'sort_order' => 0,
            'is_published' => false,
            'published_at' => null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Inquiry received.',
                'reference' => $reference,
            ]);
        }

        return redirect()
            ->route('advertise')
            ->with('inquiry_status', 'Inquiry received — reference '.$reference.'. NCC board will review within 5–7 days.')
            ->with('inquiry_reference', $reference);
    }

    private function newAdvertiseReference(): string
    {
        do {
            $reference = 'AD-'.now()->year.'-'.random_int(10000, 99999);
        } while (SiteContent::where('section', 'advertise')->where('slug', Str::lower($reference))->exists());

        return $reference;
    }

    public function latestNews()
    {
        $articles = $this->content->publishedNews();
        $heroSlides = $this->content->heroNews(3);
        $featuredArticle = $this->content->featuredNews(1)->first() ?? $articles->first();
        $categories = $this->content->newsCategories();

        return view('pages.latest-news', [
            'articles' => $articles,
            'heroSlides' => $heroSlides,
            'featuredArticle' => $featuredArticle,
            'categories' => $categories,
            'latestArticles' => $articles,
            'sponsoredAds' => $this->content->sponsoredAds(),
            'suppressCmsBlocks' => true,
        ]);
    }

    public function newsArticle(string $slug)
    {
        $article = $this->content->bySlug('news', $slug, 'news');

        if (! $article) {
            $article = $this->content->bySlug('home', $slug, 'update');
        }

        abort_unless($article, 404);

        return view('pages.news', [
            'article' => $article,
            'relatedArticles' => $this->content->relatedNews($article, 3),
            'suppressCmsBlocks' => true,
        ]);
    }

    public function resourceDetail(string $slug)
    {
        $resource = $this->content->bySlug('resources', $slug, 'resource');

        abort_unless($resource, 404);

        return view('pages.resource-detail', [
            'resource' => $resource,
            'relatedResources' => $this->content->type('resources', 'resource')
                ->reject(fn ($item) => $item->is($resource))
                ->take(3),
            'suppressCmsBlocks' => true,
        ]);
    }

    public function downloadResource(string $slug)
    {
        $resource = $this->content->bySlug('resources', $slug, 'resource');

        abort_unless($resource?->file_path, 404);

        if (Str::startsWith($resource->file_path, ['http://', 'https://'])) {
            return redirect()->away($resource->file_path);
        }

        $path = Str::startsWith($resource->file_path, ['resource/', 'images/'])
            ? public_path($resource->file_path)
            : Storage::disk('public')->path($resource->file_path);

        abort_unless(is_file($path), 404);

        return response()->download($path, basename($path));
    }

    public function checkout()
    {
        return view('pages.checkout');
    }

    private function newReportReference(): string
    {
        do {
            $reference = 'NCC-'.now()->year.'-'.random_int(10000, 99999);
        } while (SiteContent::where('section', 'reporting')->where('slug', Str::lower($reference))->exists());

        return $reference;
    }

    public function show(string $section, string $view, array $extra = [])
    {
        abort_unless(array_key_exists($section, config('cms.sections')), 404);

        return view($view, array_merge([
            'cmsContent' => $this->content->forSection($section),
            'cmsHero' => $this->content->first($section, 'hero'),
            'cmsCards' => $this->content->type($section, 'card'),
            'cmsCtas' => $this->content->type($section, 'cta'),
            'cmsNews' => $this->content->type($section, 'news'),
            'cmsResources' => $this->content->type($section, 'resource'),
            'cmsGallery' => $this->content->type($section, 'gallery'),
            'suppressCmsBlocks' => true,
        ], $extra));
    }
}
