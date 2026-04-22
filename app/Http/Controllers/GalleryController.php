<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\GalleryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $galleries = Gallery::withCount('items')->get();
        return view('master.galleries.index', compact('galleries'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('master.galleries.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:photo,video',
        ]);

        Gallery::create($request->all());

        return redirect()->route('master.galleries.index')->with('success', 'Galeri berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gallery $gallery)
    {
        return view('master.galleries.edit', compact('gallery'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Gallery $gallery)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:photo,video',
        ]);

        $gallery->update($request->all());

        return redirect()->route('master.galleries.index')->with('success', 'Galeri berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gallery $gallery)
    {
        // Delete physical files
        foreach ($gallery->items as $item) {
            Storage::disk('public')->delete($item->file_path);
        }
        $gallery->delete();

        return redirect()->route('master.galleries.index')->with('success', 'Galeri berhasil dihapus.');
    }

    /**
     * Set a gallery as active (only one active at a time).
     */
    public function setActive(Gallery $gallery)
    {
        Gallery::where('id', '!=', $gallery->id)->update(['is_active' => false]);
        $gallery->update(['is_active' => true]);

        return redirect()->route('master.galleries.index')->with('success', "Galeri '{$gallery->name}' sekarang aktif.");
    }

    /**
     * Manage items (photos/videos) within a gallery.
     */
    public function manageItems(Gallery $gallery)
    {
        $gallery->load('items');
        return view('master.galleries.items', compact('gallery'));
    }

    /**
     * Upload an item to the gallery with enhanced security.
     */
    public function storeItem(Request $request, Gallery $gallery)
    {
        // 1. Define strict MIME types and rules based on gallery type
        $rules = [];
        if ($gallery->type === 'photo') {
            $rules['file'] = [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:10240', // 10MB for high-res photos
                'dimensions:min_width=100,min_height=100' // Basic corruption check
            ];
        } else {
            $rules['file'] = [
                'required',
                'file',
                'mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo',
                'max:102400', // 100MB for video
            ];
        }

        $request->validate($rules + ['title' => 'nullable|string|max:100']);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // 2. Extra Security: Check for hidden scripts in the file content (simple scan)
            // Especially important if the server might execute files incorrectly
            if ($file->getMimeType() === 'text/x-php' || $file->getMimeType() === 'application/x-executable') {
                return back()->withErrors(['file' => 'Jenis file terlarang terdeteksi.']);
            }

            // 3. Store file with randomized name in isolated folder
            // We use store('galleries/' . $gallery->id) to isolate per-gallery if needed
            // But 'galleries' is enough as Laravel randomizes the hash
            $path = $file->store('galleries', 'public');

            // 4. Verify the file actually exists on disk after storage
            if (!Storage::disk('public')->exists($path)) {
                return back()->withErrors(['file' => 'Gagal mengunggah file ke server.']);
            }

            GalleryItem::create([
                'gallery_id' => $gallery->id,
                'file_path' => $path,
                'title' => strip_tags($request->title), // Basic XSS prevention on caption
                'order_weight' => $gallery->items()->count() + 1,
            ]);
        }

        return back()->with('success', 'Item berhasil diverifikasi dan ditambahkan ke galeri.');
    }

    /**
     * Delete an item from the gallery.
     */
    public function destroyItem(GalleryItem $item)
    {
        Storage::disk('public')->delete($item->file_path);
        $item->delete();

        return back()->with('success', 'Item berhasil dihapus.');
    }
}
