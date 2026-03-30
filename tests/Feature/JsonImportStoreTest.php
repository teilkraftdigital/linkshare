<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Bucket::factory()->inbox()->create();
});

function makeImportFile(array $data): UploadedFile
{
    $content = json_encode($data);
    $path = tempnam(sys_get_temp_dir(), 'linkshare_import_').'.json';
    file_put_contents($path, $content);

    return new UploadedFile($path, 'export.json', 'application/json', null, true);
}

function validImportExport(array $overrides = []): array
{
    return array_merge([
        'version' => 1,
        'exported_at' => '2026-01-01T00:00:00+00:00',
        'includes_notes' => false,
        'buckets' => [
            ['name' => 'Work', 'color' => 'blue', 'is_inbox' => false],
        ],
        'tags' => [
            ['name' => 'php', 'slug' => 'php', 'color' => 'blue', 'description' => null, 'is_public' => false],
        ],
        'links' => [
            ['url' => 'https://example.com', 'title' => 'Example', 'description' => null, 'notes' => null, 'bucket' => 'Work', 'tags' => ['php']],
        ],
    ], $overrides);
}

test('store requires authentication', function () {
    auth()->logout();

    $this->post(route('dashboard.import.json.store'), [
        'file' => makeImportFile(validImportExport()),
    ])->assertRedirect(route('login'));
});

test('store requires file', function () {
    $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('dashboard.import.json.store'))
        ->assertStatus(422);
});

test('store imports link bucket and tag', function () {
    Queue::fake();

    $this->post(route('dashboard.import.json.store'), [
        'file' => makeImportFile(validImportExport()),
        'bucket_names' => ['Work'],
        'tag_names' => ['php'],
    ])->assertRedirect(route('dashboard.import.create'));

    expect(Bucket::where('name', 'Work')->exists())->toBeTrue();
    expect(Tag::where('name', 'php')->exists())->toBeTrue();
    expect(Link::where('url', 'https://example.com')->exists())->toBeTrue();

    $link = Link::where('url', 'https://example.com')->first();
    expect($link->bucket->name)->toBe('Work');
    expect($link->tags->pluck('name')->toArray())->toContain('php');
});

test('store flashes json_import_result', function () {
    Queue::fake();

    $this->post(route('dashboard.import.json.store'), [
        'file' => makeImportFile(validImportExport()),
        'bucket_names' => ['Work'],
        'tag_names' => ['php'],
    ])->assertSessionHas('json_import_result');
});

test('store returns correct counts', function () {
    Queue::fake();

    $this->post(route('dashboard.import.json.store'), [
        'file' => makeImportFile(validImportExport()),
        'bucket_names' => ['Work'],
        'tag_names' => ['php'],
    ]);

    $result = session('json_import_result');
    expect($result['imported'])->toBe(1);
    expect($result['skipped'])->toBe(0);
    expect($result['buckets_created'])->toBe(1);
    expect($result['tags_created'])->toBe(1);
});

test('store skips duplicate links', function () {
    Queue::fake();

    Link::factory()->create(['url' => 'https://example.com']);

    $this->post(route('dashboard.import.json.store'), [
        'file' => makeImportFile(validImportExport()),
        'bucket_names' => ['Work'],
        'tag_names' => ['php'],
    ]);

    $result = session('json_import_result');
    expect($result['imported'])->toBe(0);
    expect($result['skipped'])->toBe(1);
    expect(Link::where('url', 'https://example.com')->count())->toBe(1);
});

test('store merges existing bucket by name', function () {
    Queue::fake();

    Bucket::factory()->create(['name' => 'Work', 'color' => 'red']);

    $this->post(route('dashboard.import.json.store'), [
        'file' => makeImportFile(validImportExport()),
        'bucket_names' => ['Work'],
        'tag_names' => ['php'],
    ]);

    $result = session('json_import_result');
    expect($result['buckets_created'])->toBe(0);
    expect(Bucket::where('name', 'Work')->count())->toBe(1);
});

test('store merges existing tag by name', function () {
    Queue::fake();

    Tag::factory()->create(['name' => 'php', 'slug' => 'php']);

    $this->post(route('dashboard.import.json.store'), [
        'file' => makeImportFile(validImportExport()),
        'bucket_names' => ['Work'],
        'tag_names' => ['php'],
    ]);

    $result = session('json_import_result');
    expect($result['tags_created'])->toBe(0);
    expect(Tag::where('name', 'php')->count())->toBe(1);
});

test('store skips links for excluded buckets', function () {
    Queue::fake();

    $export = validImportExport([
        'buckets' => [
            ['name' => 'Work', 'color' => 'blue', 'is_inbox' => false],
            ['name' => 'Personal', 'color' => 'green', 'is_inbox' => false],
        ],
        'links' => [
            ['url' => 'https://work.com', 'title' => 'Work', 'description' => null, 'notes' => null, 'bucket' => 'Work', 'tags' => []],
            ['url' => 'https://personal.com', 'title' => 'Personal', 'description' => null, 'notes' => null, 'bucket' => 'Personal', 'tags' => []],
        ],
    ]);

    $this->post(route('dashboard.import.json.store'), [
        'file' => makeImportFile($export),
        'bucket_names' => ['Work'],
        'tag_names' => [],
    ]);

    expect(Link::where('url', 'https://work.com')->exists())->toBeTrue();
    expect(Link::where('url', 'https://personal.com')->exists())->toBeFalse();
});

test('store strips excluded tags from links', function () {
    Queue::fake();

    $export = validImportExport([
        'tags' => [
            ['name' => 'php', 'slug' => 'php', 'color' => 'blue', 'description' => null, 'is_public' => false],
            ['name' => 'design', 'slug' => 'design', 'color' => 'red', 'description' => null, 'is_public' => false],
        ],
        'links' => [
            ['url' => 'https://example.com', 'title' => 'Example', 'description' => null, 'notes' => null, 'bucket' => 'Work', 'tags' => ['php', 'design']],
        ],
    ]);

    $this->post(route('dashboard.import.json.store'), [
        'file' => makeImportFile($export),
        'bucket_names' => ['Work'],
        'tag_names' => ['php'],
    ]);

    $link = Link::where('url', 'https://example.com')->first();
    $tagNames = $link->tags->pluck('name')->toArray();
    expect($tagNames)->toContain('php');
    expect($tagNames)->not->toContain('design');
});

test('store rejects invalid json', function () {
    $this->post(route('dashboard.import.json.store'), [
        'file' => makeImportFile(['version' => 2]),
    ])->assertSessionHasErrors('file');
});
