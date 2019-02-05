<?php

/**
 * DonDominio/MrDomain Registrar Module for WHMCS
 * This module uses the {@see https://docs2.dondominio.com/api/ DonDominio API}
 * to perform tasks like registering, transfering, and updating domains from
 * WHMCS.
 * API version 0.9.x
 * WHMCS version 5.2.x / 5.3.x
 * @link https://github.com/dondominio/dondominiowhmcs
 * @package DonDominioWHMCS
 * @license CC BY-ND 3.0 <http://creativecommons.org/licenses/by-nd/3.0/>
 */
 
/**
 * The DonDominio API Client for PHP
 */
if(!class_exists( 'DonDominioAPI' )){
	require_once( "lib/sdk/DonDominioAPI.php" );
}

/**
 * Helper library.
 */
require_once( "dondominio_helper.php" );



/**
 * Initialize the plugin.
 * @return DonDominioAPI
 */
function dondominio_init( $params )
{
	if(
		!array_key_exists( 'apiuser', $params )
		|| !array_key_exists( 'apipasswd', $params )
	){
		return false;
	}
	
	$options = array(
		'apiuser' => $params['apiuser'],
		'apipasswd' => $params['apipasswd'],
		'autoValidate' => false,
		'versionCheck' => true,
		'response' => array(
			'throwExceptions' => true
		),
		'userAgent' => array(
			'PluginForWHMCS' => dondominio_getVersion()
		)
	);
	
	$dondominio = new DonDominioAPI( $options );
	
	return $dondominio;
}

/**
 * Get plugin version.
 *
 * @return string
 */
function dondominio_getVersion()
{
	if( !file_exists( __DIR__ . '/version.json' )){
		return 'unknown';
	}
	
	$json = @file_get_contents( __DIR__ . '/version.json' );
	
	if( empty( $json )){
		return 'unknown';
	}
	
	$versionInfo = json_decode( $json, true );
	
	if( !is_array( $versionInfo ) || !array_key_exists( 'version', $versionInfo )){
		return 'unknown';
	}
	
	return $versionInfo['version'];
}

/**
 * Return the configuration for the plugin.
 * @return array
 */
function dondominio_getConfigArray()
{
	//Collecting custom fields
	$query = full_query("SELECT CF.id, CF.fieldname FROM tblcustomfields CF WHERE CF.type = 'client' ORDER BY CF.fieldname");
	
	$customfields = "";
	
	while( list( $cf_id, $cf_fieldname ) = mysql_fetch_row( $query )){
		$customfields .= ',' . $cf_fieldname;
	}
	
	$config = array(
		"FriendlyName" => array(
			"Type" => "System",
			"Value" => "DonDominio"
		),
		
		"Description" => array(
			"Type" => "System",
			"Value" => "Register domains with DonDominio! Signup at <a href='https://www.dondominio.com/register/'>https://www.dondominio.com/register/</a><br /><br /><strong>Your local server IP address is " . file_get_contents( 'http://ipv4.icanhazip.com' ) . "</strong>"
		),
			
		//API login details
		"apiuser" => array(
			"FriendlyName" => "API Username",
			"Type" => "text",
			"Size" => "25",
			"Description" => "Enter your API Username here"
		),
		"apipasswd" => array(
			"FriendlyName" => "API Password",
			"Type" => "password",
			"Size" => "25",
			"Description" =>"Enter your API Password here"
		),
		
		//VAT Number custom field
		"vat" => array(
			"FriendlyName" => "VAT Number Field",
			"Type" => "dropdown",
			"Options" => $customfields,
			"Description" => "Custom field containing the VAT Number for your customers"
		),
		
		//Owner Contact Override
		"ownerContact" => array(
			"FriendlyName" => "Owner Contact DonDominio ID",
			"Type" => "text",
			"Size" => "20",
			"Description" => "Override Owner contact information provided by customer"
		),
		"allowOwnerContactUpdate" => array(
			"FriendlyName" => " ",
			"Type" => "yesno",
			"Description" => "Allow customers to modify Owner contact information"
		),
		
		//Admin Contact Override
		"adminContact" => array(
			"FriendlyName" => "Admin Contact DonDominio ID",
			"Type" => "text",
			"Size" => "20",
			"Description" => "Override Admin contact information provided by customer"
		),
		"allowAdminContactUpdate" => array(
			"FriendlyName" => " ",
			"Type" => "yesno",
			"Description" => "Allow customers to modify Admin contact information"
		),
		
		//Tech Contact Override
		"techContact" => array(
			"FriendlyName" => "Tech Contact DonDominio ID",
			"Type" => "text",
			"Size" => "20",
			"Description" => "Override Tech contact information provided by customer"
		),
		"allowTechContactUpdate" => array(
			"FriendlyName" => " ",
			"Type" => "yesno",
			"Description" => "Allow customers to modify Tech contact information"
		),
		
		//Billing Contact Override
		"billingContact" => array(
			"FriendlyName" => "Billing Contact DonDominio ID",
			"Type" => "text",
			"Size" => "20",
			"Description" => "Override Billing contact information provided by customer"
		),
		
		//Double-block
		"blockAll" => array(
			"FriendlyName" => "Lock modifications",
			"Type" => "yesno",
			"Description" => "Locking domain transfers also locks domain updates"
		)
	);
	
	return $config;
}

/**
 * Return the Nameservers.
 * @param array $params Parameters sent by WHMCS
 * @return array
 */
function dondominio_GetNameservers( $params )
{
	$dondominio = dondominio_init( $params );
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	try{
		$nameservers = $dondominio->domain_getNameServers( $sld . '.' . $tld );
	
		foreach( $nameservers->get( "nameservers" ) as $key=>$nameserver ){
			if( $key <= 5 ){
				$values["ns" . $nameserver["order"]] = $nameserver["name"];
			}
		}
		
		logModuleCall( 'dondominio', 'GetNameservers', $params, $nameservers->getRawResponse(), $nameservers->getResponseData()); 
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	return $values;
}

/**
 * Save changes made to the DNS Servers.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_SaveNameservers( $params )
{
	$dondominio = dondominio_init( $params );
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	$nameservers_array = array();
	
    if( array_key_exists( "ns1", $params )) $nameservers_array[] = $params["ns1"];
	if( array_key_exists( "ns2", $params )) $nameservers_array[] = $params["ns2"];
    if( array_key_exists( "ns3", $params )) $nameservers_array[] = $params["ns3"];
	if( array_key_exists( "ns4", $params )) $nameservers_array[] = $params["ns4"];
	if( array_key_exists( "ns5", $params )) $nameservers_array[] = $params["ns5"];
		
	try{
		$nameservers = $dondominio->domain_updateNameServers(
			$sld . '.' . $tld,
			$nameservers_array
		);
		
		logModuleCall( 'dondominio', 'SaveNameservers', $params, $nameservers->getRawResponse(), $nameservers->getResponseData()); 
	}catch(DonDominioAPI_Error $e){
		return array( 'error' => $e->getMessage());
	}
	
	return array( 'success' => true );
}

/**
 * Return the Locked status of a domain.
 * @param array $params Parameters passed by WHMCS
 * @return string
 */
function dondominio_GetRegistrarLock($params)
{
	$dondominio = dondominio_init($params);
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	$lockStatus = "unlocked";
	
	try{
		$status = $dondominio->domain_getInfo(
			$sld . '.' . $tld,
			array(
				'infoType'=>'status'
			)
		);
		
		if($status->get("transferBlock") || ($params['blockAll'] == 'on' && $status->get("modifyBlock"))){
			$lockStatus = "locked";
		}
		
		logModuleCall('dondominio', 'GetRegistrarLock', $params, $status->getRawResponse(), $status->getResponseData()); 
	}catch(DonDominioAPI_Error $e){
		return "unlocked";
	}
	
	return $lockStatus;
}

/**
 * Modify the Locked status of a domain.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_SaveRegistrarLock( $params )
{
	$dondominio = dondominio_init( $params );
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	$error = null;
	
	//Check if TLD allows domain transfer lock
	try{
		$locks = $dondominio->domain_getInfo(
			$sld . '.' . $tld,
			array(
				'infoType' => 'status'
			)
		);
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	$data = $locks->getResponseData();
	
	$lockable = array_key_exists("transferBlock", $data);
	
	if($params["lockenabled"] == 'locked'){
		if($lockable){
			try{
				$lock = $dondominio->domain_update(
					$sld . '.' . $tld,
					array(
						'updateType' => 'transferBlock',
						'transferBlock' => true
					)
				);
				
				logModuleCall('dondominio', 'SaveRegistrarLockTransfer', $params, $lock->getRawResponse(), $lock->getResponseData()); 
			}catch(DonDominioAPI_Error $e){
				$error = array('error' => $e->getMessage());
			}
		}
		
		if($params['blockAll'] == 'on'){
			try{
				$lock = $dondominio->domain_update(
					$sld . '.' . $tld,
					array(
						'updateType' => 'block',
						'block' => true
					)
				);
				
				logModuleCall('dondominio', 'SaveRegistrarLockUpdate', $params, $lock->getRawResponse(), $lock->getResponseData()); 
			}catch(DonDominioAPI_Error $e){
				if(is_array($error)){
					$error['error'] .= "; " . $e->getMessage();
				}else{
					$error = array('error' => $e->getMessage());
				}
			}
		}
	}else{
		if($params['blockAll'] == 'on'){
			try{
				$lock = $dondominio->domain_update(
					$sld . '.' . $tld,
					array(
						'updateType' => 'block',
						'block' => false
					)
				);
				
				logModuleCall('dondominio', 'SaveRegistrarLockUpdate', $params, $lock->getRawResponse(), $lock->getResponseData()); 
			}catch(DonDominioAPI_Error $e){
				$error = array('error' => $e->getMessage());
			}
		}
		
		if($lockable){
			try{
				$lock = $dondominio->domain_update(
					$sld . '.' . $tld,
					array(
						'updateType' => 'transferBlock',
						'transferBlock' => false
					)
				);
				
				logModuleCall('dondominio', 'SaveRegistrarLockTransfer', $params, $lock->getRawResponse(), $lock->getResponseData()); 
			}catch(DonDominioAPI_Error $e){
				if(is_array($error)){
					$error['error'] .= "; " . $e->getMessage();
				}else{
					$error = array('error' => $e->getMessage());
				}
			}
		}
	}
	
	if(!$lockable){
		return array('error' => 'This domain extension does not allow domain transfer locking.');
	}
	
	if(is_array($error)){
		return $error;
	}
	
	return array('success' => true);
}

/**
 * Register a new domain.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_RegisterDomain($params)
{
	$dondominio = dondominio_init($params);
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
		
	$nameservers_array = array();
	
	if(array_key_exists("ns1", $params)) $nameservers_array[] = $params["ns1"];
	if(array_key_exists("ns2", $params)) $nameservers_array[] = $params["ns2"];
    if(array_key_exists("ns3", $params)) $nameservers_array[] = $params["ns3"];
	if(array_key_exists("ns4", $params)) $nameservers_array[] = $params["ns4"];
	if(array_key_exists("ns5", $params)) $nameservers_array[] = $params["ns5"];
    	
	try{
		$check = $dondominio->domain_check($sld . '.' . $tld);
		
		$domains = $check->get('domains');
		
		if(!$domains[0]['available']){
				return array('error'=>'Domain already taken');
		}
		
		$fields = array(
			'period' => intval($params['regperiod']),
			'nameservers' => implode(',', $nameservers_array),
		);
		
		//Contact data
		$fields = array_merge($fields, dondominio_buildContactData($params));
		
		switch('.' . $tld){
		case '.aero':
			$fields['aeroId'] = $params['additionalfields']['ID'];
			$fields['aeroPass'] = $params['additionalfields']['Password'];
			break;
		case '.cat':
		case '.pl':
		case '.scot':
		case '.eus':
		case '.gal':
		case '.quebec':
			$fields['domainIntendedUse'] = $params['additionalfields']['Intended Use'];
			break;
		case '.fr':
			$fields['ownerDateOfBirth'] = $params['additionalfields']['Birthdate'];
			$fields['frTradeMark'] = $params['additionalfields'][''];
			$fields['frSirenNumber'] = $params['additionalfields'][''];
			break;
		case '.hk':
			$fields['ownerDateOfBirth'] = $params['additionalfields']['Birthdate'];
			break;
		case '.jobs':
			$fields['jobsOwnerWebsite'] = $params['additionalfields']['Owner Website'];
			$fields['jobsAdminWebsite'] = $params['additionalfields']['Admin Contact Website'];
			$fields['jobsContactWebsite'] = $params['additionalfields']['Tech Contact Website'];
			$fields['jobsBillingWebsite'] = $params['additionalfields']['Billing Contact Website'];
			break;
		case '.lawyer':
		case '.attorney':
		case '.dentist':
		case '.airforce':
		case '.army':
		case '.navy':
			$fields['coreContactInfo'] = $params['additionalfields']['Contact Info'];
			break;
		case '.ltda':
			$fields['ltdaAuthority'] = $params['additionalfields']['Authority'];
			$fields['ltdaLicenseNumber'] = $params['additionalfields']['License Number'];
			break;
		case '.pro':
			$fields['proProfession'] = $params['additionalfields']['Profession'];
			break;
		case '.ru':
			$fields['ownerDateOfBirth'] = $params['additionalfields']['Birthdate'];
			$fields['ruIssuer'] = $params['additionalfields']['Issuer'];
			$fields['ruIssuerDate'] = $params['additionalfields']['Issue Date'];
			break;
		case '.travel':
			$fields['travelUIN'] = $params['additionalfields']['UIN'];
			break;
		case '.xxx':
			$fields['xxxClass'] = $params['additionalfields']['Class'];
			$fields['xxxName'] = $params['additionalfields']['Name'];
			$fields['xxxEmail'] = $params['additionalfields']['Email'];
			$fields['xxxId'] = $params['additionalfields']['Member Id'];
			break;
		case '.law':
		case '.abogado':
			$fields['lawaccid'] = $params['additionalfields']['Accreditation ID'];
			$fields['lawaccbody'] = $params['additionalfields']['Accreditation Body'];
			$fields['lawaccyear'] = $params['additionalfields']['Accreditation Year'];
			$fields['lawaccjurcc'] = $params['additionalfields']['Country'];
			$fields['lawaccjurst'] = $params['additionalfields']['State/Province'];
			break;
		}
				
		$create = $dondominio->domain_create(
			$sld . '.' . $tld,
			$fields
		);
		
		logModuleCall('dondominio', 'RegisterDomain', $params, $create->getRawResponse(), $create->getResponseData());
	}catch(DonDominioAPI_InsufficientBalance $e){		
		return array('error' => 'Error registering domain. Please, try again later.');
	}catch(DonDominioAPI_Error $e){		
		return array('error' => $e->getMessage());
	}
	
	return array('success' => true);
}

/**
 * Transfer a domain.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_TransferDomain($params)
{
	$dondominio = dondominio_init($params);
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	$nameservers_array = array();
	
	if(array_key_exists("ns1", $params)) $nameservers_array[] = $params["ns1"];
	if(array_key_exists("ns2", $params)) $nameservers_array[] = $params["ns2"];
    if(array_key_exists("ns3", $params)) $nameservers_array[] = $params["ns3"];
	if(array_key_exists("ns4", $params)) $nameservers_array[] = $params["ns4"];
	if(array_key_exists("ns5", $params)) $nameservers_array[] = $params["ns5"];
	
	$fields = array(
		'nameservers' => implode(',', $nameservers_array),
		'authcode' => $params['transfersecret']
	);
	
	//Contact data
	$fields = array_merge($fields, dondominio_buildContactData($params));
	
	//Additional domain fields
	switch('.' . $tld){
	case '.aero':
		$fields['aeroId'] = $params['additionalfields']['ID'];
		$fields['aeroPass'] = $params['additionalfields']['Password'];
		break;
	case '.cat':
	case '.pl':
	case '.scot':
	case '.eus':
	case '.gal':
	case '.quebec':
		$fields['domainIntendedUse'] = $params['additionalfields']['Intended Use'];
		break;
	case '.fr':
		$fields['ownerDateOfBirth'] = $params['additionalfields']['Birthdate'];
		$fields['frTradeMark'] = $params['additionalfields'][''];
		$fields['frSirenNumber'] = $params['additionalfields'][''];
		break;
	case '.hk':
		$fields['ownerDateOfBirth'] = $params['additionalfields']['Birthdate'];
		break;
	case '.jobs':
		$fields['jobsOwnerWebsite'] = $params['additionalfields']['Owner Website'];
		$fields['jobsAdminWebsite'] = $params['additionalfields']['Admin Contact Website'];
		$fields['jobsContactWebsite'] = $params['additionalfields']['Tech Contact Website'];
		$fields['jobsBillingWebsite'] = $params['additionalfields']['Billing Contact Website'];
		break;
	case '.lawyer':
	case '.attorney':
	case '.dentist':
	case '.airforce':
	case '.army':
	case '.navy':
		$fields['coreContactInfo'] = $params['additionalfields']['Contact Info'];
		break;
	case '.ltda':
		$fields['ltdaAuthority'] = $params['additionalfields']['Authority'];
		$fields['ltdaLicenseNumber'] = $params['additionalfields']['License Number'];
		break;
	case '.pro':
		$fields['proProfession'] = $params['additionalfields']['Profession'];
		break;
	case '.ru':
		$fields['ownerDateOfBirth'] = $params['additionalfields']['Birthdate'];
		$fields['ruIssuer'] = $params['additionalfields']['Issuer'];
		$fields['ruIssuerDate'] = $params['additionalfields']['Issue Date'];
		break;
	case '.travel':
		$fields['travelUIN'] = $params['additionalfields']['UIN'];
		break;
	case '.xxx':
		$fields['xxxClass'] = $params['additionalfields']['Class'];
		$fields['xxxName'] = $params['additionalfields']['Name'];
		$fields['xxxEmail'] = $params['additionalfields']['Email'];
		$fields['xxxId'] = $params['additionalfields']['Member Id'];
		break;
	case '.law':
	case '.abogado':
		$fields['lawaccid'] = $params['additionalfields']['Accreditation ID'];
		$fields['lawaccbody'] = $params['additionalfields']['Accreditation Body'];
		$fields['lawaccyear'] = $params['additionalfields']['Accreditation Year'];
		$fields['lawaccjurcc'] = $params['additionalfields']['Country'];
		$fields['lawaccjurst'] = $params['additionalfields']['State/Province'];
		break;
	}
	
	try{		
		$transfer = $dondominio->domain_transfer(
			$sld . '.' . $tld,
			$fields
		);
		
		logModuleCall('dondominio', 'TransferDomain', $params, $transfer->getRawResponse(), $transfer->getResponseData()); 
	}catch(DonDominioAPI_InsufficientBalance $e){		
		return array('error' => 'Error transfering domain. Please, try again later.');
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	return array('success' => true);
}

/**
 * Renew a domain.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_RenewDomain($params)
{
	$dondominio = dondominio_init($params);
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	$regperiod = (int) $params["regperiod"];
	
	try{
		$status = $dondominio->domain_getInfo(
			$sld . '.' . $tld,
			array(
				'infoType' => 'status'
			)
		);
		
		$renew = $dondominio->domain_renew(
			$sld . '.' . $tld,
			array(
				'curExpDate' => $status->get('tsExpir'),
				'period' => $regperiod
			)
		);
		
		logModuleCall('dondominio', 'RenewDomain', $params, $renew->getRawResponse(), $renew->getResponseData()); 
	}catch(DonDominioAPI_InsufficientBalance $e){		
		return array('error' => 'Error renewing domain. Please, try again later.');
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	return array('success' => true);
}

/**
 * Get contact details from a domain.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_GetContactDetails($params)
{	
	$dondominio = dondominio_init($params);
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	$result = array();
	
	try{
		$contact = $dondominio->domain_getInfo(
			$sld . '.' . $tld,
			array(
				'infoType' => 'contact'
			)
		);
				
		if(empty($params['ownerContact']) || $params['allowOwnerContactUpdate'] === 'on') populateContact($contact->get('contactOwner'), 'Registrant', $result);
		if(empty($params['adminContact']) || $params['allowAdminContactUpdate'] === 'on') populateContact($contact->get('contactAdmin'), 'Admin', $result);
		if(empty($params['techContact']) || $params['allowTechContactUpdate'] === 'on') populateContact($contact->get('contactTech'), 'Tech', $result);
		
		logModuleCall('dondominio', 'GetContactDetails', $params, $contact->getRawResponse(), $contact->getResponseData());
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	//Contact modification is disabled per module settings
	if(count($result) == 0){
		return array('error' => 'Contact modification is disabled. Contact support for more information.');
	}
	
	return $result;
}

/**
 * Save contact details for a domain.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_SaveContactDetails($params)
{	
	$dondominio = dondominio_init($params);
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	$result = array();
	
	/*
	 * Owner Contact.
	 */
	if(
		(
			empty($params['ownerContact']) ||
			$params['allowOwnerContactUpdate'] == 'on'
		)
		&& !empty($params['contactdetails']['Registrant']['First Name'])
		&& substr($tld, -2, 2) != 'es'
	){
		flattenContact('owner', $params['contactdetails']['Registrant'], $result);
		
		if(!$result['ownerContactIdentNumber']){
			$result['ownerContactIdentNumber'] = getVAT($params['vat'], $result['ownerContactEmail']);
		}
		
		if($result['ownerContactIdentNumber'] && $result['ownerContactCountry'] == 'ES'){
			if(
				is_numeric(substr($result['ownerContactIdentNumber'], 0, 1))
				|| (
					!is_numeric(substr($result['ownerContactIdentNumber'], 0, 1)) && 
					!is_numeric(substr($result['ownerContactIdentNumber'], -1, 1))
				)
			){
				$result['ownerContactType'] = 'individual';
			}else{
				$result['ownerContactType'] = 'organization';
				$result['ownerContactOrgType'] = mapOrgType($result['ownerContactIdentNumber']);
			}
		}
	}elseif(!empty($params['ownerContact']) && substr($tld, -2, 2) != 'es'){
		$result['ownerContactID'] = $params['ownerContact'];
	}
	
	/*
	 * Admin Contact.
	 */
	if(
		(
			empty($params['adminContact']) ||
			$params['allowAdminContactUpdate'] == 'on'
		)
		&& !empty($params['contactdetails']['Admin']['First Name'])
	){
	 	flattenContact('admin', $params['contactdetails']['Admin'], $result);
		
		if(!$result['adminContactIdentNumber']){
			$result['adminContactIdentNumber'] = getVAT($params['vat'], $result['adminContactEmail']);
		}
		
		if($result['adminContactIdentNumber'] && $result['adminContactCountry'] == 'ES'){
			if(
				is_numeric(substr($result['adminContactIdentNumber'], 0, 1))
				|| (
					!is_numeric(substr($result['adminContactIdentNumber'], 0, 1)) && 
					!is_numeric(substr($result['adminContactIdentNumber'], -1, 1))
				)
			){
				$result['adminContactType'] = 'individual';
			}else{
				$result['adminContactType'] = 'organization';
				$result['adminContactOrgType'] = mapOrgType($result['adminContactIdentNumber']);
			}
		}
	}elseif(!empty($params['adminContact'])){
		$result['adminContactID'] = $params['adminContact'];
	}
	
	/*
	 * Tech Contact.
	 */
	if(
		(
			empty($params['techContact']) ||
			$params['allowTechContactUpdate'] == 'on'
		)
		&& !empty($params['contactdetails']['Tech']['First Name'])
	){
		flattenContact('tech', $params['contactdetails']['Tech'], $result);
		
		if(!$result['techContactIdentNumber']){
			$result['techContactIdentNumber'] = getVAT($params['vat'], $result['techContactEmail']);
		}
		
		if($result['techContactIdentNumber'] && $result['techContactCountry'] == 'ES'){
			if(
				is_numeric(substr($result['techContactIdentNumber'], 0, 1))
				|| (
					!is_numeric(substr($result['techContactIdentNumber'], 0, 1)) && 
					!is_numeric(substr($result['techContactIdentNumber'], -1, 1))
				)
			){
				$result['techContactType'] = 'individual';
			}else{
				$result['techContactType'] = 'organization';
				$result['techContactOrgType'] = mapOrgType($result['techContactIdentNumber']);
			}
		}
	}elseif(!empty($params['techContact'])){
		$result['techContactID'] = $params['techContact'];
	}
	
	if(!empty($params['billingContact'])){
		$result['billingContactID'] = $params['billingContact'];
	}
	
	//Updating information
	try{
		$contacts = $dondominio->domain_updateContacts(
			$sld . '.' . $tld,
			$result
		);
		
		logModuleCall('dondominio', 'SaveContactDetails', $params, $contacts->getRawResponse(), $contacts->getResponseData());
	}catch(DonDominioAPI_Domain_UpdateNotAllowed $e){
		return array('error' => 'Domain Update is not allowed: ' . $e->getMessage());
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
		
	return array('success' => true);
}

/**
 * Get the AuthCode of a domain.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_GetEPPCode( $params )
{
    $dondominio = dondominio_init( $params );
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	try{
		$authCode = $dondominio->domain_getAuthCode( $sld . '.' . $tld );
		
		logModuleCall('dondominio', 'GetEPPCode', $params, $authCode->getRawResponse(), $authCode->getResponseData()); 
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	return array( 'eppcode' => $authCode->get( 'authcode' ));
}

/**
 * Add a Custom DNS Server (Gluerecord) to a domain
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_RegisterNameserver( $params )
{
    $dondominio = dondominio_init( $params );
    	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
		
	try{
		$gluerecord = $dondominio->domain_glueRecordCreate(
			$sld . '.' . $tld,
			array(
				'name' => $params['nameserver'],
				'ipv4' => $params['ipaddress']
			)
		);
		
		logModuleCall( 'dondominio', 'RegisterNameserver', $params, $gluerecord->getRawResponse(), $gluerecord->getResponseData()); 
	}catch(DonDominioAPI_Error $e){
		return array( 'error' => $e->getMessage());
	}
	
	return array( 'success' => true );
}

/**
 * Modify an existing Custom DNS Server (Gluerecord) from a domain.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */ 
function dondominio_ModifyNameserver( $params )
{
    $dondominio = dondominio_init( $params );
    	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
		
	try{
		$gluerecord = $dondominio->domain_glueRecordUpdate(
			$sld . '.' . $tld,
			array(
				'name' => $params['nameserver'],
				'ipv4' => $params['newipaddress']
			)
		);
		
		logModuleCall( 'dondominio', 'ModifyNameserver', $params, $gluerecord->getRawResponse(), $gluerecord->getResponseData());
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	return array('success' => true);
}

/**
 * Remove a Custom DNS Server (glurecord) to a domain
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_DeleteNameserver( $params )
{
    $dondominio = dondominio_init( $params );
    	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
		
	try{
		$gluerecord = $dondominio->domain_glueRecordDelete(
			$sld . '.' . $tld,
			array(
				'name' => $params['nameserver']
			)
		);
		
		logModuleCall('dondominio', 'DeleteNameserver', $params, $gluerecord->getRawResponse(), $gluerecord->getResponseData()); 
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	return array('success' => true);
}

/**
 * Toggle the ID protection for a domain.
 * @param array $params Parameters from WHMCS
 * @return array
 */
function dondominio_IDProtectToggle($params)
{
	$dondominio = dondominio_init($params);
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	//Checking for blocked status
	try{
		$block = $dondominio->domain_getInfo(
			$sld . '.' . $tld,
			array(
				'infoType' => 'status'
			)
		);
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	$blockData = $block->getResponseData();
	
	if(array_key_exists('modifyBlock', $blockData) && $block->get("modifyBlock") == true){
		return array('error' => 'Domain has the modification lock enabled. Unlock modifications to proceed.');
	}
	
	try{
		$anonymous = $dondominio->domain_update(
			$sld . '.' . $tld,
			array(
				'updateType' => 'whoisPrivacy',
				'whoisPrivacy' => ($params['protectenable']) ? true : false
			)
		);
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	return array('success' => true);
}

/**
 * Display the "whoisPrivacy" screen for a domain.
 * @param array $params Parameters from WHMCS
 * @return array
 */
function dondominio_whoisPrivacy($params)
{
	$dondominio = dondominio_init($params);
	
	$domainid = $params['domainid'];
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	$error = '';
	
	if($_POST['ok'] == 'ok'){
		try{
			$enable = $dondominio->domain_update(
				$sld . '.' . $tld,
				array(
					'updateType' => 'whoisPrivacy',
					'whoisPrivacy' => ($_POST['privacy'] == 'on') ? true : false
				)
			);
		}catch(DonDominioAPI_Error $e){
			$error = $e->getMessage();
		}
	}
	
	try{
		$status = $dondominio->domain_getInfo(
			$sld . '.' . $tld,
			array(
				'infoType' => 'status'
			)
		);
	}catch(DonDominioAPI_Error $e){
		$error = $e->getMessage();
	}
	
	if(!empty($error)){
		return array(
			'templatefile' => 'whoisprivacy',
			'breadcrumb' => array( 'clientarea.php?action=domaindetails&domainid='.$domainid.'&modop=custom&a=whoisPrivacy' => 'WHOIS Privacy' ),
			'vars' => array(
				'error' => $error
			)
		);
	}
	
	return array(
		'templatefile' => 'whoisprivacy',
		'breadcrumb' => array( 'clientarea.php?action=domaindetails&domainid='.$domainid.'&modop=custom&a=whoisPrivacy' => 'WHOIS Privacy' ),
		'vars' => array(
			'error' => $error,
			'status' => $status->get("whoisPrivacy")
		),
	);
}

/**
 * Buttons for the client area for custom functions.
 * @return array
 */
function dondominio_ClientAreaCustomButtonArray()
{
	$buttonarray = array(
		"WHOIS Privacy" => "whoisPrivacy"
	);
	
	return $buttonarray;
}

add_hook('ClientAreaSidebars', 1, function(){
	$primarySidebar = Menu::primarySidebar();
	
	if(!is_null($domainMenu = $primarySidebar->getChild('Domain Details Management'))){
		$domain = Menu::context('domain');
		
		$newMenu = $primarySidebar->addChild(
			'Additional services',
			array(
				'label' => Lang::Trans('dondominioAdditionalServices'),
			)
		);
		
		$newMenu->moveDown();
		
		$newMenu->addChild(
			'Whois Privacy',
			array(
				'name' => 'Home',
				'label' => Lang::Trans('dondominioWhoisPrivacy'),
				'uri' => 'clientarea.php?action=domaindetails&domainid=' . $domain->Id . '&modop=custom&a=whoisPrivacy',
				'current' => ($_GET['a'] == 'whoisPrivacy') ? true : false
			)
		);
	}
	
	if($_GET['modop'] == 'custom'){
		$primarySidebar->removeChild('Domain Details Management');
	}
});

/*
*******************************************************************************
* WHMCS SYNC																  *
*******************************************************************************
*/

/**
 * Sync domain status.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_Sync($params)
{
	$dondominio = dondominio_init($params);
	
	$values = array();
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	try{
		$info = $dondominio->domain_getInfo(
			$sld . '.' . $tld,
			array(
				'infoType' => 'status'
			)
		);
		 
		//Checking if the domain is active
		$values['active'] = (
			in_array(
				$info->get('status'),
				array(
					'active',
					'renewed',
					'expired-renewgrace',
					'expired-redemption',
					'expired-pendingdelete'
				)
			)
		) ? true : false;
		
		//Checking if the domain has expired
		$values['expired'] = (
			in_array(
				$info->get('status'),
				array(
					'expired-renewgrace',
					'expired-redemption',
					'expired-pendingdelete'
				)
			)
		) ? true : false;
		
		//Adding the expiry date to the information
		$expir = $info->get('tsExpir');
		
		if(!empty($expir)){
			$values['expirydate'] = $expir;
		}
		
		logModuleCall('dondominio', 'Sync', $params, $info->getRawResponse(), $info->getResponseData());
		
		//IDProtection Sync
		$protection = ($params['idprotection']) ? true : false;
		
		if($protection != $info->get("whoisPrivacy") && $values['active'] == true && $values['expired'] == false){
			//Updating IDProtection
			try{
				$whois = $dondominio->domain_update(
					$sld . '.' . $tld,
					array(
						'updateType' => 'whoisPrivacy',
						'whoisPrivacy' => $protection
					)
				);
				
				logModuleCall('dondominio', 'IDProtectionSync', $params, $whois->getRawResponse(), $whois->getResponseData());
			}catch(DonDominioAPI_Error $e){
				//return array('error' => 'Error syncing IDProtection status: ' . $e->getMessage());
			}
		}

		$dondominio->close();
		$dondominio = null;
	}catch(DonDominioAPI_Domain_NotFound $e){
		$values['active'] = false;
		$values['expired'] = true;
	}catch(DonDominioAPI_Error $e){
		return array('error' => 'Error syncing domain status: ' . $e->getMessage());
	}
	
	return $values;
}

/**
 * Sync status for domains pending transfer.
 * @param array $params Parameters passed by WHMCS
 * @return array
 */
function dondominio_TransferSync($params)
{
	$dondominio = dondominio_init($params);
	
	if( array_key_exists( 'original', $params )){
		$params["sld"] = $params["original"]["sld"];
		$params["tld"] = $params["original"]["tld"];
	}
	
	$sld = $params["sld"];
	$tld = $params["tld"];
	
	$values = array();
	
	try{
		$info = $dondominio->domain_getInfo(
			$sld . '.' . $tld,
			array(
				'infoType' => 'status'
			)
		);
		
		$status = $info->get('status');
		
		$values['completed'] = false;
		$values['failed'] = false;
		
		if($status == 'active' || $status == 'renewed'){
			$values['completed'] = true;
			$values['expirydate'] = $info->get('tsExpir');
		}elseif($status == 'transfer-cancel'){
			$values['failed'] = true;
		}
		
		$dondominio->close();
		$dondominio = null;

		logModuleCall('dondominio', 'TransferSync', $params, $info->getRawResponse(), $info->getResponseData());
	}catch(DonDominioAPI_Error $e){
		return array('error' => $e->getMessage());
	}
	
	return $values;
}

?>