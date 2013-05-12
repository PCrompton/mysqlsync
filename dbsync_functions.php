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

/** convert_timestamp($timestamp)
 * Returns a unix timestamp value converted from given timestamp. 
 *
 * $timestamp = YYYY-MM-DD hh:mm:ss.
 */
function convert_timestamp($timestamp) {
	$date_time_array = explode(' ', $timestamp);
	$date = $date_time_array[0];
	$time = $date_time_array[1];

	$date_explode = explode('-', $date);
	$time_explode = explode(':', $time);
	
	$year = $date_explode[0];
	$month = $date_explode[1];
	$day = $date_explode[2];
	$hour = $time_explode[0];
	$minute = $time_explode[1];
	$second = $time_explode[2];
	
	$unix_timestamp = mktime($hour, $minute, $second, $month, $day, $year);
	$check_date = date('Y-m-d H:i:s',$unix_timestamp);
	if ($check_date != $timestamp) {
		echo "<br>Conversion Discrepency<br>";
	}
	return $unix_timestamp;
}

function is_newer($timestamp1, $timestamp2) {
	if ($timestamp1 > $timestamp2) {
		return true;	
	}
	else {
		return false;
	}

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

function fetch_ts_tables($credentials) {
	$tables = fetch_tables($credentials);
	$ts_tables = array();
	foreach ($tables as $table) {
		if (strstr($table, '_ts') != false) {
			array_push($ts_tables, $table);
		}
	}
	return $ts_tables;
}

function fetch_non_ts_tables($credentials) {
	$tables = fetch_tables($credentials);
	$non_ts_tables = array();
	foreach ($tables as $table) {
		if (strstr($table, '_ts') == false) {
			array_push($non_ts_tables, $table);
		}
	}
	return $non_ts_tables;
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

function get_primary_key($table, $con) {
	$columns = get_column_info($table, $con);
	foreach ($columns as $column) {
		if ($column['Key'] == 'PRI') {
			return $column['Field'];
		}
		else {
			return null;
		}
	}

}

function get_column_info($table, $con) {
	$columns = array();
	$result = mysqli_query($con, "SHOW COLUMNS FROM $table");
	while($column = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		array_push($columns, $column);
	}
	return $columns;
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
		echo "  ".key($array)." <br>";
		next($array);
	}
	echo "\n<br>";
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
	global $num_types;
	if (in_array($formatted_datatype, $num_types)==false) {
		$element = "'".$element."'";
	}
	return $element;
}

//------------END FORMAT FUNCTIONS------------//

//-----------ALTER DATABSE FUNCTIONS----------//


function create_timestamp_table($tablename, $con, $columns, $pk) {
	create_table($tablename, $con, $columns, $pk);
	$column_names = get_column_names($columns);
	$timestamp_columns = array();
	foreach ($column_names as $column) {
		$timestamp_column = $column.' timestamp';
		array_push($timestamp_columns, $timestamp_column);	
	}
	create_table($tablename."_ts", $con, $timestamp_columns, $pk."_ts", $pk, $tablename);
}

/** create_table($tablename, $con, $columns)
 * Creates a MySQL table.
 *
 * $tablename = a string containing desired name of new table
 * $con = a database connection
 * $columns = an array representing the desired names and datatypes of the new table's columns 
 * $pk = the primary key for this table
 * $fk = foreign key for this table
 */
function create_table($tablename, $con, $columns, $pk='', $fk='', $fk_ref='') {
	$column_names = get_column_names($columns);
	$column_datatypes = get_column_datatypes($columns);
	$formatted_columns = format_columns($columns);
	$pk_col = $pk.' int NOT NULL AUTO_INCREMENT';
	$pk_sql = ', PRIMARY KEY ('.$pk.')';
	$fk_col = $fk.' int';
	$fk_sql = ', FOREIGN KEY ('.$fk.') REFERENCES '.$fk_ref.'('.$fk.')';
	if ($pk != '') {
		//echo "$pk <br>";
		if (in_array($pk, $column_names) == false) {
			$formatted_columns = $pk_col.', '.$formatted_columns.$pk_sql;
		}
		else {
			$pk_datatype = $column_datatypes[$pk];
			$formatted_columns = str_replace($pk.' '.$pk_datatype, $pk_col, $formatted_columns);
			$formatted_columns .= $pk_sql;	
		}
	}
	if ($fk != '') {
		if (in_array($fk, $column_names) == false) {
			$formatted_columns = $fk_col.', '.$formatted_columns.$fk_sql;
		}
		else {
			$fk_datatype = $column_datatypes[$fk];
			$formatted_columns = str_replace($fk.' '.$fk_datatype, $fk_col, $formatted_columns);
			$formatted_columns .= $fk_sql;
		}
	}
	$formatted_columns = '('.$formatted_columns.')';
	$sql='CREATE TABLE '.$tablename.$formatted_columns;
	//echo $sql;
	if (mysqli_query($con,$sql)) {
	}
	else {
		echo "$sql<br>\n";
		echo "$formatted_columns<br>\n";
  		echo "Unable to create $tablename<br><br>\n";
  		
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
function insert_column($table, $column, $con, $prev_col=null) {
	$prev_col_name = get_column_name($prev_col);
	$sql = "ALTER TABLE ".$table." ADD ".$column;
	$sql_ts = "ALTER TABLE ".$table."_ts ADD ".$column;
	if ($prev_col != '') {
		$sql = "ALTER TABLE ".$table." ADD ".$column." AFTER ".$prev_col_name;
		$sql_ts = "ALTER TABLE ".$table."_ts ADD ".$column." AFTER ".$prev_col_name;
	}
	mysqli_query($con, $sql);
	mysqli_query($con, $sql_ts);
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
	echo "<br>$table<br>";
	echo "\n<br>$query<br>\n";
	mysqli_query($con, $query);
}



/** update_row($db1_row, $db2_row, $column_datatypes, $table, $con)
 * Updates given row with new data
 *
 * $db1_row = represents database row from db1 as an
 *	array consisting of an associative array
 * 	with the column name as the element's key.
  * $db2_row = represents database row from db1 as an
 *	array consisting of an associative array
 * 	with the column name as the element's key.
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
	
	$ts_table = $table."_ts";
	$updated_columns = array();
	$remaining_columns = array();
	$updated_elements = array();
	$remaining_elements = array();
	$updated_timestamps = array();
	$pk = get_primary_key($table, $con);
	$pk_data = $db1_row[$pk];

	$U_now = time();
	date_default_timezone_set("GMT");
	$now = date("Y-m-d H:i:s", $U_now);

	echo "<br>$now<br>";
	//echo "<br>now: ".$now;
	//sorts which columns and elements need updates and which ones don't
	foreach ($db1_row as $db1_element) {
		$column_name = array_search($db1_element, $db1_row);
		$db2_element = $db2_row[$column_name];
		$element_datatype = $column_datatypes[$column_name];
		$formatted_element = format_element($db1_element, $column, $element_datatype);
		if ($db2_element !== $db1_element) {
			$updated_elements[$column_name] = $formatted_element;
			$updated_timestamps[$column_name] = $formatted_element;
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
		$ts_row = array($pk_data);
		
		for ($i=1; $i<count($row); $i++) {
			array_push($ts_row, "'$now'");
		}
		$columns = $updated_columns;
		$formatted_row = format_row($row);
		$formatted_ts_row = format_row($ts_row);
		$formatted_columns = format_columns($columns);
		insert_data('('.$formatted_row.')', $table, $con, '('.$formatted_columns.')');
		insert_data('('.$formatted_ts_row.')', $table.'_ts', $con, '('.$formatted_columns.')');
	}
	//if table does contain previously existing data
	else {
		$set = '';
		$ts_set = '';
		$n = count($updated_columns);
		foreach ($updated_columns as $column) {
			--$n;
			$element = $updated_elements[$column];
			
			$set .= "$column=$element";
			$ts_set .= "$column='$now'";
			if ($n != 0) {
				$set.=", ";
				$ts_set .=", ";
			}	
		}
		
		$where = "$pk=$pk_data";
		$query = "UPDATE $table SET $set WHERE $where";
		
		$ts_query = "UPDATE ".$table."_ts SET ".$ts_set." WHERE ".$where;
		echo "<br>query: $query";
		echo "<br>ts_query: $ts_query<br>";
		mysqli_query($con, $query);	
		mysqli_query($con, $ts_query);
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
	$db1_tables = fetch_non_ts_tables($db1_cred);
	$db2_tables = fetch_non_ts_tables($db2_cred);
	
	foreach ($db1_tables as $table) {
		
		//adds entire table and it's columns if missing from db2
		$db1_columns = fetch_columns($table, $db1_con);
		$db1_info = get_column_info($table, $db1_con);
		$pk = '';
		foreach ($db1_info as $column) {
			if ($column['Key'] == 'PRI') {
				$pk = $column['Field'];
			}
		}
		if (in_array($table, $db2_tables) == False) {
			create_timestamp_table($table, $db2_con, $db1_columns, $pk);
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