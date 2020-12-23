# YTMusicAPI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mgkprod/ytmusicapi.svg?style=flat-square)](https://packagist.org/packages/mgkprod/ytmusicapi)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/mgkprod/ytmusicapi/Tests?label=tests&style=flat-square)
[![Total Downloads](https://img.shields.io/packagist/dt/mgkprod/ytmusicapi.svg?style=flat-square)](https://packagist.org/packages/mgkprod/ytmusicapi)

A work-in-progress API that emulates web requests from the YouTube Music web client.

This is a port of [sigma67/ytmusicapi](https://github.com/sigma67/ytmusicapi), an unofficial API implementation written in Python.

This package being used in production in one of my applications, I will do my best to keep it up to date with the latest evolutions of the YouTube Music client.
**I don't plan on porting more features than I need using the YouTube Music API into my app. Contributions are open!**

## Features


**Browsing**:

* [x] search (including all filters)
* [x] get artist information and releases (songs, videos, albums, singles)
* [x] get user information (videos, playlists)
* [x] get albums
* [x] get song metadata
* [x] get watch playlists (playlist that appears when you press play in YouTube Music)

## Requirements

* PHP 7.4+ and Laravel 8 or higher

## Installation

You can install the package via composer:

```bash
composer require mgkprod/ytmusicapi
```

The package will automatically register itself.

You can optionally publish the config file with:

```bash
php artisan vendor:publish --provider="MGKProd\YTMusic\YTMusicServiceProvider" --tag="config"
```

## Usage

``` php
use MGKProd\YTMusic\Facades\YTMusic;

// search in all
$results = YTMusic::browse()->search('daft punk');

// filtered search (albums, artists, playlists, songs or videos)
$artists = YTMusic::browse()->search('magenta', 'artists');
$songs = YTMusic::browse()->search('blizzard oddscure', 'songs');

// artist
$artist = YTMusic::browse()->artist('MPLAUCmMUZbaYdNH0bEd1PAlAqsA');

// ... and his albums
$albums = YTMusic::browse()->artistAlbums(
    $artist['albums']['browseId'],
    $artist['albums']['params']
);

// ... or his singles
$singles = YTMusic::browse()->artistAlbums(
    $artist['singles']['browseId'],
    $artist['singles']['params']
);

// album
$album = YTMusic::browse()->album('MPREb_BQZvl3BFGay');

// song
$song = YTMusic::browse()->song('ZrOKjDZOtkA');

// user
$user = YTMusic::browse()->user('UCPVhZsC2od1xjGhgEc2NEPQ');

// ... and his playlists
$playlists = YTMusic::browse()->userPlaylists(
    'UCPVhZsC2od1xjGhgEc2NEPQ',
    $user['playlists']['params']
);

```

The [tests](./tests/BrowsingTest.php) are also a great source of usage examples.

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email sr@mgk.dev instead of using the issue tracker.

## Credits

- [Simon Rubuano](https://github.com/mgkprod)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
