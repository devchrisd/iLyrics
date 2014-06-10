<?php

require_once('configure.class.php');

function debug($msg)
{
    $debug = TRUE;

    if ($debug) error_log($msg);
}