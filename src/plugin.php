<?php
namespace FatPanda\WordPress\Auth;

use Laravel\Socialite\AbstractUser;
use Illuminate\Support\Str;
use \FatPanda\Illuminate\WordPress\Plugin as BasePlugin;

class Plugin extends BasePlugin {
	
	/**
	 * Using the action "socialite_add_providers", we allow any other module
	 * in WordPress to install additional providers into Socialite.
	 */
	function onPluginsLoaded()
	{
		$this->register( \Laravel\Socialite\SocialiteServiceProvider::class );
		$this->alias( 'Laravel\Socialite\Contracts\Factory', 'socialite' );

		do_action('socialite_add_providers', $this);
	}

	function onLoginEnqueueScripts()
	{
		?>
			<link rel="stylesheet" href="<?= $this->url('/node_modules/font-awesome/css/font-awesome.min.css') ?>">
			<link rel="stylesheet" href="<?= $this->url('/resources/css/admin.css') ?>">
		<?php
	}

	function filterCommentFormDefaults($defaults)
	{
		$defaults;

		ob_start();

		$this->printSocialButtons($this->getServicesConfig(), __('Please login to post a comment.', 'fp-auth'), false);
		
		$defaults['must_log_in'] = ob_get_clean();

		return $defaults;
	}

	/**
	 * Add social login features to the registration form.
	 *
	 * @return void
	 */
	function onRegisterForm()
	{
		if ($config = $this->getServicesConfig()) {

			$token = $this->request->input('socialite');

			if ($session = $this->getSavedSocialiteUser($token)) {

				?>
					<!-- <pre><?php print_r($session) ?></pre> -->
					<input type="hidden" name="socialite" value="<?php echo esc_attr($token) ?>">
				<?php

				if ($session['user']->avatar) { 
					?>
						<style>
							.login h1 a {
								background-image: url('<?php echo $session['user']->avatar ?>');
								border-radius: 50%;
								border: 5px solid #333;
							}
						</style>
					<?php
				}

				// fall back is driver + id
				$nickname = $session['driver'] . $session['user']->id;
				// best solution is nickname from third-party
				if (!empty($session['user']->nickname)) {
					$nickname = Str::slug($session['user']->nickname, '');
				} else if (!empty($session['user']->name)) {
					$nickname = Str::slug($session['user']->name, '');
				}
				$default_user_login = $nickname;

				// generate a default user_email
				$default_user_email = null;
				if (!empty($session['user']->email)) {
					$default_user_email = $session['user']->email;
				}
				
				?>
					<script type="text/javascript">
						!function() {
							var user_login = document.getElementById('user_login');
							var user_email = document.getElementById('user_email');
							if (!user_login.value) {
								user_login.value = '<?php echo esc_js($default_user_login) ?>';
							}
							if (!user_email.value) {
								user_email.value = '<?php echo esc_js($default_user_email) ?>';
							}
						}();
					</script>
				<?php
			
			} else {

				$this->printSocialButtons($config, __('Register using your favorite network.', 'fp-auth'));

			}
		}
	}

	/**
	 * @return void
	 */
	function onLoginForm()
	{
		if ($config = $this->getServicesConfig()) {

			$this->printSocialButtons($config, __('Log in using your favorite network.', 'fp-auth'));			

		}
	}

	/**
	 * @return void
	 */
	protected function printSocialButtons($config, $help, $separator = true)
	{
		?>
			<fieldset class="fp-auth">
				<?php if ($separator) { ?>
					<legend>
						<span>or</span>
					</legend>
				<?php } ?>
				<div class="help-block"><?php echo $help ?></div>
				<div class="social-buttons">
					<?php foreach($config as $network => $service) { ?>
						<a href="<?php echo esc_attr(home_url("fp-auth/socialite/{$network}")) ?>" 
							title="<?php echo sprintf(__('Register for %s with your %s account'), get_bloginfo('sitename'), $service['name']) ?>"
							class="social-button social-button-<?php echo $network ?>">
							<i class="<?php echo esc_attr($service['icon']) ?>"></i>
						</a>
					<?php } ?>
				</div>
			</fieldset>
		<?php
	}

	/**
	 * Get the Client ID for the given driver; searches first in environment, second
	 * in global options, and finally filters the result by "socialite_client_id"
	 * @param string The driver, e.g., "facebook"
	 * @return string The client ID
	 */
	protected function getProviderClientId($driver)
	{
		$environment_name = 'SERVICES_' . strtoupper($driver) . '_CLIENT_ID';
		$option_name = 'socialite_' . strtolower($driver) . '_client_id';
		return apply_filters('socialite_client_id', getenv($environment_name) ?: get_option($option_name), $driver);
	}

	/**
	 * Get the Client Secret for the given driver; searches first in environment, second
	 * in global options, and finally filters the result by "socialite_client_secret"
	 * @param string The driver, e.g., "facebook"
	 * @return string The client ID
	 */
	protected function getProviderClientSecret($driver)
	{
		$environment_name = 'SERVICES_' . strtoupper($driver) . '_CLIENT_SECRET';
		$option_name = 'socialite_' . strtolower($driver) . '_client_secret';
		return apply_filters('socialite_client_secret', getenv($environment_name) ?: get_option($option_name), $driver);
	}

	/**
	 * Get a name for the service identify by the given driver.
	 * @param string The driver, e.g., "facebook"
	 * @return string
	 */
	protected function getProviderName($driver)
	{
		return __( getenv('SERVICES_' . strtoupper($driver) . '_NAME') ?: strtoupper($driver), 'fp-auth' );
	}

	/**
	 * Get a name for the service identify by the given driver.
	 * @param string The driver, e.g., "facebook"
	 * @return string
	 */
	protected function getProviderIcon($driver)
	{
		$hasFontAwesomeIcon = [ 'facebook', 'twitter', 'github', 'linkedin', 'google' ];
		$defaultIcon = 'fa fa-plug';
		if (in_array(strtolower($driver), $hasFontAwesomeIcon)) {
			$defaultIcon = 'fa fa-' . strtolower($driver);
		}
		return apply_filters('socialite_icon', getenv('SERVICES_' . strtoupper($driver) . '_ICON') ?: $defaultIcon, $driver);
	}

	/**
	 * Get a list of the driver names of providers that are enabled.
	 * @return array
	 */
	protected function getEnabledProvidersList()
	{
		$stored = apply_filters('socialite_providers', getenv('SOCIALITE_PROVIDERS') ?: get_option('socialite_providers'));
		return preg_split('/,\s*/', $stored);
	}

	/**
	 * Allow for services to be configured from multiple data sources,
	 * including the environment and the options table. The list of providers
	 * is stored in a global option "socialite_providers"; filtered by a filter
	 * of the same name. The list of provides can also be specified by a CSV
	 * in the environment variable SOCIALITE_PROVIDERS; the environment variable
	 * will take precedence over the global option, and the filter gets the last say.
	 * The list of providers should be a comma-separated list of driver names, e.g.,
	 * "facebook,twitter". For each provider, a Client ID and Client Secret will be
	 * sought; finding both, the service will be added to the list of services
	 * that users can use to login or register.
	 * @return array
	 */
	protected function getServicesConfig()
	{
		$services = [];

		$enabled = $this->getEnabledProvidersList(); ;

		foreach($enabled as $driver) {

			$id = $this->getProviderClientId($driver);
			$secret = $this->getProviderClientSecret($driver);

			if ($id && $secret) {

				$services[$driver] = [
					'name' => $this->getProviderName($driver),
					'client_id' => $id,
					'client_secret' => $secret,
					'redirect' => home_url('fp-auth/socialite/' . strtolower($driver) . '/callback'),
					'icon' => $this->getProviderIcon($driver),
				];

			}

		}

		return $services;
	}

	/**
	 * Add the given authenticated socialiate user to the given or current
	 * WordPress user; if the given socialite user is already associated
	 * with a different user account, move it to the given/current one.
	 * @param String the socialite driver name
	 * @param Laravel\Socialite\AbstractUser
	 * @param int Optionally, a user id of a user to attach the social data to
	 * @return void
	 * @throws Exception If no user is specified and the current WordPress 
	 * session is not authenticated
	 */
	function addSocialiteUserToWordPressUser($driver, AbstractUser $socialiteUser, $user_id = null) 
	{
		$user = null;
		if (empty($user_id)) {
			if (!is_user_logged_in()) {
				throw new \Exception("WordPress session is not authenticated.");
			}
			$user = wp_get_current_user();
			$user_id = $user->ID;
		} else {
			if (!$user = get_user_by('ID', $user_id)) {
				throw new \Exception("No WordPress user found for given ID");
			}
		}

		$user_meta_key = $this->getSocialiteUserMetaKey($driver, $socialiteUser);

		$connected = $this->db->table('usermeta')
			->whereNotNull('meta_value')
			->where('user_id', '<>', $user->ID)
			->whereMetaKey($user_meta_key)
			->first();

		if ($connected) {
			delete_user_meta($connected->user_id, $user_meta_key);
		}

		update_user_meta($user->ID, $user_meta_key, $socialiteUser);
	}

	/**
	 * @return string
	 */
	function getSocialiteUserMetaKey($driver, AbstractUser $socialiteUser)
	{
		return "_socialite_{$driver}_{$socialiteUser->id}";
	}

	/**
	 * Use the given socialite user to log in an existing WordPress user;
	 * finding none, create a registration session and return a token for
	 * retrieving the socialite user later.
	 * @param String the socialite driver name
	 * @param Laravel\Socialite\AbstractUser
	 * @return mixed Either a WP_User or a string: the token for the registration session
	 * @throws Exception If current WordPress session is already authenticated
	 */
	function loginSocialiteUser($driver, AbstractUser $socialiteUser) 
	{
		if (is_user_logged_in()) {
			throw new \Exception("WordPress session is already authenticated.");
		}

		// build the meta key from the driver name and the 
		// the remove unique identifier
		$user_meta_key = $this->getSocialiteUserMetaKey($driver, $socialiteUser);
		
		// look for an existing connection
		$connected = $this->db->table('usermeta')
			->whereNotNull('meta_value')
			->whereMetaKey($user_meta_key)
			->first();

		// if already connected, we'll just login
		if ($connected) {
			// if the user doesn't exist, we need to clean it up
			if (!$user = get_user_by('ID', $connected->user_id)) {
				delete_user_meta($connection->user_id, $user_meta_key);
			  return $this->saveSocialiteUserForLater($driver, $socialiteUser);
			// otherwise, we log the user in
			} else {
				wp_set_auth_cookie($user->ID);
				do_action('wp_login', $user->user_login, $user);
				return $user;
			}
		// and if they're not connected, we save the session for later
		// and (presumably) send the user along to registration
		} else {
			return $this->saveSocialiteUserForLater($driver, $socialiteUser);
		}
	}

	/**
	 * Look for a socialite token in the request; finding one,
	 * look up the user and attach it to the newly created WordPress
	 * user account. Also, attach any other meta data from the 
	 * registration form.
	 *
	 * @return void
	 */
	function onUserRegister($user_id)
	{
		if ($token = $this->request->input('socialite')) {
			if ($session = $this->getSavedSocialiteUser($token)) {
				$this->addSocialiteUserToWordPressUser($session['driver'], $session['user'], $user_id);
			}
		}
	}

	/**
	 * TODO: add filter
	 * @return string
	 */
	protected function getSocialiteTransientName($token)
	{
		return "_socialite_saved_{$token}";
	}

	/**
	 * @return String the session token
	 */
	function saveSocialiteUserForLater($driver, AbstractUser $socialiteUser)
	{
		$token = wp_generate_password( 43, false, false );
			
		set_transient($this->getSocialiteTransientName($token), [ 'driver' => $driver, 'user' => $socialiteUser ]);

		return $token;
	}

	/**
	 * @return array with two elements: the driver, and the AbstractUser object
	 */
	function getSavedSocialiteUser($token)
	{
		return get_transient($this->getSocialiteTransientName($token));
	}


	function forgetSavedSocialiteUser($token)
	{
		delete_transient($this->getSocialiteTransientName($token));
	}

	/**
	 * This function will be invoked when your plugin is activated.
	 * @see https://codex.wordpress.org/Function_Reference/register_activation_hook
	 */
	function onActivate()
	{
		
	}

	/**
	 * This function will be invoked when your plugin is deactivated.
	 * @see https://codex.wordpress.org/Function_Reference/register_deactivate_hook
	 */
	function onDeactivate()
	{

	}
	
}