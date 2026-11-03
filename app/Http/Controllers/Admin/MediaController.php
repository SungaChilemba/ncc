<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $assets = MediaAsset::query()
            ->when($request->string('kind')->toString() === 'images', fn ($query) => $query->where('mime_type', 'like', 'image/%'))
            ->when($request->string('kind')->toString() === 'documents', fn ($query) => $query->where('mime_type', 'not like', 'image/%'))
            ->when($request->boolean('missing_alt'), fn ($query) => $query->where('mime_type', 'like', 'image/%')->where(function ($query) {
                $query->whereNull('alt_text')->orWhere('alt_text', '');
            }))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';

                $query->where(function ($query) use ($term) {
                    $query->where('original_name', 'like', $term)
                        ->orWhere('alt_text', 'like', $term)
                        ->orWhere('caption', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('path', 'like', $term);
                });
            })
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('admin.media.index', compact('assets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset' => ['required', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx'])->max('10mb')],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $this->createAsset($request, $validated);

        return redirect()->route('admin.media.index')->with('status', 'Media uploaded successfully.');
    }

    public function update(Request $request, MediaAsset $asset)
    {
        $validated = $request->validate([
            'asset' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx'])->max('10mb')],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('asset')) {
            $this->deleteStoredAsset($asset);
            $file = $request->file('asset');
            $folder = str_starts_with((string) $file->getMimeType(), 'image/') ? 'uploads' : 'documents';

            $validated['path'] = $file->store($folder, 'public');
            $validated['disk'] = 'public';
            $validated['original_name'] = $file->getClientOriginalName();
            $validated['mime_type'] = $file->getMimeType();
            $validated['size'] = $file->getSize();
        }

        unset($validated['asset']);
        $asset->update($validated);

        return redirect()->route('admin.media.index', $request->only(['q', 'kind', 'missing_alt']))
            ->with('status', 'Media details updated successfully.');
    }

    public function destroy(MediaAsset $asset)
    {
        if (SiteContent::where('media_asset_id', $asset->id)->exists()) {
            return redirect()->route('admin.media.index')
                ->with('status', 'Media is currently used by CMS content and was not deleted.');
        }

        $this->deleteStoredAsset($asset);
        $asset->delete();

        return redirect()->route('admin.media.index')->with('status', 'Media deleted successfully.');
    }

    private function createAsset(Request $request, array $validated): MediaAsset
    {
        $file = $request->file('asset');
        $folder = str_starts_with((string) $file->getMimeType(), 'image/') ? 'uploads' : 'documents';
        $path = $file->store($folder, 'public');

        return MediaAsset::create([
            'user_id' => Auth::id(),
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt_text' => $validated['alt_text'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);
    }

    private function deleteStoredAsset(MediaAsset $asset): void
    {
        if (str_starts_with($asset->path, 'images/') || str_starts_with($asset->path, 'resource/')) {
            return;
        }

        Storage::disk($asset->disk)->delete($asset->path);
    }
}
