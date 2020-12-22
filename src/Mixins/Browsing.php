<?php

namespace MGKProd\YTMusic\Mixins;

use Exception;
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
        $filters = ['albums', 'artists', 'playlists', 'songs', 'videos', 'uploads'];

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

            if ($filter == 'uploads') {
                $params = 'agIYAw%3D%3D';
            } else {
                if ($filter == 'videos') {
                    $param2 = 'BABGAAgACgA';
                } elseif ($filter == 'albums') {
                    $param2 = 'BAAGAEgACgA';
                } elseif ($filter == 'artists') {
                    $param2 = 'BAAGAAgASgA';
                } elseif ($filter == 'playlists') {
                    $param2 = 'BAAGAAgACgB';
                } elseif ($filter == 'uploads') {
                    // self.__check_auth()
                    // $param2 = 'RABGAEgASgB';
                } else {
                    $param2 = 'RAAGAAgACgA';
                }
                $params = $param1 . $param2 . $param3;
            }

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
}
