<?php
    date_default_timezone_set('America/Toronto');

    function debug($msg)
    {
        $debug = TRUE;

        if ($debug) error_log($msg);
    }
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
    const SEARCH_URL    = 'http://geci.me/api/';

    const LYRICS_PATH   = 'lyric/';
    const COVER_PATH    = 'images/cover/';
    const AUDIO_PATH    = 'audio/';

    public $id3;
    public $lyrics;
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

    function __construct($filename)
    {
        $this->song_file    = self::AUDIO_PATH . $filename;
        $this->lyrics       = '';

        $this->id3      = $this->get_ID3($this->song_file);

        // build possible lyrics file name
        if ($this->id3 !== NULL)
        {
            $song     = $this->id3['song'];
            $artist   = $this->id3['artist'];
            $song   = preg_replace('/\s+/', '', $song);
            $artist = preg_replace('/\s+/', '', $artist);
            $this->lyrics_files = array(
                self::LYRICS_PATH . $artist . '_' . $song . '.lrc',
                self::LYRICS_PATH . $song . '.lrc',
                );
            debug('ID3: ' . print_r($this->id3,1));
        }
        else
        {
            debug('No ID3 available for ' . $this->song_file);
            $this->lyrics_files = array(
                self::LYRICS_PATH . substr($filename, 0, strrpos($filename, '.')) . '.lrc'
                );
        }
    }
/*
    function __construct($id)
    {
        $this->filename = get_from_DB();
        $this->id3      = get_ID3($this->filename);
        $this->song     = $this->id3['song'];
    }
*/
    // is the lyrics already in database
    function get_lyrics_local()
    {
        if (count($this->lyrics_files) == 0)
            return FALSE;

        foreach ($this->lyrics_files as $key => $lyrics)
        {
            if (file_exists($lyrics))
            {
                debug( " file: $lyrics   ");
                // rename into format of ARTIST_SONG.lrc, if it's not.
                if ($key !== 0)
                {
                    debug("rename $lyrics to" . $this->lyrics_files[0]);
                    if (rename($lyrics, $this->lyrics_files[0]))
                    {
                        debug('rename done');
                    }
                }

                return TRUE;
            }
        }

        return FALSE;
    }

    function fetch()
    {
        $result = $this->get_lyrics_local();
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

    function fetch_lyrics_from_DB()
    {
        //$fp = fopen($ly, 'r');
        $this->lyrics = file_get_contents($this->lyrics_files[0]);
    }

    function fetch_lyrics_online()
    {
        $url = self::SEARCH_URL . self::LYRICS_PATH . $this->id3['song'] . '/' . $this->id3['artist'];
        // curl to SEARCH_URL;
        // 
        $result = NULL;
        $this->lyrics = $result['0']['lrc'];
    }

    function get_ID3()
    {
        $id3 = NULL;

        $version = id3_get_version( $this->song_file );
        if ($version & ID3_V1_0 || $version & ID3_V1_1)
        {
            // debug( "Contains a 1.1 tag<br />");
            $id3_version = 1;
        }
        elseif ($version & ID3_V2_1) 
        {
            $id3_version = ID3_V2_1;
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

        return $id3;
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

}