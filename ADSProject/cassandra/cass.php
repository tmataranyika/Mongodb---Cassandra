<?php
$GLOBALS['THRIFT_ROOT'] = 'C:/xampp/htdocs/cassandra';
require_once $GLOBALS['THRIFT_ROOT'].'/ext/packages/cassandra/Cassandra.php';
require_once $GLOBALS['THRIFT_ROOT'].'/ext/packages/cassandra/cassandra_types.php';
require_once $GLOBALS['THRIFT_ROOT'].'/ext/packages/cassandra/cassandra_constants.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';

// Get Cassandra object
function GetCassandraDB($keyspace)
{
	global	$global_cassandra;

	// If we don't have cassandra defined yet
	if (!isset($global_cassandra))
	{
		// Initialize it
		$global_cassandra = new CassandraDB($keyspace);
	}

	// Return cassandra object
	return $global_cassandra;
}

// Cassandra class
class CassandraDB
{
	// Internal variables
	protected $socket;
	protected $client;
	protected $keyspace;
	protected $host;
	protected $port;
	protected $transport;
	protected $protocol;
	protected $err_str = "";
	protected $display_errors = 0;
	protected $consistency = cassandra_ConsistencyLevel::ONE; 
	protected $parse_columns = 1;
	protected $flag_failed_init = 0;

	// Functions

	// Constructor - Connect to Cassandra via Thrift
	function CassandraDB	($keyspace, $host = "127.0.0.1", $port = 9160)
	{
		// Store passed parameters in object
		$this->keyspace = $keyspace;
		$this->host		= $host;
		$this->port		= $port;
		
		// Support for global variables
		if (isset($GLOBALS['cassandra_host'])) 	$this->host = $GLOBALS['cassandra_host'];
		if (isset($GLOBALS['cassandra_port']))	$this->port	= $GLOBALS['cassandra_port'];

		// Connect
		$this->ConnectToKeyspace();
	}

	// Connect
	function ConnectToKeyspace()
	{
		// Initialize
		$this->err_str = '';
		$this->flag_failed_init = 0;

		try
		{
  		// Make a connection to the Thrift interface to Cassandra
  		$this->socket = new TSocket($this->host, $this->port);
  		$this->transport = new TBufferedTransport($this->socket, 1024, 1024);
  		$this->protocol = new TBinaryProtocolAccelerated($this->transport);
  		$this->client = new CassandraClient($this->protocol);
  		$this->transport->open();
		$this->client->set_keyspace($this->keyspace);
		}
		catch (TException $tx)
		{
			// Error occured
			$this->flag_failed_init = 1;
			$this->err_str = $tx->why;
			$this->Debug($tx->why." ".$tx->getMessage());
		}	
	}
	
	// Disconnect
	function Disconnect()
	{
		// Free resources
		$this->transport->close();
		$this->socket->close();
		unset($this->socket);
        unset($this->protocol);
        unset($this->client);
        unset($this->transport);
	}
	
	// Destructor
	function __destruct()
	{
		// Free resources
		$this->Disconnect();
	}

	// Add columnfamily
	function AddColumnFamily($cfdef)
	{
        // Initialize
        $result = 0;

        try
        {
			// Add column family
			$this->client->system_add_column_family($cfdef);
		}
        catch (TException $tx)
        {
            // If columnfamily already defined
            if (strpos($tx->why,"already defined")!==false)
            {
                // Consider this a success
                $result = 1;
            }
            // (Otherwise - different error)
            else
            {
            $this->Debug("AddColumnFamily error");
            print_r($tx);

            // Error occured
            $this->err_str = $tx->why;
            $this->Debug($tx->why." ".$tx->getMessage());
            }
        }

        // Return result
        return $result;
	}

    // Add keyspace 
    function AddKeyspace($cfdef)
    {
        // Initialize
        $result = 0;

        try
        {
        // Prepare record
        $ks     = new cassandra_KsDef(array("name"=>$this->keyspace
                ,"strategy_class"=>"org.apache.cassandra.locator.RackAwareStrategy",
                "replication_factor"=>"2", "cf_defs"=>$cfdef));

        // Create new keyspace
        $this->client->system_add_keyspace($ks);

        // If we're up to here - all is well
        $result = 1;
        $this->flag_failed_init = 0;

		// Disconnect
		$this->Disconnect();

		// Reconnect
		$this->ConnectToKeyspace();
	
        }
        catch (TException $tx)
        {
            // If keyspace already exists
            if (strpos($tx->why,"already exists")!==false)
            {
                // Consider this a success
                $result = 1;
            }
            // (Otherwise - different error)
            else
            {
			$this->Debug("AddKeyspace error");
			print_r($tx);
			
            // Error occured
            $this->err_str = $tx->why;
            $this->Debug($tx->why." ".$tx->getMessage());
            }
        }

        // Return result
        return $result;
    }
	
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

	// Insert Column into ColumnFamily 
	// (Equivalent to RDBMS Insert record to a table)
	function InsertRecord	($table /* ColumnFamily */, $key /* ColumnFamily Key */, $record /* Columns */)
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

				// Build batch mutation
				$cfmap = array();
				$cfmap[$key][$table] = $this->array_to_supercolumns_or_columns($record, $timestamp);
		
				// Insert
				$mutation_map = null;
				$mutation_map["$table"][$key] =  $cfmap;

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
				if (Strcasecmp(get_class($tx),"cassandra_TimedOutException")==0)
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

	// Insert SuperColumn into SuperColumnFamily
	// (Equivalent to RDMBS Insert record to a "nested table")
	function InsertRecordArray	($table /* SuperColumnFamily */, $key_parent /* Super CF */, 
								$record /* Columns */)
	{
        // Initialize
        $err_str = '';

        // If we failed init, bail
        if ($this->flag_failed_init) return 0;

		try
		{
            // Timestamp for update
            $timestamp = time();

            // Build batch mutation
            $cfmap = array();
            $cfmap[$table] = $this->array_to_supercolumns_or_columns($record, $timestamp);
   
            // Insert
            $mutation_map = array("$key_parent"=>array("Standard1"=>array()));        
            $mutation_map["$key_parent"]["Standard1"] =  $cfmap;
            $this->client->batch_mutate($this->keyspace, $mutation_map, $this->consistency);
//          $this->client->batch_insert($this->keyspace, $key_parent, $cfmap, $this->consistency); 

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

	// Get keys
	function GetKeys($table /* ColumnFamily or SuperColumnFamily */, $start_from="", $end_at="", $super_column=null)
	{
        // Initialize
        $err_str = '';

        // If we failed init, bail
        if ($this->flag_failed_init) return array();

        try
        {
			$range = new cassandra_KeyRange(array("start_key"=>$start_from,"end_key"=>$end_at,"count"=>100));

        	$column_parent = new cassandra_ColumnParent();
        	$column_parent->column_family = $table;
        	$column_parent->super_column = $this->unparse_column_name($super_column, false);

        	$slice_range = new cassandra_SliceRange();
        	$slice_range->start = "ignore";//(empty($slice_start) ? "" : $slice_start);
        	$slice_range->finish = "ignore";//(empty($slice_finish) ? "" : $slice_finish);
        	$slice_range->count = 100;
        	$predicate = new cassandra_SlicePredicate();
        	$predicate->slice_range = $slice_range;

            $resp = $this->client->get_range_slices($column_parent,  $predicate, $range, /*$this->consistency*/cassandra_ConsistencyLevel::ONE);


            if (!empty($resp))
            {
				$i = 0;
				foreach ($resp as $data)
				{
					$i++;
					$arr_result[] = $data->key;

					// Limit to 100 results
					if ($i==100) break;
				}
            }

			return ($arr_result);
        }
        catch (TException $tx)
        {
			$this->Debug("Get Keys error");
			
            // Error occured
            $this->err_str = $tx->why;
            $this->Debug($tx->why." ".$tx->getMessage());
            return array();
        }
	}

	// Get record by key
   	function GetRecordByKey	($table /* ColumnFamily or SuperColumnFamily */, $key,  $start_from="", $end_at="", $reversed=0,
		$super_column="", $bStaleOk=0)
	{
        // Initialize
        $err_str = '';

        // If we failed init, bail
        if ($this->flag_failed_init) return array();

		try
		{
        	return $this->get($table, $key, $super_column, $start_from, $end_at, $reversed, $bStaleOk);
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
	function Debug			($str)
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
	function SetConsistency	($consistency)
	{
		$this->consistency = $consistency;
	}

	// Build cf array
	function array_to_supercolumns_or_columns($array, $timestamp=null) 
	{
        if(empty($timestamp)) $timestamp = time();
 
        foreach($array as $name => $value) {
            $c_or_sc = new cassandra_ColumnOrSuperColumn();
            if(is_array($value)) {
                $c_or_sc->super_column = new cassandra_SuperColumn();
                $c_or_sc->super_column->name = $this->unparse_column_name($name, true);
                $c_or_sc->super_column->columns = $this->array_to_columns($value, $timestamp);
                $c_or_sc->super_column->timestamp = $timestamp;
                $c_or_sc->super_column->clock = new cassandra_Clock( array('timestamp'=>$timestamp) );				
            } 
			else 
			{
                $c_or_sc->column = new cassandra_Column();
                $c_or_sc->column->name = $this->unparse_column_name($name, true);
                $c_or_sc->column->value = $value;
                $c_or_sc->column->timestamp = $timestamp;
                $c_or_sc->column->clock = new cassandra_Clock( array('timestamp'=>$timestamp) );				
            }
            $ret[] = new cassandra_mutation(array('column_or_supercolumn' => $c_or_sc ));
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
   	function get($table, $key, $super_column=NULL, $slice_start="", $slice_finish="", $reversed=0, $bStaleOk=0) 
	{
		try
		{
        // If we failed init, bail
        if ($this->flag_failed_init) return array();

		// If stale data is ok
		if ($bStaleOk)
		{
			// Use ONE consistency
			$consistency = cassandra_ConsistencyLevel::ONE;
		}
		// (Otherwise - stale data not ok)
		else
		{
			// Use default consistency
			$consistency = $this->consistency;
		}
		
		// Prepare query
        $column_parent = new cassandra_ColumnParent();
        $column_parent->column_family = $table;
        $column_parent->super_column = $this->unparse_column_name($super_column, false);
 
        $slice_range = new cassandra_SliceRange();
        $slice_range->start = (empty($slice_start) ? "" : $slice_start);
        $slice_range->finish = (empty($slice_finish) ? "" : $slice_finish);
		$slice_range->count = 100;
		if ($reversed) $slice_range->reversed = true;
        $predicate = new cassandra_SlicePredicate();
        $predicate->slice_range = $slice_range;
 
		if (is_array($key))
		{
			$arr_result = array();
            $resp = $this->client->multiget_slice(/*$this->keyspace,*/ $key, $column_parent, $predicate, $consistency);

			if (!empty($resp))
			foreach ($resp as $key => $data)
			{
				$arr_result[$key] = $this->supercolumns_or_columns_to_array($data);
			}

			return $arr_result;
		}
		else
		{
        	$resp = $this->client->get_slice(/*$this->keyspace,*/ $key, $column_parent, $predicate, $consistency);
			return $this->supercolumns_or_columns_to_array($resp);
		}
		}
		catch (TException $tx)
		{
  		$this->Debug($tx->why." ".$tx->getMessage());
		return array();
		}
    }

	// Convert array to columns
	function array_to_columns($array, $timestamp=null) 
	{
        if(empty($timestamp)) $timestamp = time();
 
        $ret = null;
        foreach($array as $name => $value) {
            $column = new cassandra_Column();
            $column->name = $this->unparse_column_name($name, false);
            $column->value = $value;
            $column->timestamp = $timestamp;
            $column->clock = new cassandra_Clock( array('timestamp'=>$timestamp) );				
 
            $ret[] = $column;
        }
        return $ret;
    }

	// Get error string
	function ErrorStr()
	{
		return $this->err_str;
	}
}
?>
