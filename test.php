<?php
require 'input_credentials.php';
require 'dbsync_functions.php';
require 'test_dbsync_functions.php';
echo "\n <br>";
$input_con = create_connection($input_cred);
$dbs = fetch_databases($input_cred);
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

//Test 1: tests timestamps
echo "<br>TEST 1";
$table = 'Persons';

reset_table($dbA_con, $dbA_cred, $table);
reset_table($dbB_con, $dbB_cred, $table);
reset_table($buf_con, $buf_cred, $table);
reset_table($dbA_con, $dbA_cred, $table."_ts");
reset_table($dbB_con, $dbB_cred, $table."_ts");
reset_table($buf_con, $buf_cred, $table."_ts");



$columns = array('P_Id int', 'LastName varchar(255)', 'FirstName varchar(255)', 'Address varchar(255)', 'City varchar(255)');
create_timestamp_table($table, $dbA_con, $columns, 'P_Id');

$columns = fetch_columns($table, $dbA_con);
$formatted_cols = '('.format_columns($columns).')';
echo $formatted_cols;

$U_now = time();
date_default_timezone_set("GMT");
$now = date("Y-m-d H:i:s", $U_now);
$data = "(NULL, 'Nilsen', 'Johan', 'Bakken 2', 'Stavanger')";
$data_ts ="(NULL, 1, '$now', '$now', '$now', '$now')";
$table_ts = $table."_ts";
echo "<br>$data_ts<br>";
insert_data($data, $table, $dbA_con);
insert_data($data_ts, $table_ts, $dbA_con);

$data = "(NULL, 'Hansen', 'Ola', 'Timoteivn 10', 'Sandnes')";
$data_ts ="(NULL, 2, '$now', '$now', '$now', '$now')";
echo "<br>$data_ts<br>";
insert_data($data, $table, $dbA_con);
insert_data($data_ts, $table_ts, $dbA_con);

sleep($secs);
sync_tables($dbA_cred, $buf_cred);
//echo '<br>s--------------------<br>';
$dbA_info = get_column_info($table, $dbA_con);
$buf_info = get_column_info($table, $buf_con);
echo "<br>dbA and buf column info:";

check_column_info($dbA_info, $buf_info);

$dbA_ts_info = get_column_info($table."_ts", $dbA_con);
$buf_ts_info = get_column_info($table."_ts", $buf_con);
check_column_info($dbA_ts_info, $buf_ts_info);
//echo '<br>e--------------------<br>';
$dbA_data = fetch_data($table, $dbA_con);
$buf_data = fetch_data($table, $buf_con);
check_data_match($dbA_data, $buf_data);

$dbA_ts_data = fetch_data($table."_ts", $dbA_con);
$buf_ts_data = fetch_data($table."_ts", $buf_con);
echo "<br><br>dbA_ts<br>";
check_not_null($dbA_ts_data);
echo "<br><br>buf_ts<br>";
check_not_null($buf_ts_data);

//End Test 1


//Test 2: tests inserted columns
echo "<br>TEST 2";
echo "<br>dbA<br>";
insert_column($table, 'NewColumn varchar(255)', $dbA_con);
insert_column($table, 'InsertedColumn int', $dbA_con, 'FirstName');
$U_now = time();
date_default_timezone_set("GMT");
$now = date("Y-m-d H:i:s", $U_now);
$sql1 = "UPDATE ".$table." SET InsertedColumn = 1, NewColumn='hi' WHERE P_Id=1";
$sql2 = "UPDATE ".$table." SET InsertedColumn = 2, NewColumn='ho' WHERE P_Id=2";
$sql3 = "UPDATE ".$table."_ts SET InsertedColumn = '$now', NewColumn='$now' WHERE P_Id=1";
$sql4 = "UPDATE ".$table."_ts SET InsertedColumn = '$now', NewColumn='$now' WHERE P_Id=2";
echo "<br>$sql1<br>$sql2<br>$sql3<br>$sql4<br>";
mysqli_query($dbA_con, $sql1);
mysqli_query($dbA_con, $sql2);
mysqli_query($dbA_con, $sql3);
mysqli_query($dbA_con, $sql4);

/*
Problem on dbsync_functions.php line 483. In this particular instance, columns P_Id 
and InsertedColumn in dbA table 'Persons' contain the same value and so array_search
returns the first instance--P_Id--both times, resulting in NULL values in column 
InsertedColumn in buf table 'Persons', and a zero timestamp in table 'Persons_ts'.
*/
sleep($secs);
sync_tables($dbA_cred, $buf_cred);

$dbA_info = get_column_info($table, $dbA_con);
$buf_info = get_column_info($table, $buf_con);
echo "<br>dbA and buf column info:";
check_column_info($dbA_info, $buf_info);


$dbA_columns = fetch_columns($table, $dbA_con);
$buf_columns = fetch_columns($table, $buf_con);
"<br>dbA:";
print_array($dbA_columns);
"<br>buf:";
print_array($dbA_columns);

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
$dbA_ts_data = fetch_data($table."_ts", $dbA_con);
$buf_ts_data = fetch_data($table."_ts", $buf_con);

check_data_match($dbA_data, $buf_data);
echo "<br><br>dbA_ts<br>";
check_not_null($dbA_ts_data);
echo "<br><br>buf_ts<br>";
check_not_null($buf_ts_data);
//End Test 3
/*
//Test 4: tests updated data again after alterations
echo "<br>TEST 4";
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