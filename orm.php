<?php

/**
 *	Class MySql
 *
 *	@author Damjan Krstevski
 **/
class MySql {
	private static $instance;

	protected $pdo;

	private $hostname 	= 'localhost';
	private $username 	= 'root';
	private $password 	= 'toor';
	private $database 	= 'employees';
	private $driver 	= 'mysql';
	private $timeout 	= 30;
	private $charset 	= '';

	protected $queries = [];



	/**
	 *	Object constructor
	 *
	 *	@since 1.0.0
	 *	@access private
	 *
	 *	@return void
	 **/
	private function __construct() {
		$dns = sprintf(
			'%s:host=%s;dbname=%s',
			$this->driver,
			$this->hostname,
			$this->database
		);

		$this->pdo = new PDO(
			$dns,
			$this->username,
			$this->password,
			[PDO::ATTR_TIMEOUT => $this->timeout]
		);
	} // function __consstruct;



	/**
	 *	Object destructor
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@return void
	 **/
	public function __destruct() {
		$this->pdo = null;
	} // End function __destruct();



	/**
	 *	Method to connect with the database
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@return MySql reference
	 **/
	final public static function connect() {
		if (self::$instance === null) {
			self::$instance = new MySql();
		}

		return self::$instance;
	} // End function connect();



	/**
	 *	Method to get the queries
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@return Array All database queries
	 **/
	public function getQueries() {
		return $this->queries;
	} // End of function getQueries();



	/**
	 *	Method to call PDO property function
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@param string $meethod Function to call
	 *	@param string $args Arguments to pass
	 *
	 *	@return PDO response
	 **/
	public function __call( $method, $args ) {
		if ( !empty($this->pdo) && is_callable(array($this->pdo, $method)) ) {
			$runtimeStart = ($method === 'query') ? microtime(true) : 0;
			$callResponse = call_user_func_array(array($this->pdo, $method), $args);
			$runtimeEnd = ($method === 'query') ? microtime(true) : 0;

			if ($method === 'query') {
				$this->queryies[] = [
					'query' 	=> $args[0],
					'runtime' 	=> $runtimeEnd - $runtimeStart
				];
			}

			return $callResponse;
		}
	} // End function __call();



	/**
	 *	Method to stop the cloning
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@return boolean False
	 **/
	public function __clone() {
		return false;
	} // End function __clone();



	/**
	 *	Method to stop the wakeup
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@return boolean False
	 **/
	public function __wakeup() {
		return false;
	} // End function __wakeup();

} // Class MySql;





/**
 *	Class ModelORM
 *
 *	@author Damjan Krstevski
 **/
class ModelORM {

	/**
	 *	Name of the table
	 *
	 *	@since 1.0.0
	 *	@access protected
	 *
	 *	@var string $table
	 **/
	protected $table;

	/**
	 *	PDO Object
	 *
	 *	@since 1.0.0
	 *	@access protected
	 *
	 *	@var reference $pdo
	 *	@see http://php.net/manual/en/book.pdo.php
	 **/
	protected $pdo;

	/**
	 *	Default fetch mode
	 *
	 *	@since 1.0.0
	 *	@access private
	 *
	 *	@var int $fetchMode
	 *	@see http://php.net/manual/en/pdostatement.setfetchmode.php
	 **/
	private $fetchMode = PDO::FETCH_OBJ;

	/**
	 *	Where clause
	 *
	 *	@since 1.0.0
	 *	@access private
	 *
	 *	@var string $where
	 **/
	private $where 	= '';

	/**
	 *	Columns to select
	 *
	 *	@since 1.0.0
	 *	@access private
	 *
	 *	@var string $fields
	 **/
	private $fields = '*';

	/**
	 *	Order results
	 *
	 *	@since 1.0.0
	 *	@access private
	 *
	 *	@var string $orderBy
	 **/
	private $orderBy = '';

	/**
	 *	Limit the results
	 *
	 *	@since 1.0.0
	 *	@access private
	 *
	 *	@var string $limit
	 **/
	private $limit 	= '';

	/**
	 *	Group the results
	 *
	 *	@since 1.0.0
	 *	@access private
	 *
	 *	@var string $groupBy
	 **/
	private $groupBy = '';



	/**
	 *	Methot to Executes an SQL statement,
	 *	returning a result set as a PDOStatement object
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@return mixed PDOStatement object
	 **/
	public function get() {
		$this->queryInit();

		// Build the SQL query
		$query = 'SELECT ' . $this->fields . ' FROM ' . $this->table
		. (($this->where) ? ' WHERE ' 		. $this->where : '')
		. (($this->groupBy) ? ' GROUP BY ' 	. $this->groupBy : '')
		. (($this->limit) ? ' LIMIT ' 		. $this->limit : '')
		. (($this->orderBy) ? ' ORDER BY ' 	. $this->orderBy : '');

		// Reset SQL query
		$this->resetQuery();

		return $this->query($query, $this->fetchMode)->fetchAll();
	} // End of function get();



	/**
	 *	Method to init the ORM
	 *
	 *	@since 1.0.0
	 *	@access private
	 *
	 *	@return void
	 **/
	private function queryInit() {
		// Create reference of MySql class
		if (!$this->pdo) {
			$this->pdo = MySql::connect();
		}

		// Check the table or set the parent class name as table name
		if (!$this->table) {
			$this->table = get_called_class();
		}
	} // End of function queryInit();



	/**
	 *	Method to reset the query
	 *
	 *	@since 1.0.0
	 *	@access private
	 *
	 *	@return void
	 **/
	private function resetQuery() {
		$this->table 	= '';
		$this->where 	= '';
		$this->fields 	= '*';
		$this->orderBy 	= '';
		$this->limit 	= '';
		$this->groupBy 	= '';
	} // End of function resetQuery();



	/**
	 *	Method to set the where clause
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@param string 	$column Table column name
	 *	@param string 	$operator Compare operator
	 *	@param string 	$value Value to compare
	 *	@param string 	$soft OR ot AND
	 *
	 *	@return ModelORM
	 **/
	public function where( $column = '', $operator = '', $value = '', $soft = 'OR' ) {
		// Set quotes for strings
		$value = is_numeric($value) ? $value : sprintf("'%s'", $value);

		// Append the where clause
		$this->where .= ($this->where && $soft) ? " $soft " : '';
		$this->where .= sprintf('%s %s %s', $column, $operator, $value);

		return $this;
	} // End of function where();



	/**
	 *	Method to set the table
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@param string $table Name of the table
	 *
	 *	@return ModelORM
	 **/
	public function table( $table = '' ) {
		$this->table = $table;
		return $this;
	} // End of function table();



	/**
	 *	Methot to set the select columns
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@param mixed 	$fields The columns to select
	 *
	 *	@return ModelORM
	 **/
	public function select( $fields = '*' ) {
		$this->fields = is_array($fields) ? implode(', ', $fields) : $fields;
		return $this;
	} // End of function select();



	/**
	 *	Method to limit the results
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@param int 	$limit Offset or results count
	 *	@param int 	$length Results count
	 *
	 *	@return ModelORM
	 **/
	public function limit( $limit = 0, $length = 0 ) {
		// If is set length than limit var will be offset
		$this->limit = $length ? $limit . ', ' . $length : $limit;
		return $this;
	} // End of function limit();



	/**
	 *	Method to order the results
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@param string 	$column Table column
	 *	@param string 	$order Type ASC or DESC
	 *
	 *	@return ModelORM
	 **/
	public function orderBy( $column = '', $order = 'ASC' ) {
		$this->orderBy = sprintf('%s %s', $column, $order);
		return $this;
	} // End of function orderBy();



	/**
	 *	Method to group the results
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@param string 	$column Table column
	 *
	 *	@return ModelORM
	 **/
	public function groupBy( $column = '' ) {
		$this->groupBy = sprintf('%s', $column);
		return $this;
	} // End of function groupBy();



	/**
	 *	Method to allow using PDO functions.
	 *
	 *	@since 1.0.0
	 *	@access public
	 *
	 *	@param string 	$method Name of the method to call
	 *	@param mixed 	$args Function arguments
	 *
	 *	@return mixed
	 **/
	public function __call( $method, $args ) {
		if ( !empty($this->pdo) && is_callable(array($this->pdo, $method)) ) {
			return call_user_func_array(array($this->pdo, $method), $args);
		}
	} // End function __call();

} // End class ModelORM();





/*
// Into MySQL create table 'users'
// Create class with MySQL table name and ORM is ready to use
class Employees extends ModelORM {}

$employee = new Employees();
// $employee2 = new Employees();

var_dump( $employee->table('employees')->where('birth_date', '>', '1960-0-0')->groupBy('emp_no')->limit(0, 10)->get() );
echo '<hr />';

$employee->table('employees')->select('emp_no')->limit(5)->get();

$pdodb = MySql::connect();
var_dump( $employee );
var_dump( '-----------------------' );
var_dump( $pdodb );
*/

?>