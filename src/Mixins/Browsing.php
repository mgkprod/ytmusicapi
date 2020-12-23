<?php

namespace MGKProd\YTMusic\Mixins;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use MGKProd\YTMusic\Parsers\Browsing as ParsersBrowsing;
use MGKProd\YTMusic\Parsers\Paths;
use MGKProd\YTMusic\Parsers\Utils;

class Browsing
{
    protected $ytmusic;
    protected $parser;

    public function __construct($ytmusic)
    {
        $this->ytmusic = $ytmusic;
        $this->parser = new ParsersBrowsing();
    }

    public function search($query, $filter = null, $limit = 20, $ignore_spelling = false)
    {
        $body = ['query' => $query];
        $endpoint = 'search';
        $search_results = [];
        $filters = ['albums', 'artists', 'playlists', 'songs', 'videos'];

        if ($filter and ! in_array($filter, $filters)) {
            throw new Exception('Invalid filter provided. Please use one of the following filters or leave out the parameter: ' . ', ' . implode(', ', $filters));
        }

        if ($filter) {
            $param1 = 'Eg-KAQwIA';

            if (! $ignore_spelling) {
                $param3 = 'MABqChAEEAMQCRAFEAo%3D';
            } else {
                $param3 = 'MABCAggBagoQBBADEAkQBRAK';
            }

            if ($filter == 'videos') {
                $param2 = 'BABGAAgACgA';
            } elseif ($filter == 'albums') {
                $param2 = 'BAAGAEgACgA';
            } elseif ($filter == 'artists') {
                $param2 = 'BAAGAAgASgA';
            } elseif ($filter == 'playlists') {
                $param2 = 'BAAGAAgACgB';
            } else {
                $param2 = 'RAAGAAgACgA';
            }
            $params = $param1 . $param2 . $param3;

            $body['params'] = $params;
        } elseif ($ignore_spelling) {
            $body['params'] = 'QgIIAQ%3D%3D';
        }

        $response = $this->ytmusic->_send_request($endpoint, $body);

        if (! array_key_exists('contents', $response)) {
            // no results
            return $search_results;
        }

        if (array_key_exists('tabbedSearchResultsRenderer', $response['contents'])) {
            $results = $response['contents']['tabbedSearchResultsRenderer']['tabs'][(int) $filter == 'uploads']['tabRenderer']['content'];
        } else {
            $results = $response['contents'];
        }

        $results = data_get($results, Paths::SECTION_LIST);

        if (count($results) == 1 and array_key_exists('itemSectionRenderer', $results)) {
            // no results
            return $search_results;
        }

        foreach ($results as $res) {
            if (array_key_exists('musicShelfRenderer', $res)) {
                $results = $res['musicShelfRenderer']['contents'];

                $type = $filter
                    ? substr($filter, 0, -1)
                    : null;

                $search_results = array_merge($search_results, $this->parser->parseSearchResults($results, $type));

                if (array_key_exists('continuations', $res['musicShelfRenderer'])) {
                    $continuations = Utils::getContinuations(
                        $res['musicShelfRenderer'],
                        'musicShelfContinuation',
                        $limit - count($search_results),
                        fn ($additional_params) => $this->ytmusic->_send_request($endpoint, $body, $additional_params),
                        fn ($results) => $this->parser->parseSearchResults($results, $type)
                    );

                    $search_results = array_merge($search_results, $continuations);
                }
            }
        }

        return $search_results;
    }

    public function artist($channelId)
    {
        if (Str::startsWith($channelId, 'MPLA')) {
            $channelId = substr($channelId, 4);
        }

        $body = Utils::prepareBrowseEndpoint('ARTIST', $channelId);
        $endpoint = 'browse';

        $response = $this->ytmusic->_send_request($endpoint, $body);

        $results = data_get($response, Paths::SINGLE_COLUMN_TAB . '.' . Paths::SECTION_LIST);

        $artist = [
            'description' => null,
            'views' => null,
        ];

        $header = $response['header']['musicImmersiveHeaderRenderer'];

        $artist['name'] = data_get($header, Paths::TITLE_TEXT);

        $description_shelf = Utils::findObjectByKey($results, 'musicDescriptionShelfRenderer', null, true);
        if ($description_shelf) {
            $artist['description'] = $description_shelf['description']['runs'][0]['text'];

            if (! array_key_exists('subheader', $description_shelf)) {
                $artist['views'] = null;
            } else {
                $artist['views'] = $description_shelf['subheader']['runs'][0]['text'];
            }
        }

        $subscription_button = $header['subscriptionButton']['subscribeButtonRenderer'];
        $artist['channelId'] = $subscription_button['channelId'];
        $artist['subscribers'] = data_get($subscription_button, 'subscriberCountText.runs.0.text');
        $artist['subscribed'] = $subscription_button['subscribed'];
        $artist['thumbnails'] = data_get($header, Paths::THUMBNAILS);
        $artist['songs'] = ['browseId' => null];

        if (array_key_exists('musicShelfRenderer', $results[0])) {
            // API sometimes does not return songs

            $musicShelf = data_get($results, Paths::MUSIC_SHELF);

            if (array_key_exists('navigationEndpoint', data_get($musicShelf, Paths::TITLE))) {
                $artist['songs']['browseId'] = data_get($musicShelf, Paths::TITLE . '.' . Paths::NAVIGATION_BROWSE_ID);
            }

            $artist['songs']['results'] = $this->parser->parsePlaylistItems($musicShelf['contents']);
        }

        $artist = array_merge($artist, $this->parser->parseArtistContents($results));

        return $artist;
    }

    public function artistAlbums($channelId, $params)
    {
        $body = ['browseId' => $channelId, 'params' => $params];
        $endpoint = 'browse';

        $response = $this->ytmusic->_send_request($endpoint, $body);

        $artist = data_get($response['header']['musicHeaderRenderer'], Paths::TITLE_TEXT);
        $results = data_get($response, Paths::SINGLE_COLUMN_TAB . '.' . Paths::SECTION_LIST . '.' . Paths::MUSIC_SHELF);

        $albums = [];
        $release_type = mb_strtolower(data_get($results, Paths::TITLE_TEXT));

        foreach ($results['contents'] as $result) {
            $data = $result['musicResponsiveListItemRenderer'];
            $browseId = data_get($data, Paths::NAVIGATION_BROWSE_ID);
            $title = Utils::getItemText($data, 0);
            $thumbnails = data_get($data, Paths::THUMBNAILS);

            if ($release_type == 'albums') {
                $album_type = Utils::getItemText($data, 1);
                $year = Utils::getItemText($data, 1, 2);
            } else {
                $album_type = 'Single';
                $year = Utils::getItemText($data, 1, 0);
            }

            $albums[] = [
                'browseId' => $browseId,
                'artist' => $artist,
                'title' => $title,
                'thumbnails' => $thumbnails,
                'type' => $album_type,
                'year' => $year,
            ];
        }

        return $albums;
    }

    public function user($channelId)
    {
        $endpoint = 'browse';
        $body = ['browseId' => $channelId];
        $response = $this->ytmusic->_send_request($endpoint, $body);

        $user = ['name' => data_get($response, 'header.musicVisualHeaderRenderer.' . Paths::TITLE_TEXT)];
        $results = data_get($response, Paths::SINGLE_COLUMN_TAB . '.' . Paths::SECTION_LIST);

        $user = array_merge($user, $this->parser->parseArtistContents($results));

        return $user;
    }

    public function userPlaylists($channelId, $params)
    {
        $endpoint = 'browse';
        $body = ['browseId' => $channelId, 'params' => $params];
        $response = $this->ytmusic->_send_request($endpoint, $body);

        $data = data_get($response, Paths::SINGLE_COLUMN_TAB . '.' . Paths::SECTION_LIST . '.' . Paths::MUSIC_SHELF);
        $user_playlists = [];

        foreach ($data['contents'] as $result) {
            $data = $result['musicResponsiveListItemRenderer'];
            $user_playlists[] = [
                'browseId' => data_get($data, Paths::NAVIGATION_BROWSE_ID),
                'title' => Utils::getItemText($data, 0),
                'thumbnails' => data_get($data, Paths::THUMBNAILS),
            ];
        }

        return $user_playlists;
    }

    public function album($browseId)
    {
        $body = Utils::prepareBrowseEndpoint('ALBUM', $browseId);
        $endpoint = 'browse';
        $response = $this->ytmusic->_send_request($endpoint, $body);
        $data = data_get($response, Paths::FRAMEWORK_MUTATIONS);

        $album = [];
        $album_data = Utils::findObjectByKey($data, 'musicAlbumRelease', 'payload', true);
        $album['title'] = $album_data['title'];
        $album['trackCount'] = $album_data['trackCount'];
        $album['durationMs'] = $album_data['durationMs'];
        $album['playlistId'] = $album_data['audioPlaylistId'];
        $album['releaseDate'] = $album_data['releaseDate'];
        $album['description'] = Utils::findObjectByKey($data, 'musicAlbumReleaseDetail', 'payload', true)['description'];

        $album['thumbnails'] = $album_data['thumbnailDetails']['thumbnails'];

        $album['artist'] = [];
        $artists_data = Utils::findObjectsByKey($data, 'musicArtist', 'payload');
        foreach ($artists_data as $artist) {
            $album['artist'][] = [
                'name' => $artist['musicArtist']['name'],
                'id' => $artist['musicArtist']['externalChannelId'],
            ];
        }
        $album['tracks'] = [];

        foreach (array_slice($data, 3) as $item) {
            if (array_key_exists('musicTrack', $item['payload'])) {
                $track = [];
                $track['index'] = $item['payload']['musicTrack']['albumTrackIndex'];
                $track['title'] = $item['payload']['musicTrack']['title'];
                $track['thumbnails'] = $item['payload']['musicTrack']['thumbnailDetails']['thumbnails'];
                $track['artists'] = $item['payload']['musicTrack']['artistNames'];

                // in case the song is unavailable, there is no videoId
                if (array_key_exists('videoId', $item['payload']['musicTrack'])) {
                    $track['videoId'] = $item['payload']['musicTrack']['videoId'];
                } else {
                    $track['videoId'] = null;
                }

                // very occasionally lengthMs is not returned
                if (array_key_exists('lengthMs', $item['payload']['musicTrack'])) {
                    $track['lengthMs'] = $item['payload']['musicTrack']['videoId'];
                } else {
                    $track['lengthMs'] = null;
                }

                $album['tracks'][] = $track;
            }
        }

        return $album;
    }

    public function song($videoId)
    {
        $endpoint = 'https://www.youtube.com/get_video_info';
        $params = ['video_id' => $videoId, 'hl' => 'fr', 'el' => 'detailpage'];

        $response = Http::asJson()
            ->withHeaders($this->ytmusic->headers)
            ->get($endpoint . '?' . http_build_query($params));

        parse_str($response->body(), $text);

        if (! array_key_exists('player_response', $text)) {
            return $text;
        }

        $player_response = json_decode($text['player_response'], true);

        $song_meta = $player_response['videoDetails'];
        $song_meta['category'] = $player_response['microformat']['playerMicroformatRenderer']['category'];
        if (Str::endsWith($song_meta['shortDescription'], 'Auto-generated by YouTube.')) {
            try {
                $description = explode("\n\n", $song_meta['shortDescription']);
                foreach ($description as $i => $detail) {
                    $description[$i] = utf8_decode($detail);
                }

                $song_meta['provider'] = str_replace('Provided to YouTube by ', '', $description[0]);
                // $song_meta['artists'] = [artist for artist in description[1].split(' · ')[1:]]
                $song_meta['copyright'] = $description[3];
                // $song_meta['release'] = None if len(description) < 5 else description[4].replace('Released on: ', '')
                // song_meta['production'] = None if len(description) < 6 else [ pub for pub in description[5].split('\n') ]
            } catch (\Throwable $th) {
            }
        }

        return $song_meta;
    }

    public function streamingData($videoId)
    {
        $endpoint = 'https://www.youtube.com/get_video_info';
        $params = [
            'video_id' => $videoId,
            'hl' => 'fr',
            'el' => 'detailpage',
            'c' => 'WEB_REMIX',
            'cver' => '0.1',
        ];

        $response = Http::asJson()
            ->withHeaders($this->ytmusic->headers)
            ->get($endpoint . '?' . http_build_query($params));

        parse_str($response->body(), $text);

        if (! array_key_exists('player_response', $text)) {
            return $text;
        }

        $player_response = json_decode($text['player_response'], true);

        if (! array_key_exists('streamingData', $player_response)) {
            throw new Exception('This video is not playable.', 1);
        }

        return $player_response['streamingData'];
    }

    public function lyrics($browseId)
    {
        $lyrics = [];
        $response = $this->ytmusic->_send_request('browse', ['browseId' => $browseId]);

        if (array_key_exists('sectionListRenderer', $response['contents'])) {
            $lyrics['lyricsFound'] = true;
            $lyrics['lyrics'] = $response['contents']['sectionListRenderer']['contents'][0]['musicDescriptionShelfRenderer']['description']['runs'][0]['text'];
            $lyrics['source'] = $response['contents']['sectionListRenderer']['contents'][0]['musicDescriptionShelfRenderer']['footer']['runs'][0]['text'];
        } else {
            $lyrics['lyricsFound'] = false;
            $lyrics['lyrics'] = $response['contents']['messageRenderer']['subtext']['messageSubtextRenderer']['text']['runs'][0]['text'];
            $lyrics['source'] = $response['contents']['messageRenderer']['text']['runs'][0]['text'];
        }

        return $lyrics;
    }

    public function watchPlaylist($videoId = null, $playlistId = null, $limit = 25, $params = [])
    {
        $body = ['enablePersistentPlaylistPanel' => true, 'isAudioOnly' => true];

        if ($videoId) {
            $body['videoId'] = $videoId;
            $body['watchEndpointMusicSupportedConfigs'] = [
                'watchEndpointMusicConfig' => [
                    'hasPersistentPlaylistPanel' => true,
                    'musicVideoType' => 'MUSIC_VIDEO_TYPE_OMV',
                ],
            ];
        }

        $is_playlist = false;

        if ($playlistId) {
            $body['playlistId'] = $playlistId;
            $is_playlist = Str::startsWith($playlistId, 'PL');
        }

        if ($params) {
            $body['params'] = $params;
        }

        $endpoint = 'next';
        $response = $this->ytmusic->_send_request($endpoint, $body);

        $watchNextRenderer = data_get($response, 'contents.singleColumnMusicWatchNextResultsRenderer.tabbedRenderer.watchNextTabbedResultsRenderer');

        $lyrics_browse_id = null;
        if (count($watchNextRenderer['tabs']) > 1) {
            $lyrics_browse_id = $watchNextRenderer['tabs'][1]['tabRenderer']['endpoint']['browseEndpoint']['browseId'];
        }

        $results = data_get($watchNextRenderer, Paths::TAB_CONTENT . '.musicQueueRenderer.content.playlistPanelRenderer');
        $tracks = $this->parser->parseWatchPlaylist($results['contents']);

        if (array_key_exists('continuations', $results)) {
            $continuations = Utils::getContinuations(
                $results,
                'playlistPanelContinuation',
                $limit - count($tracks),
                fn ($additional_params) => $this->ytmusic->_send_request($endpoint, $body, $additional_params),
                fn ($results) => $this->parser->parseWatchPlaylist($results),
                $is_playlist ? '' : 'Radio'
            );

            $tracks = array_merge($tracks, $continuations);
        }

        return ['tracks' => $tracks, 'lyrics' => $lyrics_browse_id];
    }

    public function watchPlaylistShuffle($playlistId, $limit = 50)
    {
        return $this->watchPlaylist(null, $playlistId, $limit, 'wAEB8gECGAE%3D');
    }
}
