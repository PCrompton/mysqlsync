<?php
require "links.html";
require_once 'dbsync_functions.php';
require_once 'input_credentials.php';
require_once 'test_dbsync_functions.php';

$choice = $_POST['choice'];
$table = $_POST['table'];
$row = $_POST['row'];
$column = $_POST['column'];
if ($choice == 1) {
	$db_value = $_POST['db1_value'];
	$db1 = $_POST['db2'];
	$db2 = $_POST['db1'];
}
elseif ($choice == 2) {
	$db_value = $_POST['db2_value'];
	$db1 = $_POST['db1'];
	$db2 = $_POST['db2'];

}

$db1_cred = $input_cred;
array_push($db1_cred, $db1);
$db2_cred = $input_cred;
array_push($db2_cred, $db2);

$db1_con = create_connection($db1_cred);
$db2_con = create_connection($db2_cred);
$pk = get_primary_key($table, $db1_con);
$query = "UPDATE $table SET $column='$db_value' WHERE $pk=$row";
mysqli_query($db1_con, $query);

sync_databases($db1_cred, $db2_cred);
test_instance($table, $db1_con, $db2_con);
resolve_conflicts($db1_cred, $db2_cred, $conflicts);
mysqli_close($db1_con);
mysqli_close($db2_con);
require "links.html";
?>
