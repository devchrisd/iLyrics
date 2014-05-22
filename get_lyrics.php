<?php
    if (isset($_GET['id']) === true && empty($_GET['id']) !== true)
        $filename =  $_GET['id'];
    else
        exit;

    require_once('ilyrics.class.php');

    $ly = '';
    $iLyrics = new ilyrics($filename);
    $ly = $iLyrics->fetch();
    echo json_encode($ly);

    return;