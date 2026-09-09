<?php

namespace App\Http\Controllers;

use App\Models\WebsiteAbout;
use App\Models\WebsiteGallery;
use App\Models\WebsiteService;
use App\Traits\AjaxResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebsiteContentController extends Controller
{
    use \App\Traits\AjaxResponse;

    /**
     * Show About Us editing page.
     */
    public function aboutEdit()
    {
        $about = WebsiteAbout::first() ?? new WebsiteAbout();
        return view('admin.website.about', compact('about'));
    }

    /**
     * Update About Us content.
     */
    public function aboutUpdate(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'mission' => 'nullable|string',
            'vision' => 'nullable|string',
        ]);

        WebsiteAbout::updateOrCreate(['id' => 1], $validated);

        return redirect()->back()->with('success', 'About Us content updated successfully.');
    }

    /**
     * List website services.
     */
    public function servicesIndex()
    {
        $services = WebsiteService::orderBy('sort_order')->get();
        return view('admin.website.services', compact('services'));
    }

    /**
     * Store new service.
     */
    public function servicesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'icon' => 'required|string|max:50',
        ]);

        $validated['sort_order'] = WebsiteService::max('sort_order') + 1;
        WebsiteService::create($validated);

        return redirect()->back()->with('success', 'Service added successfully.');
    }

    /**
     * Update existing service.
     */
    public function servicesUpdate(Request $request, WebsiteService $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'icon' => 'required|string|max:50',
        ]);

        $service->update($validated);

        return redirect()->back()->with('success', 'Service updated successfully.');
    }

    /**
     * Delete service.
     */
    public function servicesDestroy(WebsiteService $service)
    {
        $service->delete();
        return redirect()->back()->with('success', 'Service deleted successfully.');
    }

    /**
     * List gallery photos.
     */
    public function galleryIndex()
    {
        $photos = WebsiteGallery::orderBy('sort_order')->get();
        return view('admin.website.gallery', compact('photos'));
    }

    /**
     * Upload photo to gallery.
     */
    public function galleryUpload(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:5120', // Max 5MB
            'caption' => 'nullable|string|max:200',
        ]);

        $path = $request->file('photo')->store('website/gallery', 'public');

        WebsiteGallery::create([
            'hotel_id' => active_hotel_id(),
            'image_path' => $path,
            'caption' => $request->caption,
            'sort_order' => WebsiteGallery::max('sort_order') + 1,
        ]);

        return redirect()->back()->with('success', 'Photo uploaded successfully.');
    }

    /**
     * Update photo caption/status.
     */
    public function galleryUpdate(Request $request, WebsiteGallery $photo)
    {
        $validated = $request->validate([
            'caption' => 'nullable|string|max:200',
            'is_active' => 'boolean',
        ]);

        $photo->update($validated);

        return redirect()->back()->with('success', 'Gallery photo updated successfully.');
    }

    /**
     * Delete photo from gallery.
     */
    public function galleryDestroy(WebsiteGallery $photo)
    {
        Storage::disk('public')->delete($photo->image_path);
        $photo->delete();

        return redirect()->back()->with('success', 'Photo deleted successfully.');
    }

    /**
     * Reorder gallery photos (AJAX).
     */
    public function galleryReorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:website_galleries,id',
        ]);

        try {
            foreach ($request->ids as $index => $id) {
                WebsiteGallery::where('id', $id)->update(['sort_order' => $index]);
            }

            return $this->ajaxSuccess('Gallery order updated successfully.');
        } catch (\Exception $e) {
            if ($this->isAjaxRequest()) return $this->ajaxError($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
