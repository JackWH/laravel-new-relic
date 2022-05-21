<?php

it('can test', function () {
    expect(true)->toBeTrue();
});

it('can find new relic configuration', function () {
    $this->assertTrue(count(config('new-relic')) > 0);
});
