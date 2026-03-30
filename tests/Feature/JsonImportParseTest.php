<?php

use App\Models\Bucket;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Bucket::factory()->inbox()->create();
});

function makeJsonFile(array $data): UploadedFile
{
    $content = json_encode($data);
    $path = tempnam(sys_get_temp_dir(), 'linkshare_test_').'.json';
    file_put_contents($path, $content);

    return new UploadedFile($path, 'export.json', 'application/json', null, true);
}

function validExport(array $overrides = []): array
{
    return array_merge([
        'version' => 1,
        'exported_at' => '2026-01-01T00:00:00+00:00',
        'includes_notes' => false,
        'buckets' => [
            ['name' => 'Work', 'color' => 'blue', 'is_inbox' => false],
            ['name' => 'Inbox', 'color' => 'gray', 'is_inbox' => true],
        ],
        'tags' => [
            ['name' => 'php', 'slug' => 'php', 'color' => 'blue', 'description' => null, 'is_public' => false],
            ['name' => 'design', 'slug' => 'design', 'color' => 'red', 'description' => null, 'is_public' => true],
        ],
        'links' => [
            ['url' => 'https://example.com', 'title' => 'Example', 'description' => null, 'notes' => null, 'bucket' => 'Work', 'tags' => ['php']],
            ['url' => 'https://laravel.com', 'title' => 'Laravel', 'description' => null, 'notes' => null, 'bucket' => 'Inbox', 'tags' => []],
        ],
    ], $overrides);
}

test('parse requires authentication', function () {
    auth()->logout();

    $this->post(route('dashboard.import.json.parse'), [
        'file' => makeJsonFile(validExport()),
    ])->assertRedirect(route('login'));
});

test('parse returns buckets tags and link count', function () {
    $response = $this->post(route('dashboard.import.json.parse'), [
        'file' => makeJsonFile(validExport()),
    ]);

    $response->assertOk();
    $data = json_decode($response->getContent(), true);

    expect($data['buckets'])->toHaveCount(2);
    expect($data['tags'])->toHaveCount(2);
    expect($data['link_count'])->toBe(2);
});

test('parse returns bucket names and colors', function () {
    $data = json_decode(
        $this->post(route('dashboard.import.json.parse'), ['file' => makeJsonFile(validExport())])->getContent(),
        true,
    );

    expect($data['buckets'][0])->toMatchArray(['name' => 'Work', 'color' => 'blue', 'is_inbox' => false]);
    expect($data['buckets'][1])->toMatchArray(['name' => 'Inbox', 'color' => 'gray', 'is_inbox' => true]);
});

test('parse returns tag names and colors', function () {
    $data = json_decode(
        $this->post(route('dashboard.import.json.parse'), ['file' => makeJsonFile(validExport())])->getContent(),
        true,
    );

    expect($data['tags'][0])->toMatchArray(['name' => 'php', 'color' => 'blue']);
    expect($data['tags'][1])->toMatchArray(['name' => 'design', 'color' => 'red']);
});

test('parse returns 422 for invalid json structure', function () {
    $path = tempnam(sys_get_temp_dir(), 'linkshare_test_').'.json';
    file_put_contents($path, 'not valid json {{{');
    $file = new UploadedFile($path, 'export.json', 'application/json', null, true);

    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('dashboard.import.json.parse'), ['file' => $file]);

    $response->assertStatus(422);
    expect(json_decode($response->getContent(), true)['error'])->toBe('Ungültige JSON-Datei.');
});

test('parse returns 422 for wrong version', function () {
    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('dashboard.import.json.parse'), [
            'file' => makeJsonFile(validExport(['version' => 2])),
        ]);

    $response->assertStatus(422);
    expect(json_decode($response->getContent(), true)['error'])
        ->toBe('Nicht unterstütztes Export-Format (version ≠ 1).');
});

test('parse returns 422 when no file uploaded', function () {
    $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('dashboard.import.json.parse'))
        ->assertStatus(422);
});

test('parse handles export with no buckets tags or links', function () {
    $data = json_decode(
        $this->post(route('dashboard.import.json.parse'), [
            'file' => makeJsonFile(validExport(['buckets' => [], 'tags' => [], 'links' => []])),
        ])->getContent(),
        true,
    );

    expect($data['buckets'])->toBeEmpty();
    expect($data['tags'])->toBeEmpty();
    expect($data['link_count'])->toBe(0);
});
