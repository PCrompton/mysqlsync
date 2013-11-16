<?php
require "input_credential.php";
require "dbsync_functions.php";

function is_newer_test() {
	$inputs = array(
		array(
			"2000-01-01 00:00:00",
			"2000-01-01 00:00:00"),
		array(
			"2000-01-01 00:00:00",
			"2000-01-01 00:00:01")
	);
	
	foreach ($inputs as $input) {
		
		$res1 = call_user_func_array('is_newer', $input);
		$res2 = call_user_func_array('is_newer', array_reverse($input));
		
		if ($input[0] === $input[1]) {
			assert($res1 == false);
			assert($res2 == false);
		}
		else {
			assert($res1 == false);
			assert($res2 == true);
		}
	}
	echo "function is_newer test complete \n";
}




is_newer_test();

?>