<?php

if (isset($_REQUEST['action']) === true && empty($_REQUEST['action']) !== true)
    $action =  $_REQUEST['action'];
else
    exit;

require_once('lib/common.php');
require_once('lib/audio/mp3_lib.class.php');
require_once('lib/audio/playlist.class.php');

$s_id = NULL;
if (isset($_REQUEST['s_id']) === true && empty($_REQUEST['s_id']) !== true)
    $s_id =  $_REQUEST['s_id'];

switch ($action) {
    case 'lib_display':
    case 'lib_refresh':
        lib_display_refresh($action);

        break;

    case 'get_lyrics':
        if ($s_id === NULL)
        {
            break;
        }

        get_lyrics($s_id);
        break;

    case 'save_lyrics':
        if ($s_id === NULL)
        {
            break;
        }

        save_lyrics($s_id);
        break;

    case 'get_tag':
        if ($s_id === NULL)
        {
            break;
        }

        get_tag($s_id);
        break;

    case 'save_tag':
        if ($s_id === NULL)
        {
            break;
        }

        save_tag($s_id);
        break;

    case 'get_cover':
        if ($s_id === NULL)
        {
            break;
        }

        get_cover($s_id);
        break;

    case 'show_playlist':
        $playlist = new playlist;
        $result   = $playlist->show_playlist();

        echo json_encode($result);
        break;

    case 'get_playlist':
        if (isset($_REQUEST['p_id']) === true && empty($_REQUEST['p_id']) !== true)
        {
            $p_id = $_REQUEST['p_id'];
        }
        else
        {
            break;
        }

        get_playlist($p_id);
        break;

    case 'save_playlist':
        if (
            (isset($_REQUEST['p_id']) === true && empty($_REQUEST['p_id']) !== true ||
             isset($_REQUEST['p_name']) === true && empty($_REQUEST['p_name']) !== true ) &&
            isset($_REQUEST['s_id']) === true && empty($_REQUEST['s_id']) !== true
            )
        {
            $param = array(
                'p_id' => $_REQUEST['p_id'],
                'p_name' => $_REQUEST['p_name'],
                's_id'  => $_REQUEST['s_id'],
                );
        }
        else
        {
            break;
        }

        save_playlist($param);
        break;
    default:
        break;
}

function lib_display_refresh($action)
{
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
}

function get_lyrics($s_id)
{
    require_once('lib/audio/ilyrics.class.php');

    $ret        = array();
    $iLyrics    = new ilyrics($s_id);
    $ret['lyrics']  = $iLyrics->fetch();
    $ret['cover'] = $iLyrics->get_cover();

    echo json_encode($ret);
}

function save_lyrics($s_id)
{
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
}

function get_tag($s_id)
{
    $tag = '';
    $tag = mp3_lib::get_song_info($s_id);
    echo json_encode($tag);
}

function sava_tag($s_id)
{
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
}

function get_cover($s_id)
{
    $iLyrics = new ilyrics($s_id);
    $cover = $iLyrics->get_cover();
    echo json_encode($cover);
}

function get_playlist($p_id)
{
    $playlist = new playlist;
    $result = $playlist->get_playlist($p_id);
    echo json_encode($result);
}

function save_playlist($param)
{
    debug(__METHOD__. print_r($param,1));

    $playlist = new playlist;
    $result = $playlist->save_playlist(
                $param
                );
    echo json_encode($result);
}