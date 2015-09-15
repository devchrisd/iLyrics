<?php
require_once(dirname(__FILE__) . '/../base_lib.class.php');

class playlist extends base_lib
{
    static public function show_playlist()
    {
        $list = NULL;
        $index = 0;
        self::__get_dbi();

        if (self::$media_dbi->connect())
        {
            $query = 'SELECT p_id, p_name from ' . Configure::MEDIA_DB . '.playlist';

            if ($result = self::$media_dbi->select($query) )
            {
                while( $row = self::$media_dbi->fetch_row_assoc($result))
                {
                    $list[$index]['p_id'] = $row['p_id'];
                    $list[$index++]['p_name'] = $row['p_name'];
                }
            }
        }

        return $list;
    }

    static public function save_playlist($param)
    {
        error_log(__METHOD__);

    }

    static public function get_playlist($p_id)
    {
        $list = NULL;
        $index = 0;
        self::__get_dbi();

        if (self::$media_dbi->connect())
        {
            $query = 'SELECT s.s_id, p_name, song_file, title from ' . Configure::MEDIA_DB . '.playlist_item pi, playlist p, song s where pi.s_id=s.s_id and pi.p_id=p.p_id and p.p_id=' . $p_id . ' order by s.s_id;';

            if ($result = self::$media_dbi->select($query) )
            {
                while( $row = self::$media_dbi->fetch_row_assoc($result))
                {
                    $list[$index]['s_id'] = $row['s_id'];
                    $list[$index]['p_name'] = $row['p_name'];
                    $list[$index]['title'] = $row['title'];
                    $list[$index++]['file'] = $row['song_file'];
                }
            }
        }

        return $list;
    }
}