mysqlsync
=========

Syncs MySQL databases

Requirements: 
-MySQL 5.6 (previous versions untested)
-PHP 5.3 (previous versions untested)

Recommended:
-phpmyadmin 3.5

Instructions:

1. Download mysqlsync and place it on your local server.
2. In your browser's address bar, type localhost/mysqlsync (or whatever the filename may be).
3. Open 'input_credentials.php' with a text editor and replace the following strings with your own mysql credentials:
	$un = 'root';
	$pw = 'root';
	$srv = 'localhost';
4. (optional) log in to phpmyadmin before and after running the script to see changes more clearly 
