<?php

require_once('common/configure.class.php');
require_once('common/common.php');

$dir = Configure::AUDIO_PATH;
$mp3 = null;

scan_mp3($dir);

if ($mp3 !== null)
    echo json_encode($mp3);

function scan_mp3($dir)
{
    global $mp3;

    $files = null;
    if ( ($files = array_diff(scandir($dir), array('..', '.', '.DS_Store'))) && count($files)>0 )
    {
        foreach ($files as $file)
        {
            $file = ((empty($dir)) ? '' : $dir) . $file;
            if ( is_dir($file) !== true && is_mp3($file) )
            {
                // debug(__METHOD__ . ' file: ' . $file);
                $mp3[] = $file;
            }
            elseif (is_dir($file) === true)
            {
                debug(__METHOD__ . ' subdir: ' . $file);

                scan_mp3($file.'/');
            }
        }
    }

    return $mp3;
}

function is_mp3($file)
{
    return preg_match('/^[^.^:^?^\-][^:^?]*\.(?i)(mp3)$/',$file);
}
/*
function isfile($file){
    return preg_match('/^[^.^:^?^\-][^:^?]*\.(?i)' . getexts() . '$/',$file);
    //first character cannot be . : ? - 
    //subsequent characters can't be a : ?
    //then a . character and must end with one of your extentions
    //getexts() can be replaced with your extentions pattern
}

function getexts(){
    //list acceptable file extensions here
    return '(app|avi|doc|docx|exe|ico|mid|midi|mov|mp3|
                 mpg|mpeg|pdf|psd|qt|ra|ram|rm|rtf|txt|wav|word|xls)';
} 
*/
