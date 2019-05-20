(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
jQuery(document).ready(function(){
    
    jQuery('#copyButton').on("click", function() {
        copyToClipboard(document.getElementById("copyButton"));
        $(".copiedSuccessMsg").css('display','block'); 
        setTimeout(function() { $(".copiedSuccessMsg").hide(); }, 2000);
    });
    
    
    $( document ).on( 'click', '#generateButton', function (e) {
    	// stop the hash
		e.preventDefault();
    	// get my nonce
		var yrlNonce    = $( this ).data('nonce');
    	// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}
    	// get my post ID
		var yrlUserID   = $( this ).attr( 'data-user-id' );
       // alert(yrlPostID);
		// bail without post ID
		if ( yrlUserID === '' ) {
			return;
		}
	// set my row and box as a variable for later
		// set my data array
		var data = {
			action:  'inline_mojoreferral_front',
			user_id: yrlUserID,
			nonce:   yrlNonce
		};
		// my ajax return check
		jQuery.post( frontend_ajax_object.ajaxurl, data, function( response ) {
			// hide the row actions
			
			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				return false;
			}
			// add in the new click box if it comes back
			if( obj.success === true && obj.rowactn !== null ) {
				/*yrlClickBox.replaceWith( obj.rowactn );*/
				console.log(obj.rowactn);
			
				location.reload(true);
			
			} else {
			    
			    	if(obj.errcode == 'NO_API_DATA'){
				    alert('Please complete plugin settings first.');
				}
			}
		});
	});
    
    jQuery(".mojo-social-share.facebook").on("click", function() {
       var url = "https://www.facebook.com/sharer.php?u="+jQuery(this).data('mojoreferral-url');
        socialWindow(url);
    }); 
    
    jQuery(".mojo-social-share.twitter").on("click", function() {
       var url = "https://twitter.com/intent/tweet?url="+jQuery(this).data('mojoreferral-url')+ "&text="+jQuery(this).data('mojoreferral-text');
        socialWindow(url);
    });   
    
    
    jQuery('.btnRegister').on("click",function(){
    var return_flag = 0;
    var mojo_referral_UserID = jQuery('input[name="mojo-referral-userid"]').val();
     var required_error = '<span class="mojo-referral-error">This field is required*</span>';
    var validate_email_error = '<span class="mojo-referral-error">Please enter a valid email address.</span>';
    var validate_phone_error = '<span class="mojo-referral-error">Please enter a valid phone number</span>';
    
    var pattern_email = /^\b[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b$/i
    var pattern_phone = /\(?([0-9]{3})\)?([ .-]?)([0-9]{3})\2([0-9]{4})/
    jQuery('.mojo-referral-error').remove();
    
    $( ".mojo-referral-email" ).each(function( index ) {
      var mojo_referral_email = jQuery(this).val();
      if(mojo_referral_email == ''){
          jQuery(this).after(required_error);
          return_flag = 1;
        }else if(!pattern_email.test(mojo_referral_email))
        {
           jQuery(this).after(validate_email_error);
           return_flag = 1;
        } 
    });
    $( ".mojo-referral-phone" ).each(function( index ) {
      var mojo_referral_phone = jQuery(this).val();
      if(mojo_referral_phone == ''){
          jQuery(this).after(required_error);
          return_flag = 1;
        }else if(!pattern_phone.test(mojo_referral_phone))
        {
           jQuery(this).after(validate_phone_error);
           return_flag = 1;
        } 
    });
  
    if(return_flag == 1) {
        return false;
    } else{
        console.log(jQuery(this).data('mojo-referral-url'));
        var emails = [];
        $( ".mojo-referral-email" ).each(function( index ) {
            emails.push($(this).val()); 
        });
        
        var phones = [];
        $( ".mojo-referral-phone" ).each(function( index ) {
            phones.push($(this).val()); 
        });

        var data = {
			action:  'inline_mojoreferral_share_via_email_front',
			user_id: mojo_referral_UserID,
			mojo_referral_url:  jQuery(this).data('mojo-referral-url'),
			emails:   emails,
			phones:   phones
		};
		//console.log(data.users);
		// my ajax return check
		jQuery.post( frontend_ajax_object.ajaxurl, data, function( response ) {
	    	//$( ".mojo-referral-email .mojo-referral-phone" ).remove();
            jQuery('.register-row').remove();
            jQuery('#mojo-referral-users-form').append('<div class="register-row"><div class="register-col-5"><div class="register-form-inner"><input type="email" class="register-form-control mojo-referral-email" name="mojo-referral-email[]" placeholder="Email *" value="" /></div></div><div class="register-col-5"><div class="register-form-inner"><input type="text" minlength="10" maxlength="10" name="mojo-referral-phone[]" class="register-form-control mojo-referral-phone" placeholder="Phone *" value="" /></div></div><div class="register-col-2"><i class="fa fa-plus" onclick="addRow(this)"></i></div></div>');
            $(".mailSuccessMsg").css('display','block'); 
            setTimeout(function() { $(".mailSuccessMsg").hide(); }, 2000);
            // hide the row actions
			//yrlClickRow.removeClass( 'visible' );
		/*	var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				return false;
			}
			// add in the new click box if it comes back
			if( obj.success === true ) {
				
			console.log(obj.message);
				jQuery('input[name="mojo-referral-phone"]').val('');
				jQuery('input[name="mojo-referral-email"]').val('');
				
				$(".mailSuccessMsg").css('display','block'); 
                setTimeout(function() { $(".mailSuccessMsg").hide(); }, 2000);
				
				
			}*/
		});
    }
    });
    
});

//********************************************************************************************************************************
// create mojoreferral inline
//********************************************************************************************************************************

function copyToClipboard(elem) {
    // create hidden text element, if it doesn't already exist
    var targetId = "_hiddenCopyText_";
    var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
    var origSelectionStart, origSelectionEnd;
    if (isInput) {
        // can just use the original source element for the selection and copy
        target = elem;
        origSelectionStart = elem.selectionStart;
        origSelectionEnd = elem.selectionEnd;
    } else {
        // must use a temporary form element for the selection and copy
        target = document.getElementById(targetId);
        if (!target) {
            var target = document.createElement("textarea");
            target.style.position = "absolute";
            target.style.left = "-9999px";
            target.style.top = "0";
            target.id = targetId;
            document.body.appendChild(target);
        }
        target.textContent = elem.textContent;
    }
    // select the content
    var currentFocus = document.activeElement;
    target.focus();
    target.setSelectionRange(0, target.value.length);
    
    // copy the selection
    var succeed;
    try {
    	  succeed = document.execCommand("copy");
    } catch(e) {
        succeed = false;
    }
    // restore original focus
    if (currentFocus && typeof currentFocus.focus === "function") {
        currentFocus.focus();
    }
    
    if (isInput) {
        // restore prior selection
        elem.setSelectionRange(origSelectionStart, origSelectionEnd);
    } else {
        // clear temporary content
        target.textContent = "";
    }
    return succeed;
}


function socialWindow(url) {
    var left = (screen.width - 570) / 2;
    var top = (screen.height - 570) / 2;
    var params = "menubar=no,toolbar=no,status=no,width=570,height=570,top=" + top + ",left=" + left;
    window.open(url,"NewWindow",params);
}

})( jQuery );

function addRow(e){
    jQuery(e).attr('onclick','removeRow(this)');
    jQuery(e).addClass('fa-minus');
    jQuery(e).removeClass('fa-plus');
    var  current_row = jQuery(e).closest('.register-row');
    current_row.after('<div class="register-row"><div class="register-col-5"><div class="register-form-inner"><input type="email" class="register-form-control mojo-referral-email" name="mojo-referral-email[]" placeholder="Email *" value="" /></div></div><div class="register-col-5"><div class="register-form-inner"><input type="text" minlength="10" maxlength="10" name="mojo-referral-phone[]" class="register-form-control mojo-referral-phone" placeholder="Phone *" value="" /></div></div><div class="register-col-2"><i class="fa fa-plus" onclick="addRow(this)"></i></div></div>');
        
    
}	
function removeRow(e){
    
    jQuery(e).closest('.register-row').remove();
 
}
    
    
function switchTab(evt, tabName) {
  // Declare all variables
  var i, tabcontent, tablinks;

  // Get all elements with class="tabcontent" and hide them
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }

  // Get all elements with class="tablinks" and remove the class "active"
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }

  // Show the current tab, and add an "active" class to the button that opened the tab
  document.getElementById(tabName).style.display = "block";
  evt.currentTarget.className += " active";
}    
    
    
    
