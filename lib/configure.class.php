<?php
date_default_timezone_set('America/Toronto');

class Configure
{
    const SEARCH_URL    = 'http://geci.me/api/';

    const RESOURCE_PATH = 'music_resource/';
    const LYRICS_PATH   = self::RESOURCE_PATH . 'lyric/';
    const COVER_PATH    = self::RESOURCE_PATH . 'cover/';
    const AUDIO_PATH    = self::RESOURCE_PATH . 'audio/';

    const HOST          = '127.0.0.1';
    const USER          = 'developer';
    const PASSWD        = 'ppp';
    const MEDIA_DB      = 'media';
    const FIELD_LYRICS  = 'lyrics_file';
    const FIELD_COVER   = 'cover_file';
}