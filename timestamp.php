<?php

require "dbsync_functions.php";
require "input_credentials.php";


$br = '<br>';

date_default_timezone_set("America/New_York");
/*
echo mktime(3,30,0,3,3,2003),"<br>";
echo mktime(3,30,1,3,3,2003), "<br>";
echo date_default_timezone_get();
*/
/*
$tables = fetch_ts_tables($dbA_cred);
$con = create_connection($dbA_cred);
$data = fetch_data($tables[0], $con, 'LastName');
$timestamp1 = $data[0]['LastName'];
$timestamp2 = $data[1]['LastName'];
echo '<br>',$timestamp1;
echo '<br>',$timestamp2;
$U_timestamp1 = convert_timestamp($timestamp1);
$U_timestamp2 = convert_timestamp($timestamp2);
echo '<br>',$U_timestamp1;
echo '<br>',$U_timestamp2;
echo $br.is_newer($U_timestamp1, $U_timestamp2);
echo $br.is_newer($U_timestamp2, $U_timestamp1);
*/


is_newer('Hi', 'ho');
?>