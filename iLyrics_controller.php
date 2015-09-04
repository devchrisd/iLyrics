<?php

if (isset($_REQUEST['action']) === true && empty($_REQUEST['action']) !== true)
    $action =  $_REQUEST['action'];
else
    exit;

require_once('lib/common.php');
require_once('lib/audio/mp3_lib.class.php');

$s_id = NULL;
if (isset($_REQUEST['s_id']) === true && empty($_REQUEST['s_id']) !== true)
    $s_id =  $_REQUEST['s_id'];

switch ($action) {
    case 'lib_display':
    case 'lib_refresh':
        $mp3_list = NULL;
        $mp3_lib = new mp3_lib();

        // scan library and save to DB
        if ($action == 'lib_refresh')
        {
            $mp3_lib->refresh_DB();
        }

        // Get list from DB
        $mp3_list = $mp3_lib->get_list_from_DB();

        if ($mp3_list !== NULL && count($mp3_list) > 0)
        {
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
        {
            $lyrics =  $_POST['lyrics'];
        }

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

        if (isset($_GET['title']) === true && empty($_GET['title']) !== true)
            $title =  $_GET['title'];

        if (isset($_GET['artist']) === true && empty($_GET['artist']) !== true)
            $artist =  $_GET['artist'];

        if (isset($_GET['album']) === true && empty($_GET['album']) !== true)
            $album =  $_GET['album'];

        if (isset($_GET['year']) === true && empty($_GET['year']) !== true)
            $year =  $_GET['year'];

        if (isset($_GET['genre']) === true && empty($_GET['genre']) !== true)
            $genre =  $_GET['genre'];

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

    default:
        break;
}
