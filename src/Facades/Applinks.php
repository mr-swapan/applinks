<?php

namespace MrSwapan\Applinks\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string create(array|string $data, string $webUrl = '')
 *
 * @see \MrSwapan\Applinks\Services\ApplinksService
 */
class Applinks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'applinks';
    }
}
