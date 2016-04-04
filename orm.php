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

	protected $table;
	protected $pdo;

	private $fetchMode = PDO::FETCH_OBJ;

	private $where 	= '';
	private $fields = '*';
	private $order 	= '';
	private $limit 	= '';


	public function get() {
		$this->queryInit();

		$query = 'SELECT ' . $this->fields . ' FROM ' . $this->table
		. (($this->where) ? ' WHERE ' 		. $this->where : '')
		. (($this->limit) ? ' LIMIT ' 		. $this->limit : '')
		. (($this->order) ? ' ORDER BY ' 	. $this->order : '');

		$this->resetQuery();

		return $this->query($query, $this->fetchMode)->fetchAll();
	}

	private function queryInit() {
		if (!$this->pdo) {
			// parent::__construct();
			$this->pdo = MySql::connect();
		}

		if (!$this->table) {
			$this->table = get_called_class();
		}
	}

	private function resetQuery() {
		$this->where 	= '';
		$this->fields 	= '*';
		$this->order 	= '';
		$this->limit 	= '';
	}

	public function where( $column = '', $operator = '', $value = '', $soft = 'OR' ) {
		$value = is_numeric($value) ? $value : sprintf("'%s'", $value);

		$this->where .= ($this->where && $soft) ? " $soft " : '';
		$this->where .= sprintf('%s %s %s', $column, $operator, $value);

		return $this;
	}

	public function select( $fields = '*' ) {
		$this->fields = $fields;
		return $this;
	}

	public function limit( $limit = 0, $length = 0 ) {
		$this->limit = $length ? $limit . ', ' . $length : $limit;
		return $this;
	}

	public function order( $column = '', $order = 'ASC' ) {
		$this->order = sprintf('%s %s', $column, $order);
		return $this;
	}

	public function __call( $method, $args ) {
		if ( !empty($this->pdo) && is_callable(array($this->pdo, $method)) ) {
			return call_user_func_array(array($this->pdo, $method), $args);
		}
	} // End function __call();

} // End class ModelORM();













class Users extends ModelORM {}

$user = new Users();
$user1 = new Users();

$users = $user->where('first_name', 'like', 'J%')->limit(5)->get();
$users2 = $user->where('first_name', 'like', 'J%')->limit(5)->get();
$users3 = $user1->where('first_name', 'like', 'J%')->limit(5)->get();


?>



<!DOCTYPE html>
<html>
	<head>
		<title>ORM</title>
		<style type="text/css">
		body { font-size: 14px; line-height: normal; font-family: Arial; }
		.wrap { max-width: 1000px; margin: 25px auto; float: none; }
		* { padding: 0; margin: 0; }
		p { margin-bottom: 10px; font-weight: bold; }
		.hidden { display: block; margin-bottom: 30px; }
		</style>
	</head>

	<body>
		<div class="wrap">
		<?php if ($users):
			$counter = 1;
			foreach ($users as $u):
				printf('<p>%d. %s %s</p>', $counter++, $u->first_name, $u->last_name); ?>
				<div class="hidden"><?php var_dump($u); ?></div>
				<?php
			endforeach;
		endif; ?>
		</div>
	</body>
</html>