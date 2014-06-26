<?php

require_once('configure.class.php');

function debug($msg)
{
    $debug = TRUE;

    if ($debug) error_log($msg);
}

function _covert_for_URL_string($str)
{
    $pattern = array('/\s+/', '/\'/', '/\?/');
    return preg_replace($pattern, '', $str);
}
