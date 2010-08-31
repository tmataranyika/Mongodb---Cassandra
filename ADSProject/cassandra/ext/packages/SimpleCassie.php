<?php
/*
 * SimpleTools Framework.
 * Copyright (c) 2010, Marcin Rosinski. (http://www.simpletags.org)
 * All rights reserved.
 * 
 * LICENCE
 *
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 *
 * - 	Redistributions of source code must retain the above copyright notice, 
 * 		this list of conditions and the following disclaimer.
 * 
 * -	Redistributions in binary form must reproduce the above copyright notice, 
 * 		this list of conditions and the following disclaimer in the documentation and/or other 
 * 		materials provided with the distribution.
 * 
 * -	Neither the name of the Simpletags.org nor the names of its contributors may be used to 
 * 		endorse or promote products derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR 
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY 
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL 
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER 
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF 
 * THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @framework		SimpleTools
 * @packages    	SimpleCassie Client & Thrift Libraries Licenced to Apache Software Foundation
 * @description		Apache Cassandra Self Contain Client
 * @copyright  		Copyright (c) 2010 Marcin Rosinski. (http://www.simpletags.org)
 * @license    		http://www.opensource.org/licenses/bsd-license.php - BSD
 * @version    		Ver: 0.8.4 2010-03-28 14:48
 * 
 */
 
	class SimpleCassieUuid
	{
		const interval 			= 0x01b21dd213814000;
		const clearVar 			= 63;  // 00111111  Clears all relevant bits of variant byte with AND
		const varRFC   			= 128; // 10000000  The RFC 4122 variant (this variant)
		const clearVer 			= 15;  // 00001111  Clears all bits of version byte with AND 
		const version1 			= 16;  // 00010000 
		
		public function __construct($uuid = null)
		{
			if($uuid === null)
				$this->uuid = $this->__uuid();
			else
				$this->uuid = $uuid;
			
			$this->uuid_string = $this->__toString();
		}
		
		private function __randomUuidBytes($bytes) 
		{ 
		  $rand = ""; 
		  for ($a = 0; $a < $bytes; $a++) { 
		   $rand .= chr(mt_rand(0, 255)); 
		  }  
		  return $rand; 
		 }
		
		public function __toString()
		{
			$uuid = $this->uuid;
			return   
		   	bin2hex(substr($uuid,0,4))."-". 
		   	bin2hex(substr($uuid,4,2))."-". 
		   	bin2hex(substr($uuid,6,2))."-". 
		   	bin2hex(substr($uuid,8,2))."-". 
		   	bin2hex(substr($uuid,10,6)); 
		}
		
		private function __uuid()
		{
			/* 
			 * Based on Zend_Uuid - Christoph Kempen & Danny Verkade script
			 * Generates a Version 1 UUID.  
		  These are derived from the time at which they were generated. */ 
		  // Get time since Gregorian calendar reform in 100ns intervals 
		  // This is exceedingly difficult because of PHP's (and pack()'s)  
		  //  integer size limits. 
		  // Note that this will never be more accurate than to the microsecond. 
		  $time = microtime(1) * 10000000 + self::interval; 
		  // Convert to a string representation 
		  $time = sprintf("%F", $time); 
		  preg_match("/^\d+/", $time, $time); //strip decimal point 
		  // And now to a 64-bit binary representation 
		  $time = base_convert($time[0], 10, 16); 
		  $time = pack("H*", str_pad($time, 16, "0", STR_PAD_LEFT)); 
		  // Reorder bytes to their proper locations in the UUID 
		  $uuid  = $time[4].$time[5].$time[6].$time[7].$time[2].$time[3].$time[0].$time[1]; 
		  // Generate a random clock sequence 
		  $uuid .= $this->__randomUuidBytes(2); 
		  // set variant 
		  $uuid[8] = chr(ord($uuid[8]) & self::clearVar | self::varRFC); 
		  // set version 
		  $uuid[6] = chr(ord($uuid[6]) & self::clearVer | self::version1); 
		  // Set the final 'node' parameter, a MAC address 
		  
		    // If no node was provided or if the node was invalid,  
		    //  generate a random MAC address and set the multicast bit 
		   $node = $this->__randomUuidBytes(6); 
		   $node[0] = pack("C", ord($node[0]) | 1); 
		  
		  $uuid .= $node; 
		  return $uuid; 
		}
		
		public static function binUuid($uuid)
		{
			return pack("H*", $uuid);
		}
	}
	
	class SimpleCassie
	{
		private $__connection 	= null;
		private $__client		= null;
		
		private $__keyspace		= null;
		private $__columnFamily = null;
		private $__key			= null;
		private $__column		= null;
		private $__superColumn	= null;
		
		private $__connected	= false;
		
		public function uuid($uuid=null)
		{
			return new SimpleCassieUuid($uuid);
		}
		
		public function time()
		{
			$time = explode(' ',microtime());
			$mili = round($time[0]*1000); 
			$secs = $time[1]*1000;
		
			return $secs+$mili;
		}
		
		public function isConnected()
		{
			return (boolean) $this->__connected;
		}
		
		public function __construct($host,$port=9160)
		{
			try
			{
				$socket 	= new TSocket($host,$port);
				$connection = new TBufferedTransport($socket, 1024, 1024);
				$this->__connectiton = $connection;
				$this->__connectiton->open();
				$this->__connected = $this->__connectiton->isOpen();
			}
			catch(Exception $e)
			{
				$this->__connected = false;
			}
			
			$this->__client = new CassandraClient(new TBinaryProtocol($this->__connectiton));
		}
		
		public function __destruct()
		{
			if($this->__connected)
				$this->__connectiton->close();
		}
		
		public function &getClient()
		{
			if(!$this->__connected) return false;
			return $this->__client;
		}
		
		public function delete($consistencyLevel=cassandra_ConsistencyLevel::ALL)
		{
			if(!$this->__connected) return -1;
			
			if(!is_array($this->__key) && !is_array($this->__column))
				$this->__client->remove($this->__keyspace,$this->__key,$this->__getColumn(),$this->time(),$consistencyLevel);
			else
				return false;
		}
		
		public function count($consistencyLevel=cassandra_ConsistencyLevel::ONE)
		{
			if(!$this->__connected) return false;
			
			try
			{
				return $this->__client->get_count($this->__keyspace, $this->__key, $this->__getColumnParent(), $consistencyLevel);
			}
			catch(Exception $e)
			{
				return false;
			}
		}
		
		public function get($consistencyLevel=cassandra_ConsistencyLevel::ONE)
		{
			if(!$this->__connected) return null;
			
			if(!is_array($this->__key))
			{
				if(!is_array($this->__column))
				{
					try
					{
						return $this->__client->get($this->__keyspace,$this->__key,$this->__getColumn(),$consistencyLevel);
					}
					catch(Exception $e)
					{
						if($e instanceof cassandra_NotFoundException) return null;
					};
				}
				else
				{
					return $this->__client->get_slice($this->__keyspace,$this->__key,$this->__getColumnParent(),
						new cassandra_SlicePredicate(array('column_names'=>$this->__column)), 
					$consistencyLevel);
				}
			}
			else
			{
				if(!is_array($this->__column))
					return $this->__client->multiget($this->__keyspace,$this->__key,$this->__getColumn(),$consistencyLevel);
				else
				{
					return $this->__client->multiget_slice($this->__keyspace,$this->__key,$this->__getColumnParent(),
						new cassandra_SlicePredicate(array('column_names'=>$this->__column)), 
					$consistencyLevel);
				}
			}
		}
		
		public function slice($count=100,$reversed=false,$consistencyLevel=cassandra_ConsistencyLevel::ONE)
		{
			if(!$this->__connected) return null;
			
			if(!is_array($this->__column))
			{
				$start 	= '';
				$finish	= '';
			}
			else
			{
				$start 	= $this->__column[0];
				$finish	= $this->__column[1];
			}
			
			$slicePredicate = new cassandra_SlicePredicate(array('slice_range'=>new cassandra_SliceRange(
						array(
							'start' 	=> $start,
							'finish'	=> $finish,
							'reversed' 	=> $reversed,
							'count'		=> $count
						)
			)));
					
			if(!is_array($this->__key))
				return $this->__client->get_slice($this->__keyspace,$this->__key,$this->__getColumnParent(),$slicePredicate,$consistencyLevel);
			else
				return $this->__client->multiget_slice($this->__keyspace,$this->__key,$this->__getColumnParent(),$slicePredicate,$consistencyLevel);
		}
		
		public function value($consistencyLevel=cassandra_ConsistencyLevel::ONE)
		{
			if(!$this->__connected) return null;
			$res = $this->get($consistencyLevel);
			
			if(!is_array($res))
				return isset($res->column->value) ? $res->column->value : null;
			else
			{
				$cols = array();
				foreach($res as $r)
				{
					$cols[$r->column->name] = $r->column->value;
				}
				
				return $cols;
			}
		}
		
		public function timestamp($consistencyLevel=cassandra_ConsistencyLevel::ONE)
		{
			if(!$this->__connected) return null;
			$res = $this->get($consistencyLevel);
			
			if(!is_array($res))
				return isset($res->column->timestamp) ? $res->column->timestamp : null;
			else
			{
				$cols = array();
				foreach($res as $key => $val)
				{
					if(!is_array($r))
						$cols[$r->column->name] = $r->column->timestamp;
					//else
						//$cols[$r->column->name] = $r->column->timestamp;
				}
				
				return $cols;
			}
		}
		
		public function set($value,$consistencyLevel=cassandra_ConsistencyLevel::ALL)
		{
			if(!$this->__connected) return false;
			$this->__client->insert($this->__keyspace,$this->__key,$this->__getColumn(),$value,$this->time(),$consistencyLevel);
		}
		
		public function increment($step=1,$consistencyLevel=cassandra_ConsistencyLevel::ONE)
		{
			if(!$this->__connected) return false;
			
			$val = (integer) $this->value($consistencyLevel);
			$this->set($val+$step,$consistencyLevel);
			
			return $val+$step;
		}
		
		public function decrement($step=1,$consistencyLevel=cassandra_ConsistencyLevel::ONE)
		{
			if(!$this->__connected) return false;
			
			$val = (integer) $this->value($consistencyLevel);
			$this->set($val-$step,$consistencyLevel);
			
			return $val-$step;
		}
		
		private function __getColumnParent()
		{
			return new cassandra_ColumnParent(array(
				'column_family' => $this->__columnFamily,
				'super_column' => $this->__superColumn
			));
		}
		
		private function __getColumn()
		{
			return new cassandra_ColumnPath(array(
				'column_family' => $this->__columnFamily,
				'column' => $this->__column,
				'super_column' => $this->__superColumn
			));
		}
	
		public function &keyspace($keyspace)
		{
			$this->__keyspace = $keyspace;
			return $this;
		}
		
		public function &cf($cf)
		{
			$this->__columnFamily = $cf;
			return $this;
		}
		
		public function &key($key)
		{
			$numargs = func_num_args();
			if($numargs > 1)
				$this->__key = func_get_args();
			else
				$this->__key = $key;
			return $this;
		}
		
		public function &column($column)
		{
			$numargs = func_num_args();
			if($numargs > 1)
				$this->__column = $this->__parseColArgs(func_get_args());
			else
				$this->__column = ($column instanceof SimpleCassieUuid) ? $column->uuid : $column;
				
			return $this;
		}
		
		private function __parseColArgs($args)
		{
			$newargs = array();
			
			foreach($args as $a)
			{
				$newargs[] = ($a instanceof SimpleCassieUuid) ? $a->uuid : $a;
			}
			
			return $newargs;
		}
		
		public function &supercolumn($supercolumn)
		{
			$numargs = func_num_args();
			if($numargs > 1)
				$this->__superColumn = $this->__parseColArgs(func_get_args());
			else
				$this->__superColumn = ($supercolumn instanceof SimpleCassieUuid) ? $supercolumn->uuid : $supercolumn;
				
			return $this;
		}
		
		public function connect(){}
	}

	
/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package thrift
 */
	
/**
 * NOTE(mcslee): This currently contains a ton of duplicated code from TBase
 * because we need to save CPU cycles and this is not yet in an extension.
 * Ideally we'd multiply-inherit TException from both Exception and Base, but
 * that's not possible in PHP and there are no modules either, so for now we
 * apologetically take a trip to HackTown.
 *
 * Can be called with standard Exception constructor (message, code) or with
 * Thrift Base object constructor (spec, vals).
 *
 * @param mixed $p1 Message (string) or type-spec (array)
 * @param mixed $p2 Code (integer) or values (array)
 */
class TException extends Exception {
  function __construct($p1=null, $p2=0) {
    if (is_array($p1) && is_array($p2)) {
      $spec = $p1;
      $vals = $p2;
      foreach ($spec as $fid => $fspec) {
        $var = $fspec['var'];
        if (isset($vals[$var])) {
          $this->$var = $vals[$var];
        }
      }
    } else {
      parent::__construct($p1, $p2);
    }
  }

  static $tmethod = array(TType::BOOL   => 'Bool',
                          TType::BYTE   => 'Byte',
                          TType::I16    => 'I16',
                          TType::I32    => 'I32',
                          TType::I64    => 'I64',
                          TType::DOUBLE => 'Double',
                          TType::STRING => 'String');

  private function _readMap(&$var, $spec, $input) {
    $xfer = 0;
    $ktype = $spec['ktype'];
    $vtype = $spec['vtype'];
    $kread = $vread = null;
    if (isset(TBase::$tmethod[$ktype])) {
      $kread = 'read'.TBase::$tmethod[$ktype];
    } else {
      $kspec = $spec['key'];
    }
    if (isset(TBase::$tmethod[$vtype])) {
      $vread = 'read'.TBase::$tmethod[$vtype];
    } else {
      $vspec = $spec['val'];
    }
    $var = array();
    $_ktype = $_vtype = $size = 0;
    $xfer += $input->readMapBegin($_ktype, $_vtype, $size);
    for ($i = 0; $i < $size; ++$i) {
      $key = $val = null;
      if ($kread !== null) {
        $xfer += $input->$kread($key);
      } else {
        switch ($ktype) {
        case TType::STRUCT:
          $class = $kspec['class'];
          $key = new $class();
          $xfer += $key->read($input);
          break;
        case TType::MAP:
          $xfer += $this->_readMap($key, $kspec, $input);
          break;
        case TType::LST:
          $xfer += $this->_readList($key, $kspec, $input, false);
          break;
        case TType::SET:
          $xfer += $this->_readList($key, $kspec, $input, true);
          break;
        }
      }
      if ($vread !== null) {
        $xfer += $input->$vread($val);
      } else {
        switch ($vtype) {
        case TType::STRUCT:
          $class = $vspec['class'];
          $val = new $class();
          $xfer += $val->read($input);
          break;
        case TType::MAP:
          $xfer += $this->_readMap($val, $vspec, $input);
          break;
        case TType::LST:
          $xfer += $this->_readList($val, $vspec, $input, false);
          break;
        case TType::SET:
          $xfer += $this->_readList($val, $vspec, $input, true);
          break;
        }
      }
      $var[$key] = $val;
    }
    $xfer += $input->readMapEnd();
    return $xfer;
  }

  private function _readList(&$var, $spec, $input, $set=false) {
    $xfer = 0;
    $etype = $spec['etype'];
    $eread = $vread = null;
    if (isset(TBase::$tmethod[$etype])) {
      $eread = 'read'.TBase::$tmethod[$etype];
    } else {
      $espec = $spec['elem'];
    }
    $var = array();
    $_etype = $size = 0;
    if ($set) {
      $xfer += $input->readSetBegin($_etype, $size);
    } else {
      $xfer += $input->readListBegin($_etype, $size);
    }
    for ($i = 0; $i < $size; ++$i) {
      $elem = null;
      if ($eread !== null) {
        $xfer += $input->$eread($elem);
      } else {
        $espec = $spec['elem'];
        switch ($etype) {
        case TType::STRUCT:
          $class = $espec['class'];
          $elem = new $class();
          $xfer += $elem->read($input);
          break;
        case TType::MAP:
          $xfer += $this->_readMap($elem, $espec, $input);
          break;
        case TType::LST:
          $xfer += $this->_readList($elem, $espec, $input, false);
          break;
        case TType::SET:
          $xfer += $this->_readList($elem, $espec, $input, true);
          break;
        }
      }
      if ($set) {
        $var[$elem] = true;
      } else {
        $var []= $elem;
      }
    }
    if ($set) {
      $xfer += $input->readSetEnd();
    } else {
      $xfer += $input->readListEnd();
    }
    return $xfer;
  }

  protected function _read($class, $spec, $input) {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true) {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      if (isset($spec[$fid])) {
        $fspec = $spec[$fid];
        $var = $fspec['var'];
        if ($ftype == $fspec['type']) {
          $xfer = 0;
          if (isset(TBase::$tmethod[$ftype])) {
            $func = 'read'.TBase::$tmethod[$ftype];
            $xfer += $input->$func($this->$var);
          } else {
            switch ($ftype) {
            case TType::STRUCT:
              $class = $fspec['class'];
              $this->$var = new $class();
              $xfer += $this->$var->read($input);
              break;
            case TType::MAP:
              $xfer += $this->_readMap($this->$var, $fspec, $input);
              break;
            case TType::LST:
              $xfer += $this->_readList($this->$var, $fspec, $input, false);
              break;
            case TType::SET:
              $xfer += $this->_readList($this->$var, $fspec, $input, true);
              break;
            }
          }
        } else {
          $xfer += $input->skip($ftype);
        }
      } else {
        $xfer += $input->skip($ftype);
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  private function _writeMap($var, $spec, $output) {
    $xfer = 0;
    $ktype = $spec['ktype'];
    $vtype = $spec['vtype'];
    $kwrite = $vwrite = null;
    if (isset(TBase::$tmethod[$ktype])) {
      $kwrite = 'write'.TBase::$tmethod[$ktype];
    } else {
      $kspec = $spec['key'];
    }
    if (isset(TBase::$tmethod[$vtype])) {
      $vwrite = 'write'.TBase::$tmethod[$vtype];
    } else {
      $vspec = $spec['val'];
    }
    $xfer += $output->writeMapBegin($ktype, $vtype, count($var));
    foreach ($var as $key => $val) {
      if (isset($kwrite)) {
        $xfer += $output->$kwrite($key);
      } else {
        switch ($ktype) {
        case TType::STRUCT:
          $xfer += $key->write($output);
          break;
        case TType::MAP:
          $xfer += $this->_writeMap($key, $kspec, $output);
          break;
        case TType::LST:
          $xfer += $this->_writeList($key, $kspec, $output, false);
          break;
        case TType::SET:
          $xfer += $this->_writeList($key, $kspec, $output, true);
          break;
        }
      }
      if (isset($vwrite)) {
        $xfer += $output->$vwrite($val);
      } else {
        switch ($vtype) {
        case TType::STRUCT:
          $xfer += $val->write($output);
          break;
        case TType::MAP:
          $xfer += $this->_writeMap($val, $vspec, $output);
          break;
        case TType::LST:
          $xfer += $this->_writeList($val, $vspec, $output, false);
          break;
        case TType::SET:
          $xfer += $this->_writeList($val, $vspec, $output, true);
          break;
        }
      }
    }
    $xfer += $output->writeMapEnd();
    return $xfer;
  }

  private function _writeList($var, $spec, $output, $set=false) {
    $xfer = 0;
    $etype = $spec['etype'];
    $ewrite = null;
    if (isset(TBase::$tmethod[$etype])) {
      $ewrite = 'write'.TBase::$tmethod[$etype];
    } else {
      $espec = $spec['elem'];
    }
    if ($set) {
      $xfer += $output->writeSetBegin($etype, count($var));
    } else {
      $xfer += $output->writeListBegin($etype, count($var));
    }
    foreach ($var as $key => $val) {
      $elem = $set ? $key : $val;
      if (isset($ewrite)) {
        $xfer += $output->$ewrite($elem);
      } else {
        switch ($etype) {
        case TType::STRUCT:
          $xfer += $elem->write($output);
          break;
        case TType::MAP:
          $xfer += $this->_writeMap($elem, $espec, $output);
          break;
        case TType::LST:
          $xfer += $this->_writeList($elem, $espec, $output, false);
          break;
        case TType::SET:
          $xfer += $this->_writeList($elem, $espec, $output, true);
          break;
        }
      }
    }
    if ($set) {
      $xfer += $output->writeSetEnd();
    } else {
      $xfer += $output->writeListEnd();
    }
    return $xfer;
  }

  protected function _write($class, $spec, $output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin($class);
    foreach ($spec as $fid => $fspec) {
      $var = $fspec['var'];
      if ($this->$var !== null) {
        $ftype = $fspec['type'];
        $xfer += $output->writeFieldBegin($var, $ftype, $fid);
        if (isset(TBase::$tmethod[$ftype])) {
          $func = 'write'.TBase::$tmethod[$ftype];
          $xfer += $output->$func($this->$var);
        } else {
          switch ($ftype) {
          case TType::STRUCT:
            $xfer += $this->$var->write($output);
            break;
          case TType::MAP:
            $xfer += $this->_writeMap($this->$var, $fspec, $output);
            break;
          case TType::LST:
            $xfer += $this->_writeList($this->$var, $fspec, $output, false);
            break;
          case TType::SET:
            $xfer += $this->_writeList($this->$var, $fspec, $output, true);
            break;
          }
        }
        $xfer += $output->writeFieldEnd();
      }
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}
	 
/**
 * Transport exceptions
 */
class TTransportException extends TException {

  const UNKNOWN = 0;
  const NOT_OPEN = 1;
  const ALREADY_OPEN = 2;
  const TIMED_OUT = 3;
  const END_OF_FILE = 4;

  function __construct($message=null, $code=0) {
    parent::__construct($message, $code);
  }
}

/**
 * Base interface for a transport agent.
 *
 * @package thrift.transport
 */
abstract class TTransport {

  /**
   * Whether this transport is open.
   *
   * @return boolean true if open
   */
  public abstract function isOpen();

  /**
   * Open the transport for reading/writing
   *
   * @throws TTransportException if cannot open
   */
  public abstract function open();

  /**
   * Close the transport.
   */
  public abstract function close();

  /**
   * Read some data into the array.
   *
   * @param int    $len How much to read
   * @return string The data that has been read
   * @throws TTransportException if cannot read any more data
   */
  public abstract function read($len);

  /**
   * Guarantees that the full amount of data is read.
   *
   * @return string The data, of exact length
   * @throws TTransportException if cannot read data
   */
  public function readAll($len) {
    // return $this->read($len);

    $data = '';
    $got = 0;
    while (($got = strlen($data)) < $len) {
      $data .= $this->read($len - $got);
    }
    return $data;
  }

  /**
   * Writes the given data out.
   *
   * @param string $buf  The data to write
   * @throws TTransportException if writing fails
   */
  public abstract function write($buf);

  /**
   * Flushes any pending data out of a buffer
   *
   * @throws TTransportException if a writing error occurs
   */
  public function flush() {}
}





/**
 * Framed transport. Writes and reads data in chunks that are stamped with
 * their length.
 *
 * @package thrift.transport
 */
class TFramedTransport extends TTransport {

  /**
   * Underlying transport object.
   *
   * @var TTransport
   */
  private $transport_;

  /**
   * Buffer for read data.
   *
   * @var string
   */
  private $rBuf_;

  /**
   * Buffer for queued output data
   *
   * @var string
   */
  private $wBuf_;

  /**
   * Whether to frame reads
   *
   * @var bool
   */
  private $read_;

  /**
   * Whether to frame writes
   *
   * @var bool
   */
  private $write_;

  /**
   * Constructor.
   *
   * @param TTransport $transport Underlying transport
   */
  public function __construct($transport=null, $read=true, $write=true) {
    $this->transport_ = $transport;
    $this->read_ = $read;
    $this->write_ = $write;
  }

  public function isOpen() {
    return $this->transport_->isOpen();
  }

  public function open() {
    $this->transport_->open();
  }

  public function close() {
    $this->transport_->close();
  }

  /**
   * Reads from the buffer. When more data is required reads another entire
   * chunk and serves future reads out of that.
   *
   * @param int $len How much data
   */
  public function read($len) {
    if (!$this->read_) {
      return $this->transport_->read($len);
    }

    if (strlen($this->rBuf_) === 0) {
      $this->readFrame();
    }

    // Just return full buff
    if ($len >= strlen($this->rBuf_)) {
      $out = $this->rBuf_;
      $this->rBuf_ = null;
      return $out;
    }

    // Return substr
    $out = substr($this->rBuf_, 0, $len);
    $this->rBuf_ = substr($this->rBuf_, $len);
    return $out;
  }

  /**
   * Put previously read data back into the buffer
   *
   * @param string $data data to return
   */
  public function putBack($data) {
    if (strlen($this->rBuf_) === 0) {
      $this->rBuf_ = $data;
    } else {
      $this->rBuf_ = ($data . $this->rBuf_);
    }
  }

  /**
   * Reads a chunk of data into the internal read buffer.
   */
  private function readFrame() {
    $buf = $this->transport_->readAll(4);
    $val = unpack('N', $buf);
    $sz = $val[1];

    $this->rBuf_ = $this->transport_->readAll($sz);
  }

  /**
   * Writes some data to the pending output buffer.
   *
   * @param string $buf The data
   * @param int    $len Limit of bytes to write
   */
  public function write($buf, $len=null) {
    if (!$this->write_) {
      return $this->transport_->write($buf, $len);
    }

    if ($len !== null && $len < strlen($buf)) {
      $buf = substr($buf, 0, $len);
    }
    $this->wBuf_ .= $buf;
  }

  /**
   * Writes the output buffer to the stream in the format of a 4-byte length
   * followed by the actual data.
   */
  public function flush() {
    if (!$this->write_) {
      return $this->transport_->flush();
    }

    $out = pack('N', strlen($this->wBuf_));
    $out .= $this->wBuf_;

    // Note that we clear the internal wBuf_ prior to the underlying write
    // to ensure we're in a sane state (i.e. internal buffer cleaned)
    // if the underlying write throws up an exception
    $this->wBuf_ = '';
    $this->transport_->write($out);
    $this->transport_->flush();
  }

}


/**
 * Transport that only accepts writes and ignores them.
 * This is useful for measuring the serialized size of structures.
 *
 * @package thrift.transport
 */
class TNullTransport extends TTransport {

  public function isOpen() {
    return true;
  }

  public function open() {}

  public function close() {}

  public function read($len) {
    throw new TTransportException("Can't read from TNullTransport.");
  }

  public function write($buf) {}

}


/*
 * TTRANSPOREDBUFFERED.php
 */


/**
 * Buffered transport. Stores data to an internal buffer that it doesn't
 * actually write out until flush is called. For reading, we do a greedy
 * read and then serve data out of the internal buffer.
 *
 * @package thrift.transport
 */
class TBufferedTransport extends TTransport {

  /**
   * Constructor. Creates a buffered transport around an underlying transport
   */
  public function __construct($transport=null, $rBufSize=512, $wBufSize=512) {
    $this->transport_ = $transport;
    $this->rBufSize_ = $rBufSize;
    $this->wBufSize_ = $wBufSize;
  }

  /**
   * The underlying transport
   *
   * @var TTransport
   */
  protected $transport_ = null;

  /**
   * The receive buffer size
   *
   * @var int
   */
  protected $rBufSize_ = 512;

  /**
   * The write buffer size
   *
   * @var int
   */
  protected $wBufSize_ = 512;

  /**
   * The write buffer.
   *
   * @var string
   */
  protected $wBuf_ = '';

  /**
   * The read buffer.
   *
   * @var string
   */
  protected $rBuf_ = '';

  public function isOpen() {
    return $this->transport_->isOpen();
  }

  public function open() {
    $this->transport_->open();
  }

  public function close() {
    $this->transport_->close();
  }

  public function putBack($data) {
    if (strlen($this->rBuf_) === 0) {
      $this->rBuf_ = $data;
    } else {
      $this->rBuf_ = ($data . $this->rBuf_);
    }
  }

  /**
   * The reason that we customize readAll here is that the majority of PHP
   * streams are already internally buffered by PHP. The socket stream, for
   * example, buffers internally and blocks if you call read with $len greater
   * than the amount of data available, unlike recv() in C.
   *
   * Therefore, use the readAll method of the wrapped transport inside
   * the buffered readAll.
   */
  public function readAll($len) {
    $have = strlen($this->rBuf_);
    if ($have == 0) {
      $data = $this->transport_->readAll($len);
    } else if ($have < $len) {
      $data = $this->rBuf_;
      $this->rBuf_ = '';
      $data .= $this->transport_->readAll($len - $have);
    } else if ($have == $len) {
      $data = $this->rBuf_;
      $this->rBuf_ = '';
    } else if ($have > $len) {
      $data = substr($this->rBuf_, 0, $len);
      $this->rBuf_ = substr($this->rBuf_, $len);
    }
    return $data;
  }

  public function read($len) {
    if (strlen($this->rBuf_) === 0) {
      $this->rBuf_ = $this->transport_->read($this->rBufSize_);
    }

    if (strlen($this->rBuf_) <= $len) {
      $ret = $this->rBuf_;
      $this->rBuf_ = '';
      return $ret;
    }

    $ret = substr($this->rBuf_, 0, $len);
    $this->rBuf_ = substr($this->rBuf_, $len);
    return $ret;
  }

  public function write($buf) {
    $this->wBuf_ .= $buf;
    if (strlen($this->wBuf_) >= $this->wBufSize_) {
      $out = $this->wBuf_;

      // Note that we clear the internal wBuf_ prior to the underlying write
      // to ensure we're in a sane state (i.e. internal buffer cleaned)
      // if the underlying write throws up an exception
      $this->wBuf_ = '';
      $this->transport_->write($out);
    }
  }

  public function flush() {
    if (strlen($this->wBuf_) > 0) {
      $this->transport_->write($this->wBuf_);
      $this->wBuf_ = '';
    }
    $this->transport_->flush();
  }

}





/*
 * TPROTOCOL.php
 */

/**
 * Protocol module. Contains all the types and definitions needed to implement
 * a protocol encoder/decoder.
 *
 * @package thrift.protocol
 */

/**
 * Protocol exceptions
 */
class TProtocolException extends TException {
  const UNKNOWN = 0;
  const INVALID_DATA = 1;
  const NEGATIVE_SIZE = 2;
  const SIZE_LIMIT = 3;
  const BAD_VERSION = 4;

  function __construct($message=null, $code=0) {
    parent::__construct($message, $code);
  }
}

/**
 * Protocol base class module.
 */
abstract class TProtocol {
  // The below may seem silly, but it is to get around the problem that the
  // "instanceof" operator can only take in a T_VARIABLE and not a T_STRING
  // or T_CONSTANT_ENCAPSED_STRING. Using "is_a()" instead of "instanceof" is
  // a workaround but is deprecated in PHP5. This is used in the generated
  // deserialization code.
  static $TBINARYPROTOCOLACCELERATED = 'TBinaryProtocolAccelerated';

  /**
   * Underlying transport
   *
   * @var TTransport
   */
  protected $trans_;

  /**
   * Constructor
   */
  protected function __construct($trans) {
    $this->trans_ = $trans;
  }

  /**
   * Accessor for transport
   *
   * @return TTransport
   */
  public function getTransport() {
    return $this->trans_;
  }

  /**
   * Writes the message header
   *
   * @param string $name Function name
   * @param int $type message type TMessageType::CALL or TMessageType::REPLY
   * @param int $seqid The sequence id of this message
   */
  public abstract function writeMessageBegin($name, $type, $seqid);

  /**
   * Close the message
   */
  public abstract function writeMessageEnd();

  /**
   * Writes a struct header.
   *
   * @param string     $name Struct name
   * @throws TException on write error
   * @return int How many bytes written
   */
  public abstract function writeStructBegin($name);

  /**
   * Close a struct.
   *
   * @throws TException on write error
   * @return int How many bytes written
   */
  public abstract function writeStructEnd();

  /*
   * Starts a field.
   *
   * @param string     $name Field name
   * @param int        $type Field type
   * @param int        $fid  Field id
   * @throws TException on write error
   * @return int How many bytes written
   */
  public abstract function writeFieldBegin($fieldName, $fieldType, $fieldId);

  public abstract function writeFieldEnd();

  public abstract function writeFieldStop();

  public abstract function writeMapBegin($keyType, $valType, $size);

  public abstract function writeMapEnd();

  public abstract function writeListBegin($elemType, $size);

  public abstract function writeListEnd();

  public abstract function writeSetBegin($elemType, $size);

  public abstract function writeSetEnd();

  public abstract function writeBool($bool);

  public abstract function writeByte($byte);

  public abstract function writeI16($i16);

  public abstract function writeI32($i32);

  public abstract function writeI64($i64);

  public abstract function writeDouble($dub);

  public abstract function writeString($str);

  /**
   * Reads the message header
   *
   * @param string $name Function name
   * @param int $type message type TMessageType::CALL or TMessageType::REPLY
   * @parem int $seqid The sequence id of this message
   */
  public abstract function readMessageBegin(&$name, &$type, &$seqid);

  /**
   * Read the close of message
   */
  public abstract function readMessageEnd();

  public abstract function readStructBegin(&$name);

  public abstract function readStructEnd();

  public abstract function readFieldBegin(&$name, &$fieldType, &$fieldId);

  public abstract function readFieldEnd();

  public abstract function readMapBegin(&$keyType, &$valType, &$size);

  public abstract function readMapEnd();

  public abstract function readListBegin(&$elemType, &$size);

  public abstract function readListEnd();

  public abstract function readSetBegin(&$elemType, &$size);

  public abstract function readSetEnd();

  public abstract function readBool(&$bool);

  public abstract function readByte(&$byte);

  public abstract function readI16(&$i16);

  public abstract function readI32(&$i32);

  public abstract function readI64(&$i64);

  public abstract function readDouble(&$dub);

  public abstract function readString(&$str);

  /**
   * The skip function is a utility to parse over unrecognized date without
   * causing corruption.
   *
   * @param TType $type What type is it
   */
  public function skip($type) {
    switch ($type) {
    case TType::BOOL:
      return $this->readBool($bool);
    case TType::BYTE:
      return $this->readByte($byte);
    case TType::I16:
      return $this->readI16($i16);
    case TType::I32:
      return $this->readI32($i32);
    case TType::I64:
      return $this->readI64($i64);
    case TType::DOUBLE:
      return $this->readDouble($dub);
    case TType::STRING:
      return $this->readString($str);
    case TType::STRUCT:
      {
        $result = $this->readStructBegin($name);
        while (true) {
          $result += $this->readFieldBegin($name, $ftype, $fid);
          if ($ftype == TType::STOP) {
            break;
          }
          $result += $this->skip($ftype);
          $result += $this->readFieldEnd();
        }
        $result += $this->readStructEnd();
        return $result;
      }
    case TType::MAP:
      {
        $result = $this->readMapBegin($keyType, $valType, $size);
        for ($i = 0; $i < $size; $i++) {
          $result += $this->skip($keyType);
          $result += $this->skip($valType);
        }
        $result += $this->readMapEnd();
        return $result;
      }
    case TType::SET:
      {
        $result = $this->readSetBegin($elemType, $size);
        for ($i = 0; $i < $size; $i++) {
          $result += $this->skip($elemType);
        }
        $result += $this->readSetEnd();
        return $result;
      }
    case TType::LST:
      {
        $result = $this->readListBegin($elemType, $size);
        for ($i = 0; $i < $size; $i++) {
          $result += $this->skip($elemType);
        }
        $result += $this->readListEnd();
        return $result;
      }
    default:
      return 0;
    }
  }

  /**
   * Utility for skipping binary data
   *
   * @param TTransport $itrans TTransport object
   * @param int        $type   Field type
   */
  public static function skipBinary($itrans, $type) {
    switch ($type) {
    case TType::BOOL:
      return $itrans->readAll(1);
    case TType::BYTE:
      return $itrans->readAll(1);
    case TType::I16:
      return $itrans->readAll(2);
    case TType::I32:
      return $itrans->readAll(4);
    case TType::I64:
      return $itrans->readAll(8);
    case TType::DOUBLE:
      return $itrans->readAll(8);
    case TType::STRING:
      $len = unpack('N', $itrans->readAll(4));
      $len = $len[1];
      if ($len > 0x7fffffff) {
        $len = 0 - (($len - 1) ^ 0xffffffff);
      }
      return 4 + $itrans->readAll($len);
    case TType::STRUCT:
      {
        $result = 0;
        while (true) {
          $ftype = 0;
          $fid = 0;
          $data = $itrans->readAll(1);
          $arr = unpack('c', $data);
          $ftype = $arr[1];
          if ($ftype == TType::STOP) {
            break;
          }
          // I16 field id
          $result += $itrans->readAll(2);
          $result += self::skipBinary($itrans, $ftype);
        }
        return $result;
      }
    case TType::MAP:
      {
        // Ktype
        $data = $itrans->readAll(1);
        $arr = unpack('c', $data);
        $ktype = $arr[1];
        // Vtype
        $data = $itrans->readAll(1);
        $arr = unpack('c', $data);
        $vtype = $arr[1];
        // Size
        $data = $itrans->readAll(4);
        $arr = unpack('N', $data);
        $size = $arr[1];
        if ($size > 0x7fffffff) {
          $size = 0 - (($size - 1) ^ 0xffffffff);
        }
        $result = 6;
        for ($i = 0; $i < $size; $i++) {
          $result += self::skipBinary($itrans, $ktype);
          $result += self::skipBinary($itrans, $vtype);
        }
        return $result;
      }
    case TType::SET:
    case TType::LST:
      {
        // Vtype
        $data = $itrans->readAll(1);
        $arr = unpack('c', $data);
        $vtype = $arr[1];
        // Size
        $data = $itrans->readAll(4);
        $arr = unpack('N', $data);
        $size = $arr[1];
        if ($size > 0x7fffffff) {
          $size = 0 - (($size - 1) ^ 0xffffffff);
        }
        $result = 5;
        for ($i = 0; $i < $size; $i++) {
          $result += self::skipBinary($itrans, $vtype);
        }
        return $result;
      }
    default:
      return 0;
    }
  }
}

/**
 * Protocol factory creates protocol objects from transports
 */
interface TProtocolFactory {
  /**
   * Build a protocol from the base transport
   *
   * @return TProtcol protocol
   */
  public function getProtocol($trans);
}






/*
 * TBINARYPROTOCOL.php
 */

/**
 * Binary implementation of the Thrift protocol.
 *
 */
class TBinaryProtocol extends TProtocol {

  const VERSION_MASK = 0xffff0000;
  const VERSION_1 = 0x80010000;

  protected $strictRead_ = false;
  protected $strictWrite_ = true;

  public function __construct($trans, $strictRead=false, $strictWrite=true) {
    parent::__construct($trans);
    $this->strictRead_ = $strictRead;
    $this->strictWrite_ = $strictWrite;
  }

  public function writeMessageBegin($name, $type, $seqid) {
    if ($this->strictWrite_) {
      $version = self::VERSION_1 | $type;
      return
        $this->writeI32($version) +
        $this->writeString($name) +
        $this->writeI32($seqid);
    } else {
      return
        $this->writeString($name) +
        $this->writeByte($type) +
        $this->writeI32($seqid);
    }
  }

  public function writeMessageEnd() {
    return 0;
  }

  public function writeStructBegin($name) {
    return 0;
  }

  public function writeStructEnd() {
    return 0;
  }

  public function writeFieldBegin($fieldName, $fieldType, $fieldId) {
    return
      $this->writeByte($fieldType) +
      $this->writeI16($fieldId);
  }

  public function writeFieldEnd() {
    return 0;
  }

  public function writeFieldStop() {
    return
      $this->writeByte(TType::STOP);
  }

  public function writeMapBegin($keyType, $valType, $size) {
    return
      $this->writeByte($keyType) +
      $this->writeByte($valType) +
      $this->writeI32($size);
  }

  public function writeMapEnd() {
    return 0;
  }

  public function writeListBegin($elemType, $size) {
    return
      $this->writeByte($elemType) +
      $this->writeI32($size);
  }

  public function writeListEnd() {
    return 0;
  }

  public function writeSetBegin($elemType, $size) {
    return
      $this->writeByte($elemType) +
      $this->writeI32($size);
  }

  public function writeSetEnd() {
    return 0;
  }

  public function writeBool($value) {
    $data = pack('c', $value ? 1 : 0);
    $this->trans_->write($data, 1);
    return 1;
  }

  public function writeByte($value) {
    $data = pack('c', $value);
    $this->trans_->write($data, 1);
    return 1;
  }

  public function writeI16($value) {
    $data = pack('n', $value);
    $this->trans_->write($data, 2);
    return 2;
  }

  public function writeI32($value) {
    $data = pack('N', $value);
    $this->trans_->write($data, 4);
    return 4;
  }

  public function writeI64($value) {
    // If we are on a 32bit architecture we have to explicitly deal with
    // 64-bit twos-complement arithmetic since PHP wants to treat all ints
    // as signed and any int over 2^31 - 1 as a float
    if (PHP_INT_SIZE == 4) {
      $neg = $value < 0;

      if ($neg) {
        $value *= -1;
      }

      $hi = (int)($value / 4294967296);
      $lo = (int)$value;

      if ($neg) {
        $hi = ~$hi;
        $lo = ~$lo;
        if (($lo & (int)0xffffffff) == (int)0xffffffff) {
          $lo = 0;
          $hi++;
        } else {
          $lo++;
        }
      }
      $data = pack('N2', $hi, $lo);

    } else {
      $hi = $value >> 32;
      $lo = $value & 0xFFFFFFFF;
      $data = pack('N2', $hi, $lo);
    }

    $this->trans_->write($data, 8);
    return 8;
  }

  public function writeDouble($value) {
    $data = pack('d', $value);
    $this->trans_->write(strrev($data), 8);
    return 8;
  }

  public function writeString($value) {
    $len = strlen($value);
    $result = $this->writeI32($len);
    if ($len) {
      $this->trans_->write($value, $len);
    }
    return $result + $len;
  }

  public function readMessageBegin(&$name, &$type, &$seqid) {
    $result = $this->readI32($sz);
    if ($sz < 0) {
      $version = (int) ($sz & self::VERSION_MASK);
      if ($version != (int) self::VERSION_1) {
        throw new TProtocolException('Bad version identifier: '.$sz, TProtocolException::BAD_VERSION);
      }
      $type = $sz & 0x000000ff;
      $result +=
        $this->readString($name) +
        $this->readI32($seqid);
    } else {
      if ($this->strictRead_) {
        throw new TProtocolException('No version identifier, old protocol client?', TProtocolException::BAD_VERSION);
      } else {
        // Handle pre-versioned input
        $name = $this->trans_->readAll($sz);
        $result +=
          $sz +
          $this->readByte($type) +
          $this->readI32($seqid);
      }
    }
    return $result;
  }

  public function readMessageEnd() {
    return 0;
  }

  public function readStructBegin(&$name) {
    $name = '';
    return 0;
  }

  public function readStructEnd() {
    return 0;
  }

  public function readFieldBegin(&$name, &$fieldType, &$fieldId) {
    $result = $this->readByte($fieldType);
    if ($fieldType == TType::STOP) {
      $fieldId = 0;
      return $result;
    }
    $result += $this->readI16($fieldId);
    return $result;
  }

  public function readFieldEnd() {
    return 0;
  }

  public function readMapBegin(&$keyType, &$valType, &$size) {
    return
      $this->readByte($keyType) +
      $this->readByte($valType) +
      $this->readI32($size);
  }

  public function readMapEnd() {
    return 0;
  }

  public function readListBegin(&$elemType, &$size) {
    return
      $this->readByte($elemType) +
      $this->readI32($size);
  }

  public function readListEnd() {
    return 0;
  }

  public function readSetBegin(&$elemType, &$size) {
    return
      $this->readByte($elemType) +
      $this->readI32($size);
  }

  public function readSetEnd() {
    return 0;
  }

  public function readBool(&$value) {
    $data = $this->trans_->readAll(1);
    $arr = unpack('c', $data);
    $value = $arr[1] == 1;
    return 1;
  }

  public function readByte(&$value) {
    $data = $this->trans_->readAll(1);
    $arr = unpack('c', $data);
    $value = $arr[1];
    return 1;
  }

  public function readI16(&$value) {
    $data = $this->trans_->readAll(2);
    $arr = unpack('n', $data);
    $value = $arr[1];
    if ($value > 0x7fff) {
      $value = 0 - (($value - 1) ^ 0xffff);
    }
    return 2;
  }

  public function readI32(&$value) {
    $data = $this->trans_->readAll(4);
    $arr = unpack('N', $data);
    $value = $arr[1];
    if ($value > 0x7fffffff) {
      $value = 0 - (($value - 1) ^ 0xffffffff);
    }
    return 4;
  }

  public function readI64(&$value) {
    $data = $this->trans_->readAll(8);

    $arr = unpack('N2', $data);

    // If we are on a 32bit architecture we have to explicitly deal with
    // 64-bit twos-complement arithmetic since PHP wants to treat all ints
    // as signed and any int over 2^31 - 1 as a float
    if (PHP_INT_SIZE == 4) {

      $hi = $arr[1];
      $lo = $arr[2];
      $isNeg = $hi  < 0;

      // Check for a negative
      if ($isNeg) {
        $hi = ~$hi & (int)0xffffffff;
        $lo = ~$lo & (int)0xffffffff;

        if ($lo == (int)0xffffffff) {
          $hi++;
          $lo = 0;
        } else {
          $lo++;
        }
      }

      // Force 32bit words in excess of 2G to pe positive - we deal wigh sign
      // explicitly below

      if ($hi & (int)0x80000000) {
        $hi &= (int)0x7fffffff;
        $hi += 0x80000000;
      }

      if ($lo & (int)0x80000000) {
        $lo &= (int)0x7fffffff;
        $lo += 0x80000000;
      }

      $value = $hi * 4294967296 + $lo;

      if ($isNeg) {
        $value = 0 - $value;
      }
    } else {

      // Upcast negatives in LSB bit
      if ($arr[2] & 0x80000000) {
        $arr[2] = $arr[2] & 0xffffffff;
      }

      // Check for a negative
      if ($arr[1] & 0x80000000) {
        $arr[1] = $arr[1] & 0xffffffff;
        $arr[1] = $arr[1] ^ 0xffffffff;
        $arr[2] = $arr[2] ^ 0xffffffff;
        $value = 0 - $arr[1]*4294967296 - $arr[2] - 1;
      } else {
        $value = $arr[1]*4294967296 + $arr[2];
      }
    }

    return 8;
  }

  public function readDouble(&$value) {
    $data = strrev($this->trans_->readAll(8));
    $arr = unpack('d', $data);
    $value = $arr[1];
    return 8;
  }

  public function readString(&$value) {
    $result = $this->readI32($len);
    if ($len) {
      $value = $this->trans_->readAll($len);
    } else {
      $value = '';
    }
    return $result + $len;
  }
}

/**
 * Binary Protocol Factory
 */
class TBinaryProtocolFactory implements TProtocolFactory {
  private $strictRead_ = false;
  private $strictWrite_ = false;

  public function __construct($strictRead=false, $strictWrite=false) {
    $this->strictRead_ = $strictRead;
    $this->strictWrite_ = $strictWrite;
  }

  public function getProtocol($trans) {
    return new TBinaryProtocol($trans, $this->strictRead, $this->strictWrite);
  }
}

/**
 * Accelerated binary protocol: used in conjunction with the thrift_protocol
 * extension for faster deserialization
 */
class TBinaryProtocolAccelerated extends TBinaryProtocol {
  public function __construct($trans, $strictRead=false, $strictWrite=true) {
    // If the transport doesn't implement putBack, wrap it in a
    // TBufferedTransport (which does)
    if (!method_exists($trans, 'putBack')) {
      $trans = new TBufferedTransport($trans);
    }
    parent::__construct($trans, $strictRead, $strictWrite);
  }
  public function isStrictRead() {
    return $this->strictRead_;
  }
  public function isStrictWrite() {
    return $this->strictWrite_;
  }
}





/*
 * TSOCKET.php
 */
/**
 * Sockets implementation of the TTransport interface.
 *
 * @package thrift.transport
 */
class TSocket extends TTransport {

  /**
   * Handle to PHP socket
   *
   * @var resource
   */
  private $handle_ = null;

  /**
   * Remote hostname
   *
   * @var string
   */
  protected $host_ = 'localhost';

  /**
   * Remote port
   *
   * @var int
   */
  protected $port_ = '9090';

  /**
   * Send timeout in milliseconds
   *
   * @var int
   */
  private $sendTimeout_ = 100;

  /**
   * Recv timeout in milliseconds
   *
   * @var int
   */
  private $recvTimeout_ = 750;

  /**
   * Is send timeout set?
   *
   * @var bool
   */
  private $sendTimeoutSet_ = FALSE;

  /**
   * Persistent socket or plain?
   *
   * @var bool
   */
  private $persist_ = FALSE;

  /**
   * Debugging on?
   *
   * @var bool
   */
  protected $debug_ = FALSE;

  /**
   * Debug handler
   *
   * @var mixed
   */
  protected $debugHandler_ = null;

  /**
   * Socket constructor
   *
   * @param string $host         Remote hostname
   * @param int    $port         Remote port
   * @param bool   $persist      Whether to use a persistent socket
   * @param string $debugHandler Function to call for error logging
   */
  public function __construct($host='localhost',
                              $port=9090,
                              $persist=FALSE,
                              $debugHandler=null) {
    $this->host_ = $host;
    $this->port_ = $port;
    $this->persist_ = $persist;
    $this->debugHandler_ = $debugHandler ? $debugHandler : 'error_log';
  }

  /**
   * Sets the send timeout.
   *
   * @param int $timeout  Timeout in milliseconds.
   */
  public function setSendTimeout($timeout) {
    $this->sendTimeout_ = $timeout;
  }

  /**
   * Sets the receive timeout.
   *
   * @param int $timeout  Timeout in milliseconds.
   */
  public function setRecvTimeout($timeout) {
    $this->recvTimeout_ = $timeout;
  }

  /**
   * Sets debugging output on or off
   *
   * @param bool $debug
   */
  public function setDebug($debug) {
    $this->debug_ = $debug;
  }

  /**
   * Get the host that this socket is connected to
   *
   * @return string host
   */
  public function getHost() {
    return $this->host_;
  }

  /**
   * Get the remote port that this socket is connected to
   *
   * @return int port
   */
  public function getPort() {
    return $this->port_;
  }

  /**
   * Tests whether this is open
   *
   * @return bool true if the socket is open
   */
  public function isOpen() {
    return is_resource($this->handle_);
  }

  /**
   * Connects the socket.
   */
  public function open() {

    if ($this->persist_) {
      $this->handle_ = @pfsockopen($this->host_,
                                   $this->port_,
                                   $errno,
                                   $errstr,
                                   $this->sendTimeout_/1000.0);
    } else {
      $this->handle_ = @fsockopen($this->host_,
                                  $this->port_,
                                  $errno,
                                  $errstr,
                                  $this->sendTimeout_/1000.0);
    }

    // Connect failed?
    if ($this->handle_ === FALSE) {
      $error = 'TSocket: Could not connect to '.$this->host_.':'.$this->port_.' ('.$errstr.' ['.$errno.'])';
      if ($this->debug_) {
        call_user_func($this->debugHandler_, $error);
      }
      throw new TException($error);
    }

    stream_set_timeout($this->handle_, 0, $this->sendTimeout_*1000);
    $this->sendTimeoutSet_ = TRUE;
  }

  /**
   * Closes the socket.
   */
  public function close() {
    if (!$this->persist_) {
      @fclose($this->handle_);
      $this->handle_ = null;
    }
  }

  /**
   * Uses stream get contents to do the reading
   *
   * @param int $len How many bytes
   * @return string Binary data
   */
  public function readAll($len) {
    if ($this->sendTimeoutSet_) {
      stream_set_timeout($this->handle_, 0, $this->recvTimeout_*1000);
      $this->sendTimeoutSet_ = FALSE;
    }
    // This call does not obey stream_set_timeout values!
    // $buf = @stream_get_contents($this->handle_, $len);

    $pre = null;
    while (TRUE) {
      $buf = @fread($this->handle_, $len);
      if ($buf === FALSE || $buf === '') {
        $md = stream_get_meta_data($this->handle_);
        if ($md['timed_out']) {
          throw new TException('TSocket: timed out reading '.$len.' bytes from '.
                               $this->host_.':'.$this->port_);
        } else {
          throw new TException('TSocket: Could not read '.$len.' bytes from '.
                               $this->host_.':'.$this->port_);
        }
      } else if (($sz = strlen($buf)) < $len) {
        $md = stream_get_meta_data($this->handle_);
        if ($md['timed_out']) {
          throw new TException('TSocket: timed out reading '.$len.' bytes from '.
                               $this->host_.':'.$this->port_);
        } else {
          $pre .= $buf;
          $len -= $sz;
        }
      } else {
        return $pre.$buf;
      }
    }
  }

  /**
   * Read from the socket
   *
   * @param int $len How many bytes
   * @return string Binary data
   */
  public function read($len) {
    if ($this->sendTimeoutSet_) {
      stream_set_timeout($this->handle_, 0, $this->recvTimeout_*1000);
      $this->sendTimeoutSet_ = FALSE;
    }
    $data = @fread($this->handle_, $len);
    if ($data === FALSE || $data === '') {
      $md = stream_get_meta_data($this->handle_);
      if ($md['timed_out']) {
        throw new TException('TSocket: timed out reading '.$len.' bytes from '.
                             $this->host_.':'.$this->port_);
      } else {
        throw new TException('TSocket: Could not read '.$len.' bytes from '.
                             $this->host_.':'.$this->port_);
      }
    }
    return $data;
  }

  /**
   * Write to the socket.
   *
   * @param string $buf The data to write
   */
  public function write($buf) {
    if (!$this->sendTimeoutSet_) {
      stream_set_timeout($this->handle_, 0, $this->sendTimeout_*1000);
      $this->sendTimeoutSet_ = TRUE;
    }
    while (strlen($buf) > 0) {
      $got = @fwrite($this->handle_, $buf);
      if ($got === 0 || $got === FALSE) {
        $md = stream_get_meta_data($this->handle_);
        if ($md['timed_out']) {
          throw new TException('TSocket: timed out writing '.strlen($buf).' bytes from '.
                               $this->host_.':'.$this->port_);
        } else {
            throw new TException('TSocket: Could not write '.strlen($buf).' bytes '.
                                 $this->host_.':'.$this->port_);
        }
      }
      $buf = substr($buf, $got);
    }
  }

  /**
   * Flush output to the socket.
   */
  public function flush() {
    $ret = fflush($this->handle_);
    if ($ret === FALSE) {
      throw new TException('TSocket: Could not flush: '.
                           $this->host_.':'.$this->port_);
    }
  }
}





/**
 * Thrift.php
 */
class TType {
  const STOP   = 0;
  const VOID   = 1;
  const BOOL   = 2;
  const BYTE   = 3;
  const I08    = 3;
  const DOUBLE = 4;
  const I16    = 6;
  const I32    = 8;
  const I64    = 10;
  const STRING = 11;
  const UTF7   = 11;
  const STRUCT = 12;
  const MAP    = 13;
  const SET    = 14;
  const LST    = 15;    // N.B. cannot use LIST keyword in PHP!
  const UTF8   = 16;
  const UTF16  = 17;
}

/**
 * Message types for RPC
 */
class TMessageType {
  const CALL  = 1;
  const REPLY = 2;
  const EXCEPTION = 3;
  const ONEWAY = 4;
}


/**
 * Base class from which other Thrift structs extend. This is so that we can
 * cut back on the size of the generated code which is turning out to have a
 * nontrivial cost just to load thanks to the wondrously abysmal implementation
 * of PHP. Note that code is intentionally duplicated in here to avoid making
 * function calls for every field or member of a container..
 */
abstract class TBase {

  static $tmethod = array(TType::BOOL   => 'Bool',
                          TType::BYTE   => 'Byte',
                          TType::I16    => 'I16',
                          TType::I32    => 'I32',
                          TType::I64    => 'I64',
                          TType::DOUBLE => 'Double',
                          TType::STRING => 'String');

  abstract function read($input);

  abstract function write($output);

  public function __construct($spec=null, $vals=null) {
    if (is_array($spec) && is_array($vals)) {
      foreach ($spec as $fid => $fspec) {
        $var = $fspec['var'];
        if (isset($vals[$var])) {
          $this->$var = $vals[$var];
        }
      }
    }
  }

  private function _readMap(&$var, $spec, $input) {
    $xfer = 0;
    $ktype = $spec['ktype'];
    $vtype = $spec['vtype'];
    $kread = $vread = null;
    if (isset(TBase::$tmethod[$ktype])) {
      $kread = 'read'.TBase::$tmethod[$ktype];
    } else {
      $kspec = $spec['key'];
    }
    if (isset(TBase::$tmethod[$vtype])) {
      $vread = 'read'.TBase::$tmethod[$vtype];
    } else {
      $vspec = $spec['val'];
    }
    $var = array();
    $_ktype = $_vtype = $size = 0;
    $xfer += $input->readMapBegin($_ktype, $_vtype, $size);
    for ($i = 0; $i < $size; ++$i) {
      $key = $val = null;
      if ($kread !== null) {
        $xfer += $input->$kread($key);
      } else {
        switch ($ktype) {
        case TType::STRUCT:
          $class = $kspec['class'];
          $key = new $class();
          $xfer += $key->read($input);
          break;
        case TType::MAP:
          $xfer += $this->_readMap($key, $kspec, $input);
          break;
        case TType::LST:
          $xfer += $this->_readList($key, $kspec, $input, false);
          break;
        case TType::SET:
          $xfer += $this->_readList($key, $kspec, $input, true);
          break;
        }
      }
      if ($vread !== null) {
        $xfer += $input->$vread($val);
      } else {
        switch ($vtype) {
        case TType::STRUCT:
          $class = $vspec['class'];
          $val = new $class();
          $xfer += $val->read($input);
          break;
        case TType::MAP:
          $xfer += $this->_readMap($val, $vspec, $input);
          break;
        case TType::LST:
          $xfer += $this->_readList($val, $vspec, $input, false);
          break;
        case TType::SET:
          $xfer += $this->_readList($val, $vspec, $input, true);
          break;
        }
      }
      $var[$key] = $val;
    }
    $xfer += $input->readMapEnd();
    return $xfer;
  }

  private function _readList(&$var, $spec, $input, $set=false) {
    $xfer = 0;
    $etype = $spec['etype'];
    $eread = $vread = null;
    if (isset(TBase::$tmethod[$etype])) {
      $eread = 'read'.TBase::$tmethod[$etype];
    } else {
      $espec = $spec['elem'];
    }
    $var = array();
    $_etype = $size = 0;
    if ($set) {
      $xfer += $input->readSetBegin($_etype, $size);
    } else {
      $xfer += $input->readListBegin($_etype, $size);
    }
    for ($i = 0; $i < $size; ++$i) {
      $elem = null;
      if ($eread !== null) {
        $xfer += $input->$eread($elem);
      } else {
        $espec = $spec['elem'];
        switch ($etype) {
        case TType::STRUCT:
          $class = $espec['class'];
          $elem = new $class();
          $xfer += $elem->read($input);
          break;
        case TType::MAP:
          $xfer += $this->_readMap($elem, $espec, $input);
          break;
        case TType::LST:
          $xfer += $this->_readList($elem, $espec, $input, false);
          break;
        case TType::SET:
          $xfer += $this->_readList($elem, $espec, $input, true);
          break;
        }
      }
      if ($set) {
        $var[$elem] = true;
      } else {
        $var []= $elem;
      }
    }
    if ($set) {
      $xfer += $input->readSetEnd();
    } else {
      $xfer += $input->readListEnd();
    }
    return $xfer;
  }

  protected function _read($class, $spec, $input) {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true) {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      if (isset($spec[$fid])) {
        $fspec = $spec[$fid];
        $var = $fspec['var'];
        if ($ftype == $fspec['type']) {
          $xfer = 0;
          if (isset(TBase::$tmethod[$ftype])) {
            $func = 'read'.TBase::$tmethod[$ftype];
            $xfer += $input->$func($this->$var);
          } else {
            switch ($ftype) {
            case TType::STRUCT:
              $class = $fspec['class'];
              $this->$var = new $class();
              $xfer += $this->$var->read($input);
              break;
            case TType::MAP:
              $xfer += $this->_readMap($this->$var, $fspec, $input);
              break;
            case TType::LST:
              $xfer += $this->_readList($this->$var, $fspec, $input, false);
              break;
            case TType::SET:
              $xfer += $this->_readList($this->$var, $fspec, $input, true);
              break;
            }
          }
        } else {
          $xfer += $input->skip($ftype);
        }
      } else {
        $xfer += $input->skip($ftype);
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  private function _writeMap($var, $spec, $output) {
    $xfer = 0;
    $ktype = $spec['ktype'];
    $vtype = $spec['vtype'];
    $kwrite = $vwrite = null;
    if (isset(TBase::$tmethod[$ktype])) {
      $kwrite = 'write'.TBase::$tmethod[$ktype];
    } else {
      $kspec = $spec['key'];
    }
    if (isset(TBase::$tmethod[$vtype])) {
      $vwrite = 'write'.TBase::$tmethod[$vtype];
    } else {
      $vspec = $spec['val'];
    }
    $xfer += $output->writeMapBegin($ktype, $vtype, count($var));
    foreach ($var as $key => $val) {
      if (isset($kwrite)) {
        $xfer += $output->$kwrite($key);
      } else {
        switch ($ktype) {
        case TType::STRUCT:
          $xfer += $key->write($output);
          break;
        case TType::MAP:
          $xfer += $this->_writeMap($key, $kspec, $output);
          break;
        case TType::LST:
          $xfer += $this->_writeList($key, $kspec, $output, false);
          break;
        case TType::SET:
          $xfer += $this->_writeList($key, $kspec, $output, true);
          break;
        }
      }
      if (isset($vwrite)) {
        $xfer += $output->$vwrite($val);
      } else {
        switch ($vtype) {
        case TType::STRUCT:
          $xfer += $val->write($output);
          break;
        case TType::MAP:
          $xfer += $this->_writeMap($val, $vspec, $output);
          break;
        case TType::LST:
          $xfer += $this->_writeList($val, $vspec, $output, false);
          break;
        case TType::SET:
          $xfer += $this->_writeList($val, $vspec, $output, true);
          break;
        }
      }
    }
    $xfer += $output->writeMapEnd();
    return $xfer;
  }

  private function _writeList($var, $spec, $output, $set=false) {
    $xfer = 0;
    $etype = $spec['etype'];
    $ewrite = null;
    if (isset(TBase::$tmethod[$etype])) {
      $ewrite = 'write'.TBase::$tmethod[$etype];
    } else {
      $espec = $spec['elem'];
    }
    if ($set) {
      $xfer += $output->writeSetBegin($etype, count($var));
    } else {
      $xfer += $output->writeListBegin($etype, count($var));
    }
    foreach ($var as $key => $val) {
      $elem = $set ? $key : $val;
      if (isset($ewrite)) {
        $xfer += $output->$ewrite($elem);
      } else {
        switch ($etype) {
        case TType::STRUCT:
          $xfer += $elem->write($output);
          break;
        case TType::MAP:
          $xfer += $this->_writeMap($elem, $espec, $output);
          break;
        case TType::LST:
          $xfer += $this->_writeList($elem, $espec, $output, false);
          break;
        case TType::SET:
          $xfer += $this->_writeList($elem, $espec, $output, true);
          break;
        }
      }
    }
    if ($set) {
      $xfer += $output->writeSetEnd();
    } else {
      $xfer += $output->writeListEnd();
    }
    return $xfer;
  }

  protected function _write($class, $spec, $output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin($class);
    foreach ($spec as $fid => $fspec) {
      $var = $fspec['var'];
      if ($this->$var !== null) {
        $ftype = $fspec['type'];
        $xfer += $output->writeFieldBegin($var, $ftype, $fid);
        if (isset(TBase::$tmethod[$ftype])) {
          $func = 'write'.TBase::$tmethod[$ftype];
          $xfer += $output->$func($this->$var);
        } else {
          switch ($ftype) {
          case TType::STRUCT:
            $xfer += $this->$var->write($output);
            break;
          case TType::MAP:
            $xfer += $this->_writeMap($this->$var, $fspec, $output);
            break;
          case TType::LST:
            $xfer += $this->_writeList($this->$var, $fspec, $output, false);
            break;
          case TType::SET:
            $xfer += $this->_writeList($this->$var, $fspec, $output, true);
            break;
          }
        }
        $xfer += $output->writeFieldEnd();
      }
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }
}

class TApplicationException extends TException {
  static $_TSPEC =
    array(1 => array('var' => 'message',
                     'type' => TType::STRING),
          2 => array('var' => 'code',
                     'type' => TType::I32));

  const UNKNOWN = 0;
  const UNKNOWN_METHOD = 1;
  const INVALID_MESSAGE_TYPE = 2;
  const WRONG_METHOD_NAME = 3;
  const BAD_SEQUENCE_ID = 4;
  const MISSING_RESULT = 5;

  function __construct($message=null, $code=0) {
    parent::__construct($message, $code);
  }

  public function read($output) {
    return $this->_read('TApplicationException', self::$_TSPEC, $output);
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('TApplicationException');
    if ($message = $this->getMessage()) {
      $xfer += $output->writeFieldBegin('message', TType::STRING, 1);
      $xfer += $output->writeString($message);
      $xfer += $output->writeFieldEnd();
    }
    if ($code = $this->getCode()) {
      $xfer += $output->writeFieldBegin('type', TType::I32, 2);
      $xfer += $output->writeI32($code);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }
}





	/*
	 * CASSANDRA CONSTANTS
	 */
	$GLOBALS['cassandra_CONSTANTS'] 			= array();
	$GLOBALS['cassandra_CONSTANTS']['VERSION'] 	= "0.5.0";



/**
 * CASSANDRA_TYPES.php
 */

$GLOBALS['cassandra_E_ConsistencyLevel'] = array(
  'ZERO' => 0,
  'ONE' => 1,
  'QUORUM' => 2,
  'DCQUORUM' => 3,
  'DCQUORUMSYNC' => 4,
  'ALL' => 5,
);

final class cassandra_ConsistencyLevel {
  const ZERO = 0;
  const ONE = 1;
  const QUORUM = 2;
  const DCQUORUM = 3;
  const DCQUORUMSYNC = 4;
  const ALL = 5;
  static public $__names = array(
    0 => 'ZERO',
    1 => 'ONE',
    2 => 'QUORUM',
    3 => 'DCQUORUM',
    4 => 'DCQUORUMSYNC',
    5 => 'ALL',
  );
}

class cassandra_Column {
  static $_TSPEC;

  public $name = null;
  public $value = null;
  public $timestamp = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'name',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'value',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'timestamp',
          'type' => TType::I64,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['name'])) {
        $this->name = $vals['name'];
      }
      if (isset($vals['value'])) {
        $this->value = $vals['value'];
      }
      if (isset($vals['timestamp'])) {
        $this->timestamp = $vals['timestamp'];
      }
    }
  }

  public function getName() {
    return 'Column';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->name);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->value);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->timestamp);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Column');
    if ($this->name !== null) {
      $xfer += $output->writeFieldBegin('name', TType::STRING, 1);
      $xfer += $output->writeString($this->name);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->value !== null) {
      $xfer += $output->writeFieldBegin('value', TType::STRING, 2);
      $xfer += $output->writeString($this->value);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->timestamp !== null) {
      $xfer += $output->writeFieldBegin('timestamp', TType::I64, 3);
      $xfer += $output->writeI64($this->timestamp);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_SuperColumn {
  static $_TSPEC;

  public $name = null;
  public $columns = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'name',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'columns',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => 'cassandra_Column',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['name'])) {
        $this->name = $vals['name'];
      }
      if (isset($vals['columns'])) {
        $this->columns = $vals['columns'];
      }
    }
  }

  public function getName() {
    return 'SuperColumn';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->name);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::LST) {
            $this->columns = array();
            $_size0 = 0;
            $_etype3 = 0;
            $xfer += $input->readListBegin($_etype3, $_size0);
            for ($_i4 = 0; $_i4 < $_size0; ++$_i4)
            {
              $elem5 = null;
              $elem5 = new cassandra_Column();
              $xfer += $elem5->read($input);
              $this->columns []= $elem5;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('SuperColumn');
    if ($this->name !== null) {
      $xfer += $output->writeFieldBegin('name', TType::STRING, 1);
      $xfer += $output->writeString($this->name);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->columns !== null) {
      if (!is_array($this->columns)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('columns', TType::LST, 2);
      {
        $output->writeListBegin(TType::STRUCT, count($this->columns));
        {
          foreach ($this->columns as $iter6)
          {
            $xfer += $iter6->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_ColumnOrSuperColumn {
  static $_TSPEC;

  public $column = null;
  public $super_column = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'column',
          'type' => TType::STRUCT,
          'class' => 'cassandra_Column',
          ),
        2 => array(
          'var' => 'super_column',
          'type' => TType::STRUCT,
          'class' => 'cassandra_SuperColumn',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['column'])) {
        $this->column = $vals['column'];
      }
      if (isset($vals['super_column'])) {
        $this->super_column = $vals['super_column'];
      }
    }
  }

  public function getName() {
    return 'ColumnOrSuperColumn';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->column = new cassandra_Column();
            $xfer += $this->column->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->super_column = new cassandra_SuperColumn();
            $xfer += $this->super_column->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('ColumnOrSuperColumn');
    if ($this->column !== null) {
      if (!is_object($this->column)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('column', TType::STRUCT, 1);
      $xfer += $this->column->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->super_column !== null) {
      if (!is_object($this->super_column)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('super_column', TType::STRUCT, 2);
      $xfer += $this->super_column->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_NotFoundException extends TException {
  static $_TSPEC;


  public function __construct() {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        );
    }
  }

  public function getName() {
    return 'NotFoundException';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('NotFoundException');
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_InvalidRequestException extends TException {
  static $_TSPEC;

  public $why = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'why',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['why'])) {
        $this->why = $vals['why'];
      }
    }
  }

  public function getName() {
    return 'InvalidRequestException';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->why);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('InvalidRequestException');
    if ($this->why !== null) {
      $xfer += $output->writeFieldBegin('why', TType::STRING, 1);
      $xfer += $output->writeString($this->why);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_UnavailableException extends TException {
  static $_TSPEC;


  public function __construct() {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        );
    }
  }

  public function getName() {
    return 'UnavailableException';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('UnavailableException');
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_TimedOutException extends TException {
  static $_TSPEC;


  public function __construct() {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        );
    }
  }

  public function getName() {
    return 'TimedOutException';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('TimedOutException');
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_ColumnParent {
  static $_TSPEC;

  public $column_family = null;
  public $super_column = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        3 => array(
          'var' => 'column_family',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'super_column',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['column_family'])) {
        $this->column_family = $vals['column_family'];
      }
      if (isset($vals['super_column'])) {
        $this->super_column = $vals['super_column'];
      }
    }
  }

  public function getName() {
    return 'ColumnParent';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->column_family);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->super_column);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('ColumnParent');
    if ($this->column_family !== null) {
      $xfer += $output->writeFieldBegin('column_family', TType::STRING, 3);
      $xfer += $output->writeString($this->column_family);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->super_column !== null) {
      $xfer += $output->writeFieldBegin('super_column', TType::STRING, 4);
      $xfer += $output->writeString($this->super_column);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_ColumnPath {
  static $_TSPEC;

  public $column_family = null;
  public $super_column = null;
  public $column = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        3 => array(
          'var' => 'column_family',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'super_column',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'column',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['column_family'])) {
        $this->column_family = $vals['column_family'];
      }
      if (isset($vals['super_column'])) {
        $this->super_column = $vals['super_column'];
      }
      if (isset($vals['column'])) {
        $this->column = $vals['column'];
      }
    }
  }

  public function getName() {
    return 'ColumnPath';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->column_family);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->super_column);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->column);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('ColumnPath');
    if ($this->column_family !== null) {
      $xfer += $output->writeFieldBegin('column_family', TType::STRING, 3);
      $xfer += $output->writeString($this->column_family);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->super_column !== null) {
      $xfer += $output->writeFieldBegin('super_column', TType::STRING, 4);
      $xfer += $output->writeString($this->super_column);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->column !== null) {
      $xfer += $output->writeFieldBegin('column', TType::STRING, 5);
      $xfer += $output->writeString($this->column);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_SliceRange {
  static $_TSPEC;

  public $start = null;
  public $finish = null;
  public $reversed = false;
  public $count = 100;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'start',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'finish',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'reversed',
          'type' => TType::BOOL,
          ),
        4 => array(
          'var' => 'count',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['start'])) {
        $this->start = $vals['start'];
      }
      if (isset($vals['finish'])) {
        $this->finish = $vals['finish'];
      }
      if (isset($vals['reversed'])) {
        $this->reversed = $vals['reversed'];
      }
      if (isset($vals['count'])) {
        $this->count = $vals['count'];
      }
    }
  }

  public function getName() {
    return 'SliceRange';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->start);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->finish);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::BOOL) {
            $xfer += $input->readBool($this->reversed);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->count);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('SliceRange');
    if ($this->start !== null) {
      $xfer += $output->writeFieldBegin('start', TType::STRING, 1);
      $xfer += $output->writeString($this->start);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->finish !== null) {
      $xfer += $output->writeFieldBegin('finish', TType::STRING, 2);
      $xfer += $output->writeString($this->finish);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->reversed !== null) {
      $xfer += $output->writeFieldBegin('reversed', TType::BOOL, 3);
      $xfer += $output->writeBool($this->reversed);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->count !== null) {
      $xfer += $output->writeFieldBegin('count', TType::I32, 4);
      $xfer += $output->writeI32($this->count);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_SlicePredicate {
  static $_TSPEC;

  public $column_names = null;
  public $slice_range = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'column_names',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        2 => array(
          'var' => 'slice_range',
          'type' => TType::STRUCT,
          'class' => 'cassandra_SliceRange',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['column_names'])) {
        $this->column_names = $vals['column_names'];
      }
      if (isset($vals['slice_range'])) {
        $this->slice_range = $vals['slice_range'];
      }
    }
  }

  public function getName() {
    return 'SlicePredicate';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::LST) {
            $this->column_names = array();
            $_size7 = 0;
            $_etype10 = 0;
            $xfer += $input->readListBegin($_etype10, $_size7);
            for ($_i11 = 0; $_i11 < $_size7; ++$_i11)
            {
              $elem12 = null;
              $xfer += $input->readString($elem12);
              $this->column_names []= $elem12;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->slice_range = new cassandra_SliceRange();
            $xfer += $this->slice_range->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('SlicePredicate');
    if ($this->column_names !== null) {
      if (!is_array($this->column_names)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('column_names', TType::LST, 1);
      {
        $output->writeListBegin(TType::STRING, count($this->column_names));
        {
          foreach ($this->column_names as $iter13)
          {
            $xfer += $output->writeString($iter13);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->slice_range !== null) {
      if (!is_object($this->slice_range)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('slice_range', TType::STRUCT, 2);
      $xfer += $this->slice_range->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_KeySlice {
  static $_TSPEC;

  public $key = null;
  public $columns = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'key',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'columns',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => 'cassandra_ColumnOrSuperColumn',
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['key'])) {
        $this->key = $vals['key'];
      }
      if (isset($vals['columns'])) {
        $this->columns = $vals['columns'];
      }
    }
  }

  public function getName() {
    return 'KeySlice';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->key);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::LST) {
            $this->columns = array();
            $_size14 = 0;
            $_etype17 = 0;
            $xfer += $input->readListBegin($_etype17, $_size14);
            for ($_i18 = 0; $_i18 < $_size14; ++$_i18)
            {
              $elem19 = null;
              $elem19 = new cassandra_ColumnOrSuperColumn();
              $xfer += $elem19->read($input);
              $this->columns []= $elem19;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('KeySlice');
    if ($this->key !== null) {
      $xfer += $output->writeFieldBegin('key', TType::STRING, 1);
      $xfer += $output->writeString($this->key);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->columns !== null) {
      if (!is_array($this->columns)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('columns', TType::LST, 2);
      {
        $output->writeListBegin(TType::STRUCT, count($this->columns));
        {
          foreach ($this->columns as $iter20)
          {
            $xfer += $iter20->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}




/**
 * CASSANDRA.php
 */

interface CassandraIf {
  public function get($keyspace, $key, $column_path, $consistency_level);
  public function get_slice($keyspace, $key, $column_parent, $predicate, $consistency_level);
  public function multiget($keyspace, $keys, $column_path, $consistency_level);
  public function multiget_slice($keyspace, $keys, $column_parent, $predicate, $consistency_level);
  public function get_count($keyspace, $key, $column_parent, $consistency_level);
  public function get_key_range($keyspace, $column_family, $start, $finish, $count, $consistency_level);
  public function get_range_slice($keyspace, $column_parent, $predicate, $start_key, $finish_key, $row_count, $consistency_level);
  public function insert($keyspace, $key, $column_path, $value, $timestamp, $consistency_level);
  public function batch_insert($keyspace, $key, $cfmap, $consistency_level);
  public function remove($keyspace, $key, $column_path, $timestamp, $consistency_level);
  public function get_string_property($property);
  public function get_string_list_property($property);
  public function describe_keyspace($keyspace);
}

class CassandraClient implements CassandraIf {
  protected $input_ = null;
  protected $output_ = null;

  protected $seqid_ = 0;

  public function __construct($input, $output=null) {
    $this->input_ = $input;
    $this->output_ = $output ? $output : $input;
  }

  public function get($keyspace, $key, $column_path, $consistency_level)
  {
    $this->send_get($keyspace, $key, $column_path, $consistency_level);
    return $this->recv_get();
  }

  public function send_get($keyspace, $key, $column_path, $consistency_level)
  {
    $args = new cassandra_Cassandra_get_args();
    $args->keyspace = $keyspace;
    $args->key = $key;
    $args->column_path = $column_path;
    $args->consistency_level = $consistency_level;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'get', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('get', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_get()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_get_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_get_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->ire !== null) {
      throw $result->ire;
    }
    if ($result->nfe !== null) {
      throw $result->nfe;
    }
    if ($result->ue !== null) {
      throw $result->ue;
    }
    if ($result->te !== null) {
      throw $result->te;
    }
    throw new Exception("get failed: unknown result");
  }

  public function get_slice($keyspace, $key, $column_parent, $predicate, $consistency_level)
  {
    $this->send_get_slice($keyspace, $key, $column_parent, $predicate, $consistency_level);
    return $this->recv_get_slice();
  }

  public function send_get_slice($keyspace, $key, $column_parent, $predicate, $consistency_level)
  {
    $args = new cassandra_Cassandra_get_slice_args();
    $args->keyspace = $keyspace;
    $args->key = $key;
    $args->column_parent = $column_parent;
    $args->predicate = $predicate;
    $args->consistency_level = $consistency_level;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'get_slice', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('get_slice', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_get_slice()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_get_slice_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_get_slice_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->ire !== null) {
      throw $result->ire;
    }
    if ($result->ue !== null) {
      throw $result->ue;
    }
    if ($result->te !== null) {
      throw $result->te;
    }
    throw new Exception("get_slice failed: unknown result");
  }

  public function multiget($keyspace, $keys, $column_path, $consistency_level)
  {
    $this->send_multiget($keyspace, $keys, $column_path, $consistency_level);
    return $this->recv_multiget();
  }

  public function send_multiget($keyspace, $keys, $column_path, $consistency_level)
  {
    $args = new cassandra_Cassandra_multiget_args();
    $args->keyspace = $keyspace;
    $args->keys = $keys;
    $args->column_path = $column_path;
    $args->consistency_level = $consistency_level;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'multiget', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('multiget', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_multiget()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_multiget_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_multiget_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->ire !== null) {
      throw $result->ire;
    }
    if ($result->ue !== null) {
      throw $result->ue;
    }
    if ($result->te !== null) {
      throw $result->te;
    }
    throw new Exception("multiget failed: unknown result");
  }

  public function multiget_slice($keyspace, $keys, $column_parent, $predicate, $consistency_level)
  {
    $this->send_multiget_slice($keyspace, $keys, $column_parent, $predicate, $consistency_level);
    return $this->recv_multiget_slice();
  }

  public function send_multiget_slice($keyspace, $keys, $column_parent, $predicate, $consistency_level)
  {
    $args = new cassandra_Cassandra_multiget_slice_args();
    $args->keyspace = $keyspace;
    $args->keys = $keys;
    $args->column_parent = $column_parent;
    $args->predicate = $predicate;
    $args->consistency_level = $consistency_level;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'multiget_slice', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('multiget_slice', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_multiget_slice()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_multiget_slice_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_multiget_slice_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->ire !== null) {
      throw $result->ire;
    }
    if ($result->ue !== null) {
      throw $result->ue;
    }
    if ($result->te !== null) {
      throw $result->te;
    }
    throw new Exception("multiget_slice failed: unknown result");
  }

  public function get_count($keyspace, $key, $column_parent, $consistency_level)
  {
    $this->send_get_count($keyspace, $key, $column_parent, $consistency_level);
    return $this->recv_get_count();
  }

  public function send_get_count($keyspace, $key, $column_parent, $consistency_level)
  {
    $args = new cassandra_Cassandra_get_count_args();
    $args->keyspace = $keyspace;
    $args->key = $key;
    $args->column_parent = $column_parent;
    $args->consistency_level = $consistency_level;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'get_count', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('get_count', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_get_count()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_get_count_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_get_count_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->ire !== null) {
      throw $result->ire;
    }
    if ($result->ue !== null) {
      throw $result->ue;
    }
    if ($result->te !== null) {
      throw $result->te;
    }
    throw new Exception("get_count failed: unknown result");
  }

  public function get_key_range($keyspace, $column_family, $start, $finish, $count, $consistency_level)
  {
    $this->send_get_key_range($keyspace, $column_family, $start, $finish, $count, $consistency_level);
    return $this->recv_get_key_range();
  }

  public function send_get_key_range($keyspace, $column_family, $start, $finish, $count, $consistency_level)
  {
    $args = new cassandra_Cassandra_get_key_range_args();
    $args->keyspace = $keyspace;
    $args->column_family = $column_family;
    $args->start = $start;
    $args->finish = $finish;
    $args->count = $count;
    $args->consistency_level = $consistency_level;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'get_key_range', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('get_key_range', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_get_key_range()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_get_key_range_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_get_key_range_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->ire !== null) {
      throw $result->ire;
    }
    if ($result->ue !== null) {
      throw $result->ue;
    }
    if ($result->te !== null) {
      throw $result->te;
    }
    throw new Exception("get_key_range failed: unknown result");
  }

  public function get_range_slice($keyspace, $column_parent, $predicate, $start_key, $finish_key, $row_count, $consistency_level)
  {
    $this->send_get_range_slice($keyspace, $column_parent, $predicate, $start_key, $finish_key, $row_count, $consistency_level);
    return $this->recv_get_range_slice();
  }

  public function send_get_range_slice($keyspace, $column_parent, $predicate, $start_key, $finish_key, $row_count, $consistency_level)
  {
    $args = new cassandra_Cassandra_get_range_slice_args();
    $args->keyspace = $keyspace;
    $args->column_parent = $column_parent;
    $args->predicate = $predicate;
    $args->start_key = $start_key;
    $args->finish_key = $finish_key;
    $args->row_count = $row_count;
    $args->consistency_level = $consistency_level;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'get_range_slice', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('get_range_slice', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_get_range_slice()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_get_range_slice_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_get_range_slice_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->ire !== null) {
      throw $result->ire;
    }
    if ($result->ue !== null) {
      throw $result->ue;
    }
    if ($result->te !== null) {
      throw $result->te;
    }
    throw new Exception("get_range_slice failed: unknown result");
  }

  public function insert($keyspace, $key, $column_path, $value, $timestamp, $consistency_level)
  {
    $this->send_insert($keyspace, $key, $column_path, $value, $timestamp, $consistency_level);
    $this->recv_insert();
  }

  public function send_insert($keyspace, $key, $column_path, $value, $timestamp, $consistency_level)
  {
    $args = new cassandra_Cassandra_insert_args();
    $args->keyspace = $keyspace;
    $args->key = $key;
    $args->column_path = $column_path;
    $args->value = $value;
    $args->timestamp = $timestamp;
    $args->consistency_level = $consistency_level;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'insert', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('insert', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_insert()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_insert_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_insert_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->ire !== null) {
      throw $result->ire;
    }
    if ($result->ue !== null) {
      throw $result->ue;
    }
    if ($result->te !== null) {
      throw $result->te;
    }
    return;
  }

  public function batch_insert($keyspace, $key, $cfmap, $consistency_level)
  {
    $this->send_batch_insert($keyspace, $key, $cfmap, $consistency_level);
    $this->recv_batch_insert();
  }

  public function send_batch_insert($keyspace, $key, $cfmap, $consistency_level)
  {
    $args = new cassandra_Cassandra_batch_insert_args();
    $args->keyspace = $keyspace;
    $args->key = $key;
    $args->cfmap = $cfmap;
    $args->consistency_level = $consistency_level;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'batch_insert', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('batch_insert', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_batch_insert()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_batch_insert_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_batch_insert_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->ire !== null) {
      throw $result->ire;
    }
    if ($result->ue !== null) {
      throw $result->ue;
    }
    if ($result->te !== null) {
      throw $result->te;
    }
    return;
  }

  public function remove($keyspace, $key, $column_path, $timestamp, $consistency_level)
  {
    $this->send_remove($keyspace, $key, $column_path, $timestamp, $consistency_level);
    $this->recv_remove();
  }

  public function send_remove($keyspace, $key, $column_path, $timestamp, $consistency_level)
  {
    $args = new cassandra_Cassandra_remove_args();
    $args->keyspace = $keyspace;
    $args->key = $key;
    $args->column_path = $column_path;
    $args->timestamp = $timestamp;
    $args->consistency_level = $consistency_level;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'remove', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('remove', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_remove()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_remove_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_remove_result();
      $result->read($this->input_);
      
      $this->input_->readMessageEnd();
    }
    if ($result->ire !== null) {
      throw $result->ire;
    }
    if ($result->ue !== null) {
      throw $result->ue;
    }
    if ($result->te !== null) {
      throw $result->te;
    }
    return;
  }

  public function get_string_property($property)
  {
    $this->send_get_string_property($property);
    return $this->recv_get_string_property();
  }

  public function send_get_string_property($property)
  {
    $args = new cassandra_Cassandra_get_string_property_args();
    $args->property = $property;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'get_string_property', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('get_string_property', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_get_string_property()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_get_string_property_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_get_string_property_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    throw new Exception("get_string_property failed: unknown result");
  }

  public function get_string_list_property($property)
  {
    $this->send_get_string_list_property($property);
    return $this->recv_get_string_list_property();
  }

  public function send_get_string_list_property($property)
  {
    $args = new cassandra_Cassandra_get_string_list_property_args();
    $args->property = $property;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'get_string_list_property', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('get_string_list_property', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_get_string_list_property()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_get_string_list_property_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_get_string_list_property_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    throw new Exception("get_string_list_property failed: unknown result");
  }

  public function describe_keyspace($keyspace)
  {
    $this->send_describe_keyspace($keyspace);
    return $this->recv_describe_keyspace();
  }

  public function send_describe_keyspace($keyspace)
  {
    $args = new cassandra_Cassandra_describe_keyspace_args();
    $args->keyspace = $keyspace;
    $bin_accel = ($this->output_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_write_binary');
    if ($bin_accel)
    {
      thrift_protocol_write_binary($this->output_, 'describe_keyspace', TMessageType::CALL, $args, $this->seqid_, $this->output_->isStrictWrite());
    }
    else
    {
      $this->output_->writeMessageBegin('describe_keyspace', TMessageType::CALL, $this->seqid_);
      $args->write($this->output_);
      $this->output_->writeMessageEnd();
      $this->output_->getTransport()->flush();
    }
  }

  public function recv_describe_keyspace()
  {
    $bin_accel = ($this->input_ instanceof TProtocol::$TBINARYPROTOCOLACCELERATED) && function_exists('thrift_protocol_read_binary');
    if ($bin_accel) $result = thrift_protocol_read_binary($this->input_, 'cassandra_Cassandra_describe_keyspace_result', $this->input_->isStrictRead());
    else
    {
      $rseqid = 0;
      $fname = null;
      $mtype = 0;

      $this->input_->readMessageBegin($fname, $mtype, $rseqid);
      if ($mtype == TMessageType::EXCEPTION) {
        $x = new TApplicationException();
        $x->read($this->input_);
        $this->input_->readMessageEnd();
        throw $x;
      }
      $result = new cassandra_Cassandra_describe_keyspace_result();
      $result->read($this->input_);
      $this->input_->readMessageEnd();
    }
    if ($result->success !== null) {
      return $result->success;
    }
    if ($result->nfe !== null) {
      throw $result->nfe;
    }
    throw new Exception("describe_keyspace failed: unknown result");
  }

}

// HELPER FUNCTIONS AND STRUCTURES

class cassandra_Cassandra_get_args {
  static $_TSPEC;

  public $keyspace = null;
  public $key = null;
  public $column_path = null;
  public $consistency_level =   1;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'key',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'column_path',
          'type' => TType::STRUCT,
          'class' => 'cassandra_ColumnPath',
          ),
        4 => array(
          'var' => 'consistency_level',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
      if (isset($vals['key'])) {
        $this->key = $vals['key'];
      }
      if (isset($vals['column_path'])) {
        $this->column_path = $vals['column_path'];
      }
      if (isset($vals['consistency_level'])) {
        $this->consistency_level = $vals['consistency_level'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->key);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->column_path = new cassandra_ColumnPath();
            $xfer += $this->column_path->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->consistency_level);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->key !== null) {
      $xfer += $output->writeFieldBegin('key', TType::STRING, 2);
      $xfer += $output->writeString($this->key);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->column_path !== null) {
      if (!is_object($this->column_path)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('column_path', TType::STRUCT, 3);
      $xfer += $this->column_path->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->consistency_level !== null) {
      $xfer += $output->writeFieldBegin('consistency_level', TType::I32, 4);
      $xfer += $output->writeI32($this->consistency_level);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_result {
  static $_TSPEC;

  public $success = null;
  public $ire = null;
  public $nfe = null;
  public $ue = null;
  public $te = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::STRUCT,
          'class' => 'cassandra_ColumnOrSuperColumn',
          ),
        1 => array(
          'var' => 'ire',
          'type' => TType::STRUCT,
          'class' => 'cassandra_InvalidRequestException',
          ),
        2 => array(
          'var' => 'nfe',
          'type' => TType::STRUCT,
          'class' => 'cassandra_NotFoundException',
          ),
        3 => array(
          'var' => 'ue',
          'type' => TType::STRUCT,
          'class' => 'cassandra_UnavailableException',
          ),
        4 => array(
          'var' => 'te',
          'type' => TType::STRUCT,
          'class' => 'cassandra_TimedOutException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
      if (isset($vals['ire'])) {
        $this->ire = $vals['ire'];
      }
      if (isset($vals['nfe'])) {
        $this->nfe = $vals['nfe'];
      }
      if (isset($vals['ue'])) {
        $this->ue = $vals['ue'];
      }
      if (isset($vals['te'])) {
        $this->te = $vals['te'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::STRUCT) {
            $this->success = new cassandra_ColumnOrSuperColumn();
            $xfer += $this->success->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->ire = new cassandra_InvalidRequestException();
            $xfer += $this->ire->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->nfe = new cassandra_NotFoundException();
            $xfer += $this->nfe->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->ue = new cassandra_UnavailableException();
            $xfer += $this->ue->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRUCT) {
            $this->te = new cassandra_TimedOutException();
            $xfer += $this->te->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_result');
    if ($this->success !== null) {
      if (!is_object($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::STRUCT, 0);
      $xfer += $this->success->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ire !== null) {
      $xfer += $output->writeFieldBegin('ire', TType::STRUCT, 1);
      $xfer += $this->ire->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->nfe !== null) {
      $xfer += $output->writeFieldBegin('nfe', TType::STRUCT, 2);
      $xfer += $this->nfe->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ue !== null) {
      $xfer += $output->writeFieldBegin('ue', TType::STRUCT, 3);
      $xfer += $this->ue->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->te !== null) {
      $xfer += $output->writeFieldBegin('te', TType::STRUCT, 4);
      $xfer += $this->te->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_slice_args {
  static $_TSPEC;

  public $keyspace = null;
  public $key = null;
  public $column_parent = null;
  public $predicate = null;
  public $consistency_level =   1;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'key',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'column_parent',
          'type' => TType::STRUCT,
          'class' => 'cassandra_ColumnParent',
          ),
        4 => array(
          'var' => 'predicate',
          'type' => TType::STRUCT,
          'class' => 'cassandra_SlicePredicate',
          ),
        5 => array(
          'var' => 'consistency_level',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
      if (isset($vals['key'])) {
        $this->key = $vals['key'];
      }
      if (isset($vals['column_parent'])) {
        $this->column_parent = $vals['column_parent'];
      }
      if (isset($vals['predicate'])) {
        $this->predicate = $vals['predicate'];
      }
      if (isset($vals['consistency_level'])) {
        $this->consistency_level = $vals['consistency_level'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_slice_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->key);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->column_parent = new cassandra_ColumnParent();
            $xfer += $this->column_parent->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRUCT) {
            $this->predicate = new cassandra_SlicePredicate();
            $xfer += $this->predicate->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->consistency_level);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_slice_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->key !== null) {
      $xfer += $output->writeFieldBegin('key', TType::STRING, 2);
      $xfer += $output->writeString($this->key);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->column_parent !== null) {
      if (!is_object($this->column_parent)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('column_parent', TType::STRUCT, 3);
      $xfer += $this->column_parent->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->predicate !== null) {
      if (!is_object($this->predicate)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('predicate', TType::STRUCT, 4);
      $xfer += $this->predicate->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->consistency_level !== null) {
      $xfer += $output->writeFieldBegin('consistency_level', TType::I32, 5);
      $xfer += $output->writeI32($this->consistency_level);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_slice_result {
  static $_TSPEC;

  public $success = null;
  public $ire = null;
  public $ue = null;
  public $te = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => 'cassandra_ColumnOrSuperColumn',
            ),
          ),
        1 => array(
          'var' => 'ire',
          'type' => TType::STRUCT,
          'class' => 'cassandra_InvalidRequestException',
          ),
        2 => array(
          'var' => 'ue',
          'type' => TType::STRUCT,
          'class' => 'cassandra_UnavailableException',
          ),
        3 => array(
          'var' => 'te',
          'type' => TType::STRUCT,
          'class' => 'cassandra_TimedOutException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
      if (isset($vals['ire'])) {
        $this->ire = $vals['ire'];
      }
      if (isset($vals['ue'])) {
        $this->ue = $vals['ue'];
      }
      if (isset($vals['te'])) {
        $this->te = $vals['te'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_slice_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::LST) {
            $this->success = array();
            $_size21 = 0;
            $_etype24 = 0;
            $xfer += $input->readListBegin($_etype24, $_size21);
            for ($_i25 = 0; $_i25 < $_size21; ++$_i25)
            {
              $elem26 = null;
              $elem26 = new cassandra_ColumnOrSuperColumn();
              $xfer += $elem26->read($input);
              $this->success []= $elem26;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->ire = new cassandra_InvalidRequestException();
            $xfer += $this->ire->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->ue = new cassandra_UnavailableException();
            $xfer += $this->ue->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->te = new cassandra_TimedOutException();
            $xfer += $this->te->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_slice_result');
    if ($this->success !== null) {
      if (!is_array($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::LST, 0);
      {
        $output->writeListBegin(TType::STRUCT, count($this->success));
        {
          foreach ($this->success as $iter27)
          {
            $xfer += $iter27->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ire !== null) {
      $xfer += $output->writeFieldBegin('ire', TType::STRUCT, 1);
      $xfer += $this->ire->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ue !== null) {
      $xfer += $output->writeFieldBegin('ue', TType::STRUCT, 2);
      $xfer += $this->ue->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->te !== null) {
      $xfer += $output->writeFieldBegin('te', TType::STRUCT, 3);
      $xfer += $this->te->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_multiget_args {
  static $_TSPEC;

  public $keyspace = null;
  public $keys = null;
  public $column_path = null;
  public $consistency_level =   1;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'keys',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        3 => array(
          'var' => 'column_path',
          'type' => TType::STRUCT,
          'class' => 'cassandra_ColumnPath',
          ),
        4 => array(
          'var' => 'consistency_level',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
      if (isset($vals['keys'])) {
        $this->keys = $vals['keys'];
      }
      if (isset($vals['column_path'])) {
        $this->column_path = $vals['column_path'];
      }
      if (isset($vals['consistency_level'])) {
        $this->consistency_level = $vals['consistency_level'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_multiget_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::LST) {
            $this->keys = array();
            $_size28 = 0;
            $_etype31 = 0;
            $xfer += $input->readListBegin($_etype31, $_size28);
            for ($_i32 = 0; $_i32 < $_size28; ++$_i32)
            {
              $elem33 = null;
              $xfer += $input->readString($elem33);
              $this->keys []= $elem33;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->column_path = new cassandra_ColumnPath();
            $xfer += $this->column_path->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->consistency_level);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_multiget_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->keys !== null) {
      if (!is_array($this->keys)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('keys', TType::LST, 2);
      {
        $output->writeListBegin(TType::STRING, count($this->keys));
        {
          foreach ($this->keys as $iter34)
          {
            $xfer += $output->writeString($iter34);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->column_path !== null) {
      if (!is_object($this->column_path)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('column_path', TType::STRUCT, 3);
      $xfer += $this->column_path->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->consistency_level !== null) {
      $xfer += $output->writeFieldBegin('consistency_level', TType::I32, 4);
      $xfer += $output->writeI32($this->consistency_level);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_multiget_result {
  static $_TSPEC;

  public $success = null;
  public $ire = null;
  public $ue = null;
  public $te = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::MAP,
          'ktype' => TType::STRING,
          'vtype' => TType::STRUCT,
          'key' => array(
            'type' => TType::STRING,
          ),
          'val' => array(
            'type' => TType::STRUCT,
            'class' => 'cassandra_ColumnOrSuperColumn',
            ),
          ),
        1 => array(
          'var' => 'ire',
          'type' => TType::STRUCT,
          'class' => 'cassandra_InvalidRequestException',
          ),
        2 => array(
          'var' => 'ue',
          'type' => TType::STRUCT,
          'class' => 'cassandra_UnavailableException',
          ),
        3 => array(
          'var' => 'te',
          'type' => TType::STRUCT,
          'class' => 'cassandra_TimedOutException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
      if (isset($vals['ire'])) {
        $this->ire = $vals['ire'];
      }
      if (isset($vals['ue'])) {
        $this->ue = $vals['ue'];
      }
      if (isset($vals['te'])) {
        $this->te = $vals['te'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_multiget_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::MAP) {
            $this->success = array();
            $_size35 = 0;
            $_ktype36 = 0;
            $_vtype37 = 0;
            $xfer += $input->readMapBegin($_ktype36, $_vtype37, $_size35);
            for ($_i39 = 0; $_i39 < $_size35; ++$_i39)
            {
              $key40 = '';
              $val41 = new cassandra_ColumnOrSuperColumn();
              $xfer += $input->readString($key40);
              $val41 = new cassandra_ColumnOrSuperColumn();
              $xfer += $val41->read($input);
              $this->success[$key40] = $val41;
            }
            $xfer += $input->readMapEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->ire = new cassandra_InvalidRequestException();
            $xfer += $this->ire->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->ue = new cassandra_UnavailableException();
            $xfer += $this->ue->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->te = new cassandra_TimedOutException();
            $xfer += $this->te->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_multiget_result');
    if ($this->success !== null) {
      if (!is_array($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::MAP, 0);
      {
        $output->writeMapBegin(TType::STRING, TType::STRUCT, count($this->success));
        {
          foreach ($this->success as $kiter42 => $viter43)
          {
            $xfer += $output->writeString($kiter42);
            $xfer += $viter43->write($output);
          }
        }
        $output->writeMapEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ire !== null) {
      $xfer += $output->writeFieldBegin('ire', TType::STRUCT, 1);
      $xfer += $this->ire->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ue !== null) {
      $xfer += $output->writeFieldBegin('ue', TType::STRUCT, 2);
      $xfer += $this->ue->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->te !== null) {
      $xfer += $output->writeFieldBegin('te', TType::STRUCT, 3);
      $xfer += $this->te->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_multiget_slice_args {
  static $_TSPEC;

  public $keyspace = null;
  public $keys = null;
  public $column_parent = null;
  public $predicate = null;
  public $consistency_level =   1;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'keys',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        3 => array(
          'var' => 'column_parent',
          'type' => TType::STRUCT,
          'class' => 'cassandra_ColumnParent',
          ),
        4 => array(
          'var' => 'predicate',
          'type' => TType::STRUCT,
          'class' => 'cassandra_SlicePredicate',
          ),
        5 => array(
          'var' => 'consistency_level',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
      if (isset($vals['keys'])) {
        $this->keys = $vals['keys'];
      }
      if (isset($vals['column_parent'])) {
        $this->column_parent = $vals['column_parent'];
      }
      if (isset($vals['predicate'])) {
        $this->predicate = $vals['predicate'];
      }
      if (isset($vals['consistency_level'])) {
        $this->consistency_level = $vals['consistency_level'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_multiget_slice_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::LST) {
            $this->keys = array();
            $_size44 = 0;
            $_etype47 = 0;
            $xfer += $input->readListBegin($_etype47, $_size44);
            for ($_i48 = 0; $_i48 < $_size44; ++$_i48)
            {
              $elem49 = null;
              $xfer += $input->readString($elem49);
              $this->keys []= $elem49;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->column_parent = new cassandra_ColumnParent();
            $xfer += $this->column_parent->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRUCT) {
            $this->predicate = new cassandra_SlicePredicate();
            $xfer += $this->predicate->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->consistency_level);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_multiget_slice_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->keys !== null) {
      if (!is_array($this->keys)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('keys', TType::LST, 2);
      {
        $output->writeListBegin(TType::STRING, count($this->keys));
        {
          foreach ($this->keys as $iter50)
          {
            $xfer += $output->writeString($iter50);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->column_parent !== null) {
      if (!is_object($this->column_parent)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('column_parent', TType::STRUCT, 3);
      $xfer += $this->column_parent->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->predicate !== null) {
      if (!is_object($this->predicate)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('predicate', TType::STRUCT, 4);
      $xfer += $this->predicate->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->consistency_level !== null) {
      $xfer += $output->writeFieldBegin('consistency_level', TType::I32, 5);
      $xfer += $output->writeI32($this->consistency_level);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_multiget_slice_result {
  static $_TSPEC;

  public $success = null;
  public $ire = null;
  public $ue = null;
  public $te = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::MAP,
          'ktype' => TType::STRING,
          'vtype' => TType::LST,
          'key' => array(
            'type' => TType::STRING,
          ),
          'val' => array(
            'type' => TType::LST,
            'etype' => TType::STRUCT,
            'elem' => array(
              'type' => TType::STRUCT,
              'class' => 'cassandra_ColumnOrSuperColumn',
              ),
            ),
          ),
        1 => array(
          'var' => 'ire',
          'type' => TType::STRUCT,
          'class' => 'cassandra_InvalidRequestException',
          ),
        2 => array(
          'var' => 'ue',
          'type' => TType::STRUCT,
          'class' => 'cassandra_UnavailableException',
          ),
        3 => array(
          'var' => 'te',
          'type' => TType::STRUCT,
          'class' => 'cassandra_TimedOutException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
      if (isset($vals['ire'])) {
        $this->ire = $vals['ire'];
      }
      if (isset($vals['ue'])) {
        $this->ue = $vals['ue'];
      }
      if (isset($vals['te'])) {
        $this->te = $vals['te'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_multiget_slice_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::MAP) {
            $this->success = array();
            $_size51 = 0;
            $_ktype52 = 0;
            $_vtype53 = 0;
            $xfer += $input->readMapBegin($_ktype52, $_vtype53, $_size51);
            for ($_i55 = 0; $_i55 < $_size51; ++$_i55)
            {
              $key56 = '';
              $val57 = array();
              $xfer += $input->readString($key56);
              $val57 = array();
              $_size58 = 0;
              $_etype61 = 0;
              $xfer += $input->readListBegin($_etype61, $_size58);
              for ($_i62 = 0; $_i62 < $_size58; ++$_i62)
              {
                $elem63 = null;
                $elem63 = new cassandra_ColumnOrSuperColumn();
                $xfer += $elem63->read($input);
                $val57 []= $elem63;
              }
              $xfer += $input->readListEnd();
              $this->success[$key56] = $val57;
            }
            $xfer += $input->readMapEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->ire = new cassandra_InvalidRequestException();
            $xfer += $this->ire->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->ue = new cassandra_UnavailableException();
            $xfer += $this->ue->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->te = new cassandra_TimedOutException();
            $xfer += $this->te->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_multiget_slice_result');
    if ($this->success !== null) {
      if (!is_array($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::MAP, 0);
      {
        $output->writeMapBegin(TType::STRING, TType::LST, count($this->success));
        {
          foreach ($this->success as $kiter64 => $viter65)
          {
            $xfer += $output->writeString($kiter64);
            {
              $output->writeListBegin(TType::STRUCT, count($viter65));
              {
                foreach ($viter65 as $iter66)
                {
                  $xfer += $iter66->write($output);
                }
              }
              $output->writeListEnd();
            }
          }
        }
        $output->writeMapEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ire !== null) {
      $xfer += $output->writeFieldBegin('ire', TType::STRUCT, 1);
      $xfer += $this->ire->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ue !== null) {
      $xfer += $output->writeFieldBegin('ue', TType::STRUCT, 2);
      $xfer += $this->ue->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->te !== null) {
      $xfer += $output->writeFieldBegin('te', TType::STRUCT, 3);
      $xfer += $this->te->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_count_args {
  static $_TSPEC;

  public $keyspace = null;
  public $key = null;
  public $column_parent = null;
  public $consistency_level =   1;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'key',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'column_parent',
          'type' => TType::STRUCT,
          'class' => 'cassandra_ColumnParent',
          ),
        4 => array(
          'var' => 'consistency_level',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
      if (isset($vals['key'])) {
        $this->key = $vals['key'];
      }
      if (isset($vals['column_parent'])) {
        $this->column_parent = $vals['column_parent'];
      }
      if (isset($vals['consistency_level'])) {
        $this->consistency_level = $vals['consistency_level'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_count_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->key);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->column_parent = new cassandra_ColumnParent();
            $xfer += $this->column_parent->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->consistency_level);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_count_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->key !== null) {
      $xfer += $output->writeFieldBegin('key', TType::STRING, 2);
      $xfer += $output->writeString($this->key);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->column_parent !== null) {
      if (!is_object($this->column_parent)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('column_parent', TType::STRUCT, 3);
      $xfer += $this->column_parent->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->consistency_level !== null) {
      $xfer += $output->writeFieldBegin('consistency_level', TType::I32, 4);
      $xfer += $output->writeI32($this->consistency_level);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_count_result {
  static $_TSPEC;

  public $success = null;
  public $ire = null;
  public $ue = null;
  public $te = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::I32,
          ),
        1 => array(
          'var' => 'ire',
          'type' => TType::STRUCT,
          'class' => 'cassandra_InvalidRequestException',
          ),
        2 => array(
          'var' => 'ue',
          'type' => TType::STRUCT,
          'class' => 'cassandra_UnavailableException',
          ),
        3 => array(
          'var' => 'te',
          'type' => TType::STRUCT,
          'class' => 'cassandra_TimedOutException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
      if (isset($vals['ire'])) {
        $this->ire = $vals['ire'];
      }
      if (isset($vals['ue'])) {
        $this->ue = $vals['ue'];
      }
      if (isset($vals['te'])) {
        $this->te = $vals['te'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_count_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->success);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->ire = new cassandra_InvalidRequestException();
            $xfer += $this->ire->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->ue = new cassandra_UnavailableException();
            $xfer += $this->ue->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->te = new cassandra_TimedOutException();
            $xfer += $this->te->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_count_result');
    if ($this->success !== null) {
      $xfer += $output->writeFieldBegin('success', TType::I32, 0);
      $xfer += $output->writeI32($this->success);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ire !== null) {
      $xfer += $output->writeFieldBegin('ire', TType::STRUCT, 1);
      $xfer += $this->ire->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ue !== null) {
      $xfer += $output->writeFieldBegin('ue', TType::STRUCT, 2);
      $xfer += $this->ue->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->te !== null) {
      $xfer += $output->writeFieldBegin('te', TType::STRUCT, 3);
      $xfer += $this->te->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_key_range_args {
  static $_TSPEC;

  public $keyspace = null;
  public $column_family = null;
  public $start = "";
  public $finish = "";
  public $count = 100;
  public $consistency_level =   1;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'column_family',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'start',
          'type' => TType::STRING,
          ),
        4 => array(
          'var' => 'finish',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'count',
          'type' => TType::I32,
          ),
        6 => array(
          'var' => 'consistency_level',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
      if (isset($vals['column_family'])) {
        $this->column_family = $vals['column_family'];
      }
      if (isset($vals['start'])) {
        $this->start = $vals['start'];
      }
      if (isset($vals['finish'])) {
        $this->finish = $vals['finish'];
      }
      if (isset($vals['count'])) {
        $this->count = $vals['count'];
      }
      if (isset($vals['consistency_level'])) {
        $this->consistency_level = $vals['consistency_level'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_key_range_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->column_family);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->start);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->finish);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->count);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 6:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->consistency_level);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_key_range_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->column_family !== null) {
      $xfer += $output->writeFieldBegin('column_family', TType::STRING, 2);
      $xfer += $output->writeString($this->column_family);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->start !== null) {
      $xfer += $output->writeFieldBegin('start', TType::STRING, 3);
      $xfer += $output->writeString($this->start);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->finish !== null) {
      $xfer += $output->writeFieldBegin('finish', TType::STRING, 4);
      $xfer += $output->writeString($this->finish);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->count !== null) {
      $xfer += $output->writeFieldBegin('count', TType::I32, 5);
      $xfer += $output->writeI32($this->count);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->consistency_level !== null) {
      $xfer += $output->writeFieldBegin('consistency_level', TType::I32, 6);
      $xfer += $output->writeI32($this->consistency_level);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_key_range_result {
  static $_TSPEC;

  public $success = null;
  public $ire = null;
  public $ue = null;
  public $te = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        1 => array(
          'var' => 'ire',
          'type' => TType::STRUCT,
          'class' => 'cassandra_InvalidRequestException',
          ),
        2 => array(
          'var' => 'ue',
          'type' => TType::STRUCT,
          'class' => 'cassandra_UnavailableException',
          ),
        3 => array(
          'var' => 'te',
          'type' => TType::STRUCT,
          'class' => 'cassandra_TimedOutException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
      if (isset($vals['ire'])) {
        $this->ire = $vals['ire'];
      }
      if (isset($vals['ue'])) {
        $this->ue = $vals['ue'];
      }
      if (isset($vals['te'])) {
        $this->te = $vals['te'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_key_range_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::LST) {
            $this->success = array();
            $_size67 = 0;
            $_etype70 = 0;
            $xfer += $input->readListBegin($_etype70, $_size67);
            for ($_i71 = 0; $_i71 < $_size67; ++$_i71)
            {
              $elem72 = null;
              $xfer += $input->readString($elem72);
              $this->success []= $elem72;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->ire = new cassandra_InvalidRequestException();
            $xfer += $this->ire->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->ue = new cassandra_UnavailableException();
            $xfer += $this->ue->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->te = new cassandra_TimedOutException();
            $xfer += $this->te->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_key_range_result');
    if ($this->success !== null) {
      if (!is_array($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::LST, 0);
      {
        $output->writeListBegin(TType::STRING, count($this->success));
        {
          foreach ($this->success as $iter73)
          {
            $xfer += $output->writeString($iter73);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ire !== null) {
      $xfer += $output->writeFieldBegin('ire', TType::STRUCT, 1);
      $xfer += $this->ire->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ue !== null) {
      $xfer += $output->writeFieldBegin('ue', TType::STRUCT, 2);
      $xfer += $this->ue->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->te !== null) {
      $xfer += $output->writeFieldBegin('te', TType::STRUCT, 3);
      $xfer += $this->te->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_range_slice_args {
  static $_TSPEC;

  public $keyspace = null;
  public $column_parent = null;
  public $predicate = null;
  public $start_key = "";
  public $finish_key = "";
  public $row_count = 100;
  public $consistency_level =   1;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'column_parent',
          'type' => TType::STRUCT,
          'class' => 'cassandra_ColumnParent',
          ),
        3 => array(
          'var' => 'predicate',
          'type' => TType::STRUCT,
          'class' => 'cassandra_SlicePredicate',
          ),
        4 => array(
          'var' => 'start_key',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'finish_key',
          'type' => TType::STRING,
          ),
        6 => array(
          'var' => 'row_count',
          'type' => TType::I32,
          ),
        7 => array(
          'var' => 'consistency_level',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
      if (isset($vals['column_parent'])) {
        $this->column_parent = $vals['column_parent'];
      }
      if (isset($vals['predicate'])) {
        $this->predicate = $vals['predicate'];
      }
      if (isset($vals['start_key'])) {
        $this->start_key = $vals['start_key'];
      }
      if (isset($vals['finish_key'])) {
        $this->finish_key = $vals['finish_key'];
      }
      if (isset($vals['row_count'])) {
        $this->row_count = $vals['row_count'];
      }
      if (isset($vals['consistency_level'])) {
        $this->consistency_level = $vals['consistency_level'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_range_slice_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->column_parent = new cassandra_ColumnParent();
            $xfer += $this->column_parent->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->predicate = new cassandra_SlicePredicate();
            $xfer += $this->predicate->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->start_key);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->finish_key);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 6:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->row_count);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 7:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->consistency_level);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_range_slice_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->column_parent !== null) {
      if (!is_object($this->column_parent)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('column_parent', TType::STRUCT, 2);
      $xfer += $this->column_parent->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->predicate !== null) {
      if (!is_object($this->predicate)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('predicate', TType::STRUCT, 3);
      $xfer += $this->predicate->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->start_key !== null) {
      $xfer += $output->writeFieldBegin('start_key', TType::STRING, 4);
      $xfer += $output->writeString($this->start_key);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->finish_key !== null) {
      $xfer += $output->writeFieldBegin('finish_key', TType::STRING, 5);
      $xfer += $output->writeString($this->finish_key);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->row_count !== null) {
      $xfer += $output->writeFieldBegin('row_count', TType::I32, 6);
      $xfer += $output->writeI32($this->row_count);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->consistency_level !== null) {
      $xfer += $output->writeFieldBegin('consistency_level', TType::I32, 7);
      $xfer += $output->writeI32($this->consistency_level);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_range_slice_result {
  static $_TSPEC;

  public $success = null;
  public $ire = null;
  public $ue = null;
  public $te = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::LST,
          'etype' => TType::STRUCT,
          'elem' => array(
            'type' => TType::STRUCT,
            'class' => 'cassandra_KeySlice',
            ),
          ),
        1 => array(
          'var' => 'ire',
          'type' => TType::STRUCT,
          'class' => 'cassandra_InvalidRequestException',
          ),
        2 => array(
          'var' => 'ue',
          'type' => TType::STRUCT,
          'class' => 'cassandra_UnavailableException',
          ),
        3 => array(
          'var' => 'te',
          'type' => TType::STRUCT,
          'class' => 'cassandra_TimedOutException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
      if (isset($vals['ire'])) {
        $this->ire = $vals['ire'];
      }
      if (isset($vals['ue'])) {
        $this->ue = $vals['ue'];
      }
      if (isset($vals['te'])) {
        $this->te = $vals['te'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_range_slice_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::LST) {
            $this->success = array();
            $_size74 = 0;
            $_etype77 = 0;
            $xfer += $input->readListBegin($_etype77, $_size74);
            for ($_i78 = 0; $_i78 < $_size74; ++$_i78)
            {
              $elem79 = null;
              $elem79 = new cassandra_KeySlice();
              $xfer += $elem79->read($input);
              $this->success []= $elem79;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->ire = new cassandra_InvalidRequestException();
            $xfer += $this->ire->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->ue = new cassandra_UnavailableException();
            $xfer += $this->ue->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->te = new cassandra_TimedOutException();
            $xfer += $this->te->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_range_slice_result');
    if ($this->success !== null) {
      if (!is_array($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::LST, 0);
      {
        $output->writeListBegin(TType::STRUCT, count($this->success));
        {
          foreach ($this->success as $iter80)
          {
            $xfer += $iter80->write($output);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ire !== null) {
      $xfer += $output->writeFieldBegin('ire', TType::STRUCT, 1);
      $xfer += $this->ire->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ue !== null) {
      $xfer += $output->writeFieldBegin('ue', TType::STRUCT, 2);
      $xfer += $this->ue->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->te !== null) {
      $xfer += $output->writeFieldBegin('te', TType::STRUCT, 3);
      $xfer += $this->te->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_insert_args {
  static $_TSPEC;

  public $keyspace = null;
  public $key = null;
  public $column_path = null;
  public $value = null;
  public $timestamp = null;
  public $consistency_level =   0;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'key',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'column_path',
          'type' => TType::STRUCT,
          'class' => 'cassandra_ColumnPath',
          ),
        4 => array(
          'var' => 'value',
          'type' => TType::STRING,
          ),
        5 => array(
          'var' => 'timestamp',
          'type' => TType::I64,
          ),
        6 => array(
          'var' => 'consistency_level',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
      if (isset($vals['key'])) {
        $this->key = $vals['key'];
      }
      if (isset($vals['column_path'])) {
        $this->column_path = $vals['column_path'];
      }
      if (isset($vals['value'])) {
        $this->value = $vals['value'];
      }
      if (isset($vals['timestamp'])) {
        $this->timestamp = $vals['timestamp'];
      }
      if (isset($vals['consistency_level'])) {
        $this->consistency_level = $vals['consistency_level'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_insert_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->key);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->column_path = new cassandra_ColumnPath();
            $xfer += $this->column_path->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->value);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->timestamp);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 6:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->consistency_level);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_insert_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->key !== null) {
      $xfer += $output->writeFieldBegin('key', TType::STRING, 2);
      $xfer += $output->writeString($this->key);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->column_path !== null) {
      if (!is_object($this->column_path)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('column_path', TType::STRUCT, 3);
      $xfer += $this->column_path->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->value !== null) {
      $xfer += $output->writeFieldBegin('value', TType::STRING, 4);
      $xfer += $output->writeString($this->value);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->timestamp !== null) {
      $xfer += $output->writeFieldBegin('timestamp', TType::I64, 5);
      $xfer += $output->writeI64($this->timestamp);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->consistency_level !== null) {
      $xfer += $output->writeFieldBegin('consistency_level', TType::I32, 6);
      $xfer += $output->writeI32($this->consistency_level);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_insert_result {
  static $_TSPEC;

  public $ire = null;
  public $ue = null;
  public $te = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'ire',
          'type' => TType::STRUCT,
          'class' => 'cassandra_InvalidRequestException',
          ),
        2 => array(
          'var' => 'ue',
          'type' => TType::STRUCT,
          'class' => 'cassandra_UnavailableException',
          ),
        3 => array(
          'var' => 'te',
          'type' => TType::STRUCT,
          'class' => 'cassandra_TimedOutException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['ire'])) {
        $this->ire = $vals['ire'];
      }
      if (isset($vals['ue'])) {
        $this->ue = $vals['ue'];
      }
      if (isset($vals['te'])) {
        $this->te = $vals['te'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_insert_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->ire = new cassandra_InvalidRequestException();
            $xfer += $this->ire->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->ue = new cassandra_UnavailableException();
            $xfer += $this->ue->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->te = new cassandra_TimedOutException();
            $xfer += $this->te->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_insert_result');
    if ($this->ire !== null) {
      $xfer += $output->writeFieldBegin('ire', TType::STRUCT, 1);
      $xfer += $this->ire->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ue !== null) {
      $xfer += $output->writeFieldBegin('ue', TType::STRUCT, 2);
      $xfer += $this->ue->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->te !== null) {
      $xfer += $output->writeFieldBegin('te', TType::STRUCT, 3);
      $xfer += $this->te->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_batch_insert_args {
  static $_TSPEC;

  public $keyspace = null;
  public $key = null;
  public $cfmap = null;
  public $consistency_level =   0;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'key',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'cfmap',
          'type' => TType::MAP,
          'ktype' => TType::STRING,
          'vtype' => TType::LST,
          'key' => array(
            'type' => TType::STRING,
          ),
          'val' => array(
            'type' => TType::LST,
            'etype' => TType::STRUCT,
            'elem' => array(
              'type' => TType::STRUCT,
              'class' => 'cassandra_ColumnOrSuperColumn',
              ),
            ),
          ),
        4 => array(
          'var' => 'consistency_level',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
      if (isset($vals['key'])) {
        $this->key = $vals['key'];
      }
      if (isset($vals['cfmap'])) {
        $this->cfmap = $vals['cfmap'];
      }
      if (isset($vals['consistency_level'])) {
        $this->consistency_level = $vals['consistency_level'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_batch_insert_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->key);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::MAP) {
            $this->cfmap = array();
            $_size81 = 0;
            $_ktype82 = 0;
            $_vtype83 = 0;
            $xfer += $input->readMapBegin($_ktype82, $_vtype83, $_size81);
            for ($_i85 = 0; $_i85 < $_size81; ++$_i85)
            {
              $key86 = '';
              $val87 = array();
              $xfer += $input->readString($key86);
              $val87 = array();
              $_size88 = 0;
              $_etype91 = 0;
              $xfer += $input->readListBegin($_etype91, $_size88);
              for ($_i92 = 0; $_i92 < $_size88; ++$_i92)
              {
                $elem93 = null;
                $elem93 = new cassandra_ColumnOrSuperColumn();
                $xfer += $elem93->read($input);
                $val87 []= $elem93;
              }
              $xfer += $input->readListEnd();
              $this->cfmap[$key86] = $val87;
            }
            $xfer += $input->readMapEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->consistency_level);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_batch_insert_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->key !== null) {
      $xfer += $output->writeFieldBegin('key', TType::STRING, 2);
      $xfer += $output->writeString($this->key);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->cfmap !== null) {
      if (!is_array($this->cfmap)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('cfmap', TType::MAP, 3);
      {
        $output->writeMapBegin(TType::STRING, TType::LST, count($this->cfmap));
        {
          foreach ($this->cfmap as $kiter94 => $viter95)
          {
            $xfer += $output->writeString($kiter94);
            {
              $output->writeListBegin(TType::STRUCT, count($viter95));
              {
                foreach ($viter95 as $iter96)
                {
                  $xfer += $iter96->write($output);
                }
              }
              $output->writeListEnd();
            }
          }
        }
        $output->writeMapEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->consistency_level !== null) {
      $xfer += $output->writeFieldBegin('consistency_level', TType::I32, 4);
      $xfer += $output->writeI32($this->consistency_level);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_batch_insert_result {
  static $_TSPEC;

  public $ire = null;
  public $ue = null;
  public $te = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'ire',
          'type' => TType::STRUCT,
          'class' => 'cassandra_InvalidRequestException',
          ),
        2 => array(
          'var' => 'ue',
          'type' => TType::STRUCT,
          'class' => 'cassandra_UnavailableException',
          ),
        3 => array(
          'var' => 'te',
          'type' => TType::STRUCT,
          'class' => 'cassandra_TimedOutException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['ire'])) {
        $this->ire = $vals['ire'];
      }
      if (isset($vals['ue'])) {
        $this->ue = $vals['ue'];
      }
      if (isset($vals['te'])) {
        $this->te = $vals['te'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_batch_insert_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->ire = new cassandra_InvalidRequestException();
            $xfer += $this->ire->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->ue = new cassandra_UnavailableException();
            $xfer += $this->ue->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->te = new cassandra_TimedOutException();
            $xfer += $this->te->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_batch_insert_result');
    if ($this->ire !== null) {
      $xfer += $output->writeFieldBegin('ire', TType::STRUCT, 1);
      $xfer += $this->ire->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ue !== null) {
      $xfer += $output->writeFieldBegin('ue', TType::STRUCT, 2);
      $xfer += $this->ue->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->te !== null) {
      $xfer += $output->writeFieldBegin('te', TType::STRUCT, 3);
      $xfer += $this->te->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_remove_args {
  static $_TSPEC;

  public $keyspace = null;
  public $key = null;
  public $column_path = null;
  public $timestamp = null;
  public $consistency_level =   0;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        2 => array(
          'var' => 'key',
          'type' => TType::STRING,
          ),
        3 => array(
          'var' => 'column_path',
          'type' => TType::STRUCT,
          'class' => 'cassandra_ColumnPath',
          ),
        4 => array(
          'var' => 'timestamp',
          'type' => TType::I64,
          ),
        5 => array(
          'var' => 'consistency_level',
          'type' => TType::I32,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
      if (isset($vals['key'])) {
        $this->key = $vals['key'];
      }
      if (isset($vals['column_path'])) {
        $this->column_path = $vals['column_path'];
      }
      if (isset($vals['timestamp'])) {
        $this->timestamp = $vals['timestamp'];
      }
      if (isset($vals['consistency_level'])) {
        $this->consistency_level = $vals['consistency_level'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_remove_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->key);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->column_path = new cassandra_ColumnPath();
            $xfer += $this->column_path->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 4:
          if ($ftype == TType::I64) {
            $xfer += $input->readI64($this->timestamp);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 5:
          if ($ftype == TType::I32) {
            $xfer += $input->readI32($this->consistency_level);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_remove_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->key !== null) {
      $xfer += $output->writeFieldBegin('key', TType::STRING, 2);
      $xfer += $output->writeString($this->key);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->column_path !== null) {
      if (!is_object($this->column_path)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('column_path', TType::STRUCT, 3);
      $xfer += $this->column_path->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->timestamp !== null) {
      $xfer += $output->writeFieldBegin('timestamp', TType::I64, 4);
      $xfer += $output->writeI64($this->timestamp);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->consistency_level !== null) {
      $xfer += $output->writeFieldBegin('consistency_level', TType::I32, 5);
      $xfer += $output->writeI32($this->consistency_level);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_remove_result {
  static $_TSPEC;

  public $ire = null;
  public $ue = null;
  public $te = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'ire',
          'type' => TType::STRUCT,
          'class' => 'cassandra_InvalidRequestException',
          ),
        2 => array(
          'var' => 'ue',
          'type' => TType::STRUCT,
          'class' => 'cassandra_UnavailableException',
          ),
        3 => array(
          'var' => 'te',
          'type' => TType::STRUCT,
          'class' => 'cassandra_TimedOutException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['ire'])) {
        $this->ire = $vals['ire'];
      }
      if (isset($vals['ue'])) {
        $this->ue = $vals['ue'];
      }
      if (isset($vals['te'])) {
        $this->te = $vals['te'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_remove_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->ire = new cassandra_InvalidRequestException();
            $xfer += $this->ire->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 2:
          if ($ftype == TType::STRUCT) {
            $this->ue = new cassandra_UnavailableException();
            $xfer += $this->ue->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 3:
          if ($ftype == TType::STRUCT) {
            $this->te = new cassandra_TimedOutException();
            $xfer += $this->te->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_remove_result');
    if ($this->ire !== null) {
      $xfer += $output->writeFieldBegin('ire', TType::STRUCT, 1);
      $xfer += $this->ire->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->ue !== null) {
      $xfer += $output->writeFieldBegin('ue', TType::STRUCT, 2);
      $xfer += $this->ue->write($output);
      $xfer += $output->writeFieldEnd();
    }
    if ($this->te !== null) {
      $xfer += $output->writeFieldBegin('te', TType::STRUCT, 3);
      $xfer += $this->te->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_string_property_args {
  static $_TSPEC;

  public $property = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'property',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['property'])) {
        $this->property = $vals['property'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_string_property_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->property);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_string_property_args');
    if ($this->property !== null) {
      $xfer += $output->writeFieldBegin('property', TType::STRING, 1);
      $xfer += $output->writeString($this->property);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_string_property_result {
  static $_TSPEC;

  public $success = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_string_property_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->success);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_string_property_result');
    if ($this->success !== null) {
      $xfer += $output->writeFieldBegin('success', TType::STRING, 0);
      $xfer += $output->writeString($this->success);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_string_list_property_args {
  static $_TSPEC;

  public $property = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'property',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['property'])) {
        $this->property = $vals['property'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_string_list_property_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->property);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_string_list_property_args');
    if ($this->property !== null) {
      $xfer += $output->writeFieldBegin('property', TType::STRING, 1);
      $xfer += $output->writeString($this->property);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_get_string_list_property_result {
  static $_TSPEC;

  public $success = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::LST,
          'etype' => TType::STRING,
          'elem' => array(
            'type' => TType::STRING,
            ),
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_get_string_list_property_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::LST) {
            $this->success = array();
            $_size97 = 0;
            $_etype100 = 0;
            $xfer += $input->readListBegin($_etype100, $_size97);
            for ($_i101 = 0; $_i101 < $_size97; ++$_i101)
            {
              $elem102 = null;
              $xfer += $input->readString($elem102);
              $this->success []= $elem102;
            }
            $xfer += $input->readListEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_get_string_list_property_result');
    if ($this->success !== null) {
      if (!is_array($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::LST, 0);
      {
        $output->writeListBegin(TType::STRING, count($this->success));
        {
          foreach ($this->success as $iter103)
          {
            $xfer += $output->writeString($iter103);
          }
        }
        $output->writeListEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_describe_keyspace_args {
  static $_TSPEC;

  public $keyspace = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        1 => array(
          'var' => 'keyspace',
          'type' => TType::STRING,
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['keyspace'])) {
        $this->keyspace = $vals['keyspace'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_describe_keyspace_args';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 1:
          if ($ftype == TType::STRING) {
            $xfer += $input->readString($this->keyspace);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_describe_keyspace_args');
    if ($this->keyspace !== null) {
      $xfer += $output->writeFieldBegin('keyspace', TType::STRING, 1);
      $xfer += $output->writeString($this->keyspace);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

class cassandra_Cassandra_describe_keyspace_result {
  static $_TSPEC;

  public $success = null;
  public $nfe = null;

  public function __construct($vals=null) {
    if (!isset(self::$_TSPEC)) {
      self::$_TSPEC = array(
        0 => array(
          'var' => 'success',
          'type' => TType::MAP,
          'ktype' => TType::STRING,
          'vtype' => TType::MAP,
          'key' => array(
            'type' => TType::STRING,
          ),
          'val' => array(
            'type' => TType::MAP,
            'ktype' => TType::STRING,
            'vtype' => TType::STRING,
            'key' => array(
              'type' => TType::STRING,
            ),
            'val' => array(
              'type' => TType::STRING,
              ),
            ),
          ),
        1 => array(
          'var' => 'nfe',
          'type' => TType::STRUCT,
          'class' => 'cassandra_NotFoundException',
          ),
        );
    }
    if (is_array($vals)) {
      if (isset($vals['success'])) {
        $this->success = $vals['success'];
      }
      if (isset($vals['nfe'])) {
        $this->nfe = $vals['nfe'];
      }
    }
  }

  public function getName() {
    return 'Cassandra_describe_keyspace_result';
  }

  public function read($input)
  {
    $xfer = 0;
    $fname = null;
    $ftype = 0;
    $fid = 0;
    $xfer += $input->readStructBegin($fname);
    while (true)
    {
      $xfer += $input->readFieldBegin($fname, $ftype, $fid);
      if ($ftype == TType::STOP) {
        break;
      }
      switch ($fid)
      {
        case 0:
          if ($ftype == TType::MAP) {
            $this->success = array();
            $_size104 = 0;
            $_ktype105 = 0;
            $_vtype106 = 0;
            $xfer += $input->readMapBegin($_ktype105, $_vtype106, $_size104);
            for ($_i108 = 0; $_i108 < $_size104; ++$_i108)
            {
              $key109 = '';
              $val110 = array();
              $xfer += $input->readString($key109);
              $val110 = array();
              $_size111 = 0;
              $_ktype112 = 0;
              $_vtype113 = 0;
              $xfer += $input->readMapBegin($_ktype112, $_vtype113, $_size111);
              for ($_i115 = 0; $_i115 < $_size111; ++$_i115)
              {
                $key116 = '';
                $val117 = '';
                $xfer += $input->readString($key116);
                $xfer += $input->readString($val117);
                $val110[$key116] = $val117;
              }
              $xfer += $input->readMapEnd();
              $this->success[$key109] = $val110;
            }
            $xfer += $input->readMapEnd();
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        case 1:
          if ($ftype == TType::STRUCT) {
            $this->nfe = new cassandra_NotFoundException();
            $xfer += $this->nfe->read($input);
          } else {
            $xfer += $input->skip($ftype);
          }
          break;
        default:
          $xfer += $input->skip($ftype);
          break;
      }
      $xfer += $input->readFieldEnd();
    }
    $xfer += $input->readStructEnd();
    return $xfer;
  }

  public function write($output) {
    $xfer = 0;
    $xfer += $output->writeStructBegin('Cassandra_describe_keyspace_result');
    if ($this->success !== null) {
      if (!is_array($this->success)) {
        throw new TProtocolException('Bad type in structure.', TProtocolException::INVALID_DATA);
      }
      $xfer += $output->writeFieldBegin('success', TType::MAP, 0);
      {
        $output->writeMapBegin(TType::STRING, TType::MAP, count($this->success));
        {
          foreach ($this->success as $kiter118 => $viter119)
          {
            $xfer += $output->writeString($kiter118);
            {
              $output->writeMapBegin(TType::STRING, TType::STRING, count($viter119));
              {
                foreach ($viter119 as $kiter120 => $viter121)
                {
                  $xfer += $output->writeString($kiter120);
                  $xfer += $output->writeString($viter121);
                }
              }
              $output->writeMapEnd();
            }
          }
        }
        $output->writeMapEnd();
      }
      $xfer += $output->writeFieldEnd();
    }
    if ($this->nfe !== null) {
      $xfer += $output->writeFieldBegin('nfe', TType::STRUCT, 1);
      $xfer += $this->nfe->write($output);
      $xfer += $output->writeFieldEnd();
    }
    $xfer += $output->writeFieldStop();
    $xfer += $output->writeStructEnd();
    return $xfer;
  }

}

?>