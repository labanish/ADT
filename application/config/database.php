<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
/*
 | -------------------------------------------------------------------
 | DATABASE CONNECTIVITY SETTINGS
 | -------------------------------------------------------------------
 | This file will contain the settings needed to access your database.
 |
 | For complete instructions please consult the 'Database Connection'
 | page of the User Guide.
 |
 | -------------------------------------------------------------------
 | EXPLANATION OF VARIABLES
 | -------------------------------------------------------------------
 |
 |	['hostname'] The hostname of your database server.
 |	['username'] The username used to connect to the database
 |	['password'] The password used to connect to the database
 |	['database'] The name of the database you want to connect to
 |	['dbdriver'] The database type. ie: mysql.  Currently supported:
 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
 |	['dbprefix'] You can add an optional prefix, which will be added
 |				 to the table name when using the  Active Record class
 |	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
 |	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
 |	['cache_on'] TRUE/FALSE - Enables/disables query caching
 |	['cachedir'] The path to the folder where cache files should be stored
 |	['char_set'] The character set used in communicating with the database
 |	['dbcollat'] The character collation used in communicating with the database
 |	['swap_pre'] A default table prefix that should be swapped with the dbprefix
 |	['autoinit'] Whether or not to automatically initialize the database.
 |	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
 |							- good for ensuring strict SQL while developing
 |
 | The $active_group variable lets you choose which connection group to
 | make active.  By default there is only one group (the 'default' group).
 |
 | The $active_record variables lets you determine whether or not to load
 | the active record class
 */

$active_group = 'default';
$active_record = TRUE;
$db['default']['hostname'] = 'localhost';
$db['default']['username'] = 'root';
$db['default']['password'] = 'root';
$db['default']['database'] = 'testadt';
$db['default']['port'] = 3306;
$db['default']['dbdriver'] = 'mysql';
$db['default']['dbprefix'] = '';
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = FALSE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;

$db['mirth_db']['hostname'] = 'localhost';
$db['mirth_db']['username'] = 'root';
$db['mirth_db']['password'] = 'root';
$db['mirth_db']['database'] = 'mirth_adt_db';
$db['mirth_db']['port'] = 3306;
$db['mirth_db']['dbdriver'] = 'mysql';
$db['mirth_db']['dbprefix'] = '';
$db['mirth_db']['pconnect'] = FALSE;
$db['mirth_db']['db_debug'] = FALSE;
$db['mirth_db']['cache_on'] = FALSE;
$db['mirth_db']['cachedir'] = '';
$db['mirth_db']['char_set'] = 'utf8';
$db['mirth_db']['dbcollat'] = 'utf8_general_ci';
$db['mirth_db']['swap_pre'] = '';
$db['mirth_db']['autoinit'] = TRUE;
$db['mirth_db']['stricton'] = FALSE;

/* End of file database.php */

/**
 * Doctrine integration
 */
$db['default']['cachedir'] = "";
// Create dsn from the info above
$db['default']['dsn'] = $db['default']['dbdriver'] . '://' . $db['default']['username'] . ':' . $db['default']['password'] . '@' . $db['default']['hostname'] . '/' . $db['default']['database'] . '' . '?charset=utf-8';

// Require Doctrine.php
require_once (realpath(dirname(__FILE__) . '/../..') . DIRECTORY_SEPARATOR . 'system/database/Doctrine.php');

// Set the autoloader
spl_autoload_register(array('Doctrine', 'autoload'));

// Load the Doctrine connection
Doctrine_Manager::connection($db['default']['dsn'], $db['default']['database']);

// Load the models for the autoloader
Doctrine::loadModels(realpath(dirname(__FILE__) . '/..') . DIRECTORY_SEPARATOR . 'models');

/* Location: ./application/config/database.php */
