<?php

/**
 * DonDominio Domain Importer for WHMCS
 * Synchronization tool for domains in DonDomino accounts and WHMCS.
 * @copyright Soluciones Corporativas IP, SL 2015
 * @package DonDominioWHMCSImporter
 */
 
/**
 * Application version.
 */
define('APP_VERSION', '0.2');

/**#@+
 * Required files.
 */
include dirname(__FILE__) . '/../../../init.php';			//WHMCS Initialization
require dirname(__FILE__) . '/lib/class/DDLog.php';			//Log Handler
require dirname(__FILE__) . '/lib/class/DDOutput.php';		//Output Handler
require dirname(__FILE__) . '/lib/class/DDSync.php';		//Sync library
require dirname(__FILE__) . '/lib/class/DDArguments.php';		//Argument parser
require dirname(__FILE__) . '/lib/sdk/DonDominioAPI.php';	//DD API SDK
/**#@-*/

/*
* Arguments passed to the application.
*/
$arguments = new DDArguments;

$arguments->addOption(array('username', 'u'), null, 'DonDominio API Username (Required)');
$arguments->addOption(array('password', 'p'), null, 'DonDominio API Password (Required)');
$arguments->addOption('uid', null, 'Default Client Id (Required)');
$arguments->addOption(array('output', 'o'), "php://stdout", 'Filename to output data - Defaults to STDOUT');

$arguments->addFlag('forceUID', 'Use the default Client Id for all domains');
$arguments->addFlag('dry', 'Do not make any changes to the database');
$arguments->addFlag(array('verbose', 'v'), 'Display extra output');
$arguments->addFlag(array('debug', 'd'), 'Display cURL debug information');
$arguments->addFlag(array('silent', 's'), 'No output');
$arguments->addFlag('version', 'Version information');
$arguments->addFlag(array('help', 'h'), 'This information');

$arguments->parse();
/*
* --
*/

//¿Enable Silent mode?
if($arguments->get('silent')){
	DDOutput::setSilent(true);
}

//Set output file/method
DDOutput::setOutput($arguments->get('output'));

//Check required arguments
//If an argument is missing, show help screen.
//Also show help screen with --help (-h) flag.
if(
	(
		!$arguments->get('username') ||
		!$arguments->get('password') ||
		!$arguments->get('uid') ||
		$arguments->get('help')
	) &&
	!$arguments->get('version')
){
	$arguments->helpScreen();
	
	DDOutput::line("");
	
	exit();
}

//Display version information
if($arguments->get('version')){
	DDOutput::debug("Version information requested");
	
	displayVersion();
	
	exit();
}

//¿Is the "verbose" flag set?
//If so, enable verbose mode
if($arguments->get('verbose')){
	DDOutput::setDebug(true);
}

/*
 * Init DD API SDK
 */
DDOutput::debug("Initializing DonDominio API Client");

//Options for DD API SDK
$options = array(
	'apiuser' => $arguments->get('username'),
	'apipasswd' => $arguments->get('password'),
	'autoValidate' => true,
	'versionCheck' => true,
	'debug' => ($arguments->get('debug') && !$arguments->get('silent')) ? true : false,
	'response' => array(
		'throwExceptions' => true
	)
);

//The DonDominio API Client
$dondominio = new DonDominioAPI($options);

/*
 * Start sinchronization.
 */
DDOutput::debug("Initializing Sync");

//DDSync class
$sync = new DDSync(array(
	'apiClient' => $dondominio,						//An initialized DonDominioAPI object
	'clientId' => $arguments->get('uid'),			//Default WHMCS client ID
	'dryrun' => $arguments->get('dry'),				//Dry run - makes no changes to database
	'forceClientId' => $arguments->get('forceUID')	//Always use default WHMCS Client ID for all operations
));

//Start syncing
$sync->sync();

/**
 * Display version information.
 */
function displayVersion()
{
	DDOutput::line("");
	DDOutput::line("DonDominio Domain Importer for WHMCS v" . APP_VERSION);
	DDOutput::line("Copyright (c) 2015 Soluciones Corporativas IP SL");
	DDOutput::line("");
	DDOutput::line("For usage instructions, use -h or --help");
	DDOutput::line("");
}