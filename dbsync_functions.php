<?php

//global datatype arrays
$text_types = array('char','varchar','tinytext','text','blob','mediumtext','mediumblob','longtext','longblob','enum','set');
$num_types = array('tinyint','smallint','mediumint','int','bigint','float','double','decimal');
$date_types = array('date', 'datetime', 'timestamp','time','year');

/** create_connection($credentials)
 * Creates and returns a MySQL connection as $con by passing in an array of the database
 * credentials.
 *
 * $credentials = array([server name], [username], [password], [database name])
 */
function create_connection($credentials) {
	$con = call_user_func_array('mysqli_connect', $credentials);
	if (mysqli_connect_errno($con)) {
  		echo "Failed to connect to MySQL: " . mysqli_connect_error()."<br>";
	}
	return $con;  		
}

//-------------FETCH FUNCTIONS-------------------//

function fetch_databases($credentials) {
	$databases = array();
	$con = create_connection($credentials);
	$result = mysqli_query($con, "SHOW DATABASES");
	while($db = mysqli_fetch_array($result, MYSQLI_NUM)) {
    	$databases[] = "$db[0]";
    }
    mysqli_close($con);
    return $databases;
}    
    
/** fetch_tables($credentials)
 * Returns array of all tables from a given database
 *
 * $credentials = array([server name], [username], [password], [database name]) 
 
 * Dependant on following functions:
 		-create_connection()
 */
function fetch_tables($credentials) {
	$tables = array();
	$con = create_connection($credentials);  
	$database = $credentials[3];
	$result = mysqli_query($con, "SHOW TABLES FROM $database");
	while($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
    	$tables[] = "$row[0]";
    }
    mysqli_close($con);
    return $tables;
}

/** fetch_columns($table, $con)
 * Returns array of all column names and their data types in a given table.
 *
 * $table = string of table's name
 * $con = database's pre-establised connection
 */
function fetch_columns($table, $con) {
	$columns = array();
	$result = mysqli_query($con, "SHOW COLUMNS FROM $table");
	while($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
		array_push($columns, $row[0].' '.$row[1]);	
	}
	return $columns;
}

/** fetch_column_name($column)
 * Returns the name of given column
 *
 * $column = string of column name and datatype separated by a space
 * 		(formatted by function fetch_columns())
 */
function get_column_name($column) {
	$array = explode(" ", $column);
	$name = $array[0];
	return $name;	
}

/** fetch_column_datatype($column)
 * Returns the datatype of given column
 *
 * $column = string of column name and datatype separated by a space
 * 		(formatted by function fetch_columns())
 */
function get_column_datatype($column) {
	$array = explode(" ", $column);
	$datatype = $array[1];
	return $datatype;
}

/** get_column_names($columns)
 * Returns array of just the column names
 *
 * $columns = array of strings each featuring a column name
 * and datatype separated by a space.
 *
 * Dependant on following functions:
 * 		-get_column_name()
 */
function get_column_names($columns) {
	$column_names = array();
	foreach($columns as $column) {
		$column_name = get_column_name($column);
		array_push($column_names, $column_name);
	}
	return $column_names;
}

/** get_column_datatypes($columns)
 * Returns an associative array of the column's datatype
 * with the column's name as it's key.
 *
 * $columns = array of strings each featuring a column name
 * and datatype separated by a space.
 *
 * Dependant on following functions:
 * 		-get_column_name()
 * 		-get_column_datatype()
 */
function get_column_datatypes($columns) {
	$column_datatypes = array();
	foreach($columns as $column) {
		$column_name = get_column_name($column);
		$column_datatype = get_column_datatype($column);
		$column_datatypes[$column_name] = $column_datatype;
	}
	return $column_datatypes;
}
	
/** fetch_data($table, $con, $columns = '*')
 * Returns two-dimensional array of all data in the given columns of the given table
 *
 * $table = string of table's name
 * $con = database's pre-established connection
 * $columns = string of column names separated by comma
 * 		defaults to '*' which selects all columns in table
 */
function fetch_data($table, $con, $column_names = '*') {
	$data = array();
	$result = mysqli_query($con, "SELECT $column_names FROM ".$table);
	while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		array_push($data, $row);
	}
	return $data;
}

//------------------END FETCH FUNCTIONS------------------//

//---------DISPLAY FUNCTIONS-------------------//
 
/** print_array($array)
 * Prints all the elecments of a given array
 *
 * $array = array to be printed
 */
function print_array($array) {
	foreach ($array as $element) {
		echo "  $element <br>";
	}
	echo "\n<br>";
}

/** print_keys($array)
 * Prints all keys of given associative array
 *
 * $array = associative array 
 */
function print_keys($array) {
	foreach ($array as $element) {
		echo "  ".key($array)." ";
		next($array);
	}
}

//----------END DISPLAY FUNCTIONS--------------//

//-------------FORMAT FUNCTIONS----------------//

/** format_columns($columns)
 * Returns string of column names and their datatypes
 * suitable for use in MySQL queries
 * i.e. 'column1 datatype, column2 datatype...'
 * NOTE: parentheses must be added separately
 *
 * $columns = an array of columns and their datatypes 
 */
function format_columns($columns) {
	$n = count($columns);
	$formatted_columns = '';
	foreach ($columns as $column) {
		--$n;
		$formatted_columns .= $column;
		if ($n != 0) {
			$formatted_columns .= ', ';
		}		
	}
	return $formatted_columns;
}

/** format_row($row)
 * Returns string of data suitable for use in MySQL queries
 * i.e. 'element1, element2, element3....'
 * NOTE: parentheses must be added separately
 *
 * $row = array of row elments formatted by datatype
 */
function format_row($row) {
	$n = count($row);
	$formatted_row = '';
	foreach ($row as $element) {
		--$n;
		$formatted_row .= $element;
		if ($n != 0) {
			$formatted_row .= ', ';
		}
	}
	return $formatted_row;
}

/** format_element($element)
 * Returns formatted data element based on datatype for use in MySQL queries.
 *
 * $element = a string representing an element of data
 * $column_name = a string representing the name of $element's
 * column.
 * 
 */
function format_element($element, $column_name, $datatype) {
	$formatted_datatype = trim(preg_replace("/\([^)]+\)/", "", $datatype));
	global $text_types;
	if (in_array($formatted_datatype, $text_types)) {
		$element = "'".$element."'";
	}
	return $element;
}

//------------END FORMAT FUNCTIONS------------//

//-----------ALTER DATABSE FUNCTIONS----------//

/** create_table($tablename, $con, $columns)
 * Creates a MySQL table.
 *
 * $tablename = a string containing desired name of new table
 * $con = a database connection
 * $columns = a string representing the parameters of the table's columns 
 * 		NOTE: required format = '(column1, datatype, column2, datatype...)'
 */
function create_table($tablename, $con, $columns) {
	$sql='CREATE TABLE '.$tablename.$columns;
	if (mysqli_query($con,$sql)) {
	}
	else {
		echo "$sql \n";
		echo "$columns \n";
  		echo "Unable to create $tablename \n";
  		
  	}	
}

/** add_columns($table, $columns, $con)
 * Creates columns in MySQL table given array of column names and datatype, database
 * connection, and table name
 *
 * $table = string of tablename to which columns are to be added
 * $columns = array of columns to be added
 * $con = connection of respective database
 *
 */
function add_columns($table, $columns, $con) {
	foreach ($columns as $column) {
		$sql = "ALTER TABLE ".$table." ADD ".$column;
		echo $sql."\n";
		mysqli_query($con, $sql);
	}
}

/** add_column($table, $column, $con, $prev_col)
 * Inserts new column after specific existing column
 *
 * $table = string of tablename
 * $column = string of name of column to be inserted
 * $con = established database connection
 * $prev_col = a string of the column name immediate preceding
 * the column to be added
 */
function insert_column($table, $column, $con, $prev_col) {
	$prev_col_name = get_column_name($prev_col);
	$sql = "ALTER TABLE ".$table." ADD ".$column;
	if ($prev_col != '') {
		$sql = "ALTER TABLE ".$table." ADD ".$column." AFTER ".$prev_col_name;
	}
	mysqli_query($con, $sql);
}

/** insert_data($data, $table, $con)
 * Inserts data into given table
 * 
 * $formatted_data = string of data to be inserted
 * $table = string of the table name receiving data
 * $con = connection of respective database
 */
function insert_data($formatted_data, $table, $con, $formatted_columns = '') {
	$query = 'INSERT INTO '.$table.''.$formatted_columns.' VALUES '.$formatted_data;
	//echo "\n$query\n";
	mysqli_query($con, $query);
}

/** update_row($db1_row, $db2_row, $column_datatypes, $table, $con)
 * Updates given row with new data
 *
 * $db1_data = represents db1_data as a two-dimensional 
 *	array consisting of an associative array
 * 	of each row inside another array
 * $db2_data = represents db2_data as a two-dimensional 
 *	array consisting of an associative array
 * 	of each row inside another array
 * $column_datatypes = an associative array of string values
 *  representing each column's datatype with the respective
 *  column name as it's key.
 * $table = string of table name
 * $con = established database connections
 *
 * Dependant on following functions:
 * 		-format_elements()
 *		
 */
function update_row($db1_row, $db2_row, $column_datatypes, $table, $con) {
	
	$updated_columns = array();
	$remaining_columns = array();
	$updated_elements = array();
	$remaining_elements = array();
	
	//sorts which columns and elements need updates and which ones don't
	foreach ($db1_row as $db1_element) {
		$column_name = array_search($db1_element, $db1_row);
		$db2_element = $db2_row[$column_name];
		$element_datatype = $column_datatypes[$column_name];
		$formatted_element = format_element($db1_element, $column, $element_datatype);
		if ($column_name == 'LastUpdated') {
			$updated_elements[$column_name] = 'NOW()';
			array_push($updated_columns, $column_name);
		}
		elseif ($db2_element !== $db1_element) {
			$updated_elements[$column_name] = $formatted_element;
			array_push($updated_columns, $column_name);			
		}
		else {
			$remaining_elements[$column_name] = $formatted_element;
			array_push($remaining_columns, $column_name);
		}
	}
	
	//if table contains no previously existing data
	if (count($remaining_columns) == 0) {
		$row = $updated_elements;
		$columns = $updated_columns;
		$formatted_row = format_row($row);
		$formatted_columns = format_columns($columns);
		insert_data('('.$formatted_row.')', $table, $con, '('.$formatted_columns.')');
	}
	//if table does contain previously existing data
	else {
		$set = '';
		$n = count($updated_columns);
		foreach ($updated_columns as $column) {
			--$n;
			$element = $updated_elements[$column];
			
			$set .= "$column=$element";
			if ($n != 0) {
				$set.=", ";
			}	
		}
		$where = '';
		$n = count($remaining_columns);
		foreach ($remaining_columns as $column) {
			--$n;
			$element = $remaining_elements[$column];
			$where .= "$column=$element";
			if ($n != 0) {
				$where .= " AND ";
			}
		}	
		$query = "UPDATE $table SET $set WHERE $where";
		//echo "\n$query\n";
		mysqli_query($con, $query);	
	}	
}

/** update_rows($db1_data, $db2_data, $column_datatypes, $table, $con)
 * Updates given rows with new data
 *
 * $db1_data = represents rows from db1 as a two-dimensional 
 *	array consisting of an associative array
 * 	of each row inside another array
 * $db2_data = represents rows from db2 as a two-dimensional 
 *	array consisting of an associative array
 * 	of each row inside another array
 * $column_datatypes = an associative array of string values
 *  representing each column's datatype with the respective
 *  column name as it's key.
 * $table = string of table name
 * $con = established database connections
 *
 * Dependant on following functions:
 * 		-update_row()
 *		
 */
function update_rows($db1_data, $db2_data, $column_datatypes, $table, $con) {
	$i = 0;
	foreach ($db1_data as $db1_row) {
		$db2_row = $db2_data[$i];
		if ($db2_row !== $db1_row) {
			update_row($db1_row, $db2_row, $column_datatypes, $table, $con);	
		}
		$i++;	
	}
}	

//------------END ALTER DABASE FUNCTIONS---------------//

//-------------SYNC DATABASE FUNCTIONS-----------------//

/** sync_tables($db1_cred, $db2_cred)
 * Synchronizes db2 with db1
 * 
 * $db1_cred = credentials for source database
 * 		array([server name], [username], [password], [database name])
 * $db2_cred = credentials for target database
 * 		array([server name], [username], [password], [database name])
 *
 * Dependant on following functions:
 *	-fetch_tables()
 *	-create_connection()
 *	-fetch_columns()
 *	-format_columns()
 *	-create_table
 *	-print_keys()
 *	-print_array()
 *	-format_data()
 *	-insert_column()
 *	-fetch_data()
 *	-update_column()
 */
function sync_tables($db1_cred, $db2_cred) {

	$db1 = $db1_cred[3];
	$db2 = $db2_cred[3];
	$db1_con = create_connection($db1_cred);
	$db2_con = create_connection($db2_cred);
	$db1_tables = fetch_tables($db1_cred);
	$db2_tables = fetch_tables($db2_cred);
	
	foreach ($db1_tables as $table) {
	
		//adds entire table and it's columns if missing from db2
		$db1_columns = fetch_columns($table, $db1_con);
		$formatted_columns = '('.format_columns($db1_columns).')';
		if (in_array($table, $db2_tables) == False) {
			create_table($table, $db2_con, $formatted_columns);
		}
		//adds missing columns
		$db1_columns = fetch_columns($table, $db1_con);
		$db2_columns = fetch_columns($table, $db2_con);	
		$column_datatypes = get_column_datatypes($db1_columns);
		$db1_data = fetch_data($table, $db1_con);
		$prev_col = '';
		$n = 0;
		foreach ($db1_columns as $column) {			
			if (in_array($column, $db2_columns) == False) {	
				insert_column($table, $column, $db2_con, $prev_col);
			}
			else {
				$n++;	
			}	
			$prev_col = $column;
		}	
		//updates data to existing columns
		$db2_columns = fetch_columns($table, $db2_con);	
		$db2_data = fetch_data($table, $db2_con);
		update_rows($db1_data, $db2_data, $column_datatypes, $table, $db2_con);	
	}
	mysqli_close($db1_con);
	mysqli_close($db2_con);
}

/**
 * Synchronizes db2 with db1, and vice versa
 *
 * $db1_cred = credentials for source database
 * 		array([server name], [username], [password], [database name])
 * $db2_cred = credentials for target database
 * 		array([server name], [username], [password], [database name])
 *
 * Dependant on following functions:
 * 		-sync_tables($db1_cred, $db2_cred)
 */
function two_way_sync_tables($db1_cred, $db2_cred){
	//echo "\ndb1-->db2\n";
	sync_tables($db1_cred, $db2_cred);
	//echo "\ndb2-->db1\n";
	sync_tables($db2_cred, $db1_cred);	
}

//---------END SYNC DATABASE FUNCTIONS-----------------//






?>