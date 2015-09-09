<?php

/**
 * DonDominio Account Sincronization.
 * Gets all domains from a DonDominio account and attemps to sincronize
 * them with the local WHMCS database.
 * @copyright Soluciones Corporativas IP, SL 2015
 * @package DonDominioWHMCSImporter
 */

/**
 * DonDominio Account Sincronization.
 * Gets all domains from a DonDominio account and attemps to sincronize
 * them with the local WHMCS database.
 */
class DDSync
{
	/**
	 * Array of options used by this class.
	 * @var array
	 */
	protected $options = array(
		'apiClient' => null,
		'clientId' => '',
		'dryrun' => false,
		'forceClientId' => false
	);
	
	/**
	 * Initialize sync.
	 * Applies the options provided and checks for any missing
	 * or invalid parameters.
	 * @param array $options Options
	 */
	public function __construct(array $options = array())
	{
		$this->options = array_merge(
			$this->options,
			(is_array($options)) ? $options : array()
		);
		
		DDOutput::debug("Checking DonDominio API Client");
		if(!$this->options['apiClient'] instanceOf DonDominioAPI){
			DDOutput::error("API Client is not a valid DonDominioAPI instance.");
			exit();
		}
		
		DDOutput::debug("Checking valid Client ID");
		if(!$this->options['clientId']){
			DDOutput::error("You must specify a valid Client ID to continue.");
		}
		
		DDOutput::debug("Searching Client ID in database");
		if(!$this->findUser()){
			DDOutput::error('Client could not be found. Provide a valid Client ID using the --uid parameter.');
		}
		
		if($this->options['dryrun']){
			DDOutput::debug("--dry flag found, enabling Dry Run mode");
			DDOutput::line("*** DRY RUN MODE ***");
			DDOutput::line("No changes will be made to your database.");
		}
	}
	
	/**
	 * Sync domains.
	 * Creates all missing domains in the local database comparing it against
	 * the DonDominio account associated to the API Username provided.
	 *
	 * Does not return anything, writes directly to output.
	 */
	public function sync()
	{
		DDOutput::debug("Sync start");
		
		$total = $results = $created = $exists = $error = 0;
		
		$error_list = array();
		
		DDOutput::debug("Getting domains from API");
		
		$domains = $this->getDomainsFromAPI();
		
		$order_created = false;
		
		$i = 1;
		
		DDOutput::debug("Looping through " . count($domains) . " domains");
		
		foreach($domains as $domain){			
			if($this->domainExists($domain['name'])){
				DDOutput::debug("Domain " . $domain['name'] . " already on database. Do nothing.");
								
				$exists++;
			}else{
				if(!$this->tldExists($domain['tld'])){
					DDOutput::line(str_pad($domain['name'], 30, " ") . "TLD not configured (" . $domain['tld'] . ")");
					$error_list['tld_' . $domain['tld'] . '_notfound'] = 'You need to configure the ' . $domain['tld'] . ' TLD in WHMCS to sync .' . $domain['tld'] . ' domains.';
					
					$error++;
				}else{
					DDOutput::debug("Searching domain owner for " . $domain['name']);
					
					$user = ($this->options['forceClientId']) ? $this->options['clientId'] : $this->findDomainOwner($domain['name']);
					
					if(!$user){
						DDOutput::line("An error occurred while getting the domain's owner. Can't continue.");
						DDOutput::line("");
						
						return false;
					}
					
					if(!$order_created){
						if(!$this->options['dryrun']){
							DDOutput::debug("Creating order in database to hold domains");
							
							$orderId = $this->createOrder($user);
							
							if(!is_numeric($orderId)){							
								DDOutput::line("An error ocurred while creating the order: " . $orderId);
								DDOutput::line("");
								
								return false;
							}
						}
						
						$order_created = true;
					}
					
					if(!$this->options['dryrun']){
						DDOutput::debug("Creating domain " . $domain['name']);
						$create = $this->createDomain($orderId, $domain['name'], $domain['tld'], $domain['tsExpir']);
						
						if($create !== true){
							DDOutput::line(str_pad($domain['name'], 30, " ") . "Error: " . $create);
						}else{
							DDOutput::line(str_pad($domain['name'], 30, " ") . "Created");
						}
					}
					
					$created++;
				}
			}
			
			$i++;
		}
		
		DDOutput::line("");
		DDOutput::line("Sync finished.");
		DDOutput::line("$created domains created - $exists already exist - $error errors found");
		DDOutput::line("");
		
		if(count($error_list)){
			DDOutput::line("The following errors were found:");
			
			foreach($error_list as $error){
				DDOutput::line("-" . $error);
			}
		}
	}
	
	/**
	 * Get the domain list from the API.
	 * Returns an array containing all the domains in the user account.
	 * @return array
	 */
	protected function getDomainsFromAPI()
	{
		$dondominio = $this->options['apiClient'];
		
		$domains = array();
		
		do{
			try{				
				$list = $dondominio->domain_list();
							
				$info = $list->get("queryInfo");
								
				$results = $info['results'];
				$total = $info['total'];
				
				$domainList = $list->get("domains");
				
				$domains = array_merge(
					$domains,
					$domainList
				);
			}catch(DonDominioAPI_Error $e){
				DDOutput::line("");
				DDOutput::line("There was an error fetching information: " . $e->getMessage());
				
				break;
			}
		}while(count($domains) < $total);
		
		return $domains;
	}
	
	/**
	 * Find an user in the database.
	 * Returns true if the user exists, false otherwise.
	 * @return boolean
	 */
	protected function findUser()
	{
		$uid = $this->options['clientId'];
		
		$user = full_query("SELECT u.id FROM tblclients u WHERE u.id = '" . $uid . "'");
		
		if(mysql_num_rows($user) != 1){
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check if a domain already exists.
	 * Returns true if the domain exists, false otherwise.
	 * @param string $cname Domain
	 * @return boolean
	 */
	protected function domainExists($cname)
	{
		$query = full_query("SELECT D.id, D.domain, D.registrar FROM tbldomains D WHERE D.domain = '" . $cname . "'");
	
		if(mysql_num_rows($query) == 0){
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check if a TLD exists and is configured to work with DonDominio.
	 * Returns true if the TLD exists, false otherwise.
	 * @param string $tld TLD
	 * @return boolean
	 */
	protected function tldExists($tld)
	{
		$checkTLD = full_query("SELECT extension FROM tbldomainpricing WHERE extension = '." . $tld . "' AND autoreg = 'dondominio'");
			
		if(mysql_num_rows($checkTLD) == 0){
			return false;
		}
		
		return true;
	}
	
	/**
	 * Create a new order to hold the domains.
	 * Returns the Order Id upon success, or the error from the database if failed.
	 * @return integer|string
	 */
	protected function createOrder()
	{
		$uid = $this->options['clientId'];
		
		$order = full_query("
			INSERT INTO tblorders(
				ordernum,
				userid,
				contactid,
				date,
				amount,
				notes
			) VALUES (
				1,
				" . $uid . ",
				0,
				NOW(),
				0,
				'Created automatically by DonDominio Domain Importer for WHMCS v" . APP_VERSION . " on " . date('m-d-Y H:i:s') . "'
			)
		");
		
		if(!$order){
			return mysql_error();
		}
		
		$order = full_query("SELECT MAX(id) FROM tblorders WHERE ordernum=1");
							
		list($orderId) = mysql_fetch_row($order);
		
		return $orderId;
	}
	
	/**
	 * Create a domain.
	 * Returns true if the domain has been created, or the error from the database if failed.
	 * @param integer $orderId Order that will hold the domain
	 * @param string $cname Domain to create
	 * @param string $tld TLD of the domain
	 * @param string $tsExpir Date of expiration
	 * @return boolean|string
	 */
	protected function createDomain($orderId, $cname, $tld, $tsExpir)
	{
		$uid = $this->options['clientId'];
		
		$query = "
			INSERT INTO tbldomains(
				userid,
				orderid,
				type,
				registrationdate,
				domain,
				firstpaymentamount,
				recurringamount,
				registrar,
				registrationperiod,
				expirydate,
				nextduedate,
				status,
				additionalnotes
			) VALUES (
				'" . $uid . "',
				'" . $orderId . "',
				'Register',
				NOW(),
				'" . $cname . "',
				0,
				(select msetupfee from tblpricing where type = 'domainrenew' and relid = (select id from tbldomainpricing where extension = '." . $tld . "')),
				'dondominio',
				1,
				'" . $tsExpir . "',
				'" . $tsExpir . "',
				'Active',
				'Created automatically by DonDominio Domain Importer for WHMCS v" . APP_VERSION . " on " . date('m-d-Y H:i:s') . "'
			)
		";
			
		$domain = full_query($query);
		
		if(!$domain){
			return mysql_error();
		}
		
		return true;
	}
	
	/**
	 * Find the Client ID of a domain owner.
	 * Returns the Client ID of the owner if found, false if not found or the error from the database if failed.
	 * @param string $cname Domain
	 * @return integer|string|boolean
	 */
	protected function findDomainOwner($cname)
	{
		$dondominio = $this->options['apiClient'];
		
		$email = "";
		$ownerId = $this->options['clientId'];
		
		try{
			$info = $dondominio->domain_getInfo($cname, array('infoType' => 'contact'));
			
			$owner = $info->get("contactOwner");
			
			$email = $owner['email'];
		}catch(DonDominioAPI_Error $e){
			return false;
		}
		
		$owner = full_query("SELECT id FROM tblclients WHERE email = '" . $email . "'");
		
		if(mysql_num_rows($owner) > 0){
			list($ownerId) = mysql_fetch_row($owner);
		}
		
		return $ownerId;
	}
}