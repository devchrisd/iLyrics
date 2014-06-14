<?php

if (isset($_GET['id']) === true && empty($_GET['id']) !== true)
    $s_id =  $_GET['id'];
else
    exit;

require_once('lib/audio/mp3_lib.class.php');

$tag = '';
$tag = mp3_lib::get_song_info($s_id);
echo json_encode($tag);

return;