<?php
class commands_migration_Migrations {
    
    /**
     * Platform - which platform to run migrations on
     */
    private $platform = "";

    /**
     * db - database connection
     */
    private $db = null;

    private $config = null;

    function __construct() {
        //$this->db = new Database("mysql:host=localhost;dbname=mapper", "mapper", "mapper");
        //echo "\nCalling api_database::factory()\n";
        $this->db = api_database::factory();
        //echo "\nIn commands_migration_migrations - constructor\n";
        //print_r( $this->db );
        $this->platform = "mysql";

        // get database config
        $this->config = api_config::getInstance()->database;
        $this->config = $this->config['default'];
        //print_r($this->config);
    }
   
    /**
     * Get Default Limit
     *
     * @param type
     */
    private function getDefaultLimit( $type ) {
        $default_limit = "";
        switch ( $type ) {

			case 'decimal': $default_limit = "(10,0)"; break;
            case 'integer': 
                if ($this->config['driver'] == "postgres") {
                    $default_limit = '';
                } else {
                    $default_limit = "(11)";
                }
                break;
			case 'string':  $default_limit = "(255)"; break;
			case 'binary':  $default_limit = "(1)"; break;
			case 'boolean': $default_limit = "(1)"; break;
			default: $default_limit = "";

		}
        return $default_limit;

    }

    /**
     * Get Datatype
     *
     * @param type
     */
    private function getDatatypes( $type ) {
        //print_r($this->config);
        $new_type = "";
        $database = $this->config['driver'];

        switch ( $type ) {
        case 'integer': 
            if ($database == 'postgres') {
                $new_type = "integer";
            } else {
                $new_type = "INT";
            } 
            break;
        
        case 'timestamp': 
            if ($database == 'postgres') {
                $new_type = "timestamp without time zone";
            } else {
                $new_type = "datetime";
            } 
            break;

        case 'string': 
            if ($database == 'postgres') {
                $new_type = "character varying";
            } else {
                $new_type = "VARCHAR";
            }
            break;
        case 'boolean': $new_type = "TINYINT"; break;
        
        case 'text': $new_type = "TEXT"; break;
        }
        return $new_type;
    }

    private function quote($name) {
        return $this->db->quote($name);
    }

// ------------------------------------------------------------------------

/**
 * Create Table
 *
 * Creates a new table
 *
 * $table_name:
 *
 * 		Name of the table to be created
 *
 * $fields:
 *
 * 		Associative array containing the name of the field as a key and the
 * 		value could be either a string indicating the type of the field, or an
 * 		array containing the field type at the first position and any optional
 * 		arguments the field might require in the remaining positions.
 * 		Refer to the TYPES function for valid type arguments.
 * 		Refer to the FIELD_ARGUMENTS function for valid optional arguments for a
 * 		field.
 *
 * $primary_keys:
 *
 * 		A string indicating the name of the field to be set as a unique primary
 * 		key or an array listing the fields to be set as a combined key.
 * 		If a field is selected as a primary key and its type is integer, it
 * 		will be set as an incremental field (auto_increment in mysql, serial in
 * 		postgre, etc).
 *
 * @example
 *
 *		create_table (
 * 			'blog',
 * 			array (
 * 				'id' => array ( 'integer' ),
 * 				'title' => array ( 'string', LIMIT, 50, DEFAULT, "The blog's title." ),
 * 				'date' => DATE,
 * 				'content' => TEXT
 * 			),
 * 			'id'
 * 		)
 *
 * @access	public
 * @param	string $table_name
 * @param	array $fields
 * @param	mixed $primary_keys
 * @return	boolean
 */ 
    protected function create_table( $table_name, $fields, $primary_keys = false ) {
        //echo "In create table!";
        //$platform = "mysql";
        switch ( $this->platform ) {
        case 'mysql':
            if ( !empty($primary_keys) ) $primary_keys = (array)$primary_keys;
			$sql = "CREATE TABLE {$table_name} (";

			foreach ( $fields as $field_name => $params ) {

				$params = (array)$params;

				// Get the default Limit
                $default_limit = $this->getDefaultLimit( $params[0] );
				
                // Convert to mysql datatypes
                $params[0] = $this->getDatatypes( $params[0] );

                $field_name = $field_name;
                //print_r($field_name);

                if (in_array($field_name,$primary_keys,true) && $params[0] == 'integer') {
                    $sql .= "{$field_name} ";
                    $sql .=  "SERIAL ";
                } else {
                    $sql .= "{$field_name} {$params[0]}";
				    $sql .= in_array('LIMIT',$params,true) ? "(" . $params[array_search('LIMIT',$params,true) + 1] . ") " : $default_limit . " ";
				    $sql .= in_array('DEFAULT_VALUE',$params,true) ? "default " . $CI->db->escape($params[array_search('DEFAULT_VALUE',$params,true) + 1]) . " " : "";
                    $sql .= in_array('NOT_NULL',$params,true) ? "NOT NULL " : "";
                }
				$sql .= ",";

			}

			$sql = rtrim($sql,',');

			if ( !empty($primary_keys) ) {

				$sql .= ",PRIMARY KEY (";

				foreach ( $primary_keys as $pk ) {
					$sql .= "{$pk},";
				}

				$sql = rtrim($sql,',');
				$sql .= ")";

			}

			$sql .= ")";

            // use InnoDB as default
            //$sql .= ' ENGINE=InnoDB';

		break;
    }

    // Execute query
//    echo "\nSQL: "; 
//    echo $sql;
//    echo "\n";
//    print_r( $this->db );
    
    if ($result = $this->db->query( $sql )) {
//        print_r( $result );
    } else {
//        echo "ErrorInfo";
        $error = $this->db->errorInfo();
        throw new Exception ($error[2]);
    }
}
    
// ----------------------------------------------------------------
/**
 * Rename a table
 *
 * @access public
 * @param string $old_name
 * @param string $new_name
 * @return boolean
 */
protected function rename_table($old_name, $new_name) {

	//$db =& _get_instance_w_dbutil();

	switch ( $this->platform ) {

		case 'mysql':
		default:

			$sql = "RENAME TABLE `{$old_name}`  TO `{$new_name}` ;";
			break;

	}
    
    echo "\nSQL: $sql\n";
	return $this->db->query($sql);

}
// ------------------------------------------------------------------------

/**
 * Drop a table
 *
 * @param string $table_name
 * @return boolean
 */
protected function drop_table($table_name) {

	return $this->db->query("DROP TABLE {$table_name}");

}

// ------------------------------------------------------------------------

    /**
    * Add a column to a table
    *
    * @example add_column ( "the_table", "the_field", 'string', array(LIMIT, 25, NOT_NULL) );
    * @access public
    * @param string $table_name
    * @param string $column_name
    * @param string $type
    * @param array $arguments
    * @return boolean
    */
    protected function add_column($table_name,$column_name,$type,$arguments=array()) {
	    //$CI =& _get_instance_w_dbutil();

	    switch ( $this->platform ) {

		    case 'mysql':
		    default:

                $default_limit = $this->getDefaultLimit($type);
                $type = $this->getDatatypes($type);
			    $sql = "ALTER TABLE {$table_name} ADD {$column_name} {$type}";

			    // Get the default Limit
                /*
			    switch ( $type ) {

				    case 'decimal': $default_limit = "(10,0)"; break;
				    case 'integer': $default_limit = "(11)"; break;
				    case 'string':  $default_limit = "(255)"; break;
				    case 'binary':  $default_limit = "(1)"; break;
				    case 'boolean': $default_limit = "(1)"; break;
				    default: $default_limit = "";

			    }
                 */
			    $sql .= in_array('LIMIT',$arguments,true) ? "(" . $arguments[array_search(LIMIT,$arguments,true) + 1] . ") " : $default_limit . " ";
			    $sql .= in_array('DEFAULT_VALUE',$arguments,true) ? "default " . $CI->db->escape($arguments[array_search(DEFAULT_VALUE,$arguments,true) + 1]) . " " : "";
			    $sql .= in_array('NOT_NULL',$arguments,true) ? "NOT NULL " : "NULL ";
			    break;

	        }
echo $sql."\n";
	        return $this->db->query($sql);

        }

// ------------------------------------------------------------------------

    /**
    * Rename a column
    *
    * @access public
    * @param string $table_name
    * @param string $column_name
    * @param string $new_column_name
    */
    protected function rename_column($table_name, $column_name, $new_column_name) {

	    // TO DO

    }

    // ------------------------------------------------------------------------

    protected function change_column($table_name, $column_name, $type, $options) {

	    // TO DO

    }

    // ------------------------------------------------------------------------

    /**
    * Remove a column from a table
    *
    * @access public
    * @param string $table_name
    * @param string $column_name
    * @return boolean
    */
    protected function remove_column($table_name, $column_name) {

	    return $this->db->query("ALTER TABLE {$table_name} DROP COLUMN {$column_name}");

    }

// ------------------------------------------------------------------------

    protected function add_index($table_name, $column_name, $index_type) {

	    // TO DO

    }

// ------------------------------------------------------------------------

    protected function remove_index($table_name, $column_name) {

	    // TO DO

    }
}
