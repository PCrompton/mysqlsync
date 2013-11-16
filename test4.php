<?php
require "links.html";

require_once 'input_credentials.php';
require_once 'dbsync_functions.php';
require_once 'test_dbsync_functions.php';
echo "\n <br>";
$input_con = create_connection($input_cred);

$dbs = array($dbA, $dbB, $dbC, $buf);
echo "<h3>TESTS dbA, dbB and dbC SYNC THROUGH BUF</h3><br>";
reset_dbs($dbs, $input_con);

$dbA_con = create_connection($dbA_cred);
$dbB_con = create_connection($dbB_cred);
$dbC_con = create_connection($dbC_cred);
$buf_con = create_connection($buf_cred);
//*

//INSTANCE 1:
$table = 'persons';
$secs = 1;
echo "<br>INSTANCE 1: tests sync of newly configured dbA to empty buf and then buf to dbB<br>";


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
echo "<br>dbB pre-sync verification<br>";
verify_instance(array(), array(), array(), $dbB_cred);
echo "<br>dbC pre-sync verification<br>";
verify_instance(array(), array(), array(), $dbC_cred);
echo "<br>buf pre-sync verification<br>";
verify_instance(array(), array(), array(), $buf_cred);


sync_databases($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);
sync_databases($buf_cred, $dbB_cred);
test_instance($table, $buf_con, $dbB_con);
sync_databases($buf_cred, $dbC_cred);
test_instance($table, $buf_con, $dbC_con);

echo "<br>dbA post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);
echo "<br>dbB post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbB_cred);
echo "<br>dbC post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbC_cred);
echo "<br>buf post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);

//End INSTANCE 1

//*

//INSTANCE 2: tests inserted columns
echo "<br>INSTANCE 2: tests sync of inserted columns in dbA to previously sync'd buf and then buf to dbB<br>";
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
sync_databases($buf_cred, $dbB_cred);
test_instance($table, $dbB_con, $buf_con);
sync_databases($buf_cred, $dbC_cred);
test_instance($table, $dbC_con, $buf_con);
echo "<br>dbA post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);
echo "<br>dbB post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbB_cred);
echo "<br>dbC post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbC_cred);
echo "<br>buf post-sync verification<br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);

//End INSTANCE 2



//INSTANCE 3
echo "<br>INSTANCE 3: tests sync of different updated data in different columns in dbA, dbB and dbC<br>";
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
mysqli_query($dbB_con, $sql5);
mysqli_query($dbB_con, $sql6);
mysqli_query($dbB_con, $sql7);
mysqli_query($dbB_con, $sql8);

//Expected parameters dbB pre-sync
$exp_data1_dbB = $exp_data1;
$exp_data2_dbB = $exp_data2;
$exp_ts_data1_dbB = $exp_ts_data1;
$exp_ts_data2_dbB = $exp_ts_data2;
$exp_data1_dbB['LastName'] = 'Ortiz';
$exp_data2_dbB['LastName'] = 'Pedroia';
$exp_data1_dbB['FirstName'] = 'David';
$exp_data2_dbB['FirstName'] = 'Dustin';

$exp_ts_data1_dbB['LastName'] = $now3;
$exp_ts_data2_dbB['LastName'] = $now4;
$exp_ts_data1_dbB['FirstName'] = $now3;
$exp_ts_data2_dbB['FirstName'] = $now4;

$exp_data = array($exp_data1_dbB, $exp_data2_dbB);
$exp_ts_data = array($exp_ts_data1_dbB, $exp_ts_data2_dbB);

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
echo "<br>dbB pre-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbB_cred);



$U_now += 5;
date_default_timezone_set("GMT");
$now5 = date("Y-m-d H:i:s", $U_now);
$U_now += 5;
date_default_timezone_set("GMT");
$now6 = date("Y-m-d H:i:s", $U_now);
$sql5 = "UPDATE ".$table." SET Address = '4 Yawkey Way', City='Boston, MA' WHERE P_Id=1";
$sql6 = "UPDATE ".$table." SET Address = '4 Yawkey Way', City='Boston, MA' WHERE P_Id=2";
$sql7 = "UPDATE ".$table."_ts SET Address = '$now5', City='$now5' WHERE P_Id=1";
$sql8 = "UPDATE ".$table."_ts SET Address = '$now6', City='$now6' WHERE P_Id=2";
mysqli_query($dbC_con, $sql5);
mysqli_query($dbC_con, $sql6);
mysqli_query($dbC_con, $sql7);
mysqli_query($dbC_con, $sql8);

//Expected parameters dbC pre-sync
$exp_data1_dbC = $exp_data1;
$exp_data2_dbC = $exp_data2;
$exp_ts_data1_dbC = $exp_ts_data1;
$exp_ts_data2_dbC = $exp_ts_data2;
$exp_data1_dbC['Address'] = '4 Yawkey Way';
$exp_data2_dbC['Address'] = '4 Yawkey Way';
$exp_data1_dbC['City'] = 'Boston, MA';
$exp_data2_dbC['City'] = 'Boston, MA';

$exp_ts_data1_dbC['Address'] = $now5;
$exp_ts_data2_dbC['Address'] = $now6;
$exp_ts_data1_dbC['City'] = $now5;
$exp_ts_data2_dbC['City'] = $now6;

$exp_data = array($exp_data1_dbC, $exp_data2_dbC);
$exp_ts_data = array($exp_ts_data1_dbC, $exp_ts_data2_dbC);

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
echo "<br>dbC pre-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbC_cred);


//Expected parameters

$exp_data1['InsertedColumn'] = '3';
$exp_data2['InsertedColumn'] = '4';
$exp_data1['NewColumn'] = 'ho';
$exp_data2['NewColumn'] = 'hum';
$exp_data1['LastName'] = 'Ortiz';
$exp_data2['LastName'] = 'Pedroia';
$exp_data1['FirstName'] = 'David';
$exp_data2['FirstName'] = 'Dustin';
$exp_data1['Address'] = '4 Yawkey Way';
$exp_data2['Address'] = '4 Yawkey Way';
$exp_data1['City'] = 'Boston, MA';
$exp_data2['City'] = 'Boston, MA';

$exp_ts_data1['InsertedColumn'] = $now1;
$exp_ts_data2['InsertedColumn'] = $now2;
$exp_ts_data1['NewColumn'] = $now1;
$exp_ts_data2['NewColumn'] = $now2;
$exp_ts_data1['LastName'] = $now3;
$exp_ts_data2['LastName'] = $now4;
$exp_ts_data1['FirstName'] = $now3;
$exp_ts_data2['FirstName'] = $now4;
$exp_ts_data1['Address'] = $now5;
$exp_ts_data2['Address'] = $now6;
$exp_ts_data1['City'] = $now5;
$exp_ts_data2['City'] = $now6;

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

//all to buf
sync_databases($dbA_cred, $buf_cred);
test_instance($table, $dbA_con, $buf_con);
sync_databases($dbB_cred, $buf_cred);
test_instance($table, $dbB_con, $buf_con);
sync_databases($dbC_cred, $buf_cred);
test_instance($table, $dbC_con, $buf_con);

//buf to all
sync_databases($buf_cred, $dbA_cred);
test_instance($table, $buf_con, $dbA_con);
sync_databases($buf_cred, $dbB_cred);
test_instance($table, $buf_con, $dbB_con);
sync_databases($buf_cred, $dbC_cred);
test_instance($table, $buf_con, $dbC_con);

echo "<br>dbA post-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbA_cred);
echo "<br>buf post-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $buf_cred);
echo "<br>dbB post-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbB_cred);
echo "<br>dbC post-sync verifications: <br>";
verify_instance($exp_tables, $exp_cols_array, $exp_data_array, $dbC_cred);

//End INSTANCE 3


mysqli_close($dbA_con);
mysqli_close($dbB_con);
mysqli_close($buf_con);
require "links.html";
?>
