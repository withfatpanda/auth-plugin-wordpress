<?php
namespace FatPanda\WordPress\Auth;

use FatPanda\Illuminate\WordPress\Plugin;
use Laravel\Socialite\AbstractUser;
use Illuminate\Support\Str;

class Auth extends Plugin {
	
	/**
	 * This function will be invoked on WordPress' "init" action; note
	 * that text translation features have already been configured by the
	 * baseclass: you don't need to do that yourself. 
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/init
	 * @see https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
	 */
	function onInit()
	{
		$token = $this->request->input('socialite');
		if ($session = $this->getSavedSocialiteUser($token)) {
			// XXX: This is dirty, dirty, dirty...
			// If this ends up being unstable, introduce form post from callbacks
			$_SERVER['REQUEST_METHOD'] = 'POST'; 
			
			// fill in default user_login
			if (!$this->request->input('user_login')) {
				// fall back is driver + id
				$nickname = $session['driver'] . $session['user']->id;
				// best solution is nickname from third-party
				if (!empty($session['user']->nickname)) {
					$nickname = Str::slug($session['user']->nickname, '');
				} else if (!empty($session['user']->name)) {
					$nickname = Str::slug($session['user']->name, '');
				}

				$_POST['user_login'] = $_REQUEST['user_login'] = $nickname;
			}
			if (!$this->request->input('user_email') && !empty($session['user']->email)) {
				$_POST['user_email'] = $_REQUEST['user_email'] = $session['user']->email;
			}
		}
	}

	function onLoginEnqueueScripts()
	{
		?>
			<link rel="stylesheet" href="<?= $this->url('/node_modules/font-awesome/css/font-awesome.min.css') ?>">
			<link rel="stylesheet" href="<?= $this->url('/ui/css/admin.css') ?>">
		<?php
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
	protected function printSocialButtons($config, $help)
	{
		?>
			<fieldset class="fp-auth">
				<legend>
					<span>or</span>
				</legend>
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
	 * Allow for services to be configured from multiple data sources,
	 * including the environment and the options table.
	 * @return array
	 */
	protected function getServicesConfig()
	{
		$services = [];

		$services['facebook'] = [
			'name' => 'Facebook',
			'client_id' => getenv('SERVICES_FACEBOOK_CLIENT_ID') ?: get_option('socialite_facebook_client_id'),
			'client_secret' => getenv('SERVICES_FACEBOOK_CLIENT_SECRET') ?: get_option('socialite_facebook_client_secret'),
			'redirect' => home_url('fp-auth/socialite/facebook/callback'),
			'enabled' => true,
			'icon' => 'fa fa-facebook'
		];

		$services['twitter'] = [
			'name' => 'Twitter',
			'client_id' => getenv('SERVICES_TWITTER_CLIENT_ID') ?: get_option('socialite_twitter_client_id'),
			'client_secret' => getenv('SERVICES_TWITTER_CLIENT_SECRET') ?: get_option('socialite_twitter_client_secret'),
			'redirect' => home_url('fp-auth/socialite/twitter/callback'),
			'enabled' => true,
			'icon' => 'fa fa-twitter'
		];

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
			$user = get_user_by('ID', $connected->user_id);
			wp_set_auth_cookie($user->ID);
			do_action('wp_login', $user->user_login, $user);
			return $user;

		// otherwise, create a registration session	
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