<?php

require_once(dirname(__FILE__) . '/../common.php');

// Lets log queries that are slow.
define('SLOW_QUERY_THRESHOLD', 3);

/**
 * a thin API wrapper class to isolate database implementation from the code base.  It also contains
 * certain tracing and control instrumentation
 */

Abstract class dbi_class
{
    public static $db_trace_select = 0x2;
    public static $db_trace_update = 0x4;
    public static $db_trace_all = 0x6;

    protected static $querycounter = 0;

    var $connection;
    var $database;
    var $user;
    var $passwd;
    var $host;
    var $auto_commit;

    var $trace_buffer;
    var $debug;
    var $error_flag;

  /**
   * Create an instance of the dbi_class object.
   *
   * @param string $host
   * The host to connect to.
   *
   * @param string $user
   * The username to use for the connection.
   *
   * @param string $passwd
   * The password to use for the connection.
   *
   * @param string $db
   * The name of the database to select.  This parameter is
   * optional and defaults to no database if unspecified.
   *
   * @param boolean $ac
   * Auto-commit flag.  This parameter is null and defaults to
   * true if unspecified.
   *
   * @return dbi_class
   */
   function __construct($host, $user, $passwd, $db = null, $ac = true)
   {
      $this->user   = $user;
      $this->passwd = $passwd;
      $this->host   = $host;
      $this->database   = $db;
      $this->connection = FALSE;

      // the number of times to attempt reconnection
      $this->retry = 1;

      $this->debug = false;
      $this->trace_buffer = "";
      $this->auto_commit  = $ac;
   }

   static function get_query_count()
   {
    return self::$querycounter;
   }

    /**
     * Set the number of times to retry the connection.
     *
     * @param integer $n
     */
    function set_retry($n)
    {
        $this->retry = $n;
    }

   /**
    * Get the username used for the connection.
    *
    * @return string
    */
   function get_user()
   {
      return $this->user;
   }

   /**
    * Get the host used for the connection.
    *
    * @return string
    */
   function get_host()
   {
      return $this->host;
   }

   /**
    * unroll any pending error messages
    */
   function clear_error_flag()
   {
      $this->error_flag = false;
   }

   /**
    * Get the value of the auto-commit flag.
    *
    * @return boolean
    */
   function get_auto_commit()
   {
      return $this->auto_commit;
   }

   /**
    * Establish a connection to the database.  This generally should not be called directly by
    * application programmers; calls to update() or select() detect if the dbi object is connected
    * and will connect automatically if need be.
    *
    * @return unknown
    * a reference to a connection resource
    */
   function &connect()
   {
        return;
   }

   /**
    * USE a specific database.  This function is intelligent in the, when unconnected, it merely
    * reassigns the name of the default database. The USE command will be automatically issued
    * upon connection.   If connected, it wll issue a USE command only if
    * the names database is not the current database.
    *
    * @param string $dbname
    * the name of the database to use
    *
    * @return boolean
    * true if the database was successfully selected; false otherwise.
    */
    function select_db($dbname)
   {
        return;
   }

   /**
    * Used to issue a SELECT query.  Any query that resturns a result set should use this method.
    *
    * @param string $query
    * The SELECT query to be executed.
    *
    * @param array $supp_err_no
    * An optional array of integer error codes that should be ignored by the query to
    * reduce the amount of exception logging.
    *
    * @return resource | boolean
    * Returns a resource representing a resultset if successful, or a boolean false on failure.
    */
   function select($query,$supp_err_no = null)
   {
      return false;
   }

   /**
    * Execute an INSERT query.
    *
    * @param string $query
    * The INSERT query to be executed
    *
    * @param array $supp_err_no
    * An optional array of integer error codes that should be ignored by the query to
    * reduce the amount of exception logging.
    *
    * @return boolean
    * True on success, otherwise false.
    */
   function insert($query, $supp_err_no =null)
   {
      return $this->update($query, $supp_err_no);
   }

   /**
    * Execute a DELETE query.
    *
    * @param string $query
    * The DELETE query to be executed
    *
    * @param array $supp_err_no
    * An optional array of integer error codes that should be ignored by the query to
    * reduce the amount of exception logging.
    *
    * @return boolean
    * True on success, otherwise false.
    */
   function delete($query, $supp_err_no =null)
   {
      return $this->update($query, $supp_err_no);
   }

   /**
    * Execute any query which modifies data or any other routine which simply does
    * not return a result set
    *
    * @param string $query
    * The query to be executed.
    *
    * @param array $supp_err_no
    * An optional array of integer error codes that should be ignored by the query to
    * reduce the amount of exception logging.
    *
    * @return boolean
    * True on success; otherwise false.
    */
    function update($query,$supp_err_no =null)
    {
        return false;
    }

   /**
    * This function sohuld only be used internally.  Use insert, update, delete or
    * select methods instead.
    *
    * @param string $query
    * The SQL query to execute
    *
    * @param array $supp_err_no
    * An optional array of integer error codes that should be ignored by the query to
    * reduce the amount of exception logging.
    *
    * @return resource | boolean
    * For SELECT queries, if successful, returns a resource resource representing a
    * resultset.  For other queries, returns true if successful and false if unsuccessful.
    */
   function query($query,$supp_err_no =null)
   {
      return false;
   }

   /**
    * This function is used to produce an *unbuffered* result handle from a query.  There
    * can be no other queriees performed on the same handle until the entire result set
    * has been exhausted *and* freed (using dbi->release)
    *
    * :NOTE: DO NOT USE THIS FUNCTION WITHOUT APPROVAL AND APPROPRIATE KNOWLEDGE OF ITS
    *        VARIOUS CAVEATS AND LIMITATIONS.  FOR MORE INFORMATION REFER TO THE MANUAL
    *        PAGE:
    *
    *        http://php.net/mysql_unbuffered_query
    *
    * :NOTE: DO NOT USE THIS FUNCTION UNLESS YOU KNOW EXACTLY WHAT IT DOES AND DOESN'T DO.
    *
    * @param string $query
    * The SQL query to execute
    *
    * @param array $supp_err_no
    * An optional array of integer error codes that should be ignored by the query to
    * reduce the amount of exception logging.
    *
    * @return resource | boolean
    * For SELECT queries, if successful, returns a resource resource representing a
    * resultset.  For other queries, returns true if successful and false if unsuccessful.
    */
   function unbuffered_query($query,$supp_err_no =null)
   {
        return false;
   }

   /**
    * releases the underlaying vendor resource of a result set
    *
    * @param resource $resultset
    * the result set resource to release
    *
    * @return unknown
    */
   function release(&$resultset)
   {
        return false;
   }

   /**
    * move the cursor in a result set to a specified row;
    *
    * @param resource $resultset
    * the result set to manipulate
    *
    * @param integer $rnum
    * the record number to seek the pointeer to
    *
    * @return boolean
    * false on error, true otherwise
    */
   function data_seek(&$resultset, $rnum)
   {
        return false;
   }

   /**
    * closes the dbi_connection but should leave the object in acceptable state for reconnection
    *
    * @return unknown
    */
   function close()
   {
        return false;
   }

   /**
    * Determine whether or not this instance of dbi_class is connected.
    *
    * @return boolean
    * true if this dbi object is already connected, false otherwise.
    */
   function is_connected()
   {
      return (($this->connection != null)? TRUE : FALSE);
   }

   /**
    * escapes a string via escape_string and wraps it in single quotes
    *
    * @param string $string
    * the string to be escaped
    *
    * @return string
    * the fully-escaped and quoted string
    */
   function enquote_string($string)
   {
      return "'" . $this->escape_string($string) . "'";
   }

   /**
    * escapes a string for use as a quoted char string; linked to the vendor specific routine
    *
    * @param string $string
    * the string to be escaped
    *
    * @return string
    * the fully-escaped string
    */
   function escape_string($string)
   {
        return false;
   }

   /**
    * returns the value of the auto-increment primary key value generated by the
    * most recent insert operation.
    *
    * @return integer
    * the value of the most recently inserted id created through insertion to an
    * auto-increment column.
    */
   function last_insert_id()
   {
        return false;
   }

   /**
    * returns the most recent vendor-specific error message that was generated
    * by a failed query.
    *
    * @return string
    * the most recent vendor-specific error message
    */
   function error()
   {
      return $this->error();
   }

   /**
    * returns the most recent vendor-specific error number that was generated
    * by a failed query.
    *
    * @return string
    * the most recent vendor-specific error number
    */
   function errno()
   {
      return $this->errno();
   }

   /**
    * Returns the number of rows in a given result set.  (The result set is
    * the resource returned by a select() method.)
    *
    * @param resource $result
    * a refernece to a result set
    *
    * @return integer
    * the number of rows in this results set
    */
   function num_rows(&$result)
   {
      return false;
   }

   /**
    * Returns the number of rows affected by the most recent update or delete
    * operation.
    *
    * @return integer
    * the number of rows affected by the most recent update or delete statement
    */
   function affected_rows()
   {
      return false;
   }

   /**
    * Returns the number of rows matched by the most recent query.
    *
    * @return integer
    * the number of rows matched by the most recent query
    */
   function matched_rows()
   {
      return intval($this->matched_rows());
   }

   /**
    * Returns a row of a resultset resource as an associative
    * array of column values indexed by column names.
    *
    * @param resource $resultset
    * a reference to a resource representing a result set
    *
    * @param integer $rownumber
    * a specific row to fetch; this is optional
    *
    * @return array
    * returns either the next row or the specified row from the result set
    * as an associative arrary.  the keys of the array are the column names.
    */
   function fetch_row_assoc(&$resultset,$rownumber = null)
   {
      return false;
   }

   /**
    * Returns a row of a resultset resource as an associative
    * array of column values indexed by column names and column
    * position (base 0).
    *
    * @param resource $resultset
    * a reference to a resource reprenting a result set
    *
    * @param integer $rownumber
    * a specific row to fetch; this is optional
    *
    * @return array
    * return either the next row or a specific row from the result set
    * as an array indexed by both column name and column position (base 0)
    */
   function fetch_row(&$resultset,$rownumber = null)
   {
      return false;
   }

   /**
    * Returns a row of a resultset resource as an associative
    * array of column values indexed by column position (base 0).
    *
   * @param resource $resultset
    * a reference to a resource reprenting a result set
    *
    * @param integer $rownumber
    * a specific row to fetch; this is optional
    *
    * @return array
    * return either the next row or a specific row from the result set
    * as an array indexed by column position (base 0)
    */
   function fetch_array(&$resultset,$rownumber = null)
   {
      return false;
   }

   /**
    * Returns a row of a resultset resource as an object.
    *
    * @param resource $resultset
    * a reference to a resource reprenting a result set
    *
    * @param integer $rownumber
    * a specific row to fetch; this is optional
    *
    * @return unknown
    * return either the next row or a specific row from the result set
    * as an object representation
    */
   function fetch_object(&$resultset,$rownumber = null)
   {
      return false;
   }

   /**
    * Begin a transaction on the database.
    *
    * @return boolean
    * True if the transaction was successfully started; otherwise, false.
    */
   function start_transaction()
   {
        return false;
   }

   /**
    * Commit a transaction on the database.
    *
    * @return boolean
    * True if the transaction was successfully committed; otherwise, false.
    */
   function commit_transaction()
   {
        return false;
   }

   /**
    * Rollback a transaction on the database.
    *
    * @return boolean
    * True if the transaction was successfully rolled back; otherwise, false.
    */
   function rollback_transaction()
   {
        return false;
   }

    /**
     * return a comma separated string of all column's results
     * 
     * @param string $query
     * @param string $column specific field to return
     * @return string blank string on error
     **/
    function fetch_all_csv(&$rs, $column, $limit=10000)
    {
        $result = array();
        if($rs)
        {
            $i=0;
            while ( ($row = $this->fetch_array($rs)) && $i++ < $limit) {
                $result[] = $row[$column];
            }
            $this->data_seek($rs, 0);
        }
        return implode(",",$result);
    }

   /**
    * clear the trace buffer
    */
   function clear_trace()
   {
      $this->trace_buffer = null;
   }

   /**
    * write a line to the trace bufer
    *
    * @param string $line
    * the line to be written to the trace buffer
    */
   function trace($line)
   {
      $this->trace_buffer .= "$line\n";
      $this->trace_buffer .= "----------------\n";
   }

   /**
    * return the contents of the trace buffer wrapped in SGML comments
    */
   function printtrace()
   {
      debug( "\n<!--\n" . $this->trace_buffer . "\n -->\n" );
   }

   /**
    * return the contents of the trace buffer
    */
   function get_trace()
   {
      return $this->trace_buffer;
   }


   /**
    * set the debug flag
    *
    * @param unknown $b
    * the state to set the debug flag to
    */
   function set_debug($b)
   {
      $this->debug = $b;
      return $this->debug;
   }

   /**
    * get the state of the debug flag
    *
    * @return unknown
    * the state of the debug flag
    */
   function get_debug()
   {
      return $this->debug;
   }

}
?>
