<?php
    $filename = isset($_GET['id']) ? $_GET['id'] : '';
    $ly = '';

    // ? parse lrc file and return jason
    $filename = 'audio/' . substr($filename, 0, strrpos($filename, '.')) . '.lrc';
    if (file_exists($filename))
    {
        //$fp = fopen($ly, 'r');
        $ly = file_get_contents($filename);
    }
    echo $ly;
    return;