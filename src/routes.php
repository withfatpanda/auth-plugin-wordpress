<?php
$router->rewrite('/fp-auth/socialite/cancel/{token?}', function($token = null) use ($plugin) {
	if (is_null($token)) {
		$token = $plugin->request->input('token');
	}
	$plugin->forgetSavedSocialite($token);
	wp_redirect(home_url());
});

/**
 * Provide an endpoint for receiving and processing
 * the response from the OAuth service.
 */
$router->rewrite('/fp-auth/socialite/{driver}/callback', function($driver) use ($plugin) {
	// make sure we're configured to handle this driver
	if (!$plugin['config']["services.{$driver}"]) {
		// if we're not, tell the user they need to go back and login
		wp_die(sprintf(__('<a href="%s">%s</a> does not support this network for signing in.'), 
			site_url('wp-login.php'), get_bloginfo('sitename')));

	// if we are, then try to process the response from the service
	} else {
		$redirect_to = home_url();

		try {
			$provider = $plugin->socialite->driver($driver);
			// generate a socialite user
			$user = $provider->user();
			// if the WordPress user is logged in, add the socialite user to his/her profile
			if (is_user_logged_in()) {
				$plugin->addSocialiteUserToWordPressUser($driver, $user);
				wp_redirect($redirect_to);
					
			// otherwise try to login
			} else {
				$result = $plugin->loginSocialiteUser($driver, $user);
				if ($result instanceof \WP_User) {
					wp_redirect($redirect_to);
					
				}	else {
					wp_redirect(site_url('wp-login.php').'?'.http_build_query([
						'action' => 'register',
						'socialite' => $result
					]));
				}
			}

		} catch (\Exception $e) {
			wp_die($e->getTraceAsString());
			
			// TODO: log whatever went wrong
			// send the user back to login
			wp_redirect(site_url('wp-login.php'));
		}
	}
});

$router->rewrite('/fp-auth/socialite/{driver?}', function($driver = null) use ($plugin) {
	if (is_null($driver)) {
		$driver = $plugin->request->input('driver');
	}

	if (empty($driver)) {
		wp_redirect(site_url('wp-login.php'));

	} else if (!$plugin['config']["services.{$driver}"]) {
		wp_die(sprintf(__('<a href="%s">%s</a> does not support this network for signing in.'), 
			site_url('wp-login.php'), get_bloginfo('sitename')));

	} else {
		$provider = $plugin->socialite->driver($driver);

		if ($scopes = $plugin->request->input('scopes')) {
			$scopes = array_map('trim', !is_array($scopes) ? preg_split('/,\s*/', $scopes) : $scopes);
			$provider->scopes($scopes);
		}

		$response = $provider->redirect();
		wp_redirect($response->headers->get('location'));
	}
});