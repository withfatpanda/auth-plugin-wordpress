# CHANGELOG

## v1.0.2

* Environment-based configuration of the built-in drivers now supported: `facebook`, `twitter`, `google`, `linkedin`, `github`, and `bitbucket`
* Created `socialite_add_providers` action hook as a means of expanding available drivers via third-party packages (installed via Composer); this feature depends on expanding [illuminate-wordpress](https://github.com/withfatpanda/illuminate-wordpress) to support the Laravel events backbone&mdash;not ready for prime-time
* Social buttons are now laid out in rows of three; didn't like how any of the other configurations looked

## v1.0.1

* Fix a bug in the autoloader context detection

## v1.0.0

* First release. Yay!