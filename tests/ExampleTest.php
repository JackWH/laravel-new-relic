<?php

declare(strict_types=1);

it('can test', function (): void {
    $this->expect(true)->toBeTrue();
});

it('can find new relic configuration', function (): void {
    $this->assertTrue(count(config('new-relic')) > 0);
});
