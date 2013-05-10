<?php


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
			echo '<br>';
			echo 'db1 <br>';
			print_array($db1_column);
			echo 'db2 <br>';
			print_array($db2_column);
			$i++;
			echo '<br>';
		}
	}
	else {
		echo "<br>Column info matches!<br>";
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
function reset_table($db_con, $db_cred, $table) {
	$db_tables = fetch_tables($db_cred);
	if (in_array($table, $db_tables)) {
		mysqli_query($db_con, "DROP TABLE ".$table);
	}
}


function check_data_match($data1, $data2) {
	$i = 0;
	foreach ($data1 as $row1) {
		$row2 = $data2[$i];
		foreach ($row1 as $element1) {
			$column = array_search($element1, $row1);
			$element2 = $row2[$column];
			if ($column != 'LastUpdated' and $element1 != $element2) {
				return false;
				break;	
			}
		}
		$i++;
	}
	return true;
}
?>