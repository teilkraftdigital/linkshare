<?php

use App\Services\BookmarkImportService;

beforeEach(function () {
    $this->service = app(BookmarkImportService::class);
});

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
    $bookmarks = $this->service->parse('');

    expect($bookmarks)->toBeEmpty();
});

test('returns empty array for whitespace-only content', function () {
    $bookmarks = $this->service->parse('   ');

    expect($bookmarks)->toBeEmpty();
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
    $html = <<<'HTML'
        <DL>
            <DT><A HREF="https://example.com"></A>
        </DL>
        HTML;

    $bookmarks = $this->service->parse($html);

    expect($bookmarks[0]['title'])->toBe('https://example.com');
});
