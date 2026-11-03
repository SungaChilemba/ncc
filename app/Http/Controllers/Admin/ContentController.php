<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentAudit;
use App\Models\ContentRevision;
use App\Models\MediaAsset;
use App\Models\SiteContent;
use App\Support\AdEnquiries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ContentController extends Controller
{
    public function __construct(private readonly AdEnquiries $adEnquiries)
    {
    }

    public function index(Request $request, string $section)
    {
        $sectionConfig = $this->sectionConfig($section);
        $typeOptions = $this->typeOptions($sectionConfig);

        $contents = $this->contentQuery($request, $section)
            ->paginate(12)
            ->withQueryString();
        $mediaAssets = MediaAsset::latest()->take(18)->get();
        $enquiryStates = $section === 'advertise' ? $this->adEnquiries->counts() : null;
        $currentReview = $this->reviewFilter($request);

        return view('admin.contents.index', compact(
            'contents',
            'section',
            'sectionConfig',
            'typeOptions',
            'mediaAssets',
            'enquiryStates',
            'currentReview'
        ));
    }

    public function store(Request $request, string $section)
    {
        $sectionConfig = $this->sectionConfig($section);
        $this->hydrateSlug($request);

        $categoryRule = match (true) {
            $section === 'resources' && $request->string('type')->toString() === 'resource' => ['nullable', 'string', Rule::in(array_keys(config('cms.resource_categories')))],
            $section === 'donate' && $request->string('type')->toString() === 'card' => ['nullable', 'string', Rule::in(array_keys(config('cms.donate_categories')))],
            default => ['nullable', 'string', 'max:100'],
        };

        $validated = $request->validate([
            'type' => ['required', Rule::in($sectionConfig['types'])],
            'slug' => ['nullable', 'string', 'max:255', $this->uniqueSlugRule($request, $section)],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'category' => $categoryRule,
            'location' => ['nullable', 'string', 'max:100'],
            'published_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'data_json' => ['nullable', 'json'],
            'hero_featured' => ['nullable', 'boolean'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'media_asset_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'image' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max('3mb')],
            'document' => ['nullable', File::types(['pdf', 'doc', 'docx'])->max('10mb')],
            'accent_color' => ['nullable', Rule::in(array_keys(config('cms.accent_colors')))],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
            'publish_mode' => ['nullable', Rule::in(['draft', 'published', 'scheduled'])],
            'publish_at' => ['nullable', 'date'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'source_posted_on' => ['nullable', 'date'],
            'source_excerpt' => ['nullable', 'string'],
            'item_label' => ['nullable', 'array'],
            'item_label.*' => ['nullable', 'string', 'max:255'],
            'item_value' => ['nullable', 'array'],
            'item_value.*' => ['nullable', 'string'],
            'bio_heading' => ['nullable', 'array'],
            'bio_heading.*' => ['nullable', 'string', 'max:255'],
            'bio_body' => ['nullable', 'array'],
            'bio_body.*' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('uploads', 'public');
            $validated['image_path'] = $path;
            $validated['media_asset_id'] = $this->recordMedia($request, 'image', $path)->id;
        } elseif ($request->filled('media_asset_id')) {
            $this->applyMediaSelection($validated);
        }

        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('documents', 'public');
            $validated['file_path'] = $path;
            $validated['media_asset_id'] = $this->recordMedia($request, 'document', $path)->id;
        }

        $validated['section'] = $section;
        $validated = $this->prepareContentData($validated);
        $validated = $this->preparePublishingState($request, $validated);

        $content = SiteContent::create($validated);
        $this->audit($content, 'created');

        return redirect()->route('admin.contents.index', ['section' => $section])->with('status', 'Content created successfully.');
    }

    public function create(Request $request, string $section)
    {
        $sectionConfig = $this->sectionConfig($section);
        $typeOptions = $this->typeOptions($sectionConfig);
        $mediaAssets = MediaAsset::latest()->take(18)->get();

        return view('admin.contents.editor', compact('section', 'sectionConfig', 'typeOptions', 'mediaAssets'));
    }

    public function edit(Request $request, string $section, int $id)
    {
        $sectionConfig = $this->sectionConfig($section);
        $typeOptions = $this->typeOptions($sectionConfig);
        $content = SiteContent::where('section', $section)->findOrFail($id);

        return view('admin.contents.editor', [
            'section' => $section,
            'sectionConfig' => $sectionConfig,
            'typeOptions' => $typeOptions,
            'mediaAssets' => MediaAsset::latest()->take(18)->get(),
            'editing' => $content,
        ]);
    }

    public function update(Request $request, string $section, int $id)
    {
        $sectionConfig = $this->sectionConfig($section);
        $content = SiteContent::where('section', $section)->findOrFail($id);
        $this->hydrateSlug($request);

        $categoryRuleUpdate = match (true) {
            $section === 'resources' && $request->string('type')->toString() === 'resource' => ['nullable', 'string', Rule::in(array_keys(config('cms.resource_categories')))],
            $section === 'donate' && $request->string('type')->toString() === 'card' => ['nullable', 'string', Rule::in(array_keys(config('cms.donate_categories')))],
            default => ['nullable', 'string', 'max:100'],
        };

        $validated = $request->validate([
            'type' => ['required', Rule::in($sectionConfig['types'])],
            'slug' => ['nullable', 'string', 'max:255', $this->uniqueSlugRule($request, $section, $content->id)],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'category' => $categoryRuleUpdate,
            'location' => ['nullable', 'string', 'max:100'],
            'published_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'data_json' => ['nullable', 'json'],
            'hero_featured' => ['nullable', 'boolean'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'media_asset_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'image' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max('3mb')],
            'document' => ['nullable', File::types(['pdf', 'doc', 'docx'])->max('10mb')],
            'accent_color' => ['nullable', Rule::in(array_keys(config('cms.accent_colors')))],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
            'publish_mode' => ['nullable', Rule::in(['draft', 'published', 'scheduled'])],
            'publish_at' => ['nullable', 'date'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'source_posted_on' => ['nullable', 'date'],
            'source_excerpt' => ['nullable', 'string'],
            'item_label' => ['nullable', 'array'],
            'item_label.*' => ['nullable', 'string', 'max:255'],
            'item_value' => ['nullable', 'array'],
            'item_value.*' => ['nullable', 'string'],
            'bio_heading' => ['nullable', 'array'],
            'bio_heading.*' => ['nullable', 'string', 'max:255'],
            'bio_body' => ['nullable', 'array'],
            'bio_body.*' => ['nullable', 'string'],
            '_remove_image' => ['boolean'],
            '_remove_document' => ['boolean'],
        ]);

        if ($request->boolean('_remove_image') && $content->image_path) {
            $this->deleteStoredAsset($content->image_path);
            $validated['image_path'] = null;
            $validated['media_asset_id'] = null;
        } elseif ($request->hasFile('image')) {
            $this->deleteStoredAsset($content->image_path);
            $path = $request->file('image')->store('uploads', 'public');
            $validated['image_path'] = $path;
            $validated['media_asset_id'] = $this->recordMedia($request, 'image', $path)->id;
        } elseif ($request->filled('media_asset_id')) {
            $this->applyMediaSelection($validated);
        }

        if ($request->boolean('_remove_document') && $content->file_path) {
            $this->deleteStoredAsset($content->file_path);
            $validated['file_path'] = null;
        } elseif ($request->hasFile('document')) {
            $this->deleteStoredAsset($content->file_path);
            $path = $request->file('document')->store('documents', 'public');
            $validated['file_path'] = $path;
            $validated['media_asset_id'] = $this->recordMedia($request, 'document', $path)->id;
        }

        $validated['section'] = $section;
        $validated = $this->prepareContentData($validated);
        $validated = $this->preparePublishingState($request, $validated);
        unset($validated['section']);

        $original = $content->getOriginal();
        $this->recordRevision($content);
        $content->update($validated);
        $this->audit($content, 'updated', $this->changedValues($original, $content));

        return redirect()->route('admin.contents.index', ['section' => $section])->with('status', 'Content updated successfully.');
    }

    public function togglePublish(Request $request, string $section, int $id)
    {
        $this->sectionConfig($section);
        $content = SiteContent::where('section', $section)->findOrFail($id);

        if (! $this->canBePublished($content->type)) {
            $content->update([
                'is_published' => false,
                'published_at' => null,
            ]);

            $this->audit($content, 'publish_blocked');

            return redirect()->route('admin.contents.index', ['section' => $section])
                ->with('status', 'Report submissions cannot be published.');
        }

        $content->is_published = ! $content->is_published;
        $content->published_at = $content->is_published ? now() : null;
        $content->save();
        $this->audit($content, $content->is_published ? 'published' : 'unpublished');

        return redirect()->route('admin.contents.index', ['section' => $section])
            ->with('status', 'Content ' . ($content->is_published ? 'published' : 'unpublished') . ' successfully.');
    }

    public function destroy(Request $request, string $section, int $id)
    {
        $this->sectionConfig($section);
        $content = SiteContent::where('section', $section)->findOrFail($id);

        $this->deleteStoredAsset($content->image_path);
        $this->deleteStoredAsset($content->file_path);

        $this->audit($content, 'deleted');
        $content->delete();

        return redirect()->route('admin.contents.index', ['section' => $section])->with('status', 'Content deleted successfully.');
    }

    public function uploadImage(Request $request, int $id)
    {
        $request->validate([
            'image' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max('3mb')],
        ]);

        $content = SiteContent::findOrFail($id);

        if ($content->image_path) {
            Storage::disk('public')->delete($content->image_path);
        }

        $path = $request->file('image')->store('uploads', 'public');
        $asset = $this->recordMedia($request, 'image', $path);
        $content->update(['image_path' => $path, 'media_asset_id' => $asset->id]);
        $this->audit($content, 'image_uploaded');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }

    public function bulkAction(Request $request, string $section)
    {
        $this->sectionConfig($section);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['publish', 'unpublish', 'delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $contents = SiteContent::forSection($section)->whereIn('id', $validated['ids'])->get();

        foreach ($contents as $content) {
            if ($validated['action'] === 'delete') {
                $this->deleteStoredAsset($content->image_path);
                $this->deleteStoredAsset($content->file_path);
                $this->audit($content, 'deleted');
                $content->delete();
                continue;
            }

            if ($validated['action'] === 'publish' && ! $this->canBePublished($content->type)) {
                $content->update([
                    'is_published' => false,
                    'published_at' => null,
                ]);
                $this->audit($content, 'publish_blocked');
                continue;
            }

            $content->update([
                'is_published' => $validated['action'] === 'publish',
                'published_at' => $validated['action'] === 'publish' ? now() : null,
            ]);
            $this->audit($content, $validated['action'] === 'publish' ? 'published' : 'unpublished');
        }

        return redirect()->route('admin.contents.index', ['section' => $section])
            ->with('status', $contents->count() . ' content item(s) processed.');
    }

    public function reorder(Request $request, string $section)
    {
        $this->sectionConfig($section);

        $validated = $request->validate([
            'orders' => ['required', 'array'],
            'orders.*' => ['integer', 'min:0'],
        ]);

        foreach ($validated['orders'] as $id => $sortOrder) {
            SiteContent::forSection($section)->whereKey($id)->update(['sort_order' => $sortOrder]);
        }

        return redirect()->route('admin.contents.index', ['section' => $section])->with('status', 'Sort order updated.');
    }

    public function preview(string $section, int $id)
    {
        $this->sectionConfig($section);
        $content = SiteContent::where('section', $section)->findOrFail($id);
        $audits = ContentAudit::where('site_content_id', $content->id)->latest()->take(10)->get();
        $revisions = $content->revisions()->latest()->take(10)->get();

        return view('admin.contents.preview', compact('content', 'section', 'audits', 'revisions'));
    }

    public function restoreRevision(string $section, int $id, ContentRevision $revision)
    {
        $this->sectionConfig($section);
        $content = SiteContent::where('section', $section)->findOrFail($id);
        abort_unless($revision->site_content_id === $content->id, 404);

        $this->recordRevision($content);

        $snapshot = collect($revision->snapshot)
            ->only([
                'type',
                'slug',
                'title',
                'subtitle',
                'category',
                'location',
                'published_on',
                'description',
                'data',
                'image_path',
                'alt_text',
                'file_path',
                'media_asset_id',
                'accent_color',
                'button_text',
                'button_url',
                'sort_order',
                'is_published',
                'published_at',
                'preview_token',
            ])
            ->all();

        if (! $this->canBePublished($snapshot['type'] ?? $content->type)) {
            $snapshot['is_published'] = false;
            $snapshot['published_at'] = null;
        }

        $content->update($snapshot);
        $this->audit($content, 'revision_restored', ['revision_id' => $revision->id]);

        return redirect()
            ->route('admin.contents.edit', ['section' => $section, 'id' => $content->id])
            ->with('status', 'Revision restored successfully.');
    }

    private function sectionConfig(string $section): array
    {
        abort_unless(array_key_exists($section, config('cms.sections')), 404);

        return config("cms.sections.$section");
    }

    private function typeOptions(array $sectionConfig): array
    {
        return collect(config('cms.types'))
            ->only($sectionConfig['types'])
            ->all();
    }

    private function canBePublished(string $type): bool
    {
        return $type !== 'submission';
    }

    private function preparePublishingState(Request $request, array $validated): array
    {
        $mode = $request->string('publish_mode')->toString() ?: ($request->has('is_published') ? 'published' : 'draft');

        unset($validated['publish_mode'], $validated['publish_at']);

        if (! $this->canBePublished($validated['type'])) {
            $validated['is_published'] = false;
            $validated['published_at'] = null;

            return $validated;
        }

        if ($mode === 'scheduled') {
            $validated['is_published'] = true;
            $validated['published_at'] = $request->date('publish_at') ?: now();

            return $validated;
        }

        $validated['is_published'] = $mode === 'published';
        $validated['published_at'] = $mode === 'published' ? now() : null;

        return $validated;
    }

    private function contentQuery(Request $request, string $section)
    {
        if ($section === 'advertise' && $request->string('status')->toString() === 'enquiries') {
            return $this->adEnquiries
                ->filter($this->reviewFilter($request))
                ->when($request->filled('q'), fn (Builder $query) => $this->applySearch($query, $request->string('q')->toString()))
                ->latest('id');
        }

        return SiteContent::forSection($section)
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('status'), function ($query) use ($request, $section) {
                $status = $request->string('status')->toString();

                if ($status === 'private') {
                    $query->where('type', 'submission');

                    return;
                }

                if ($status === 'enquiries') {
                    $query->where('type', 'submission');
                    // For advertise, scope to Ad Inquiries only to keep enquiry tab clean (reporting has Case Submissions)
                    if ($section === 'advertise') {
                        $query->where('category', 'Ad Inquiries');
                    } elseif ($section === 'reporting') {
                        $query->where('category', 'Case Submissions');
                    }

                    return;
                }

                if ($status === 'all') {
                    // Explicit status=all includes submissions; omitting status still hides them.
                    return;
                }

                $query->where('is_published', $status === 'published');
            })
            ->when(!$request->filled('status'), function ($query) {
                // Default "All" view should not show enquiries — only Enquiries tab does
                $query->where('type', '!=', 'submission');
            })
            ->when($request->filled('q'), fn (Builder $query) => $this->applySearch($query, $request->string('q')->toString()))
            ->ordered();
    }

    private function reviewFilter(Request $request): ?string
    {
        $review = $request->string('review')->toString();

        return in_array($review, AdEnquiries::FILTERS, true) ? $review : null;
    }

    private function applySearch(Builder $query, string $term): Builder
    {
        $like = '%' . $term . '%';

        return $query->where(function (Builder $query) use ($like) {
            $query->where('title', 'like', $like)
                ->orWhere('subtitle', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('slug', 'like', $like)
                ->orWhere('data', 'like', $like);
        });
    }

    private function uniqueSlugRule(Request $request, string $section, ?int $ignoreId = null)
    {
        return Rule::unique('site_contents', 'slug')
            ->where(fn ($query) => $query
                ->where('section', $section)
                ->where('type', $request->string('type')->toString()))
            ->ignore($ignoreId);
    }

    private function hydrateSlug(Request $request): void
    {
        $type = $request->string('type')->toString();

        if (in_array($type, ['news', 'resource'], true) && ! $request->filled('slug') && $request->filled('title')) {
            $request->merge(['slug' => Str::slug($request->string('title')->toString())]);
        } elseif ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->string('slug')->toString())]);
        }
    }

    private function prepareContentData(array $data): array
    {
        $structured = $this->structuredDataFromFields($data);

        if (array_key_exists('data_json', $data)) {
            $data['data'] = $data['data_json'] ? json_decode($data['data_json'], true) : null;
            unset($data['data_json']);
        }

        $data['data'] = array_replace_recursive($data['data'] ?? [], $structured);

        unset(
            $data['source_name'],
            $data['source_url'],
            $data['source_posted_on'],
            $data['source_excerpt'],
            $data['item_label'],
            $data['item_value'],
            $data['bio_heading'],
            $data['bio_body'],
        );

        $data['preview_token'] ??= Str::random(40);

        $detailTypes = ['news', 'resource'];

        if (in_array($data['type'], $detailTypes, true) && empty($data['slug']) && ! empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        } elseif (! empty($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }

        if (($data['section'] ?? null) === 'advertise') {
            $existingData = is_array($data['data'] ?? null) ? $data['data'] : [];
            $existingData['is_featured'] = filter_var($data['hero_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $data['data'] = $existingData;
        }

        unset($data['hero_featured']);

        return $data;
    }

    private function structuredDataFromFields(array $data): array
    {
        $structured = [];

        if (! empty($data['source_name']) || ! empty($data['source_url']) || ! empty($data['source_posted_on']) || ! empty($data['source_excerpt'])) {
            $structured['source'] = array_filter([
                'name' => $data['source_name'] ?? null,
                'url' => $data['source_url'] ?? null,
                'posted_on' => $data['source_posted_on'] ?? null,
                'visible_excerpt' => $data['source_excerpt'] ?? null,
            ], fn ($value) => filled($value));
        }

        $labels = $data['item_label'] ?? [];
        $values = $data['item_value'] ?? [];
        $items = [];

        foreach ($labels as $index => $label) {
            $value = $values[$index] ?? null;

            if (blank($label) && blank($value)) {
                continue;
            }

            $items[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        if ($items) {
            $structured['items'] = $items;
        }

        $headings = $data['bio_heading'] ?? [];
        $bodies = $data['bio_body'] ?? [];
        $bioSections = [];

        foreach ($headings as $index => $heading) {
            $body = $bodies[$index] ?? null;

            if (blank($heading) && blank($body)) {
                continue;
            }

            $bioSections[] = [
                'heading' => $heading,
                'body' => $body,
            ];
        }

        if ($bioSections) {
            $structured['bio_sections'] = $bioSections;
        }

        return $structured;
    }

    private function recordRevision(SiteContent $content): void
    {
        ContentRevision::create([
            'site_content_id' => $content->id,
            'user_id' => Auth::id(),
            'snapshot' => $content->fresh()->only([
                'type',
                'slug',
                'title',
                'subtitle',
                'category',
                'location',
                'published_on',
                'description',
                'data',
                'image_path',
                'alt_text',
                'file_path',
                'media_asset_id',
                'accent_color',
                'button_text',
                'button_url',
                'sort_order',
                'is_published',
                'published_at',
                'preview_token',
            ]),
        ]);
    }

    private function recordMedia(Request $request, string $field, string $path): MediaAsset
    {
        $file = $request->file($field);

        return MediaAsset::create([
            'user_id' => Auth::id(),
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file?->getClientOriginalName(),
            'mime_type' => $file?->getMimeType(),
            'size' => $file?->getSize() ?? 0,
            'alt_text' => $request->string('alt_text')->toString() ?: null,
        ]);
    }

    private function applyMediaSelection(array &$validated): void
    {
        $asset = MediaAsset::find($validated['media_asset_id']);

        if (! $asset) {
            return;
        }

        if ($asset->is_image) {
            $validated['image_path'] = $asset->path;
        } else {
            $validated['file_path'] = $asset->path;
        }

        $validated['alt_text'] ??= $asset->alt_text;
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

    private function changedValues(array $original, SiteContent $content): array
    {
        return collect($content->getChanges())
            ->except(['updated_at'])
            ->map(fn ($value, $key) => [
                'from' => $original[$key] ?? null,
                'to' => $value,
            ])
            ->all();
    }

    private function deleteStoredAsset(?string $path): void
    {
        if (! $path || str_starts_with($path, 'images/') || str_starts_with($path, 'resource/') || str_starts_with($path, 'http')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
