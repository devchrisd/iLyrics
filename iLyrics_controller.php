<?php

if (isset($_REQUEST['action']) === true && empty($_REQUEST['action']) !== true)
    $action =  $_REQUEST['action'];
else
    exit;

require_once('lib/common.php');
require_once('lib/audio/mp3_lib.class.php');

$s_id = $p_id = NULL;
if (isset($_REQUEST['s_id']) === true && empty($_REQUEST['s_id']) !== true)
    $s_id =  $_REQUEST['s_id'];

switch ($action) {
    case 'lib_display':
    case 'lib_refresh':
        $mp3_list = NULL;
        $mp3_lib = new mp3_lib();
        debug(__METHOD__);

        // scan library and save to DB
        if ($action == 'lib_refresh')
        {
            $mp3_lib->refresh_DB();
        }
        // Get list from DB
        $mp3_list = $mp3_lib->get_list_from_DB();
        if ($mp3_list !== NULL && count($mp3_list) > 0)
        {
            // debug(__METHOD__ . print_r($mp3_list,1));
            echo json_encode($mp3_list);
        }

        break;

    case 'get_lyrics':
        if ($s_id === NULL) break;

        require_once('lib/audio/ilyrics.class.php');

        $ret        = array();
        $iLyrics    = new ilyrics($s_id);
        $ret['lyrics']  = $iLyrics->fetch();
        $ret['cover'] = $iLyrics->get_cover();

        echo json_encode($ret);
        break;
    case 'save_lyrics':
        if ($s_id === NULL) break;

        $lyrics = '';
        if (isset($_POST['lyrics']) === true && empty($_POST['lyrics']) !== true)
            $lyrics =  $_POST['lyrics'];

        $result = mp3_lib::get_song_info($s_id);

        $filename = $result['lyrics_file'];

        $fp = fopen($filename, 'w');
        fwrite($fp, $lyrics);
        fclose($fp);

        echo json_encode($filename);
        break;

    case 'get_tag':
        if ($s_id === NULL) break;

        $tag = '';
        $tag = mp3_lib::get_song_info($s_id);
        echo json_encode($tag);
        break;
    case 'save_tag':
        if ($s_id === NULL) break;

        $title = $artist = $album = $year = $genre = '';

        if (isset($_POST['title']) === true && empty($_POST['title']) !== true)
            $title =  $_POST['title'];

        if (isset($_POST['artist']) === true && empty($_POST['artist']) !== true)
            $artist =  $_POST['artist'];

        if (isset($_POST['album']) === true && empty($_POST['album']) !== true)
            $album =  $_POST['album'];

        if (isset($_POST['year']) === true && empty($_POST['year']) !== true)
            $year =  $_POST['year'];

        if (isset($_POST['genre']) === true && empty($_POST['genre']) !== true)
            $genre =  $_POST['genre'];

        $result = mp3_lib::set_song_id3(
                    $s_id,
                    $title,
                    $artist,
                    $album,
                    $year,
                    $genre
                    );
        echo json_encode($result);
        break;

    case 'get_cover':
        if ($s_id === NULL) break;

        $iLyrics = new ilyrics($s_id);
        $cover = $iLyrics->get_cover();
        echo json_encode($cover);
        break;

    case 'new_playlist':
        // add playlist record and return p_id
        $pl_list = $p_id = NULL;
        if (isset($_REQUEST['pl_title']) === true && empty($_REQUEST['pl_title']) !== true)
            $pl_title =  $_REQUEST['pl_title'];

        if (isset($_POST['pl_list']) === true && empty($_POST['pl_list']) !== true)
        {
            $pl_list = $_POST['pl_list'];

            $playlist = new playlist();
            $p_id = $playlist->new_playlist($pl_title, $pl_list);
        }

        echo json_encode($p_id);
        break;

    case 'save_playlist':
        if (isset($_REQUEST['p_id']) === true && empty($_REQUEST['p_id']) !== true)
            $p_id =  $_REQUEST['p_id'];
        if ($p_id === NULL) break;

        echo json_encode($p_id);
        break;

    default:
        break;
}
