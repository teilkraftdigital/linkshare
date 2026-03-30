<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreTagRequest;
use App\Http\Requests\Dashboard\UpdateTagRequest;
use App\Models\Tag;
use App\Services\SlugGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function __construct(private readonly SlugGenerator $slugGenerator) {}

    public function index(Request $request): Response
    {
        $showTrashed = $request->boolean('trashed');

        $tags = $showTrashed
            ? Tag::onlyTrashed()->withCount('links')->orderBy('name')->get()
            : Tag::withCount('links')->orderBy('name')->get();

        return Inertia::render('dashboard/Tags', [
            'tags' => $tags,
            'showTrashed' => $showTrashed,
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = $this->slugGenerator->generate($validated['name']);

        Tag::create($validated);

        return back();
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = $this->slugGenerator->generate($validated['name'], $tag->id);

        $tag->update($validated);

        return back();
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return back();
    }

    public function restore(Tag $tag): RedirectResponse
    {
        $tag->restore();

        return back();
    }

    public function forceDelete(Tag $tag): RedirectResponse
    {
        $tag->links()->detach();
        $tag->forceDelete();

        return back();
    }
}
