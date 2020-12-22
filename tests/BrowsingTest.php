<?php

namespace MGKProd\YTMusic\Tests;

use Exception;
use MGKProd\YTMusic\YTMusicFacade as YTMusic;

class BrowsingTest extends TestCase
{
    public function test_search_fails()
    {
        $query = 'The Weekend';
        $this->expectException(Exception::class);

        YTMusic::search($query, 'song');
    }

    public function test_search()
    {
        $query = 'Daft Punk';

        $results = YTMusic::search($query);
        $this->assertGreaterThan(10, count($results));
    }

    public function test_search_filters()
    {
        $query = 'Oasis Wonderwall';

        $results = YTMusic::search($query, 'songs');
        $this->assertGreaterThan(10, count($results));

        $results = YTMusic::search($query, 'videos');
        $this->assertGreaterThan(5, count($results));

        $results = YTMusic::search($query, 'albums', 40);
        $this->assertGreaterThan(20, count($results));

        $results = YTMusic::search($query, 'artists');
        $this->assertGreaterThan(0, count($results));

        $results = YTMusic::search($query, 'playlists');
        $this->assertGreaterThan(5, count($results));
    }

    public function test_search_ignore_spelling()
    {
        $query = 'Magenta - Boum Bap';

        $results = YTMusic::search($query, null, 20, true);
        $this->assertGreaterThan(0, count($results));
    }

    public function test_get_artist()
    {
        // $results = youtube.get_artist("MPLAUCmMUZbaYdNH0bEd1PAlAqsA");
        // $this->assertEqual(len(results), 11);
        // $results = youtube.get_artist("UCLZ7tlKC06ResyDmEStSrOw")  # no album year;
        // $this->assertGreaterEqual(len(results), 9);
        // $results = youtube.get_artist(;
        //     "UCDAPd3S5CBIEKXn-tvy57Lg")  # no thumbnail, albums, subscribe count
        // $this->assertGreaterEqual(len(results), 9);
    }

    public function test_get_artist_albums()
    {
        // artist = youtube.get_artist("UCAeLFBCQS7FvI8PvBrWvSBg")
        // $results = youtube.get_artist_albums(artist['albums']['browseId'],;
        //                                     artist['albums']['params'])
        // $this->assertGreater(len(results), 0);
    }

    public function test_get_artist_singles()
    {
        // artist = youtube_auth.get_artist("UCAeLFBCQS7FvI8PvBrWvSBg")
        // $results = youtube_auth.get_artist_albums(artist['singles']['browseId'],;
        //                                          artist['singles']['params'])
        // $this->assertGreater(len(results), 0);
    }

    public function test_get_user()
    {
        // $results = youtube.get_user("UC44hbeRoCZVVMVg5z0FfIww");
        // $this->assertEqual(len(results), 3);
    }

    public function test_get_user_playlists()
    {
        // $results = youtube.get_user("UCPVhZsC2od1xjGhgEc2NEPQ");
        // $results = youtube.get_user_playlists("UCPVhZsC2od1xjGhgEc2NEPQ",;
        //                                      results['playlists']['params'])
        // $this->assertGreater(len(results), 200);
    }

    public function test_get_album()
    {
        // $results = youtube_auth.get_album("MPREb_BQZvl3BFGay");
        // $this->assertEqual(len(results), 9);
        // $this->assertIn('feedbackTokens', results['tracks'][0]);
        // $results = youtube.get_album("MPREb_BQZvl3BFGay");
        // $this->assertEqual(len(results['tracks']), 7);
    }

    public function test_get_song()
    {
        // song = youtube.get_song("ZrOKjDZOtkA")
        // $this->assertGreaterEqual(len(song), 16);
    }

    public function test_get_streaming_data()
    {
        // streaming_data = youtube_auth.get_streaming_data("ZrOKjDZOtkA")
        // $this->assertEqual(len(streaming_data), 3);
    }

    public function test_get_lyrics()
    {
        // playlist = youtube.get_watch_playlist("ZrOKjDZOtkA")
        // lyrics_song = youtube.get_lyrics(playlist["lyrics"])
        // $this->assertTrue(lyrics_song["lyricsFound"]);
        // $this->assertIsNotNone(lyrics_song["lyrics"]);
        // $this->assertIsNotNone(lyrics_song["source"]);
        // playlist = youtube.get_watch_playlist("9TnpB8WgW4s")
        // no_lyrics_song = youtube.get_lyrics(playlist["lyrics"])
        // $this->assertFalse(no_lyrics_song["lyricsFound"]);
        // $this->assertIsNotNone(no_lyrics_song["lyrics"]);
        // $this->assertIsNotNone(no_lyrics_song["source"]);
    }
}
