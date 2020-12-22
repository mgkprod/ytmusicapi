<?php

namespace MGKProd\YTMusic\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MGKProd\YTMusic\Skeleton\SkeletonClass
 */
class YTMusic extends Facade
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
