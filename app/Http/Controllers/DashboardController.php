<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $data = Cache::remember('dashboard', now()->addDay(), function () {
            $sevenDaysAgo = now()->subDays(7);

            $linkCount = Link::count();
            $tagCount = Tag::count();
            $publicTagCount = Tag::where('is_public', true)->count();
            $lastLinkDate = Link::max('created_at');

            $linkDelta = Link::where('created_at', '>=', $sevenDaysAgo)->count();
            $tagDelta = Tag::where('created_at', '>=', $sevenDaysAgo)->count();
            $publicTagDelta = Tag::where('is_public', true)->where('updated_at', '>=', $sevenDaysAgo)->count();

            $recentLinks = Link::with('bucket', 'tags')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (Link $link) => [
                    'id' => $link->id,
                    'url' => $link->url,
                    'title' => $link->title,
                    'favicon_url' => $link->getFirstMediaUrl('favicon') ?: null,
                    'bucket' => $link->bucket ? ['id' => $link->bucket->id, 'name' => $link->bucket->name, 'color' => $link->bucket->color] : null,
                    'tags' => $link->tags->map(fn (Tag $tag) => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color])->values()->all(),
                ])
                ->all();

            $tags = Tag::withCount('links')
                ->get()
                ->map(fn (Tag $tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'color' => $tag->color,
                    'is_public' => $tag->is_public,
                    'links_count' => $tag->links_count,
                    'updated_at' => $tag->updated_at?->toISOString(),
                ])
                ->all();

            return [
                'stats' => [
                    'links' => ['count' => $linkCount, 'delta' => $linkDelta],
                    'tags' => ['count' => $tagCount, 'delta' => $tagDelta],
                    'public_tags' => ['count' => $publicTagCount, 'delta' => $publicTagDelta],
                    'last_link_date' => $lastLinkDate,
                ],
                'recent_links' => $recentLinks,
                'tags' => $tags,
            ];
        });

        return Inertia::render('Dashboard', $data);
    }
}
