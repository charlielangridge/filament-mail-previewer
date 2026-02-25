<?php

namespace CharlieLangridge\FilamentMailPreviewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CharlieLangridge\FilamentMailPreviewer\FilamentMailPreviewer
 */
class FilamentMailPreviewer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CharlieLangridge\FilamentMailPreviewer\FilamentMailPreviewer::class;
    }
}
