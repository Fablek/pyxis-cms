<?php

use App\Models\Page;
use App\Models\Setting;
use App\Services\FrontendRevalidator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'app.frontend_url' => 'http://frontend.test',
        'pyxis.revalidate.token' => 'shared-secret',
    ]);
});

it('sends the configured token and the page path to the frontend', function () {
    Http::fake(['frontend.test/*' => Http::response(['revalidated' => true])]);
    $page = Page::factory()->create(['slug' => 'kontakt']);

    expect(app(FrontendRevalidator::class)->revalidate($page))->toBeTrue();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://frontend.test/api/revalidate'
        && $request['secret'] === 'shared-secret'
        && $request['path'] === '/kontakt');
});

it('sends the root path for the homepage', function () {
    Http::fake(['frontend.test/*' => Http::response(['revalidated' => true])]);
    $homepage = Page::factory()->create(['slug' => null]);
    Setting::set('homepage_id', $homepage->id);

    app(FrontendRevalidator::class)->revalidate($homepage);

    Http::assertSent(fn (Request $request): bool => $request['path'] === '/');
});

it('logs and reports a rejected revalidation request', function () {
    Http::fake(['frontend.test/*' => Http::response(['message' => 'Invalid token'], 401)]);
    Log::spy();
    $page = Page::factory()->create();

    expect(app(FrontendRevalidator::class)->revalidate($page))->toBeFalse();

    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context): bool => $context['status'] === 401)->once();
});

it('logs and reports an unreachable frontend', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));
    Log::spy();
    $page = Page::factory()->create();

    expect(app(FrontendRevalidator::class)->revalidate($page))->toBeFalse();

    Log::shouldHaveReceived('warning')->once();
});
