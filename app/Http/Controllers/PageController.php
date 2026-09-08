<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use App\Models\MediaAsset;
use App\Support\CmsContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class PageController extends Controller
{
    public function __construct(private readonly CmsContent $content)
    {
    }

    public function home()
    {
        $hero = $this->content->first('home', 'hero');
        $updates = $this->content->type('home', 'update');
        $news = $this->content->type('home', 'news');
        $latestNews = $this->content->type('news', 'news');

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
        return $this->show('childrens-corner', 'pages.childrens-corner');
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
            $path = $file->store('evidence/' . Str::lower($reference), 'public');

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
            ->with('evidence_status', count($uploadedEvidence) . ' evidence file(s) attached to ' . $reference . '.')
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
        return $this->show('advertise', 'pages.advertise');
    }

    public function news()
    {
        return $this->show('news', 'pages.news');
    }

    public function newsArticle(string $slug)
    {
        $article = $this->content->bySlug('news', $slug, 'news');

        abort_unless($article, 404);

        return view('pages.news-detail', [
            'article' => $article,
            'relatedArticles' => $this->content->type('news', 'news')
                ->reject(fn ($item) => $item->is($article))
                ->take(3),
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
            $reference = 'NCC-' . now()->year . '-' . random_int(10000, 99999);
        } while (SiteContent::where('section', 'reporting')->where('slug', Str::lower($reference))->exists());

        return $reference;
    }

    public function show(string $section, string $view)
    {
        abort_unless(array_key_exists($section, config('cms.sections')), 404);

        return view($view, [
            'cmsContent' => $this->content->forSection($section),
            'cmsHero' => $this->content->first($section, 'hero'),
            'cmsCards' => $this->content->type($section, 'card'),
            'cmsCtas' => $this->content->type($section, 'cta'),
            'cmsNews' => $this->content->type($section, 'news'),
            'cmsResources' => $this->content->type($section, 'resource'),
            'cmsGallery' => $this->content->type($section, 'gallery'),
            'suppressCmsBlocks' => true,
        ]);
    }
}
