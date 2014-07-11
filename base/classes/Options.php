<?php

/**
 * Options class.
 */
class Options {


	/**
	 * options
	 *
	 * @var mixed
	 * @access private
	 */
	private $options;


	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $array
	 * @return void
	 */
	public function __construct($array) {

		$this->options = $array;

	}


	/**
	 * getAllOptions function.
	 *
	 * @access public
	 * @return void
	 */
	public function getAllOptions() {

		$buildarray = array();

		foreach($this->options as $object) {

			$buildarray[$object['opt_name']] = stripslashes(html_entity_decode($object->opt_value));

		}

		return $buildarray;

	}


	/**
	 * getOption function.
	 *
	 * @access public
	 * @param mixed $opt_name
	 * @param string $opt_val (default: '')
	 * @return void
	 */
	function getOption($opt_name, $opt_val='') {

		foreach($this->options as $option) {

			if(array_search($opt_name, (array) $option)) {

		    	return $this->{$opt_name} = stripslashes(html_entity_decode($option->opt_value));

		    }

		}

	}


	/**
	 * setOption function.
	 *
	 * @access public
	 * @param mixed $opt_name
	 * @param mixed $opt_val
	 * @return void
	 */
	function setOption($opt_name, $opt_val) {

		$database = new Database();

		$database->query("INSERT INTO options (`user_id`, `opt_name`, `opt_value`) VALUES (:user_id, :option, :value) ON DUPLICATE KEY UPDATE opt_value = :value");
		$database->bind(":user_id", (int) $this->options[0]['user_id']);
		$database->bind(":option", $opt_name);
		$database->bind(":value", $opt_val);
		$database->execute();

	}


	/**
	 * deleteOption function.
	 *
	 * @access public
	 * @param mixed $opt_name
	 * @param string $opt_val (default: '')
	 * @return void
	 */
	function deleteOption($opt_name, $opt_val='') {

		$database = new Database();

		if(!empty($opt_val)) {

			$database->query("SELECT * FROM options WHERE user_id = :user_id AND opt_name = :opt_name AND opt_value = :opt_val");
			$database->bind(":user_id", (int) $this->options[0]['user_id']);
			$database->bind(":opt_name", $opt_name);
			$database->bind(":opt_val", $opt_val);
			$database->execute();

			$option = $database->resultset();

		} else {

			$database->query("SELECT * FROM options WHERE user_id = :user_id AND opt_name = :opt_name");
			$database->bind(":user_id", (int) $this->options[0]['user_id']);
			$database->bind(":opt_name", $opt_name);
			$database->execute();

			$option = $database->resultset();

		}

		foreach($option as $op) {

			$database->query("DELETE FROM options WHERE user_id = :user_id AND id = :option_id");
			$database->bind(":user_id", $op['user_id']);
			$database->bind(":option_id", $op['id']);
			$database->execute();

		}

	}

}