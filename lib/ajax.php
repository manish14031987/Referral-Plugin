<?php
/**
 * mojoreferral Link Creator - Ajax Module
 *
 * Contains our ajax related functions
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

if ( ! class_exists( 'mojoreferralCreator_Ajax' ) ) {

// Start up the engine
class mojoreferralCreator_Ajax
{

	/**
	 * This is our constructor
	 *
	 * @return mojoreferralCreator_Ajax
	 */
	public function __construct() {
		add_action( 'wp_ajax_create_mojoreferral',        array( $this, 'create_mojoreferral'       )           );
		add_action( 'wp_ajax_delete_mojoreferral',        array( $this, 'delete_mojoreferral'       )           );
		add_action( 'wp_ajax_stats_mojoreferral',         array( $this, 'stats_mojoreferral'        )           );
		add_action( 'wp_ajax_inline_mojoreferral',        array( $this, 'inline_mojoreferral'       )           );
		add_action( 'wp_ajax_inline_mojoreferral_front',        array( $this, 'inline_mojoreferral_front' )     );
		add_action( 'wp_ajax_nopriv_inline_mojoreferral_front',        array( $this,'inline_mojoreferral_front'));
		add_action( 'wp_ajax_status_mojoreferral',        array( $this, 'status_mojoreferral'       )           );
		add_action( 'wp_ajax_refresh_mojoreferral',       array( $this, 'refresh_mojoreferral'      )           );
		add_action( 'wp_ajax_convert_mojoreferral',       array( $this, 'convert_mojoreferral'      )           );
		add_action( 'wp_ajax_import_mojoreferral',        array( $this, 'import_mojoreferral'       )           );
		add_action( 'wp_ajax_inline_mojoreferral_share_via_email_front',        array( $this, 'inline_mojoreferral_share_via_email_front' )     );
		add_action( 'wp_ajax_nopriv_inline_mojoreferral_share_via_email_front',        array( $this,'inline_mojoreferral_share_via_email_front'));
	}

	/**
	 * Create shortlink function
	 */
	public function create_mojoreferral() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'mojoreferral_editor_create', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = mojoreferralCreator_Helper::get_mojoreferral_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post_id ), mojoreferralCreator_Helper::get_mojoreferral_status() ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'INVALID_STATUS';
			$ret['message'] = __( 'This is not a valid post status.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a URL
		if ( false !== $link = mojoreferralCreator_Helper::get_mojoreferral_meta( $post_id, '_mojoreferral_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'URL_EXISTS';
			$ret['message'] = __( 'A URL already exists.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a permalink
		if ( false === $url = mojoreferralCreator_Helper::prepare_api_link( $post_id ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_PERMALINK';
			$ret['message'] = __( 'No permalink could be retrieved.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// check for keyword and get the title
		$keyword = ! empty( $_POST['keyword'] ) ? mojoreferralCreator_Helper::prepare_api_keyword( $_POST['keyword'] ) : '';
		$title   = get_the_title( $post_id );

		// set my args for the API call
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keyword );

		// make the API call
		$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'shorturl', $args );

		// bail if empty data
		if ( empty( $build ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( false === $build['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// we have done our error checking and we are ready to go
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// get my short URL
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// update the post meta
			update_post_meta( $post_id, '_mojoreferral_url', $shorturl );
			update_post_meta( $post_id, '_mojoreferral_clicks', '0' );

			// and do the API return
			$ret['success'] = true;
			$ret['message'] = __( 'You have created a new mojoreferral link.', 'wpmojoreferral' );
			$ret['linkurl'] = $shorturl;
			$ret['linkbox'] = mojoreferralCreator_Helper::get_mojoreferral_linkbox( $shorturl, $post_id );
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wpmojoreferral' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * Delete shortlink function
	 */
	public function delete_mojoreferral() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'mojoreferral_editor_delete', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = mojoreferralCreator_Helper::get_mojoreferral_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// do a quick check for a URL
		if ( false === $link = mojoreferralCreator_Helper::get_mojoreferral_meta( $post_id, '_mojoreferral_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_URL_EXISTS';
			$ret['message'] = __( 'There is no URL to delete.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// passed it all. go forward
		delete_post_meta( $post_id, '_mojoreferral_url' );
		delete_post_meta( $post_id, '_mojoreferral_clicks' );

		// and do the API return
		$ret['success'] = true;
		$ret['message'] = __( 'You have removed your mojoreferral link.', 'wpmojoreferral' );
		$ret['linkbox'] = mojoreferralCreator_Helper::get_mojoreferral_subbox( $post_id );
		echo json_encode( $ret );
		die();
	}

	/**
	 * retrieve stats
	 */
	public function stats_mojoreferral() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// bail if the API key or URL have not been entered
		if(	false === $api = mojoreferralCreator_Helper::get_mojoreferral_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// verify our nonce
		$check	= check_ajax_referer( 'mojoreferral_inline_update_' . absint( $post_id ), 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// get my click number
		$clicks = mojoreferralCreator_Helper::get_single_click_count( $post_id );

		// bad API call
		if ( empty( $clicks['success'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = $clicks['errcode'];
			$ret['message'] = $clicks['message'];
			echo json_encode( $ret );
			die();
		}

		// got it. update the meta
		update_post_meta( $post_id, '_mojoreferral_clicks', $clicks['clicknm'] );

		// and do the API return
		$ret['success'] = true;
		$ret['message'] = __( 'Your mojoreferral click count has been updated', 'wpmojoreferral' );
		$ret['clicknm'] = $clicks['clicknm'];
		echo json_encode( $ret );
		die();
	}

	/**
	 * Create shortlink function inline. Called on ajax
	 */
	public function inline_mojoreferral() {
        
       	// only run on admin
		if ( ! is_admin() ) {
			die();
		}


		// start our return
		$ret = array();

		// bail if the API key or URL have not been entered
		if(	false === $api = mojoreferralCreator_Helper::get_mojoreferral_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post_id ), mojoreferralCreator_Helper::get_mojoreferral_status() ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'INVALID_STATUS';
			$ret['message'] = __( 'This is not a valid post status.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// verify our nonce
		$check	= check_ajax_referer( 'mojoreferral_inline_create_' . absint( $post_id ), 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a URL
		if ( false !== $link = mojoreferralCreator_Helper::get_mojoreferral_meta( $post_id, '_mojoreferral_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'URL_EXISTS';
			$ret['message'] = __( 'A URL already exists.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a permalink
		if ( false === $url = mojoreferralCreator_Helper::prepare_api_link( $post_id ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_PERMALINK';
			$ret['message'] = __( 'No permalink could be retrieved.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// get my post URL and title
		$title  = get_the_title( $post_id );

		// check for a keyword
		$keywd  = mojoreferralCreator_Helper::get_mojoreferral_keyword( $post_id );

		// set my args for the API call
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );

		// make the API call
		$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'shorturl', $args );

		// bail if empty data
		if ( empty( $build ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( false === $build['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// we have done our error checking and we are ready to go
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// get my short URL
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// update the post meta
			update_post_meta( $post_id, '_mojoreferral_url', $shorturl );
			update_post_meta( $post_id, '_mojoreferral_clicks', '0' );

			// and do the API return
			$ret['success'] = true;
			$ret['message'] = __( 'You have created a new mojoreferral link.', 'wpmojoreferral' );
			$ret['rowactn'] = '<span class="update-mojoreferral">' . mojoreferralCreator_Helper::update_row_action( $post_id ) . '</span>';
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wpmojoreferral' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * Share shortlink function inline. Called on ajax from frontend to share via Email
	 */
	public function inline_mojoreferral_share_via_email_front() {
	    if(isset($_POST['mojo_referral_url']) && $_POST['mojo_referral_url'] != '' ){
	        
    	$data   = mojoreferralCreator_Helper::get_mojoreferral_option();    
    	
        $mail_subject   = ! empty( $data['mail_subject'] ) ? $data['mail_subject'] : bloginfo().' link sharing';
		$email_header    = ! empty( $data['email_header'] ) ? $data['email_header'] : 'Hello there,';
		$email_body    = ! empty( $data['email_body'] ) ? $data['email_body'] : 'Your are invited by [SENDER_NAME]([SENDER_EMAIL]).';
		$email_footer    = ! empty( $data['email_footer'] ) ? $data['email_footer'] : 'Thanks';
    	
        $find_sender_email = '[SENDER_EMAIL]';
	    $replace = wp_get_current_user(get_current_user_id())->user_email;
        $email_body = str_replace('[SENDER_EMAIL]', $replace, $email_body);
        
        
        
    	$find_sender_name = '[SENDER_NAME]';
	    $replace = wp_get_current_user(get_current_user_id())->display_name;
        
        $email_body = str_replace('[SENDER_NAME]', $replace, $email_body);
        
            $referral_url =  $_POST['mojo_referral_url'];
	        foreach($_POST['emails'] as $key => $value){
	            
                $emailHtml = $email_header.'<br>';
                $emailHtml .= $email_body.'<br>';
                $emailHtml .= '<a href="';
                $emailHtml .= $referral_url;
                $emailHtml .= '">Click here</a> to visit the referred site.<br>';
                $emailHtml .=  $email_footer;
                
                mail($value,$mail_subject,$emailHtml);
                $ret['success'] = true;
                $ret['message'] = __( 'Email sent to '.$value.'.', 'wpmojoreferral' );
                echo json_encode( $ret ); 
	           
	       } 
	       die;
	    }
	}
	
	
	
	/**
	 * Create shortlink function inline. Called on ajax from frontend
	 */
	public function inline_mojoreferral_front() {
        

		// start our return
		$ret = array();

		// bail if the API key or URL have not been entered
		if(	false === $api = mojoreferralCreator_Helper::get_mojoreferral_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['user_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_USER_ID';
			$ret['message'] = __( 'No User present.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// now cast the user ID
		$user_id    = absint( $_POST['user_id'] );

		// verify our nonce
		$check	= check_ajax_referer( 'mojoreferral_inline_create_front', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		    $meta_key = 'mojoreferral_created';
			$user_info = get_userdata($_POST['user_id']);
			$user_id = $_POST['user_id'];
        	$url_exits =  get_user_meta($user_id, $meta_key)[0];
        	if($url_exits != 1 ){
    			$shorturl   = esc_url( $build['data']['shorturl'] );
                $meta_key = 'mojoreferral_created';
                $meta_value = 1;
    			update_user_meta( $user_id, $meta_key, $meta_value );
    			
    			
    			$url_cp  =  urlencode(site_url().'?ref=cp&user='.$user_id);
    			$title = 'Copy url for '.$user_info->user_login. '('.$user_id.')';
        		// set my args for the API call
        		$args   = array( 'url' => esc_url( $url_cp ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );
    			$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'shorturl', $args );
    			$mojoreferral_cp = $build;
    			$meta_key_mojoreferral_url_cp = 'mojoreferral_url_cp';
    			$meta_value_mojoreferral_url_cp = $mojoreferral_cp['data']['shorturl'];
    			update_user_meta( $user_id, $meta_key_mojoreferral_url_cp, $meta_value_mojoreferral_url_cp );
    			$ret['mojoreferral_url_cp'] = $meta_value_mojoreferral_url_cp;
    			
    			$url_fb  =  urlencode(site_url().'?ref=fb&user='.$user_id);
    			$title = 'Facebook url for '.$user_info->user_login. '('.$user_id.')';
        		// set my args for the API call
        		$args   = array( 'url' => esc_url( $url_fb ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );
    			$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'shorturl', $args );
    			$mojoreferral_fb = $build;
    			$meta_key_mojoreferral_url_fb = 'mojoreferral_url_fb';
    			$meta_value_mojoreferral_url_fb = $mojoreferral_fb['data']['shorturl'];
    			update_user_meta( $user_id, $meta_key_mojoreferral_url_fb, $meta_value_mojoreferral_url_fb );
    			$ret['mojoreferral_url_fb'] = $meta_value_mojoreferral_url_fb;
    			
    			
    			$url_tw  =  urlencode(site_url().'?ref=tw&user='.$user_id);
    			$title = 'Twitter url for '.$user_info->user_login. '('.$user_id.')';
        		// set my args for the API call
        		$args   = array( 'url' => esc_url( $url_tw ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );
    			$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'shorturl', $args );
    			$mojoreferral_tw = $build;
    			$meta_key_mojoreferral_url_tw = 'mojoreferral_url_tw';
    			$meta_value_mojoreferral_url_tw = $mojoreferral_tw['data']['shorturl'];
    			update_user_meta( $user_id, $meta_key_mojoreferral_url_tw, $meta_value_mojoreferral_url_tw );
    			$ret['mojoreferral_url_tw'] = $meta_value_mojoreferral_url_tw;
    			
    			
    			$url_em  =  urlencode(site_url().'?ref=em&user='.$user_id);
    			$title = 'Email url for '.$user_info->user_login. '('.$user_id.')';
        		// set my args for the API call
        		$args   = array( 'url' => esc_url( $url_em ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );
    			$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'shorturl', $args );
    			$mojoreferral_em = $build;
    			$meta_key_mojoreferral_url_em = 'mojoreferral_url_em';
    			$meta_value_mojoreferral_url_em = $mojoreferral_em['data']['shorturl'];
    			update_user_meta( $user_id, $meta_key_mojoreferral_url_em, $meta_value_mojoreferral_url_em );
    			$ret['mojoreferral_url_em'] = $meta_value_mojoreferral_url_em;
    			
    			$ret['success'] = true;
    			$ret['message'] = __( 'You have created all mojoreferral link for this user.', 'wpmojoreferral' );
    			echo json_encode( $ret );
                die;
        	} else{	
    			$ret['success'] = false;
    			$ret['message'] = __( 'Mojoreferral links for thhis user are already available in database.', 'wpmojoreferral' );
    			echo json_encode( $ret );
                die;
        	}
			// and do the API return
		/*	$ret['success'] = true;
			$ret['message'] = __( 'You have created a new mojoreferral link.', 'wpmojoreferral' );
			$ret['rowactn'] = '<span class="update-mojoreferral">' . mojoreferralCreator_Helper::update_row_action( $post_id ) . '</span>';
			echo json_encode( $ret );
			die();
		}*/

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wpmojoreferral' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * run the status check on call
	 */
	public function status_mojoreferral() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'mojoreferral_status_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = mojoreferralCreator_Helper::get_mojoreferral_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// make the API call
		$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'db-stats' );

		// handle the check and set it
		$check  = ! empty( $build ) && false !== $build['success'] ? 'connect' : 'noconnect';

		// set the option return
		if ( false !== get_option( 'mojoreferral_api_test' ) ) {
			update_option( 'mojoreferral_api_test', $check );
		} else {
			add_option( 'mojoreferral_api_test', $check, null, 'no' );
		}

		// now get the API data
		$data	= mojoreferralCreator_Helper::get_api_status_data();

		// check to see if no data happened
		if( empty( $data ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_STATUS_DATA';
			$ret['message'] = __( 'The status of the mojoreferral API could not be determined.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// if we have data, send back things
		if(	! empty( $data ) ) {
			$ret['success'] = true;
			$ret['errcode'] = null;
			$ret['baricon'] = $data['icon'];
			$ret['message'] = $data['text'];
			$ret['stcheck'] = '<span class="dashicons dashicons-yes api-status-checkmark"></span>';
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wpmojoreferral' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * run update job to get click counts via manual ajax
	 */
	public function refresh_mojoreferral() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'mojoreferral_refresh_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = mojoreferralCreator_Helper::get_mojoreferral_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// fetch the IDs that contain a mojoreferral url meta key
		if ( false === $items = mojoreferralCreator_Helper::get_mojoreferral_post_ids() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_IDS';
			$ret['message'] = __( 'There are no items with stored URLs.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// loop the IDs
		foreach ( $items as $item_id ) {

			// get my click number
			$clicks = mojoreferralCreator_Helper::get_single_click_count( $item_id );

			// bad API call
			if ( empty( $clicks['success'] ) ) {
				$ret['success'] = false;
				$ret['errcode'] = $clicks['errcode'];
				$ret['message'] = $clicks['message'];
				echo json_encode( $ret );
				die();
			}

			// got it. update the meta
			update_post_meta( $item_id, '_mojoreferral_clicks', $clicks['clicknm'] );
		}

		// and do the API return
		$ret['success'] = true;
		$ret['message'] = __( 'The click counts have been updated', 'wpmojoreferral' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * convert from Ozh (and Otto's) plugin
	 */
	public function convert_mojoreferral() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// verify our nonce
		$check	= check_ajax_referer( 'mojoreferral_convert_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// filter our key to replace
		$key = apply_filters( 'mojoreferral_key_to_convert', 'mojoreferral_shorturl' );

		// fetch the IDs that contain a mojoreferral url meta key
		if ( false === $items = mojoreferralCreator_Helper::get_mojoreferral_post_ids( $key ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_KEYS';
			$ret['message'] = __( 'There are no meta keys to convert.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// set up SQL query
		global $wpdb;

		// prepare my query
		$setup  = $wpdb->prepare("
			UPDATE $wpdb->postmeta
			SET    meta_key = '%s'
			WHERE  meta_key = '%s'
			",
			esc_sql( '_mojoreferral_url' ), esc_sql( $key )
		);

		// run SQL query
		$query = $wpdb->query( $setup );

		// start our return
		$ret = array();

		// no matches, return message
		if( $query == 0 ) {
			$ret['success'] = false;
			$ret['errcode'] = 'KEY_MISSING';
			$ret['message'] = __( 'There are no keys matching this criteria. Please try again.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// we had matches. return the success message with a count
		if( $query > 0 ) {
			$ret['success'] = true;
			$ret['errcode'] = null;
			$ret['updated'] = $query;
			$ret['message'] = sprintf( _n( '%d key has been updated.', '%d keys have been updated.', $query, 'wpmojoreferral' ), $query );
			echo json_encode( $ret );
			die();
		}
	}

	/**
	 * check the mojoreferral install for existing links
	 * and pull the data if it exists
	 */
	public function import_mojoreferral() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// verify our nonce
		$check	= check_ajax_referer( 'mojoreferral_import_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = mojoreferralCreator_Helper::get_mojoreferral_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// set my args for the API call
		$args   = array( 'filter' => 'top', 'limit' => apply_filters( 'mojoreferral_import_limit', 999 ) );

		// make the API call
		$fetch  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'stats', $args );

		// bail if empty data
		if ( empty( $fetch ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( false === $fetch['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( empty( $fetch['data']['links'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_LINKS';
			$ret['message'] = __( 'There was no available link data to import.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// filter the incoming for matching links
		$filter = mojoreferralCreator_Helper::filter_mojoreferral_import( $fetch['data']['links'] );

		// bail error received
		if ( empty( $filter ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_MATCHING_LINKS';
			$ret['message'] = __( 'There were no matching links to import.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// set a false flag
		$error  = false;

		// now filter them
		foreach ( $filter as $item ) {

			// do the import
			$import = mojoreferralCreator_Helper::maybe_import_link( $item );

			// bail error received
			if ( empty( $import ) ) {
				$error  = true;
				break;
			}
		}

		// bail if we had true on the import
		if ( true === $error ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_IMPORT_ACTION';
			$ret['message'] = __( 'The data could not be imported.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// hooray. it worked. do the ajax return
		if ( false === $error ) {
			$ret['success'] = true;
			$ret['message'] = __( 'All available mojoreferral data has been imported.', 'wpmojoreferral' );
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'wpmojoreferral' );
		echo json_encode( $ret );
		die();
	}

// end class
}

// end exists check
}

// Instantiate our class
new mojoreferralCreator_Ajax();

