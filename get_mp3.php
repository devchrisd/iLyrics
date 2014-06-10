<?php

require_once('lib/common.php');
require_once('lib/audio/mp3_lib.class.php');

$mp3_list = NULL;

$mp3_lib = new mp3_lib();

// scan library and save to DB
if (isset($_GET['r']) === true && empty($_GET['r']) !== true)
{
    $mp3_lib->refresh_DB();
}

// Get list from DB
$mp3_list = $mp3_lib->get_list_from_DB();
if ($mp3_list !== NULL && count($mp3_list) > 0)
{
    echo json_encode($mp3_list);
    exit;
}

