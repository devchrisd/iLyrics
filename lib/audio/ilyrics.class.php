<?php
require_once(dirname(__FILE__) . '/../configure.class.php');
require_once(dirname(__FILE__) . '/../common.php');
require_once(dirname(__FILE__) . '/../curl.class.php');
require_once('mp3_lib.class.php');

    /*

        http://geci.me/api/lyric/SongName
        http://geci.me/api/lyric/SongName/Artist

        Return:
        {
            "count": 2,
            "code": 0,
            "result":
            [
                {
                    "aid": 3280385,
                    "artist_id": 30883,
                    "song": "\u6625\u5929\u91cc",
                    "lrc": "http://s.geci.me/lrc/401/40183/4018355.lrc",
                    "sid": 4018355
                },
                {
                    "aid": 3288629,
                    "artist_id": 30883,
                    "song": "\u6625\u5929\u91cc",
                    "lrc": "http://s.geci.me/lrc/402/40282/4028293.lrc",
                    "sid": 4028293
                }
            ]
        }
    接口返回值类型
    json

        count
        查询到的歌词数量。
        code
        不详，猜测可能是查询状态码，正常为0。
        result
        查询到的歌词条目列表。
        aid
        专辑编号。
        lrc
        歌词下载链接。
        artist
        艺术家姓名。
        song
        歌曲名称。
        sid
        歌曲编号。


    Get cover of album:
        http://geci.me/api/cover/AlbumId
        Return:
        {
            "count": 1,
            "code": 0,
            "result": {
                "cover": "http://s.geci.me/album-cover/328/3288629.jpg",
                "thumb": "http://s.geci.me/album-cover/328/3288629-thumb.jpg"
                }
        }

$id3["title"];
$id3["artist"];
$id3["album"];
$id3["year"];
$id3["genre"];
$id3["comment"];

    */
class ilyrics
{
    const SEARCH_URL    = Configure::SEARCH_URL;

    const LYRICS_PATH   = Configure::LYRICS_PATH;
    const COVER_PATH    = Configure::COVER_PATH;
    const AUDIO_PATH    = Configure::AUDIO_PATH;
    const MEDIA_DB      = Configure::MEDIA_DB;

    public $lyrics;
    public $cover_file;

    private $song_file;
    private $lyrics_files;
    private $s_id;
    // private $media_dbi;


    // $filename: file name only, no path included
    function __construct($s_id)
    {
        // $this->media_dbi = NULL;//new mysql_interface_class(Configure::HOST, Configure::USER, Configure::PASSWD, Configure::MEDIA_DB);

        // Get song info from DB
        $this->song_info    = mp3_lib::get_song_info($s_id);

        if (
            $this->song_info === NULL ||
            isset($this->song_info['song_file']) === FALSE ||
            empty($this->song_info['song_file']) === TRUE
            )
        {
            return FALSE;
        }

        $this->s_id       = $s_id;
        $this->cover_file = $this->lyrics_files = NULL;
        $this->lyrics     = '';

        $this->song_file = $this->song_info['song_file'];

        if (
            isset($this->song_info['lyrics_file']) === TRUE &&
            empty($this->song_info['lyrics_file']) === FALSE
            )
        {
            $this->lyrics_files = array($this->song_info['lyrics_file']);
        }

        if (
            isset($this->song_info['cover_file']) === TRUE &&
            empty($this->song_info['cover_file']) === FALSE
            )
        {
            $this->cover_file = $this->song_info['cover_file'];
        }

        if (
                $this->lyrics_files === NULL ||
                $this->cover_file === NULL
            )
        {
            debug(__METHOD__ . ' song_info for ' . $this->song_file . ':' . print_r($this->song_info,1));

            $_title  = _covert_for_URL_string($this->song_info['title']);
            $_artist = _covert_for_URL_string($this->song_info['artist']);

            // build possible lyrics file name
            if ($this->lyrics_files === NULL)
            {
                $this->lyrics_files = array(
                    self::LYRICS_PATH . $_artist . '_' . $_title . '.lrc',
                    self::LYRICS_PATH . $_title . '.lrc',
                    );
            }

            if ($this->cover_file === NULL)
            {
                $album = $this->song_info['album'];
                $_album = _covert_for_URL_string($album);
                $this->cover_file = self::COVER_PATH . $_artist . '_' . $_album;
            }
        }

        $this->filename       = basename($this->song_file);
        $this->lyrics_files[] = self::LYRICS_PATH . substr($this->filename, 0, strrpos($this->filename, '.')) . '.lrc';
    }

    function get_cover()
    {
        // get album cover
        if (empty($this->song_info['cover_file']) === FALSE)
            return $this->song_info['cover_file'];

        return mp3_lib::get_cover($this->s_id);
    }

    function fetch()
    {
        $result = $this->is_lyrics_local();

        if ($result !== FALSE)
        {
            debug( "get file " . $this->lyrics_files[0]);
            $this->__fetch_lyric();

            if (empty($this->song_info['lyrics_file']) === TRUE)
            {
                // save file name to database
                $ret = mp3_lib::update_record($this->s_id, $this->lyrics_files[0], Configure::FIELD_LYRICS);
                $this->song_info['lyrics_file'] = $this->lyrics_files[0];
            }
        }
        else
        {
            debug( "Cannot find lyrics locally. try online..." );
            $this->fetch_lyrics_online();
        }

        return $this->lyrics;
    }

    // is the lyrics already in database
    function is_lyrics_local()
    {
        if (count($this->lyrics_files) == 0)
        {
            debug('no lyrics_files provided.');

            return FALSE;
        }

        foreach ($this->lyrics_files as $key => $lyrics_file)
        {
            if (file_exists($lyrics_file))
            {
                // rename into format of ARTIST_SONG.lrc, if it's not.
                if ($key !== 0)
                {
                    debug("rename $lyrics_file to" . $this->lyrics_files[0]);
                    if (rename($lyrics_file, $this->lyrics_files[0]))
                    {
                        debug('rename done');
                    }
                }

                return TRUE;
            }

            debug('File ' . $lyrics_file . ' does not exist.');
        }

        return FALSE;
    }

    function __fetch_lyric()
    {
        $this->lyrics = file_get_contents($this->lyrics_files[0]);
    }

    function __save_file($file, $contents, $type='')
    {
        $ret = FALSE;
        // if (file_exists($file)) return TRUE;

        try
        {
            debug('Save file: ' . $file);
            $fp = fopen($file, 'w');
            fputs($fp, $contents);
            fclose($fp);

            if (empty($type) === FALSE)
            {
                $ret = mp3_lib::update_record($this->s_id, $file, $type);

                if ($ret === FALSE) return FALSE;

                // update song_info
                switch ($type)
                {
                    case Configure::FIELD_COVER:
                        $this->song_info['cover_file'] = $file;
                        break;
                    case Configure::FIELD_LYRICS:
                        $this->song_info['lyrics_file'] = $file;
                        break;
                    default:
                        break;
                }
            }
        }
        catch(Exception $e)
        {
            debug( __METHOD__ . ' Exception caught: ' . $e->getMessage() . "\n");
        }

        return $ret;
    }

    function fetch_lyrics_online()
    {
        $result = NULL;

        $url = self::SEARCH_URL . self::LYRICS_PATH
                 . substr($this->filename, 0, strrpos($this->filename, '.'));
        if ($this->song_info !== NULL)
        {
            $url = self::SEARCH_URL . self::LYRICS_PATH . $this->song_info['title'] . '/' . $this->song_info['artist'];
        }

        try {
            // curl to SEARCH_URL;
            $curl   = new curl_out($url);
            $result = $curl->send_request();

            if ($result !== FALSE)
            {
                $response_header = $curl->getHeaders();
                if ($response_header['http_code'] !== 200)
                {
                    throw new Exception('online search Got ' . $response_header['http_code'] . ' ! ! !');
                }
                $result_arr = json_decode($result, TRUE);
                debug('online result_arr: ' . print_r($result_arr,1));

                if (
                    is_array($result_arr) === TRUE &&
                    isset($result_arr['count']) &&
                    $result_arr['count'] > 0 &&
                    count($result_arr['result']) > 0
                   )
                {
                    $count = 0;
                    foreach ($result_arr['result'] as $key => $lrc_arr)
                    {
                        $count++;
                        if (isset($lrc_arr['lrc']) === TRUE && empty($lrc_arr['lrc']) === FALSE)
                        {
                            $lyrics_url = $lrc_arr['lrc'];

                            // debug(__METHOD__ . ' lyrics_url: ' . $lyrics_url);

                            $curl->set_para($lyrics_url);
                            $this->lyrics = $curl->send_request();
                            $response_header = $curl->getHeaders();
                            if ($response_header['http_code'] !== 200)
                            {
                                $this->lyrics = '';
                                debug(__METHOD__ . " :$count: online Lyrics Got " . $response_header['http_code'] . ' ! ! ! ');
                            }

                            // get cover:         http://geci.me/api/cover/AlbumId
                            if (
                                    (
                                        isset($this->song_info['cover_file']) === FALSE ||
                                        empty($this->song_info['cover_file']) === TRUE
                                    )
                                    &&
                                    isset($lrc_arr['aid']) === TRUE
                                )
                            {
                                $cover_url = self::SEARCH_URL . 'cover/'. $lrc_arr['aid'];
                                $curl->set_para($cover_url);
                                $cover_arr = json_decode($curl->send_request(), TRUE);

                                debug('get cover file: ' . print_r($cover_arr,1));

                                if (isset($cover_arr['result']['thumb']) === TRUE &&
                                    empty($cover_arr['result']['thumb']) === FALSE
                                    )
                                {
                                    $imagefile = $cover_arr['result']['thumb'];
                                    $this->cover_file .= '.' . substr($imagefile, strrpos($imagefile, '.')+1);
                                    $this->__save_file($this->cover_file, file_get_contents($imagefile), Configure::FIELD_COVER);
                                }
                            }

                            if ($this->lyrics !== '')
                            {
                                $this->__save_file($this->lyrics_files[0], $this->lyrics, Configure::FIELD_LYRICS);
                                break;
                            }
                        }
                    }
                }
            }

        } catch (Exception $e) {
            debug('Exception ' . $e->getMessage());
        }

        unset($curl);
        return $result;
    }
}