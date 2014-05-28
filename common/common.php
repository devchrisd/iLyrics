<?php
    date_default_timezone_set('America/Toronto');

    function debug($msg)
    {
        $debug = TRUE;

        if ($debug) error_log($msg);
    }