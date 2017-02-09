var ezt_replace_last_instance = function (srch, repl, str) {
	n = str.lastIndexOf(srch);
	if (n >= 0 && n + srch.length >= str.length) {
		str = str.substring(0, n) + repl;
	}
	return str;
}

var ezt_submit_ajax_form = function (f) {
	var msg = jQuery('<p><span class="fa fa-refresh fa-spin"></span><em> One moment..</em></p>');	
	var f = jQuery(f).after(msg).detach();
	var enc = f.attr('enctype');
	var act = f.attr('action');
	var meth = f.attr('method');
	var submit_with_ajax = ( f.data('ajax-submit') == 1 );
	var ok_to_send_site_details = ( f.find('input[name="include_wp_info"]:checked').length > 0 );
	
	if ( !ok_to_send_site_details ) {
		f.find('.gp_galahad_site_details').remove();
	}
	
	var wrap = f.wrap('<form></form>').parent();
	wrap.attr('enctype', f.attr('enctype'));
	wrap.attr('action', f.attr('action'));
	wrap.attr('method', f.attr('method'));
	wrap.find('#submit').attr('id', '#notsubmit');

	if ( !submit_with_ajax ) {
		jQuery('body').append(wrap);
		setTimeout(function () {
			wrap.submit();
		}, 500);	
		return false;
	}
	
	data = wrap.serialize();
	
	$.ajax(act,
	{
		crossDomain: true,
		method: 'post',
		data: data,
		dataType: "json",
		success: function (ret) {
			var r = jQuery(ret)[0];
			msg.html('<p class="ajax_response_message">' + r.msg + '</p>');
		}
	});		
};

var ezt_submit_ajax_contact_form = function (f) {
	$ = jQuery;
	
	// initialize the form
	var ajax_url = 'https://goldplugins.com/tickets/galahad/catch.php';
	//f.attr('action', ajax_url);
	
	// show 'one moment' emssage
	var msg = '<p><span class="fa fa-refresh fa-spin"></span><em> One moment..</em></p>';
	$('.gp_ajax_contact_form_message').html(msg);
	
	var f = jQuery(f).after(msg).detach();
	var enc = f.attr('enctype');
	var act = f.attr('action');
	var meth = f.attr('method');

	jQuery('body').append(f);	
	var wrap = f.wrap('<form></form>').parent();
	wrap.attr('enctype', f.attr('enctype'));
	wrap.attr('action', f.attr('action'));
	wrap.attr('method', f.attr('method'));	
	wrap.find('#submit').attr('id', '#notsubmit');

	setTimeout(function () {
		wrap.submit();
	}, 100);
	
	
	
	
	
	data = f.serialize();
	
	$.ajax(
		ajax_url,
		'post',
		data,
		function (ret) {
			alert(ret);
		}
	);
	return false; // prevent form from submitting normally
};

var ezt_setup_contact_forms = function() {
	$ = jQuery;
	var forms = $('.gp_support_form_wrapper div[data-gp-ajax-contact-form="1"]');
	if (forms.length > 0) {
		forms.each(function () {
			var f = this;
			var btns = $(this).find('.button[type="submit"]').on('click', 
				function () {
					ezt_submit_ajax_contact_form(f);
					return false;
				} 
			);
		});
	}
	jQuery('.gp_ajax_contact_form').on('submit', ezt_submit_contact_form);
};

var ezt_setup_ajax_forms = function() {
	$ = jQuery;
	var forms = $('div[data-gp-ajax-form="1"]');
	if (forms.length > 0) {
		forms.each(function () {
			var f = this;
			var btns = $(this).find('.button[type="submit"]').on('click', 
				function () {
					ezt_submit_ajax_form(f);
					return false;
				} 
			);
		});
	}
};
jQuery(function () {
	ezt_setup_ajax_forms();
	//ezt_setup_contact_forms();
});

function ezt_link_upgrade_labels()
{
	if (jQuery('.plugin_is_not_registered').length == 0) {
		return;
	}
	jQuery('.easy-t-radio-button').each(function (index) {
		var my_radio = jQuery(this).find('input[type=radio]');
		if (my_radio)
		{
			var disabled = (my_radio.attr('disabled') && my_radio.attr('disabled').toLowerCase() == 'disabled');
			if (disabled) {
				var my_em = jQuery(this).find('label em:first');
				var my_img = jQuery(this).find('label img');
				if (my_em.length > 0 || my_img.length > 0) {
					var my_id = my_radio.attr('id');
					var buy_url = 'https://goldplugins.com/our-plugins/easy-testimonials-details/upgrade-to-easy-testimonials-pro/?utm_campaign=upgrade_themes&utm_source=theme_selection&utm_banner=' + my_id;
					var link_template = '<a href="@buy_url" target="_blank"></a>';
					var link = link_template.replace(/@buy_url/g, buy_url);
					my_em.wrap(link);
					my_img.wrap(link);
				}				
			}
		}
	});
}

jQuery(document).ready(function() {
  ezt_theme_preview_swap();
});
function ezt_theme_preview_swap()
{
	jQuery('#testimonials_style').change(function(){
		var new_theme = jQuery(this).val();
		var pro_required = 0;
		
		if (new_theme.indexOf("-disabled") >= 0){
			new_theme = new_theme.replace("-disabled", "");
			pro_required = 1;
		}
		
		new_theme = new_theme.replace("-style","");
		
		jQuery('#easy_t_preview > div.easy_t_single_testimonial').removeClass().addClass('style-' + new_theme + ' easy_t_single_testimonial');
		
		if(pro_required){
			jQuery('#easy_t_preview .easy_testimonials_not_registered').show();
			jQuery('.submit input[type="submit"]').prop('disabled', true);
		} else {
			jQuery('#easy_t_preview .easy_testimonials_not_registered').hide();
			jQuery('.submit input[type="submit"]').prop('disabled', false);
		}
	});
}