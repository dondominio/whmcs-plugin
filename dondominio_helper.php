<?php

/**
 * DonDominio API Helper
 * Functions used in dondominio.php to perform some tasks.
 * API version 0.9.x
 * WHMCS version 5.2.x / 5.3.x
 * @link https://github.com/dondominio/dondominiowhmcs
 * @package DonDominioWHMCS
 * @license CC BY-ND 3.0 <http://creativecommons.org/licenses/by-nd/3.0/>
 */

/**
 * Map VAT number to the corresponding additional domain field.
 * Returns the VAT number from the additional parameters passed by
 * the register domain call made in WHMCS.
 * @param string $tld TLD
 * @param array $params Parameters passed by WHMCS
 * @return string
 */
function dondominio_MapVAT($tld, $params)
{
	$vatNumber = '';
	
	if(substr($tld, 0, 1) == '.') $tld = substr($tld, 1);
	
	if( !empty( $params['additionalfields']['VAT Number'] )){
		return $params['additionalfields']['VAT Number'];
	}
	
	switch($tld){
	case 'co.uk':
	case 'net.uk':
	case 'org.uk':
	case 'me.uk':
	case 'plc.uk':
	case 'ltd.uk':
	case 'uk':
		$vatNumber = $params['additionalfields']['Company ID Number'];
		break;
		
	case 'es':
		$vatNumber = $params['additionalfields']['ID Form Number'];
		break;
		
	case 'sg':
	case 'com.sg':
	case 'edu.sg':
	case 'net.sg':
	case 'org.sg':
	case 'per.sg':
		$vatNumber = $params['additionalfields']['RCB Singapore ID'];
		break;
		
	case 'it':
	case 'de':
		$vatNumber = $params['additionalfields']['Tax ID'];
		break;
		
	case 'com.au':
	case 'net.au':
	case 'org.au':
	case 'asn.au':
	case 'id.au':
		$vatNumber = $params['additionalfields']['Registrant ID'];
		break;
		
	case 'asia':
		$vatNumber = $params['additionalfields']['Identity Number'];
		break;
		
	case 'fr':
		$vatNumber = $params['additionalfields']['VAT Number'];
		break;
	default:
		$vatNumber = $params['additionalfields']['VAT Number'];
		break;
	}
	
	if(empty($vatNumber)) {
		$varNumber = $params['additionalfields']['VAT Number'];
	}
	
	//Grab the custom field, if it exists
	if(empty($vatNumber) && !empty($params['vat'])){
		$query = full_query("SELECT CF.id FROM tblcustomfields CF WHERE CF.fieldname = '" . $params['vat'] . "'");
		
		if(mysql_num_rows($query) == 1){
			list($fieldID) = mysql_fetch_row($query);
			
			$fieldID = $fieldID - 1;
			
			$vatNumber = $params['customfields'][$fieldID]['value'];			
		}
	}
	
	return $vatNumber;
}

/**
 * Get the VAT number field from a customer using an email.
 * @param string $field VAT Number field name
 * @param string $email Customer's email
 * @return string VAT Number field value
 */
function getVAT($field, $email)
{
	$sql = "
		SELECT
			value
		FROM tblcustomfieldsvalues
		WHERE
			fieldid = (SELECT id FROM tblcustomfields WHERE fieldname = '" . $field . "')
			AND relid = (SELECT id FROM tblclients WHERE email = '" . $email . "')
	";
	
	$query = full_query($sql);
	
	if(mysql_num_rows($query) != 1){
		return false;
	}
	
	list($value) = mysql_fetch_row($query);
	
	return $value;
}

/**
 * Build an array with contact data to be sent to the API.
 * @param array $params Parameters containing the contact data
 * @return array
 */
function dondominio_buildContactData($params)
{
	$fields = array();
	
	$nif_letra = substr(dondominio_MapVAT($params['tld'], $params), 0, 1);
	
	$ownerContactType = 'individual';
	
	if( $nif_letra != 'X' && $nif_letra != 'Y' && $nif_letra != 'Z' && !is_numeric( $nif_letra )){
		$ownerContactType = 'organization';
	}
			
	//Owner Contact
	if(empty($params['ownerContact'])){
		$fields = array_merge(
			$fields,
			array(
				//Owner Contact
				'ownerContactType' => $ownerContactType,
				'ownerContactFirstName' => $params['firstname'],
				'ownerContactLastName' => $params['lastname'],
				'ownerContactIdentNumber' => dondominio_MapVAT($params['tld'], $params),
				'ownerContactOrgName' => $params['companyname'],
				'ownerContactOrgType' => mapOrgType(dondominio_MapVAT($params['tld'], $params)),
				'ownerContactEmail' => $params['email'],
				'ownerContactPhone' => $params['fullphonenumber'],
				'ownerContactAddress' => $params['address1'] . "\r\n" . $params['address2'],
				'ownerContactPostalCode' => $params['postcode'],
				'ownerContactCity' => $params['city'],
				'ownerContactState' => $params['state'],
				'ownerContactCountry' => $params['country']
			)
		);
	}else{
		$fields['ownerContactID'] = $params['ownerContact'];
	}
	
	//Admin Contact
	if(empty($params['adminContact'])){
		if(array_key_exists('Administrative Document Number', $params['additionalfields'])){
			$adminContactType = 'individual';
			$adminContactIdentNumber = $params['additionalfields']['Administrative Document Number'];
			$adminContactOrgType = '';
		}else{
			$adminContactType = $ownerContactType;
			$adminContactIdentNumber = dondominio_MapVAT($params['tld'], $params);
			$adminContactOrgType = mapOrgType(dondominio_MapVAT($params['tld'], $params));
		}
		
		$fields = array_merge(
			$fields,
			array(
				//Admin Contact
				'adminContactType' => $adminContactType,
				'adminContactFirstName' => $params['adminfirstname'],
				'adminContactLastName' => $params['adminlastname'],
				'adminContactIdentNumber' => $adminContactIdentNumber, 
				'adminContactOrgName' => $params['companyname'],
				'adminContactOrgType' => $adminContactOrgType,
				'adminContactEmail' => $params['adminemail'],
				'adminContactPhone' => $params['adminfullphonenumber'],
				'adminContactAddress' => $params['adminaddress1'] . "\r\n" . $params['adminaddress2'],
				'adminContactPostalCode' => $params['adminpostcode'],
				'adminContactCity' => $params['admincity'],
				'adminContactState' => $params['adminstate'],
				'adminContactCountry' => $params['admincountry']
			)
		);
	}else{
		$fields['adminContactID'] = $params['adminContact'];
	}
	
	//Tech Contact
	if(!empty($params['techContact'])){
		$fields['techContactID'] = $params['techContact'];
	}
	
	//Billing Contact
	if(!empty($params['billingContact'])){
		$fields['billingContactID'] = $params['billingContact'];
	}
	
	return $fields;
}

/**
 * Convert organization type to the corresponding code for the API using a VAT Number.
 * @param string $vat VAT Number used to get the code
 * @return string
 */
function mapOrgType($vat)
{
	$letter = substr($vat, 0, 1);
	
	if(is_numeric($letter)){
		return "1";
	}
	
	switch($letter){
	case 'A':
		return "524";
		break;
	case 'B':
		return "612";
		break;
	case 'C':
		return "560";
		break;
	case 'D':
		return "562";
		break;
	case 'E':
		return "150";
		break;
	case 'F':
		return "566";
		break;
	case 'G':
		return "47";
		break;
	case 'J':
		return "554";
		break;
	case 'P':
		return "747";
		break;
	case 'Q':
		return "746";
		break;
	case 'R':
		return "164";
		break;
	case 'S':
		return "436";
		break;
	case 'U':
		return "717";
		break;
	case 'V':
		return "877";
		break;
	case 'N':
	case 'W':
		return "713";
		break;
	case 'X':
	case 'Y':
	case 'Z':
		return "1";
	}
	
	return "877";
}

/**
 * Convert an array containing contact data to the array structure used by the API.
 * @param string $type Contact type
 * @param array $contact Contact data
 * @param array $result The resulting array, passed by reference
 */
function flattenContact($type, $contact, &$result)
{
	$pcode = "";
	
	if(array_key_exists('Zip Code', $contact)){
		$pcode = $contact['Zip Code'];
	}elseif(array_key_exists('Postcode', $contact)){
		$pcode = $contact['Postcode'];
	}elseif(array_key_exists('ZIP Code', $contact)){
		$pcode = $contact['ZIP Code'];
	}
	
	$result[$type . 'ContactType'] = 'individual';
	$result[$type . 'ContactFirstName'] = $contact['First Name'];
	$result[$type . 'ContactLastName'] = $contact['Last Name'];
	$result[$type . 'ContactOrgName'] = $contact['Company Name'];
	$result[$type . 'ContactIdentNumber'] = $contact['VAT Number'];
	$result[$type . 'ContactEmail'] = $contact['Email Address'];
	$result[$type . 'ContactAddress'] = $contact['Address'];
	$result[$type . 'ContactPostalCode'] = $pcode;
	$result[$type . 'ContactCity'] = $contact['City'];
	$result[$type . 'ContactState'] = $contact['State'];
	$result[$type . 'ContactCountry'] = $contact['Country'];
	$result[$type . 'ContactPhone'] = $contact['Phone Number'];
}

/**
 * Build an array suitable for WHMCS with contact data obtained from the API.
 * @param array $contact Contact data
 * @param string $type Contact type
 * @param array $result Resulting array, passed by reference
 */
function populateContact(array $contact, $type, &$result)
{
	$result[$type]['First Name'] = $contact['firstName'];
	$result[$type]['Last Name'] = $contact['lastName'];
	$result[$type]['Company Name'] = $contact['orgName'];
	$result[$type]['Email Address'] = $contact['email'];
	$result[$type]['Address'] = $contact['address'];
	$result[$type]['City'] = $contact['city'];
	$result[$type]['State'] = $contact['state'];
	$result[$type]['Zip Code'] = $contact['postalCode'];
	$result[$type]['Country'] = $contact['country'];
	$result[$type]['Phone Number'] = $contact['phone'];
	$result[$type]['VAT Number'] = $contact['identNumber'];
}

?>
