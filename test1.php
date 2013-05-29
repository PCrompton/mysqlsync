<?php
require "links.html";

require_once 'input_credentials.php';
require_once 'dbsync_functions.php';
require_once 'test_dbsync_functions.php';
echo "\n <br>";
$input_con = create_connection($input_cred);

$dbs = array($dbA, $dbB, $buf);
echo "<h3>INITIAL TEST SET</h3><br>";
reset_dbs($dbs, $input_con);

$dbA_con = create_connection($dbA_cred);
$dbB_con = create_connection($dbB_cred);
$buf_con = create_connection($buf_cred);

//*
//INSTANCE 1:
$table = 'Persons';
$secs = 1;
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

$U_now = time();
date_default_timezone_set("GMT");
$now1 = date("Y-m-d H:i:s", $U_now);
$U_now += 5;
date_default_timezone_set("GMT");
$now2 = date("Y-m-d H:i:s", $U_now);

$data1 = "(1, 'Nilsen', 'Johan', 'Bakken 2', 'Stavanger')";
insert_data($data1, $table, $dbA_con);
$data1_ts = "(1, 1, '$now1', '$now1', '$now1', '$now1')";
insert_data($data1_ts, $table."_ts", $dbA_con);

$data2 = "(2, 'Hansen', 'Ola', 'Timoteivn 10', 'Sandnes')";
insert_data($data2, $table, $dbA_con);
$data2_ts = "(2, 2, '$now2', '$now2', '$now2', '$now2')";
insert_data($data2_ts, $table."_ts", $dbA_con);

//Expected parameters
$exp_cols = array(
	'P_Id int(11)', 
	'LastName varchar(255)', 
	'FirstName varchar(255)', 
	'Address varchar(255)', 
	'City varchar(255)'
);
$exp_ts_cols = array(
	'P_Id_ts int(11)', 
	'P_Id int(11)', 
	'LastName datetime', 
	'FirstName datetime', 
	'Address datetime', 
	'City datetime'
);	
$exp_data1 = array(
	'P_Id' => '1',
	'LastName' => 'Nilsen', 
	'FirstName' => 'Johan', 
	'Address' => 'Bakken 2', 
	'City' => 'Stavanger'
);
$exp_data2 = array(
	'P_Id' => '2', 
	'LastName' => 'Hansen', 
	'FirstName' => 'Ola', 
	'Address' => 'Timoteivn 10', 
	'City' => 'Sandnes'
);
$exp_ts_data1 = array(
	'P_Id_ts' => '1', 
	'P_Id' => '1', 
	'LastName' => $now1,
	'FirstName' => $now1, 
	'Address' => $now1, 
	'City' => $now1
);
$exp_ts_data2 = array(
	'P_Id_ts' => '2', 
	'P_Id' => '2', 
	'LastName' => $now2,
	'FirstName' => $now2, 
	'Address' => $now2, 
	'City' => $now2
);
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

//End expected parameters
echo "<br>dbA pre-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);
echo "<br>buf pre-sync verification<br>";
verify_instance(array(), array(), array(), $buf_cred);

sync_databases($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);

echo "<br>dbA post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);

echo "<br>buf post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);
//End INSTANCE 1

//*

//INSTANCE 2: tests inserted columns
echo "<br>INSTANCE 2: tests sync of inserted columns in dbA to previously sync'd buf<br>";
insert_column($table, 'NewColumn varchar(255)', $dbA_con);
insert_column($table, 'InsertedColumn int(11)', $dbA_con, 'FirstName');

$U_now += 5;
date_default_timezone_set("GMT");
$now1 = date("Y-m-d H:i:s", $U_now);
$U_now += 5;
date_default_timezone_set("GMT");
$now2 = date("Y-m-d H:i:s", $U_now);
$sql1 = "UPDATE ".$table." SET InsertedColumn = 1, NewColumn='hi' WHERE P_Id=1";
$sql2 = "UPDATE ".$table." SET InsertedColumn = 2, NewColumn='ho' WHERE P_Id=2";
$sql3 = "UPDATE ".$table."_ts SET InsertedColumn = '$now1', NewColumn='$now1' WHERE P_Id=1";
$sql4 = "UPDATE ".$table."_ts SET InsertedColumn = '$now2', NewColumn='$now2' WHERE P_Id=2";
mysqli_query($dbA_con, $sql1);
mysqli_query($dbA_con, $sql2);
mysqli_query($dbA_con, $sql3);
mysqli_query($dbA_con, $sql4);

//Expected parameters
array_splice($exp_cols, 3, 0, 'InsertedColumn int(11)');
array_push($exp_cols, 'NewColumn varchar(255)');

array_splice($exp_ts_cols, 4, 0, 'InsertedColumn datetime');
array_push($exp_ts_cols, 'NewColumn datetime');

$exp_data1['InsertedColumn'] = '1';
$exp_data2['InsertedColumn'] = '2';
$exp_data1['NewColumn'] = 'hi';
$exp_data2['NewColumn'] = 'ho';

$exp_ts_data1['InsertedColumn'] = $now1;
$exp_ts_data2['InsertedColumn'] = $now2;
$exp_ts_data1['NewColumn'] = $now1;
$exp_ts_data2['NewColumn'] = $now2;

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

//End expected parameters

echo "<br>dbA pre-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);
sync_databases($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);

echo "<br>dbA post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);
echo "<br>buf post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);
//End INSTANCE 2



//INSTANCE 3: tests updated data again after alterations
echo "<br>INSTANCE 3: tests sync of updated data in dbA to previously sync'd buf<br>";
$U_now += 5;
date_default_timezone_set("GMT");
$now1 = date("Y-m-d H:i:s", $U_now);
$U_now += 5;
date_default_timezone_set("GMT");
$now2 = date("Y-m-d H:i:s", $U_now);
$sql5 = "UPDATE ".$table." SET InsertedColumn = 3, NewColumn='ho' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET InsertedColumn = 4, NewColumn='hum' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET InsertedColumn = '$now1', NewColumn='$now1' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET InsertedColumn = '$now2', NewColumn='$now2' WHERE P_Id=2";
mysqli_query($dbA_con, $sql5);
mysqli_query($dbA_con, $sql6);
mysqli_query($dbA_con, $sql7);
mysqli_query($dbA_con, $sql8);

//Expected parameters

$exp_data1['InsertedColumn'] = '3';
$exp_data2['InsertedColumn'] = '4';
$exp_data1['NewColumn'] = 'ho';
$exp_data2['NewColumn'] = 'hum';

$exp_ts_data1['InsertedColumn'] = $now1;
$exp_ts_data2['InsertedColumn'] = $now2;
$exp_ts_data1['NewColumn'] = $now1;
$exp_ts_data2['NewColumn'] = $now2;

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


//End expected parameters
echo "<br>dbA pre-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);

sync_databases($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);

echo "<br>dbA post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);
echo "<br>buf post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);
//End INSTANCE 3




//INSTANCE 4
echo "<br>INSTANCE 4: tests sync of updated data in buf to previously sync'd dbA <br>";
$U_now += 5;
date_default_timezone_set("GMT");
$now1 = date("Y-m-d H:i:s", $U_now);
$U_now += 5;
date_default_timezone_set("GMT");
$now2 = date("Y-m-d H:i:s", $U_now);
$sql1 = "UPDATE ".$table." SET InsertedColumn = 1, NewColumn='hi' WHERE P_Id=1";
$sql2 = "UPDATE ".$table." SET InsertedColumn = 2, NewColumn='ho' WHERE P_Id=2";
$sql3 = "UPDATE ".$table."_ts SET InsertedColumn = '$now1', NewColumn='$now1' WHERE P_Id=1";
$sql4 = "UPDATE ".$table."_ts SET InsertedColumn = '$now2', NewColumn='$now2' WHERE P_Id=2";
mysqli_query($buf_con, $sql1);
mysqli_query($buf_con, $sql2);
mysqli_query($buf_con, $sql3);
mysqli_query($buf_con, $sql4);

//Expected parameters
$exp_data1['InsertedColumn'] = '1';
$exp_data2['InsertedColumn'] = '2';
$exp_data1['NewColumn'] = 'hi';
$exp_data2['NewColumn'] = 'ho';

$exp_ts_data1['InsertedColumn'] = $now1;
$exp_ts_data2['InsertedColumn'] = $now2;
$exp_ts_data1['NewColumn'] = $now1;
$exp_ts_data2['NewColumn'] = $now2;

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
//End expected parameters

echo "<br>dbA pre-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);

sync_databases($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);

echo "<br>dbA post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);
echo "<br>buf post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);
//End INSTANCE 4




//*
//INSTANCE 5
echo "<br>INSTANCE 5: tests sync of different updated data in different columns in both dbA and buf<br>";
$U_now += 5;
date_default_timezone_set("GMT");
$now1 = date("Y-m-d H:i:s", $U_now);
$U_now += 5;
date_default_timezone_set("GMT");
$now2 = date("Y-m-d H:i:s", $U_now);
$sql5 = "UPDATE ".$table." SET InsertedColumn = 3, NewColumn='ho' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET InsertedColumn = 4, NewColumn='hum' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET InsertedColumn = '$now1', NewColumn='$now1' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET InsertedColumn = '$now2', NewColumn='$now2' WHERE P_Id=2";
mysqli_query($dbA_con, $sql5);
mysqli_query($dbA_con, $sql6);
mysqli_query($dbA_con, $sql7);
mysqli_query($dbA_con, $sql8);

//Expected parameters dbA pre-sync
$exp_data1_dbA = $exp_data1;
$exp_data2_dbA = $exp_data2;
$exp_ts_data1_dbA = $exp_ts_data1;
$exp_ts_data2_dbA = $exp_ts_data2;
$exp_data1_dbA['InsertedColumn'] = '3';
$exp_data2_dbA['InsertedColumn'] = '4';
$exp_data1_dbA['NewColumn'] = 'ho';
$exp_data2_dbA['NewColumn'] = 'hum';

$exp_ts_data1_dbA['InsertedColumn'] = $now1;
$exp_ts_data2_dbA['InsertedColumn'] = $now2;
$exp_ts_data1_dbA['NewColumn'] = $now1;
$exp_ts_data2_dbA['NewColumn'] = $now2;

$exp_data = array($exp_data1_dbA, $exp_data2_dbA);
$exp_ts_data = array($exp_ts_data1_dbA, $exp_ts_data2_dbA);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

//End expected parameters
echo "<br>dbA pre-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);

$U_now += 5;
date_default_timezone_set("GMT");
$now3 = date("Y-m-d H:i:s", $U_now);
$U_now += 5;
date_default_timezone_set("GMT");
$now4 = date("Y-m-d H:i:s", $U_now);
$sql5 = "UPDATE ".$table." SET LastName = 'Ortiz', FirstName='David' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET LastName = 'Pedroia', FirstName='Dustin' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET LastName = '$now3', FirstName='$now3' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET LastName = '$now4', FirstName='$now4' WHERE P_Id=2";
mysqli_query($buf_con, $sql5);
mysqli_query($buf_con, $sql6);
mysqli_query($buf_con, $sql7);
mysqli_query($buf_con, $sql8);

//Expected parameters buf pre-sync
$exp_data1_buf = $exp_data1;
$exp_data2_buf = $exp_data2;
$exp_ts_data1_buf = $exp_ts_data1;
$exp_ts_data2_buf = $exp_ts_data2;
$exp_data1_buf['LastName'] = 'Ortiz';
$exp_data2_buf['LastName'] = 'Pedroia';
$exp_data1_buf['FirstName'] = 'David';
$exp_data2_buf['FirstName'] = 'Dustin';

$exp_ts_data1_buf['LastName'] = $now3;
$exp_ts_data2_buf['LastName'] = $now4;
$exp_ts_data1_buf['FirstName'] = $now3;
$exp_ts_data2_buf['FirstName'] = $now4;

$exp_data = array($exp_data1_buf, $exp_data2_buf);
$exp_ts_data = array($exp_ts_data1_buf, $exp_ts_data2_buf);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);
//End expected parameters
echo "<br>buf pre-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);

//Expected parameters

$exp_data1['InsertedColumn'] = '3';
$exp_data2['InsertedColumn'] = '4';
$exp_data1['NewColumn'] = 'ho';
$exp_data2['NewColumn'] = 'hum';
$exp_data1['LastName'] = 'Ortiz';
$exp_data2['LastName'] = 'Pedroia';
$exp_data1['FirstName'] = 'David';
$exp_data2['FirstName'] = 'Dustin';

$exp_ts_data1['InsertedColumn'] = $now1;
$exp_ts_data2['InsertedColumn'] = $now2;
$exp_ts_data1['NewColumn'] = $now1;
$exp_ts_data2['NewColumn'] = $now2;
$exp_ts_data1['LastName'] = $now3;
$exp_ts_data2['LastName'] = $now4;
$exp_ts_data1['FirstName'] = $now3;
$exp_ts_data2['FirstName'] = $now4;

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
//End expected parameters


echo "<br>Instance 5 test: <br>";
sync_databases($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);

echo "<br>dbA post-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);
echo "<br>buf post-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);

//End INSTANCE 5








//INSTANCE 6
echo "<br>INSTANCE 6: tests sync of different updated data in same columns in both dbA and buf<br>";
$U_now += 5;
date_default_timezone_set("GMT");
$now1 = date("Y-m-d H:i:s", $U_now);
$U_now += 5;
date_default_timezone_set("GMT");
$now2 = date("Y-m-d H:i:s", $U_now);
$sql5 = "UPDATE ".$table." SET LastName = 'Ellsbury', FirstName='Jacoby' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET LastName = 'Victorino', FirstName='Shane' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET LastName = '$now1', FirstName='$now1' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET LastName = '$now2', FirstName='$now2' WHERE P_Id=2";
mysqli_query($dbA_con, $sql5);
mysqli_query($dbA_con, $sql6);
mysqli_query($dbA_con, $sql7);
mysqli_query($dbA_con, $sql8);

//Expected parameters dbA pre-sync
$exp_data1_dbA = $exp_data1;
$exp_data2_dbA = $exp_data2;
$exp_ts_data1_dbA = $exp_ts_data1;
$exp_ts_data2_dbA = $exp_ts_data2;
$exp_data1_dbA['LastName'] = 'Ellsbury';
$exp_data2_dbA['LastName'] = 'Victorino';
$exp_data1_dbA['FirstName'] = 'Jacoby';
$exp_data2_dbA['FirstName'] = 'Shane';

$exp_ts_data1_dbA['LastName'] = $now1;
$exp_ts_data2_dbA['LastName'] = $now2;
$exp_ts_data1_dbA['FirstName'] = $now1;
$exp_ts_data2_dbA['FirstName'] = $now2;

$exp_data = array($exp_data1_dbA, $exp_data2_dbA);
$exp_ts_data = array($exp_ts_data1_dbA, $exp_ts_data2_dbA);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);

//End expected parameters 

echo "<br>dbA pre-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);

$now4 = date("Y-m-d H:i:s", $U_now);
$sql5 = "UPDATE ".$table." SET LastName = 'Drew', FirstName='Stephen' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET LastName = 'Middlebrooks', FirstName='Will' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET LastName = '$now1', FirstName='$now1' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET LastName = '$now2', FirstName='$now2' WHERE P_Id=2";
mysqli_query($buf_con, $sql5);
mysqli_query($buf_con, $sql6);
mysqli_query($buf_con, $sql7);
mysqli_query($buf_con, $sql8);

//Expected parameters buf pre-sync
$exp_data1_buf = $exp_data1;
$exp_data2_buf = $exp_data2;
$exp_ts_data1_buf = $exp_ts_data1;
$exp_ts_data2_buf = $exp_ts_data2;
$exp_data1_buf['LastName'] = 'Drew';
$exp_data2_buf['LastName'] = 'Middlebrooks';
$exp_data1_buf['FirstName'] = 'Stephen';
$exp_data2_buf['FirstName'] = 'Will';

$exp_ts_data1_buf['LastName'] = $now1;
$exp_ts_data2_buf['LastName'] = $now2;
$exp_ts_data1_buf['FirstName'] = $now1;
$exp_ts_data2_buf['FirstName'] = $now2;

$exp_data = array($exp_data1_buf, $exp_data2_buf);
$exp_ts_data = array($exp_ts_data1_buf, $exp_ts_data2_buf);

$exp_tables = array($table, $table."_ts");
$exp_cols_array = array(
	$table => $exp_cols,
	$table."_ts" => $exp_ts_cols
);
$exp_data_array = array(
	$table => $exp_data,
	$table."_ts" => $exp_ts_data
);
//End expected parameters
echo "<br>buf pre-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);

//Expected parameters post-sync

//End expected parameters
echo "<br>Instance 6 test: <br>";
sync_databases($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);
//End INSTANCE 6
global $conflicts;
resolve_conflicts($dbA_cred, $buf_cred, $conflicts);
mysqli_close($dbA_con);
mysqli_close($dbB_con);
mysqli_close($buf_con);
require "links.html";
?>
