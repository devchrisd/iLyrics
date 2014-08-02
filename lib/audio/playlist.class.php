<?php
require_once(dirname(__FILE__) . '/../configure.class.php');
require_once(dirname(__FILE__) . '/../common.php');

class playlist
{
    const PLAYLIST_PATH = Configure::PLAYLIST_PATH;
    const MEDIA_DB      = Configure::MEDIA_DB;

    public $p_id;
    public $p_str;

    private $media_dbi;

    function __construct($p_id=NULL)
    {
        $this->media_dbi = NULL;

        $this->p_id       = $p_id;
        $this->p_str     = '';
    }

    function __get_dbi()
    {
        if ($this->media_dbi === NULL)
        {
            // debug(print_r(debug_backtrace(),1));
            $this->media_dbi = new mysql_interface_class(Configure::HOST, Configure::USER, Configure::PASSWD, Configure::MEDIA_DB);
        }
    }

    function get_playlist()
    {
        $result = FALSE;
        // get album cover
        if (empty($this->p_id) === FALSE)
        {
            // get data 
            $this->__get_dbi();
            $query = 'SELECT title, song_list FROM ' . Configure::MEDIA_DB . 'playlist WHERE p_id=' . $this->media_dbi->escape_string($this->p_id);
            if ($result = $this->media_dbi->select($query) )
            {
                if( $row = $this->media_dbi->fetch_row_assoc($result))
                {
                    $result['title'] = $this->title = $row['title'];
                    // get file content
                    $this->p_str =  $result['p_str'] = $row['song_list'];
                }
            }
        }
        return $result;
    }

    function new_playlist($pl_title, $song_list)
    {
        $this->__get_dbi();
        $query = 'INSERT INTO playlist (`title`, `song_list`) VALUES("'
            . $this->media_dbi->escape_string($pl_title) . '", "'
            . $this->media_dbi->escape_string($song_list)
            . '")';

        // insert into table
        $p_id = $this->media_dbi->insert($query);
        return $p_id;
    }

    function save_playlist($contents)
    {
        $ret = FALSE;

        try
        {

        }
        catch(Exception $e)
        {
            debug( __METHOD__ . ' Exception caught: ' . $e->getMessage() . "\n");
        }

        return $ret;
    }
}