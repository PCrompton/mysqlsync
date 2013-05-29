<h4>Please Select a Test Set:</h4>
<?php
require "links.html";
?>
<br>
<h3>mysqlsync<br>
=========</h3><br>

Syncs MySQL databases<br><br>

Requirements: <br>
-MySQL 5.6 (previous versions untested)<br>
-PHP 5.3 (previous versions untested)<br><br>

Recommended:<br>
-phpmyadmin 3.5<br><br>

Instructions:<br><br>

1. Download mysqlsync and place it on your local server.<br>
2. In your browser's address bar, type localhost/mysqlsync (or whatever the filename may be).<br>
3. Open 'input_credentials.php' with a text editor and replace the following strings with your own mysql credentials:<br>
	$un = 'root';<br>
	$pw = 'root';<br>
	$srv = 'localhost';<br>
4. (optional) log in to phpmyadmin before and after running the script to see changes more clearly<br><br>
