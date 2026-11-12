<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Folders
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        $folders = MediaFolder::withCount('media')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.media.index', compact('folders'));
    }

    public function create()
    {
        return view('admin.media.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'slug'        => ['nullable', 'string', 'max:120', 'unique:media_folders,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $folder = MediaFolder::create($validated);

        return redirect()
            ->route('admin.media.show', $folder->slug)
            ->with('success', 'Gallery folder "' . $folder->name . '" created.');
    }

    public function show(MediaFolder $mediaFolder)
    {
        $media = $mediaFolder->getMedia('images');

        return view('admin.media.show', [
            'folder' => $mediaFolder,
            'media'  => $media,
        ]);
    }

    public function edit(MediaFolder $mediaFolder)
    {
        return view('admin.media.edit', ['folder' => $mediaFolder]);
    }

    public function update(Request $request, MediaFolder $mediaFolder)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'slug'        => ['nullable', 'string', 'max:120', 'unique:media_folders,slug,' . $mediaFolder->id],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $mediaFolder->update($validated);

        return redirect()
            ->route('admin.media.show', $mediaFolder->slug)
            ->with('success', 'Folder updated.');
    }

    public function destroy(MediaFolder $mediaFolder)
    {
        $mediaFolder->clearMediaCollection('images');
        $mediaFolder->delete();

        return redirect()
            ->route('admin.media.index')
            ->with('success', 'Folder and all its images deleted.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Media items (AJAX)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /admin/media/{folder}/upload
     * Accepts: file (single image). Called by Dropzone one file at a time.
     */
    public function upload(Request $request, MediaFolder $mediaFolder)
    {
        $request->validate([
            'file' => [
                'required',
                File::image()
                    ->max(10 * 1024) // 10 MB
                    ->dimensions(Rule::dimensions()->minWidth(10)->minHeight(10)),
            ],
        ]);

        $media = $mediaFolder
            ->addMediaFromRequest('file')
            ->usingName($request->input('custom_name', pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME)))
            ->withCustomProperties([
                'alt'     => $request->input('alt', ''),
                'caption' => $request->input('caption', ''),
            ])
            ->toMediaCollection('images');

        return response()->json([
            'id'        => $media->id,
            'name'      => $media->name,
            'file_name' => $media->file_name,
            'url'       => $media->getUrl(),
            'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
            'size'      => $media->size,
            'mime_type' => $media->mime_type,
            'alt'       => $media->getCustomProperty('alt', ''),
            'caption'   => $media->getCustomProperty('caption', ''),
        ], 201);
    }

    /**
     * DELETE /admin/media/items/{media}
     */
    public function destroyMedia(Media $media)
    {
        abort_if($media->model_type === (new \App\Models\Project)->getMorphClass(), 422, __('projects.use_project_form'));
        $media->delete();

        if (request()->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('success', 'Image deleted.');
    }

    /**
     * PATCH /admin/media/items/{media}
     * Update custom properties: alt, caption, name.
     */
    public function updateMedia(Request $request, Media $media)
    {
        abort_if($media->model_type === (new \App\Models\Project)->getMorphClass(), 422, __('projects.use_project_form'));
        $validated = $request->validate([
            'name'    => ['nullable', 'string', 'max:255'],
            'alt'     => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
        ]);

        if (isset($validated['name'])) {
            $media->name = $validated['name'];
        }

        $media->setCustomProperty('alt', $validated['alt'] ?? $media->getCustomProperty('alt', ''));
        $media->setCustomProperty('caption', $validated['caption'] ?? $media->getCustomProperty('caption', ''));
        $media->save();

        return response()->json([
            'id'      => $media->id,
            'name'    => $media->name,
            'alt'     => $media->getCustomProperty('alt'),
            'caption' => $media->getCustomProperty('caption'),
        ]);
    }

    /**
     * POST /admin/media/{folder}/reorder
     * Body: { "ids": [3, 1, 2] }
     */
    public function reorder(Request $request, MediaFolder $mediaFolder)
    {
        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        Media::setNewOrder($request->input('ids'));

        return response()->json(['reordered' => true]);
    }

    /**
     * POST /admin/media/items/{media}/move
     * Move a media item to a different folder.
     * Body: { "folder_slug": "new-folder" }
     */
    public function moveMedia(Request $request, Media $media)
    {
        abort_if($media->model_type === (new \App\Models\Project)->getMorphClass(), 422, __('projects.use_project_form'));
        $request->validate([
            'folder_slug' => ['required', 'string', 'exists:media_folders,slug'],
        ]);

        $targetFolder = MediaFolder::where('slug', $request->input('folder_slug'))->firstOrFail();
        $media->move($targetFolder, 'images');

        return response()->json(['moved' => true, 'folder' => $targetFolder->name]);
    }
}
