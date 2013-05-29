<?php
require_once 'input_credentials.php';
require_once 'dbsync_functions.php';
require_once 'test_dbsync_functions.php';
echo "\n <br>";
$input_con = create_connection($input_cred);

$dbs = array($dbA, $dbB, $buf);
echo "<br><h3>INITIAL TEST SET</h3><br>";
reset_dbs($dbs, $input_con);

$dbA_con = create_connection($dbA_cred);
$dbB_con = create_connection($dbB_cred);
$buf_con = create_connection($buf_cred);


require "test1.php";


global $conflicts;
resolve_conflicts($dbA_cred, $buf_cred, $conflicts);


echo "\n <br>";
$input_con = create_connection($input_cred);

$dbs = array($dbA, $dbB, $buf);
echo "<br><h3>SAME TESTS BUT DATABASES SWAPPED IN sync_databases() PARAMETERS</h3><br>";
reset_dbs($dbs, $input_con);
$conflicts = array();
$dbA_con = create_connection($dbA_cred);
$dbB_con = create_connection($dbB_cred);
$buf_con = create_connection($buf_cred);

require "test2.php";

global $conflicts;
resolve_conflicts($dbA_cred, $buf_cred, $conflicts);
?>