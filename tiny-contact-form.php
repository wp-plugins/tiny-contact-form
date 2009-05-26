<?php
/*
Plugin Name: Tiny Contact Form
Plugin URI: http://www.tomsdimension.de/wp-plugins/tiny-contact-form
Description: Little form that allows site visitors to contact you. Use [TINY-CONTACT-FORM] within any post or page.
Author: Tom Braider
Author URI: http://www.tomsdimension.de
Version: 0.4.2

Silent Helper: Jay Shergill http://www.pdrater.com
*/


/**
 * creates tcf code
 *
 * @return string form code
 */
function tcf_show_form()
{
	$result = tcf_send_mail();
	$o = get_option('tiny_contact_form');
	$title = (!empty($o['submit'])) ? 'value="'.$o['submit'].'"' : '';
	
	if ( $result == $o['msg_ok'] )
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
		<form action="'.get_permalink().'" method="post">
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
		<input type="submit" name="submit" id="contactsubmit" '.$title.' />
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
function tcf_send_mail()
{
	$result = tcf_check_input();
		
    if ( $result == 'OK' )
    {
    	$result = '';
    	
    	// get options
		$o		= get_option('tiny_contact_form');
		$to		= $o['to_email'];
		$from	= $o['from_email'];
	
		$name		= $_POST['tcf_sender'];
		$email		= $_POST['tcf_email'];
		$subject	= $_POST['tcf_subject'].' - '.get_bloginfo('name').' - Tiny Contact Form';
		$msg		= $_POST['tcf_msg'];

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
		'Browser: '.$_SERVER['HTTP_USER_AGENT']."\r\n";
		
    	// send mail
		if ( wp_mail( $to, $subject, $fullmsg, $headers) )
		{
			// ok
			unset($_POST);
			$result = $o['msg_ok'];
		}
		else
			// error
			$result = $o['msg_err'];
    }
    return $result;
}



/**
 * shows options page
 */
function tcf_options_page()
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
		
		$options = array(
			'to_email'		=> $to,
			'from_email'	=> $from,
			'css'			=> $css,
			'msg_ok'		=> $msg_ok,
			'msg_err'		=> $msg_err,
			'submit'		=> $submit);
		update_option('tiny_contact_form', $options);
	}
		
	$o = get_option('tiny_contact_form');

	// show page
	?>
	<div class="wrap">
		<h2>Tiny Contact Form</h2>
		<form action="options-general.php?page=tiny-contact-form" method="post">
	    <table class="form-table">
    	<tr>
			<th><?php _e('TO:', 'tcf-lang')?></th>
			<td><input name="tcf_to_email" type="text" size="30" value="<?php echo $o['to_email'] ?>" /> <?php _e('E-mail'); ?></td>
		</tr>
    	<tr>
			<th><?php _e('FROM:', 'tcf-lang')?></th>
			<td><input name="tcf_from_email" type="text" size="30" value="<?php echo $o['from_email'] ?>" /> <?php _e('E-mail'); ?> <?php _e('(optional)'); ?></td>
		</tr>
    	<tr>
			<th><?php _e('Message OK:', 'tcf-lang')?></th>
			<td><input name="tcf_msg_ok" type="text" size="72" value="<?php echo $o['msg_ok'] ?>" /></td>
		</tr>
    	<tr>
			<th><?php _e('Message Error:', 'tcf-lang')?></th>
			<td><input name="tcf_msg_err" type="text" size="72" value="<?php echo $o['msg_err'] ?>" /></td>
		</tr>
		<tr>
			<th><?php _e('Submit Button:', 'tcf-lang')?></th>
			<td><input name="tcf_submit" type="text" size="30" value="<?php echo $o['submit'] ?>" /> <?php _e('(optional)'); ?></td>
		</tr>
    	<tr>
			<th>
				<?php _e('StyleSheet:', 'tcf-lang'); ?><br />
				<a href="javascript:tcf_reset_css();"><?php _e('reset', 'tcf-lang'); ?></a>
			</th>
			<td>
				<textarea name="tcf_css" id="tcf_css" cols="70" rows="10"><?php echo $o['css'] ?></textarea><br />
				<?php _e('Use this field or the <code>style.css</code> in your theme directory.', 'tcf-lang') ?>
			</td>
		</tr>
		</table>
		<p class="submit">
			<input name="tcf_save" class="button-primary" value="<?php _e('Save Changes'); ?>" type="submit" />
		</p>
		</form>
		
		<script type="text/javascript">
		function tcf_reset_css()
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
function widget_tcf_init()
{
	if (! function_exists('register_sidebar_widget'))
		return;
	
	function widget_tcf($args)
	{
		extract($args);
		$options = get_option('tiny_contact_form');
		$title = (!empty($options['widget_title'])) ? $options['widget_title'] : 'Tiny Contact Form';
		echo $before_widget;
		echo $before_title.$title.$after_title;
		echo tcf_show_form();
		echo $after_widget;
	}
	register_sidebar_widget('Tiny Contact Form', 'widget_tcf');
	
	function widget_tcf_control()
	{
		if ( !empty($_POST['widget_tcf_title']) )
		{
			$options = get_option('tiny_contact_form');
			$options['widget_title'] = stripslashes($_POST['widget_tcf_title']);
			update_option('tiny_contact_form', $options);
		}
		$options = get_option('tiny_contact_form');
		$title = (!empty($options['widget_title'])) ? $options['widget_title'] : 'Tiny Contact Form';
		echo '<p style="text-align:right;"><label for="widget_tcf_title">Title: <input style="width: 200px;" id="widget_tcf_title" name="widget_tcf_title" type="text" value="'.$title.'" /></label></p>';
	}
	register_widget_control('Tiny Contact Form', 'widget_tcf_control');
}

add_action('plugins_loaded', 'widget_tcf_init');



/**
 * adds admin menu
 */
function tcf_add_options_page()
{
	add_options_page('Tiny Contact Form', 'Tiny Contact Form', 9, 'tiny-contact-form', 'tcf_options_page');
}

add_action('admin_menu', 'tcf_add_options_page');



/**
 * parses parameters
 *
 * @param string $atts parameters
 */
function tcf_shortcode( $atts )
{
	return tcf_show_form();
}

add_shortcode('TINY-CONTACT-FORM', 'tcf_shortcode');



/**
 * check input fields
 * 
 * @return string message
 */
function tcf_check_input()
{
	// exit if no form data
	if ( !isset($_POST['tcf_sendit']))
		return false;

	// spam check
	if ( (isset($_POST['tcf_sendit']) && $_POST['tcf_sendit'] != 1)
		|| (isset($_POST['tcf_name']) && $_POST['tcf_name'] != '') )
	{
		return 'No Spam please!';
	}
		
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
	if ( !empty($error) )
		return __('Check these fields:', 'tcf-lang').' '.implode(', ', $error);
	
	return 'OK';
}



/**
 * adds locale support
 */
if (defined('WPLANG') && function_exists('load_plugin_textdomain'))
	load_plugin_textdomain('tcf-lang', '', dirname(plugin_basename(__FILE__)).'/locale');



/**
 * clean up when uninstall
 */
function tcf_uninstall()
{
	delete_option('tiny_contact_form');
}

// since WordPress 2.7
if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, 'tcf_uninstall'); 



/**
 * adds style
 */
function tcf_add_style()
{
	$o = get_option('tiny_contact_form');
	echo "<style type=\"text/css\">\n .tcf_input { display:none; }\n".$o['css']."\n</style>\n";
}
add_action('wp_head', 'tcf_add_style');



/**
 * adds an action link to the plugins page
 */
function tcf_plugin_actions($links, $file)
{
	if( $file == plugin_basename(__FILE__) )
	{
		//$link = '<a href="options-general.php?page='.dirname(plugin_basename(__FILE__)).'/tiny-contact-form.php">'.__('Settings').'</a>';
		$link = '<a href="options-general.php?page=tiny-contact-form">'.__('Settings').'</a>';
		array_unshift( $links, $link );
	}
	return $links;
}

add_filter('plugin_action_links', 'tcf_plugin_actions', 10, 2);

?>
