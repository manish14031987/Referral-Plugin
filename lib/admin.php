<?php
/**
 * mojoreferral Link Creator - Admin Module
 *
 * Contains admin related functions
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

if ( ! class_exists( 'mojoreferralCreator_Admin' ) ) {

// Start up the engine
class mojoreferralCreator_Admin
{

	/**
	 * This is our constructor
	 *
	 * @return mojoreferralCreator_Admin
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts',        array( $this, 'scripts_styles'      ),  10      );
		add_action( 'show_user_profile', array( $this, 'mojo_referral_additional_profile_fields' ));
        add_action( 'edit_user_profile', array( $this, 'mojo_referral_additional_profile_fields' ));
        add_action('restrict_manage_users', array( $this,'mojoreferral_filter_by_referral_by'));
        add_filter('pre_get_users', array( $this,'mojoreferral_filter_users_by_user'));
        add_action('restrict_manage_users', array( $this,'mojoreferral_filter_by_referral_via'));
   
	
	}

	/**
	 * scripts and stylesheets
	 *
	 * @param  [type] $hook [description]
	 * @return [type]       [description]
	 */
	public function scripts_styles( $hook ) {

		// bail if not on the right part
		if ( ! in_array( $hook, array( 'settings_page_mojoreferral-settings', 'edit.php', 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		// set our JS and CSS prefixes
		$css_sx = defined( 'WP_DEBUG' ) && WP_DEBUG ? '.css' : '.min.css';
		$js_sx  = defined( 'WP_DEBUG' ) && WP_DEBUG ? '.js' : '.min.js';

		// load the password stuff on just the settings page
		if ( $hook == 'settings_page_mojoreferral-settings' ) {
			wp_enqueue_script( 'hideshow', plugins_url( '/js/hideShowPassword' . $js_sx, __FILE__ ) , array( 'jquery' ), '2.0.3', true );
		}

		// load our files
		wp_enqueue_style( 'wp-color-picker');
    	wp_enqueue_script( 'wp-color-picker');
		wp_enqueue_style( 'mojoreferral-admin', plugins_url( '/css/mojoreferral-admin' . $css_sx, __FILE__ ), array(), YOURS_VER, 'all' );
		wp_enqueue_script( 'mojoreferral-admin', plugins_url( '/js/mojoreferral-admin' . $js_sx, __FILE__ ) , array( 'jquery' ), YOURS_VER, true );
		wp_localize_script( 'mojoreferral-admin', 'mojoreferralAdmin', array(
			'shortSubmit'   => '<a onclick="prompt(\'URL:\', jQuery(\'#shortlink\').val()); return false;" class="button button-small" href="#">' . __( 'Get Shortlink' ) . '</a>',
			'defaultError'  => __( 'There was an error with your request.' )
		));
	}

	/**
	 * call the metabox if on an appropriate
	 * post type and post status
	 *
	 * @return [type] [description]
	 */
	public function mojoreferral_metabox() {

		// fetch the global post object
		global $post;

		// make sure we're working with an approved post type
		if ( ! in_array( $post->post_type, mojoreferralCreator_Helper::get_mojoreferral_types() ) ) {
			return;
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = mojoreferralCreator_Helper::get_mojoreferral_api_data() ) {
			return;
		}

		// only fire if user has the option
		if(	false === $check = mojoreferralCreator_Helper::check_mojoreferral_cap() ) {
			return;
		}

		// now add the meta box
		add_meta_box( 'mojoreferral-post-display', __( 'mojoreferral Shortlink', 'wpmojoreferral' ), array( __class__, 'mojoreferral_post_display' ), $post->post_type, 'side', 'high' );
	}

	/**
	 * Display mojoreferral shortlink if present
	 *
	 * @param  [type] $post [description]
	 * @return [type]       [description]
	 */
	public static function mojoreferral_post_display( $post ) {

		// cast our post ID
		$post_id    = absint( $post->ID );

		// check for a link and click counts
		$link   = mojoreferralCreator_Helper::get_mojoreferral_meta( $post_id, '_mojoreferral_url' );

		// if we have no link, display our box
		if ( empty( $link ) ) {

			// display the box
			echo mojoreferralCreator_Helper::get_mojoreferral_subbox( $post_id );

			// and return
			return;
		}

		// we have a shortlink. show it along with the count
		if( ! empty( $link ) ) {

			// get my count
			$count  = mojoreferralCreator_Helper::get_mojoreferral_meta( $post_id, '_mojoreferral_clicks', '0' );

			// and echo the box
			echo mojoreferralCreator_Helper::get_mojoreferral_linkbox( $link, $post_id, $count );
		}
	}

	/**
	 * our check for a custom mojoreferral keyword
	 *
	 * @param  integer $post_id [description]
	 *
	 * @return void
	 */
	public function mojoreferral_keyword( $post_id ) {

		// run various checks to make sure we aren't doing anything weird
		if ( mojoreferralCreator_Helper::meta_save_check( $post_id ) ) {
			return;
		}

		// make sure we're working with an approved post type
		if ( ! in_array( get_post_type( $post_id ), mojoreferralCreator_Helper::get_mojoreferral_types() ) ) {
			return;
		}

		// we have a keyword and we're going to store it
		if( ! empty( $_POST['mojoreferral-keyw'] ) ) {

			// sanitize it
			$keywd  = mojoreferralCreator_Helper::prepare_api_keyword( $_POST['mojoreferral-keyw'] );

			// update the post meta
			update_post_meta( $post_id, '_mojoreferral_keyword', $keywd );
		} else {
			// delete it if none was passed
			delete_post_meta( $post_id, '_mojoreferral_keyword' );
		}
	}

	/**
	 * Create mojoreferral link on publish if one doesn't exist
	 *
	 * @param  integer $post_id [description]
	 *
	 * @return void
	 */
	public function mojoreferral_on_save( $post_id ) {

		// bail if this is an import since it'll potentially mess up the process
		if ( ! empty( $_POST['import_id'] ) ) {
			return;
		}

		// run various checks to make sure we aren't doing anything weird
		if ( mojoreferralCreator_Helper::meta_save_check( $post_id ) ) {
			return;
		}

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post_id ), mojoreferralCreator_Helper::get_mojoreferral_status( 'save' ) ) ) {
			return;
		}

		// make sure we're working with an approved post type
		if ( ! in_array( get_post_type( $post_id ), mojoreferralCreator_Helper::get_mojoreferral_types() ) ) {
			return;
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = mojoreferralCreator_Helper::get_mojoreferral_api_data() ) {
			return;
		}

		// bail if user hasn't checked the box
		if ( false === $onsave = mojoreferralCreator_Helper::get_mojoreferral_option( 'sav' ) ) {
		   	return;
		}

		// check for a link and bail if one exists
		if ( false !== $exist = mojoreferralCreator_Helper::get_mojoreferral_meta( $post_id ) ) {
			return;
		}

		// get my post URL and title
		$url    = mojoreferralCreator_Helper::prepare_api_link( $post_id );
		$title  = get_the_title( $post_id );

		// and optional keyword
		$keywd  = ! empty( $_POST['mojoreferral-keyw'] ) ? mojoreferralCreator_Helper::prepare_api_keyword( $_POST['mojoreferral-keyw'] ) : '';

		// set my args for the API call
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );

		// make the API call
		$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'shorturl', $args );

		// bail if empty data or error received
		if ( empty( $build ) || false === $build['success'] ) {
			return;
		}

		// we have done our error checking and we are ready to go
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// get my short URL
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// update the post meta
			update_post_meta( $post_id, '_mojoreferral_url', $shorturl );
			update_post_meta( $post_id, '_mojoreferral_clicks', '0' );

			// do the action after saving
			do_action( 'mojoreferral_after_url_save', $post_id, $shorturl );
		}
	}

    public function mojo_referral_additional_profile_fields( $user ) {
    global $wpdb;
      $user_id = $user->ID ;
      
      $args_total = array(
          'meta_query' =>
          array(
                array(
                    'key' =>'referred_by',
                    'value' => $user_id
                )
            )
        );
       $user_referral_count_total = count(get_users( $args_total ));
       
       $args_cp = array(
          'meta_query' =>
          array(
              array(
                  'relation' => 'AND',
                  array(
                      'key' =>'referred_by',
                      'value' => $user_id
                      ),
                      array(
                          'key' => 'referred_via',
                          'value' =>  'cp'
                    )
                )
            )
        );
       $user_referral_count_cp = count(get_users( $args_cp ));
       
       $args_em = array(
          'meta_query' =>
          array(
              array(
                  'relation' => 'AND',
                  array(
                      'key' =>'referred_by',
                      'value' => $user_id
                ),
                array(
                    'key' => 'referred_via',
                    'value' =>  'em'
                )
            )
        )
     );
      $user_referral_count_em = count(get_users( $args_em ));
      
      $args_fb = array(
          'meta_query' =>
          array(
              array(
                  'relation' => 'AND',
                  array(
                      'key' =>'referred_by',
                      'value' => $user_id
                      ),
                      array(
                          'key' => 'referred_via',
                          'value' =>  'fb'
                          ) 
                    )
                )
            );
      $user_referral_count_fb = count(get_users( $args_fb ));
      
      $args_tw = array(
          'meta_query' =>
          array(
              array(
                  'relation' => 'AND',
                  array(
                      'key' =>'referred_by',
                      'value' => $user_id
                 ),
                 array(
                     'key' => 'referred_via',
                     'value' =>  'tw'
                )
            )
        )
      );
      $user_referral_count_tw = count(get_users( $args_tw ));

        ?>
        <h3>Mojoreferral Links</h3>
        
        <table class="form-table">
            <thead><tr><th cpolspan="3"><h3>General Settings</h3></th></tr></thead>
        <?php 
        $referred_by = get_user_meta($user_id, 'referred_by')[0];
   		   if( $referred_by != ''){
   		       $referred_person = get_userdata($referred_by)->display_name;
   		       $referred_via = get_user_meta($user_id, 'referred_via')[0];
   		       if($referred_via == 'fb'){
   		           $referred_via = 'Facebook';
   		       }else if($referred_via == 'tw'){
   		           $referred_via = 'Twitter';
   		       }elseif($referred_via == 'em'){
   		           $referred_via = 'Email';
   		       }else{
   		           $referred_via = 'Shared Link';
   		       }
   	     ?>
        <tr>
            <th colspan="3" style="text-align:center;"><h2><?php if(is_admin()){
            echo 'This user is ';} else{ echo 'You are';} ?> referred By <?php echo $referred_person; ?> via <?php echo  $referred_via; ?>.</h2></th>
        </tr>
        <?php } ?>
        <tr>
            <th>Referral type</th>
            <th>Link</th>
            <th>Count</th>
        </tr>
       	 <tr>
       		 <th><label >Facebook:-</label></th>
       		 <td><?php 
       		    echo get_user_meta($user_id, 'mojoreferral_url_fb')[0];
   		     ?></td>
       		 <td>
       			<?php echo $user_referral_count_fb; ?> 
       		 </td>
       	 </tr>
       	 <tr>
       		 <th><label >Twitter:-</label></th>
       		 <td><?php 
       		    echo get_user_meta($user_id, 'mojoreferral_url_tw')[0];
   		     ?></td>
       		 <td>
       			 <?php echo $user_referral_count_tw; ?>
       		 </td>
       	 </tr>
       	 <tr>
       		 <th><label >Copy and share:-</label></th>
       		 <td><?php 
       		    echo get_user_meta($user_id, 'mojoreferral_url_cp')[0];
   		     ?></td>
       		 <td>
       			 <?php echo $user_referral_count_cp; ?>
       		 </td>
       	 </tr>
       	 <tr>
       		 <th><label >Emails:-</label></th>
       		 <td><?php 
       		    echo get_user_meta($user_id, 'mojoreferral_url_em')[0];
   		     ?></td>
       		 <td>
       		    <?php echo $user_referral_count_em; ?>
       		 </td>
       	 </tr>
       	 <tr>
       		 <th></th>
       		 <th style="text-align:right;"><label>Total</label></th>
       		 <td style="font-weight:bold;">
       		   <?php echo $user_referral_count_total; ?>
       		 </td>
       	 </tr>
        </table>
        <?php
    }



    /*** Sort and Filter Users ***/
    public function mojoreferral_filter_by_referral_by($which)
    {
     // template for filtering
     global $wpdb;
     $st = '<select name="mojoreferral_filter_referred_by_%s" style="float:none;margin-left:10px;">';
    $options = '';
     // generate options
    /* $options = '<option value="job_seeker">Seeker</option>
        <option value="job_lister">Hirer</option>';*/
       // echo count(get_users());
       
      $mojoreferral_by = '';
      $top_by = $_GET['mojoreferral_filter_referred_by_top'];
      $bottom_by = $_GET['mojoreferral_filter_referred_by_bottom'];
      if (!empty($top_by) OR !empty($bottom_by))
      {
       $mojoreferral_by = !empty($top_by) ? $top_by : $bottom_by;
      }
     if( $mojoreferral_by == '0'){
         $st .= '<option value="0" selected>%s</option>%s</select>';
     }else{
         $st .= '<option value="0">%s</option>%s</select>';
     }
      $users =  $wpdb->get_results("SELECT * from $wpdb->users ");
       
        foreach( $users as $user){
           $user_name =  $user->display_name;
           $user_id =  $user->ID;
           if($user_id == $mojoreferral_by ){
            $options .= '<option value="'.$user_id.'" selected>'.$user_name.'</option> ';
               
           } else{
            $options .= '<option value="'.$user_id.'" >'.$user_name.'</option> '; }
        }
     
     // combine template and options
     $select = sprintf( $st, $which, __( 'Referred by' ), $options );
    
     // output <select> and submit button
     echo $select;
     submit_button(__( 'Filter' ), null, $which, false);
    }
  
    /*** Sort and Filter Users ***/
    public function mojoreferral_filter_by_referral_via($which)
    {
     // template for filtering
     $st = '<select name="mojoreferral_filter_referred_via_%s" style="float:none;margin-left:10px;">';
     
    
    $options = '';
     // generate options
     $referral_type = array('cp'=>'Copied link','em'=>'Email','fb'=>'Facebook','tw'=>'Twitter');
      $mojoreferral_via = '';
      $top_via = $_GET['mojoreferral_filter_referred_via_top'];
      $bottom_via = $_GET['mojoreferral_filter_referred_via_bottom'];
      if (!empty($top_via) OR !empty($bottom_via))
      {
       $mojoreferral_via = !empty($top_via) ? $top_via : $bottom_via;
      }
      //<option value="">%s</option>%s</select>
     if( $mojoreferral_via == '0'){
         $st .= '<option value="0" selected>%s</option>%s</select>';
     }else{
         $st .= '<option value="0">%s</option>%s</select>';
     }
     foreach($referral_type as $key => $value){
         if($mojoreferral_via == $key){
            $options .= '<option value="'.$key.'" selected>'.$value.'</option>';
         } else {
            $options .= '<option value="'.$key.'">'.$value.'</option>';
         }
     }
     $select = sprintf( $st, $which, __( 'Referred via' ), $options );
    
     // output <select> and submit button
     echo $select;
     submit_button(__( 'Filter' ), null, $which, false);
    }

    public  function mojoreferral_filter_users_by_user($query)
    {
         global $pagenow;
         if (is_admin() && 'users.php' == $pagenow) {
              // figure out which button was clicked. The $which in filter_by_job_role()
                $top_by = $_GET['mojoreferral_filter_referred_by_top'];
                $bottom_by = $_GET['mojoreferral_filter_referred_by_bottom'];
                
                $meta_query_arr['relation'] = 'AND';
                if (!empty($top_by) OR !empty($bottom_by))
                {
                    $section_by = !empty($top_by) ? $top_by : $bottom_by;
                    $meta_query_arr[] = array(
                        'key' =>'referred_by',
                        'value' => $section_by
                    );
                }     
                // change the meta query based on which option was chosen
                $top_via = $_GET['mojoreferral_filter_referred_via_top'];
                $bottom_via = $_GET['mojoreferral_filter_referred_via_bottom'];
                if (!empty($top_via) OR !empty($bottom_via))
                {
                    $section_via = !empty($top_via) ? $top_via : $bottom_via;
                    $meta_query_arr[] = array(
                        'key' =>'referred_via',
                        'value' => $section_via
                    );
                }
               
               $query->set('meta_query', $meta_query_arr);
          
         }
    }
    
// end class
}
// end exists check
}

// Instantiate our class
new mojoreferralCreator_Admin();

