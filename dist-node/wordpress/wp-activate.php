<?php
 define( 'WP_INSTALLING', true ); require __DIR__ . '/wp-load.php'; require __DIR__ . '/wp-blog-header.php'; if ( ! is_multisite() ) { wp_redirect( wp_registration_url() ); die(); } $valid_error_codes = array( 'already_active', 'blog_taken' ); list( $activate_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) ); $activate_cookie = 'wp-activate-' . COOKIEHASH; $key = ''; $result = null; if ( isset( $_GET['key'] ) && isset( $_POST['key'] ) && $_GET['key'] !== $_POST['key'] ) { wp_die( __( 'A key value mismatch has been detected. Please follow the link provided in your activation email.' ), __( 'An error occurred during the activation' ), 400 ); } elseif ( ! empty( $_GET['key'] ) ) { $key = $_GET['key']; } elseif ( ! empty( $_POST['key'] ) ) { $key = $_POST['key']; } if ( $key ) { $redirect_url = remove_query_arg( 'key' ); if ( remove_query_arg( false ) !== $redirect_url ) { setcookie( $activate_cookie, $key, 0, $activate_path, COOKIE_DOMAIN, is_ssl(), true ); wp_safe_redirect( $redirect_url ); exit; } else { $result = wpmu_activate_signup( $key ); } } if ( null === $result && isset( $_COOKIE[ $activate_cookie ] ) ) { $key = $_COOKIE[ $activate_cookie ]; $result = wpmu_activate_signup( $key ); setcookie( $activate_cookie, ' ', time() - YEAR_IN_SECONDS, $activate_path, COOKIE_DOMAIN, is_ssl(), true ); } if ( null === $result || ( is_wp_error( $result ) && 'invalid_key' === $result->get_error_code() ) ) { status_header( 404 ); } elseif ( is_wp_error( $result ) ) { $error_code = $result->get_error_code(); if ( ! in_array( $error_code, $valid_error_codes, true ) ) { status_header( 400 ); } } nocache_headers(); if ( is_object( $wp_object_cache ) ) { $wp_object_cache->cache_enabled = false; } $wp_query->is_404 = false; do_action( 'activate_header' ); function do_activate_header() { do_action( 'activate_wp_head' ); } add_action( 'wp_head', 'do_activate_header' ); function wpmu_activate_stylesheet() { ?>
	<style type="text/css">
		form { margin-top: 2em; }
		#submit, #key { width: 90%; font-size: 24px; }
		#language { margin-top: .5em; }
		.error { background: #f66; }
		span.h3 { padding: 0 8px; font-size: 1.3em; font-weight: 600; }
	</style>
	<?php
} add_action( 'wp_head', 'wpmu_activate_stylesheet' ); add_action( 'wp_head', 'wp_strict_cross_origin_referrer' ); add_filter( 'wp_robots', 'wp_robots_sensitive_page' ); get_header( 'wp-activate' ); $blog_details = get_blog_details(); ?>

<div id="signup-content" class="widecolumn">
	<div class="wp-activate-container">
	<?php if ( ! $key ) { ?>

		<h2><?php _e( 'Activation Key Required' ); ?></h2>
		<form name="activateform" id="activateform" method="post" action="<?php echo network_site_url( $blog_details->path . 'wp-activate.php' ); ?>">
			<p>
				<label for="key"><?php _e( 'Activation Key:' ); ?></label>
				<br /><input type="text" name="key" id="key" value="" size="50" />
			</p>
			<p class="submit">
				<input id="submit" type="submit" name="Submit" class="submit" value="<?php esc_attr_e( 'Activate' ); ?>" />
			</p>
		</form>

		<?php
 } else { if ( is_wp_error( $result ) && in_array( $result->get_error_code(), $valid_error_codes, true ) ) { $signup = $result->get_error_data(); ?>
			<h2><?php _e( 'Your account is now active!' ); ?></h2>
			<?php
 echo '<p class="lead-in">'; if ( '' === $signup->domain . $signup->path ) { printf( __( 'Your account has been activated. You may now <a href="%1$s">log in</a> to the site using your chosen username of &#8220;%2$s&#8221;. Please check your email inbox at %3$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%4$s">reset your password</a>.' ), network_site_url( $blog_details->path . 'wp-login.php', 'login' ), $signup->user_login, $signup->user_email, wp_lostpassword_url() ); } else { printf( __( 'Your site at %1$s is active. You may now log in to your site using your chosen username of &#8220;%2$s&#8221;. Please check your email inbox at %3$s for your password and login instructions. If you do not receive an email, please check your junk or spam folder. If you still do not receive an email within an hour, you can <a href="%4$s">reset your password</a>.' ), sprintf( '<a href="http://%1$s%2$s">%1$s%2$s</a>', $signup->domain, $blog_details->path ), $signup->user_login, $signup->user_email, wp_lostpassword_url() ); } echo '</p>'; } elseif ( null === $result || is_wp_error( $result ) ) { ?>
			<h2><?php _e( 'An error occurred during the activation' ); ?></h2>
			<?php if ( is_wp_error( $result ) ) : ?>
				<p><?php echo $result->get_error_message(); ?></p>
			<?php endif; ?>
			<?php
 } else { $url = isset( $result['blog_id'] ) ? get_home_url( (int) $result['blog_id'] ) : ''; $user = get_userdata( (int) $result['user_id'] ); ?>
			<h2><?php _e( 'Your account is now active!' ); ?></h2>

			<div id="signup-welcome">
			<p><span class="h3"><?php _e( 'Username:' ); ?></span> <?php echo $user->user_login; ?></p>
			<p><span class="h3"><?php _e( 'Password:' ); ?></span> <?php echo $result['password']; ?></p>
			</div>

			<?php
 if ( $url && network_home_url( '', 'http' ) !== $url ) : switch_to_blog( (int) $result['blog_id'] ); $login_url = wp_login_url(); restore_current_blog(); ?>
				<p class="view">
				<?php
 printf( __( 'Your account is now activated. <a href="%1$s">View your site</a> or <a href="%2$s">Log in</a>' ), $url, esc_url( $login_url ) ); ?>
				</p>
			<?php else : ?>
				<p class="view">
				<?php
 printf( __( 'Your account is now activated. <a href="%1$s">Log in</a> or go back to the <a href="%2$s">homepage</a>.' ), network_site_url( $blog_details->path . 'wp-login.php', 'login' ), network_home_url( $blog_details->path ) ); ?>
				</p>
				<?php
 endif; } } ?>
	</div>
</div>
<script type="text/javascript">
	var key_input = document.getElementById('key');
	key_input && key_input.focus();
</script>
<?php
get_footer( 'wp-activate' ); 