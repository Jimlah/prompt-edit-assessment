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

arch('application code does not use debug functions')
    ->expect(['dd', 'dump', 'var_dump'])
    ->not->toBeUsedIn('App');

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel();
