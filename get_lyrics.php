<?php
    if (isset($_GET['id']) === true && empty($_GET['id']) !== true)
        $s_id =  $_GET['id'];
    else
        exit;

    require_once('lib/audio/ilyrics.class.php');

    $ret        = array();
    $iLyrics    = new ilyrics($s_id);
    $ret['lyrics']  = $iLyrics->fetch();
    $ret['cover'] = $iLyrics->get_cover();

    echo json_encode($ret);

    return;