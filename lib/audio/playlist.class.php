<?php
require_once(dirname(__FILE__) . '/../base_lib.class.php');

class playlist extends base_lib
{
    public function show_playlist()
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

    public function save_playlist($param)
    {
        self::__get_dbi();

        if (! self::$media_dbi->connect())
        {
            return false;
        }

        if (isset($param['p_id']) && !empty($param['p_id']))
        {
            // update playlist
            $p_id = $param['p_id'];
            $this->reset_playlist_items($p_id);
            $this->add_playitems($p_id, $param['s_id']);
        }
        else if (isset($param['p_name']) && !empty($param['p_name']))
        {
            $p_id = $this->new_playlist($param);
        }
        else
        {
            return null;
        }

        return $p_id;
    }

    private function reset_playlist_items($p_id)
    {
        $query = 'DELETE FROM ' . Configure::MEDIA_DB . ".playlist_item WHERE p_id='" . self::$media_dbi->escape_string($p_id) . "'";

        self::$media_dbi->update($query);
    }

    private function new_playlist($param)
    {
        $p_id   = false;
        $p_name = $param['p_name'];
        $query  = 'REPLACE INTO ' . Configure::MEDIA_DB . ".playlist set p_name='" . self::$media_dbi->escape_string($p_name) . "'";

        if (self::$media_dbi->insert($query))
        {
            $p_id = self::$media_dbi->last_insert_id();
        }

        if ($p_id == false)
        {
            error_log(__METHOD__ . ' failed create new playlist: ' . $query);
            return false;
        }

        $this->add_playitems($p_id, $param['s_id']);

        return $p_id;
    }

    private function add_playitems($p_id, $s_ids)
    {
        // insert play items
        foreach ($s_ids as $s_id)
        {
            $query = "INSERT INTO " . Configure::MEDIA_DB . ".playlist_item SET p_id='" . self::$media_dbi->escape_string($p_id) . "', s_id='" . self::$media_dbi->escape_string($s_id) . "'";

            if (self::$media_dbi->insert($query) == false)
            {
                error_log(__METHOD__ . ' failed create new playlist_item: ' . $query);
                return false;
            }
        }
    }

    public function get_playlist($p_id)
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