<?php

require_once('getid3/getid3.php');
require_once('mysql_driver.class.php');

class mp3_lib
{
    var $mp3_arr;
    var $media_dbi;

    function __construct()
    {
        $this->mp3_arr = NULL;
        $this->media_dbi = NULL;
    }

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
                    debug(__METHOD__ . ' file: ' . $file);
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

    function refresh_DB()
    {
        $this->_scan_mp3();

        if ($this->mp3_arr === NULL)
            return;

        if ($this->media_dbi === NULL)
        {
            $this->media_dbi = new mysql_interface_class(Configure::HOST, Configure::USER, Configure::PASSWD, Configure::MEDIA_DB);
        }
        foreach ($this->mp3_arr as $key => $value)
        {
            $query = 'REPLACE INTO ' . Configure::MEDIA_DB . '.song'
                    . " SET song_file='" . $this->media_dbi->escape_string($value) . "'"
                    ;
            $this->media_dbi->insert($query);
        }
    }

    function get_list_from_DB()
    {
        $list = NULL;
        if ($this->media_dbi === NULL)
        {
            $this->media_dbi = new mysql_interface_class(Configure::HOST, Configure::USER, Configure::PASSWD, Configure::MEDIA_DB);
        }

        if ($this->media_dbi->connect())
        {
            // $this->media_dbi->select_db(Configure::MEDIA_DB);
            $query = 'SELECT song_file from ' . Configure::MEDIA_DB . '.song;';
            if ($result = $this->media_dbi->select($query) )
            {
                while( $row = $this->media_dbi->fetch_row_assoc($result))
                {
                    $list[] = $row['song_file'];
                }
            }
        }

        return $list;
    }

    public function get_ID3($song_file)
    {
        // Initialize getID3 engine
        $getID3 = new getID3;

        $id3 = NULL;
        // Analyze file and store returned data in $ThisFileInfo
        $ThisFileInfo = $getID3->analyze($song_file);
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
            $filename = basename($song_file);
            $id3['song'] = substr($filename, 0, strrpos($filename, '.'));
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