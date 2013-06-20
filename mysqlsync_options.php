<div class="wrap">
<?php screen_icon(); ?>
<h2>MySQL Sync</h2>

<form method="post" action="options.php">

<?php settings_fields( 'myoption-group' ); ?>
<!--
<h3>Local MySQL Server</h3>


<input type="radio" name="hostorconnect" onclick="sv.disabled=true;
												un.disabled=true;
												pw.disabled=true;
												hsv.disabled=false;
												hun.disabled=false;
												hpw.disabled=false;
											"/>
HOST
<br>
												
Host Server Name: <input type="text" name="hsv"><br>
Create Host User Name: <input type="text" name="hun"><br>
Create Host Password: <input type="text" name="hpw"><br>
<input type="submit" value="Create">
<br>
<br>
<input type="radio" name="hostorconnect" onclick="sv.disabled=false;
												un.disabled=false;
												pw.disabled=false;
												hsv.disabled=true;
												hun.disabled=true;
												hpw.disabled=true;
											"/>
CONNECT
<br>
Host Server Name: <input type="text" name="sv"><br>
Host MySQL User Name: <input type="text" name="un"><br>
Host MySQL Password: <input type="text" name="pw"><br>
<input type="submit" value="Connect">	
<br>
-->
<?php submit_button(); ?>
</form>
</div>