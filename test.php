<?php
require 'input_credentials.php';
require 'dbsync_functions.php';
require 'test_dbsync_functions.php';
echo "\n <br>";
$input_con = create_connection($input_cred);
$dbs = array('dbA', 'dbB', 'buf');
foreach ($dbs as $db) {
	mysqli_query($input_con, 'DROP DATABASE '.$db);
}
$dbs_to_create = array($dbA, $dbB, $buf);

create_database('dbA', $input_con);
create_database('dbB', $input_con);
create_database('buf', $input_con);

$dbA_con = create_connection($dbA_cred);
$dbB_con = create_connection($dbB_cred);
$buf_con = create_connection($buf_cred);

$secs = 1;
echo "\n<br>Intentional delay to test timestamps...<br>\n";
$table = 'Persons';

drop_table($dbA_con, $dbA_cred, $table);
drop_table($dbB_con, $dbB_cred, $table);
drop_table($buf_con, $buf_cred, $table);
drop_table($dbA_con, $dbA_cred, $table."_ts");
drop_table($dbB_con, $dbB_cred, $table."_ts");
drop_table($buf_con, $buf_cred, $table."_ts");

//Test 1: tests timestamps
echo "<br>TEST 1<br>";


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
//End Test 1


//Test 2: tests inserted columns
echo "<br>TEST 2<br>";
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
//End Test 2


//Test 3: tests updated data again after alterations
echo "<br>TEST 3<br>";
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

//End Test 3

/*
//Test 4: tests two-way sync
drop_table($dbA_con, $dbA_cred, $table);
drop_table($dbB_con, $dbB_cred, $table);
drop_table($buf_con, $buf_cred, $table);
drop_table($dbA_con, $dbA_cred, $table."_ts");
drop_table($dbB_con, $dbB_cred, $table."_ts");
drop_table($buf_con, $buf_cred, $table."_ts");


//dbA
$columns = array('P_Id int', 'LastName varchar(255)', 'FirstName varchar(255)', 'Address varchar(255)', 'City varchar(255)', 'dbACol varchar(255)', 'LastUpdated timestamp');
create_table($table, $dbA_con, $columns, 'P_Id');
$columns = fetch_columns($table, $dbA_con);
$formatted_cols = '('.format_columns($columns).')';
$data = "(4,'Nilsen', 'Johan', 'Bakken 2', 'Stavanger', 'howdy' NOW())";
insert_data($data, $table, $dbA_con);
$data = "(1, 'Hansen', 'Ola', 'Timoteivn 10', 'Sandnes', 'hey', NOW())";
insert_data($data, $table, $dbA_con);

sleep($secs);

//buf
$columns = array('P_Id int', 'LastName varchar(255)', 'FirstName varchar(255)', 'Address varchar(255)', 'City varchar(255)', 'bufCol int, LastUpdated timestamp');
create_table($table, $buf_con, $columns, 'P_Id');
$columns = fetch_columns($table, $buf_con);
$formatted_cols = '('.format_columns($columns).')';
$data = "(4,'Nilsen', 'Johan', 'Bakken 2', 'Stavanger', 100, NOW())";
insert_data($data, $table, $buf_con);
$data = "(1, 'Hansen', 'Ola', 'Timoteivn 10', 'Sandnes', 200, NOW())";
insert_data($data, $table, $buf_con);

sleep($secs);
//two_way_sync_tables($dbA_cred, $buf_cred);

$dbA_data = fetch_data($table, $dbA_con);
$buf_data = fetch_data($table, $buf_con);

$match = check_data_match($dbA_data, $buf_data);
if ($match == true) {
	echo "Test 5: two-way sync pass! <br>\n";
}
else {
	echo "Test 5: two-way sync fail! <br>\n";
}
*/

mysqli_close($dbA_con);
mysqli_close($dbB_con);
mysqli_close($buf_con);
//*/

?>