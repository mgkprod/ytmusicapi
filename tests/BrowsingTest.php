<?php

namespace MGKProd\YTMusic\Tests;

use Exception;
use MGKProd\YTMusic\Facades\YTMusic;

class BrowsingTest extends TestCase
{
    public function test_search_fails()
    {
        $query = 'The Weekend';
        $this->expectException(Exception::class);

        YTMusic::browse()->search($query, 'song');
    }

    public function test_search()
    {
        $query = 'Daft Punk';

        $results = YTMusic::browse()->search($query);
        $this->assertGreaterThan(10, count($results));
    }

    public function test_search_filters()
    {
        $query = 'Oasis Wonderwall';

        $results = YTMusic::browse()->search($query, 'songs');
        $this->assertGreaterThan(10, count($results));

        $results = YTMusic::browse()->search($query, 'videos');
        $this->assertGreaterThan(5, count($results));

        $results = YTMusic::browse()->search($query, 'albums', 40);
        $this->assertGreaterThan(20, count($results));

        $results = YTMusic::browse()->search($query, 'artists');
        $this->assertGreaterThan(0, count($results));

        $results = YTMusic::browse()->search($query, 'playlists');
        $this->assertGreaterThan(5, count($results));
    }

    public function test_search_ignore_spelling()
    {
        $query = 'Magenta - Boum Bap';

        $results = YTMusic::browse()->search($query, null, 20, true);
        $this->assertGreaterThan(0, count($results));
    }

    public function test_get_artist()
    {
        $artist = YTMusic::browse()->artist('MPLAUCmMUZbaYdNH0bEd1PAlAqsA');
        $this->assertEquals(11, count($artist));

        $artist = YTMusic::browse()->artist('UCLZ7tlKC06ResyDmEStSrOw'); // no album year;
        $this->assertGreaterThanOrEqual(9, count($artist));

        $artist = YTMusic::browse()->artist('UCDAPd3S5CBIEKXn-tvy57Lg');  // no thumbnail, albums, subscribe count
        $this->assertGreaterThanOrEqual(9, count($artist));
    }

    public function test_get_artist_albums()
    {
        $artist = YTMusic::browse()->artist('UCAeLFBCQS7FvI8PvBrWvSBg');

        $results = YTMusic::browse()->artistAlbums(
            $artist['albums']['browseId'],
            $artist['albums']['params']
        );

        $this->assertGreaterThan(0, count($results));
    }

    public function test_get_artist_singles()
    {
        $artist = YTMusic::browse()->artist('UCAeLFBCQS7FvI8PvBrWvSBg');
        $results = YTMusic::browse()->artistAlbums(
            $artist['singles']['browseId'],
            $artist['singles']['params']
        );

        $this->assertGreaterThan(0, count($results));
    }

    public function test_get_user()
    {
        $user = YTMusic::browse()->user('UC44hbeRoCZVVMVg5z0FfIww');

        $this->assertEquals(3, count($user));
    }

    public function test_get_user_playlists()
    {
        $user = YTMusic::browse()->user('UCPVhZsC2od1xjGhgEc2NEPQ');
        $results = YTMusic::browse()->userPlaylists(
            'UCPVhZsC2od1xjGhgEc2NEPQ',
            $user['playlists']['params']
        );

        $this->assertGreaterThan(150, count($results));
    }

    public function test_get_album()
    {
        $results = YTMusic::browse()->album('MPREb_BQZvl3BFGay');
        $this->assertEquals(9, count($results));

        $results = YTMusic::browse()->album('MPREb_BQZvl3BFGay');
        $this->assertEquals(7, count($results['tracks']));
    }

    public function test_get_song()
    {
        $song = YTMusic::browse()->song('ZrOKjDZOtkA');
        $this->assertGreaterThanOrEqual(16, count($song));
    }

    public function test_get_streaming_data()
    {
        $streaming_data = YTMusic::browse()->streamingData('ZrOKjDZOtkA');
        $this->assertEquals(3, count($streaming_data));
    }

    public function test_get_lyrics()
    {
        $playlist = YTMusic::browse()->watchPlaylist('ZrOKjDZOtkA');
        $lyrics_song = YTMusic::browse()->lyrics($playlist['lyrics']);

        $this->assertTrue($lyrics_song['lyricsFound']);
        $this->assertNotNull($lyrics_song['lyrics']);
        $this->assertNotNull($lyrics_song['source']);

        $playlist = YTMusic::browse()->watchPlaylist('9TnpB8WgW4s');
        $no_lyrics_song = YTMusic::browse()->lyrics($playlist['lyrics']);

        $this->assertFalse($no_lyrics_song['lyricsFound']);
        $this->assertNotNull($no_lyrics_song['lyrics']);
        $this->assertNotNull($no_lyrics_song['source']);
    }
}
