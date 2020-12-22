<?php

namespace MGKProd\YTMusic;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MGKProd\YTMusic\Skeleton\SkeletonClass
 */
class YTMusicFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ytmusicapi';
    }
}
