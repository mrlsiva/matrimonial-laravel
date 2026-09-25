<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index(): View
    {
        return view('admin.pages.index', ['pages' => CmsPage::orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new CmsPage(['is_active' => true, 'show_in_footer' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        CmsPage::create($this->validated($request));
        Cache::forget('footer_pages');

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(CmsPage $page): View
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, CmsPage $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));
        Cache::forget('footer_pages');

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(CmsPage $page): RedirectResponse
    {
        $page->delete();
        Cache::forget('footer_pages');

        return back()->with('success', 'Page deleted.');
    }

    private function validated(Request $request, ?CmsPage $page = null): array
    {
        $request->merge(['slug' => Str::slug($request->slug ?: $request->title)]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:160', Rule::unique('cms_pages', 'slug')->ignore($page?->id)],
            'content' => ['nullable', 'string', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // Optional side image (e.g. a portrait poster on About Us).
        unset($data['image']);
        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            if ($page?->image) {
                Storage::disk('public')->delete($page->image);
            }
            $data['image'] = $request->hasFile('image') ? $this->images->storePageImage($request->file('image')) : null;
        }

        // Admin-authored HTML: strip scripts and inline event handlers.
        $data['content'] = $this->sanitize($data['content'] ?? '');
        $data['is_active'] = $request->boolean('is_active');
        $data['show_in_footer'] = $request->boolean('show_in_footer');

        return $data;
    }

    private function sanitize(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|style)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);

        return preg_replace('#(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '$1="#"', $html);
    }
}
