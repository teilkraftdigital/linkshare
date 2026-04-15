<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;

beforeEach(function () {
    Bucket::factory()->inbox()->create();
});

test('export returns netscape html file', function () {
    $tag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php', 'is_public' => true]);

    $this->get(route('tags.export', $tag->slug))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="bookmarks_linkshare_'.now()->toDateString().'.html"');
});

test('export returns 404 for private tag', function () {
    $tag = Tag::factory()->create(['slug' => 'private', 'is_public' => false]);

    $this->get(route('tags.export', $tag->slug))->assertNotFound();
});

test('export contains link url and title', function () {
    $tag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php', 'is_public' => true]);
    $bucket = Bucket::factory()->create();
    $link = Link::factory()->create(['url' => 'https://laravel.com', 'title' => 'Laravel', 'bucket_id' => $bucket->id]);
    $link->tags()->attach($tag);

    $content = $this->get(route('tags.export', $tag->slug))->getContent();

    expect($content)->toContain('HREF="https://laravel.com"');
    expect($content)->toContain('>Laravel</A>');
});

test('export contains description when present', function () {
    $tag = Tag::factory()->create(['slug' => 'php', 'is_public' => true]);
    $bucket = Bucket::factory()->create();
    $link = Link::factory()->create(['description' => 'A great framework', 'bucket_id' => $bucket->id]);
    $link->tags()->attach($tag);

    $content = $this->get(route('tags.export', $tag->slug))->getContent();

    expect($content)->toContain('<DD>A great framework');
});

test('export omits description when null', function () {
    $tag = Tag::factory()->create(['slug' => 'php', 'is_public' => true]);
    $bucket = Bucket::factory()->create();
    $link = Link::factory()->create(['description' => null, 'bucket_id' => $bucket->id]);
    $link->tags()->attach($tag);

    $content = $this->get(route('tags.export', $tag->slug))->getContent();

    expect($content)->not->toContain('<DD>');
});

test('export wraps links in folder named after tag', function () {
    $tag = Tag::factory()->create(['name' => 'My Tag', 'slug' => 'my-tag', 'is_public' => true]);

    $content = $this->get(route('tags.export', $tag->slug))->getContent();

    expect($content)->toContain('<H2>My Tag</H2>');
});

test('export links are sorted alphabetically by title', function () {
    $tag = Tag::factory()->create(['slug' => 'php', 'is_public' => true]);
    $bucket = Bucket::factory()->create();

    $linkB = Link::factory()->create(['title' => 'Banana', 'bucket_id' => $bucket->id]);
    $linkA = Link::factory()->create(['title' => 'Apple', 'bucket_id' => $bucket->id]);
    $linkC = Link::factory()->create(['title' => 'Cherry', 'bucket_id' => $bucket->id]);
    $tag->links()->attach([$linkA->id, $linkB->id, $linkC->id]);

    $content = $this->get(route('tags.export', $tag->slug))->getContent();

    $posA = strpos($content, 'Apple');
    $posB = strpos($content, 'Banana');
    $posC = strpos($content, 'Cherry');

    expect($posA)->toBeLessThan($posB);
    expect($posB)->toBeLessThan($posC);
});

test('export contains add_date as unix timestamp', function () {
    $tag = Tag::factory()->create(['slug' => 'php', 'is_public' => true]);
    $bucket = Bucket::factory()->create();
    $link = Link::factory()->create(['bucket_id' => $bucket->id]);
    $link->tags()->attach($tag);

    $content = $this->get(route('tags.export', $tag->slug))->getContent();

    expect($content)->toContain('ADD_DATE="'.$link->created_at->timestamp.'"');
});

test('export requires no authentication', function () {
    $tag = Tag::factory()->create(['slug' => 'php', 'is_public' => true]);

    $this->get(route('tags.export', $tag->slug))->assertOk();
});

test('export creates nested sub-tag folder inside root folder', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'slug' => 'vue', 'is_public' => true]);
    $child = Tag::factory()->childOf($parent)->create(['name' => 'Pinia', 'slug' => 'pinia']);
    $bucket = Bucket::factory()->create();

    $childLink = Link::factory()->create(['title' => 'Pinia Docs', 'url' => 'https://pinia.vuejs.org', 'bucket_id' => $bucket->id]);
    $childLink->tags()->attach($child);

    $content = $this->get(route('tags.export', $parent->slug))->getContent();

    $posRoot = strpos($content, '<H3>Vue</H3>');
    $posChild = strpos($content, '<H3>Pinia</H3>');

    expect($posRoot)->toBeLessThan($posChild);
    expect($content)->toContain('>Pinia Docs</A>');
});

test('export nested sub-tag folder appears inside root folder', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'slug' => 'vue', 'is_public' => true]);
    $child = Tag::factory()->childOf($parent)->create(['name' => 'Pinia']);
    $bucket = Bucket::factory()->create();

    $childLink = Link::factory()->create(['bucket_id' => $bucket->id]);
    $childLink->tags()->attach($child);

    $content = $this->get(route('tags.export', $parent->slug))->getContent();

    // The root DL should close after the child folder (nested structure)
    $posRootDlOpen = strpos($content, '<DT><H3>Vue</H3>');
    $posChildFolder = strpos($content, '<H3>Pinia</H3>');
    $posRootDlClose = strrpos($content, '</DL><p>');

    expect($posRootDlOpen)->toBeLessThan($posChildFolder);
    expect($posChildFolder)->toBeLessThan($posRootDlClose);
});

test('export includes direct root links alongside sub-tag folders', function () {
    $parent = Tag::factory()->create(['name' => 'Vue', 'slug' => 'vue', 'is_public' => true]);
    $child = Tag::factory()->childOf($parent)->create(['name' => 'Pinia']);
    $bucket = Bucket::factory()->create();

    $rootLink = Link::factory()->create(['title' => 'Vue Docs', 'bucket_id' => $bucket->id]);
    $rootLink->tags()->attach($parent);

    $childLink = Link::factory()->create(['title' => 'Pinia Guide', 'bucket_id' => $bucket->id]);
    $childLink->tags()->attach($child);

    $content = $this->get(route('tags.export', $parent->slug))->getContent();

    expect($content)->toContain('>Vue Docs</A>');
    expect($content)->toContain('>Pinia Guide</A>');
});
