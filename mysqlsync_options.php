<div class="wrap">
<?php screen_icon(); ?>
<h2>MySQL Sync</h2>

<form method="post" action="options.php">

<?php settings_fields( 'myoption-group' ); ?>
<?php do_settings_fields( 'myoption-group', '' );?>

<?php submit_button(); ?>
</form>
</div>