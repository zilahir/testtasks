<?php
// Easy Testimonials Welcome Page template

ob_start();
$learn_more_url = 'https://goldplugins.com/special-offers/upgrade-to-easy-testimonials-pro/?utm_source=easy_testimonials_free&utm_campaign=welcome_screen_upgrade&utm_content=col_1_learn_more';
$pro_registration_url = menu_page_url('easy-testimonials-settings', false) . '#tab-registration_options';
$settings_url = menu_page_url('easy-testimonials-settings', false);
$utm_str = '?utm_source=easy_testimonials_free&utm_campaign=welcome_screen_help_links';
?>

<p class="aloha_intro">Thank you for installing Easy Testimonials<?php echo ($is_pro ? " Pro" : ''); ?>! This screen will help you get up and running with the plugin.</p>

<div class="three_col">
	<div class="col">
		<?php if ($is_pro): ?>
			<h3>Easy Testimonials Pro: Active</h3>
			<p class="plugin_activated">Easy Testimonials Pro is licensed and active.</p>
			<a href="<?php echo $pro_registration_url; ?>">Registration Settings</a>
		<?php else: ?>
			<h3>Upgrade To Pro</h3>
			<p>Easy Testimonials Pro is the Professional, fully-functional version of Easy Testimonials, which features technical support and access to all features and themes.</p>
			<a class="button" href="<?php echo $learn_more_url; ?>">Click Here To Learn More</a>
			<br>
			<br>
			<p><strong>Already upgraded to Easy Testimonials Pro?</strong></p>
			<a href="<?php echo $pro_registration_url; ?>">Click here to enter your Easy Testimonials Pro API Key</a>			
		<?php endif; ?>
	</div>
	<div class="col">
		<h3>Getting Started</h3>
		<ul>
			<li><a href="https://goldplugins.com/documentation/easy-testimonials-documentation/easy-testimonials-installation-and-usage-instructions/<?php echo $utm_str; ?>">Getting Started With Easy Testimonials</a></li>
			<li><a href="https://goldplugins.com/documentation/easy-testimonials-documentation/easy-testimonials-installation-and-usage-instructions/<?php echo $utm_str; ?>#add_a_new_testimonial">How To Create Your First Testimonial</a></li>
			<li><a href="https://goldplugins.com/documentation/easy-testimonials-documentation/easy-testimonials-installation-and-usage-instructions/<?php echo $utm_str; ?>#testimonial_submission">Create a Submit Your Testimonial Form</a></li>
			<li><a href="https://goldplugins.com/documentation/easy-testimonials-documentation/faqs/<?php echo $utm_str; ?>">Frequently Asked Questions (FAQs)</a></li>
			<li><a href="https://goldplugins.com/contact/<?php echo $utm_str; ?>">Contact Technical Support</a></li>
		</ul>
	</div>
	<div class="col">
		<h3>Further Reading</h3>
		<ul>
			<li><a href="https://goldplugins.com/documentation/easy-testimonials-documentation/<?php echo $utm_str; ?>">Easy Testimonials Documentation</a></li>
			<li><a href="https://wordpress.org/support/plugin/easy-testimonials/<?php echo $utm_str; ?>">WordPress Support Forum</a></li>
			<li><a href="https://goldplugins.com/documentation/easy-testimonials-documentation/<?php echo $utm_str; ?>">Recent Changes</a></li>
			<li><a href="https://goldplugins.com/<?php echo $utm_str; ?>">Gold Plugins Website</a></li>
		</ul>
	</div>
</div>
<div class="continue_to_settings">
	<p><a href="<?php echo $settings_url; ?>">Continue to Basic Settings &raquo;</a></p>
</div>

<?php 
$content =  ob_get_contents();
ob_end_clean();
return $content;