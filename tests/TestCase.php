<?php

namespace MGKProd\YTMusic\Tests;

use Illuminate\Support\Facades\Config;
use MGKProd\YTMusic\YTMusicServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set('app.locale', config('ytmusicapi.context.client.hl'));
    }

    protected function getPackageProviders($app)
    {
        return [
            YTMusicServiceProvider::class,
        ];
    }
}
