<?php
/*
Plugin Name: 		Auth
Plugin URI:  		https://github.com/withfatpanda/auth-plugin-wordpress
Description: 		Expand the core auth and registration features of WordPress.
Version:     		1.0.0
Author:      		Fat Panda 
Author URI:  		https://github.com/withfatpanda
License:     		GPL2
License URI: 		https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: 		fp-auth
Domain Path: 		/resources/lang
*/

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/functions.php';
require_once __DIR__.'/src/plugin.php';

$plugin = new FatPanda\WordPress\Auth\Auth(__FILE__);

// Using custom data types? Register them here:
// $plugin->registerCustomPostType( Root\FooBar\Models\Person::class );
// $plugin->registerTaxonomy( Root\FooBar\Models\Department::class );