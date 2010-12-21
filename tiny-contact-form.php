<?php
/*
Plugin Name: Tiny Contact Form
Plugin URI: http://www.tomsdimension.de/wp-plugins/tiny-contact-form
Description: Little form that allows site visitors to contact you. Use [TINY-CONTACT-FORM] within any post or page.
Author: Tom Braider
Author URI: http://www.tomsdimension.de
Version: 0.7
*/

$tcf_version = '0.7';
$tcf_script_printed = 0;
$tiny_contact_form = new TinyContactForm();

class TinyContactForm
{

var $o; // options
var $captcha;
var $userdata;
var $nr = 0; // form number to use more then once forms/widgets


/**
 * Constructor
 */	
function TinyContactForm()
{
	// get options from DB
	$this->o = get_option('tiny_contact_form');
	// widget
	add_action('widgets_init', array( &$this, 'register_widgets'));
	// options page in menu
	add_action('admin_menu', array( &$this, 'addOptionsPage'));
	// shortcode
	add_shortcode('TINY-CONTACT-FORM', array( &$this, 'shortcode'));
	// add stylesheet
	add_action('wp_head', array( &$this, 'addStyle'));
	// uninstall function
	if ( function_exists('register_uninstall_hook') )
		register_uninstall_hook(ABSPATH.PLUGINDIR.'/tiny-contact-form/tiny-contact-form.php', array( &$this, 'uninstall')); 
	// settingslink on plugin page
	add_filter('plugin_action_links', array( &$this, 'pluginActions'), 10, 2);
	// add locale support
	if (defined('WPLANG') && function_exists('load_plugin_textdomain'))
		load_plugin_textdomain('tcf-lang', false, 'tiny-contact-form/locale');
	// creates image recources
	$this->setRecources();
}

/**
 * creates tcf code
 *
 * @return string form code
 */
function showForm( $params = '' )
{
	$n = ($this->nr == 0) ? '' : $this->nr;
	$this->nr++;

	if ( isset($_POST['tcf_sender'.$n]) )
		$result = $this->sendMail( $n, $params );
		
	$captcha = new TinyContactFormCaptcha( rand(1000000000, 9999999999) );
	
	$form = '<div class="contactform" id="tcform'.$n.'">';
	
	if ( !empty($result) )
	{
		if ( $result == $this->o['msg_ok'] )
			// mail successfully sent, no form
			$form .= '<p class="contactform_respons">'.$result.'</p>';
		else
			// error message
			$form .= '<p class="contactform_error">'.$result.'</p>';
	}
		
	if ( empty($result) || (!empty($result) && !$this->o['hideform']) )
	{
		// subject from form
		if ( !empty($_POST['tcf_subject'.$n]) )
			$tcf_subject = $_POST['tcf_subject'.$n];
		// subject from widget instance
		else if ( is_array($params) && !empty($params['subject']))
			$tcf_subject = $params['subject'];
		// subject from URL
		else if ( empty($_POST['tcf_subject'.$n]) && !empty($_GET['subject']) )
			$tcf_subject = $_GET['subject'];
		// subject from shortcode
		else if ( empty($_POST['tcf_subject'.$n]) && !empty($this->userdata['subject']) )
			$tcf_subject = $this->userdata['subject'];
		else
			$tcf_subject = '';
			
		$tcf_sender = (isset($_POST['tcf_sender'.$n])) ? $_POST['tcf_sender'.$n] : ''; 
		$tcf_email = (isset($_POST['tcf_email'.$n])) ? $_POST['tcf_email'.$n] : '';
		$tcf_msg = (isset($_POST['tcf_msg'.$n])) ? $_POST['tcf_msg'.$n] : '';
		
		$form .= '
			<form action="#tcform'.$n.'" method="post" id="tinyform'.$n.'">
			<div>
			<input name="tcf_name'.$n.'" id="tcf_name'.$n.'" value="" class="tcf_input" />
			<input name="tcf_sendit'.$n.'" id="tcf_sendit'.$n.'" value="1" class="tcf_input" />
			<label for="tcf_sender'.$n.'" class="tcf_label">'.__('Name', 'tcf-lang').':</label>
			<input name="tcf_sender'.$n.'" id="tcf_sender'.$n.'" size="30" value="'.$tcf_sender.'" class="tcf_field" />
			<label for="tcf_email'.$n.'" class="tcf_label">'.__('Email', 'tcf-lang').':</label>
			<input name="tcf_email'.$n.'" id="tcf_email'.$n.'" size="30" value="'.$tcf_email.'" class="tcf_field" />';
		// additional fields
		for ( $x = 1; $x <=5; $x++ )
		{
			$i = 'tcf_field_'.$x.$n;
			$tcf_f = (isset($_POST[$i])) ? $_POST[$i] : '';
			$f = $this->o['field_'.$x];
			if ( !empty($f) )
				$form .= '
				<label for="'.$i.'" class="tcf_label">'.$f.':</label>
				<input name="'.$i.'" id="'.$i.'" size="30" value="'.$tcf_f.'" class="tcf_field" />';
		}
		$form .= '
			<label for="tcf_subject'.$n.'" class="tcf_label">'.__('Subject', 'tcf-lang').':</label>
			<input name="tcf_subject'.$n.'" id="tcf_subject'.$n.'" size="30" value="'.$tcf_subject.'" class="tcf_field" />
			<label for="tcf_msg'.$n.'" class="tcf_label">'.__('Your Message', 'tcf-lang').':</label>
			<textarea name="tcf_msg'.$n.'" id="tcf_msg'.$n.'" class="tcf_textarea" cols="50" rows="10">'.$tcf_msg.'</textarea>
			';
		if ( $this->o['captcha'] )
			$form .= $captcha->getCaptcha($n);
		if ( $this->o['captcha2'] )
			$form .= '
			<label for="tcf_captcha2_'.$n.'" class="tcf_label">'.$this->o['captcha2_question'].'</label>
			<input name="tcf_captcha2_'.$n.'" id="tcf_captcha2_'.$n.'" size="30" class="tcf_field" />
			';
			
		$title = (!empty($this->o['submit'])) ? 'value="'.$this->o['submit'].'"' : '';
		$form .= '	
			<input type="submit" name="submit'.$n.'" id="contactsubmit'.$n.'" class="tcf_submit" '.$title.'  onclick="return checkForm(\''.$n.'\');" />
			</div>
			</form>';
	}
	
	$form .= '</div>'; 
	$form .= $this->addScript();
	return $form;
}

/**
 * adds javescript code to check the values
 */
function addScript()
{
	global $tcf_script_printed;
	if ($tcf_script_printed) // only once
		return;
	
	$script = "
		<script type=\"text/javascript\">
		//<![CDATA[
		function checkForm( n )
		{
			var f = new Array();
			f[1] = document.getElementById('tcf_sender' + n).value;
			f[2] = document.getElementById('tcf_email' + n).value;
			f[3] = document.getElementById('tcf_subject' + n).value;
			f[4] = document.getElementById('tcf_msg' + n).value;
			f[5] = f[6] = f[7] = f[8] = f[9] = '-';
		";
	for ( $x = 1; $x <=5; $x++ )
		if ( !empty($this->o['field_'.$x]) )
			$script .= 'f['.($x + 4).'] = document.getElementById("tcf_field_'.$x.'" + n).value;'."\n";
	$script .= '
		var msg = "";
		for ( i=0; i < f.length; i++ )
		{
			if ( f[i] == "" )
				msg = "'.__('Please fill out all fields.', 'tcf-lang').'\nPlease fill out all fields.\n\n";
		}
		if ( !isEmail(f[2]) )
			msg += "'.__('Wrong Email.', 'tcf-lang').'\nWrong Email.";
		if ( msg != "" )
		{
			alert(msg);
			return false;
		}
	}
	function isEmail(email)
	{
		var rx = /^([^\s@,:"<>]+)@([^\s@,:"<>]+\.[^\s@,:"<>.\d]{2,}|(\d{1,3}\.){3}\d{1,3})$/;
		var part = email.match(rx);
		if ( part )
			return true;
		else
			return false
	}
	//]]>
	</script>
	';
	$tcf_script_printed = 1;
	return $script;
}

/**
 * send mail
 * 
 * @return string Result, Message
 */
function sendMail( $n = '', $params = '' )
{
	$result = $this->checkInput( $n );
		
    if ( $result == 'OK' )
    {
    	$result = '';
    	
    	// use "to" from widget instance
		if ( is_array($params) && !empty($params['to']))
			$to = $params['to'];
    	// or from shortcode
		else if ( !empty($this->userdata['to']) )
			$to = $this->userdata['to'];
		// or default
		else
			$to = $this->o['to_email'];
		
		$from	= $this->o['from_email'];
	
		$name	= $_POST['tcf_sender'.$n];
		$email	= $_POST['tcf_email'.$n];
		$subject= $this->o['subpre'].' '.$_POST['tcf_subject'.$n];
		$msg	= $_POST['tcf_msg'.$n];
		
		// additional fields
		$extra = '';
		foreach ($_POST as $k => $f )
			if ( strpos( $k, 'tcf_field_') !== false )
				$extra .= $this->o[substr($k, 4, 7)].": $f\r\n";
		
		// create mail
		$headers =
		"MIME-Version: 1.0\r\n".
		"Reply-To: \"$name\" <$email>\r\n".
		"Content-Type: text/plain; charset=\"".get_settings('blog_charset')."\"\r\n";
		if ( !empty($from) )
			$headers .= "From: ".get_bloginfo('name')." - $name <$from>\r\n";
		else if ( !empty($email) )
			$headers .= "From: ".get_bloginfo('name')." - $name <$email>\r\n";

		$fullmsg =
		"Name: $name\r\n".
		"Email: $email\r\n".
		$extra."\r\n".
		'Subject: '.$_POST['tcf_subject'.$n]."\r\n\r\n".
		wordwrap($msg, 76, "\r\n")."\r\n\r\n".
		'Referer: '.$_SERVER['HTTP_REFERER']."\r\n".
		'Browser: '.$_SERVER['HTTP_USER_AGENT']."\r\n";
		
    	// send mail
		if ( wp_mail( $to, $subject, $fullmsg, $headers) )
		{
			// ok
			if ( $this->o['hideform'] )
			{
				unset($_POST['tcf_sender'.$n]);
				unset($_POST['tcf_email'.$n]);
				unset($_POST['tcf_subject'.$n]);
				unset($_POST['tcf_msg'.$n]);
				foreach ($_POST as $k => $f )
					if ( strpos( $k, 'tcf_field_') !== false )
						unset($k);
			}
			$result = $this->o['msg_ok'];
		}
		else
			// error
			$result = $this->o['msg_err'];
    }
    return $result;
}

/**
 * shows options page
 */
function optionsPage()
{	
	global $tcf_version;
	if (!current_user_can('manage_options'))
		wp_die(__('Sorry, but you have no permissions to change settings.'));
		
	// save data
	if ( isset($_POST['tcf_save']) )
	{
		$to = stripslashes($_POST['tcf_to_email']);
		if ( empty($to) )
			$to = get_option('admin_email');
		$msg_ok = stripslashes($_POST['tcf_msg_ok']);
		if ( empty($msg_ok) )
			$msg_ok = "Thank you! Your message was sent successfully.";
		$msg_err = stripslashes($_POST['tcf_msg_err']);
		if ( empty($msg_err) )
			$msg_err = "Sorry. An error occured while sending the message!";
		$captcha = ( isset($_POST['tcf_captcha']) ) ? 1 : 0;
		$captcha2 = ( isset($_POST['tcf_captcha2']) ) ? 1 : 0;
		$hideform = ( isset($_POST['tcf_hideform']) ) ? 1 : 0;
		
		$this->o = array(
			'to_email'		=> $to,
			'from_email'	=> stripslashes($_POST['tcf_from_email']),
			'css'			=> stripslashes($_POST['tcf_css']),
			'msg_ok'		=> $msg_ok,
			'msg_err'		=> $msg_err,
			'submit'		=> stripslashes($_POST['tcf_submit']),
			'captcha'		=> $captcha,
			'captcha_label'	=> stripslashes($_POST['tcf_captcha_label']),
			'captcha2'		=> $captcha2,
			'captcha2_question'	=> stripslashes($_POST['tcf_captcha2_question']),
			'captcha2_answer'	=> stripslashes($_POST['tcf_captcha2_answer']),
			'subpre'		=> stripslashes($_POST['tcf_subpre']),
			'field_1'		=> stripslashes($_POST['tcf_field_1']),
			'field_2'		=> stripslashes($_POST['tcf_field_2']),
			'field_3'		=> stripslashes($_POST['tcf_field_3']),
			'field_4'		=> stripslashes($_POST['tcf_field_4']),
			'field_5'		=> stripslashes($_POST['tcf_field_5']),
			'hideform'			=> $hideform
			);
		update_option('tiny_contact_form', $this->o);
	}
		
	// show page
	?>
	<div id="poststuff" class="wrap">
		<h2><img src="<?php echo $this->getResource('tcf_logo.png') ?>" alt="" style="width:24px;height:24px" /> Tiny Contact Form</h2>
		<div class="postbox">
		<h3><?php _e('Options', 'cpd') ?></h3>
		<div class="inside">
		
		<form action="options-general.php?page=tiny-contact-form" method="post">
	    <table class="form-table">
		<tr>
			<td colspan="2" style="border-top: 1px #ddd solid; background: #eee"><strong><?php _e('Form', 'tcf-lang'); ?></strong></td>
		</tr>
    	<tr>
			<th><?php _e('TO:', 'tcf-lang')?></th>
			<td><input name="tcf_to_email" type="text" size="70" value="<?php echo $this->o['to_email'] ?>" /><br /><?php _e('E-mail'); ?>, <?php _e('one or more (e.g. email1,email2,email3)', 'tcf-lang'); ?></td>
		</tr>
    	<tr>
			<th><?php _e('FROM:', 'tcf-lang')?> <?php _e('(optional)', 'tcf-lang'); ?></th>
			<td><input name="tcf_from_email" type="text" size="70" value="<?php echo $this->o['from_email'] ?>" /><br /><?php _e('E-mail'); ?></td>
		</tr>
    	<tr>
			<th><?php _e('Message OK:', 'tcf-lang')?></th>
			<td><input name="tcf_msg_ok" type="text" size="70" value="<?php echo $this->o['msg_ok'] ?>" /></td>
		</tr>
    	<tr>
			<th><?php _e('Message Error:', 'tcf-lang')?></th>
			<td><input name="tcf_msg_err" type="text" size="70" value="<?php echo $this->o['msg_err'] ?>" /></td>
		</tr>
		<tr>
			<th><?php _e('Submit Button:', 'tcf-lang')?> <?php _e('(optional)', 'tcf-lang'); ?></th>
			<td><input name="tcf_submit" type="text" size="70" value="<?php echo $this->o['submit'] ?>" /></td>
		</tr>
    	<tr>
			<th><?php _e('Subject Prefix:', 'tcf-lang')?> <?php _e('(optional)', 'tcf-lang'); ?></th>
			<td><input name="tcf_subpre" type="text" size="70" value="<?php echo $this->o['subpre'] ?>" /></td>
		</tr>
    	<tr>
			<th><?php _e('Additional Fields:', 'tcf-lang')?></th>
			<td>
				<p><?php _e('The contact form includes the fields Name, Email, Subject and Message. If you need more (e.g. Phone, Website) type in the name of the field.', 'tcf-lang'); ?></p>
				<?php
				for ( $x = 1; $x <= 5; $x++ )
					echo '<p>'.__('Field', 'tcf-lang').' '.$x.': <input name="tcf_field_'.$x.'" type="text" size="30" value="'.$this->o['field_'.$x].'" /></p>';
				?>
			</td>
		</tr>
    	<tr>
			<th><?php _e('After Submit', 'tcf-lang')?>:</th>
			<td><label for="tcf_hideform"><input name="tcf_hideform" id="tcf_hideform" type="checkbox" <?php if($this->o['hideform']==1) echo 'checked="checked"' ?> /> <?php _e('hide the form', 'tcf-lang'); ?></label></td>
		</tr>
		<tr>
			<td colspan="2" style="border-top: 1px #ddd solid; background: #eee"><strong><?php _e('Captcha', 'tcf-lang'); ?></strong></td>
		</tr>
    	<tr>
			<th><?php _e('Captcha', 'tcf-lang')?>:</th>
			<td><label for="tcf_captcha"><input name="tcf_captcha" id="tcf_captcha" type="checkbox" <?php if($this->o['captcha']==1) echo 'checked="checked"' ?> /> <?php _e('use a little mathematic spam test like " 12 + 5 = "', 'tcf-lang'); ?></label></td>
		</tr>
    	<tr>
			<th><?php _e('Captcha Label:', 'tcf-lang')?></th>
			<td><input name="tcf_captcha_label" type="text" size="70" value="<?php echo $this->o['captcha_label'] ?>" /></td>
		</tr>
    	<tr style="border-top: 1px #ddd dashed;" >
			<th><?php _e('Alternative Captcha:', 'tcf-lang')?></th>
			<td><label for="tcf_captcha2"><input name="tcf_captcha2" id="tcf_captcha2" type="checkbox" <?php if($this->o['captcha2']==1) echo 'checked="checked"' ?> /> <?php _e('Set you own question and answer.', 'tcf-lang'); ?></label></td>
		</tr>
    	<tr>
			<th><?php _e('Question:', 'tcf-lang')?></th>
			<td><input name="tcf_captcha2_question" type="text" size="70" value="<?php echo $this->o['captcha2_question'] ?>" /></td>
		</tr>
    	<tr>
			<th><?php _e('Answer:', 'tcf-lang')?></th>
			<td><input name="tcf_captcha2_answer" type="text" size="70" value="<?php echo $this->o['captcha2_answer'] ?>" /></td>
		</tr>
		<tr>
			<td colspan="2" style="border-top: 1px #ddd solid; background: #eee"><strong><?php _e('Style', 'tcf-lang'); ?></strong></td>
		</tr>
    	<tr>
			<th>
				<?php _e('StyleSheet:', 'tcf-lang'); ?><br />
				<a href="javascript:resetCss();"><?php _e('reset', 'tcf-lang'); ?></a>
			</th>
			<td>
				<textarea name="tcf_css" id="tcf_css" style="width:100%" rows="10"><?php echo $this->o['css'] ?></textarea><br />
				<?php _e('Use this field or the <code>style.css</code> in your theme directory.', 'tcf-lang') ?>
			</td>
		</tr>
		</table>
		<p class="submit">
			<input name="tcf_save" class="button-primary" value="<?php _e('Save Changes'); ?>" type="submit" />
		</p>
		</form>
		
		<script type="text/javascript">
		function resetCss()
		{
			css = ".contactform {}\n.contactform label {}\n.contactform input {}\n.contactform textarea {}\n"
				+ ".contactform_respons {}\n.contactform_error {}\n.widget .contactform { /* same fields but in sidebar */ }";
			document.getElementById('tcf_css').value = css;
		}
		</script>
	</div>
	</div>
	
	<div class="postbox">
		<h3><?php _e('Contact', 'tcf-lang') ?></h3>
		<div class="inside">
			<p>
			Tiny Contact Form: <code><?php echo $tcf_version ?></code><br />
			<?php _e('Bug? Problem? Question? Hint? Praise?', 'tcf-lang') ?><br />
			<?php printf(__('Write a comment on the <a href="%s">plugin page</a>.', 'tcf-lang'), 'http://www.tomsdimension.de/wp-plugins/tiny-contact-form'); ?><br />
			<?php _e('License') ?>: <a href="http://www.tomsdimension.de/postcards">Postcardware :)</a>
			</p>
			<p><a href="<?php echo get_bloginfo('wpurl').'/'.PLUGINDIR ?>/tiny-contact-form/readme.txt?KeepThis=true&amp;TB_iframe=true" title="Tiny Contact Form - Readme.txt" class="thickbox"><strong>Readme.txt</strong></a></p>
		</div>
	</div>
	
	</div>
	<?php
}

/**
 * adds admin menu
 */
function addOptionsPage()
{
	global $wp_version;
	$menutitle = '';
	if ( version_compare( $wp_version, '2.6.999', '>' ) )
		$menutitle = '<img src="'.$this->getResource('tcf_menu.png').'" alt="" /> ';
	$menutitle .= 'Tiny Contact Form';
	add_options_page('Tiny Contact Form', $menutitle, 9, 'tiny-contact-form', array( &$this, 'optionsPage'));
}

/**
 * parses parameters
 *
 * @param string $atts parameters
 */
function shortcode( $atts )
{
	// e.g. [TINY-CONTENT-FORM to="abc@xyz.com" subject="xyz"]
	
	extract( shortcode_atts( array(
		'to' => '',
		'subject' => ''
	), $atts) );
	$this->userdata = array(
		'to' => $to,
		'subject' => $subject
	);
	return $this->showForm();
}

/**
 * check input fields
 * 
 * @return string message
 */
function checkInput( $n = '' )
{
	// exit if no form data
	if ( !isset($_POST['tcf_sendit'.$n]))
		return false;

	// hidden field check
	if ( (isset($_POST['tcf_sendit'.$n]) && $_POST['tcf_sendit'.$n] != 1)
		|| (isset($_POST['tcf_name'.$n]) && $_POST['tcf_name'.$n] != '') )
	{
		return 'No Spam please!';
	}
	
	// for captcha check
	$o = get_option('tiny_contact_form');

	$_POST['tcf_sender'.$n] = stripslashes(trim($_POST['tcf_sender'.$n]));
	$_POST['tcf_email'.$n] = stripslashes(trim($_POST['tcf_email'.$n]));
	$_POST['tcf_subject'.$n] = stripslashes(trim($_POST['tcf_subject'.$n]));
	$_POST['tcf_msg'.$n] = stripslashes(trim($_POST['tcf_msg'.$n]));
//    extra felder

	$error = array();
	if ( empty($_POST['tcf_sender'.$n]) )
		$error[] = __('Name', 'tcf-lang');
    if ( !is_email($_POST['tcf_email'.$n]) )
		$error[] = __('Email', 'tcf-lang');
    if ( empty($_POST['tcf_subject'.$n]) )
		$error[] = __('Subject', 'tcf-lang');
    if ( empty($_POST['tcf_msg'.$n]) )
		$error[] = __('Your Message', 'tcf-lang');
	if ( $o['captcha'] && !TinyContactFormCaptcha::isCaptchaOk() )
		$error[] = $this->o['captcha_label'];
	if ( $o['captcha2'] && ( empty($_POST['tcf_captcha2_'.$n]) || $_POST['tcf_captcha2_'.$n] != $o['captcha2_answer'] ) )
		$error[] = $this->o['captcha2_question'];
	if ( !empty($error) )
		return __('Check these fields:', 'tcf-lang').' '.implode(', ', $error);
	
	return 'OK';
}

/**
 * clean up when uninstall
 */
function uninstall()
{
	delete_option('tiny_contact_form');
}

/**
 * adds custom style to page
 */
function addStyle()
{
	echo "\n<!-- Tiny Contact Form -->\n"
		."<style type=\"text/css\">\n"
		.".tcf_input {display:none !important; visibility:hidden !important;}\n"
		.$this->o['css']."\n"
		."</style>\n";
}

/**
 * adds an action link to the plugins page
 */
function pluginActions($links, $file)
{
	if( $file == plugin_basename(__FILE__)
		&& strpos( $_SERVER['SCRIPT_NAME'], '/network/') === false ) // not on network plugin page
	{
		$link = '<a href="options-general.php?page=tiny-contact-form">'.__('Settings').'</a>';
		array_unshift( $links, $link );
	}
	return $links;
}

/**
 * defines base64 encoded image recources
 */
function setRecources()
{
	if ( isset($_GET['resource']) && !empty($_GET['resource']) )
	{
		# base64 encoding
		$resources = array(
			'tcf_menu.png' =>
			'iVBORw0KGgoAAAANSUhEUgAAAAwAAAAMCAYAAABWdVznAAAAAX'.
			'NSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAgY0hSTQAAeiYA'.
			'AICEAAD6AAAAgOgAAHUwAADqYAAAOpgAABdwnLpRPAAAABh0RV'.
			'h0U29mdHdhcmUAUGFpbnQuTkVUIHYzLjM2qefiJQAAAEtJREFU'.
			'KFNj9GD4/58BCnYwMDI2NDTA+TBxymiQDTAMMglkAz5Mum20t4'.
			'GQm9HlwZ4k1iNgtWRpIMVZINewEusksDrkYEWOQFyGAABXBYxc'.
			'mDNSvQAAAABJRU5ErkJggg==',
			'tcf_logo.png' =>
			'iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAX'.
			'NSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAgY0hSTQAAeiYA'.
			'AICEAAD6AAAAgOgAAHUwAADqYAAAOpgAABdwnLpRPAAAABh0RV'.
			'h0U29mdHdhcmUAUGFpbnQuTkVUIHYzLjM2qefiJQAAAHpJREFU'.
			'SEtj9GD4/58BC9jBwMgIEm5oaMAqj00PVjGQBdgwTDHIAkow0Q'.
			'4ZvAppHkQ0t2Dwhi2xLqN5ENHcAmJ9OnjVUVIMEKMXXJjREsMt'.
			'oHYYwxw9agHOkMUIIlpFNO1TEdCPrFBM7YQEN4+2FuAq7NDFyf'.
			'YerS0AAHa/Vp9sTByIAAAAAElFTkSuQmCC');
			 
		if ( array_key_exists($_GET['resource'], $resources) )
		{
			$content = base64_decode($resources[ $_GET['resource'] ]);
			$lastMod = filemtime(__FILE__);
			$client = ( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false );
			if (isset($client) && (strtotime($client) == $lastMod))
			{
				header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastMod).' GMT', true, 304);
				exit;
			}
			else
			{
				header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastMod).' GMT', true, 200);
				header('Content-Length: '.strlen($content));
				header('Content-Type: image/' . substr(strrchr($_GET['resource'], '.'), 1) );
				echo $content;
				exit;
			}
		}
	}
}

/**
 * gets image recource with given name
 */
function getResource( $resourceID ) {
	return trailingslashit( get_bloginfo('url') ).'?resource='.$resourceID;
}


/**
 * calls widget class
 */
function register_widgets()
{
	register_widget('TinyContactForm_Widget');
}

} // TCF class





/**
 * Captcha class
 */
class TinyContactFormCaptcha
{
	
var $first;
var $operation;
var $second;
var $answer;
var $captcha_id;

/**
 * creates captcha
 * @param String $seed ID
 */
function TinyContactFormCaptcha( $seed )
{
	$this->captcha_id = $seed;
	if ( $seed )
		srand($seed);
	$operation = rand(1, 4);
	switch ( $operation )
	{
		case 1:
			$this->operation = '+';
			$this->first = rand(1, 20);
			$this->second = rand(0, 20);
			$this->answer = $this->first + $this->second;
			break;
		case 2:
			$this->operation = '-';
			$this->first = rand(1, 20);
			$this->second = rand(0, ($this->first - 1));
			$this->answer = $this->first - $this->second;
			break;
		case 3:
			$this->operation = 'x';
			$this->first = rand(1, 10);
			$this->second = rand(1, 10);
			$this->answer = $this->first * $this->second;
			break;
		case 4:
			$this->operation = '/';
			$this->first = rand(1, 20);
			do
			{
				$this->second = rand(1, 20);
			}
			while (($this->first % $this->second) != 0);
			$this->answer = $this->first / $this->second;
			break;
	}
}

/**
 * returns answer
 */
function getAnswer()
{
	return $this->answer;
}

/**
 * returns question
 */
function getQuestion()
{
	return $this->first.' '.$this->operation.' '.$this->second.' = ';
}

/**
 * checks answer
 */
function isCaptchaOk()
{
	$ok = true;
	// time and ID in form?
	if ($_POST[base64_encode(strrev('current_time'))] && $_POST[base64_encode(strrev('captcha'))])
	{
		// maximum 30 minutes to fill the form
		if ((time() - strrev(base64_decode($_POST[base64_encode(strrev('current_time'))]))) > 1800)
			$ok = false;
		// check answer
		$valid = new TinyContactFormCaptcha(strrev(base64_decode($_POST[base64_encode(strrev('captcha'))])));
		if ($_POST[base64_encode(strrev('answer'))] != $valid->getAnswer())
			$ok = false;
	}
	return $ok;
}
	
/**
 * creates input fields in form
 */
function getCaptcha( $n = '' )
{
	global $tiny_contact_form;
	return '<input name="'.base64_encode(strrev('current_time')).'" type="hidden" value="'.base64_encode(strrev(time())).'" />'."\n"
		.'<input name="'.base64_encode(strrev('captcha')).'" type="hidden" value="'.base64_encode(strrev($this->captcha_id)).'" />'."\n"
		.'<label class="tcf_label" style="display:inline" for="tcf_captcha'.$n.'">'.$tiny_contact_form->o['captcha_label'].' <b>'.$this->getQuestion().'</b></label> <input id="tcf_captcha'.$n.'" name="'.base64_encode(strrev('answer')).'" type="text" size="2" />'."\n";
}

} // captcha class



class TinyContactForm_Widget extends WP_Widget
{
	var $fields = array('Title', 'Subject', 'To');
	
	/**
	 * constructor
	 */	 
	function TinyContactForm_Widget() {
		parent::WP_Widget('tcform_widget', 'Tiny Contact Form', array('description' => 'Little Contact Form'));	
	}
 
	/**
	 * display widget
	 */	 
	function widget( $args, $instance)
	{
		global $tiny_contact_form;
		extract($args, EXTR_SKIP);
		$title = empty($instance['title']) ? '&nbsp;' : apply_filters('widget_title', $instance['title']);
		echo $before_widget;
		if ( !empty( $title ) )
			echo $before_title.$title.$after_title;
		echo $tiny_contact_form->showForm( $instance );
		echo $after_widget;
	}
 
	/**
	 *	update/save function
	 */	 	
	function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;
		foreach ( $this->fields as $f )
			$instance[strtolower($f)] = strip_tags($new_instance[strtolower($f)]);
		return $instance;
	}
 
	/**
	 *	admin control form
	 */	 	
	function form( $instance )
	{
		$default = array('title' => 'Tiny Contact Form');
		$instance = wp_parse_args( (array) $instance, $default );
 
		foreach ( $this->fields as $field )
		{ 
			$f = strtolower( $field );
			$field_id = $this->get_field_id( $f );
			$field_name = $this->get_field_name( $f );
			echo "\r\n".'<p><label for="'.$field_id.'">'.__($field, 'tcf-lang').': <input type="text" class="widefat" id="'.$field_id.'" name="'.$field_name.'" value="'.attribute_escape( $instance[$f] ).'" /><label></p>';
		}
	}
} // widget class
?>