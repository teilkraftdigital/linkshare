<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreTagRequest;
use App\Http\Requests\Dashboard\UpdateTagRequest;
use App\Models\Tag;
use App\Services\SlugGenerator;
use Illuminate\Http\JsonResponse;
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

        if ($showTrashed) {
            $tags = Tag::onlyTrashed()->withCount('links')->orderBy('name')->get();
        } else {
            $tags = Tag::with([
                'children' => fn ($q) => $q->withCount('links')->orderBy('name'),
            ])
                ->whereNull('parent_id')
                ->withCount('links')
                ->orderBy('name')
                ->get();
        }

        $rootTags = $showTrashed
            ? collect()
            : Tag::whereNull('parent_id')->orderBy('name')->get(['id', 'name', 'color', 'is_public']);

        return Inertia::render('dashboard/Tags', [
            'tags' => $tags,
            'rootTags' => $rootTags,
            'showTrashed' => $showTrashed,
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $parentId = $validated['parent_id'] ?? null;

        if ($parentId) {
            $parent = Tag::findOrFail($parentId);
            $validated['color'] = $parent->color;
            $validated['is_public'] = $parent->is_public;
        }

        $validated['slug'] = $this->slugGenerator->generate($validated['name'], null, $parentId);

        $tag = Tag::create($validated);

        if ($request->wantsJson()) {
            return response()->json($tag, 201);
        }

        return back();
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validated();
        $newParentId = array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $tag->parent_id;

        // A tag with children cannot become a child itself
        if ($newParentId && $tag->children()->exists()) {
            return back()->withErrors(['parent_id' => __('tags.form.hasChildrenHint')]);
        }

        if ($newParentId) {
            $parent = Tag::findOrFail($newParentId);
            $validated['color'] = $parent->color;
            $validated['is_public'] = $parent->is_public;
        }

        $validated['slug'] = $this->slugGenerator->generate($validated['name'], $tag->id, $newParentId);

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
