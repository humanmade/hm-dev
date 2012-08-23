# WordPress Development by Human Made Limited

Various things to assist with the development of kick ass WordPress Sites & Apps.

**This plugin should be activated on your site whilst you are developing it locally.**

## Componants

### Debug functions

Our better `var_dump` / `print_r` and others.

````
hm( $foo );
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