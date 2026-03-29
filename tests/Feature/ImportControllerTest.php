<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Bucket::factory()->inbox()->create();
});

test('import page renders for authenticated user', function () {
    $this->get(route('dashboard.import.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/Import'));
});

test('guests are redirected from import page', function () {
    auth()->logout();

    $this->get(route('dashboard.import.create'))
        ->assertRedirect(route('login'));
});

test('import creates links from bookmark file', function () {
    $html = <<<'HTML'
        <!DOCTYPE NETSCAPE-Bookmark-file-1>
        <DL><p>
            <DT><A HREF="https://laravel.com">Laravel</A>
            <DT><A HREF="https://vuejs.org">Vue.js</A>
        </DL>
        HTML;

    $file = UploadedFile::fake()->createWithContent('bookmarks.html', $html);

    $this->post(route('dashboard.import.store'), ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('import_count', 2);

    expect(Link::count())->toBe(2);
    expect(Link::where('url', 'https://laravel.com')->exists())->toBeTrue();
    expect(Link::where('url', 'https://vuejs.org')->exists())->toBeTrue();
});

test('import places links in inbox bucket', function () {
    $html = '<DL><DT><A HREF="https://example.com">Example</A></DL>';
    $file = UploadedFile::fake()->createWithContent('bookmarks.html', $html);

    $this->post(route('dashboard.import.store'), ['file' => $file]);

    $link = Link::first();
    expect($link->bucket->is_inbox)->toBeTruthy();
});

test('import with empty file creates zero links', function () {
    $file = UploadedFile::fake()->createWithContent('bookmarks.html', '');

    $this->post(route('dashboard.import.store'), ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('import_count', 0);

    expect(Link::count())->toBe(0);
});

test('import requires a file', function () {
    $this->post(route('dashboard.import.store'), [])
        ->assertSessionHasErrors(['file']);
});

test('guests cannot post to import', function () {
    auth()->logout();

    $file = UploadedFile::fake()->createWithContent('bookmarks.html', '');

    $this->post(route('dashboard.import.store'), ['file' => $file])
        ->assertRedirect(route('login'));
});
