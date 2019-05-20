<?php
/**
 * mojoreferral Link Creator - Front End Module
 *
 * Contains front end functions
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

if ( ! class_exists( 'mojoreferralCreator_Front' ) ) {

// Start up the engine
class mojoreferralCreator_Front
{

	/**
	 * This is our constructor
	 *
	 * @return mojoreferralCreator_Front
	 */
	public function __construct() {
	    add_action( 'wp_enqueue_scripts',        array( $this, 'scripts_styles'      ),  10      );
		add_action( 'wp_head',                      array( $this, 'shortlink_meta'      )           );
		add_action( 'mojoreferral_display',               array( $this, 'mojoreferral_display'      )           );
		add_shortcode('mojo_referral', array($this,'mojo_referral_function'));
		add_action('init', array($this,'mojo_referral_functionStartSession'));
		add_action('init', array($this,'mojo_referral_functionStartSession'));
		add_action( 'user_register', array($this,'mojo_referral_registration_save') );
		add_shortcode( 'mojoreferralcountlist', array( $this,'mojo_referral_count_list_func') );  
		add_shortcode( 'mojoreferreduserlist', array( $this, 'mojo_referred_user_list_func') );  
		add_action('init', array($this,'mojo_referrel_api_func'));
	}
	
	/**
	 * scripts and stylesheets
	 *
	 * @param  [type] $hook [description]
	 * @return [type]       [description]
	 */
	public function scripts_styles( $hook ) {
    	// set our JS and CSS prefixes
    	$css_sx = '.css';
		$js_sx  = '.js';
		wp_enqueue_style( 'mojoreferral-front', plugins_url( '/css/mojoreferral-front' . $css_sx, __FILE__ ), array(), YOURS_VER, 'all' );
		wp_enqueue_script( 'mojoreferral-front', plugins_url( '/js/mojoreferral-front' . $js_sx, __FILE__ ) , array( 'jquery' ), YOURS_VER, true );
		wp_localize_script( 'mojoreferral-front', 'frontend_ajax_object',
            array( 
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
            )
        );
	}

	/**
	 * add shortlink into head if present
	 *
	 * @return [type] [description]
	 */
	public function shortlink_meta() {

		// no shortlinks exist on non-singular items, so bail
		if ( ! is_singular() ) {
			return;
		}

		// check options to see if it's enabled
		if ( false === mojoreferralCreator_Helper::get_mojoreferral_option( 'sht' ) ) {
			return;
		}

		// call the global post object
		global $post;

		// bail without a post object
		if ( empty( $post ) || ! is_object( $post ) || empty( $post->ID ) ) {
			return;
		}

		// check existing postmeta for mojoreferral link
		if ( false === $link = mojoreferralCreator_Helper::get_mojoreferral_meta( $post->ID ) ) {
			return;
		}

		// got a mojoreferral? well then add it
		echo '<link href="' . esc_url( $link ) . '" rel="shortlink">' . "\n";
	}

	/**
	 * our pre-built template tag
	 *
	 * @return [type] [description]
	 */
	public function mojoreferral_display( $post_id = 0, $echo = false ) {

		// no display exist on non-singular items, so bail
		if ( ! is_singular() ) {
			return;
		}

		// fetch the post ID if not provided
		if ( empty( $post_id ) ) {

			// call the object
			global $post;

			// bail if missing
			if ( empty( $post ) || ! is_object( $post ) || empty( $post->ID ) ) {
				return;
			}

			// set my post ID
			$post_id	= absint( $post->ID );
		}

		// check for the link
		if ( false === $link = mojoreferralCreator_Helper::get_mojoreferral_meta( $post_id ) ) {
			return;
		}

		// set an empty
		$show   = '';

		// build the markup
		$show  .= '<p class="mojoreferral-display">' . __( 'Shortlink:', 'wpmojoreferral' );
			$show  .= '<input id="mojoreferral-link-' . absint( $post_id ) . '" class="mojoreferral-link" size="28" title="' . __( 'click to highlight', 'wpmojoreferral' ) . '" type="url" name="mojoreferral-link-' . absint( $post_id ) . '" value="'. esc_url( $link ) .'" readonly="readonly" tabindex="501" onclick="this.focus();this.select()" />';
		$show  .= '</p>';

		// echo the box if requested
		if ( ! empty( $echo ) ) {
			echo apply_filters( 'mojoreferral_template_tag', $show, $post_id );
		}

		// return the box
		return apply_filters( 'mojoreferral_template_tag', $show, $post_id );
	}
	
	public function mojo_referral_function(){
	    $nonce  = wp_create_nonce( 'mojoreferral_inline_create_front' );
	    $meta_key = 'mojoreferral_created';
		$user_id = get_current_user_id();
    	$url_exits =  get_user_meta($user_id, $meta_key)[0];
        $data_mojoreferral_option   = mojoreferralCreator_Helper::get_mojoreferral_option();
        $mail_sent_msg = !empty($data_mojoreferral_option['mail_sent_msg']) ? $data_mojoreferral_option['mail_sent_msg'] : 'Invitation Sent.';
        $invite_button_text = !empty($data_mojoreferral_option['invite_button_text']) ? $data_mojoreferral_option['invite_button_text'] : 'Invite';
        $text_with_tw_link = !empty($data_mojoreferral_option['text_with_tw_link']) ? $data_mojoreferral_option['text_with_tw_link'] : 'Lets check this amazing website.';
        $color_1 = !empty($data_mojoreferral_option['color_1']) ? $data_mojoreferral_option['color_1'] : '#3931af';
        $color_2 = !empty($data_mojoreferral_option['color_2']) ? $data_mojoreferral_option['color_2'] : '#00c6ff';
        
        //print_r($data );
	    ?>
	    
	    <div class="register mojo-referral-container" style="background: -webkit-linear-gradient(left, <?php echo $color_1;?>, <?php echo $color_2;?>);">
	        <div class="row">
                <div class="col-md-3 register-left">
                    <img src="https://image.ibb.co/n7oTvU/logo_white.png" alt=""/>
                    <h3>Welcome</h3>
                    <?php $data   = mojoreferralCreator_Helper::get_mojoreferral_option(); ?>
                    <p>Share your unique referral links!</p>
                </div>
                <div class="col-md-9 register-right">
                     <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <h3 class="register-heading">The more friends who sign up using your link, the faster you get access.</h3>
                            <div class="row register-form">
                                 <?php if ($url_exits != 1 ){ ?>
                                <div class="mojo-referral-overlay">
                                    <div class="register-col-12" style="text-align:center;">
                                        <?php if ( is_user_logged_in() ) {
                                               ?>
                                               <input id="generateButton" class="mobile-button" value="Generate" data-user-id="<?php echo get_current_user_id(); ?>" data-nonce="<?php echo esc_attr( $nonce );?>" readonly>
                                               <?php
                                            } else { ?>
                                              <a id="mojoLoginButton" class="mobile-button" href="<?php if( $data['mojo_login_url'] != ''){ echo $data['mojo_login_url']; } else {  echo wp_login_url(); }?>">Login to get your Referral link</a>
                                              
                                            <?php }?>
                                               
                                         
                                         
                                        <!--<input type="button" class="register-form-control btnGenerateLink"  value="Generate your own short link."  data-mojo-referral-url="<?php //echo get_user_meta($user_id, 'mojoreferral_url_em')[0]; ?>"/>-->
                                    </div>
                                </div>
                                <div class="mojo-referral-overlay-blur"><?php } ?>
                                    <div class="register-col-12">
                                        <h4 class="mailSuccessMsg" style="color: rgb(66, 216, 152); display: none; text-align: center !important;"><?php echo $mail_sent_msg; ?></h4>
                                                                                
                                    </div>
                                    <form id="mojo-referral-users-form">
                                        <div class="register-row">
                                            <div class="register-col-5">
                                                <div class="register-form-inner">
                                                    <input type="email" class="register-form-control mojo-referral-email" name="mojo-referral-email[]" placeholder="Email *" value="" />
                                                </div>
                                            </div>
                                            <div class="register-col-5">
                                                <div class="register-form-inner">
                                                    <input type="text" minlength="10" maxlength="10" name="mojo-referral-phone[]" class="register-form-control mojo-referral-phone" placeholder="Phone *" value="" />
                                                </div>
                                            </div>
                                            <div class="register-col-2"><i class="fa fa-plus" onclick="addRow(this);"></i></div>
                                        </div>
                                        <input type="hidden" class="register-form-control" name="mojo-referral-userid" value="<?php echo get_current_user_id(); ?>" />
                                    </form>
                                     <?php if ($url_exits == 1 ){ ?>
                                    <div class="register-col-12" style="text-align:center;">
                                        <input type="button" class="register-form-control btnRegister"  value="<?php echo $invite_button_text; ?>"  data-mojo-referral-url="<?php echo get_user_meta($user_id, 'mojoreferral_url_em')[0]; ?>" style="background: <?php echo $color_1;?>;"/>
                                    </div>
                                    <?php }  ?>
                                    <div class="register-col-12" style="text-align:center;">
                                       COPY & SHARE YOUR LINK
                                    </div>
                                    <div class="register-col-12">
                                        <span class="copiedSuccessMsg" style="text-align: center !important;color: #42d898;display: none;">Link copied</span>
                                        <?php if ($url_exits == 1 ){ ?>
                                            <input id="copyButton" class="mobile-button" value="<?php echo get_user_meta($user_id, 'mojoreferral_url_cp')[0]; ?>" style="background: -webkit-linear-gradient(left, <?php echo $color_1;?>, <?php echo $color_2;?>);" readonly>
                                        <?php }  ?>
                                    </div>
                                    <div class="register-col-12" style="text-align:center;">
                                       OR SPREAD THE WORD ON SOCIAL
                                    </div>
                                    <div class="register-col-12">
                                        <div class="social-container">
                                            <a href="javascript:void(0);" class="mojo-social-share twitter"  onclick="" data-mojoreferral-url="<?php echo get_user_meta($user_id, 'mojoreferral_url_tw')[0]; ?>" data-mojoreferral-text="<?php echo $text_with_tw_link; ?>"><i class="fa fa-twitter"></i>Twitter</a>
                                            <a href="javascript:void(0);" class="mojo-social-share facebook" onclick="" data-mojoreferral-url="<?php echo get_user_meta($user_id, 'mojoreferral_url_fb')[0]; ?>"><i class="fa fa-facebook"></i>Facebook</a>
                                        </div>
                                    </div>
                                <?php if ($url_exits != 1 ){ ?>
                                </div> <?php } ?>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
	    </div>
	    <?php
	}
	
    public function mojo_referral_functionStartSession() {
        if(!session_id()) {
            session_start();
            if(isset($_GET['ref']) && $_GET['ref'] != '' && isset($_GET['user']) &&  $_GET['user'] != '' ){
    	        $_SESSION['referred_by'] =  $_GET['user'];
                $_SESSION['referred_via'] =  $_GET['ref'];
              ?>
    	        <script>location.replace("<?php echo site_url();?>");</script>
    	        <?php
    	    }
        }
    }
    
    function mojo_referral_registration_save( $user_id ) {

    if ( isset( $_SESSION['referred_by'] ) ){
            update_user_meta($user_id, 'referred_by', $_SESSION['referred_by']);
            update_user_meta($user_id, 'referred_via', $_SESSION['referred_via']);
            $user_info = get_userdata( $user_id ); 
            
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
			
			
			$url_fb  =  urlencode(site_url().'?ref=fb&user='.$user_id);
			$title = 'Facebook url for '.$user_info->user_login. '('.$user_id.')';
    		// set my args for the API call
    		$args   = array( 'url' => esc_url( $url_fb ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );
			$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'shorturl', $args );
			$mojoreferral_fb = $build;
			$meta_key_mojoreferral_url_fb = 'mojoreferral_url_fb';
			$meta_value_mojoreferral_url_fb = $mojoreferral_fb['data']['shorturl'];
			update_user_meta( $user_id, $meta_key_mojoreferral_url_fb, $meta_value_mojoreferral_url_fb );
			
			
			$url_tw  =  urlencode(site_url().'?ref=tw&user='.$user_id);
			$title = 'Twitter url for '.$user_info->user_login. '('.$user_id.')';
    		// set my args for the API call
    		$args   = array( 'url' => esc_url( $url_tw ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );
			$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'shorturl', $args );
			$mojoreferral_tw = $build;
			$meta_key_mojoreferral_url_tw = 'mojoreferral_url_tw';
			$meta_value_mojoreferral_url_tw = $mojoreferral_fb['data']['shorturl'];
			update_user_meta( $user_id, $meta_key_mojoreferral_url_tw, $meta_value_mojoreferral_url_tw );
			
			
			$url_em  =  urlencode(site_url().'?ref=em&user='.$user_id);
			$title = 'Email url for '.$user_info->user_login. '('.$user_id.')';
    		// set my args for the API call
    		$args   = array( 'url' => esc_url( $url_em ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );
			$build  = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'shorturl', $args );
			$mojoreferral_em = $build;
			$meta_key_mojoreferral_url_em = 'mojoreferral_url_em';
			$meta_value_mojoreferral_url_em = $mojoreferral_fb['data']['shorturl'];
			update_user_meta( $user_id, $meta_key_mojoreferral_url_em, $meta_value_mojoreferral_url_em );
        }
    }
    


    
    // to show count list on frontend
    public function mojo_referral_count_list_func(){
      global $wpdb;
      if ( is_user_logged_in() ) {
          $user_id = get_current_user_id();
          
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
            
        /** click count **/ 
        //facebook
        $facebook_url = get_user_meta($user_id, 'mojoreferral_url_fb')[0];
        $facebook_url = substr($facebook_url, strrpos($facebook_url, '/') + 1);
        $args['shorturl'] = $facebook_url;
        // $facebook_url_clicks = $this->mojo_referrel_api_func('url-stats', $args);
        $facebook_url_clicks = mojoreferralCreator_Helper::run_mojoreferral_api_call( 'url-stats', $args);
        $facebook_url_clicks = $facebook_url_clicks['data']['link']['clicks'];

        //twitter
        $twitter_url = get_user_meta($user_id, 'mojoreferral_url_tw')[0];
        $twitter_url = substr($twitter_url, strrpos($twitter_url, '/') + 1);
        $args['shorturl'] = $twitter_url;
        $twitter_url_clicks =  mojoreferralCreator_Helper::run_mojoreferral_api_call('url-stats', $args);
        $twitter_url_clicks =  $twitter_url_clicks['data']['link']['clicks'];
        
        //email
        $email_url = get_user_meta($user_id, 'mojoreferral_url_em')[0];
        $email_url = substr($email_url, strrpos($email_url, '/') + 1);
        $args['shorturl'] = $email_url;
        $email_url_clicks =  mojoreferralCreator_Helper::run_mojoreferral_api_call('url-stats', $args);
        $email_url_clicks = $email_url_clicks['data']['link']['clicks'];
        
        //copy 
        $copy_url = get_user_meta($user_id, 'mojoreferral_url_cp')[0];
        $copy_url = substr($copy_url, strrpos($copy_url, '/') + 1);
        $args['shorturl'] = $copy_url;
        $copy_url_clicks =  mojoreferralCreator_Helper::run_mojoreferral_api_call('url-stats', $args);
        $copy_url_clicks = $copy_url_clicks['data']['link']['clicks'];        
        
        ?>
          
            <table class="form-table">
                <thead><tr><th colspan="4"><h3>Referral Counts</h3></th></tr></thead>
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
                <th style="padding:5px;">Referrals</th>
                <th style="padding:5px;">Clicks</th>
            </tr>
           	 <tr>
           		 <th><label >Facebook:-</label></th>
           		 <td><?php echo get_user_meta($user_id, 'mojoreferral_url_fb')[0]; ?></td>
           		 <td style="text-align:center;">
           			<?php echo $user_referral_count_fb; ?> 
           		 </td>
           		 <td style="text-align:center;"><?php echo $facebook_url_clicks; ?></td>
           	 </tr>
           	 <tr>
           		 <th><label >Twitter:-</label></th>
           		 <td><?php echo get_user_meta($user_id, 'mojoreferral_url_tw')[0]; ?></td>
           		 <td style="text-align:center;">
           			 <?php echo $user_referral_count_tw; ?>
           		 </td>
           		 <td style="text-align:center;"><?php echo $twitter_url_clicks; ?></td>
           	 </tr>
           	 <tr>
           		 <th><label >Copy and share:-</label></th>
           		 <td><?php echo get_user_meta($user_id, 'mojoreferral_url_cp')[0]; ?></td>
           		 <td style="text-align:center;">
           			 <?php echo $user_referral_count_cp; ?>
           		 </td>
           		 <td style="text-align:center;"><?php echo $copy_url_clicks; ?></td>
           		 
           	 </tr>
           	 <tr>
           		 <th><label >Emails:-</label></th>
           		 <td><?php echo get_user_meta($user_id, 'mojoreferral_url_em')[0]; ?></td>
           		 <td style="text-align:center;">
           		    <?php echo $user_referral_count_em; ?>
           		 </td>
           		 <td style="text-align:center;"><?php echo $email_url_clicks; ?></td>
           	 </tr>
           	 <tr>
           		 <th></th>
           		 <th style="font-weight:bold;"><label>Total </label></th>
           		 <th><?php echo $user_referral_count_total; ?></th>
           		 <th><?php echo $email_url_clicks + $copy_url_clicks + $twitter_url_clicks + $facebook_url_clicks; ?></th>
           	 </tr>
            </table>
    <?php
        } else {
            echo "Please login to see your referral counts"; 
        }
    }
    
    // to show users list on frontend
    public function mojo_referred_user_list_func()
    { 
        global $wpdb;
        if ( is_user_logged_in() )
        {
            $limit = 1;
            $user_id = get_current_user_id();
            $args_total = array( 'number' => $limit,'paged' => 1, 'meta_query' => array( array( 'key' =>'referred_by', 'value' => $user_id ) ) );
            $user_referral_count_total = count(get_users( $args_total )); 
            $args_cp = array( 'meta_query' => array( array( 'relation' => 'AND', array( 'key' =>'referred_by', 'value' => $user_id ), array( 'key' => 'referred_via', 'value' => 'cp' ) ) ) );
            $referred_by_cp = get_users( $args_cp ); 
            $args_em = array( 'number' => $limit,'paged' => 1, 'meta_query' => array( array( 'relation' => 'AND', array( 'key' =>'referred_by', 'value' => $user_id ), array( 'key' => 'referred_via', 'value' => 'em' ) ) ) );
            $referred_by_email = get_users( $args_em ); 
            $args_fb = array(  'meta_query' => array( array( 'relation' => 'AND', array( 'key' =>'referred_by', 'value' => $user_id ), array( 'key' => 'referred_via', 'value' => 'fb' ) ) ) );
            $referred_by_fb_total = get_users( $args_fb );
            $args_fb['number'] = $limit;
            $args_fb['paged'] = 1;
            $referred_by_fb = get_users( $args_fb );
            
            $args_tw = array( 'number' => $limit,'paged' => 1, 'meta_query' => array( array( 'relation' => 'AND', array( 'key' =>'referred_by', 'value' => $user_id ), array( 'key' => 'referred_via', 'value' => 'tw' ) ) ) );
            $referred_by_tw = get_users( $args_tw ); ?>
            <div class="mojo-referral-container">
                <h3>Referred User List</h3>
                <!-- Tab links -->
                <div class="tab">
                    <button class="tablinks active" onclick="switchTab(event, 'facebook')">Facebook</button>
                    <button class="tablinks" onclick="switchTab(event, 'copy-and-share')">Copy and share</button>
                    <button class="tablinks" onclick="switchTab(event, 'email')">Email</button>
                    <button class="tablinks" onclick="switchTab(event, 'twitter')">Twitter</button>
                </div>
            
                <!-- Tab content -->
            
                <div id="facebook" class="tabcontent"  style="display:block;">
                    <h3 style="text-align:center;">Facebook</h3>
                    <?php if( count($referred_by_fb) == 0 ){ ?>
                        <div style="text-align:center;">There are no user joined by facebook invitation yet.</div>
                        <?php } else { ?>
                        <table class="form-table">
                            <tr>
                                <th style="text-align: left;">User Email</th>
                                <th style="text-align: left;">Joined Date</th>
                            </tr>
                            <?php
                            foreach( $referred_by_fb as $userData ){
                            ?>
                                <tr>
                                    <td>
                                        <?php echo $userData->user_email; ?>
                                    </td>
                                    <td>
                                        <?php echo $userData->user_registered; ?>
                                    </td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <td colspan="2">
                                    --<?php echo count($referred_by_fb_total)/$limit;?>--
                                        <?php for($i=1; $i<=count($referred_by_fb_total)/$limit; $i++){
                                            echo '<a class="mojo-user-lisi-paginate" href="#" onclick="return true;">'.$i.'</a>';
                                        } ?>
                                    </td>
                                </tr>
                        </table>
                    <?php } ?>
                </div>
            
                <div id="email" class="tabcontent">
                    <h3 style="text-align:center;">Email</h3>
                    <?php 
                    if( count($referred_by_email) == 0 ){ ?>
                        <div style="text-align:center;">There are no user joined by email invitation yet.</div>
                        <?php } else { ?>
                        <table class="form-table">
                            <tr>
                                <th style="text-align: left;">User Email</th>
                                <th style="text-align: left;">Joined Date</th>
                            </tr>
                            <?php 
                            foreach( $referred_by_email as $userData ){
                            ?>
                                <tr>
                                    <td>
                                        <?php echo $userData->user_email; ?>
                                    </td>
                                    <td>
                                        <?php echo $userData->user_registered; ?>
                                    </td>
                                </tr>
                                <?php } ?>
                        </table>
                    <?php } ?>
                </div>
            
                <div id="copy-and-share" class="tabcontent">
                    <h3 style="text-align:center;">Copy and Share</h3>
                    <?php 
                        if( count($referred_by_cp) == 0 ){
                        ?>
                        <div style="text-align:center;">There are no user joined by Copy and Share invitation yet.</div>
                        <?php
                        } else { ?>
                        <table class="form-table">
                            <tr>
                                <th style="text-align: left;">User Email</th>
                                <th style="text-align: left;">Joined Date</th>
                            </tr>
                            <?php foreach( $referred_by_cp as $userData ){
                            ?>
                                <tr>
                                    <td>
                                        <?php echo $userData->user_email; ?>
                                    </td>
                                    <td>
                                        <?php echo $userData->user_registered; ?>
                                    </td>
                                </tr>
                                <?php } ?>
                        </table>
                <?php } ?>
                </div>
                <div id="twitter" class="tabcontent">
                    <h3 style="text-align:center;">Twitter</h3>
                    <?php  if( count($referred_by_tw) == 0 ){ ?>
                        <div style="text-align:center;">There are no user joined by twitter invitation yet.</div>
                        <?php } else { ?>
                        <table class="form-table">
                            <tr>
                                <th style="text-align: left;">User Email</th>
                                <th style="text-align: left;">Joined Date</th>
                            </tr>
                            <?php
                            foreach( $referred_by_tw as $userData ){
                            ?>
        
                                <tr>
                                    <td>
                                        <?php echo $userData->user_email; ?>
                                    </td>
                                    <td>
                                        <?php echo $userData->user_registered; ?>
                                    </td>
                                </tr>
                                <?php } ?>
                        </table>
                            <?php } ?>
                </div>
        </div>
                <?php  
            
        } else {
                   // your code for logged out user 
                echo "Please login to see referred user by you.";
        }
        ?>
        <?php
    }
    
    public function mojo_referrel_api_func($action, $args = array()){

        $post_fields = [
                'signature'=> '7fd67a014f',
                'format'   => 'json',
                'username' => $username,
                'password' => $password
        ];        
              
        if( isset( $action ) && $action == 'url-stats'  ){
            $post_fields['action'] = $action;
            $post_fields['shorturl'] = $args['shorturl'];     
        } 
        else if( isset( $action ) && $action == 'db-stats' ){
            $post_fields['action'] = $action;
            $post_fields['db-stats'] = $args['db-stats'];
        } 
        else if( isset( $action ) && $action == 'stats' ){
            $post_fields['action'] = $action;
            $post_fields['stats'] = $args['stats'];
        }
        else if( isset( $action ) && $action == 'shorturl' ){
            $post_fields['action'] = $action;
            $post_fields['url'] = $args['url'];
        }
        else if( isset( $action ) && $action == 'expand' ){
            $post_fields['action'] = $action;
            $post_fields['shorturl'] = $args['shorturl'];
        }
        else if(isset( $action ) && $action == 'version'){
            $post_fields['action'] = $action;
        }
        
        $username = 'm0j0admin';
        $password = 'g41amZk(C48WxeAn3^';
        $api_url =  'https://staging.mojocreator.com/referral-plugin/referrals/yourls-api.php';

        // Init the CURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
        curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        
        // Fetch and return content
        $data = curl_exec($ch);
        curl_close($ch);
        
        return $data;        
        
    }
    
    
// end class
}

// end exists check
}

// Instantiate our class
new mojoreferralCreator_Front();

