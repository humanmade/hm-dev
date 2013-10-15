# WordPress Development by Human Made Limited

Various things to assist with the development of kick ass WordPress Sites & Apps.

**This plugin should be activated on your site whilst you are developing it locally.**

## Componants

### DEV •

Prepends "Dev • " to the site `<title />` and admin bar site name menu.

### Debug functions

Our better `var_dump` / `print_r` and others.

````
hm( $foo );
hm_log( $foo );
````

### Safe Email

All email sent using `wp_mail` is redirected to whatever is defined as `HM_DEV_EMAIL`, default is `dev@hmn.md`.

You can override by the email messages are redirected to by adding the following line to your `wp-config.php`:

````
define( 'HM_DEV_EMAIL', 'email_goes_here' );
````

If you want to disable the redirect and allow all email to go it the original recipients then add the following to your `wp-config.php` file:

````
define( 'HM_DEV_EMAIL', false );
````

### WP CLI `import` command

Add a `import` command to wp-cli to allow easy synching of database and uploads between your local server and the production server.

Knows about WP Thumb so won't import the `uploads/cache` dir.

````
$ wp import uploads --uploads_dir="2012"
$ wp import db
````

To get those commands to work `define` the following in your `wp-config-local.php` file.

````
define( 'IMPORT_DB_HOST', 'database_hostname' );
define( 'IMPORT_DB_USER', 'database_user_name' );
define( 'IMPORT_DB_NAME', 'database_name' );
define( 'IMPORT_DB_PASSWORD', 'database_password' );

define( 'IMPORT_UPLOADS_SSH_HOST', 'ssh_hostname' );
define( 'IMPORT_UPLOADS_SSH_USER', 'ssh_username' );
define( 'IMPORT_UPLOADS_REMOTE_PATH', 'remote_path_to_uploads_dir' );
````

### WP Unit

Submodules in our fork of WP Unit (originally from https://github.com/nunomorgadinho/wp-unit).

UNIT TEST ALL THE THINGS

WP Unit requires the PHPUnit PEAR module to be installed.

````
$ sudo pear config-set auto_discover 1
$ sudo pear install pear.phpunit.de/PHPUnit
````

### WP CLI WP Unit `test` command

Adds support for running WP Unit tests using WP CLI

````
$ wp test show
$ wp test run
$ wp test run exampleTestCase
````

### Timestack

Submodules in the timestack plugin from https://github.com/joehoyle/Time-Stack-Plugin

## Contribution guidelines ##

see https://github.com/humanmade/hm-dev/blob/master/CONTRIBUTING.md

