<?php
// CassandraDB version 0.1
// Software Projects Inc
// http://www.softwareprojects.com
//

// Includes
//$GLOBALS['THRIFT_ROOT'] = '/services/thrift/';
$GLOBALS['THRIFT_ROOT'] = '/var/www/html/cassandra';
require_once $GLOBALS['THRIFT_ROOT'].'/ext/packages/cassandra/Cassandra.php';
require_once $GLOBALS['THRIFT_ROOT'].'/ext/packages/cassandra/cassandra_types.php';
require_once $GLOBALS['THRIFT_ROOT'].'/ext/packages/cassandra/cassandra_constants.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';

class CassandraDB
{
  // Internal variables
  protected $socket;
  protected $client;
  protected $keyspace;
  protected $transport;
  protected $protocol;
  protected $err_str = "";
  protected $display_errors = 0;
  protected $consistency = 1;
  protected $parse_columns = 1;

  // Functions

  // Constructor - Connect to Cassandra via Thrift
  function CassandraDB  ($keyspace, $host = "127.0.0.1", $port = 9160)
  {
    // Initialize
    $this->err_str = '';

    try
    {
    // Store passed 'keyspace' in object
    $this->keyspace = $keyspace;

    // Make a connection to the Thrift interface to Cassandra
    $this->socket = new TSocket($host, $port);
    $this->transport = new TBufferedTransport($this->socket, 1024, 1024);
    $this->protocol = new TBinaryProtocolAccelerated($this->transport);
    $this->client = new CassandraClient($this->protocol);
    $this->transport->open();
    }
    catch (TException $tx)
    {
      // Error occured
      $this->err_str = $tx->why;
      $this->Debug($tx->why." ".$tx->getMessage());
    }
  }

  // Insert Column into ColumnFamily
  // (Equivalent to RDBMS Insert record to a table)
  function InsertRecord  ($table /* ColumnFamily */, $key /* ColumnFamily Key */, $record /* Columns */)
  {
    // Initialize
    $this->err_str = '';

    try
    {
      // Timestamp for update
      $timestamp = time();

      // Build batch mutation
      $cfmap = array();
      $cfmap[$table] = $this->array_to_supercolumns_or_columns($record, $timestamp);
 
      // Insert
      $this->client->batch_insert($this->keyspace, $key, $cfmap, $this->consistency);

      // If we're up to here, all is well
      $result = 1;
    }
    catch (TException $tx)
    {
      // Error occured
      $result = 0;
      $this->err_str = $tx->why;
      $this->Debug($tx->why." ".$tx->getMessage());
    }

    // Return result
    return $result;
  }

  // Insert SuperColumn into SuperColumnFamily
  // (Equivalent to RDMBS Insert record to a "nested table")
  function InsertRecordArray  ($table /* SuperColumnFamily */, $key_parent /* Super CF */,
                $record /* Columns */)
  {
    // Initialize
    $err_str = '';

    try
    {
      // Timestamp for update
      $timestamp = time();

      // Build batch mutation
      $cfmap = array();
      $cfmap[$table] = $this->array_to_supercolumns_or_columns($record, $timestamp);

      // Insert
      $this->client->batch_insert($this->keyspace, $key_parent, $cfmap, $this->consistency);

      // If we're up to here, all is well
      $result = 1;
    }
    catch (TException $tx)
    {
      // Error occured
      $result = 0;
      $this->err_str = $tx->why;
      $this->Debug($tx->why." ".$tx->getMessage());
    }

    // Return result
    return $result;
  }

  // Get record by key
  function GetRecordByKey  ($table /* ColumnFamily or SuperColumnFamily */, $key, $start_from="", $end_at="")
  {
    // Initialize
    $err_str = '';

    try
    {
      return $this->get($table, $key, NULL, $start_from, $end_at);
    }
    catch (TException $tx)
    {
      // Error occured
      $this->err_str = $tx->why;
      $this->Debug($tx->why." ".$tx->getMessage());
      return array();
    }
  }

  // Print debug message
  function Debug      ($str)
  {
    // If verbose is off, we're done
    if (!$this->display_errors) return;

    // Print
    echo date("Y-m-d h:i:s")." CassandraDB ERROR: $str\r\n";
  }

  // Turn verbose debug on/off (Default is off)
  function SetDisplayErrors($flag)
  {
    $this->display_errors = $flag;
  }

  // Set Consistency level (Default is 1)
  function SetConsistency  ($consistency)
  {
    $this->consistency = $consistency;
  }

  // Build cf array
  function array_to_supercolumns_or_columns($array, $timestamp=null)
  {
    if(empty($timestamp)) $timestamp = time();

    $ret = null;
    foreach($array as $name => $value) {
      $c_or_sc = new cassandra_ColumnOrSuperColumn();
      if(is_array($value)) {
        $c_or_sc->super_column = new cassandra_SuperColumn();
        $c_or_sc->super_column->name = $this->unparse_column_name($name, true);
        $c_or_sc->super_column->columns = $this->array_to_columns($value, $timestamp);
        $c_or_sc->super_column->timestamp = $timestamp;
      }
      else
      {
        $c_or_sc = new cassandra_ColumnOrSuperColumn();
        $c_or_sc->column = new cassandra_Column();
        $c_or_sc->column->name = $this->unparse_column_name($name, true);
        $c_or_sc->column->value = $value;
        $c_or_sc->column->timestamp = $timestamp;
      }
      $ret[] = $c_or_sc;
    }

    return $ret;
  }


  // Parse column names for Cassandra
  function parse_column_name($column_name, $is_column=true)
  {
    if(!$column_name) return NULL;

    return $column_name;
  }
 
  // Unparse column names for Cassandra
  function unparse_column_name($column_name, $is_column=true)
  {
    if(!$column_name) return NULL;

    return $column_name;
  }

  // Convert supercolumns or columns into an array
  function supercolumns_or_columns_to_array($array)
  {
    $ret = null;
    for ($i=0; $i<count($array); $i++)
    foreach ($array[$i] as $object)
    {
      if ($object)
      {
        // If supercolumn
        if (isset($object->columns))
        {
          $record = array();
          for ($j=0; $j<count($object->columns); $j++)
          {
            $column = $object->columns[$j];
            $record[$column->name] = $column->value;
          }
          $ret[$object->name] = $record;
        }
        // (Otherwise - not supercolumn)
        else
        {
          $ret[$object->name] = $object->value;
        }
      }
    }

    return $ret;
  }

  // Get record from Cassandra
  function get($table, $key, $super_column=NULL, $slice_start="", $slice_finish="")
  {
    try
    {
    $column_parent = new cassandra_ColumnParent();
    $column_parent->column_family = $table;
    $column_parent->super_column = $this->unparse_column_name($super_column, false);

    $slice_range = new cassandra_SliceRange();
    $slice_range->start = $slice_start;
    $slice_range->finish = $slice_finish;
    $predicate = new cassandra_SlicePredicate();
    $predicate->slice_range = $slice_range;

    $resp = $this->client->get_slice($this->keyspace, $key, $column_parent, $predicate, $this->consistency);

    return $this->supercolumns_or_columns_to_array($resp);
    }
    catch (TException $tx)
    {
    $this->Debug($tx->why." ".$tx->getMessage());
    return array();
    }
  }

  // Convert array to columns
  function array_to_columns($array, $timestamp=null) {
    if(empty($timestamp)) $timestamp = time();

    $ret = null;
    foreach($array as $name => $value) {
      $column = new cassandra_Column();
      $column->name = $this->unparse_column_name($name, false);
      $column->value = $value;
      $column->timestamp = $timestamp;

      $ret[] = $column;
    }
    return $ret;
  }

  // Get error string
  function ErrorStr()
  {
    return $this->err_str;
  }
  
   //$this->client->batch_insert($this->keyspace, $key_parent, $cfmap, $this->consistency);
      // Delete Column from ColumnFamily
    // (Equivalent to RDBMS Insert record to a table)
function DeleteRecord   ($table /* ColumnFamily */, $key /* ColumnFamily Key */, $super_column=null, $columns=null)
    {
        // Initialize
        $this->err_str = '';
		$cnt_retries = 0;
		$flag_timeout = 0;
		
        // If we failed init, bail
        if ($this->flag_failed_init) return 0;
          
		do
		{
		
		try
		{
			try
			{   
				// Timestamp for update
				$timestamp = time();
				
				// Reset exception
				$tx = array();

				if (!empty($columns))
				{
				$slice_range = new cassandra_SliceRange();
				//$slice_range->count = 100;
				$slice_range->start = $super_column;
				$slice_range->finish = $super_column;
				$predicate = new cassandra_SlicePredicate();
				$predicate->column_names = $columns;
				}
				else $predicate = null;


				$cfmap = array();
				
				$cfmap[$key][$table][] = new cassandra_mutation(
					array("deletion"=> new cassandra_deletion( array("super_column"=>$super_column,
					"predicate"=>$predicate,"clock"=>new cassandra_Clock( array('timestamp'=>$timestamp) ),
					"timestamp"=>time()) )) );

				$this->client->batch_mutate($cfmap, $this->consistency);
				
				// If we're up to here, all is well
				$result = 1;
			}
			catch (TException $tx)
			{    
				// Error occured
				$result = 0;              
				$this->err_str = $tx->why;
				$this->Debug($tx->why." ".$tx->getMessage());
			}
			
			// If this was a timeout error
			if (!$result)
			{
				// If this was a timeout error
				if (Strcasecmp(get_class($tx),"cassandra_TimedOutException")==0 ||
				    Strcasecmp(get_class($tx),"cassandra_UnavailableException")==0)
				{
					// Print error and retry
					$this->Debug("Timeout error detected. Sleeping 1 second and retrying $cnt_retries / 10 times");
					sleep(1);
					$flag_timeout = true;
					$cnt_retries++;
					
					// Disconnect and re-connect
					$this->Disconnect();
					$this->ConnectToKeyspace();
				}
				// (Otherwise - this was not a timeout)
				if (!empty($tx))
				{
					// Print exception info
					print_r($tx);
				}
			}
		}
		catch (TException $tx)
		{
		}
		
		} while ($flag_timeout && $cnt_retries<10);
                        
        // Return result
        return $result;
    }

} 
?>
