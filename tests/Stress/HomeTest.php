<?php

use function Pest\Stressless\stress;

test('home page remains available under light load', function (): void {
    $result = stress('http://prompt-edit-assessment.test')
        ->concurrently(requests: 2)
        ->for(2)
        ->seconds();

    expect($result->requests()->count())->toBeGreaterThan(0)
        ->and($result->requests()->failed()->count())->toBe(0);
});
