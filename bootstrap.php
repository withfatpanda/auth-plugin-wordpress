<?php
/*
Plugin Name: 		Auth
Plugin URI:  		https://github.com/withfatpanda/auth-plugin-wordpress
Description: 		Social login and registration, built with <a href="https://github.com/withfatpanda/bamboo">Bamboo</a> and powered by <a href="https://github.com/laravel/socialite">Laravel Socialite</a>
Version:     		1.2.0
Author:      		Fat Panda 
Author URI:  		https://github.com/withfatpanda
License:     		GPL2
License URI: 		https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: 		fp-auth
Domain Path: 		/resources/lang
*/

@include_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/functions.php';
FatPanda\Illuminate\WordPress\Plugin::bootstrap(__FILE__);