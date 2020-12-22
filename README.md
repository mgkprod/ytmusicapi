# YTMusicAPI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mgkprod/ytmusicapi.svg?style=flat-square)](https://packagist.org/packages/mgkprod/ytmusicapi)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/mgkprod/ytmusicapi/run-tests?label=tests)
[![Build Status](https://img.shields.io/travis/mgkprod/ytmusicapi/master.svg?style=flat-square)](https://travis-ci.org/mgkprod/ytmusicapi)
[![Total Downloads](https://img.shields.io/packagist/dt/mgkprod/ytmusicapi.svg?style=flat-square)](https://packagist.org/packages/mgkprod/ytmusicapi)

A work-in-progress API that emulates web requests from the YouTube Music web client.

This is a port of [sigma67/ytmusicapi](https://github.com/sigma67/ytmusicapi), an unofficial API implementation written in Python.

This package being used in production in one of my applications, I will do my best to keep it up to date with the latest evolutions of the YouTube Music client.

## Features

**I don't plan on porting more features than I need using the YouTube Music API into my app. Contributions are open!**

**Browsing**:

* [x] search (including all filters)
* [ ] get artist information and releases (songs, videos, albums, singles)
* [ ] get user information (videos, playlists)
* [ ] get albums
* [ ] get song metadata
* [ ] get watch playlists (playlist that appears when you press play in YouTube Music)

**Library management**:

* [ ] get library contents: playlists, songs, artists, albums and subscriptions
* [ ] add/remove library content: rate songs, albums and playlists, subscribe/unsubscribe artists

**Playlists**:

* [ ] create and delete playlists
* [ ] modify playlists: edit metadata, add/move/remove tracks
* [ ] get playlist contents

**Uploads**:

* [ ] Upload songs and remove them again
* [ ] List uploaded songs, artists and albums

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
use MGKProd\YTMusic\YTMusicFacade as YTMusic;

$results = YTMusic::search('daft punk');
$artists = YTMusic::search('magenta', 'artists');
$songs = YTMusic::search('blizzard oddscure', 'songs');
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

### Security

If you discover any security related issues, please email sr@mgk.dev instead of using the issue tracker.

## Credits

- [Simon Rubuano](https://github.com/mgkprod)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
