<?php

namespace Lintaba\LaravelNexudus\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Lintaba\LaravelNexudus\Nexudus
 */
class Nexudus extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Lintaba\LaravelNexudus\Nexudus::class;
    }
}
