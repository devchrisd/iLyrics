<?php

require_once('dbi.class.php');

class mongo_interface_class extends dbi_class
{
    function __construct($host, $user, $passwd, $db = null)
    {
        parent::__construct($host,$user,$passwd,$db);
    }

    function &connect()
    {
        if ($this->connection === FALSE)
        {
            $this->connection = new Mongo();
                    debug(__METHOD__ . ' : new connection.');

        }
        return $this->connection;
    }

    function select_db($dbname)
    {
        $this->database = $this->connection->selectDB($dbname);
        // debug(__METHOD__ . ' ' . $dbname);
    }

    function select($db, $collection, $return_fields=array(), $query_arr=array())
    {
        $this->select_db($db);
        $dataset = $this->database->$collection->find($query_arr, $return_fields);
        return $dataset;
    }

    function insert($db, $collection, $data_arr)
    {
        $this->select_db($db);
        $this->database->$collection->save($data_arr);
        // debug(__METHOD__ . ', data:' . print_r($data_arr, 1));

        // $this->database->$collection->insert($data_arr);
    }

    function update($db, $collection, $query_arr, $data_arr)
    {
        $this->select_db($db);
        $this->database->$collection->update($query_arr, array('$set' => $data_arr));
    }

    function append($db, $collection, $query_arr, $data_arr)
    {
        $this->select_db($db);
        $this->database->$collection->update($query_arr, array('$push' => $data_arr));
    }

    function remove($db, $collection, $query_arr=array())
    {
         $this->select_db($db);
         $this->database->$collection->remove($query_arr);
                 debug(__METHOD__ );

    }
}