<?php
require 'input_credentials.php';
require 'dbsync_functions.php';
require 'test_dbsync_functions.php';
echo "\n <br>";

$dbs = fetch_databases($input_cred);
$dbs_to_create = array($dbA, $dbB, $buf);
$input_con = create_connection($input_cred);
foreach ($dbs_to_create as $db) {
	if (in_array($db, $dbs) == false) {
		create_database($db, $input_con);
	}
} 
$dbA_con = create_connection($dbA_cred);
$dbB_con = create_connection($dbB_cred);
$buf_con = create_connection($buf_cred);

$secs = 1;
echo "\n<br>Intentional delay to test timestamps...<br>\n";

//Test 1: tests timestamps
echo "<br>TEST 1";
$table = 'Persons';

reset_table($dbA_con, $dbA_cred, $table);
reset_table($dbB_con, $dbB_cred, $table);
reset_table($buf_con, $buf_cred, $table);
reset_table($dbA_con, $dbA_cred, $table."_ts");
reset_table($dbB_con, $dbB_cred, $table."_ts");
reset_table($buf_con, $buf_cred, $table."_ts");

//echo '<br>s--------------------<br>';

$columns = array('P_Id int', 'LastName varchar(255)', 'FirstName varchar(255)', 'Address varchar(255)', 'City varchar(255)');
create_timestamp_table($table, $dbA_con, $columns, 'P_Id');
//echo '<br>e--------------------<br>';
$columns = fetch_columns($table, $dbA_con);
$formatted_cols = '('.format_columns($columns).')';



$data = "(4,'Nilsen', 'Johan', 'Bakken 2', 'Stavanger', NOW())";
insert_data($data, $table, $dbA_con);

$data = "(1, 'Hansen', 'Ola', 'Timoteivn 10', 'Sandnes', NOW())";
insert_data($data, $table, $dbA_con);

sleep($secs);
sync_tables($dbA_cred, $buf_cred);

$dbA_info = get_column_info($table, $dbA_con);
$buf_info = get_column_info($table, $buf_con);
echo "<br>dbA and buf column info:";
check_column_info($dbA_info, $buf_info);

$dbA_data = fetch_data($table, $dbA_con);
$buf_data = fetch_data($table, $buf_con);

$i = 0;
foreach ($dbA_data as $dbA_row) {
	$buf_row = $buf_data[$i];
	if ($dbA_row['LastUpdated'] == $buf_row['LastUpdated']) {
		$test1 = true;
	}
	else {
		$test1 = false;
		break;
	}
	++$i;
}
if ($test1 = false) {
	echo "Test 1: timestamp fail! <br>\n";	
}
elseif ($test2 = true) {
	echo "Test 1: timestamp pass! <br>\n";
}
//End Test 1


//Test 2: tests inserted columns
echo "<br>TEST 2";
$sql1 = "ALTER TABLE ".$table." ADD NewColumn varchar(255)";
$sql2 = "ALTER TABLE ".$table." ADD InsertedColumn int AFTER FirstName";
$sql3 = "UPDATE ".$table." SET InsertedColumn = 1, NewColumn='hi' WHERE P_Id=4";
$sql4 = "UPDATE ".$table." SET InsertedColumn = 2, NewColumn='ho' WHERE P_Id=1";
mysqli_query($dbA_con, $sql1);
mysqli_query($dbA_con, $sql2);
mysqli_query($dbA_con, $sql3);
mysqli_query($dbA_con, $sql4);

sleep($secs);
sync_tables($dbA_cred, $buf_cred);

$dbA_info = get_column_info($table, $dbA_con);
$buf_info = get_column_info($table, $buf_con);
echo "<br>dbA and buf column info:";
check_column_info($dbA_info, $buf_info);

$dbA_columns = fetch_columns($table, $dbA_con);
$buf_columns = fetch_columns($table, $buf_con);

if ($dbA_columns === $buf_columns) {
	echo "Test 2: columns pass! <br>\n";
}
elseif ($dbA_columns !== $buf_columns) {
	echo "Test 2: columns fail! <br>\n";
}
//End Test 2

//Test 3: tests updated data
echo "<br>TEST 3";
$dbA_data = fetch_data($table, $dbA_con);
$buf_data = fetch_data($table, $buf_con);

$match = check_data_match($dbA_data, $buf_data);
if ($match == true) {
	echo "<br>Test 3: data pass! <br>\n";
}
else {
	echo "<br>Test 3: data fail! <br>\n";
}
//End Test 3

//Test 4: tests updated data again after alterations
echo "<br>TEST 4";
$sql5 = "UPDATE ".$table." SET InsertedColumn = 3, NewColumn='ho' WHERE P_Id=4";
$sql6 = "UPDATE ".$table." SET InsertedColumn = 4, NewColumn='hum' WHERE P_Id=1";
mysqli_query($dbA_con, $sql5);
mysqli_query($dbA_con, $sql6);

sleep($secs);
sync_tables($dbA_cred, $buf_cred);

$dbA_info = get_column_info($table, $dbA_con);
$buf_info = get_column_info($table, $buf_con);
echo "<br>dbA and buf column info:";
check_column_info($dbA_info, $buf_info);

$dbA_data = fetch_data($table, $dbA_con);
$buf_data = fetch_data($table, $buf_con);

$match = check_data_match($dbA_data, $buf_data);
if ($match == true) {
	echo "Test 4: data pass! <br>\n";
}
else {
	echo "Test 4: data fail! <br>\n";
}
//End Test 4

/*
//Test 5: tests two-way sync
reset_table($dbA_con, $dbA_cred, $table);
reset_table($dbB_con, $dbB_cred, $table);
reset_table($buf_con, $buf_cred, $table);
reset_table($dbA_con, $dbA_cred, $table."_ts");
reset_table($dbB_con, $dbB_cred, $table."_ts");
reset_table($buf_con, $buf_cred, $table."_ts");


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