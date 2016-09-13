<?php
//
// Database Configuration File created by baserCMS Installation
//
class DATABASE_CONFIG {
public $baser = array(
	'datasource' => 'Database/BcSqlite',
	'persistent' => false,
	'host' => '',
	'port' => '',
	'login' => '',
	'password' => '',
	'database' => '/Library/WebServer/Documents/ogicomi/app/db/sqlite/basercms.db',
	'schema' => '',
	'prefix' => '',
	'encoding' => 'utf8'
);
public $plugin = array(
	'datasource' => 'Database/BcSqlite',
	'persistent' => false,
	'host' => '',
	'port' => '',
	'login' => '',
	'password' => '',
	'database' => '/Library/WebServer/Documents/ogicomi/app/db/sqlite/basercms.db',
	'schema' => '',
	'prefix' => 'pg_',
	'encoding' => 'utf8'
);
public $test = array(
	'datasource' => 'Database/BcSqlite',
	'persistent' => false,
	'host' => '',
	'port' => '',
	'login' => '',
	'password' => '',
	'database' => '/Library/WebServer/Documents/ogicomi/app/db/sqlite/basercms.db',
	'schema' => '',
	'prefix' => 'test_',
	'encoding' => 'utf8'
);
}
