<?php
require_once('dbi.class.php');

define('mysql_interface_default_autocommit',true);

define('MYSQL_DUPLICATE_KEY_ERROR', 1022);
define('MYSQL_NON_UNIQUE_ERROR', 1062);
define('MYSQL_SERVER_GONE', 2006);
define('MYSQL_SERVER_LOST', 2013);

class mysql_interface_class extends dbi_class
{
   function __construct($host, $user, $passwd, $db = null,
         $ac = mysql_interface_default_autocommit)
   {
        parent::__construct($host,$user,$passwd,$db,$ac);
   }

    function &connect()
    {
        $retry = $this->retry;
        while($this->connection == FALSE && --$retry >= 0)
        {
            $c = mysql_connect($this->host, $this->user, $this->passwd, false);
            if($c)
            {
                $this->connection = &$c;
                if($this->database != null)
                {
                    $r = $this->select_db($this->database);
                    if(!$r)
                    {
                        $c = null;
                        $msg = "failed to select database " . $this->database
                              . " on " . $this->user . "@" .  $this->host;
                        debug(__METHOD__ . " - " . $msg);

                        throw new Exception("DBI " .$this->user . "@" .  $this->host . ": $msg  (" . $this->error() . ")", $this->errno());
                    }
                }

                debug(__METHOD__ . " - successfully connected to " . $this->user . "@" .  $this->host);
            }
            else
            {
                debug(__METHOD__ . " - failed to connect to " . $this->user . "@" .  $this->host);
                throw new Exception("DBI failed to connect to " . $this->user . "@" .  $this->host, $this->errno());
            }
        }
        return $this->connection;
    }

    function close()
    {
        $result =NULL;
        if ($this->connection){
            $result = mysql_close($this->connection);
        }
        if ($result){
            $this->connection=NULL;
        }
        return $result;
    }

   /**
    * Set the value of the auto-commit flag.
    *
    * @param boolean $ac
    * The value of the auto-commit flag.
    *
    * @return boolean
    * true if the autocommit flag value was successfully changed; false otherwise.
    */
   function set_auto_commit($ac)
   {
      $result = false;
      if($this->is_connected())
      {
         $val = $ac ? 1 : 0;
         $sql = "SET AUTOCOMMIT = $val";
         $result = $this->update($sql);
      }
      else
      {
         ;
      }
      return $result;
   }

   function start_transaction()
   {
         return $this->query("BEGIN");
   }

   function commit_transaction()
   {
        return $this->query("COMMIT");
   }

   function rollback_transaction()
   {
        return $this->query("ROLLBACK");
   }

   function select_db($dbname)
   {
      $r = false;

     if($this->connection)
     {
        $r = mysql_select_db($dbname,$this->connection);
        if($r)
        {
            debug(__METHOD__ . " - selected database " . $dbname);
        }
        else
        {
           $msg = "failed to select database " . $dbname
                 . " on " . $this->user . "@" .  $this->host
                 . ": " . $this->error();
           debug(__METHOD__ . " - " . $msg);
           throw new Exception("DBI: " . $this->user . "@" .  $this->host . ": " . $msg, $this->errno());
        }
     }
     else
     {
        debug(__METHOD__ . " - not connected.  deferring selection of database " . $dbname);
     }
      
      return $r;

   }

   function escape_string($string)
   {
        //return @mysql_escape_string($string);
        if ($this->connect())
        {
            return mysql_real_escape_string($string, $this->connection);
        }
        else
        {
            debug( 'Connection failed when trying to escape string:['.$string.']');
            return NULL;
        }
   }

   function error()
   {
      return mysql_error($this->connection);
   }

   function errno()
   {
      return mysql_errno($this->connection);
   }

   function num_rows(&$result)
   {
      return mysql_num_rows($result);
   }

   function affected_rows()
   {
      return mysql_affected_rows($this->connection);
   }

    /**
    *  Note: mysql_info() returns a non-FALSE value for the INSERT ... VALUES
    *  statement only if multiple value lists are specified in the statement.
    **/
   function matched_rows()
   {
      $mysql_info=mysql_info($this->connection);
      preg_match("/Rows matched: ([0-9]*)/", $mysql_info,$matched_rows);
      return $matched_rows[1];
   }

   function fetch_array(&$resultset)
   {
      return mysql_fetch_array($resultset);
   }

   function fetch_row_assoc(&$resultset)
   {
      return mysql_fetch_assoc($resultset);
   }

   function fetch_row(&$resultset,$rownumber = null)
   {
      return mysql_fetch_row($resultset);
   }

   function fetch_object(&$resultset,$rownumber = null)
   {
      return mysql_fetch_object($resultset);
   }

   function release(&$rs)
   {
      return mysql_free_result($rs);
   }

   function data_seek(&$resource,$rnum)
   {
      return mysql_data_seek($resource,$rnum);
   }

   function last_insert_id()
   {
      $result = null;
      $query = "SELECT LAST_INSERT_ID()";
      $rs = $this->select($query);
      if(($row = $this->fetch_array($rs)))
      {
         $result = $row[0];
      }

      if(!$result)
      {
        throw new Exception("DBI " .$this->user . "@" .  $this->host . ": failed to retreive last_insert_id: " . $this->error(), $this->errno());
      }
      return $result;
   }

   function &query($query,$supp_err_no)
   {
      $result = false;
      ++self::$querycounter;
      
      if(! $this->connection)
      {
         if(! $this->connect())
         {
// connection failure logs itself           
//            $this->trace("failed to connect to " . $this->user . "@" .  $this->host);
         }
      }
        $logm = 'QUERY: `' . preg_replace("/[\s]+/"," ",$query) . "'";
      if($this->connection)
      {
         if (!is_array($supp_err_no))
         {
            debug(__METHOD__ . ' - ' . $logm);

            $t_start = microtime(true);

            $result = mysql_query('/* '. $this->get_backtrace_caller() .' */ '. $query,$this->connection);

            $t_end = microtime(true);

            $elapsed = $t_end - $t_start;

            if (($elapsed) > SLOW_QUERY_THRESHOLD)
            {
                debug('dbi ' . date('Y-m-d H:i:s') . ' - SLOW: ' . $query . '[  ' . round($elapsed, 3) . 's ]');
            }
         }
         else
         {
            $logm .= " with tolerance for errors (";
            foreach($supp_err_no as $no)
            {
                $logm .= "$no ";
            }
            $logm .= ")";
            debug(__METHOD__ . ' - ' . $logm);
            $result = mysql_query('/* '. $this->get_backtrace_caller() .' */ '. $query,$this->connection);
         }
      }

      if(!$result)
      {
        $err = $this->errno();
        if(is_array($supp_err_no) && in_array($err,$supp_err_no))
        {
            debug('dbi ' . "suppressing exception for error no. $err");
        }
        else
        {
            debug('dbi ' . date('Y-m-d H:i:s') . " - " . $this->user . "@" .  $this->host . ": SQL statement failed: " . $this->error() . " `$query''", $this->errno());
            throw new Exception("DBI " .$this->user . "@" .  $this->host . ": SQL statement failed: " . $this->error() . " `$query''", $this->errno());
        }
      }

      return $result;
   }

   function &unbuffered_query($query,$supp_err_no)
   {
      $result = false;
      ++self::$querycounter;
      
      if(! $this->connection)
      {
         if(! $this->connect())
         {
            $this->trace("failed to connect to " . $this->user . "@" .  $this->host);
         }
      }

      if($this->connection)
      {
        $result = mysql_unbuffered_query($query,$this->connection);
      }

      if(!$result)
      {
        $err = $this->errno();
        if(is_array($supp_err_no) && in_array($err,$supp_err_no))
        {
            // deliberatle silenced error messages
        }
        else
        {
            throw new Exception("DBI " .$this->user . "@" .  $this->host . ": SQL statement failed: " . $this->error() . " `$query''", $this->errno());
        }
      }
      return $result;
   }

   function &select($query,$supp_err_no =null)
   {
        if($this->debug & self::$db_trace_select)
        {
            $this->trace($query);
        }

        $ret = $this->query($query,$supp_err_no);
        return $ret;
   }

   function &update($query,$supp_err_no =null)
   {
        if($this->debug & self::$db_trace_update)
        {
            $this->trace($query);
        }

        $ret = $this->query($query,$supp_err_no);
        if(!$ret)
        {
            $this->error_flag = $this->error();
        }

        return $ret;
   }

   function escape_string_no_connection( $string)
   {
        $result = '';
        $rarr = array( chr(0) => '\0',
                        chr(8) => '\b',
                        chr(10) => '\n',
                        chr(13) => '\r',
                        chr(9) => '\t',
                        chr(26) => '\z',
                        '%' => '\%',
                        '_' => '\_',
                        '\'' => '\\\'',
                        '"' => '\"',
                        '\\' => '\\\\');
        $l =strlen( $string);
        for ($i=0; $i<$l;$i++)
        {
            $ch = substr($string, $i, 1);
            if ( isset( $rarr[$ch]))
            {
                $result .= $rarr[$ch];
            }
            else
            {
                $result .= $ch;
            }
        }
        return $result;
   }
   
   function get_backtrace_caller()
   {
       $backtrace = debug_backtrace();
       // It goes through several steps before getting here
       $trace = count($backtrace) > 6 ? $backtrace[6]: $backtrace[count($backtrace)-1];
       
       $comment = $trace['file'] .':'. $trace['line'] .' ';
       
       if (!empty($trace['class'])) {
           $comment .= $trace['class'] .'::';
       }
       
       if (!empty($trace['function'])) {
           $comment .= $trace['function'] .'()';
       }
       
       return $comment;
   }
}
?>
