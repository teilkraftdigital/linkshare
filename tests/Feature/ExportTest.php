<?php

use App\Models\Bucket;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->inbox = Bucket::factory()->inbox()->create();
});

test('export requires authentication', function () {
    auth()->logout();

    $this->post(route('dashboard.export'))
        ->assertRedirect(route('login'));
});

test('export returns json file download', function () {
    $response = $this->post(route('dashboard.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/json');
    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment')
        ->toContain('linkshare-export-')
        ->toContain('.json');
});

test('export json contains required top-level fields', function () {
    $response = $this->post(route('dashboard.export'));

    $data = json_decode($response->getContent(), true);

    expect($data)
        ->toHaveKey('version', 1)
        ->toHaveKey('includes_notes', false)
        ->toHaveKey('exported_at')
        ->toHaveKey('buckets')
        ->toHaveKey('tags')
        ->toHaveKey('links');
});

test('export includes all active buckets tags and links by default', function () {
    $bucket = Bucket::factory()->create(['name' => 'Work']);
    $tag = Tag::factory()->create(['name' => 'dev']);
    $link = Link::factory()->create(['bucket_id' => $bucket->id, 'url' => 'https://example.com', 'title' => 'Example']);
    $link->tags()->attach($tag);

    $data = json_decode($this->post(route('dashboard.export'))->getContent(), true);

    $bucketNames = array_column($data['buckets'], 'name');
    expect($bucketNames)->toContain('Work');

    $tagNames = array_column($data['tags'], 'name');
    expect($tagNames)->toContain('dev');

    expect($data['links'])->toHaveCount(1);
    expect($data['links'][0])->toHaveKey('bucket', 'Work');
    expect($data['links'][0]['tags'])->toContain('dev');
});

test('export excludes soft deleted entries', function () {
    $bucket = Bucket::factory()->create();
    $bucket->delete();

    $tag = Tag::factory()->create();
    $tag->delete();

    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->delete();

    $data = json_decode($this->post(route('dashboard.export'))->getContent(), true);

    expect($data['buckets'])->toHaveCount(1); // only inbox
    expect($data['tags'])->toBeEmpty();
    expect($data['links'])->toBeEmpty();
});

test('export filename contains today date', function () {
    $response = $this->post(route('dashboard.export'));

    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toContain('linkshare-export-'.now()->toDateString().'.json');
});

test('export is valid json', function () {
    Link::factory()->count(3)->create(['bucket_id' => $this->inbox->id]);

    $content = $this->post(route('dashboard.export'))->getContent();

    expect(json_decode($content, true))->not->toBeNull();
    expect(json_last_error())->toBe(JSON_ERROR_NONE);
});

test('export with selected bucket_ids filters buckets and links', function () {
    $work = Bucket::factory()->create(['name' => 'Work']);
    $personal = Bucket::factory()->create(['name' => 'Personal']);
    Link::factory()->create(['bucket_id' => $work->id]);
    Link::factory()->create(['bucket_id' => $personal->id]);

    $data = json_decode(
        $this->post(route('dashboard.export'), ['bucket_ids' => [$work->id]])->getContent(),
        true,
    );

    $bucketNames = array_column($data['buckets'], 'name');
    expect($bucketNames)->toBe(['Work']);
    expect($data['links'])->toHaveCount(1);
    expect($data['links'][0]['bucket'])->toBe('Work');
});

test('export with selected tag_ids strips excluded tags from links', function () {
    $php = Tag::factory()->create(['name' => 'php']);
    $js = Tag::factory()->create(['name' => 'js']);
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);
    $link->tags()->attach([$php->id, $js->id]);

    $data = json_decode(
        $this->post(route('dashboard.export'), ['tag_ids' => [$php->id]])->getContent(),
        true,
    );

    $tagNames = array_column($data['tags'], 'name');
    expect($tagNames)->toBe(['php']);
    expect($data['links'][0]['tags'])->toBe(['php']);
});

test('export includes notes when includes_notes is true', function () {
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'notes' => 'private note']);

    $data = json_decode(
        $this->post(route('dashboard.export'), ['includes_notes' => true])->getContent(),
        true,
    );

    expect($data['includes_notes'])->toBeTrue();
    expect($data['links'][0]['notes'])->toBe('private note');
});

test('export notes are null when includes_notes is false', function () {
    Link::factory()->create(['bucket_id' => $this->inbox->id, 'notes' => 'private note']);

    $data = json_decode(
        $this->post(route('dashboard.export'), ['includes_notes' => false])->getContent(),
        true,
    );

    expect($data['includes_notes'])->toBeFalse();
    expect($data['links'][0]['notes'])->toBeNull();
});
