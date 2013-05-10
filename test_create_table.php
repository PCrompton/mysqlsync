<?php

require "dbsync_credentials.php";
require "dbsync_functions.php";
require "test_dbsync_functions.php";

$con = create_connection($dbA_cred);
$table = 'table';
reset_table($con, $dbA_cred, $table);
for ($i=0; $i<5; $i++) {
	reset_table($con, $dbA_cred, $table.$i);
}

//Test 1

$columns = array('col1 int', 'col2 varchar(255)', 'col3 varchar(255)');
create_table('table1', $con, $columns);
create_table('table2', $con, $columns, 'col1');
create_table('table3', $con, $columns, 'col0');
$columns = array('col1 varchar(255)', 'col2 varchar(255)', 'col3 varchar(255)');
create_table('table4', $con, $columns, 'col1');



for ($i = 1; $i<5; $i++) {
	echo 'table'.$i."<br>";
	$columns = get_column_info('table'.$i, $con);
	foreach ($columns as $column) {
		echo 'Field: '.$column['Field']."<br>";
		echo 'Type: '.$column['Type']."<br>";
		echo 'Null: '.$column['Null']."<br>";
		echo 'Key: '.$column['Key']."<br><br>";
	
	}
}
mysqli_close($con);
	
?>