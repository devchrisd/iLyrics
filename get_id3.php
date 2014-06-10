<?php

if (isset($_GET['id']) === true && empty($_GET['id']) !== true)
    $filename =  $_GET['id'];
else
    exit;

require_once('lib/audio/ilyrics.class.php');

$id3 = '';
$iLyrics = new ilyrics($filename);
$id3 = $iLyrics->get_ID3();
echo json_encode($id3);

return;