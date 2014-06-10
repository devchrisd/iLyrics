<?php

if (isset($_GET['id']) === true && empty($_GET['id']) !== true)
    $s_id =  $_GET['id'];
else
    exit;

require_once('lib/audio/ilyrics.class.php');

$iLyrics = new ilyrics($s_id);
$cover = $iLyrics->get_cover();
echo json_encode($cover);

return;