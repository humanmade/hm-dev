# WordPress Development by Human Made Limited

Various things to assist with the development of kick ass WordPress Sites & Apps.

**This plugin should be activated on your site whilst you are developing it locally.**

## Componants

### Debug functions

Our better `var_dump` / `print_r` and others.

````
hm( $foo );
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

Add import and export commands to wp-cli to allow easy synching of database and uploads between your local server and the production server.

Knows about WP Thumb so won't import the `uploads/cache` dir.

````
$ wp import uploads --import-dir="uploads/2012"
$ wp import db
````

To get those commands to work add the following `define`'s to your `wp-config.php` file.

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

### WP CLI WP Unit `test` command

Adds support for running WP Unit tests using WP CLI

````
$ wp test show
$ wp test run
$ wp test run exampleTestCase
````

### Timestack

Submodules in the timestack plugin from https://github.com/joehoyle/Time-Stack-Plugin