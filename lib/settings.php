<?php
/**
 * mojoreferral Link Creator - Settings Module
 *
 * Contains the specific settings page configuration
 *
 * @package mojoreferral Link Creator
 */
/*  Copyright 2015 Reaktiv Studios

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License (GPL v2) only.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( 'mojoreferralCreator_Settings' ) ) {

// Start up the engine
class mojoreferralCreator_Settings
{

	/**
	 * This is our constructor
	 *
	 * @return mojoreferralCreator_Settings
	 */
	public function __construct() {
		add_action( 'admin_menu',                   array( $this, 'mojoreferral_menu_item'    )           );
		add_action( 'admin_init',                   array( $this, 'reg_settings'        )           );
		add_action( 'admin_init',                   array( $this, 'store_settings'      )           );
		add_action( 'admin_notices',                array( $this, 'settings_messages'   )           );
		add_filter( 'plugin_action_links',          array( $this, 'quick_link'          ),  10, 2   );
	}

	/**
	 * show settings link on plugins page
	 *
	 * @param  [type] $links [description]
	 * @param  [type] $file  [description]
	 * @return [type]        [description]
	 */
	public function quick_link( $links, $file ) {

		static $this_plugin;

		if ( ! $this_plugin ) {
			$this_plugin = mojoreferral_BASE;
		}

		// check to make sure we are on the correct plugin
		if ( $file != $this_plugin ) {
			return $links;
		}

		// buil my link
		$single = '<a href="' . menu_page_url( 'mojoreferral-settings', 0 ) . '">' . __( 'Settings', 'wpmojoreferral' ) . '</a>';

		// get it in the group
		array_push( $links, $single );

		// return it
		return $links;
	}

	/**
	 * call the menu page for the mojoreferral settings
	 *
	 * @return void
	 */
	public function mojoreferral_menu_item() {
		add_options_page( __( 'Mojoreferral Settings', 'wpmojoreferral' ), __( 'Mojoreferral Settings', 'wpmojoreferral' ), apply_filters( 'mojoreferral_settings_cap', 'manage_options' ), 'mojoreferral-settings', array( __class__, 'mojoreferral_settings_page' ) );
	}

	/**
	 * Register settings
	 *
	 * @return
	 */
	public function reg_settings() {
		register_setting( 'mojoreferral_options', 'mojoreferral_options' );
	}

	/**
	 * check for, sanitize, and store our options
	 *
	 * @return [type] [description]
	 */
	public function store_settings() {

		// make sure we have our settings item
		if ( empty( $_POST['mojoreferral-options'] ) ) {
			return;
		}

		// verify our nonce
		if ( ! isset( $_POST['mojoreferral_settings_save'] ) || ! wp_verify_nonce( $_POST['mojoreferral_settings_save'], 'mojoreferral_settings_save_nonce' ) ) {
			return;
		}

		// cast our options as a variable
		$data   = (array) $_POST['mojoreferral-options'];

		// set an empty
		$store  = array();

		// check and sanitize the URL
		if ( ! empty( $data['url'] ) ) {
			$store['url']   = esc_url( mojoreferralCreator_Helper::strip_trailing_slash( $data['url'] ) );
		}

		// check and sanitize the API key
		if ( ! empty( $data['api'] ) ) {
			$store['api']   = sanitize_text_field( $data['api'] );
		}

		// check the boolean for autosave
		if ( ! empty( $data['mojo_login_url'] ) ) {
			$store['mojo_login_url']   = sanitize_text_field( $data['mojo_login_url'] );
		}
        
        // check the boolean for autosave
		if ( ! empty( $data['invite_button_text'] ) ) {
			$store['invite_button_text']   = sanitize_text_field( $data['invite_button_text'] );
		}

		// check the boolean for autosave
		if ( ! empty( $data['email_header'] ) ) {
			$store['email_header']   = sanitize_text_field( $data['email_header'] );
		}

		// check the boolean for autosave
		if ( ! empty( $data['mail_subject'] ) ) {
			$store['mail_subject']   = sanitize_text_field( $data['mail_subject'] );
		}

		// check the boolean for autosave
		if ( ! empty( $data['email_body'] ) ) {
			$store['email_body']   = sanitize_text_field( $data['email_body'] );
		}

		// check the boolean for autosave
		if ( ! empty( $data['email_footer'] ) ) {
			$store['email_footer']   = sanitize_text_field( $data['email_footer'] );
		}


		// check the boolean for autosave
		if ( ! empty( $data['text_with_tw_link'] ) ) {
			$store['text_with_tw_link']   = sanitize_text_field( $data['text_with_tw_link'] );
		}

		// check the boolean for autosave
		if ( ! empty( $data['mail_sent_msg'] ) ) {
			$store['mail_sent_msg']   = sanitize_text_field( $data['mail_sent_msg'] );
		}
	// check the boolean for autosave
		if ( ! empty( $data['color_1'] ) ) {
			$store['color_1']   = sanitize_text_field( $data['color_1'] );
		}
	// check the boolean for autosave
		if ( ! empty( $data['color_2'] ) ) {
			$store['color_2']   = sanitize_text_field( $data['color_2'] );
		}

	
		// pass it
		self::save_redirect_settings( $store );
	}

	/**
	 * save our settings and redirect to the proper place
	 *
	 * @param  array  $data [description]
	 * @param  string $key  [description]
	 * @return [type]       [description]
	 */
	public static function save_redirect_settings( $data = array(), $key = 'mojoreferral-settings' ) {
		// first purge the API check
		delete_option( 'mojoreferral_api_test' );

		// delete if empty, else go through some checks
		if ( empty( $data ) ) {
			// delete the key
			delete_option( 'mojoreferral_options' );
			// get the link
			$redirect   = self::get_settings_page_link( $key, 'mojoreferral-deleted=1' );
			// and redirect
			wp_redirect( $redirect, 302 );
			// and exit
			exit();
		}

		// we got something. check and store
		if ( get_option( 'mojoreferral_options' ) !== false ) {
			update_option( 'mojoreferral_options', $data );
		} else {
			add_option( 'mojoreferral_options', $data, null, 'no' );
		}

		// get the link
		$redirect   = self::get_settings_page_link( $key, 'mojoreferral-saved=1' );

		// and redirect
		wp_redirect( $redirect, 302 );

		// and exit
		exit();
	}

	/**
	 * display the admin settings based on the
	 * provided query string
	 *
	 * @return [type] [description]
	 */
	public function settings_messages() {

		// check for string first
		if ( empty( $_GET['mojoreferral-action'] ) ) {
			return;
		}

		// our saved
		if ( ! empty( $_GET['mojoreferral-saved'] ) ) {
			// the message
			echo '<div class="updated settings-error" id="setting-error-settings_updated">';
			echo '<p><strong>' . __( 'Your settings have been saved.', 'wpmojoreferral' ) . '</strong></p>';
			echo '</div>';
		}

		// our deleted
		if ( ! empty( $_GET['mojoreferral-deleted'] ) ) {
			// the message
			echo '<div class="error settings-error" id="setting-error-settings_updated">';
			echo '<p><strong>' . __( 'Your settings have been deleted.', 'wpmojoreferral' ) . '</strong></p>';
			echo '</div>';
		}
	}

	/**
	 * get the link of my settings page
	 *
	 * @param  string $page   [description]
	 * @param  string $string [description]
	 * @return [type]         [description]
	 */
	public static function get_settings_page_link( $page = 'mojoreferral-settings', $string = '' ) {

		// get the base
		$base   = menu_page_url( $page, 0 ) . '&mojoreferral-action=1';

		// build the link
		$link   = ! empty( $string ) ? $base . '&' . $string : $base;

		// return it as base or with a string
		return esc_url_raw( html_entity_decode( $link ) );
	}

	/**
	 * Display main options page structure
	 *
	 * @return void
	 */
	public static function mojoreferral_settings_page() {

		// bail if current user cannot manage options
		if(	! current_user_can( apply_filters( 'mojoreferral_settings_cap', 'manage_options' ) ) ) {
			return;
		}
		?>

		<div class="wrap">
		<h2><?php _e( 'Mojoreferral Link Creator Settings', 'wpmojoreferral' ); ?></h2>

		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<?php
			self::settings_side();
			self::settings_open();
			?>

		   	<div class="mojoreferral-form-text">
		   	<p><?php _e( 'Below are the basic settings for the mojoreferral creator. A reminder, your mojoreferral install cannot be public.', 'wpmojoreferral' ); ?></p>
			</div>

			<div class="mojoreferral-form-options">
				<form method="post" autocomplete="off">
				<?php
				// fetch our data for the settings
				$data   = mojoreferralCreator_Helper::get_mojoreferral_option();

				// filter and check each one
				$url    = ! empty( $data['url'] ) ? $data['url'] : '';
				$api    = ! empty( $data['api'] ) ? $data['api'] : '';
				
				if(isset($data['mojo_login_url']) && !empty($data['mojo_login_url'])){
				  $login_url =  $data['mojo_login_url'];
				    
				}else {
				    $login_url =  '';
				}
				
				
    			$email_header    = ! empty( $data['email_header'] ) ? $data['email_header'] : '';
    			$email_body    = ! empty( $data['email_body'] ) ? $data['email_body'] : '';
    			$email_footer    = ! empty( $data['email_footer'] ) ? $data['email_footer'] : '';
    			$text_with_fb_link    = ! empty( $data['text_with_fb_link'] ) ? $data['text_with_fb_link'] : '';
    			$text_with_tw_link    = ! empty( $data['text_with_tw_link'] ) ? $data['text_with_tw_link'] : '';
    			$mail_sent_msg   = ! empty( $data['mail_sent_msg'] ) ? $data['mail_sent_msg'] : '';
    			$mail_subject   = ! empty( $data['mail_subject'] ) ? $data['mail_subject'] : '';
    			$invite_button_text   = ! empty( $data['invite_button_text'] ) ? $data['invite_button_text'] : '';
    			$color_1   = ! empty( $data['color_1'] ) ? $data['color_1'] : '';
    			$color_2   = ! empty( $data['color_2'] ) ? $data['color_2'] : '';
				
             	// load the settings fields
				wp_nonce_field( 'mojoreferral_settings_save_nonce', 'mojoreferral_settings_save', false, true );
				?>

				<table class="form-table mojoreferral-table">
				 <thead>
				     <tr>
				         <th cpolspan="3">
				             <h3><?php _e( 'General Settings', 'wpmojoreferral' ); ?></h3>
				        </th>
			        </tr>
				</thead>
				<tbody>
					<tr>
						<th><?php _e( 'Mojoreferral Custom URL', 'wpmojoreferral' ); ?></th>
						<td>
							<input type="url" class="regular-text code" value="<?php echo esc_url( $url ); ?>" id="mojoreferral-url" name="mojoreferral-options[url]">
							<p class="description"><?php _e( 'Enter the domain URL for your mojoreferral API', 'wpmojoreferral' ); ?></p>
						</td>
					</tr>

					<tr>
						<th><?php _e( 'Mojoreferral API Signature Key', 'wpmojoreferral' ); ?></th>
						<td class="apikey-field-wrapper">
							<input type="text" class="regular-text code" value="<?php echo esc_attr( $api ); ?>" id="mojoreferral-api" name="mojoreferral-options[api]" autocomplete="off">
							<span class="dashicons dashicons-visibility password-toggle"></span>
							<p class="description"><?php _e('Found in the tools section on your mojoreferral admin page.', 'wpmojoreferral') ?></p>
						</td>
					</tr>
	                <tr>
						<th><?php _e( 'Mojoreferral Login URL' ); ?></th>
						<td>
							<input type="text" class="regular-text code" autocomplete="false" id="mojoreferral-login_url" name="mojoreferral-options[mojo_login_url]" value="<?php echo  $login_url ; ?>" />
							<p class="description"><?php _e( 'Enter the domain URL for your mojoreferral API', 'wpmojoreferral' ); ?></p>
						</td>
					</tr> 
					<tr>
						<th><?php _e( 'Background Colors' ); ?></th>
						<td>
						 <div class="pagebox">
                            <p class="separator">
                                <input class="color-field" type="text" name="mojoreferral-options[color_1]" value="<?php esc_attr_e($color_1); ?>"/>
                            </p>
                        </div>
						</td>
					</tr>
                    <tr>
						<th><?php _e( 'Background Colors' ); ?></th>
						<td>
						 <div class="pagebox">
                                <p class="separator">
                                    <input class="color-field" type="text" name="mojoreferral-options[color_2]" value="<?php esc_attr_e($color_2); ?>"/>
                                </p>
                            </div>
				     	</td>
					</tr>
					</tbody>
				</table>
				
				<table class="form-table mojoreferral-table">
				 <thead>
				     <tr>
				         <th cpolspan="3">
				             <h3><?php _e( 'Email Settings', 'wpmojoreferral' ); ?></h3>
				        </th>
			        </tr>
				</thead>
				<tbody>
					<tr>
						<th><?php _e( 'Invite Button text', 'wpmojoreferral' ); ?></th>
						<td class="apikey-field-wrapper">
							<input  class="regular-text code" id="mojoreferral-invite_button_text" name="mojoreferral-options[invite_button_text]" autocomplete="off" value="<?php echo esc_attr( $invite_button_text ); ?>">
						    <p class="description"><?php _e('Please enter the text for the invite button.', 'wpmojoreferral') ?></p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Email Subject', 'wpmojoreferral' ); ?></th>
						<td class="apikey-field-wrapper">
							<input  class="regular-text code" id="mojoreferral-mail_subject" name="mojoreferral-options[mail_subject]" autocomplete="off" value="<?php echo esc_attr( $mail_subject ); ?>">
						    <p class="description"><?php _e('Please enter the subject for the sharing email.', 'wpmojoreferral') ?></p>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Email Header', 'wpmojoreferral' ); ?></th>
						<td>
							<textarea class="regular-text code" id="mojoreferral-email_header" name="mojoreferral-options[email_header]" autocomplete="off"><?php echo esc_attr( $email_header ); ?></textarea>
							<p class="description"><?php _e( 'Please enter the header text for the email. (Hello there, Welcome user etc...)', 'wpmojoreferral' ); ?></p>
						</td>
					</tr>

					<tr>
						<th><?php _e( 'Email body', 'wpmojoreferral' ); ?></th>
						<td class="apikey-field-wrapper">
							<textarea class="regular-text code" id="mojoreferral-email_body" name="mojoreferral-options[email_body]" autocomplete="off"><?php echo esc_attr( $email_body ); ?></textarea>
						<p class="description"><?php _e('Please enter the email body for the link sharing by email body. You can use [SENDER_NAME] and [SENDER_EMAIL] shortcodes with the mail body.', 'wpmojoreferral') ?></p>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Email Footer', 'wpmojoreferral' ); ?></th>
						<td class="apikey-field-wrapper">
							<textarea class="regular-text code" id="mojoreferral-email_footer" name="mojoreferral-options[email_footer]" autocomplete="off"><?php echo esc_attr( $email_footer ); ?></textarea>
						<p class="description"><?php _e('Please enter the email footer for the link sharing by email footer.', 'wpmojoreferral') ?></p>
						</td>
					</tr>

					<tr>
						<th><?php _e( 'Email sent message', 'wpmojoreferral' ); ?></th>
						<td class="apikey-field-wrapper">
							<textarea class="regular-text code" id="mojoreferral-mail_sent_msg" name="mojoreferral-options[mail_sent_msg]" autocomplete="off"><?php echo esc_attr( $mail_sent_msg ); ?></textarea>
						<p class="description"><?php _e('Please enter the message to show after mail sent.', 'wpmojoreferral') ?></p>
						</td>
					</tr>
				</tbody>
				</table>
				
				<table class="form-table mojoreferral-table">
				 <thead>
				     <tr>
				         <th cpolspan="3">
				             <h3><?php _e( 'Twitter Settings', 'wpmojoreferral' ); ?></h3>
				        </th>
			        </tr>
				</thead>
				<tbody>
					<tr>
						<th><?php _e( 'Twitter text', 'wpmojoreferral' ); ?></th>
						<td>
							<input type="text" class="regular-text code" autocomplete="false" id="mojoreferral-text_with_tw_link" name="mojoreferral-options[text_with_tw_link]" value="<?php echo  $text_with_tw_link ; ?>" />
							<p class="description"><?php _e( 'This text will be shared with twitter link.', 'wpmojoreferral' ); ?></p>
						</td>
					</tr>
				</tbody>
				</table>

				<p><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /></p>
				</form>

			</div>

		<?php self::settings_close(); ?>

		</div>
		</div>

	<?php }


	/**
	 * Some extra stuff for the settings page
	 *
	 * this is just to keep the area cleaner
	 *
	 */
	public static function settings_side() { ?>

		<div id="side-info-column" class="inner-sidebar">

			<div class="meta-box-sortables">
				<?php self::sidebox_status(); ?>
			</div>

		</div> <!-- // #side-info-column .inner-sidebar -->

	<?php }

	/**
	 * the status sidebox
	 */
	public static function sidebox_status() {

		// get my API status data
		if ( false === $data = mojoreferralCreator_Helper::get_api_status_data() ) {
			return;
		}
		?>

		<div id="mojoreferral-admin-status" class="postbox mojoreferral-sidebox">
			<h3 class="hndle" id="status-sidebar"><?php echo $data['icon']; ?><?php _e( 'API Status Check', 'wpmojoreferral' ); ?></h3>
			<div class="inside">
				<form>

				<p class="api-status-text"><?php echo esc_attr( $data['text'] ); ?></p>

				<p class="api-status-actions">
					<input type="button" class="mojoreferral-click-status button-primary" value="<?php _e( 'Check Status', 'wpmojoreferral' ); ?>" >
					<span class="spinner mojoreferral-spinner mojoreferral-status-spinner"></span>
					<?php wp_nonce_field( 'mojoreferral_status_nonce', 'mojoreferral_status', false, true ); ?>
				</p>

				</form>
			</div>
		</div>
		
		<div id="mojoreferral-admin-shortcode" class="postbox mojoreferral-sidebox">
			<h3 class="hndle" id="shortcode-sidebar"><?php _e( 'Plugin Shortcode', 'wpmojoreferral' ); ?></h3>
			<div class="inside">
		
				<p class="api-status-actions">
    		        <?php _e( 'These are our main shortcodes you can use this plugin usig these shortcodes.', 'wpmojoreferral' ); ?>
    			</p>
    			<ul>
    			    <li>
    			        1. <?php _e( 'Show the sharing section', 'wpmojoreferral' ); ?>:- [mojo_referral] 
    			    </li> 
    			    <li>
    			        2. <?php _e( 'Show the list of all referred users by the user', 'wpmojoreferral' ); ?>:- [mojoreferreduserlist]
    			    </li>
    			    <li>
    			        3. <?php _e( 'Show the counts of click and referred users for all links', 'wpmojoreferral' ); ?>:- [mojoreferralcountlist]
    			    </li>
    			</ul>

				</form>
			</div>
		</div>

	<?php }

	/**
	 * open up the settings page markup
	 */
	public static function settings_open() { ?>

		<div id="post-body" class="has-sidebar">
			<div id="post-body-content" class="has-sidebar-content">
				<div id="normal-sortables" class="meta-box-sortables">
					<div id="about" class="postbox">
						<div class="inside">

	<?php }

	/**
	 * close out the settings page markup
	 */
	public static function settings_close() { ?>

						<br class="clear" />
						</div>
					</div>
				</div>
			</div>
		</div>

	<?php }

// end class
}

// end exists check
}

// Instantiate our class
new mojoreferralCreator_Settings();

