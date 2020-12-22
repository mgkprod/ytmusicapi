<?php

namespace MGKProd\YTMusic\Parsers;

class Paths
{
    // commonly used navigation paths
    const TAB_CONTENT = 'tabs.0.tabRenderer.content';
    const SINGLE_COLUMN_TAB = 'contents.singleColumnBrowseResultsRenderer.' . self::TAB_CONTENT;
    const SECTION_LIST = 'sectionListRenderer.contents';
    const ITEM_SECTION = 'itemSectionRenderer.contents.0';
    const MUSIC_SHELF = '0.musicShelfRenderer';
    const MENU = 'menu.menuRenderer';
    const MENU_ITEMS = self::MENU . 'items';
    const MENU_LIKE_STATUS = self::MENU . 'topLevelButtons.0.likeButtonRenderer.likeStatus';
    const MENU_SERVICE = 'menuServiceItemRenderer.serviceEndpoint';
    const PLAY_BUTTON = 'overlay.musicItemThumbnailOverlayRenderer.content.musicPlayButtonRenderer';
    const NAVIGATION_BROWSE_ID = 'navigationEndpoint.browseEndpoint.browseId';
    const NAVIGATION_VIDEO_ID = 'navigationEndpoint.watchEndpoint.videoId';
    const NAVIGATION_PLAYLIST_ID = 'navigationEndpoint.watchEndpoint.playlistId';
    const CAROUSEL_TITLE = 'header.musicCarouselShelfBasicHeaderRenderer.title.runs.0';
    const FRAMEWORK_MUTATIONS = 'frameworkUpdates.entityBatchUpdate.mutations';
    const TITLE = 'title.runs.0';
    const TITLE_TEXT = 'title.runs.0.text';
    const TEXT_RUN = 'text.runs.0';
    const SUBTITLE = 'subtitle.runs.0.text';
    const SUBTITLE2 = 'subtitle.runs.text';
    const SUBTITLE3 = 'subtitle.runs.text';
    const THUMBNAIL = 'thumbnail.thumbnails';
    const THUMBNAILS = 'thumbnail.musicThumbnailRenderer.' . self::THUMBNAIL;
    const THUMBNAIL_RENDERER = 'thumbnailRenderer.musicThumbnailRenderer.' . self::THUMBNAIL;
    const THUMBNAIL_CROPPED = 'thumbnail.croppedSquareThumbnailRenderer.' . self::THUMBNAIL;
    const FEEDBACK_TOKEN = 'feedbackEndpoint.feedbackToken';
}
