# Add social login and more to WordPress.
[![Packagist](https://img.shields.io/packagist/v/withfatpanda/auth-plugin-wordpress.svg?style=flat-square)](https://packagist.org/packages/withfatpanda/auth-plugin-wordpress)
[![Patreon](https://img.shields.io/badge/patreon-donate-yellow.svg)](https://patreon.com/withfatpanda)

This project is a work in progress. It should be considered an unstable experiment until otherwise advertised herein&mdash;not for production use. Thank you.

---

This plugin expands WordPress' core authentication features:

* Log in or register using accounts on [almost 100 social networks](https://socialiteproviders.github.io/) ([screenshot](https://github.com/withfatpanda/auth-plugin-wordpress/raw/master/assets/screenshot-1.png))
* Associate social network accounts with existing WordPress Users

Features in our roadmap:

* Log in via one-time links sent to e-mail (i.e., Magic Links)
* Designate which roles are given to Users when they register
* Protect your site from spam and abuse with [reCAPTCHA](https://www.google.com/recaptcha/intro/index.html)
* Two-factor authentication via SMS

This plugin is free to use in any project (public, private, non-profit and for-profit; dual licensed GPLv2 and MIT).

## Requirements

* PHP >= 5.6.4
* WordPress >= 4.0
* [Bedrock](http://roots.io/bedrock)

## Installation

Use Composer to add this plugin as a dependency to your Bedrock-based WordPress installation:

`composer require withfatpanda/auth-plugin-wordpress`

## Configuration

For each third-party service you wish to employ in authentication, you will need a driver, 
a client ID, and a client secret.

Out of the box, this plugin makes available six drivers: `facebook`, `twitter`, `linkedin`, `google`, `github`, and `bitbucket`. 

**Note:** In addition to these built-in drivers, there are almost 100 socialite drivers available through the community-driven [Socialite Providers](https://socialiteproviders.github.io) project, but to use any one of them, you must do some additional setup work (see *Using Third-Party Providers* below).

For each provider you wish to enable for authentication:

1. Create a relationship with the provider; for example, if you want to enable Facebook as a login provider,
you must first create a Facebook app. This process will be slightly different for each provider, and is beyond the scope of this documentation. Good luck!

2. Get the public ID and secret key for each of your apps; again, this is outside the scope of this documentation.

3. Install these values into your [Bedrock environment](https://roots.io/bedrock/docs/environment-variables/) as follows:

  ```
  SERVICES_FACEBOOK_CLIENT_ID=Public ID
  SERVICES_FACEBOOK_CLIENT_SECRET=Secret Key
  ```

  Where `FACEBOOK` should be the name of the driver you're configuring.

4. Also in your Bedrock environment, you will need to install a list of the drivers you are using, as follows:

  ```
  SOCIALITE_PROVIDERS=facebook,twitter,google
  ```

5. If you haven't done so yet, activate the plugin!

  ```
  wp plugin activate auth-plugin-wordpress
  ```

6. Flush your cached rewrite rules; this plugin adds two rewrite rules&mdash;one for inititing the OAuth flow, and another for handling the response from the auth providers. You can flush your rewrite rules with WP-CLI, as follows:

  ```
  wp rewrite flush
  ```

### Using Third-Party Providers

To be written.

## About This Project

This plugin is the first to be built with the [illuminate-wordpress](https://github.com/withfatpanda/illuminate-wordpress) project.

illuminate-wordpress, powered by the [Laravel Framework](https://laravel.com), provides developers with an expressive, beautiful syntax for building faster for WordPress. Extending WordPress' REST API, defining custom data types and taxonomies, querying the database, and much more are all made easier through a semantic, object-oriented API. The objective of illuminate-wordpress is to allow the entire community of Laravel developers to fall in love with WordPress the way that we have fallen in love with Laravel.

This project in particular depends heavily on [Laravel Socialite](https://github.com/laravel/socialite)&mdash;a library that handles almost all of the boilerplate social authentication code that all developers dread writing.

## About Fat Panda

[Fat Panda](https://www.withfatpanda.com) is a software product consultancy located in Winchester, VA. We specialize in Laravel, WordPress, and Ionic. No matter where you are in the development of your product, we'll meet you there and work with you to propel you forward.

## Contributing

If you run into a problem using this plugin, please [open an issue](https://github.com/withfatpanda/auth-plugin-wordpress/issues).

If you want to help make this plugin amazing, check out the [help wanted](https://github.com/withfatpanda/auth-plugin-wordpress/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22) list.

If you'd like to support this and the other open source projects Fat Panda is building, please join our community of supporters on [Patreon](https://www.patreon.com/withfatpanda).




