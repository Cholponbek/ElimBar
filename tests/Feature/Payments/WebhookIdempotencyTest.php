<?php

use App\Domain\Payments\Services\WebhookGuard;
use App\Models\PaymentEvent;

/**
 * Провайдер пришлёт один и тот же колбэк дважды — это норма, а не сбой.
 * UNIQUE(provider, external_event_id) в БД, WebhookGuard — тонкая обёртка
 * над ним (см. ARCHITECTURE.md §6).
 */
it('records a webhook event on first delivery', function () {
    $guard = new WebhookGuard;

    $event = $guard->recordIfNew('elqr', 'evt_123', ['amount' => 50000]);

    expect($event)->toBeInstanceOf(PaymentEvent::class);
    expect(PaymentEvent::count())->toBe(1);
});

it('silently ignores a redelivered webhook with the same external_event_id', function () {
    $guard = new WebhookGuard;

    $first = $guard->recordIfNew('elqr', 'evt_123', ['amount' => 50000]);
    $second = $guard->recordIfNew('elqr', 'evt_123', ['amount' => 50000]);

    expect($first)->not->toBeNull();
    expect($second)->toBeNull('a redelivery must not create a second row or throw');
    expect(PaymentEvent::count())->toBe(1);
});

it('treats the same external_event_id from different providers as distinct events', function () {
    $guard = new WebhookGuard;

    $guard->recordIfNew('elqr', 'evt_123', []);
    $guard->recordIfNew('card-acquirer', 'evt_123', []);

    expect(PaymentEvent::count())->toBe(2);
});
