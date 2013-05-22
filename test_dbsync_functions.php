<?php
if (function_exists('fetch_columns') == false) {
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
}

if (function_exists('fetch_tables') == false) {
   /** fetch_tables($credentials)
	* Returns array of all tables from a given database
	*
	* $credentials = array([server name], [username], [password], [database name]) 
	*
	* Dependant on following functions:
	*	-create_connection()
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
}

function test_instance($table, $db1_con, $db2_con) {
	
	$db1_info = get_column_info($table, $db1_con);
	$db2_info = get_column_info($table, $db2_con);
	$col_info = check_column_info($db1_info, $db2_info);

	$db1_ts_info = get_column_info($table."_ts", $db1_con);
	$db2_ts_info = get_column_info($table."_ts", $db2_con);
	$col_ts_info = check_column_info($db1_ts_info, $db2_ts_info);

	$db1_data = fetch_data($table, $db1_con);
	$db2_data = fetch_data($table, $db2_con);
	$db_match = check_data_match($db1_data, $db2_data);

	$db1_ts_data = fetch_data($table."_ts", $db1_con);
	$db2_ts_data = fetch_data($table."_ts", $db2_con);
	$db_ts_match = check_data_match($db1_ts_data, $db2_ts_data);

	$db1_no_null = check_no_null($db1_data);
	$db2_no_null = check_no_null($db2_data);
	$db1_ts_no_null = check_no_null($db1_ts_data);
	$db2_ts_no_null = check_no_null($db2_ts_data);
	
	$test_results = array(
		"column info" => $col_info, 
		"column ts info" => $col_ts_info, 
		"db match" => $db_match, 
		"db ts match" => $db_ts_match, 
		"db1 no null" => $db1_no_null, 
		"db2 no null" => $db2_no_null, 
		"db1 ts no null" => $db1_ts_no_null, 
		"db2 ts no null" => $db2_ts_no_null
	);	
	$exhausted_keys = array();
	foreach ($test_results as $result) {
		$keys = array_keys($test_results, $result);
		foreach ($keys as $key) {
			if (in_array($key, $exhausted_keys) == false) {
				array_push($exhausted_keys, $key);
				break;
			}
		}
		if ($result == false) {
			echo "<br>".$key.": failed!";
		}
		else {
			echo "<br>".$key.": passed!";
		}	
	}
	echo "<br>";
}

function verify_instance($expected_tables, $expected_cols_array, $expected_data_array, $col_names_array, $cred) {
	verify_tables($expected_tables, $cred);
	$con = create_connection($cred);
	foreach ($expected_tables as $table) {
		$expected_columns = $expected_cols_array[$table];
		$expected_data = $expected_data_array[$table];
		$col_names = $col_names_array[$table];
		echo "$table: ";
		verify_columns($expected_columns, $table, $con);
		echo "$table: ";
		verify_data($expected_data, $table, $con, $col_names);

	}
	mysqli_close($con);
}

function verify_tables($expected_tables, $credentials) {
	$actual_tables = fetch_tables($credentials);
	if ($actual_tables === $expected_tables) {
		echo "Tables verified<br>";
	}
	else {
		echo "Error in table verifications<br>";
		print_array($expected_tables);
		print_array($actual_tables);
	}
}

function verify_columns($expected_columns, $table, $con) {
	$actual_columns = fetch_columns($table, $con);
	//print_array($actual_columns);
	if ($actual_columns == $expected_columns) {
		echo "Columns verified<br>";
	}
	else {
		echo "Error in column verification<br>";
		print_array($expected_columns);
		print_array($actual_columns);
	}
}

function verify_data($expected_data, $table, $con, $column_names = '*') {
	$actual_data = fetch_data($table, $con, $column_names);
	$formatted_actual_elements = array();
	foreach ($actual_data as $row) {
		$formatted_elements = format_elements($row, $table, $con);
		array_push($formatted_actual_elements, $formatted_elements);
	}
	$formatted_actual = format_data($formatted_actual_elements);

	
	if ($formatted_actual === $expected_data) {
		echo "Data verified<br>";
	}
	else {
		echo "Error in data verification<br>";
		print_array($expected_data);
		print_array($formatted_actual);
	}

}

function create_database($db, $con) {
		$sql="CREATE DATABASE $db";
	if (mysqli_query($con,$sql)){
  		echo "Database $db created successfully<br>";
	}
	else {
  		echo "Error creating database: " . mysqli_error($con)."<br>";
	}
}

function check_column_info($db1_info, $db2_info) {
	if ($db1_info !== $db2_info) {
		echo "<br>Column info does not match!<br>";
		$i = 0;
		foreach ($db1_info as $db1_column) {
			$db2_column = $db2_info[$i];
			echo $db2_info['Field'],$db2_info;
			echo '<br>';
			print_array($db1_column);
			echo 'db2 <br>';
			print_array($db2_column);
			$i++;
			echo '<br>';
		}
		return false;
	}
	else {
		return true;
	}

}

/** reset_table($db_con, $db_cred, $table)
 * Clears all contents from given table
 *
 * $db_con = database's pre-established connection
 * $db_cred = array([server name], [username], [password], [database name])
 * $table = string of table's name
 *
 * Dependant on follwing functions:
 *		-fetch_tables
 */
function drop_table($db_con, $db_cred, $table) {
	$db_tables = fetch_tables($db_cred);
	if (in_array($table, $db_tables)) {
		mysqli_query($db_con, "DROP TABLE ".$table);
	}
}

function check_no_null($data) {
	$is_null = false;
	$null_cols = array();
	foreach ($data as $row) {
		foreach ($row as $element) {
			$column = array_search($element, $row);
			//echo "<br>$column<br>";
			//echo "$element<br>";
			if ($element == '') {
				$is_null = true;	
				array_push($null_cols, $column);	
			}	
		}
	}
	if ($is_null == false) {
		return true;
	}
	else {
		echo "<br>Error: data contains Null in columns:<br>";
		return false;
	}
	
}

function check_data_match($data1, $data2) {
	$match = true;
	$i = 0;
	foreach ($data1 as $row1) {
		$row2 = $data2[$i];
		foreach ($row1 as $element1) {
			$column = array_search($element1, $row1);
			$element2 = $row2[$column];
			if ($element1 != $element2) {
				$match = false;
				break;	
			}
		}
		$i++;
	}
	
	if ($match == true) {
		return true;
	}
	else {
		return false;
	}
}
?>