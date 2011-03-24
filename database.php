<?php

/**
 * \file database.php
 * \brief Contains the database connectivity classes
 *
 * Contains the DatabaseConnection, QueryResult, and MysqlConnection classes.
 * Defines DatabaseException, NotFoundException and AssertionFailedException.
 */

class DatabaseException extends Exception {}
class NotFoundException extends Exception {}
class AssertionFailedException extends Exception {}

/**
 * \brief Database connection and utilities
 *
 * The DatabaseConnection class provides a DBMS-independant database interface, as well as certain often-used utility functions.
 */
class MysqlConnection {
	private $connected;
	private $connectionData;
	private $delayPending;
	private $connection;
	
	/**
	 * \brief Constructor
	 * 
	 * \return void
	 */
	public function __construct()
	{
		$this->connected = false;
		$this->delayPending = false;
		$this->connectionData = null;
		$this->connection = null;
	}
	
	/**
	 * \brief Connect to a database server
	 * \param $address Server address
	 * \param $username Connection username
	 * \param $password Connection password
	 * \param $dbname Database name
	 *
	 * \return void
	 * \throws DatabaseException if no connection can be made
	 *
	 * Not all parameters are applicable for each DBMS.
	 */
	public function open($address, $username, $password, $dbname)
	{
		if($this->connected) {
			throw new AssertionFailedException("Already connected");
		}
		
		$this->connectionData = array("address"=>$address, "username"=>$username, "password"=>$password, "dbname"=>$dbname);
		$this->delayPending = false;
		
		$this->dbOpen($address, $username, $password, $dbname);
		$this->connected = true;
	}
	
	/**
	 * \brief Connect to a database server whenever it is used
	 * \param $address Server address
	 * \param $username Connection username
	 * \param $password Connection password
	 * \param $dbname Database name
	 *
	 * \return void
	 *
	 * Not all parameters are applicable for each DBMS.
	 */
	public function openLater($address, $username, $password, $dbname)
	{
		if($this->connected) {
			throw new AssertionFailedException("Already connected");
		}
		
		$this->connectionData = array("address"=>$address, "username"=>$username, "password"=>$password, "dbname"=>$dbname);
		$this->delayPending = true;
	}
	
	/**
	 * \brief Disconnect from the database server
	 *
	 * \return void
	 */
	public function close()
	{
		if($this->connected) {
			$this->dbClose();
		}
		$this->connected = false;
		$this->delayPending = false;
		$this->connectionData = null;
	}
	
	/**
	 * \brief Execute a query, returning the raw result
	 * \param $query The SQL query string
	 *
	 * \return A resource on data retrieval queries, \c true otherwise
	 * \throws DatabaseException in case of a query error or database connection error
	 */
	public function rawQuery($query)
	{
		if($this->delayPending) {
			$this->open($this->connectionData["address"],
			            $this->connectionData["username"],
			            $this->connectionData["password"],
			            $this->connectionData["dbname"]
			           );
		}
		
		if(!$this->connected) {
			throw new AssertionFailedException("Not connected");
		}
		
		return $this->dbQuery($query);
	}
	
	/**
	 * \brief Execute a data retrieval query
	 * \param $query The SQL query string
	 *
	 * \return A QueryResult object
	 * \throws DatabaseError in case of a query error or database connection error
	 *
	 * \warning Do not use this function for data modification; use setQuery instead.
	 *
	 * \see setQuery
	 */
	public function query($query)
	{
		return new QueryResult($this, $this->rawQuery($query));
	}
	
	/**
	 * \brief Execute a data modification query
	 * \param $query The SQL query string
	 *
	 * \return The number of affected rows
	 * \throws DatabaseError in case of a query error or database connection error
	 *
	 * \see query
	 */
	public function setQuery($query)
	{
		$this->rawQuery($query);
		return $this->dbAffectedRows();
	}
	
	/**
	 * \brief Get the latest error message
	 *
	 * \return The latest error message produced by the database server
	 */
	public function error()
	{
		return $this->dbError();
	}
	
	/**
	 * \brief Escape a string to the point where it's safe to insert it into queries
	 */
	public function addSlashes($data)
	{
		return mysql_real_escape_string($data, $this->connection);
	}
	
	/**
	 * \brief Get a single record from a single table using simple conditions
	 * \param $table The name of the table
	 * \param $where The search conditions
	 * \param $get The fields to get
	 *
	 * \return Either an associative array of fields to values or a single value
	 * \throws DatabaseException in case of a database connection error
	 * \throws NotFoundException if a number of records other than 1 is found
	 *
	 * This function gets a single record in \a $table which matches the conditions specified in \a $where.
	 * \a $where is an associative array of fieldnames (in \a $table) to values.
	 * The record is retrieved whose \a $key field equals \a $value, for all (\a $key => \a $value) pairs in \a $where.
	 * \a $get can either be an array of fields to get or a single field. If it is an array,
	 * the return value is an associative array of fieldnames to values; if it is a single value,
	 * the return value is the value of that field.
	 *
	 * This function should only be used to retrieve a single record.
	 * If you want to search for multiple records, use stdList instead.
	 *
	 * If none or multiple records are found, a NotFoundException is thrown.
	 *
	 * Examples:
	 * \code
	 * stdGet("users", array("userId"=>123), "username");
	 * \endcode
	 * This call will return the user's username.
	 *
	 * \code
	 * $userData = stdGet("users", array("userId"=>123), array("username", "email"));
	 * \endcode
	 * Considered user 123 does exist, $userData["username"] will contain the user's username,
	 * and $userData["email"] will contain the user's email address.
	 *
	 * \see stdGetTry
	 * \see stdList
	 */
	public function stdGet($table, $where, $get)
	{
		$query = $this->buildSelect($get);
		$query .= $this->buildFrom($table);
		$query .= $this->buildWhere($where);
		
		$queryResult = $this->query($query);
		$rows = $queryResult->numRows();
		if($rows != 1) {
			$queryResult->free();
			throw new NotFoundException();
		}
		$result = $queryResult->fetchArray();
		$queryResult->free();
		if(is_array($get) || $get == "*") {
			return $result;
		} else {
			return $result[$get];
		}
	}
	
	/**
	 * \brief Try to get a single record from a single table using simple conditions
	 * \param $table The name of the table
	 * \param $where The search conditions
	 * \param $get The fields to get
	 * \param $default The value to return if zero or multiple records are found
	 *
	 * \return Either an associative array of fields to values or a single value, or \a $default if no objects were found
	 * \throws DatabaseException in case of a database connection error
	 *
	 * This function tries to get a single record in \a $table which matches the conditions specified in \a $where.
	 * \a $where is an associative array of fieldnames (in \a $table) to values.
	 * The record is retrieved whose \a $key field equals \a $value, for all (\a $key => \a $value) pairs in \a $where.
	 * \a $get can either be an array of fields to get or a single field. If it is an array,
	 * the return value is an associative array of fieldnames to values; if it is a single value,
	 * the return value is the value of that field.
	 *
	 * This function should only be used to retrieve a single record. 
	 * If you want to search for multiple records, use stdList instead.
	 *
	 * If none or multiple results are found, \a $default is returned.
	 *
	 * Examples:
	 * \code
	 * stdGetTry("users", array("userId"=>123), "username");
	 * \endcode
	 * Considered user 123 does exist, this call will return the user's username.
	 *
	 * \code
	 * $userData = stdGetTry("users", array("userId"=>123), array("username", "email"), null);
	 * \endcode
	 * Considered user 123 does exist, $userData["username"] will contain the user's username,
	 * and $userData["email"] will contain the user's email address. If user 123 does not exist,
	 * something has gone wrong and the page will terminate; presumably, the existance of user 123
	 * has been verified earlier on this page, so it is quite safe to assume it's still there.
	 *
	 * \see stdGet
	 * \see stdList
	 */
	public function stdGetTry($table, $where, $get, $default = null)
	{
		try {
			return $this->stdGet($table, $where, $get);
		} catch(NotFoundException $e) {
			return $default;
		}
	}
	
	/**
	 * \brief List a single table using simple conditions
	 * \param $table The name of the table
	 * \param $where The search conditions
	 * \param $get The fields to get
	 * \param $sort The fields to sort by
	 * \param $number The number of records to return
	 * \param $skip The number of records to skip
	 *
	 * \return Either an array of associative arrays of fields to values or an array of values
	 * \throws DatabaseException in case of a database connection error
	 *
	 * This function lists all records in \a $table matching the conditions specified in \a $where.
	 * \a $where is an associative array of fieldnames (in \a $table) to values.
	 * The records are listed whose \a $key field equals \a $value, for all (\a $key => \a $value) pairs in \a $where.
	 * \a $get can either be an array or fields to get or a single field. If it is an array,
	 * the return value is an array of associative arrays of fields to values; if it is a single value,
	 * the return value is an array of values.
	 * \a $sort is an associative array of fieldnames (in \a $table) to sort directions. The query is sorted
	 * by these fields, in order. The sort direction can be \c a or \c ASC for ascending, or \c d or \c DESC for
	 * descending. The sort direction is case insensitive.
	 *
	 * Examples:
	 * \code
	 * $list = stdList("users", array("groupId"=>3), array("userId", "username"), array("username"=>"asc"));
	 * foreach ($user in $list) {
	 *   echo "User id: " . $user["userId"] . "\n";
	 *   echo "Username: " . $user["username"] . "\n";
	 * }
	 * \endcode
	 * This code will output the userId and username of all users in group 3, ordered by username in ascending order.
	 */
	public function stdList($table, $where, $get, $sort = null, $number = 0, $skip = 0)
	{
		$query = $this->buildSelect($get);
		$query .= $this->buildFrom($table);
		$query .= $this->buildWhere($where);
		$query .= $this->buildSort($sort);
		$query .= $this->buildLimit($number, $skip);
		
		$qresult = $this->query($query);
		$result = $qresult->fetchList();
		$qresult->free();
		if(is_array($get) || $get == "*") {
			return $result;
		} else {
			$list = array();
			foreach($result as $item) {
				$list[] = $item[$get];
			}
			return $list;
		}
	}
	
	/**
	 * \brief List a single table using simple conditions
	 * \param $table The name of the table
	 * \param $where The search conditions
	 *
	 * \return The number of records found
	 * \throws DatabaseException in case of a database connection error
	 *
	 * This function counts all records in \a $table matching the conditions specified in \a $where.
	 * \a $where is an associative array of fieldnames (in \a $table) to values.
	 * The records are counter whose \a $key field equals \a $value, for all (\a $key => \a $value) pairs in \a $where.
	 */
	public function stdCount($table, $where)
	{
		$query = "SELECT COUNT(*) ";
		$query .= $this->buildFrom($table);
		$query .= $this->buildWhere($where);
		
		$qresult = $this->query($query);
		$result = $qresult->fetchArray();
		$qresult->free();
		return $result[0];
	}
	
	/**
	 * \brief Update fields in a single table using simple conditions
	 * \param $table The table to update
	 * \param $where The update conditions
	 * \param $set The fields to set and what to set them
	 *
	 * \return The number of affected records
	 * \throws DatabaseException in case of a database connection error
	 *
	 * This function updates all records in \a $table matching the conditions in \a $where.
	 * \a $where is an associative array of fieldnames (in \a $table) to values.
	 * The records are updated whose \a $key field equals \a $value, for all (\a $key => \a $value) pairs in \a $where.
	 * \a $set is an associative array of fieldnames (in \a $table) to values.
	 * The updated records have \a $key set to \a $value, for all (\a $key => \a $value) pairs in \a $set.
	 *
	 * Examples:
	 * \code
	 * stdSet("users", array("userId"=>123), array("password"=>"very_secret_password"));
	 * \endcode
	 * This call will set the password of all users with userId 123 (let's hope there's just a single one) to very_secret_password.
	 *
	 * \code
	 * stdSet("users", array("groupId"=>3), array("groupId"=>4));
	 * \endcode
	 * This call will move all the users in group 3 to group 4.
	 */
	public function stdSet($table, $where, $set)
	{
		$query = "UPDATE `" . $table . "` ";
		$query .= $this->buildSet($set);
		$query .= $this->buildWhere($where);
		
		return $this->setQuery($query);
	}
	
	/**
	 * \brief Insert a single new record into a table
	 * \param $table The table to insert into
	 * \param $set The fields to set and what to set them
	 *
	 * \return The insert ID of the inserted record
	 * \throws DatabaseException in case of a database connection error
	 *
	 * This function inserts a single record into \a $table, with values as specified in \a $set
	 * \a $set is an associative array of fieldnames (in \a $table) to values.
	 * The inserted records have \a $key set to \a $value, for all (\a $key => \a $value) pairs in \a $set.
	 *
	 * Examples:
	 * \code
	 * stdNew("users", array("username"=>"timmy", "password"=>"secret", "email"=>"timmy@provider.ext", "group"=>1));
	 * \endcode
	 * This call will insert the new user "timmy" into the database. Should this fail, because (for instance) there is
	 * already a user with the username timmy, false is returned.
	 */
	public function stdNew($table, $set)
	{
		if(!is_array($set) || !count($set)) {
			throw new AssertionFailedException("Bad \$set clause");
		}
		
		$query = "INSERT INTO `" . $table . "` ";
		$query .= $this->buildInsertFields(array_keys($set));
		$query .= "VALUES ";
		$query .= $this->buildInsertRecord($set);
		
		$this->setQuery($query);
		
		return $this->dbInsertID();
	}
	
	/**
	 * \brief Delete records from a single table using simple conditions
	 * \param $table The table to delete from
	 * \param $where The deletion conditions
	 *
	 * \return The number of deleted records
	 * \throws DatabaseException in case of a database connection error
	 *
	 * This function deletes all records in \a $table matching the conditions in \a $where.
	 * \a $where is an associative array of fieldnames (in \a $table) to values.
	 * The records are deleted whose \a $key field equals \a $value, for all (\a $key => \a $value) pairs in \a $where.
	 *
	 * Examples:
	 * \code
	 * stdDel("users", array("userId"=>123));
	 * \endcode
	 * This call will delete all users with userId 123 (let's hope it's just a single user).
	 */
	public function stdDel($table, $where)
	{
		$query = "DELETE FROM `" . $table . "` ";
		$query .= $this->buildWhere($where);
		
		return $this->setQuery($query);
	}
	
	public function stdIncrement($table, $where, $field)
	{
		$query = "UPDATE `" . $table . "` ";
		$query .= "SET `" . $field . "`=`" . $field . "` + 1 ";
		$query .= $this->buildWhere($where);
		
		return $this->setQuery($query);
	}
	
	public function buildSelect($get)
	{
		if(is_array($get) && !count($get)) {
			throw new AssertionFailedException("Bad \$get clause: empty array");
		}
		
		$output = "";
		if(is_array($get)) {
			reset($get);
			list($key, $value) = each($get);
			$output .= "SELECT `" . $value . "` ";
			while(list($key, $value) = each($get)) {
				$output .= ", `" . $value . "` ";
			}
		} else if($get == "*") {
			$output .= "SELECT * ";
		} else {
			$output .= "SELECT `" . $get . "` ";
		}
		return $output;
	}
	
	public function buildFrom($tables)
	{
		if(is_array($tables) && !count($tables)) {
			throw new AssertionFailedException("Bad \$tables clause: empty array");
		}
		
		$output = "";
		if(is_array($tables)) {
			reset($tables);
			list($key, $value) = each($tables);
			$output .= "FROM `" . $value . "` ";
			while(list($key, $value) = each($tables)) {
				$output .= ", `" . $value . "` ";
			}
		} else {
			$output .= "FROM `" . $tables . "` ";
		}
		return $output;
	}
	
	public function buildSet($set)
	{
		if(!is_array($set) || !count($set)) {
			throw new AssertionFailedException("Bad \$set clause: not an array or empty");
		}
		
		$output = "";
		reset($set);
		list($key, $value) = each($set);
		if($value === null) {
			$output .= "SET `" . $key . "`=NULL ";
		} else {
			$output .= "SET `" . $key . "`='" . $this->addSlashes($value) . "' ";
		}
		while(list($key, $value) = each($set)) {
			if($value === null) {
				$output .= ", `" . $key . "`=NULL ";
			} else {
				$output .= ", `" . $key . "`='" . $this->addSlashes($value) . "' ";
			}
		}
		return $output;
	}
	
	public function buildInsertFields($fields)
	{
		reset($fields);
		list($key, $value) = each($fields);
		$output = "(`" . $key . "`";
		while(list($key, $value) = each($fields)) {
			$output .= ", `" . $key . "`";
		}
		$output .= ") ";
		return $output;
	}
	
	public function buildInsertRecord($record)
	{
		$output = "";
		reset($record);
		list($key, $value) = each($record);
		if($value === null) {
			$output .= "(NULL ";
		} else {
			$output .= "('" . $this->addSlashes($value) . "' ";
		}
		while(list($key, $value) = each($record)) {
			if($value === null) {
				$output .= ", NULL ";
			} else {
				$output .= ", '" . $this->addSlashes($value) . "' ";
			}
		}
		$output .= ")";
		return $output;
	}
	
	public function buildWhere($where)
	{
		if($where === null) {
			return "";
		}
		if(!is_array($where)) {
			throw new AssertionFailedException("Bad \$where clause: not an array");
		}
		if(count($where) == 0) {
			return "";
		}
		
		$output = "";
		reset($where);
		list($key, $value) = each($where);
		if($value === null) {
			$output .= "WHERE `" . $key . "` IS NULL ";
		} else {
			$output .= "WHERE `" . $key . "`='" . $this->addSlashes($value) . "' ";
		}
		while(list($key, $value) = each($where)) {
			if($value === null) {
				$output .= "AND `" . $key . "` IS NULL ";
			} else {
				$output .= "AND `" . $key . "`='" . $this->addSlashes($value) . "' ";
			}
		}
		return $output;
	}
	
	public function buildSort($sort)
	{
		if($sort == null) {
			return "";
		}
		if(!is_array($sort)) {
			throw new AssertionFailedException("Bad \$sort clause: not null and not an array");
		}
		if(count($sort) == 0) {
			return "";
		}
		
		$output = "";
		reset($sort);
		list($key, $value) = each($sort);
		$output .= "ORDER BY `" . $key . "` ";
		if(!strcasecmp($value, "a") || !strcasecmp($value, "asc")) {
			$output .= "ASC ";
		} else if(!strcasecmp($value, "d") || !strcasecmp($value, "desc")) {
			$output .= "DESC ";
		} else {
			throw new AssertionFailedException("Bad sort order");
		}
		while(list($key, $value) = each($sort)) {
			$output .= ", `" . $key . "` ";
			if(!strcasecmp($value, "a") || !strcasecmp($value, "asc")) {
				$output .= "ASC ";
			} else if(!strcasecmp($value, "d") || !strcasecmp($value, "desc")) {
				$output .= "DESC ";
			} else {
				throw new AssertionFailedException("Bad sort order");
			}
		}
		return $output;
	}
	
	public function buildLimit($number, $skip)
	{
		if($number != 0 || $skip != 0) {
			return "LIMIT " . (int)$skip . ", " . (int)$number . " ";
		} else {
			return "";
		}
	}
	
	public function dbOpen($address, $username, $password, $dbname)
	{
		$this->connection = mysql_connect($address, $username, $password, true);
		if($this->connection === false) {
			throw new DatabaseException("Could not connect to the database");
		}
		if(!mysql_select_db($dbname, $this->connection)) {
			throw new DatabaseException("Could not select database");
		}
	}
	
	public function dbClose()
	{
		mysql_close($this->connection);
	}
	
	public function dbQuery($query)
	{
		$result = @mysql_query($query, $this->connection);
		if($result === false) {
			throw new DatabaseException("Query error: " . $this->dbError());
		}
		return $result;
	}
	
	public function dbError()
	{
		return mysql_error();
	}
	
	public function dbFree($result)
	{
		return mysql_free_result($result);
	}
	
	public function dbFetchArray($result)
	{
		return mysql_fetch_array($result);
	}
	
	public function dbFetchObject($result)
	{
		return mysql_fetch_object($result);
	}
	
	public function dbNumRows($result)
	{
		$rows = mysql_num_rows($result);
		if($rows === false) {
			throw new DatabaseException("Query result error");
		}
		return $rows;
	}
	
	public function dbAffectedRows()
	{
		$rows = mysql_affected_rows($this->connection);
		if($rows === false) {
			throw new DatabaseException("Query result error");
		}
		return $rows;
	}
	
	public function dbInsertID()
	{
		return mysql_insert_id($this->connection);
	}
}

class QueryResult {
	private $connection;
	private $result;
	
	public function __construct($connection, $result)
	{
		$this->connection = $connection;
		$this->result = $result;
	}
	
	/**
	 * \brief Free this query result
	 *
	 * \return void
	 */
	public function free()
	{
		$this->connection->dbFree($this->result);
	}
	
	/**
	 * \brief Get the next record from this query result in array format
	 *
	 * \return An associative array of field names to values, containing the next record of \a $result
	 *
	 * \see fetchObject
	 */
	public function fetchArray()
	{
		return $this->connection->dbFetchArray($this->result);
	}
	
	/**
	 * \brief Get the next record from this query result in object format
	 *
	 * \return An object of properties to values, containing the next record of \a $result
	 *
	 * \see fetchArray
	 */
	public function fetchObject()
	{
		return $this->connection->dbFetchObject($this->result);
	}
	
	/**
	 * \brief Get a list of all records from this query result
	 *
	 * \return An array of associative arrays of field names to values, each containing a single record of \a $result
	 *
	 * This function only retrieves the records which were not previously retrieved using fetchArray of fetchRecord.
	 *
	 * \see fetchArray
	 */
	public function fetchList()
	{
		$list = array();
		while($record = $this->fetchArray()) {
			$list[] = $record;
		}
		return $list;
	}
	
	/**
	 * \brief Get the number of returned records from a this query result
	 *
	 * \return The number of records returned by the query
	 */
	public function numRows()
	{
		return $this->connection->dbNumRows($this->result);
	}
}

?>