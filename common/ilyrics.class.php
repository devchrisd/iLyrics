<?php
require_once('configure.class.php');
require_once('common.php');
require_once('curl.class.php');

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
    */
class ilyrics
{
    const SEARCH_URL    = Configure::SEARCH_URL;

    const LYRICS_PATH   = Configure::LYRICS_PATH;
    const COVER_PATH    = Configure::COVER_PATH;
    const AUDIO_PATH    = Configure::AUDIO_PATH;
    const MEDIA_DB      = Configure::MEDIA_DB;

    public $id3;
    public $lyrics;
    public $cover_file;
/*
$id3["song"];
$id3["artist"];
$id3["album"];
$id3["year"];
$id3["genre"];
$id3["comment"];
*/
    private $song_file;
    private $lyrics_files;

    // $filename: file name only, no path included
    function __construct($filename)
    {
        // Get song info from DB
        $this->song_info    = $this->get_song_info($filename);
        $this->song_file    = self::AUDIO_PATH . $filename;
        $this->filename     = $filename;
        $this->lyrics       = '';

        $this->id3      = $this->get_ID3($this->song_file);

        // build possible lyrics file name
        if ($this->id3 !== NULL)
        {
            // get possible lyrics file name
            $song     = $this->id3['song'];
            $artist   = $this->id3['artist'];
            $song   = preg_replace('/\s+/', '', $song);
            $artist = preg_replace('/\s+/', '', $artist);
            $this->lyrics_files = array(
                self::LYRICS_PATH . $artist . '_' . $song . '.lrc',
                self::LYRICS_PATH . $song . '.lrc',
                );

            $this->cover_file = self::COVER_PATH . $artist . '_' . $song;
        }

        debug(__METHOD__ . ' ID3 for ' . $this->song_file . ':' . print_r($this->id3,1));

        $this->lyrics_files[] = self::LYRICS_PATH . substr($filename, 0, strrpos($filename, '.')) . '.lrc';
    }
/*
    function __construct($id)
    {
        $this->filename = get_from_DB();
        $this->id3      = get_ID3($this->filename);
        $this->song     = $this->id3['song'];
    }
*/

    function get_song_info($filename)
    {
        $song_info = NULL;
        $query = 'select * from ' . self::MEDIA_DB . '.song WHERE filename="' . $filename .'"';

        return $song_info;
    }

    function fetch()
    {
        $result = $this->is_lyrics_local();
        if ($result !== FALSE)
        {
            debug( "get file " . $this->lyrics_files[0]);
            $this->fetch_lyrics_from_DB();
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

    function fetch_lyrics_from_DB()
    {
        $this->__fetch_lyric($this->lyrics_files[0]);
    }

    function __fetch_lyric($lyrics_file, $bSave=false)
    {
        $this->lyrics = file_get_contents($lyrics_file);
        if ($bSave === true)
        {
            // save lyrics
            $this->__save_file($this->lyrics_files[0], $this->lyrics);
        }
    }

    function __save_file($file, $contents)
    {
        // if (file_exists($file)) return true;

        try
        {
            debug('Save file: ' . $file);
            $fp = fopen($file, 'w');
            fputs($fp, $contents);
            fclose($fp);
        }
        catch(Exception $e)
        {
            debug( 'Exception caught: ' . $e->getMessage() . "\n");
        }
    }

    function fetch_lyrics_online()
    {
        $result = NULL;

        $url = self::SEARCH_URL . self::LYRICS_PATH
                 . substr($this->filename, 0, strrpos($this->filename, '.'));
        if ($this->id3 !== NULL)
        {
            $url = self::SEARCH_URL . self::LYRICS_PATH . $this->id3['song'] . '/' . $this->id3['artist'];
        }

        try {
            // curl to SEARCH_URL;
            $curl   = new curl_out($url);
            $result = $curl->send_request();

            if ($result !== false)
            {
                $response_header = $curl->getHeaders();
                if ($response_header['http_code'] !== 200)
                {
                    throw new Exception('online search Got ' . $response_header['http_code'] . ' ! ! !');
                }
                $result_arr = json_decode($result, true);
                debug('online result_arr: ' . print_r($result_arr,1));

                if (
                    is_array($result_arr) === true && 
                    isset($result_arr['count']) &&
                    $result_arr['count'] > 0 &&
                    count($result_arr['result']) > 0
                   )
                {
                    $count = 0;
                    foreach ($result_arr['result'] as $key => $lrc_arr)
                    {
                        $count++;
                        if (isset($lrc_arr['lrc']) === true && !empty($lrc_arr['lrc']))
                        {
                            $lyrics_url = $lrc_arr['lrc'];

                            debug(__METHOD__ . ' lyrics_url: ' . $lyrics_url);

                            $curl->set_para($lyrics_url);
                            $this->lyrics = $curl->send_request();
                            $response_header = $curl->getHeaders();
                            if ($response_header['http_code'] !== 200)
                            {
                                $this->lyrics = '';
                                debug(__METHOD__ . " :$count: online Lyrics Got " . $response_header['http_code'] . ' ! ! ! ');
                            }

                            if ($this->lyrics !== '')
                            {
                                debug(__METHOD__ . " :$count: Save online lyrics to file: " . $this->lyrics_files[0]);

                                $this->__save_file($this->lyrics_files[0], $this->lyrics);

                        if (isset($lrc_arr['aid']) === true)
                        {
                            // get cover:         http://geci.me/api/cover/AlbumId
                            $cover_url = self::SEARCH_URL . 'cover/'. $lrc_arr['aid'];
                            $curl->set_para($cover_url);
                            $cover_arr = json_decode($curl->send_request(), true);

                            debug('get cover file: ' . print_r($cover_arr,1));

debug('thumb : ' . $cover_arr['result']['thumb']);
                            if (isset($cover_arr['result']['thumb']) === true &&
                                empty($cover_arr['result']['thumb']) === false
                                )
                            {
                                $imagefile = $cover_arr['result']['thumb'];
                                debug(' image file: ' .$imagefile);
                                $this->cover_file .= '.' . substr($imagefile, strrpos($imagefile, '.')+1);
                                debug('save cover file: ' . $this->cover_file);
                                $this->__save_file($this->cover_file, file_get_contents($imagefile));
                            }
                        }
                                break;
                            }
                            // $this->__fetch_lyric($lyrics_url, true);
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

    function get_ID3()
    {
        require_once('getid3/getid3.php');

        // Initialize getID3 engine
        $getID3 = new getID3;

        $id3 = NULL;
        // Analyze file and store returned data in $ThisFileInfo
        $ThisFileInfo = $getID3->analyze($this->song_file);
        /*
         Optional: copies data from all subarrays of [tags] into [comments] so
         metadata is all available in one location for all tag formats
         metainformation is always available under [tags] even if this is not called
        */
        getid3_lib::CopyTagsToComments($ThisFileInfo);
        if (isset($ThisFileInfo['comments']) && !empty($ThisFileInfo['comments']))
        {
            // debug(print_r($ThisFileInfo['comments'],1));

            $id3['song'] = $ThisFileInfo['comments']['title'][0];
            $id3['artist'] = $ThisFileInfo['comments']['artist'][0];
            $id3['album'] = $ThisFileInfo['comments']['album'][0];
            $id3['year'] = $ThisFileInfo['comments']['year'][0];
            $id3['genre'] = $ThisFileInfo['comments']['genre'][0];
            if (isset($ThisFileInfo['comments']['unsynchronised_lyric']))
                $id3['unsynchronised_lyric'] = $ThisFileInfo['comments']['unsynchronised_lyric'][0];
        }

        if (!isset($id3['song']) || empty($id3['song']))
        {
            $id3['song'] = substr($this->filename, 0, strrpos($this->filename, '.'));
        }

        return $id3;
    }

/*
    function get_ID3_old()
    {
        $version = id3_get_version( $this->song_file );
        if ($version & ID3_V2_4) 
        {
            $id3_version = ID3_V2_4;
            debug( "Contains a 2.4 tag".PHP_EOL);
        }
        elseif ($version & ID3_V2_3) 
        {
            $id3_version = ID3_V2_3;
            debug( "Contains a 2.3 tag".PHP_EOL);
        }
        elseif ($version & ID3_V2_2) 
        {
            $id3_version = ID3_V2_2;
            debug( "Contains a 2.2 tag".PHP_EOL);
        }
        elseif ($version & ID3_V2_1) 
        {
            $id3_version = ID3_V2_1;
            debug( "Contains a 2.1 tag".PHP_EOL);
        }
        elseif( $version & ID3_V1_1)
        {
            debug( "Contains a 1.1 tag". PHP_EOL);
            // $id3_version = ID3_V1_1;
            $id3_version = 1;
        }
        elseif ($version & ID3_V1_0 )
        {
            debug( "Contains a 1.0 tag". PHP_EOL);
            // $id3_version = ID3_V1_0;
            $id3_version = 1;
        }
        else{
            $id3_version = $version;
        }

        if ($id3_version === 1)
        {
            $id3 = $this->get_ID3_v1();
        }
        else
        {
            // ini_set('memory_limit', '1024M');
            // $tag = id3_get_tag( $tag_song , $tag_version);
            // $tag = id3_get_tag( $tag_song , ID3_BEST);
        }

    }
    //Reads ID3v1 from a MP3 file and displays it
    function get_ID3_v1()
    {
        //make a array of genres
        $genre_arr = array(
            "Blues","Classic Rock","Country","Dance","Disco","Funk","Grunge",
        "Hip-Hop","Jazz","Metal","New Age","Oldies","Other","Pop","R&B",
        "Rap","Reggae","Rock","Techno","Industrial","Alternative","Ska",
        "Death Metal","Pranks","Soundtrack","Euro-Techno","Ambient",
        "Trip-Hop","Vocal","Jazz+Funk","Fusion","Trance","Classical",
        "Instrumental","Acid","House","Game","Sound Clip","Gospel",
        "Noise","AlternRock","Bass","Soul","Punk","Space","Meditative",
        "Instrumental Pop","Instrumental Rock","Ethnic","Gothic",
        "Darkwave","Techno-Industrial","Electronic","Pop-Folk",
        "Eurodance","Dream","Southern Rock","Comedy","Cult","Gangsta",
        "Top 40","Christian Rap","Pop/Funk","Jungle","Native American",
        "Cabaret","New Wave","Psychadelic","Rave","Showtunes","Trailer",
        "Lo-Fi","Tribal","Acid Punk","Acid Jazz","Polka","Retro",
        "Musical","Rock & Roll","Hard Rock","Folk","Folk-Rock",
        "National Folk","Swing","Fast Fusion","Bebob","Latin","Revival",
        "Celtic","Bluegrass","Avantgarde","Gothic Rock","Progressive Rock",
        "Psychedelic Rock","Symphonic Rock","Slow Rock","Big Band",
        "Chorus","Easy Listening","Acoustic","Humour","Speech","Chanson",
        "Opera","Chamber Music","Sonata","Symphony","Booty Bass","Primus",
        "Porn Groove","Satire","Slow Jam","Club","Tango","Samba",
        "Folklore","Ballad","Power Ballad","Rhythmic Soul","Freestyle",
        "Duet","Punk Rock","Drum Solo","Acapella","Euro-House","Dance Hall"
        );

        $data = NULL;

        try
        {
            $file = fopen($this->song_file, "r");
            fseek($file, -128, SEEK_END);
            $raw_tag = fread($file, 128);
            // debug( 'raw: ' . $raw_tag . PHP_EOL);

            fseek($file, -128, SEEK_END);
            $tag = fread($file, 3);

            if($tag == "TAG")
            {
                $data["song"] = trim(fread($file, 30));
                $data["artist"] = trim(fread($file, 30));
                $data["album"] = trim(fread($file, 30));
                $data["year"] = trim(fread($file, 4));
                $data["comment"] = trim(fread($file, 30));
                $data["genre"] = $genre_arr[ord(trim(fread($file, 1)))];
            }
            // else
            // {
            //     // die("MP3 file does not have any ID3 tag!");
            // }
        }
        catch (Exception $e)
        {
            debug( 'Exception caught: ' . $e->getMessage() . "\n");
        }
        // finally
        {
            fclose($file);
        }

        // while(list($key, $value) = each($data))
        // {
        //     print(" $key: $value\r\n");
        // }
        
        return $data;
    }
*/
}