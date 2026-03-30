<?php

use App\Jobs\FetchFavicon;
use App\Models\Bucket;
use App\Models\Link;
use App\Models\User;
use App\Services\MetaFetchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\mock;

beforeEach(function () {
    Queue::fake();
    Http::fake();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->inbox = Bucket::factory()->inbox()->create();
});

test('refetch updates title and description always', function () {
    $link = Link::factory()->create([
        'bucket_id' => $this->inbox->id,
        'title' => 'Old Title',
        'description' => 'Old description',
    ]);

    mock(MetaFetchService::class)
        ->shouldReceive('fetch')
        ->once()
        ->andReturn(['title' => 'New Title', 'description' => 'New description', 'favicon_url' => null]);

    $this->post(route('dashboard.links.refetch-meta', $link))
        ->assertRedirect();

    expect($link->fresh())
        ->title->toBe('New Title')
        ->description->toBe('New description');
});

test('refetch overwrites manually set title', function () {
    $link = Link::factory()->create([
        'bucket_id' => $this->inbox->id,
        'title' => 'My custom title',
    ]);

    mock(MetaFetchService::class)
        ->shouldReceive('fetch')
        ->once()
        ->andReturn(['title' => 'Fetched Title', 'description' => null, 'favicon_url' => null]);

    $this->post(route('dashboard.links.refetch-meta', $link))
        ->assertRedirect();

    expect($link->fresh()->title)->toBe('Fetched Title');
});

test('refetch dispatches FetchFavicon when favicon url found', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    mock(MetaFetchService::class)
        ->shouldReceive('fetch')
        ->once()
        ->andReturn(['title' => 'Title', 'description' => null, 'favicon_url' => 'https://example.com/favicon.ico']);

    $this->post(route('dashboard.links.refetch-meta', $link))
        ->assertRedirect();

    Queue::assertPushed(FetchFavicon::class);
});

test('refetch flashes success when meta found', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    mock(MetaFetchService::class)
        ->shouldReceive('fetch')
        ->once()
        ->andReturn(['title' => 'Title', 'description' => null, 'favicon_url' => null]);

    $this->post(route('dashboard.links.refetch-meta', $link))
        ->assertRedirect()
        ->assertSessionHas('refetch_success');
});

test('refetch flashes failed when no meta found', function () {
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    mock(MetaFetchService::class)
        ->shouldReceive('fetch')
        ->once()
        ->andReturn(['title' => null, 'description' => null, 'favicon_url' => null]);

    $this->post(route('dashboard.links.refetch-meta', $link))
        ->assertRedirect()
        ->assertSessionHas('refetch_failed');
});

test('refetch leaves link unchanged when no meta found', function () {
    $link = Link::factory()->create([
        'bucket_id' => $this->inbox->id,
        'title' => 'Original',
        'description' => 'Original desc',
    ]);

    mock(MetaFetchService::class)
        ->shouldReceive('fetch')
        ->once()
        ->andReturn(['title' => null, 'description' => null, 'favicon_url' => null]);

    $this->post(route('dashboard.links.refetch-meta', $link))
        ->assertRedirect();

    expect($link->fresh())
        ->title->toBe('Original')
        ->description->toBe('Original desc');
});

test('refetch requires authentication', function () {
    auth()->logout();
    $link = Link::factory()->create(['bucket_id' => $this->inbox->id]);

    $this->post(route('dashboard.links.refetch-meta', $link))
        ->assertRedirect(route('login'));
});
