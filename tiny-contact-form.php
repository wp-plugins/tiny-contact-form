<?php
/*
Plugin Name: Tiny Contact Form
Plugin URI: http://www.tomsdimension.de/wp-plugins/tiny-contact-form
Description: Little form that allows site visitors to contact you. Use [TINY-CONTACT-FORM] within any post or page.
Author: Tom Braider
Author URI: http://www.tomsdimension.de
Version: 0.2
*/


function tcf_check_input()
{
	// exit if no form data
	if ( !isset($_POST['tcf_sendit']))
		return false;

	// spam check
	if ( isset($_POST['tcf_sendit']) && $_POST['tcf_sendit'] != 1
		&& isset($_POST['tcf_name']) && $_POST['tcf_name'] != '' )
	{
		return 'No Spam please!';
	}
		
    $_POST['tcf_sender'] = stripslashes(trim($_POST['tcf_sender']));
    $_POST['tcf_email'] = stripslashes(trim($_POST['tcf_email']));
    $_POST['tcf_subject'] = stripslashes(trim($_POST['tcf_subject']));
    $_POST['tcf_msg'] = stripslashes(trim($_POST['tcf_msg']));

	$error = '';

	if ( empty($_POST['tcf_sender']) )
		$error .= __('Name').' ';

    if ( !is_email($_POST['tcf_email']) )
		$error .= __('Email').' ';

    if ( empty($_POST['tcf_subject']) )
		$error .= __('Subject').' ';

    if ( empty($_POST['tcf_msg']) )
		$error .= __('Message').' ';

	if ( !empty($error) )
		return 'Check these fields: '.$error;
	
	return 'OK';
}



/**
 * creates tcf code
 *
 * @return string form code
 */
function replace_tcf_tag()
{
	$result = tcf_send_mail();
	
    // show form
	if ( !empty($result) )
		$result = '<p class="contacterror">'.$result.'</p>';

	$form = '
	<div class="contactform" id="tcform">'
	.$result.'
	<form action="'.get_permalink().'" method="post">
	<input type="hidden" name="tcf_name" id="tcf_name" value="" />
	<input type="hidden" name="tcf_sendit" id="tcf_sendit" value="1" />
	<label for="tcf_sender">'.__('Name').':</label>
	<input name="tcf_sender" id="tcf_sender" size="30" value="'.$_POST['tcf_sender'].'" />
	<label for="tcf_email">'.__('Email').':</label>
	<input name="tcf_email" id="tcf_email" size="30" value="'.$_POST['tcf_email'].'" />
	<label for="tcf_subject">'.__('Subject').':</label>
	<input name="tcf_subject" id="tcf_subject" size="30" value="'.$_POST['tcf_subject'].'" />
	<label for="tcf_msg">'.__('Message').':</label>
	<textarea name="tcf_msg" id="tcf_msg" cols="50" rows="10">'.$_POST['tcf_msg'].'</textarea>
	<input type="submit" name="submit" value="abschicken" id="contactsubmit" />
	</form>
	</div>
	';
	
	return $form;
}



/**
 * parses parameters
 *
 * @param string $atts parameters
 */
function tcf_shortcode( $atts )
{
	return replace_tcf_tag();
}

add_shortcode('TINY-CONTACT-FORM', 'tcf_shortcode');



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
    	// send mail
    	$from		= get_option('admin_email'); 
		$to			= get_option('tcf_to_email');
		$name		= $_POST['tcf_sender'];
		$email		= $_POST['tcf_email'];
		$subject	= $_POST['tcf_subject'].' - '.get_bloginfo('name').' - Tiny Contact Form';
		$msg		= $_POST['tcf_msg'];

		$headers =
		"MIME-Version: 1.0\r\n".
		"From: $name - ".get_bloginfo('name'). " <$from>\r\n".
		"Reply-To: \"$name\" <$email>\r\n".
		"Content-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"\r\n";

		$fullmsg =
		'Name...: '.$name."\r\n".
		'Email..: '.$email."\r\n\r\n".
		'Betreff: '.$_POST['tcf_subject']."\r\n\r\n".
		wordwrap($msg, 76, "\r\n")."\r\n\r\n".
		'Browser: '.$_SERVER['HTTP_USER_AGENT']."\r\n";
		
		if ( mail( $to, $subject, $fullmsg, $headers ) )
			$result = 'Thank you for the message!';
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
	if ( isset($_POST['tcf_to_email']) )
		update_option('tcf_to_email', stripslashes($_POST['tcf_to_email']));
		
	// load email
	$to_email = get_option('tcf_to_email');
	if ( empty($to_email) )
		$to_email = get_option('admin_email'); 
	
	// show page
	?>
	<div class="wrap">
		<h2>Tiny Contact Form</h2>
		<form action="options-general.php?page=tiny-contact-form" method="post">
	    <table class="form-table">
    	<tr>
			<th><?php _e('E-mail'); ?>:</th>
			<td><input name="tcf_to_email" type="text" size="30" value="<?php echo $to_email ?>" /></td>
		</tr>
		</table>
		<p class="submit">
			<input name="tcf_save" class="button-primary" value="<?php _e('Save Changes'); ?>" type="submit" />
		</p>
		</form>
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
		echo $before_widget;
		echo $before_title.'Tiny Contact Form'.$after_title;
		echo replace_tcf_tag();
		echo $after_widget;
	}
	register_sidebar_widget('Tiny Contact Form', 'widget_tcf');
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
?>
