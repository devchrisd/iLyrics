<?php
require_once(dirname(__FILE__) . '/dbi/mysql_driver.class.php');

class base_lib
{
    static $media_dbi;

    static function __get_dbi()
    {
        if (self::$media_dbi === NULL)
        {
            // debug(print_r(debug_backtrace(),1));
            self::$media_dbi = new mysql_interface_class(Configure::HOST, Configure::USER, Configure::PASSWD, Configure::MEDIA_DB);
        }
    }
}