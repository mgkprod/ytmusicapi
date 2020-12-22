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

                    // if (array_key_exists('menu', $data)) {
                        // $toggle_menu = find_object_by_key(data_get($data, Path::MENU_ITEMS), 'toggleMenuServiceItemRenderer')
                        // if ($toggle_menu) {
                        //     $search_result['feedbackTokens'] = parse_song_menu_tokens(toggle_menu)
                        // }
                    // }
                }
            } elseif (in_array($result_type, ['video'])) {
                $search_result['views'] = explode(' ', Utils::getItemText($data, 1, -3))[0];
                $search_result['duration'] = Utils::getItemText($data, 1, count($runs) - 1);
            } elseif (in_array($result_type, ['upload'])) {
                $search_result['title'] = Utils::getItemText($data, 0);

                $browse_id = data_get($data, Paths::NAVIGATION_BROWSE_ID);
                if (! $browse_id) {
                    // song result
                    // flex_items = [
                    //     data_get($Utils::getFlexColumnItem(data, i), ['text', 'runs'], True)
                    //     for i in range(2)
                    // ]
                    // if flex_items[0]:
                    //     search_result['videoId'] = data_get($flex_items[0][0], NAVIGATION_VIDEO_ID)
                    //     search_result['playlistId'] = data_get($flex_items[0][0], NAVIGATION_PLAYLIST_ID)
                    // if flex_items[1]:
                    //     search_result['artist'] = {
                    //         'name': flex_items[1][0]['text'],
                    //         'id': data_get($flex_items[1][0], NAVIGATION_BROWSE_ID)
                    //     }
                    //     search_result['album'] = {
                    //         'name': flex_items[1][2]['text'],
                    //         'id': data_get($flex_items[1][2], NAVIGATION_BROWSE_ID)
                    //     }
                    //     search_result['duration'] = flex_items[1][4]['text']
                    // search_result['resultType'] = 'song'
                } else {
                    // artist or album result
                    // $search_result['browseId'] = $browse_id;
                    // if 'artist' in search_result['browseId']:
                    //     search_result['resultType'] = 'artist'
                    // else:
                    //     flex_item2 = Utils::getFlexColumnItem(data, 1)
                    //     runs = [
                    //         run['text'] for i, run in enumerate(flex_item2['text']['runs'])
                    //         if i % 2 == 0
                    //     ]
                    //     search_result['artist'] = runs[1]
                    //     if len(runs) > 2:  # date may be missing
                    //         search_result['releaseDate'] = runs[2]
                    //     search_result['resultType'] = 'album'
                }
            }

            $search_result['thumbnails'] = data_get($data, Paths::THUMBNAILS);

            $search_results[] = $search_result;
        }

        return $search_results;
    }

    protected function parseArtistContents($results)
    {
        $categories = ['albums', 'singles', 'videos', 'playlists'];
        $categories_local = [trans('ytmusicapi::types.albums'), trans('ytmusicapi::types.singles'), trans('ytmusicapi::types.videos'), trans('ytmusicapi::types.playlists')];
        $categories_parser = ['parseAlbum', 'parseSingle', 'parseVideo', 'parsePlaylist'];

        $artist = [];

        foreach ($categories as $category) {
            $data = [
                // r['musicCarouselShelfRenderer'] for r in results
                // if 'musicCarouselShelfRenderer' in r
                // and data_get($r['musicCarouselShelfRenderer'],
                //         CAROUSEL_TITLE)['text'].lower() == categories_local[i]
            ];

            if (count($data)) {
                // $artist[$category] = ['browseId' => 'None', 'results': []];
                // if 'navigationEndpoint' in data_get($data[0], CAROUSEL_TITLE) {
                //     artist[category]['browseId'] = data_get($data[0],
                //                                     CAROUSEL_TITLE + NAVIGATION_BROWSE_ID)
                //     if category in ['albums', 'singles', 'playlists']:
                //         artist[category]['params'] = data_get($
                //             data[0],
                //             CAROUSEL_TITLE)['navigationEndpoint']['browseEndpoint']['params']

                // artist[category]['results'] = parse_content_list(data[0]['contents'],
                //                                                 categories_parser[i])
                //                                             }
            }
        }

        return $artist;
    }

    protected function parseContentList($results, $parse_func)
    {
        $contents = [];

        foreach ($results as $result) {
            $contents[] = $this->{$parse_func}($result['musicTwoRowItemRenderer']);
        }

        return $contents;
    }

    protected function parseAlbum($result)
    {
        return [
            'title' => data_get($result, Paths::TITLE_TEXT),
            'year' => data_get($result, Paths::SUBTITLE2),
            'browseId' => data_get($result, Paths::TITLE . Parse::NAVIGATION_BROWSE_ID),
            'thumbnails' => data_get($result, Paths::THUMBNAIL_RENDERER),
        ];
    }

    protected function parseSingle($result)
    {
        return [
            'title' => data_get($result, Paths::TITLE_TEXT),
            'year' => data_get($result, Paths::SUBTITLE, true),
            'browseId' => data_get($result, Paths::TITLE . Parse::NAVIGATION_BROWSE_ID),
            'thumbnails' => data_get($result, Paths::THUMBNAIL_RENDERER),
        ];
    }

    protected function parseVideo($result)
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

    protected function parsePlaylist($data)
    {
        $playlist = [
            'title' => data_get($data, Paths::TITLE_TEXT),
            'playlistId' => data_get($data, Paths::TITLE + Paths::NAVIGATION_BROWSE_ID)[2],
            'thumbnails' => data_get($data, Paths::THUMBNAIL_RENDERER),
        ];

        if (count($data['subtitle']['runs']) == 3) {
            $playlist['count'] = explode(' ', data_get($data, Paths::SUBTITLE2))[0];
        }

        return $playlist;
    }
}
