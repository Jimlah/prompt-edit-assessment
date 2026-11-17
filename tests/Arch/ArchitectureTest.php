<?php

use Illuminate\Foundation\Http\FormRequest;

arch('application classes use correct casing')
    ->expect('App')
    ->toBeCasedCorrectly();

arch('controllers use the Controller suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('form requests extend the base form request')
    ->expect('App\Http\Requests')
    ->toExtend(FormRequest::class);

arch('all exception classes live in the Exceptions namespace')
    ->expect('App')
    ->not->toImplement(Throwable::class)
    ->ignoring('App\Exceptions');

arch('everything in the Exceptions namespace is an exception')
    ->expect('App\Exceptions')
    ->classes()
    ->toImplement(Throwable::class);

arch('all enums live in the Enums namespace')
    ->expect('App')
    ->not->toBeEnums()
    ->ignoring('App\Enums');

arch('everything in the Enums namespace is an enum')
    ->expect('App\Enums')
    ->toBeEnums();

arch('application code does not use debug functions')
    ->expect(['dd', 'dump', 'var_dump'])
    ->not->toBeUsedIn('App');

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();
