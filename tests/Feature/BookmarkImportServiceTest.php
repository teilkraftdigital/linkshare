<?php

use App\Jobs\FetchLinkMeta;
use App\Models\Bucket;
use App\Models\Link;
use App\Services\BookmarkImportService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->service = app(BookmarkImportService::class);
    $this->inbox = Bucket::factory()->inbox()->create();
});

// --- parse() ---

test('parses urls and titles from netscape bookmark html', function () {
    $html = <<<'HTML'
        <!DOCTYPE NETSCAPE-Bookmark-file-1>
        <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
        <TITLE>Bookmarks</TITLE>
        <H1>Bookmarks</H1>
        <DL><p>
            <DT><A HREF="https://laravel.com" ADD_DATE="1234567890">Laravel</A>
            <DT><A HREF="https://vuejs.org" ADD_DATE="1234567891">Vue.js</A>
        </DL>
        HTML;

    $bookmarks = $this->service->parse($html);

    expect($bookmarks)->toHaveCount(2);
    expect($bookmarks[0]['url'])->toBe('https://laravel.com');
    expect($bookmarks[0]['title'])->toBe('Laravel');
    expect($bookmarks[1]['url'])->toBe('https://vuejs.org');
    expect($bookmarks[1]['title'])->toBe('Vue.js');
});

test('returns empty array for empty file', function () {
    expect($this->service->parse(''))->toBeEmpty();
});

test('returns empty array for whitespace-only content', function () {
    expect($this->service->parse('   '))->toBeEmpty();
});

test('skips anchors without http urls', function () {
    $html = <<<'HTML'
        <DL>
            <DT><A HREF="javascript:void(0)">JS Link</A>
            <DT><A HREF="">Empty</A>
            <DT><A HREF="https://example.com">Valid</A>
        </DL>
        HTML;

    $bookmarks = $this->service->parse($html);

    expect($bookmarks)->toHaveCount(1);
    expect($bookmarks[0]['url'])->toBe('https://example.com');
});

test('uses url as title when title is empty', function () {
    $html = '<DL><DT><A HREF="https://example.com"></A></DL>';

    $bookmarks = $this->service->parse($html);

    expect($bookmarks[0]['title'])->toBe('https://example.com');
});

// --- import() ---

test('import returns correct counts for a clean import', function () {
    $html = <<<'HTML'
        <DL>
            <DT><A HREF="https://laravel.com">Laravel</A>
            <DT><A HREF="https://vuejs.org">Vue.js</A>
        </DL>
        HTML;

    $result = $this->service->import($html, $this->inbox->id);

    expect($result)->toBe(['imported' => 2, 'skipped' => 0, 'hints' => 0]);
    expect(Link::count())->toBe(2);
});

test('import skips duplicate against db', function () {
    Link::factory()->create(['url' => 'https://laravel.com', 'bucket_id' => $this->inbox->id]);

    $html = '<DL><DT><A HREF="https://laravel.com">Laravel</A></DL>';

    $result = $this->service->import($html, $this->inbox->id);

    expect($result)->toBe(['imported' => 0, 'skipped' => 1, 'hints' => 0]);
    expect(Link::count())->toBe(1);
});

test('import skips duplicate with trailing slash against db', function () {
    Link::factory()->create(['url' => 'https://laravel.com', 'bucket_id' => $this->inbox->id]);

    $html = '<DL><DT><A HREF="https://laravel.com/">Laravel</A></DL>';

    $result = $this->service->import($html, $this->inbox->id);

    expect($result)->toBe(['imported' => 0, 'skipped' => 1, 'hints' => 0]);
});

test('import deduplicates within file', function () {
    $html = <<<'HTML'
        <DL>
            <DT><A HREF="https://example.com">Example</A>
            <DT><A HREF="https://example.com">Example again</A>
        </DL>
        HTML;

    $result = $this->service->import($html, $this->inbox->id);

    expect($result)->toBe(['imported' => 1, 'skipped' => 1, 'hints' => 0]);
    expect(Link::count())->toBe(1);
});

test('import flags similar url with different query string as hint', function () {
    Link::factory()->create(['url' => 'https://example.com/page?ref=old', 'bucket_id' => $this->inbox->id]);

    $html = '<DL><DT><A HREF="https://example.com/page?ref=new">Example</A></DL>';

    $result = $this->service->import($html, $this->inbox->id);

    expect($result['imported'])->toBe(1);
    expect($result['hints'])->toBe(1);
    expect($result['skipped'])->toBe(0);
    expect(Link::count())->toBe(2);
});

test('import normalizes trailing slash before storing', function () {
    $html = '<DL><DT><A HREF="https://example.com/">Example</A></DL>';

    $this->service->import($html, $this->inbox->id);

    expect(Link::first()->url)->toBe('https://example.com');
});

test('import places links in given bucket', function () {
    $other = Bucket::factory()->create();
    $html = '<DL><DT><A HREF="https://example.com">Example</A></DL>';

    $this->service->import($html, $other->id);

    expect(Link::first()->bucket_id)->toBe($other->id);
});

test('import with empty html returns zeros', function () {
    $result = $this->service->import('', $this->inbox->id);

    expect($result)->toBe(['imported' => 0, 'skipped' => 0, 'hints' => 0]);
});

test('dispatches FetchLinkMeta job for each imported link', function () {
    $html = <<<'HTML'
        <DL>
            <DT><A HREF="https://laravel.com">Laravel</A>
            <DT><A HREF="https://vuejs.org">Vue.js</A>
        </DL>
        HTML;

    $this->service->import($html, $this->inbox->id);

    Queue::assertPushed(FetchLinkMeta::class, 2);
});

test('does not dispatch FetchLinkMeta job for skipped links', function () {
    Link::factory()->create(['url' => 'https://laravel.com', 'bucket_id' => $this->inbox->id]);

    $html = '<DL><DT><A HREF="https://laravel.com">Laravel</A></DL>';

    $this->service->import($html, $this->inbox->id);

    Queue::assertNothingPushed();
});
