<?php

require_once(dirname(__FILE__) . '/../getid3/getid3.php');
require_once(dirname(__FILE__) . '/../dbi/mongo_driver.class.php');

class mp3_lib
{
    var $mp3_arr;
    static $media_dbi;

    function __construct()
    {
        $this->mp3_arr   = NULL;
        self::$media_dbi = NULL;
        self::__get_dbi();
    }

    // scan and set file name of songs(including path) into array mp3_arr
    function _scan_mp3($dir = Configure::AUDIO_PATH)
    {
        $files = null;

        if ( ($files = array_diff(scandir($dir), array('..', '.', '.DS_Store'))) && count($files)>0 )
        {
            foreach ($files as $file)
            {
                $file = ((empty($dir)) ? '' : $dir) . $file;

                if ( is_dir($file) !== true && $this->is_mp3($file) )
                {
                    // debug(__METHOD__ . ' file: ' . $file);
                    $this->mp3_arr[] = $file;
                }
                elseif (is_dir($file) === true)
                {
                    debug(__METHOD__ . ' subdir: ' . $file);

                    $this->_scan_mp3($file.'/');
                }
            }
        }

        return $this->mp3_arr;
    }

    function is_mp3($file)
    {
        return preg_match('/^[^.^:^?^\-][^:^?]*\.(?i)(mp3)$/',$file);
    }
    /*
    function isfile($file){
        return preg_match('/^[^.^:^?^\-][^:^?]*\.(?i)' . getexts() . '$/',$file);
        //first character cannot be . : ? -
        //subsequent characters can't be a : ?
        //then a . character and must end with one of your extentions
        //getexts() can be replaced with your extentions pattern
    }

    function getexts(){
        //list acceptable file extensions here
        return '(app|avi|doc|docx|exe|ico|mid|midi|mov|mp3|
                     mpg|mpeg|pdf|psd|qt|ra|ram|rm|rtf|txt|wav|word|xls)';
    }
    */

    static function __get_dbi()
    {
        if (self::$media_dbi === NULL)
        {
            // debug(print_r(debug_backtrace(),1));
            self::$media_dbi = new Configure::$DB_DRIVER(Configure::HOST, Configure::USER, Configure::PASSWD, Configure::MEDIA_DB);
        }
    }

    function refresh_DB()
    {
        // scan media folder and put them into mp3_arr
        $this->_scan_mp3();

        if ($this->mp3_arr === NULL)
        {
            return;
        }

        self::__get_dbi();

        if (self::$media_dbi->connect())
        {
            $collection = 'song';
            // delete current data
            self::$media_dbi->drop(
            // self::$media_dbi->remove(
                                Configure::MEDIA_DB,
                                'song'
                                );

            // save each file info into database
            foreach ($this->mp3_arr as $key => $value)
            {
                $title = $artist = $album = $year = $genre = '';
                // get id3
                $id3   = $this->get_ID3($value);

                if ($id3 !== NULL)
                {
                    $title  = isset($id3['title'])  ? $id3['title'] : '';
                    $artist = isset($id3['artist']) ? $id3['artist']: '';
                    $album  = isset($id3['album'])  ? $id3['album'] : '';
                    $year   = isset($id3['year'])   ? $id3['year']  : '';
                    $genre  = isset($id3['genre'])  ? $id3['genre'] : '';

                    $_title      = _covert_for_URL_string($title);
                    $_artist     = _covert_for_URL_string($artist);
                    $_album      = _covert_for_URL_string($album);
                    $lyrics_file = Configure::LYRICS_PATH . $_artist . '_' . $_title . '.lrc';

                    if (file_exists($lyrics_file) === FALSE)
                    {
                        $lyrics_file = NULL;
                    }

                    $cover_file = Configure::COVER_PATH . $_artist . '_' . $_album . '.jpg';

                    if (file_exists($cover_file) === FALSE)
                    {
                        $cover_file = NULL;
                    }
                }

                $song_arr = array(
                        'song_file' => self::$media_dbi->escape_string($value),
                        'title'     => self::$media_dbi->escape_string($title),
                        'artist'    => self::$media_dbi->escape_string($artist),
                        'album'     => self::$media_dbi->escape_string($album),
                        'year'      => self::$media_dbi->escape_string($year),
                        'genre'     => self::$media_dbi->escape_string($genre),
                        );

                if ($lyrics_file !== NULL)
                {
                    $song_arr['lyrics_file'] = self::$media_dbi->escape_string($lyrics_file);
                }

                if ($cover_file !== NULL)
                {
                    $song_arr['cover_file'] = self::$media_dbi->escape_string($cover_file);
                }

                try
                {
                    self::$media_dbi->insert(
                                        array(
                                            Configure::MEDIA_DB,
                                            $collection,
                                            $song_arr
                                            )
                                        );
                }
                catch (Exception $e) {
                    debug('Caught Exception : '. $e->getMessage() . "\n");
                }

            }
        }
    }

    // get media list from database
    function get_list_from_DB()
    {
        $list  = NULL;
        $index = 0;
        self::__get_dbi();

        if (self::$media_dbi->connect())
        {
            $collection = 'song';

            // self::$media_dbi->select_db(Configure::MEDIA_DB);
            $return_fields = array('_id', 'song_file', 'title'); // order by s_id;';

            if ($result = self::$media_dbi->select(
                                                array(
                                                    Configure::MEDIA_DB,
                                                    $collection,
                                                    $return_fields
                                                    )
                                                )
                )
            {
                foreach ($result as $row)
                {
                    $list[$index]['s_id']   = $row['_id'];
                    $list[$index]['title']  = $row['title'];
                    $list[$index++]['file'] = $row['song_file'];
                }
            }
        }
/*
$test_arr = array(
    // 'audio/01.七个母音.mp3',
        // 'audio/富士山下.mp3',
    'audio/01.天边.mp3',
        // 'audio/02.轻轻地告诉你.mp3',
        // 'audio/对不起谢谢.mp3',
);
foreach ($test_arr as $value) self::get_ID3($value);
*/
        return $list;
    }

    static function get_song_info($s_id)
    {
        self::__get_dbi();

        if (self::$media_dbi->connect())
        {
            $collection = 'song';

            $return_fields = array();
            $query_fields  = array( '_id' => new MongoId($s_id) );
            // :TODO: use findOne()
            //
            if ($result = self::$media_dbi->select(
                                                array(
                                                    Configure::MEDIA_DB,
                                                    $collection,
                                                    $return_fields,
                                                    $query_fields
                                                    )
                                                )
                )
            {
                foreach ($result as $row)
                {
                    return $row;
                }
            }
        }
        return NULL;
    }

    static function set_song_id3(
            $s_id,
            $title,
            $artist,
            $album,
            $year,
            $genre
        )
    {
        $TagData = $result = NULL;

        // update DB
        self::__get_dbi();

        if (self::$media_dbi->connect())
        {
            $collection = 'song';

            $ID3_key = array(
                        'title',
                        'artist',
                        'album',
                        'year',
                        'genre',
                        );

            $data_arr = array();
            foreach($ID3_key as $key)
            {
                if (empty($$key) === FALSE)
                {
                    $data_arr[$key]              = self::$media_dbi->escape_string($$key);
                    $TagData[strtolower($key)][] = $$key;
                }
            }
            $query_fields = array( '_id' => new MongoId( $s_id ) );
            $result = self::$media_dbi->update(
                                            array(
                                                Configure::MEDIA_DB,
                                                $collection,
                                                $query_fields,
                                                $data_arr
                                            )
                                        );
        }

        // update id3
        $song_info = self::get_song_info($s_id);
        self::set_ID3($TagData, $song_info['song_file']);

        return $result;
    }

    static public function set_ID3($TagData, $Filename)
    {
        require_once(dirname(__FILE__) . '/../getid3/write.php');

        $tagwriter                    = new getid3_writetags;
        $tagwriter->filename          = $Filename;
        $tagwriter->tagformats        = array('id3v1', 'id3v2.3', 'id3v2.4', 'ape');
        $tagwriter->tag_encoding      = 'UTF-8';
        $tagwriter->overwrite_tags    = TRUE;  // FALSE;
        $tagwriter->remove_other_tags = FALSE;

        $tagwriter->tag_data = $TagData;

        if ($tagwriter->WriteTags())
        {
            debug('Successfully wrote tags');
            if (!empty($tagwriter->warnings))
            {
                debug('There were some warnings:' . $tagwriter->warnings);
            }
        } else {
            debug ('Failed to write tags! ' . print_r($tagwriter->errors,1));
        }
    }

    public function get_ID3($song_file)
    {
        // Initialize getID3 engine
        $getID3       = new getID3;
        $id3          = NULL;
        // Analyze file and store returned data in $ThisFileInfo
        $ThisFileInfo = $getID3->analyze($song_file);
        /*
         Optional: copies data from all subarrays of [tags] into [comments] so
         metadata is all available in one location for all tag formats
         metainformation is always available under [tags] even if this is not called
        */
        // debug( print_r($ThisFileInfo,1));

        getid3_lib::CopyTagsToComments($ThisFileInfo);

        if (isset($ThisFileInfo['comments']) && !empty($ThisFileInfo['comments']))
        {
            // debug('### comments: ' . print_r($ThisFileInfo['comments'],1));

            $id3['title']  = isset($ThisFileInfo['comments']['title'][0])  ? $ThisFileInfo['comments']['title'][0]  : '';
            $id3['artist'] = isset($ThisFileInfo['comments']['artist'][0]) ? $ThisFileInfo['comments']['artist'][0] : '';
            $id3['album']  = isset($ThisFileInfo['comments']['album'][0])  ? $ThisFileInfo['comments']['album'][0]  : '';
            $id3['year']   = isset($ThisFileInfo['comments']['year'][0])   ? $ThisFileInfo['comments']['year'][0]   : '';
            $id3['genre']  = isset($ThisFileInfo['comments']['genre'][0])  ? $ThisFileInfo['comments']['genre'][0]  : '';
            if (isset($ThisFileInfo['comments']['unsynchronised_lyric']))
                $id3['unsynchronised_lyric'] = $ThisFileInfo['comments']['unsynchronised_lyric'][0];
        }

        if (!isset($id3['title']) || empty($id3['title']))
        {
            $filename     = basename($song_file);
            $id3['title'] = substr($filename, 0, strrpos($filename, '.'));
        }
        // debug( print_r($id3,1));

        return $id3;
    }

    function update_record($s_id, $data, $field)
    {
        $ret = FALSE;

        self::__get_dbi();
        $update_arr = '';
        $collection = 'song';

        switch ($field)
        {
            case Configure::FIELD_COVER:
                // update all records of (album, artist)
                //
                $return_fields = array('album', 'artist');
                $query_fields  = array('_id' => new MongoId( $s_id));

                // :TODO: use findOne()
                if ($result_select = self::$media_dbi->select(
                                                        array(
                                                            Configure::MEDIA_DB,
                                                            $collection,
                                                            $return_fields,
                                                            $query_select
                                                            )
                                                        )
                    )
                {
                    foreach ( $result_select as $row_select)
                    {
                        $album  = $row_select['album'];
                        $artist = $row_select['artist'];
                    }
                }

                $query_fields = array(
                                     'album' => self::$media_dbi->escape_string($album),
                                     'artist'=> self::$media_dbi->escape_string($artist)
                                    );
                $update_arr   = array( 'cover_file' => self::$media_dbi->escape_string($data));

                break;

            case Configure::FIELD_LYRICS:
                $query_fields = array('_id' => new MongoId($s_id));
                $update_arr   = array('lyrics_file' => self::$media_dbi->escape_string($data) );
                break;

            default:
                break;
        }
        if (empty($update_arr) === FALSE)
        {
            // update database
            $ret = self::$media_dbi->update(
                                        array(
                                            Configure::MEDIA_DB,
                                            $collection,
                                            $query_fields,
                                            $update_arr
                                            )
                                        );
        }

        return $ret;
    }

    static function get_cover($s_id)
    {
        if ($s_id)
        {
            self::__get_dbi();
            $collection    = 'song';
            $return_fields = array('cover_file');
            $query_fields  = array('_id' => new MongoId( self::$media_dbi->escape_string($s_id) ));

            if ($result = self::$media_dbi->select(
                                                array(
                                                    Configure::MEDIA_DB,
                                                    $collection,
                                                    $return_fields,
                                                    $query_fields
                                                )
                                            )
                )
            {
                foreach ($result as $row)
                {
                    if (! isset($row['cover_file']))
                    {
                        $row['cover_file'] = '';
                    }

                    return $row['cover_file'];
                }
            }
        }
        return NULL;
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