<?php
/*
Plugin Name: Tiny Contact Form
Plugin URI: http://www.tomsdimension.de/wp-plugins/tiny-contact-form
Description: Little form that allows site visitors to contact you. Use [TINY-CONTACT-FORM] within any post or page.
Author: Tom Braider
Author URI: http://www.tomsdimension.de
Version: 0.5

Silent Helper: Jay Shergill http://www.pdrater.com
*/


$tiny_contact_form = new TinyContactForm();


class TinyContactForm
{

var $o; // options
var $captcha;


/**
 * Constructor
 */	
function TinyContactForm()
{
	// get options from DB
	$this->o = get_option('tiny_contact_form');
	
	// widget
	add_action('plugins_loaded', array( &$this, 'widgetTcfInit'));
	// options page in menu
	add_action('admin_menu', array( &$this, 'addOptionsPage'));
	// shortcode
	add_shortcode('TINY-CONTACT-FORM', array( &$this, 'shortcode'));
	// add stylesheet
	add_action('wp_head', array( &$this, 'addStyle'));
	// uninstall function - since WordPress 2.7
	if ( function_exists('register_uninstall_hook') )
		register_uninstall_hook(__FILE__, array( &$this, 'uninstall')); 
	// settingslink on plugin page
	add_filter('plugin_action_links', array( &$this, 'pluginActions'), 10, 2);
	// add locale support
	if (defined('WPLANG') && function_exists('load_plugin_textdomain'))
		load_plugin_textdomain('tcf-lang', '', dirname(plugin_basename(__FILE__)).'/locale');
	// creates image recources
	$this->setRecources();
}



/**
 * creates tcf code
 *
 * @return string form code
 */
function showForm()
{
	$result = $this->sendMail();
	$captcha = new TinyContactFormCaptcha( rand(1000000000, 9999999999) );
	$title = (!empty($this->o['submit'])) ? 'value="'.$this->o['submit'].'"' : '';
	
	if ( $result == $this->o['msg_ok'] )
		// mail successfully sent, no form
		$form = '<div class="contactform" id="tcform"><p class="contactform_respons">'.$result.'</p></div>';
	else 
	{
		if ( !empty($result) )
			// error message
			$result = '<p class="contactform_error">'.$result.'</p>';
		$form = '
			<div class="contactform" id="tcform">
			'.$result.'
			<form action="" method="post">
			<div>
			<input name="tcf_name" id="tcf_name" value="" class="tcf_input" />
			<input name="tcf_sendit" id="tcf_sendit" value="1" class="tcf_input" />
			<label for="tcf_sender">'.__('Name', 'tcf-lang').':</label>
			<input name="tcf_sender" id="tcf_sender" size="30" value="'.$_POST['tcf_sender'].'" />
			<label for="tcf_email">'.__('Email', 'tcf-lang').':</label>
			<input name="tcf_email" id="tcf_email" size="30" value="'.$_POST['tcf_email'].'" />
			<label for="tcf_subject">'.__('Subject', 'tcf-lang').':</label>
			<input name="tcf_subject" id="tcf_subject" size="30" value="'.$_POST['tcf_subject'].'" />
			<label for="tcf_msg">'.__('Your Message', 'tcf-lang').':</label>
			<textarea name="tcf_msg" id="tcf_msg" cols="50" rows="10">'.$_POST['tcf_msg'].'</textarea>
			';
		if ( $this->o['captcha'] )
			$form .= $captcha->getCaptcha();
		$form .= '	
			<input type="submit" name="submit" id="contactsubmit" '.$title.' />
			</div>
			</form>
			</div>';
	}
	return $form;
}



/**
 * send mail
 * 
 * @return string Result, Message
 */
function sendMail()
{
	$result = $this->checkInput();
		
    if ( $result == 'OK' )
    {
    	$result = '';
    	
		$to		= $this->o['to_email'];
		$from	= $this->o['from_email'];
	
		$name	= $_POST['tcf_sender'];
		$email	= $_POST['tcf_email'];
		$subject= $_POST['tcf_subject'].' - '.get_bloginfo('name').' - Tiny Contact Form';
		$msg	= $_POST['tcf_msg'];

		$headers =
		"MIME-Version: 1.0\r\n".
		"Reply-To: \"$name\" <$email>\r\n".
		"Content-Type: text/plain; charset=\"".get_settings('blog_charset')."\"\r\n";
		if ( !empty($from) )
			$headers .= "From: $name - ".get_bloginfo('name')." <$from>\r\n";

		$fullmsg =
		'Name...: '.$name."\r\n".
		'Email..: '.$email."\r\n\r\n".
		'Subject: '.$_POST['tcf_subject']."\r\n\r\n".
		wordwrap($msg, 76, "\r\n")."\r\n\r\n".
		'Referer: '.get_permalink()."\r\n".
		'Browser: '.$_SERVER['HTTP_USER_AGENT']."\r\n";
		
    	// send mail
		if ( wp_mail( $to, $subject, $fullmsg, $headers) )
		{
			// ok
			unset($_POST);
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
	if (!current_user_can('manage_options'))
		wp_die(__('Sorry, but you have no permissions to change settings.'));
		
	// save data
	if ( isset($_POST['tcf_save']) )
	{
		$to = stripslashes($_POST['tcf_to_email']);
		if ( empty($to) )
			$to = get_option('admin_email');
		$from = stripslashes($_POST['tcf_from_email']);
		$msg_ok = wp_specialchars($_POST['tcf_msg_ok']);
		if ( empty($msg_ok) )
			$msg_ok = "Thank you! Your message was sent successfully.";
		$msg_err = wp_specialchars($_POST['tcf_msg_err']);
		if ( empty($msg_err) )
			$msg_err = "Sorry. An error occured while sending the message!";
		$submit = stripslashes($_POST['tcf_submit']);
		$css = stripslashes($_POST['tcf_css']);
		$captcha = ( isset($_POST['tcf_captcha']) ) ? 1 : 0;
		
		$this->o = array(
			'to_email'		=> $to,
			'from_email'	=> $from,
			'css'			=> $css,
			'msg_ok'		=> $msg_ok,
			'msg_err'		=> $msg_err,
			'submit'		=> $submit,
			'captcha'		=> $captcha,
			'captcha_label'	=> wp_specialchars($_POST['tcf_captcha_label'])
			);
		update_option('tiny_contact_form', $this->o);
	}
		
	// show page
	?>
	<div class="wrap">
		<h2><img src="<?php echo $this->getResource('tcf_logo.png') ?>" alt="" style="width:24px;height:24px" /> Tiny Contact Form</h2>
		<form action="options-general.php?page=tiny-contact-form" method="post">
	    <table class="form-table">
    	<tr>
			<th><?php _e('TO:', 'tcf-lang')?></th>
			<td><input name="tcf_to_email" type="text" size="30" value="<?php echo $this->o['to_email'] ?>" /> <?php _e('E-mail'); ?></td>
		</tr>
    	<tr>
			<th><?php _e('FROM:', 'tcf-lang')?></th>
			<td><input name="tcf_from_email" type="text" size="30" value="<?php echo $this->o['from_email'] ?>" /> <?php _e('E-mail'); ?> <?php _e('(optional)'); ?></td>
		</tr>
    	<tr>
			<th><?php _e('Message OK:', 'tcf-lang')?></th>
			<td><input name="tcf_msg_ok" type="text" size="72" value="<?php echo $this->o['msg_ok'] ?>" /></td>
		</tr>
    	<tr>
			<th><?php _e('Message Error:', 'tcf-lang')?></th>
			<td><input name="tcf_msg_err" type="text" size="72" value="<?php echo $this->o['msg_err'] ?>" /></td>
		</tr>
		<tr>
			<th><?php _e('Submit Button:', 'tcf-lang')?></th>
			<td><input name="tcf_submit" type="text" size="30" value="<?php echo $this->o['submit'] ?>" /> <?php _e('(optional)'); ?></td>
		</tr>
    	<tr>
			<th><?php _e('Captcha:', 'tcf-lang')?></th>
			<td><label for="tcf_captcha"><input name="tcf_captcha" id="tcf_captcha" type="checkbox" <?php if($this->o['captcha']==1) echo 'checked="checked"' ?>" /> <?php _e('use a little mathematic spam test like " 12 + 5 = "', 'tcf-lang'); ?></label></td>
		</tr>
    	<tr>
			<th><?php _e('Captcha Label:', 'tcf-lang')?></th>
			<td><input name="tcf_captcha_label" type="text" size="72" value="<?php echo $this->o['captcha_label'] ?>" /></td>
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
	<?php
}



/**
 * widget
 */
function widgetTcfInit()
{
	if (! function_exists('register_sidebar_widget'))
		return;
	
	function widgetTcf($args)
	{
		global $tiny_contact_form;
		
		extract($args);
		$title = (!empty($tiny_contact_form->o['widget_title'])) ? $tiny_contact_form->o['widget_title'] : 'Tiny Contact Form';
		echo $before_widget;
		echo $before_title.$title.$after_title;
		echo $tiny_contact_form->showForm();
		echo $after_widget;
	}
	register_sidebar_widget('Tiny Contact Form', 'widgetTcf');
	
	function widgetTcfControl()
	{
		global $tiny_contact_form;
		
		if ( !empty($_POST['widget_tcf_title']) )
		{
			$tiny_contact_form->o['widget_title'] = stripslashes($_POST['widget_tcf_title']);
			update_option('tiny_contact_form', $tiny_contact_form->o);
		}
		$title = (!empty($tiny_contact_form->o['widget_title'])) ? $tiny_contact_form->o['widget_title'] : 'Tiny Contact Form';
		echo '<p><label for="widget_tcf_title">Title: <input style="width: 150px;" id="widget_tcf_title" name="widget_tcf_title" type="text" value="'.$title.'" /></label></p>';
	}
	register_widget_control('Tiny Contact Form', 'widgetTcfControl');
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
	return $this->showForm();
}



/**
 * check input fields
 * 
 * @return string message
 */
function checkInput()
{
	// exit if no form data
	if ( !isset($_POST['tcf_sendit']))
		return false;

	// hidden field check
	if ( (isset($_POST['tcf_sendit']) && $_POST['tcf_sendit'] != 1)
		|| (isset($_POST['tcf_name']) && $_POST['tcf_name'] != '') )
	{
		return 'No Spam please!';
	}
	
	// for captcha check
	$o = get_option('tiny_contact_form');

	$_POST['tcf_sender'] = stripslashes(trim($_POST['tcf_sender']));
    $_POST['tcf_email'] = stripslashes(trim($_POST['tcf_email']));
    $_POST['tcf_subject'] = stripslashes(trim($_POST['tcf_subject']));
    $_POST['tcf_msg'] = stripslashes(trim($_POST['tcf_msg']));

	$error = array();
	if ( empty($_POST['tcf_sender']) )
		$error[] = __('Name', 'tcf-lang');
    if ( !is_email($_POST['tcf_email']) )
		$error[] = __('Email', 'tcf-lang');
    if ( empty($_POST['tcf_subject']) )
		$error[] = __('Subject', 'tcf-lang');
    if ( empty($_POST['tcf_msg']) )
		$error[] = __('Your Message', 'tcf-lang');
	if ( $o['captcha'] && !TinyContactFormCaptcha::isCaptchaOk() )
		$error[] = $this->o['captcha_label'];
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
	echo "\n<style type=\"text/css\">\n .tcf_input {display:none !important; visibility:hidden !important;}\n".$this->o['css']."\n</style>\n";
}



/**
 * adds an action link to the plugins page
 */
function pluginActions($links, $file)
{
	if( $file == plugin_basename(__FILE__) )
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
		if ((mktime() - strrev(base64_decode($_POST[base64_encode(strrev('current_time'))]))) > 1800)
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
function getCaptcha()
{
	global $tiny_contact_form;
	return '<input name="'.base64_encode(strrev('current_time')).'" type="hidden" value="'.base64_encode(strrev(mktime())).'" />'."\n"
		.'<input name="'.base64_encode(strrev('captcha')).'" type="hidden" value="'.base64_encode(strrev($this->captcha_id)).'" />'."\n"
		.'<label for="tcf_captcha">'.$tiny_contact_form->o['captcha_label'].' <b>'.$this->getQuestion().'</b></label><input id="tcf_captcha" name="'.base64_encode(strrev('answer')).'" type="text" />'."\n";
}

} // captcha class



?>