<?php

//global datatype arrays
$text_types = array('char','varchar','tinytext','text','blob','mediumtext','mediumblob','longtext','longblob','enum','set');
$num_types = array('tinyint','smallint','mediumint','int','bigint','float','double','decimal');
$date_types = array('date', 'datetime', 'timestamp','time','year');
$conflicts = array();
$test = '';

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

//-------------TIMESTAMP FUNCTIONS-----------------//
/** is_newer($timestamp1, $timestamp2)
 * Determines whether $timestamp1 is newer than $timestamp2
 * returns true or false.
 *
 * $timestamp1 = YYYY-MM-DD hh:mm:ss
 * $timestamp2 = YYYY-MM-DD hh:mm:ss
 */
function is_newer($timestamp1, $timestamp2) {
	//echo "<br>timestamp1: ".gettype($timestamp1)." ".$timestamp2;
	//echo "<br>timestamp2: ".gettype($timestamp1)." ".$timestamp2."<br>";
	$ts1_unix = convert_timestamp($timestamp1);
	$ts2_unix = convert_timestamp($timestamp2);
	if (is_newer_unix($ts1_unix, $ts2_unix) == true) {
		return true;
	}
	else {
		return false;
	}
}

/** convert_timestamp($timestamp)
 * Returns a unix timestamp value converted from given timestamp. 
 *
 * $timestamp = YYYY-MM-DD hh:mm:ss
 */
function convert_timestamp($timestamp) {
	date_default_timezone_set("GMT");
	if ($timestamp == null) {
		return 0;
	}
	//echo "<br>convert_timestamp<br>";
	$date_time_array = explode(' ', $timestamp);
	$date = $date_time_array[0];
	$time = $date_time_array[1];

	$date_explode = explode('-', $date);
	$time_explode = explode(':', $time);
	
	$year = (int) $date_explode[0];
	$month = (int) $date_explode[1];
	$day = (int) $date_explode[2];
	$hour = (int) $time_explode[0];
	$minute = (int) $time_explode[1];
	$second = (int) $time_explode[2];
	
	$unix_timestamp = mktime($hour, $minute, $second, $month, $day, $year);
	//echo "<br>$unix_timestamp<br>";
	$check_date = date('Y-m-d H:i:s',$unix_timestamp);
	if ($check_date != $timestamp and $timestamp != '0000-00-00 00:00:00') {
		echo "<br>checkdate: ".gettype($check_date)." ".$check_date;
		echo "<br>timestamp: ".gettype($timstamp)." ".$timestamp;
		echo "<br>Conversion Discrepency<br>";
	}
	return $unix_timestamp;
}

/** is_newer($timestamp1, $timestamp2)
 * Determines whether $timestamp1 is newer than $timestamp2;
 * returns true or false.
 *
 * $timestamp1 = Unix timestamp int
 * $timestamp2 = Unix timestamp int
 */
function is_newer_unix($timestamp1, $timestamp2) {
	if ($timestamp1 > $timestamp2) {
		return true;	
	}
	else {
		return false;
	}

}

//----------END TIMESTAMP FUNCTIONS---------------//

//-------------FETCH FUNCTIONS-------------------//

/** fetch_databases($credentials)
 * Returns array of all databases in the connection
 *
 * $credentials = credentials = array([server name], [username], [password])
 * 
 * Dependant on following functions:
 *		-create_connection()
 */
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

/** fetch_ts_tables($credentials)
 * Fetches all tablenames ending in "_ts" and returns them as array
 *
 * $credentials = array([server name], [username], [password], [database name])
 *
 * Dependant on following functions:
 *		-fetch_tables()
 */
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

/**
 * fetch_non_ts_tables($credentials)
 * Fetches all tablenames not ending in "_ts" and returns them as array
 *
 * $credentials = array([server name], [username], [password], [database name])
 *
 * Dependant on following functions:
 * 		-fetch_tables()
 */
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

/** get_primary_key($table, $con)
 * Searches and returns primary key for given table; 
 * returns null if no primary key is found
 *
 * $table = string of table's name
 * $con = database's pre-establised connection 
 */
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

/** get_column_info($table, $con)
 * Returns an numeric array containing associative arrays 
 * of each column in given table each with the following keys:
 *		'Field'
 *		'Type'
 * 		'Null'
 * 		'Key'
 * 		'Default'
 * 		'Extra'
 *
 * $table = string of table's name
 * $con = database's pre-establised connection 
 */
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

/** get_row($table, $con, $pk, $pk_id)
 * Returns array of $row based on $table, $pk and $pk_id
 *
 * $table = string of tables name
 * $con = database's pre-established connection
 * $pk = primary key for $table
 * $pk_id = the primary key value for desired row
 */
function get_row($table, $con, $pk, $pk_id) {
	$row = array();
	$result = mysqli_query($con, "SELECT * FROM ".$table." WHERE ".$pk."=".$pk_id);

	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		return $row;
	}
}
	
/** fetch_data($table, $con, $columns = '*')
 * Returns two-dimensional array of all data in the given columns of the given table
 *
 * $table = string of table's name
 * $con = database's pre-established connection
 * $column_names = string of column names separated by comma
 * 		defaults to '*' which selects all columns in table
 */
function fetch_data($table, $con, $column_names='*') {
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
		//echo gettype($element);
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
function format_element($element, $datatype) {
	$formatted_datatype = trim(preg_replace("/\([^)]+\)/", "", $datatype));
	global $num_types;
	if (in_array($formatted_datatype, $num_types)==false) {
		$element = "'".$element."'";
	}
	return $element;
}

//------------END FORMAT FUNCTIONS------------//

//-----------ALTER DATABSE FUNCTIONS----------//

/** create_table_suite($tablename, $con, $columns, $pk)
 * Creates two tables: one as $tablename (main table with primary key $pk) and one as
 * $tablename_ts (intended to log the timestamps of each update in $tablename
 * under the same column names). $tablename_ts contains all of the same columns
 * as $tablename plus one more as $pk_ts as it's own primary key. Column $pk functions
 * as a secondary key pointing to $tablename in $tablename_ts.
 *
 * $tablename = string of desired name for primary table
 * $con = database's pre-establised connection
 * $columns = an array of columns and their datatypes
 * $pk = desired primary key column
 * 		NOTE: $pk may or may not be included in $columns. If it is not included,
 * the function automatically adds it to the table.
 *
 */
function create_table_suite($tablename, $con, $columns, $pk) {
	create_table($tablename, $con, $columns, $pk);
	create_ts_table($tablename, $con, $columns, $pk);
}

/** create_ts_table($tablename, $con, $columns, $pk)
 * Creates a parallel table for $tablename as $tablename_ts to record the
 * 		time the record was updated.
 * 
 * $tablename = pre-existing table name for which parallel table
 * 		is to be created
 * $con = database's pre-established connection
 * $columns = an array of columns and their datatypes
 * $pk = primary key of $tablename (will become the foreign key
 * 		of $tablename_ts, whose primary key become $pk_ts).
 */
function create_ts_table($tablename, $con, $columns, $pk) {
	$column_names = get_column_names($columns);
	$timestamp_columns = array();
	foreach ($column_names as $column) {
		$timestamp_column = $column.' datetime';
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
 * 		NOTE: $pk may or may not be included in $columns. If it is not included,
 * 		the function automatically adds it to the table. If no $pk is supplied,
 * 		the function defaults the primary key to the first column in $columns.
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
	$col_name = get_column_name($column);
	$prev_col_name = get_column_name($prev_col);
	$sql = "ALTER TABLE ".$table." ADD ".$column;
	$sql_ts = "ALTER TABLE ".$table."_ts ADD ".$col_name." datetime";
	if ($prev_col != '') {
		$sql = "ALTER TABLE ".$table." ADD ".$column." AFTER ".$prev_col_name;
		$sql_ts = "ALTER TABLE ".$table."_ts ADD ".$col_name." datetime AFTER ".$prev_col_name;
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
	mysqli_query($con, $query);
}

/** insert_data_suite($formatted_data, $table, $con, $formatted_columns = '')
 * Inserts data into $table and the corresponding timestamps into $table."_ts" 
 * by calling function insert_data() twice for each table.
 *
 * $formatted_data = string of data to be inserted into $table
 * $table = string of main table name receiving data
 * $con = connection of respective database
 */
function insert_data_suite($formatted_data, $table, $con, $formatted_columns = '') {
	insert_data($formatted_data, $table, $con, $formatted_columns);
	$pk = get_primary_key($table, $con);
	$pk_values = fetch_data($table, $con, $pk);
	$values = array();
	foreach ($pk_values as $pk_value) {
		$value = $pk_value[$pk];
		array_push($values, $value);
	}
	$pk_value = max($values);
	$U_now = time();
	date_default_timezone_set("GMT");
	$now = date("Y-m-d H:i:s", $U_now);
	$data_ts ="(NULL, $pk_value, '$now', '$now', '$now', '$now')";
	$table_ts = $table."_ts";
	insert_data($data_ts, $table_ts, $con, $formatted_columns);
	
}

/** update_query($updated_columns, $remaining_columns, $updated_elements, $pk_id, $table, $con)
 * Executes the actual syncronization of a single row by sending an update 
 * query to the respective database. 
 * 
 * $updated_columns = array of columns in row that need updating
 * $remaining_columns = array of all columns in row that do not need updating
 * $updated_elements = associative array of elements that need updating with column name as key
 * $pk_id = the value of the primary key in row
 * $table = name of table to be updated
 * $con = connection of database to be updated
 */
function update_query($updated_columns, $remaining_columns, $updated_elements, $pk_id, $table, $con) {
	
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
		$pk = get_primary_key($table, $con);
		$where = "$pk=$pk_id";
		$query = "UPDATE $table SET $set WHERE $where";
		mysqli_query($con, $query);	
	}	
}

/** check_row_for_updates($db1_row, $db2_row, $table, $db1_con, $db2_con)
 * Sorts rows and data according those that need updating and those that do not.
 * Returns array arrays: array($updated_columns, $remaining_columns, $updated_elements, $remaining_elements)
 *
 * $db1_row = row from database already updated
 * $db2_row = row from database to be updated
 * $table = name of table being checked
 * $db1_con = corresponding connection for $db1_row
 * $db2_con = corresponding connection for $db2_row
 */
function check_row_for_updates($db1_row, $db2_row, $table, $db1_con, $db2_con) {
	$pk = get_primary_key($table, $db1_con);
	$pk_id = $db1_row[$pk];
	if (strrpos($table, "_ts") == false) {
		$table_ts = $table."_ts";
	}
	else {
		$table_ts = $table;
	}
	$db1_ts_row = get_row($table_ts, $db1_con, $pk, $pk_id);
	$db2_ts_row = get_row($table_ts, $db2_con, $pk, $pk_id);
	
	$exhausted_columns = array();
	$updated_columns = array();
	$remaining_columns = array();
	$updated_elements = array();
	$remaining_elements = array();
	foreach ($db1_row as $db1_element) {
		$keys = array_keys($db1_row, $db1_element);
		foreach ($keys as $key) {
			if (in_array($key, $exhausted_columns) == false) {
				$column_name = $key;
				break;
			}
		}
		$ts_columns = fetch_columns($table_ts, $db1_con);
		foreach ($ts_columns as $ts_column) {
			if (get_column_name($ts_column) == $column_name) {
				$ts_column_datatype = get_column_datatype($ts_column);
				break;
			}
		}
		
		$db2_element = $db2_row[$column_name];
		$db1_ts_element = $db1_ts_row[$column_name];
		$db2_ts_element = $db2_ts_row[$column_name];

		if ($ts_column_datatype == 'datetime') {
			if (is_newer($db1_ts_element, $db2_ts_element) == true) {
				$update = true;	
			}
			else {
				$update = false;
			}
		}
		else {
			if ($db2_ts_element == null and $db1_ts_element != null) {
				$update = true;
			}
			else {
				$update = false;
			}
		}
		$element_datatype = $column_datatypes[$column_name];
		$formatted_element = format_element($db1_element, $element_datatype);
		if (($db2_element != $db1_element) and ($update == true)) {
			$updated_elements[$column_name] = $formatted_element;
			array_push($updated_columns, $column_name);	

		}
		elseif ($db1_element != $db2_element and $db1_ts_element == $db2_ts_element)	{
			global $conflicts;
			array_push($conflicts, array(
				'Table' => $table,
				'Column' => $column_name, 
				'Row' => $pk_id, 
				'db1' => $db1_element, 
				'db1_ts' => $db1_ts_element, 
				'db2' => $db2_element, 
				'db2_ts' => $db2_ts_element
			));
		}	
		else {
			$remaining_elements[$column_name] = $formatted_element;
			array_push($remaining_columns, $column_name);
		}
		array_push($exhausted_columns, $column_name);
	}
	return array($updated_columns, $remaining_columns, $updated_elements, $remaining_elements);
}

//------------END ALTER DATABASE FUNCTIONS---------------//

//-------------SYNC DATABASE FUNCTIONS-----------------//

/** sync_row($db1_row, $db2_row, $table, $db1_con, $db2_con)
 * Syncs row by calling check_row_for_updates() to determine what needs updating,
 * then executes needed updates by calling update_query()
 *
 * $db1_row = row from database already updated
 * $db2_row = row from database to be updated
 * $table = name of table being synced
 * $db1_con = corresponding connection for $db1_row
 * $db2_con = corresponding connection for $db2_row
 */
function sync_row($db1_row, $db2_row, $table, $db1_con, $db2_con) {
	$columns = fetch_columns($table, $db1_con);
	$column_datatypes = get_column_datatypes($columns);
	$pk = get_primary_key($table, $db1_con);
	$pk_id = $db1_row[$pk];
	
	//sorts which columns and elements need updates and which ones don't
	$sorted_db1_cols = check_row_for_updates($db1_row, $db2_row, $table, $db1_con, $db2_con);
	
	//updates rows
	$db1_updated_columns = $sorted_db1_cols[0];
	$db1_remaining_columns = $sorted_db1_cols[1];
	$db1_updated_elements = $sorted_db1_cols[2];
	$db1_remaining_elements = $sorted_db1_cols[3];
	update_query($db1_updated_columns, $db1_remaining_columns, $db1_updated_elements, $pk_id, $table, $db2_con);
}

/** sync_data_suite($table, $db1_con, $db2_con)
 * Syncs all rows in $table and $table."_ts" by calling sync_row for each row in $table and $table."_ts"
 * 
 * $table = name of table being synced
 * $db1_con = connection of database already updated
 * $db2_con = connection of database to be updated
 */
function sync_data_suite($table, $db1_con, $db2_con) {

	sync_data($table, $db1_con, $db2_con);
	sync_data($table."_ts", $db1_con, $db2_con);
}

/** sync_data($table, $db1_con, $db2_con)
 * Syncs all rows in $table by calling sync_row for each row in $table
 *
 * $table = name of table being synced
 * $db1_con = connection of database already updated
 * $db2_con = connection of database to be updated
 */
function sync_data($table, $db1_con, $db2_con) {
	$db1_data = fetch_data($table, $db1_con);
	$db2_data = fetch_data($table, $db2_con);
	$i = 0; 
	foreach ($db1_data as $db1_row) {
		$db2_row = $db2_data[$i];
		if ($db2_row !== $db1_row) {
			sync_row($db1_row, $db2_row, $table, $db1_con, $db2_con);	
		}
		$i++;	
	}
	
}	

/** sync_table($table, $db1_con, $db2_con, $db2_tables)
 * Determines if $table from db1 exists in db2 and if not creates said table
 *
 * $table = name of table being synced
 * $db1_con = connection corresponding to database of origin for $table
 * $db2_con = connection corresponding to database being checked
 * $db2_tables = array of existin tables in db2 
 */
function sync_table($table, $db1_con, $db2_con, $db2_tables) {
	$db1_columns = fetch_columns($table, $db1_con);
	$db1_info = get_column_info($table, $db1_con);
	$pk = '';
	foreach ($db1_info as $column) {
		if ($column['Key'] == 'PRI') {
			$pk = $column['Field'];
		}
	}
	if (in_array($table, $db2_tables) == False) {
		create_table_suite($table, $db2_con, $db1_columns, $pk);
	}
}

/** sync_tables($db1_tables, $db2_tables, $db1_con, $db2_con)
 * Adds entire tables and their columns in db1 if missing from db2
 * 
 * $db1_tables = array of existing tables in database of origin
 * $db2_tables = array of existing tables in database to be synced
 * $db1_con = corresponding connection for $db1_tables
 * $db2_con = corresponding connection for $db2_tables
 */
function sync_tables($db1_tables, $db2_tables, $db1_con, $db2_con) {
	foreach ($db1_tables as $table) {
		sync_table($table, $db1_con, $db2_con, $db2_tables);
		sync_columns($table, $db1_con, $db2_con);
		sync_data_suite($table, $db1_con, $db2_con);	
	}
}

/** sync_columns($table, $db1_con, $db2_con)
 * Adds existing columns in db1 to those missing in db2
 *
 * $table = name of $table being synced
 * $db1_con = connection corresponding to database of origin
 * $db2_con = connection corresponding to database to be synced
 */
function sync_columns($table, $db1_con, $db2_con) {
	$db1_columns = fetch_columns($table, $db1_con);
	$db2_columns = fetch_columns($table, $db2_con);	
	$prev_col = '';
	foreach ($db1_columns as $column) {			
		if (in_array($column, $db2_columns) == False) {	
			insert_column($table, $column, $db2_con, $prev_col);
		}	
		$prev_col = $column;
	}	
}

/** sync_databases($db1_cred, $db2_cred)
 * Synchronizes db2 with db1
 * 
 * $db1_cred = credentials for source database
 * 		array([server name], [username], [password], [database name])
 * $db2_cred = credentials for target database
 * 		array([server name], [username], [password], [database name])
 *
 */
function sync_databases($db1_cred, $db2_cred) {

	$db1_con = create_connection($db1_cred);
	$db2_con = create_connection($db2_cred);
	$db1_tables = fetch_non_ts_tables($db1_cred);
	$db2_tables = fetch_non_ts_tables($db2_cred);
	sync_tables($db1_tables, $db2_tables, $db1_con, $db2_con);
	global $conflicts;
	$conflicts = array();
	sync_tables($db2_tables, $db1_tables, $db2_con, $db1_con);	
	
	mysqli_close($db1_con);
	mysqli_close($db2_con);
}

//Temporary interface for resolving conflicts
function resolve_conflicts($db1_cred, $db2_cred, $conflicts) {
	if (count($conflicts) != 0) {
		$db1 = $db1_cred[3];
		$db2 = $db2_cred[3];
		foreach ($conflicts as $conflict) {
			$table = $conflict['Table'];
			$row = $conflict['Row'];
			$column = $conflict['Column'];
			echo '<b>WARNING! The following could not be updated because of a conflict!</b>';
			echo '<br><b>Please select one</b>';
			echo "<br>Column:<b> ", $conflict['Column'], "</b>, Row:<b> ", $conflict['Row'], "</b>";
			echo '
				<form name = "resolve" action="resolve_conflict.php" method="post"
				<br><input type="radio" name="choice" value=1> '.$db1.'<b>: '.$conflict["db2"].'</b>
				<br><input type="radio" name="choice" value=2> '.$db2.'<b>: '.$conflict["db1"].'</b>
				<input type="hidden" name="table" value='.$table.'>
				<input type="hidden" name="row" value='.$row.'>
				<input type="hidden" name="column" value='.$column.'>
				<input type="hidden" name="db1_value" value='.$conflict["db2"].'>
				<input type="hidden" name="db2_value" value='.$conflict["db1"].'>
				<input type="hidden" name="db1" value='.$db1.'>
				<input type="hidden" name="db2" value='.$db2.'>
				<br><input type="submit" value="Submit">
				</form>
			';
		}
	}
}

//---------END SYNC DATABASE FUNCTIONS-----------------//

?>