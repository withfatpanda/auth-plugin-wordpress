<?php
namespace FatPanda\WordPress\Auth;

use FatPanda\Illuminate\WordPress\Plugin;

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
		?>
			<fieldset class="fp-auth">
				<legend>
					<span>or</span>
				</legend>
				<div class="help-block">Register using your favorite social network</div>
				<div class="social-buttons">
					<a class="social-button social-button-facebook" href="#">
						<i class="fa fa-facebook"></i>
					</a>
					<a class="social-button social-button-twitter" href="#">
						<i class="fa fa-twitter"></i>
					</a>
					<a class="social-button social-button-google" href="#">
						<i class="fa fa-google"></i>
					</a>
				</div>
			</fieldset>
		<?php
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