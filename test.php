<?php
require 'input_credentials.php';
require 'dbsync_functions.php';
require 'test_dbsync_functions.php';
echo "\n <br>";
$input_con = create_connection($input_cred);
$dbs = array($dbA, $dbB, $buf);

reset_dbs($dbs, $input_con);

$dbA_con = create_connection($dbA_cred);
$dbB_con = create_connection($dbB_cred);
$buf_con = create_connection($buf_cred);

$secs = 1;
echo "\n<br>Intentional delay to test timestamps...<br>\n";
$table = 'Persons';

//INSTANCE 1:
echo "<br>INSTANCE 1: tests sync of newly configured dbA to empty buf<br>";


$columns = array(
	'P_Id int(11)', 
	'LastName varchar(255)', 
	'FirstName varchar(255)', 
	'Address varchar(255)', 
	'City varchar(255)'
);

create_table_suite($table, $dbA_con, $columns, 'P_Id');

$columns = fetch_columns($table, $dbA_con);
$columns_ts = fetch_columns($table."_ts", $dbA_con);

$formatted_cols = '('.format_columns($columns).')';
$formatted_ts_cols = '('.format_columns($columns_ts).')';

sleep($secs);
$data1 = "(NULL, 'Nilsen', 'Johan', 'Bakken 2', 'Stavanger')";
insert_data_suite($data1, $table, $dbA_con);

sleep($secs);
$data2 = "(NULL, 'Hansen', 'Ola', 'Timoteivn 10', 'Sandnes')";
insert_data_suite($data2, $table, $dbA_con);

//Expected parameters
$exp_cols = $columns;
$exp_ts_cols = array(
	'P_Id_ts int(11)', 
	'P_Id int(11)', 
	'LastName timestamp', 
	'FirstName timestamp', 
	'Address timestamp', 
	'City timestamp');
$exp_data1 = str_replace('NULL', '1', $data1);
$exp_data2 = str_replace('NULL', '2', $data2);
$exp_ts_data1 = "(1, 1)";
$exp_ts_data2 = "(2, 2)";
$exp_data = array($exp_data1, $exp_data2);
$exp_ts_data = array($exp_ts_data1, $exp_ts_data2);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

$col_names_array = array(
	$table => '*',
	$table."_ts" => "P_Id_ts, P_Id"
);
//End expected parameters

verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $col_names_array, $dbA_cred);
sync_tables($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);
//End INSTANCE 1

//INSTANCE 2: tests inserted columns
echo "<br>INSTANCE 2: tests sync of inserted columns in dbA to previously sync'd buf<br>";
insert_column($table, 'NewColumn varchar(255)', $dbA_con);
insert_column($table, 'InsertedColumn int(11)', $dbA_con, 'FirstName');
$U_now = time();
date_default_timezone_set("GMT");
$now = date("Y-m-d H:i:s", $U_now);
$sql1 = "UPDATE ".$table." SET InsertedColumn = 1, NewColumn='hi' WHERE P_Id=1";
$sql2 = "UPDATE ".$table." SET InsertedColumn = 2, NewColumn='ho' WHERE P_Id=2";
$sql3 = "UPDATE ".$table."_ts SET InsertedColumn = '$now', NewColumn='$now' WHERE P_Id=1";
$sql4 = "UPDATE ".$table."_ts SET InsertedColumn = '$now', NewColumn='$now' WHERE P_Id=2";
mysqli_query($dbA_con, $sql1);
mysqli_query($dbA_con, $sql2);
mysqli_query($dbA_con, $sql3);
mysqli_query($dbA_con, $sql4);

//Expected parameters
$exp_cols = array(
	'P_Id int(11)', 
	'LastName varchar(255)', 
	'FirstName varchar(255)',
	'InsertedColumn int(11)', 
	'Address varchar(255)', 
	'City varchar(255)',
	'NewColumn varchar(255)'
);
$exp_ts_cols = array(
	'P_Id_ts int(11)', 
	'P_Id int(11)', 
	'LastName timestamp', 
	'FirstName timestamp',
	'InsertedColumn timestamp',  
	'Address timestamp', 
	'City timestamp',
	'NewColumn timestamp'
);
$exp_data1 = "(1, 'Nilsen', 'Johan', 1, 'Bakken 2', 'Stavanger', 'hi')";
$exp_data2 = "(2, 'Hansen', 'Ola', 2, 'Timoteivn 10', 'Sandnes', 'ho')";
$exp_ts_data1 = "(1, 1)";
$exp_ts_data2 = "(2, 2)";
$exp_data = array($exp_data1, $exp_data2);
$exp_ts_data = array($exp_ts_data1, $exp_ts_data2);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

$col_names_array = array(
	$table => '*',
	$table."_ts" => "P_Id_ts, P_Id"
);
//End expected parameters

verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $col_names_array, $dbA_cred);
sync_tables($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);
//End INSTANCE 2

//INSTANCE 3: tests updated data again after alterations
echo "<br>INSTANCE 3: tests sync of updated data in dbA to previously sync'd buf<br>";
$U_now = time();
date_default_timezone_set("GMT");
$now = date("Y-m-d H:i:s", $U_now);
$sql5 = "UPDATE ".$table." SET InsertedColumn = 3, NewColumn='ho' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET InsertedColumn = 4, NewColumn='hum' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET InsertedColumn = '$now', NewColumn='$now' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET InsertedColumn = '$now', NewColumn='$now' WHERE P_Id=2";
mysqli_query($dbA_con, $sql5);
mysqli_query($dbA_con, $sql6);
mysqli_query($dbA_con, $sql7);
mysqli_query($dbA_con, $sql8);

//Expected parameters
$exp_cols = array(
	'P_Id int(11)', 
	'LastName varchar(255)', 
	'FirstName varchar(255)',
	'InsertedColumn int(11)', 
	'Address varchar(255)', 
	'City varchar(255)',
	'NewColumn varchar(255)'
);
$exp_ts_cols = array(
	'P_Id_ts int(11)', 
	'P_Id int(11)', 
	'LastName timestamp', 
	'FirstName timestamp',
	'InsertedColumn timestamp',  
	'Address timestamp', 
	'City timestamp',
	'NewColumn timestamp'
);
$exp_data1 = "(1, 'Nilsen', 'Johan', 3, 'Bakken 2', 'Stavanger', 'ho')";
$exp_data2 = "(2, 'Hansen', 'Ola', 4, 'Timoteivn 10', 'Sandnes', 'hum')";
$exp_ts_data1 = "(1, 1)";
$exp_ts_data2 = "(2, 2)";
$exp_data = array($exp_data1, $exp_data2);
$exp_ts_data = array($exp_ts_data1, $exp_ts_data2);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

$col_names_array = array(
	$table => '*',
	$table."_ts" => "P_Id_ts, P_Id"
);
//End expected parameters

verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $col_names_array, $dbA_cred);
sync_tables($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);
//End INSTANCE 3

//INSTANCE 4
echo "<br>INSTANCE 4: tests sync of updated data in buf to previously sync'd dbA <br>";
$U_now = time();
date_default_timezone_set("GMT");
$now = date("Y-m-d H:i:s", $U_now);
$sql1 = "UPDATE ".$table." SET InsertedColumn = 1, NewColumn='hi' WHERE P_Id=1";
$sql2 = "UPDATE ".$table." SET InsertedColumn = 2, NewColumn='ho' WHERE P_Id=2";
$sql3 = "UPDATE ".$table."_ts SET InsertedColumn = '$now', NewColumn='$now' WHERE P_Id=1";
$sql4 = "UPDATE ".$table."_ts SET InsertedColumn = '$now', NewColumn='$now' WHERE P_Id=2";
mysqli_query($buf_con, $sql1);
mysqli_query($buf_con, $sql2);
mysqli_query($buf_con, $sql3);
mysqli_query($buf_con, $sql4);

//Expected parameters
$exp_cols = array(
	'P_Id int(11)', 
	'LastName varchar(255)', 
	'FirstName varchar(255)',
	'InsertedColumn int(11)', 
	'Address varchar(255)', 
	'City varchar(255)',
	'NewColumn varchar(255)'
);
$exp_ts_cols = array(
	'P_Id_ts int(11)', 
	'P_Id int(11)', 
	'LastName timestamp', 
	'FirstName timestamp',
	'InsertedColumn timestamp',  
	'Address timestamp', 
	'City timestamp',
	'NewColumn timestamp'
);
$exp_data1 = "(1, 'Nilsen', 'Johan', 1, 'Bakken 2', 'Stavanger', 'hi')";
$exp_data2 = "(2, 'Hansen', 'Ola', 2, 'Timoteivn 10', 'Sandnes', 'ho')";
$exp_ts_data1 = "(1, 1)";
$exp_ts_data2 = "(2, 2)";
$exp_data = array($exp_data1, $exp_data2);
$exp_ts_data = array($exp_ts_data1, $exp_ts_data2);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

$col_names_array = array(
	$table => '*',
	$table."_ts" => "P_Id_ts, P_Id"
);
//End expected parameters

verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $col_names_array, $buf_cred);
sync_tables($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);
//End INSTANCE 4

//INSTANCE 5
echo "<br>INSTANCE 5: tests sync of different updated data in different columns in both dbA and buf<br>";
$U_now = time();
date_default_timezone_set("GMT");
$now = date("Y-m-d H:i:s", $U_now);
$sql5 = "UPDATE ".$table." SET InsertedColumn = 3, NewColumn='ho' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET InsertedColumn = 4, NewColumn='hum' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET InsertedColumn = '$now', NewColumn='$now' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET InsertedColumn = '$now', NewColumn='$now' WHERE P_Id=2";
mysqli_query($dbA_con, $sql5);
mysqli_query($dbA_con, $sql6);
mysqli_query($dbA_con, $sql7);
mysqli_query($dbA_con, $sql8);

//Expected parameters
$exp_cols = array(
	'P_Id int(11)', 
	'LastName varchar(255)', 
	'FirstName varchar(255)',
	'InsertedColumn int(11)', 
	'Address varchar(255)', 
	'City varchar(255)',
	'NewColumn varchar(255)'
);
$exp_ts_cols = array(
	'P_Id_ts int(11)', 
	'P_Id int(11)', 
	'LastName timestamp', 
	'FirstName timestamp',
	'InsertedColumn timestamp',  
	'Address timestamp', 
	'City timestamp',
	'NewColumn timestamp'
);
$exp_data1 = "(1, 'Nilsen', 'Johan', 3, 'Bakken 2', 'Stavanger', 'ho')";
$exp_data2 = "(2, 'Hansen', 'Ola', 4, 'Timoteivn 10', 'Sandnes', 'hum')";
$exp_ts_data1 = "(1, 1)";
$exp_ts_data2 = "(2, 2)";
$exp_data = array($exp_data1, $exp_data2);
$exp_ts_data = array($exp_ts_data1, $exp_ts_data2);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

$col_names_array = array(
	$table => '*',
	$table."_ts" => "P_Id_ts, P_Id"
);
//End expected parameters

echo "<br><b>dbA update verifications: </b><br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $col_names_array, $dbA_cred);


$sql5 = "UPDATE ".$table." SET LastName = 'Ortiz', FirstName='David' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET LastName = 'Pedroia', FirstName='Dustin' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET LastName = '$now', FirstName='$now' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET LastName = '$now', FirstName='$now' WHERE P_Id=2";
mysqli_query($buf_con, $sql5);
mysqli_query($buf_con, $sql6);
mysqli_query($buf_con, $sql7);
mysqli_query($buf_con, $sql8);

//Expected parameters
$exp_cols = array(
	'P_Id int(11)', 
	'LastName varchar(255)', 
	'FirstName varchar(255)',
	'InsertedColumn int(11)', 
	'Address varchar(255)', 
	'City varchar(255)',
	'NewColumn varchar(255)'
);
$exp_ts_cols = array(
	'P_Id_ts int(11)', 
	'P_Id int(11)', 
	'LastName timestamp', 
	'FirstName timestamp',
	'InsertedColumn timestamp',  
	'Address timestamp', 
	'City timestamp',
	'NewColumn timestamp'
);
$exp_data1 = "(1, 'Ortiz', 'David', 3, 'Bakken 2', 'Stavanger', 'ho')";
$exp_data2 = "(2, 'Pedroia', 'Dustin', 4, 'Timoteivn 10', 'Sandnes', 'hum')";
$exp_ts_data1 = "(1, 1)";
$exp_ts_data2 = "(2, 2)";
$exp_data = array($exp_data1, $exp_data2);
$exp_ts_data = array($exp_ts_data1, $exp_ts_data2);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

$col_names_array = array(
	$table => '*',
	$table."_ts" => "P_Id_ts, P_Id"
);
echo "<br><b>buf update verifications: </b><br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $col_names_array, $buf_cred);
echo "<br><b>Instance 5 test: </b><br>";
sync_tables($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);
//End INSTANCE 5

//INSTANCE 6
echo "<br>INSTANCE 6: tests sync of different updated data in same columns in both dbA and buf<br>";
echo "(does not catch conflict)<br>";
$U_now = time();
date_default_timezone_set("GMT");
$now = date("Y-m-d H:i:s", $U_now);
$sql5 = "UPDATE ".$table." SET LastName = 'Ellsbury', FirstName='Jacoby' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET LastName = 'Victorino', FirstName='Shane' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET LastName = '$now', FirstName='$now' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET LastName = '$now', FirstName='$now' WHERE P_Id=2";
mysqli_query($dbA_con, $sql5);
mysqli_query($dbA_con, $sql6);
mysqli_query($dbA_con, $sql7);
mysqli_query($dbA_con, $sql8);

//Expected parameters
$exp_cols = array(
	'P_Id int(11)', 
	'LastName varchar(255)', 
	'FirstName varchar(255)',
	'InsertedColumn int(11)', 
	'Address varchar(255)', 
	'City varchar(255)',
	'NewColumn varchar(255)'
);
$exp_ts_cols = array(
	'P_Id_ts int(11)', 
	'P_Id int(11)', 
	'LastName timestamp', 
	'FirstName timestamp',
	'InsertedColumn timestamp',  
	'Address timestamp', 
	'City timestamp',
	'NewColumn timestamp'
);
$exp_data1 = "(1, 'Ellsbury', 'Jacoby', 3, 'Bakken 2', 'Stavanger', 'ho')";
$exp_data2 = "(2, 'Victorino', 'Shane', 4, 'Timoteivn 10', 'Sandnes', 'hum')";
$exp_ts_data1 = "(1, 1)";
$exp_ts_data2 = "(2, 2)";
$exp_data = array($exp_data1, $exp_data2);
$exp_ts_data = array($exp_ts_data1, $exp_ts_data2);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

$col_names_array = array(
	$table => '*',
	$table."_ts" => "P_Id_ts, P_Id"
);
//End expected parameters

echo "<br><b>dbA update verifications: </b><br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $col_names_array, $dbA_cred);

$sql5 = "UPDATE ".$table." SET LastName = 'Ortiz', FirstName='David' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET LastName = 'Pedroia', FirstName='Dustin' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET LastName = '$now', FirstName='$now' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET LastName = '$now', FirstName='$now' WHERE P_Id=2";
mysqli_query($buf_con, $sql5);
mysqli_query($buf_con, $sql6);
mysqli_query($buf_con, $sql7);
mysqli_query($buf_con, $sql8);

//Expected parameters
$exp_cols = array(
	'P_Id int(11)', 
	'LastName varchar(255)', 
	'FirstName varchar(255)',
	'InsertedColumn int(11)', 
	'Address varchar(255)', 
	'City varchar(255)',
	'NewColumn varchar(255)'
);
$exp_ts_cols = array(
	'P_Id_ts int(11)', 
	'P_Id int(11)', 
	'LastName timestamp', 
	'FirstName timestamp',
	'InsertedColumn timestamp',  
	'Address timestamp', 
	'City timestamp',
	'NewColumn timestamp'
);
$exp_data1 = "(1, 'Ortiz', 'David', 3, 'Bakken 2', 'Stavanger', 'ho')";
$exp_data2 = "(2, 'Pedroia', 'Dustin', 4, 'Timoteivn 10', 'Sandnes', 'hum')";
$exp_ts_data1 = "(1, 1)";
$exp_ts_data2 = "(2, 2)";
$exp_data = array($exp_data1, $exp_data2);
$exp_ts_data = array($exp_ts_data1, $exp_ts_data2);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

$col_names_array = array(
	$table => '*',
	$table."_ts" => "P_Id_ts, P_Id"
);
echo "<br><b>buf update verifications: </b><br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $col_names_array, $buf_cred);
echo "<br><b>Instance 5 test: </b><br>";
sync_tables($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);
//End INSTANCE 6


mysqli_close($dbA_con);
mysqli_close($dbB_con);
mysqli_close($buf_con);

reset_dbs($dbs, $input_con);

$dbA_con = create_connection($dbA_cred);
$dbB_con = create_connection($dbB_cred);
$buf_con = create_connection($buf_cred);

//INSTANCE 7:
echo "<br>INSTANCE 7: tests sync of newly configured buf to empty dbA<br>";

$columns = array(
	'P_Id int(11)', 
	'LastName varchar(255)', 
	'FirstName varchar(255)', 
	'Address varchar(255)', 
	'City varchar(255)'
);

create_table_suite($table, $buf_con, $columns, 'P_Id');

$columns = fetch_columns($table, $buf_con);
$columns_ts = fetch_columns($table."_ts", $buf_con);

$formatted_cols = '('.format_columns($columns).')';
$formatted_ts_cols = '('.format_columns($columns_ts).')';

sleep($secs);
$data1 = "(NULL, 'Nilsen', 'Johan', 'Bakken 2', 'Stavanger')";
insert_data_suite($data1, $table, $buf_con);

sleep($secs);
$data2 = "(NULL, 'Hansen', 'Ola', 'Timoteivn 10', 'Sandnes')";
insert_data_suite($data2, $table, $buf_con);

//Expected parameters
$exp_cols = $columns;
$exp_ts_cols = array(
	'P_Id_ts int(11)', 
	'P_Id int(11)', 
	'LastName timestamp', 
	'FirstName timestamp', 
	'Address timestamp', 
	'City timestamp');
$exp_data1 = str_replace('NULL', '1', $data1);
$exp_data2 = str_replace('NULL', '2', $data2);
$exp_ts_data1 = "(1, 1)";
$exp_ts_data2 = "(2, 2)";
$exp_data = array($exp_data1, $exp_data2);
$exp_ts_data = array($exp_ts_data1, $exp_ts_data2);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

$col_names_array = array(
	$table => '*',
	$table."_ts" => "P_Id_ts, P_Id"
);
//End expected parameters

verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $col_names_array, $buf_cred);

sync_tables($dbA_cred, $buf_cred);
echo "<br>---------------------------------------------<br>";
test_instance($table, $dbA_con, $buf_con);
echo "<br>---------------------------------------------<br>";
//End INSTANCE 7

mysqli_close($dbA_con);
mysqli_close($dbB_con);
mysqli_close($buf_con);
//*/

?>