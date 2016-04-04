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
	private $password 	= '';
	private $database 	= 'performances';
	private $driver 	= 'mysql';
	private $timeout 	= 30;
	private $charset 	= '';

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

	public function __destruct() {
		$this->pdo = null;
	} // End function __destruct();

	final public static function connect() {
		if (self::$instance === null) {
			self::$instance = new MySql();
		}

		return self::$instance;
	} // End function connect();

	public function __call( $method, $args ) {
		if ( !empty($this->pdo) && is_callable(array($this->pdo, $method)) ) {
			return call_user_func_array(array($this->pdo, $method), $args);
		}
	} // End function __call();

	public function __clone() {
		return false;
	} // End function __clone();

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
	 *	@var string @where
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
	 *	@var string $order
	 **/
	private $order 	= '';

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

		$query = 'SELECT ' . $this->fields . ' FROM ' . $this->table
		. (($this->where) ? ' WHERE ' 		. $this->where : '')
		. (($this->limit) ? ' LIMIT ' 		. $this->limit : '')
		. (($this->order) ? ' ORDER BY ' 	. $this->order : '');

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
		$this->where 	= '';
		$this->fields 	= '*';
		$this->order 	= '';
		$this->limit 	= '';
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
	public function order( $column = '', $order = 'ASC' ) {
		$this->order = sprintf('%s %s', $column, $order);
		return $this;
	} // End of function order();



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












// Into MySQL create table 'users'
// Create class with MySQL table name and ORM is ready to use
class Users extends ModelORM {}

// Create instance of your database table
// You have only one instance of the class because the ORM use Singleton Pattern
$user = new Users();
$user1 = new Users();

// Use the ORM
$users = $user->where('first_name', 'like', 'J%')->limit(5)->get();
$users2 = $user->where('first_name', 'like', 'J%')->limit(5)->get();

?>