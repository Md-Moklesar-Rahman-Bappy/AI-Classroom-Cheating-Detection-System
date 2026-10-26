<?php

use App\Services\LoginProtectionService;
use Illuminate\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('first failed login accepted and counted', function () {
    $service = new LoginProtectionService('test@example.com', '127.0.0.1');
    expect($service->isPairBlocked())->toBeFalse();
    $service->recordFailedAttempt();
    expect($service->isPairBlocked())->toBeFalse();
});

test('third failure triggers pair block', function () {
    $service = new LoginProtectionService('test@example.com', '127.0.0.1');
    $service->recordFailedAttempt();
    $service->recordFailedAttempt();
    expect($service->isPairBlocked())->toBeFalse();
    $service->recordFailedAttempt();
    expect($service->isPairBlocked())->toBeTrue();
});

test('different email from same IP follows independent pair policy', function () {
    $serviceA = new LoginProtectionService('a@example.com', '192.168.1.10');
    $serviceB = new LoginProtectionService('b@example.com', '192.168.1.10');
    $serviceA->recordFailedAttempt();
    $serviceA->recordFailedAttempt();
    $serviceA->recordFailedAttempt();
    expect($serviceA->isPairBlocked())->toBeTrue();
    expect($serviceB->isPairBlocked())->toBeFalse();
});

test('same email from another IP follows independent pair policy', function () {
    $serviceA = new LoginProtectionService('same@example.com', '10.0.0.1');
    $serviceB = new LoginProtectionService('same@example.com', '10.0.0.2');
    $serviceA->recordFailedAttempt();
    $serviceA->recordFailedAttempt();
    $serviceA->recordFailedAttempt();
    expect($serviceA->isPairBlocked())->toBeTrue();
    expect($serviceB->isPairBlocked())->toBeFalse();
});

test('successful login clears pair counter', function () {
    $service = new LoginProtectionService('clear@example.com', '127.0.0.1');
    $service->recordFailedAttempt();
    $service->recordFailedAttempt();
    $service->clearPairCounter();
    expect($service->isPairBlocked())->toBeFalse();
});

test('security event contains no password', function () {
    $service = new LoginProtectionService('nopass@example.com', '127.0.0.1');
    $service->recordFailedAttempt();
    $events = \DB::table('login_security_events')->where('normalized_email', 'nopass@example.com')->get();
    expect($events)->not->toBeEmpty();
    foreach ($events as $ev) {
        expect($ev->normalized_email)->not->toContain('password');
    }
});
