<?php

/**
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !! WARNING																 !!
 * !! YOU SHOULD NOT MODIFY THIS FILE UNDER ANY CIRCUMSTANCES, UNLESS		 !!
 * !! INSTRUCTED SO BY THE DONDOMINIO/MRDOMAIN SUPPORT TEAM.				 !!
 * !!																		 !!
 * !! Making any changes to this file may cause the DonDominio WHMCS Module  !!
 * !! to stop working or register invalid domains/information.				 !!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * Automatically adding fields needed to register domains with DonDominio/MrDomain.
 *
 * Please include this file in /path/to/whmcs/includes/additionaldomainfields.php:
 *
 * <?php
 *
 * //...file contents
 *
 * include('../modules/registrars/dondominio/additionaldomainfields.php');
 *
 * ?>
 *
 * API version 0.9.x
 * WHMCS version 5.2.x / 5.3.x
 * @link https://github.com/dondominio/dondominiowhmcs
 * @package DonDominioWHMCS
 * @license CC BY-ND 3.0 <http://creativecommons.org/licenses/by-nd/3.0/>
 */

$result = full_query("
	SELECT
		DP.extension
	FROM tbldomainpricing DP
	WHERE
		DP.autoreg = 'dondominio'
");

while($data = mysql_fetch_array($result)){
	$tld = $data[0];
	
	$add = true;
	
	switch($tld){
	case '.uk':
	case '.co.uk':
	case '.net.uk':
	case '.org.uk':
	case '.me.uk':
	case '.plc.uk':
	case '.ltd.uk':
		$name = 'Company ID Number';
		break;
		
	case '.es':
		$name = 'ID Form Number';
		break;
		
	case '.sg':
	case '.com.sg':
	case '.edu.sg':
	case '.net.sg':
	case '.org.sg':
	case '.per.sg':
		$name = 'RCB Singapore ID';
		break;
		
	case '.it':
	case '.de':
		$name = 'Tax ID';
		break;
		
	case '.com.au':
	case '.net.au':
	case '.org.au':
	case '.asn.au':
	case '.id.au':
		$name = 'Registrant ID';
		break;
		
	case '.asia':
		$name = 'Identity Number';
		break;
		
	case '.fr':
		$name = 'VAT Number';
		break;
	}
	
	//Searching for already defined fields
	foreach($additionaldomainfields[$tld] as $field){
		if($field['Name'] == $name) $add = false;
	}
	
	//Adding any missing "VAT Number" fields to the Additional Domain Fields
	if($add){
		$additionaldomainfields[$tld][] = array('Name' => 'VAT Number', 'LangVar' => 'vat', 'Type' => 'text', 'Size' => 50, 'Default' => '', 'Required' => true);
	}
}

/*
 * Other fields.
 */
$additionaldomainfields['.es'][] 		= array('Name' => 'Administrative Document Number', 'LangVar' => 'es_admin_vat',	'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true);
 
if(array_key_exists(".es", $additionaldomainfields)){
	$additionaldomainfields[".com.es"] = $additionaldomainfields['.es'];
	$additionaldomainfields[".org.es"] = $additionaldomainfields['.es'];
	$additionaldomainfields[".nom.es"] = $additionaldomainfields['.es'];
	$additionaldomainfields[".gob.es"] = $additionaldomainfields['.es'];
	$additionaldomainfields[".edu.es"] = $additionaldomainfields['.es'];
}

// .AERO
$additionaldomainfields['.aero'][] 		= array('Name' => 'ID', 						'LangVar' => 'aero_id', 			'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.aero'][] 		= array('Name' => 'Password', 					'LangVar' => 'aero_pass', 			'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);

// .CAT, .PL, .SCOT, .EUS, .GAL, .QUEBEC
$additionaldomainfields['.cat'][] 		= array('Name' => 'Intended Use', 				'LangVar' => 'cat_intendeduse', 	'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.pl'][] 		= array('Name' => 'Intended Use', 				'LangVar' => 'pl_intendeduse', 		'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.scot'][] 		= array('Name' => 'Intended Use', 				'LangVar' => 'scot_intendeduse', 	'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.eus'][] 		= array('Name' => 'Intended Use', 				'LangVar' => 'eus_intendeduse', 	'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.gal'][]		= array('Name' => 'Intended Use', 				'LangVar' => 'gal_intendeduse', 	'Type' => 'text',	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.quebec'][]	= array('Name' => 'Intended Use',				'LangVar' => 'quebec_intendeduse',	'Type' => 'text',	'Size' => 50, 	'Default' => '', 	'Required' => true);

// .COOP
$additionaldomainfields['.coop'][]		= array('Name' => 'CVC',						'LangVar' => 'coop_cvc',			'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true);

// .HK
$additionaldomainfields['.hk'][]		= array('Name' => 'Birthdate',					'LangVar' => 'hk_birthdate',		'Type' => 'text',	'Size' => 16,	'Default' => '1900-01-01',	'Required' => true);


// .IT
$additionaldomainfields['.it'][]		= array('Name' => 'Birthdate',					'LangVar' => 'it_birthdate',		'Type' => 'text',	'Size' => 16,	'Default' => '1900-01-01',	'Required' => true);
$additionaldomainfields['.it'][]		= array('Name' => 'Birthplace',					'LangVar' => 'it_birthplace',		'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true);

// .JOBS
$additionaldomainfields['.jobs'][]		= array('Name' => 'Owner Website',				'LangVar' => 'jobs_ownerwebsite',	'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true);
$additionaldomainfields['.jobs'][]		= array('Name' => 'Admin Contact Website',		'LangVar' => 'jobs_adminwebsite',	'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true);
$additionaldomainfields['.jobs'][]		= array('Name' => 'Tech Contact Website',		'LangVar' => 'jobs_techwebsite', 	'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.jobs'][]		= array('Name' => 'Billing Contact Website', 	'LangVar' => 'jobs_billingwebsite', 'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);

// .LAWYER, .ATTORNEY, .DENTIST, .AIRFORCE, .ARMY, .NAVY
$additionaldomainfields['.lawyer'][]	= array('Name' => 'Contact Info', 				'LangVar' => 'lawyer_contactinfo', 	'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.attorney'][]	= array('Name' => 'Contact Info', 				'LangVar' => 'lawyer_contactinfo', 	'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.dentist'][]	= array('Name' => 'Contact Info', 				'LangVar' => 'lawyer_contactinfo', 	'Type' => 'text', 	'Size' => 50, 	'Default' => '',	'Required' => true);
$additionaldomainfields['.airforce'][]	= array('Name' => 'Contact Info', 				'LangVar' => 'lawyer_contactinfo', 	'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.army'][]		= array('Name' => 'Contact Info', 				'LangVar' => 'lawyer_contactinfo', 	'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);
$additionaldomainfields['.navy'][]		= array('Name' => 'Contact Info', 				'LangVar' => 'lawyer_contactinfo', 	'Type' => 'text', 	'Size' => 50, 	'Default' => '', 	'Required' => true);

// .LTDA
$additionaldomainfields['.ltda'][]		= array('Name' => 'Authority',					'LangVar' => 'ltda_authority',		'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => false);
$additionaldomainfields['.ltda'][]		= array('Name' => 'License Number',				'LangVar' => 'ltda_license',		'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => false);

// .RU
$additionaldomainfields['.ru'][]		= array('Name' => 'Birthdate',					'LangVar' => 'ru_birthdate',		'Type' => 'text',	'Size' => 16,	'Default' => '1900-01-01',	'Required' => false);
$additionaldomainfields['.ru'][]		= array('Name' => 'Issuer',						'LangVar' => 'ru_issuer',			'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => false);
$additionaldomainfields['.ru'][]		= array('Name' => 'Issue Date',					'LangVar' => 'ru_issuedate',		'Type' => 'text',	'Size' => 16,	'Default' => '1900-01-01',	'Required' => false);

// .TRAVEL
$additionaldomainfields['.travel'][]	= array('Name' => 'UIN',						'LangVar' => 'travel_uin',			'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true);

// .XXX
$additionaldomainfields['.xxx'][]		= array('Name' => 'Class',						'LangVar' => 'xxx_class',			'Type' => 'dropdown',	'Options'=>'default|Non-Member of .XXX,membership|Member of .XXX,nonResolver|Do not resolve DNS',	'Default' => 'default|Default');
$additionaldomainfields['.xxx'][]		= array('Name' => 'Name',						'LangVar' => 'xxx_name',			'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => false);
$additionaldomainfields['.xxx'][]		= array('Name' => 'Email',						'LangVar' => 'xxx_email',			'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => false);
$additionaldomainfields['.xxx'][]		= array('Name' => 'Member Id',					'LangVar' => 'xxx_memberid',		'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => false);

// .LAW, .ABOGADO
$additionaldomainfields['.law'][]		= array( 'Name' => 'Accreditation ID',			'LangVar' => 'law_accid',			'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true );
$additionaldomainfields['.law'][]		= array( 'Name' => 'Accreditation Body',		'LangVar' => 'law_accbody',			'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true );
$additionaldomainfields['.law'][]		= array( 'Name' => 'Accreditation Year',		'LangVar' => 'law_accyear',			'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true );
$additionaldomainfields['.law'][]		= array( 'Name' => 'Country',					'LangVar' => 'law_acccountry',		'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true );
$additionaldomainfields['.law'][]		= array( 'Name' => 'State/Province',			'LangVar' => 'law_accprovince',		'Type' => 'text',	'Size' => 50,	'Default' => '',	'Required' => true );
$additionaldomainfields['.abogado'] 	= $additionaldomainfields['.law'];
?>