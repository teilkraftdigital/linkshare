<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreTagRequest;
use App\Http\Requests\Dashboard\UpdateTagRequest;
use App\Models\Tag;
use App\Services\SlugGenerator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function __construct(private readonly SlugGenerator $slugGenerator) {}

    public function index(): Response
    {
        return Inertia::render('dashboard/Tags', [
            'tags' => Tag::withCount('links')->orderBy('name')->get(),
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
        // When a Link model exists, detach tag from all links:
        // $tag->links()->detach();

        $tag->delete();

        return back();
    }
}
