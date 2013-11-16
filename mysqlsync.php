<?php
/*
Plugin Name: MySQL Sync
Plugin URI:
Description:
Author: Paul Crompton
Author URI:
*/

// Specify Hooks/Filters
register_activation_hook(__FILE__, 'add_defaults_fn');
add_action('admin_init', 'mysqlsyncopt_init_fn' );
add_action('admin_menu', 'mysqlsyncopt_add_page_fn');

// Define default option settings
function add_defaults_fn() {
	$tmp = get_option('mysqlsync_options');
    if(($tmp['chkbox1']=='on')||(!is_array($tmp))) {
		$arr = array("dropdown1"=>"Orange", "text_area" => "Space to put a lot of information here!", "text_string" => "Some sample text", "pass_string" => "123456", "chkbox1" => "", "chkbox2" => "on", "option_set1" => "Triangle");
		update_option('mysqlsync_options', $arr);
	}
}

// Register our settings. Add the settings section, and settings fields
function mysqlsyncopt_init_fn(){
	
	register_setting('mysqlsync_options', 'mysqlsync_options', 'mysqlsync_options_validate' );
	add_settings_section('config_section', 'Configuration', '', __FILE__);
	add_settings_field('drop_down1', 'Choose "Host" or "Client"', 'setting_dropdown_fn', __FILE__, 'config_section');
	add_settings_field('mysqlsync_text_string', 'Username', 'setting_string_fn', __FILE__, 'config_section');
	add_settings_field('mysqlsync_text_pass', 'Password', 'setting_pass_fn', __FILE__, 'config_section');
	
}

// Add sub page to the Settings Menu
function mysqlsyncopt_add_page_fn() {
	add_options_page('MySQL Sync Configuration', 'MySQL Sync', 'administrator', __FILE__, 'options_page_fn');
}

// ************************************************************************************************************

// Callback functions

// Section HTML, displayed before the first option

function config_type_section_text_fn() {
	echo '<p>Choose "Host" or "Client" from the following dropdown menu</p>';

}
function  host_section_text_fn() {
	echo '<p>Create a username and password for your sync repository.<br>Give these credentials to anyone whom you wish to connect to you.</p>';
}

function  client_section_text_fn() {
	echo "<p>Enter the crendentials provided by the desired repository's host.</p>";
}


// DROP-DOWN-BOX - Name: mysqlsync_options[dropdown1]
function  setting_dropdown_fn() {
	$options = get_option('mysqlsync_options');
	$items = array("Host", "Client");
	echo "<select id='drop_down1' name='mysqlsync_options[dropdown1]'>";
	foreach($items as $item) {
		$selected = ($options['dropdown1']==$item) ? 'selected="selected"' : '';
		echo "<option value='$item' $selected>$item</option>";
	}
	echo "</select>";
}

function setting_block_fn() {
	
}

function sub_setting_field_fn() {
	$options = get_option('mysqlsync_options');
	setting_radio_fn();
	echo '<div id="sub_setting">';
		setting_string_fn();
		echo '<br>';
		setting_pass_fn();
	echo '</div>';
	

}

// TEXTAREA - Name: mysqlsync_options[text_area]
function setting_textarea_fn() {
	$options = get_option('mysqlsync_options');
	echo "<textarea id='mysqlsync_textarea_string' name='mysqlsync_options[text_area]' rows='7' cols='50' type='textarea'>{$options['text_area']}</textarea>";
}

// TEXTBOX - Name: mysqlsync_options[text_string]
function setting_string_fn() {
	$options = get_option('mysqlsync_options');
	echo "<input id='mysqlsync_text_string' name='mysqlsync_options[text_string]' size='40' type='text' value='{$options['text_string']}' />";
}

// PASSWORD-TEXTBOX - Name: mysqlsync_options[pass_string]
function setting_pass_fn() {
	$options = get_option('mysqlsync_options');
	echo "<input id='mysqlsync_text_pass' name='mysqlsync_options[pass_string]' size='40' type='password' value='{$options['pass_string']}' />";
}

// CHECKBOX - Name: mysqlsync_options[chkbox1]
function setting_chk1_fn() {
	$options = get_option('mysqlsync_options');
	if($options['chkbox1']) { $checked = ' checked="checked" '; }
	echo "<input ".$checked." id='mysqlsync_chk1' name='mysqlsync_options[chkbox1]' type='checkbox' />";
}

// CHECKBOX - Name: mysqlsync_options[chkbox2]
function setting_chk2_fn() {
	$options = get_option('mysqlsync_options');
	if($options['chkbox2']) { $checked = ' checked="checked" '; }
	echo "<input ".$checked." id='mysqlsync_chk2' name='mysqlsync_options[chkbox2]' type='checkbox' />";
}

// RADIO-BUTTON - Name: mysqlsync_options[option_set1]
function setting_radio_fn() {
	$options = get_option('mysqlsync_options');
	$items = array("Host", "Client");
	foreach($items as $item) {
		$checked = ($options['option_set1']==$item) ? ' checked="checked" ' : '';
		echo "<label><input ".$checked." id='radio1' value='$item' name='mysqlsync_options[option_set1]' type='radio' /> $item</label><br />";
		 
		
	}
}

// Display the admin options page
function options_page_fn() {
?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>My Example Options Page</h2>
		Some optional text here explaining the overall purpose of the options and what they relate to etc.
		<form action="options.php" method="post">
		<?php settings_fields('mysqlsync_options'); ?>
		<?php do_settings_sections(__FILE__); ?>
		<p class="submit">
			<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
		</p>
		</form>
	</div>
<?php
}

// Validate user data for some/all of your input fields
function mysqlsync_options_validate($input) {
	// Check our textbox option field contains no HTML tags - if so strip them out
	$input['text_string'] =  wp_filter_nohtml_kses($input['text_string']);	
	return $input; // return validated input
}