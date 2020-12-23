<?php

namespace MGKProd\YTMusic\Parsers;

class Browsing
{
    public function parseSearchResults($results, $result_type = null)
    {
        $search_results = [];
        $default_offset = (! $result_type) * 2;

        foreach ($results as $result) {
            $data = $result['musicResponsiveListItemRenderer'];
            $search_result = [];

            if (! $result_type) {
                $result_type = mb_strtolower(Utils::getItemText($data, 1));

                $result_types_local = [
                    trans('ytmusicapi::types.artist') => 'artist',
                    trans('ytmusicapi::types.playlist') => 'playlist',
                    trans('ytmusicapi::types.song') => 'song',
                    trans('ytmusicapi::types.video') => 'video',
                ];

                // default to album since it's labeled with multiple values ('Single', 'EP', etc.)
                if (! array_key_exists($result_type, $result_types_local)) {
                    $result_type = 'album';
                } else {
                    $result_type = $result_types_local[$result_type];
                }
            }

            $search_result['resultType'] = $result_type;
            $last_artist_index = $default_offset;
            $runs = [];

            if (in_array($result_type, ['song', 'video'])) {
                $search_result['videoId'] = data_get($data, Paths::PLAY_BUTTON . '.playNavigationEndpoint.watchEndpoint.videoId');
                $search_result['title'] = Utils::getItemText($data, 0);

                $runs = Utils::getFlexColumnItem($data, 1)['text']['runs'];
                $search_result['artists'] = Utils::parseSongArtistsRuns($runs);
            }

            if (in_array($result_type, ['artist', 'album', 'playlist'])) {
                $search_result['browseId'] = data_get($data, Paths::NAVIGATION_BROWSE_ID);
                if (! $search_result['browseId']) {
                    continue;
                }
            }

            if (in_array($result_type, ['artist'])) {
                $search_result['artist'] = Utils::getItemText($data, 0);
            } elseif (in_array($result_type, ['album'])) {
                $search_result['title'] = Utils::getItemText($data, 0);
                $search_result['type'] = Utils::getItemText($data, 1);
                $search_result['artist'] = Utils::getItemText($data, 1, 2);
                $search_result['year'] = Utils::getItemText($data, 1, 4, true);
            } elseif (in_array($result_type, ['playlist'])) {
                $search_result['title'] = Utils::getItemText($data, 0);
                $search_result['author'] = Utils::getItemText($data, 1, $default_offset);
                $search_result['itemCount'] = explode(' ', Utils::getItemText($data, 1, $default_offset + 2))[0];
            } elseif (in_array($result_type, ['song'])) {
                $search_result['album'] = null;

                if (count($runs) - $last_artist_index == 5) {
                    // has album
                    $search_result['album'] = [
                        'name' => $runs[$last_artist_index + 2]['text'],
                        'id' => data_get($runs[$last_artist_index + 2], Paths::NAVIGATION_BROWSE_ID),
                    ];

                    $search_result['duration'] = $runs[count($runs) - 1]['text'] ?? null;
                }
            } elseif (in_array($result_type, ['video'])) {
                $search_result['views'] = explode(' ', Utils::getItemText($data, 1, -3))[0];
                $search_result['duration'] = Utils::getItemText($data, 1, count($runs) - 1);
            }

            $search_result['thumbnails'] = data_get($data, Paths::THUMBNAILS);

            $search_results[] = $search_result;
        }

        return $search_results;
    }

    public function parseWatchPlaylist($results)
    {
        $tracks = [];

        foreach ($results as $result) {
            if (! array_key_exists('playlistPanelVideoRenderer', $result)) {
                continue;
            }

            $data = $result['playlistPanelVideoRenderer'];

            if (array_key_exists('unplayableText', $data)) {
                continue;
            }

            $track = [
                'title' => data_get($data, Paths::TITLE_TEXT),
                'byline' => data_get($data, 'shortBylineText.runs.0.text'),
                'length' => data_get($data, 'lengthText.runs.0.text'),
                'videoId' => $data['videoId'],
                'playlistId' => data_get($data, Paths::NAVIGATION_PLAYLIST_ID),
                'thumbnail' => data_get($data, Paths::THUMBNAIL),
            ];

            $tracks[] = $track;
        }

        return $tracks;
    }

    public function parseArtistContents($results)
    {
        $categories = [
            trans('ytmusicapi::types.albums') => 'albums',
            trans('ytmusicapi::types.singles') => 'singles',
            trans('ytmusicapi::types.videos') => 'videos',
            trans('ytmusicapi::types.playlists') => 'playlists',
        ];

        $categories_parser = [
            'albums' => 'parseAlbum',
            'singles' => 'parseSingle',
            'videos' => 'parseVideo',
            'playlists' => 'parsePlaylist',
        ];

        $artist = [];

        foreach ($categories as $trans => $category) {
            $data = [];

            foreach ($results as $result) {
                if (
                    array_key_exists('musicCarouselShelfRenderer', $result)
                    && mb_strtolower(data_get($result['musicCarouselShelfRenderer'], Paths::CAROUSEL_TITLE)['text']) == $trans
                ) {
                    $data[] = $result['musicCarouselShelfRenderer'];
                }
            }

            if (count($data)) {
                $artist[$category] = ['browseId' => null, 'results' => []];

                if (array_key_exists('navigationEndpoint', data_get($data[0], Paths::CAROUSEL_TITLE))) {
                    $artist[$category]['browseId'] = data_get($data[0], Paths::CAROUSEL_TITLE . '.' . Paths::NAVIGATION_BROWSE_ID);
                    if (in_array($category, ['albums', 'singles', 'playlists'])) {
                        $artist[$category]['params'] = data_get($data[0], Paths::CAROUSEL_TITLE)['navigationEndpoint']['browseEndpoint']['params'];
                    }
                }

                $artist[$category]['results'] = $this->parseContentList($data[0]['contents'], $categories_parser[$category]);
            }
        }

        return $artist;
    }

    public function parseContentList($results, $parse_func)
    {
        $contents = [];

        foreach ($results as $result) {
            $contents[] = $this->{$parse_func}($result['musicTwoRowItemRenderer']);
        }

        return $contents;
    }

    public function parseAlbum($result)
    {
        return [
            'title' => data_get($result, Paths::TITLE_TEXT),
            'year' => data_get($result, Paths::SUBTITLE2),
            'browseId' => data_get($result, Paths::TITLE . '.' . Paths::NAVIGATION_BROWSE_ID),
            'thumbnails' => data_get($result, Paths::THUMBNAIL_RENDERER),
        ];
    }

    public function parseSingle($result)
    {
        return [
            'title' => data_get($result, Paths::TITLE_TEXT),
            'year' => data_get($result, Paths::SUBTITLE, true),
            'browseId' => data_get($result, Paths::TITLE . '.' . Paths::NAVIGATION_BROWSE_ID),
            'thumbnails' => data_get($result, Paths::THUMBNAIL_RENDERER),
        ];
    }

    public function parseVideo($result)
    {
        $video = [
            'title' => data_get($result, Paths::TITLE_TEXT),
            'videoId' => data_get($result, Paths::NAVIGATION_VIDEO_ID),
            'playlistId' => data_get($result, Paths::NAVIGATION_PLAYLIST_ID),
            'thumbnails' => data_get($result, Paths::THUMBNAIL_RENDERER),
        ];

        if (count($result['subtitle']['runs']) == 3) {
            $video['views'] = explode(' ', data_get($result, Paths::SUBTITLE2))[0];
        }

        return $video;
    }

    public function parsePlaylist($data)
    {
        $playlist = [
            'title' => data_get($data, Paths::TITLE_TEXT),
            'playlistId' => data_get($data, Paths::TITLE . '.' . Paths::NAVIGATION_BROWSE_ID)[2],
            'thumbnails' => data_get($data, Paths::THUMBNAIL_RENDERER),
        ];

        if (count($data['subtitle']['runs']) == 3) {
            $playlist['count'] = explode(' ', data_get($data, Paths::SUBTITLE2))[0];
        }

        return $playlist;
    }

    public function parsePlaylistItems($results, $menu_entries = null)
    {
        $songs = [];
        $count = 1;

        foreach ($results as $result) {
            $count++;
            if (! array_key_exists('musicResponsiveListItemRenderer', $result)) {
                continue;
            }

            $data = $result['musicResponsiveListItemRenderer'];

            try {
                $videoId = $setVideoId = null;
                $like = null;
                $feedback_tokens = null;

                // if the item has a menu, find its setVideoId
                if (array_key_exists('menu', $data)) {
                    foreach (data_get($data, Paths::MENU_ITEMS, []) as $item) {
                        if (array_key_exists('menuServiceItemRenderer', $item)) {
                            $menu_service = data_get($item, Paths::MENU_SERVICE);
                            if (array_key_exists('playlistEditEndpoint', $menu_service)) {
                                $setVideoId = $menu_service['playlistEditEndpoint']['actions'][0]['setVideoId'];
                                $videoId = $menu_service['playlistEditEndpoint']['actions'][0]['removedVideoId'];
                            }
                        }

                        if (array_key_exists('toggleMenuServiceItemRenderer', $item)) {
                            $feedback_tokens = Utils::parseSongMenuTokens($item);
                        }
                    }
                }

                // if item is not playable, the videoId was retrieved above
                if (array_key_exists('playNavigationEndpoint', data_get($data, Paths::PLAY_BUTTON))) {
                    $videoId = data_get($data, Paths::PLAY_BUTTON)['playNavigationEndpoint']['watchEndpoint']['videoId'];

                    if (array_key_exists('menu', $data)) {
                        $like = data_get($data, Paths::MENU_LIKE_STATUS);
                    }
                }
                $title = Utils::getItemText($data, 0);
                if ($title == 'Song deleted') {
                    continue;
                }

                $artists = Utils::parseSongArtists($data, 1);
                $album = Utils::parseSongAlbum($data, 2);

                $duration = null;
                if (array_key_exists('fixedColumns', $data)) {
                    if (array_key_exists('simpleText', Utils::getFixedColumnItem($data, 0)['text'])) {
                        $duration = Utils::getFixedColumnItem($data, 0)['text']['simpleText'];
                    } else {
                        $duration = Utils::getFixedColumnItem($data, 0)['text']['runs'][0]['text'];
                    }
                }

                $thumbnails = null;
                if (array_key_exists('thumbnails', $data)) {
                    $thumbnails = data_get($data, Paths::THUMBNAILS);
                }

                $isAvailable = true;
                if (array_key_exists('musicItemRendererDisplayPolicy', $data)) {
                    $isAvailable = $data['musicItemRendererDisplayPolicy'] != 'MUSIC_ITEM_RENDERER_DISPLAY_POLICY_GREY_OUT';
                }

                $song = [
                    'videoId' => $videoId,
                    'title' => $title,
                    'artists' => $artists,
                    'album' => $album,
                    'likeStatus' => $like,
                    'thumbnails' => $thumbnails,
                    'isAvailable' => $isAvailable,
                ];

                if ($duration) {
                    $song['duration'] = $duration;
                }
                if ($setVideoId) {
                    $song['setVideoId'] = $setVideoId;
                }
                if ($feedback_tokens) {
                    $song['feedbackTokens'] = $feedback_tokens;
                }

                // if ($menu_entries)
                //     $for menu_entry in $menu_entries:;
                //         song[menu_entry[-1]] = nav(data, MENU_ITEMS + menu_entry)

                $songs[] = $song;
            } catch (\Throwable $th) {
                throw $th;
            }
        }

        return $songs;
    }
}
