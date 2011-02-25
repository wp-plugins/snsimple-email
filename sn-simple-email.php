<?php
/*
Plugin Name: snSimple Email
Plugin URI: http://sndevelopment.com
Description: This plugin is designed to create a simple email form for your WordPress blog.
Version: 1.0
Author: Sean Newby
Author URI: http://sndevelopment.com
License: GPL2

Copyright 2011 Sean Newby (email : seannewby@sndevelopment.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( !class_exists('snSimpleEmail') ) {
	class snSimpleEmail {
		var $plugin_url;
		var $plugin_dir;
		var $plugin_options;
		var $email_html_entities;

		function snSimpleEmail() {
			__construct();
		}
		
		function __construct() {
			$this->plugin_url = WP_PLUGIN_URL . '/' . basename( dirname( __FILE__ ) );
			$this->plugin_dir = basename( dirname( __FILE__ ) );
			$this->plugin_options = get_option( 'se-options' );
			$this->email_html_entities = array('a' => '&#97;','b' => '&#98;','c' => '&#99;','d' => '&#100;','e' => '&#101;','f' => '&#102;','g' => '&#103;','h' => '&#104;','i' => '&#105;','j' => '&#106;','k' => '&#107;','l' => '&#108;','m' => '&#109;','n' => '&#110;','o' => '&#111;','p' => '&#112;','q' => '&#113;','r' => '&#114;','s' => '&#115;','t' => '&#116;','u' => '&#117;','v' => '&#118;','w' => '&#119;','x' => '&#120;','y' => '&#121;','z' => '&#122;','A' => '&#65;','B' => '&#66;','C' => '&#67;','D' => '&#68;','E' => '&#69;','F' => '&#70;','G' => '&#71;','H' => '&#72;','I' => '&#73;','J' => '&#74;','K' => '&#75;','L' => '&#76;','M' => '&#77;','N' => '&#78;','O' => '&#79;','P' => '&#80;','Q' => '&#81;','R' => '&#82;','S' => '&#83;','T' => '&#84;','U' => '&#85;','V' => '&#86;','W' => '&#87;','X' => '&#88;','Y' => '&#89;','Z' => '&#90;','0' => '&#48;','1' => '&#49;','2' => '&#50;','3' => '&#51;','4' => '&#52;','5' => '&#53;','6' => '&#54;','7' => '&#55;','8' => '&#56;','9' => '&#57;','.' => '&#46;','-' => '&#150;','_' => '&#95;','+' => '&#43;','@' => '&#64;');
			
			if( is_admin() ){
				add_action( 'admin_init' , array( &$this , 'register_plugin_settings' ) );
				add_action( 'admin_menu' , array( &$this , 'plugin_menu' ) );
				add_action( 'init' , array( &$this , 'plugin_add_button' ) );
			} else {
				add_action( 'template_redirect' , array( &$this , 'plugin_css_query' ) );
				add_shortcode( 'sn-simple-email' , array( &$this, 'simple_email_interface' ) );
				add_action( 'sn-simple-email' , array( &$this, 'simple_email_interface_action' ) );
				add_action( 'init' , array( &$this, 'start_php_session' ) );
			}
			add_action( 'init' , array( &$this, 'plugin_localization' ) );
		}
		
		function start_php_session() {
			if ( !session_id() ) {session_start();}
		}
		
		function plugin_localization() {
			load_plugin_textdomain( 'sn-simple-email' , false , dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
		
		function register_plugin_settings() {
			register_setting( 'simple-email-options' , 'se-options' );
		}
		
		function plugin_menu() {
			add_options_page( 'snSimple Email', 'snSimple Email', 'manage_options', 'sn_simple_email', array( &$this, 'general_options' ) );
		}
		
		function general_options() {
			// double check
			if( !current_user_can( 'manage_options' ) ) wp_die( __( 'You do not have sufficient permissions to access this page.' , 'sn-simple-email' ) ); ?>
			<div class="wrap">
				<h2>snSimple Email</h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'simple-email-options' ) ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'Your Email' , 'sn-simple-email' ) ?></th>
							<td><input type="text" name="se-options[your-email]" value="<?php echo $this->plugin_options['your-email']; ?>" size="40" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Display your email' , 'sn-simple-email' ) ?></th>
							<td><input name="se-options[display-email]" type="checkbox" value="1" <?php checked( '1' , !empty( $this->plugin_options['display-email'] ) ); ?> /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Human Validation (Captcha)' , 'sn-simple-email' ) ?></th>
							<td><input name="se-options[validate-user]" type="checkbox" value="1" <?php checked( '1' , !empty( $this->plugin_options['validate-user'] ) ); ?> /></td>
						</tr>
					</table>
					<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' , 'sn-simple-email' ) ?>" />
					</p>
				</form>
			</div><!-- .wrap -->	
<?php	}

		function simple_email_interface_action(){
			// basically all this does is uses simple_email_interface, but echos it rather then returns it therefore the plugin has both a shortcode and action
			echo $this->simple_email_interface();
		}
		
		function simple_email_interface() {
			if( isset( $_POST['submit'] ) ) { // form was submitted
				if( empty( $this->plugin_options['validate-user'] ) || isset( $_SESSION['captcha_keystring'] ) && $_SESSION['captcha_keystring'] == $_POST['word-validation'] ) { // word validation passed
					$output = $this->send_email();
					if( $output === false )	$output = '<p id="sn-simple-form-error">' . __( 'There was an error processing your message, please try again.' , 'sn-simple-email' ) . '</p>' . $this->simple_email_form();
				} else { // word validation failed
					$output = '<p id="sn-simple-form-error">' . __( 'The word Verification you entered was incorrect, please try again.' , 'sn-simple-email' ) . '</p>' . $this->simple_email_form();
				}
			} else { // form not submitted so lets return it
				$output = $this->simple_email_form();
			}
			
			if( $output ) {
				return $output;
			} else {
				return false;
			}
		}

		function simple_email_form() {
			$output = '
				<div id="sn-simple-email-container">
					<form enctype="multipart/form-data" action="" method="post" id="sn-simple-email-form">';
			if( isset( $this->plugin_options['display-email'] ) && $this->plugin_options['display-email'] ) {
				$output .= '
						<p>
							<label for="author-email">' . __( 'To' , 'sn-simple-email' ) . ': </label>
							<input type="text" name="author-email" value="' . strtr( $this->plugin_options['your-email'] , $this->email_html_entities ) . '" readonly="true" />
						</p>';
			}
			$output .= '
						<p>
							<label for="user-name">' . __( 'Your Name' , 'sn-simple-email' ) . ': </label>
							<input type="text" name="user-name" value="' . ( isset( $_POST['user-name'] ) ? $_POST['user-name'] : '' ) . '" class="required" />
						</p>
						<p>
							<label for="user-email">' . __( 'Your Email' , 'sn-simple-email' ) . ': </label>
							<input type="text" name="user-email" value="' . ( isset( $_POST['user-name'] ) ? $_POST['user-email'] : '' ) . '" class="required email" />
						</p>
						<p>
							<label for="user-message">' . __( 'Message' , 'sn-simple-email' ) . ': </label>
							<textarea name="user-message" class="required">' . ( isset( $_POST['user-message'] ) ? $_POST['user-message'] : '' ) . '</textarea>
						</p>';
			if( isset( $this->plugin_options['validate-user'] ) && $this->plugin_options['validate-user'] ) {
				$output .= '
						<p>
							<img src="' . $this->plugin_url . '/captcha/index.php?' . session_name() . '=' . session_id() . '" alt="" />
						</p>
						<p>
							<label for="word-validation">' . __( 'Word Verification' , 'sn-simple-email' ) . ': </label>
							<input type="text" name="word-validation" value="" class="required" />
						</p>';
			}
			$output .= '
						<p>
							<input type="submit" name="submit" class="simple-email-button" value="' . __( 'Submit' , 'sn-simple-email' ) . '" />
							<input type="reset" name="reset" class="simple-email-button" value="' . __( 'Reset' , 'sn-simple-email' )  . '" />
						</p>
					</form>
				</div><!-- sn-simple-email-container -->';
			
			add_action( 'wp_footer' , array( &$this, 'jQuery_validation' ) );
			
			return $output;
		}
		
		function send_email() {
			// email subject
			$email_subject = sprintf( __( 'You have a new message from %1$s, sent from %2$s' , 'sn-simple-email' ) , $_POST['user-name'] , get_bloginfo( 'blogname' ) );

			// email message
			$email_message = '
				<html>
					<head>
						<title>' . sprintf( __( 'New message from %s' , 'sn-simple-email' ) , $_POST['user-name'] ) . '</title>
						<style>
							tr.odd{ background: #F3C068; }
							tr.even{ background: #FBEED7; }
						</style>
					</head>
					<body>
						<table cellpadding="5" cellspacing="2" width="600">
							<tr class="odd">
								<td><strong>' . __( 'From' , 'sn-simple-email' ) . ': </strong></td>
								<td>' . $_POST['user-name'] . '</td>
							</tr><tr class="even">
								<td><strong>' . __( 'Email' , 'sn-simple-email' ) . ': </strong></td>
								<td>' . $_POST['user-email'] . '</td>
							</tr><tr class="odd">
								<td><strong>' . __( 'IP' , 'sn-simple-email' ) . ': </strong></td>
								<td>' . $_SERVER['REMOTE_ADDR'] . '</td>
							</tr><tr class="even">
								<td valign="top"><strong>' . __( 'Message' , 'sn-simple-email' ) . ': </strong></td>
								<td>' . strip_tags( $_POST['user-message'] ) . '</td>
							</tr>
						</table>
					</body>
				</html>';
			
			// Always set content-type when sending HTML email
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";

			$mail_status = mail( $this->plugin_options['your-email'] , $email_subject , $email_message, $headers );

			if( $mail_status ) {
				$message = __( 'Thank you, your message was sent.' , 'sn-simple-email' );
				return $message;
			} else {
				return false;
			}
		}
		
		function plugin_css_query() {
			// css
			wp_enqueue_style( 'sn_simple_form-css' , $this->plugin_url . '/css/sn-simple-email.css' );
			
			// jQuery
			// causes issue with 3.1
			//wp_deregister_script('jquery');
			wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"), false, '1.4.2');
			wp_enqueue_script('jquery');
		}
		
		function jQuery_validation() { ?>
			<script type="text/javascript" src="<?php echo $this->plugin_url ?>/js/jquery.validate.js"></script>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$("#sn-simple-email-form").validate();
				});
			</script>
<?php	}

		function plugin_add_button() {
			// Don't bother doing this stuff if the current user lacks permissions
			if( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) return;

			// Add only in Rich Editor mode
			if( get_user_option( 'rich_editing' ) == 'true' ) {
				add_filter( 'mce_external_plugins' , array( &$this , 'add_plugin_tinymce_plugin' ) );
				add_filter( 'mce_buttons' , array( &$this , 'register_plugin_button' ) );
			}
		}

		function register_plugin_button( $buttons ) {
			array_push( $buttons , 'seperator' , 'snSimpleEmail' );
			return $buttons;
		}

		// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
		function add_plugin_tinymce_plugin( $plugin_array ) {
			$plugin_array['snSimpleEmail'] = $this->plugin_url . '/tinyMCE/editor_plugin.js';
			return $plugin_array;
		}
	}
}

// Instantiate the class
if ( class_exists( 'snSimpleEmail' ) ) {
	$sn_simple_email = new snSimpleEmail();
}