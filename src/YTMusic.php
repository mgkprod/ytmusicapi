<?php

namespace MGKProd\YTMusic;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use MGKProd\YTMusic\Mixins\Browsing;

class YTMusic
{
    const PARAMS = '?alt=json&key=AIzaSyC9XL3ZjWddXya6X74dJoCTL-WEYFDNX30';
    const BASE_URL = 'https://music.youtube.com/youtubei/v1/';

    protected $headers;
    protected $context;

    public function __construct()
    {
        $this->headers = json_decode(File::get(__DIR__ . '/headers.json'), true);
        $this->context = json_decode(File::get(__DIR__ . '/context.json'), true);
    }

    public function search($query, $filter = null, $limit = 20, $ignore_spelling = false)
    {
        return (new Browsing($this))->search($query, $filter, $limit, $ignore_spelling);
    }

    public function _send_request($endpoint, $body, $additional_params = null)
    {
        $body = array_merge(
            $this->context,
            $body,
        );

        // if self.auth:
        //     origin = self.headers.get('origin', self.headers.get('x-origin'))
        //     self.headers["Authorization"] = get_authorization(self.sapisid + ' ' + origin)

        $response = Http::asJson()
            ->withHeaders($this->headers)
            ->post(
                self::BASE_URL . $endpoint . self::PARAMS . $additional_params,
                $body,
            );

        if ($response->failed()) {
            throw new Exception('Error Processing Request: ' . $response->body());
        }

        return $response->json();
    }
}
