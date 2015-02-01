<?php

/*
 * @Description: VAT validation
 * @Author: Bill Seddon
 * @Author URI: http://www.lyquidity.com
 * @Copyright: Lyquidity Solutions Limited 2013 and later
 * @License:	GNU Version 2 or Any Later Version
 * @History: This is a port of the JavaScript code to check VAT numbers
 *		2013-12-04 Updated to reflect the most recent version of the JavaScript code
 *			   See the JS code for it's history.  It can be foundhere
 *			   http://www.braemoor.co.uk/software/downloads/jsvat.zip
 */

namespace lyquidity\vat_ecsl;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
* Validates the $taxid by checking the structure is correct
*
* @param mixed $taxid
* @param mixed $out
* @param mixed $debug
*/
function perform_simple_check($taxid, &$out, $debug = false)
{

	$taxid = strtoupper(trim($taxid));
	$defCode = "GB";
	$vatexp = Array();

	$vatexp[] = "/^(AT)U(\d{8})$/";                           //** Austria
	$vatexp[] = "/^(BE)(0?\d{9})$/";                          //** Belgium
	$vatexp[] = "/^(BG)(\d{9,10})$/";                         //** Bulgaria
	$vatexp[] = "/^(CHE)(\d{9})MWST$/";						  //** Switzerland (not EU)
	$vatexp[] = "/^(CY)([0-5|9]\d{7}[A-Z])$/";                //** Cyprus
	$vatexp[] = "/^(CZ)(\d{8,13})$/";						  //** Czech Republic
	$vatexp[] = "/^(DE)([1-9]\d{8})$/";                       //** Germany
	$vatexp[] = "/^(DK)(\d{8})$/";                            //** Denmark
	$vatexp[] = "/^(EE)(10\d{7})$/";                          //** Estonia
	$vatexp[] = "/^(EL)(\d{9})$/";                            //** Greece
	$vatexp[] = "/^(ES)([A-Z]\d{8})$/";                       //** Spain (National juridical entities)
	$vatexp[] = "/^(ES)([A-H|N-S|W]\d{7}[A-J])$/";            //** Spain (Other juridical entities)
	$vatexp[] = "/^(ES)([0-9|Y|Z]\d{7}[A-Z])$/";              //** Spain (Personal entities type 1)
	$vatexp[] = "/^(ES)([K|L|M|X]\d{7}[A-Z])$/";              //** Spain (Personal entities type 2)
	$vatexp[] = "/^(EU)(\d{9})$/";                            //** EU-type
	$vatexp[] = "/^(FI)(\d{8})$/";                            //** Finland
	$vatexp[] = "/^(FR)(\d{11})$/";                           //** France (1)
	$vatexp[] = "/^(FR)[(A-H)|(J-N)|(P-Z)](\d{10})$/";        //   France (2)
	$vatexp[] = "/^(FR)\d[(A-H)|(J-N)|(P-Z)](\d{9})$/";       //   France (3)
	$vatexp[] = "/^(FR)[(A-H)|(J-N)|(P-Z)]{2}(\d{9})$/";      //   France (4)
	$vatexp[] = "/^(GB)?(\d{9})$/";                           //** UK (Standard)
	$vatexp[] = "/^(GB)?(\d{12})$/";                          //** UK (Branches)
	$vatexp[] = "/^(GB)?(GD\d{3})$/";                         //** UK (Government)
	$vatexp[] = "/^(GB)?(HA\d{3})$/";                         //** UK (Health authority)
	$vatexp[] = "/^(GR)(\d{8,9})$/";                          //** Greece
	$vatexp[] = "/^(HR)(\d{11})$/";                           //** Croatia
	$vatexp[] = "/^(HU)(\d{8})$/";                            //** Hungary
	$vatexp[] = "/^(IE)(\d{7}[A-W])$/";                       //** Ireland (1)
	$vatexp[] = "/^(IE)([7-9][A-Z\*\+)]\d{5}[A-W])$/";        //** Ireland (2)
	$vatexp[] = "/^(IE)(\d{7}[A-Z][AH])$/";                   // Ireland (3) (new format from 1 Jan 2013)
	$vatexp[] = "/^(IT)(\d{11})$/";                           //** Italy
	$vatexp[] = "/^(LV)(\d{11})$/";                           //** Latvia
	$vatexp[] = "/^(LT)(\d{9}|\d{12})$/";                     //** Lithuania
	$vatexp[] = "/^(LU)(\d{8})$/";                            //** Luxembourg
	$vatexp[] = "/^(MT)([1-9]\d{7})$/";                       //** Malta
	$vatexp[] = "/^(NL)(\d{9})B\d{2}$/";                      //** Netherlands
	$vatexp[] = "/^(NO)(\d{9})$/";                            //** Norway (not EU)
	$vatexp[] = "/^(PL)(\d{10})$/";                           //** Poland
	$vatexp[] = "/^(PT)(\d{9})$/";                            //** Portugal
	$vatexp[] = "/^(RO)([1-9]\d{1,9})$/";                     //** Romania
	$vatexp[] = "/^(RS)(\d{9})$/";                            //** Serbia (not EU)
	$vatexp[] = "/^(SI)([1-9]\d{7})$/";                       //** Slovenia
	$vatexp[] = "/^(SK)([1-9]\d[(2-4)|(6-9)]\d{7})$/";        //** Slovakia Republic
	$vatexp[] = "/^(SE)(\d{10}01)$/";                         //** Sweden

	$valid = false;

	foreach($vatexp as $vat)
	{
		$matches = null;
 // echo("vat $vat - taxid $taxid\n");
		preg_match_all( $vat, $taxid, $matches );

// echo(print_r($matches,true));
		if (count( $matches[0] ) != 1) continue;
		if (count( $matches ) != 3) continue;

		$cCode = $matches[1][0];                             // Isolate country code
		$cNumber = $matches[2][0];                           // Isolate the number
		if (strlen($cCode) == 0) $cCode = $defCode;           // Set up default country code

		$out->country = $cCode;
		$out->number = str_replace($cCode, "", $taxid);

		// Now look at the check digits for those countries we know about.
		switch ($cCode)
		{
			case "AT":
				$valid = ATVATCheckDigit ($cNumber);
				break;
			case "BE":
				$valid = BEVATCheckDigit ($cNumber);
				break;
			case "BG":
				// The SIMA validation rules are incorrect for Bulgarian numbers.
				$valid = BGVATCheckDigit ($cNumber);
				// $valid = true;
				break;
			case "CY":
				$valid = CYVATCheckDigit ($cNumber);
				break;
			case "CZ":
				$valid = CZVATCheckDigit ($cNumber);
				break;
			case "DE":
				$valid = DEVATCheckDigit ($cNumber);
				break;
			case "DK":
				$valid = DKVATCheckDigit ($cNumber);
				break;
			case "EE":
				$valid = EEVATCheckDigit ($cNumber);
				break;
			case "EL":
				$valid = ELVATCheckDigit ($cNumber);
				break;
			case "ES":
				$valid = ESVATCheckDigit ($cNumber);
				break;
			case "EU":
				$valid = EUVATCheckDigit ($cNumber);
				break;
			case "FI":
				$valid = FIVATCheckDigit ($cNumber);
				break;
			case "FR":
				$valid = FRVATCheckDigit ($cNumber);
				break;
			case "GB":
				$valid = UKVATCheckDigit ($cNumber);
				break;
			case "GR":
				$valid = ELVATCheckDigit ($cNumber);
				break;
			case "HU":
				$valid = HUVATCheckDigit ($cNumber);
				break;
			case "IE":
				$valid = IEVATCheckDigit ($cNumber);
				break;
			case "IT":
				$valid = ITVATCheckDigit ($cNumber);
				break;
			case "LT":
				$valid = LTVATCheckDigit ($cNumber);
				break;
			case "LU":
				$valid = LUVATCheckDigit ($cNumber);
				break;
			case "LV":
				$valid = LVVATCheckDigit ($cNumber);
				break;
			case "MT":
				$valid = MTVATCheckDigit ($cNumber);
				break;
			case "NL":
				$valid = NLVATCheckDigit ($cNumber);
				break;
			case "PL":
				$valid = PLVATCheckDigit ($cNumber);
				break;
			case "PT":
				$valid = PTVATCheckDigit ($cNumber);
				break;
			case "RO":
				$valid = ROVATCheckDigit ($cNumber);
				break;
			case "SE":
				$valid = SEVATCheckDigit ($cNumber);
				break;
			case "SI":
				$valid = SIVATCheckDigit ($cNumber);
				break;

			case "CHE":
				$valid = CHEVATCheckDigit ($cNumber);
				break;
			case "HR":
				$valid = HRVATCheckDigit ($cNumber);
				break;
			case "NO":
				$valid = NOVATCheckDigit ($cNumber);
				break;
			case "RS":
				$valid = RSVATCheckDigit ($cNumber);
				break;
			case "SK":
				$valid = SKVATCheckDigit ($cNumber);
				break;

			default:
				$valid = false;
		}

		if ($valid) break;
	}

	if (!$valid)
	{
		$out->message = VAT_ECSL_ERROR_VALIDATING_VAT_ID . ": " . VAT_ECSL_REASON_SIMPLE_CHECK_FAILS;
		return $out->valid = false;
	}

	return true;
}

/**
* Case insensitive version of array_key_exists.
* Returns the matching key on success, else false.
*
* @param string $key
* @param array $search
* @return string|false
*/
function array_key_exists_nc($key, $search)
{
	if (!is_array($search)) return false;

	if (array_key_exists($key, $search)) {
		return $key;
	}
	if (!(is_string($key) && is_array($search) && count($search))) {
		return false;
	}
	$key = strtolower($key);
	foreach ($search as $k => $v) {
		if (strtolower($k) == $key) {
			return $k;
		}
	}
	return false;
}

function ATVATCheckDigit ($vatnumber) {

  // Checks the check digits of an Austrian VAT number.

  $total = 0;
  $multipliers = Array(1,2,1,2,1,2,1);
  $temp = 0;

  // Extract the next digit and multiply by the appropriate multiplier.
  for ($i = 0; $i < 7; $i++) {
    $temp = $vatnumber[$i] * $multipliers[$i];
    if ($temp > 9)
      $total = $total + floor($temp/10) + $temp%10;
    else
      $total = $total + $temp;
  }

  // Establish check digit.
  $total = 10 - ($total+4) % 10;
  if ($total == 10) $total = 0;

  // Compare it with the last character of the VAT number. If it is the same,
  // then it's a valid check digit.
  if ($total == substr ($vatnumber,7,2))
    return true;
  else
    return false;
}

function BEVATCheckDigit ($vatnumber) {

  // Checks the check digits of a Belgium VAT number.

//  // First character of 10 digit numbers should be 0
//  if (strlen($vatnumber) == 10 && $vatnumber[0] != "0") return false;

  // Nine digit numbers have a 0 inserted at the front.
  if (strlen($vatnumber) == 9) $vatnumber = "0" . $vatnumber;

  // if (vatnumber.slice(1,2) == 0) return false;
  if (substr($vatnumber, 1, 1) === 0) return false;

  // Modulus 97 check on last nine digits
  if (97 - substr ($vatnumber,0,8) % 97 == substr ($vatnumber,8,2))
    return true;
  else
    return false;

}

function BGVATCheckDigit_old ($vatnumber) {

  // Check the check digit of 10 digit Bulgarian VAT numbers.
  if (strlen($vatnumber) != 10) return true;
  $total = 0;
  $multipliers = Array(4,3,2,7,6,5,4,3,2);
  $temp = 0;

  // Extract the next digit and multiply by the appropriate multiplier.
  for ($i = 0; $i < 9; $i++) {
    $temp = $temp + $vatnumber[$i] * $multipliers[$i];
  }

  // Establish check digit.
  $total = 11 - $total % 11;
  if ($total == 10) $total = 0;
  if ($total == 11) $total = 1;

  // Compare it with the last character of the VAT number. If it is the same,
  // then it's a valid check digit.
  if ($total == substr($vatnumber,9,10))
    return true;
  else
    return false;
}

function xxx($source, $result)
{
	// echo "$source\n";
	return $result;
}

function BGVATCheckDigit ($vatnumber) {
  // Checks the check digits of a Bulgarian VAT number.

  if (strlen($vatnumber) == 9) {

	// Check the check digit of 9 digit Bulgarian VAT numbers.
	$total = 0;

	// First try to calculate the check digit using the first multipliers
	$temp = 0;
	for ($i = 0; $i < 8; $i++)
		$temp = $temp + $vatnumber[$i] * ($i+1);

	// See if we have a check digit yet
	$total = $temp % 11;
	if ($total != 10) {
	  if ($total == substr($vatnumber, 8))
		return true;
	  else
		return false;
	}

	// We got a modulus of 10 before so we have to keep going. Calculate the new check digit using the
	// different multipliers
	$temp = 0;
	for ($i = 0; $i < 8; $i++)
		$temp = $temp + $vatnumber[$i] * ($i+3);

	// See if we have a check digit yet. If we still have a modulus of 10, set it to 0.
	$total = $temp % 11;
	if ($total == 10) $total = 0;
	if ($total == substr($vatnumber, 8))
	  return true;
	else
	  return false;
  }

  // 10 digit VAT code - see if it relates to a standard physical person
  $result = preg_match("/^\d\d[0-5]\d[0-3]\d\d{4}$/", $vatnumber);
  if ($result)
  {
	// Check month
	$month = substr($vatnumber, 2, 2);
	if (($month > 0 && $month < 13) || ($month > 20 & $month < 33)) {

	  // Extract the next digit and multiply by the counter.
	  $total = 0;
	  $multipliers = Array(2,4,8,5,10,9,7,3,6);

	  for ($i = 0; $i < 9; $i++)
		  $total = $total + $vatnumber[$i] * $multipliers[$i];

	  // Establish check digit.
	  $total = $total % 11;
	  if ($total == 10)
		  $total = 0;

	  // Check to see if the check digit given is correct, If not, try next type of person
	  if ($total == substr($vatnumber, 9,1))
		  return true;
	}
  }

  // It doesn't relate to a standard physical person - see if it relates to a foreigner.

  // Extract the next digit and multiply by the counter.
  $total = 0;
  $multipliers = Array(21,19,17,13,11,9,7,3,1);
  for ($i = 0; $i < 9; $i++)
	  $total = $total + $vatnumber[$i] * $multipliers[$i];

  // Check to see if the check digit given is correct, If not, try next type of person
  if ($total % 10 == substr($vatnumber, 9,1)) 
	  return true;

  // Finally, if not yet identified, see if it conforms to a miscellaneous VAT number

  // Extract the next digit and multiply by the counter.
  $total = 0;
  $multipliers = Array(4,3,2,7,6,5,4,3,2);
  for ($i = 0; $i < 9; $i++)
	  $total = $total + $vatnumber[$i] * $multipliers[$i];

  // Establish check digit.
  $total = 11 - $total % 11;
  if ($total == 10) return false;
  if ($total == 11) $total = 0;

  // Check to see if the check digit given is correct, If not, we have an error with the VAT number
  if ($total == substr($vatnumber,9,1))
	return true;
  else
	return false;
}

function CHEVATCheckDigit($vatnumber) {

  // Checks the check digits of a Swiss VAT number.
  // Extract the next digit and multiply by the counter.
  $multipliers = array(5,4,3,2,7,6,5,4);
  $total = 0;
  for ($i = 0; $i < 8; $i++)
	  $total = $total + $vatnumber[$i] * $multipliers[$i];

  // Establish check digit.
  $total = 11 - $total % 11;
  if ($total == 10) return false;
  if ($total == 11) $total = 0;

  // Check to see if the check digit given is correct, If not, we have an error with the VAT number
  if ($total == substr($vatnumber, 8,1))
	return true;
  else
	return false;
}

function CYVATCheckDigit ($vatnumber) {

  // Checks the check digits of a Cypriot VAT number.

  // Not allowed to start with '12'
  if (substr($vatnumber, 0,2) == '12') return false;

  // Extract the next digit and multiply by the counter.
  $total = 0;
  for ($i = 0; $i < 8; $i++) {
    $temp = $vatnumber[$i];
    if ($i % 2 == 0) {
      switch ($temp) {
        case 0: $temp = 1; break;
        case 1: $temp = 0; break;
        case 2: $temp = 5; break;
        case 3: $temp = 7; break;
        case 4: $temp = 9; break;
        default: $temp = $temp*2 + 3;
      }
    }
    $total = $total + $temp;
  }

  // Establish check digit using modulus 26, and translate to char. equivalent.
  $total = $total % 26;
  $total = chr($total+65);

  // Check to see if the check digit given is correct
  if ($total == substr($vatnumber,8,1))
    return true;
  else
    return false;
}

function CZVATCheckDigit_old ($vatnumber) {

  // Checks the check digits of a Czech Republic VAT number.

  $total = 0;
  $multipliers = array(8,7,6,5,4,3,2);

  // Only do check digit validation for standard VAT numbers
  if (strlen($vatnumber) != 8) return true;

  // Extract the next digit and multiply by the counter.
  for ($i = 0; $i < 7; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

  // Establish check digit.
  $total = 11 - $total % 11;
  if ($total == 10) $total = 0;
  if ($total == 11) $total = 1;

  // Compare it with the last character of the VAT number. If it is the same,
  // then it's a valid check digit.

  if ($total == substr($vatnumber,8,1))
    return true;
  else
    return false;
}

function CZVATCheckDigit($vatnumber) {

  // Checks the check digits of a Czech Republic VAT number.

  $total = 0;
  $multipliers = array(8,7,6,5,4,3,2);

  $czexp = array ();
  $czexp[0] = "/^\d{8}$/";
  $czexp[1] = "/^[0-5][0-9][0|1|5|6]\d[0-3]\d\d{3}$/";
  $czexp[2] = "/^6\d{8}$/";
  $czexp[3] = "/^\d{2}[0-3|5-8]\d[0-3]\d\d{4}$/";
  $i = 0;

  // Legal entities
  if (preg_match($czexp[0], $vatnumber)) {

	// Extract the next digit and multiply by the counter.
	for ($i = 0; $i < 7; $i++)
	  $total = $total + $vatnumber[$i] * $multipliers[$i];

	// Establish check digit.
	$total = 11 - $total % 11;
	if ($total == 10) $total = 0;
	if ($total == 11) $total = 1;

	// Compare it with the last character of the VAT number. If it is the same,
	// then it's a valid check digit.
	if ($total == substr($vatnumber, 7, 1))
	  return true;
	else
	  return false;
  }

  // Individuals type 1
  else if (preg_match($czexp[1], $vatnumber)) {
	if ($temp = substr($vatnumber, 0, 2) > 53) return false;
	return true;
  }

  // Individuals type 2
  else if (preg_match($czexp[2], $vatnumber)) {

	// Extract the next digit and multiply by the counter.
	for ($i = 0; $i < 7; $i++)
		$total = $total + $vatnumber[$i+1] * $multipliers[$i];

	// Establish check digit.
	$total = 11 - $total % 11;
	if ($total == 10) $total = 0;
	if ($total == 11) $total = 1;

	// Convert calculated check digit according to a lookup table;
	$lookup  = array(8,7,6,5,4,3,2,1,0,9,10);
	if ($lookup[$total-1] == substr($vatnumber, 8, 1))
	  return true;
	else
	  return false;
  }

  // Individuals type 3
  else if (preg_match($czexp[3], $vatnumber)) {
	// $temp = Number(vatnumber.slice(0,2)) + Number(vatnumber.slice(2,4)) + Number(vatnumber.slice(4,6)) + Number(vatnumber.slice(6,8)) + Number(vatnumber.slice(8));
	$temp = substr($vatnumber, 0, 2) + substr($vatnumber, 2, 2) + substr($vatnumber, 4, 2) + substr($vatnumber, 6, 2) + substr($vatnumber, 8);
	if ($temp % 11 == 0 && ($vatnumber + 0) % 11 == 0)
	  return true;
	else
	  return false;
  }

  // else error
  return false;
}

function DEVATCheckDigit ($vatnumber) {

  // Checks the check digits of a German VAT number.

  $product = 10;
  $sum = 0;
  $checkdigit = 0;
  for ($i = 0; $i < 8; $i++) {

    // Extract the next digit and implement perculiar algorithm!.
    $sum = ($vatnumber[$i] + $product) % 10;
    if ($sum == 0) {$sum = 10;}
    $product = (2 * $sum) % 11;
  }

  // Establish check digit.
  if (11 - $product == 10) {$checkdigit = 0;} else {$checkdigit = 11 - $product;}

  // Compare it with the last  digit of the VAT number. If the same,
  // then it is a valid check digit.
  if ($checkdigit == substr($vatnumber,8,1))
    return true;
  else
    return false;

}

function DKVATCheckDigit ($vatnumber) {

  // Checks the check digits of a Danish VAT number.

  $total = 0;
  $multipliers = Array(2,7,6,5,4,3,2,1);

  // Extract the next digit and multiply by the counter.
  for ($i = 0; $i < 8; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

  // Establish check digit.
  $total = $total % 11;

  // The remainder should be 0 for it to be valid..
  if ($total == 0)
    return true;
  else
    return false;
}

function EEVATCheckDigit ($vatnumber) {

  // Checks the check digits of an Estonian VAT number.

  $total = 0;
  $multipliers = Array(3,7,1,3,7,1,3,7);

  // Extract the next digit and multiply by the counter.
  for ($i = 0; $i < 8; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

  // Establish check digits using modulus 10.
  $total = 10 - $total % 10;
  if ($total == 10) $total = 0;

  // Compare it with the last character of the VAT number. If it is the same,
  // then it's a valid check digit.
  if ($total == substr($vatnumber,8,1))
    return true;
  else
    return false;

}

function ELVATCheckDigit ($vatnumber) {

  // Checks the check digits of a Greek VAT number.

  $total = 0;
  $multipliers = Array(256,128,64,32,16,8,4,2);

  //eight character numbers should be prefixed with an 0.
  if (strlen($vatnumber) == 8) {$vatnumber = "0" + vatnumber;}

  // Extract the next digit and multiply by the counter.
  for ($i = 0; $i < 8; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

  // Establish check digit.
  $total = $total % 11;
  if ($total > 9) {$total = 0;};

  // Compare it with the last character of the VAT number. If it is the same,
  // then it's a valid check digit.
  if ($total == substr($vatnumber,8,1))
    return true;
  else
    return false;
}

function ESVATCheckDigit ($vatnumber) {

	// Checks the check digits of a Spanish VAT number.
	$total = 0;
	$temp = 0;
	$multipliers = Array(2,1,2,1,2,1,2);
	$esexp = Array ();
	$esexp[0] = "/^[A-H|J|U|V]\d{8}$/";
	$esexp[1] = "/^[A-H|N-S|W]\d{7}[A-J]$/";
	$esexp[2] = "/^[0-9|Y|Z]\d{7}[A-Z]$/";
	$esexp[3] = "/^[K|L|M|X]\d{7}[A-Z]$/";

	$i = 0;

	$matches = Array();
	foreach($esexp as $exp)
	{
		preg_match_all( $exp, $vatnumber, $matches );
		if (count( $matches[0] ) == 1) break;
		$i++;
	}

	// National juridical entities
	// With profit companies
	if ($i == 0)
	{
		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 7; $i++)
		{
			$temp = $vatnumber[$i+1] * $multipliers[$i];
			if ($temp > 9)
				$total = $total + floor($temp/10) + $temp%10;
			else
				$total = $total + $temp;
		}

		// Now calculate the check digit itself.
		$total = 10 - $total % 10;
		if ($total == 10) {$total = 0;}

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatnumber,8,1))
			return true;
		else
			return false;
	}

	// Juridical entities other than national ones
	// Non-profit companies
	else if ($i == 1)
	{
		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 7; $i++) {
			$temp = $vatnumber[$i+1] * $multipliers[$i];
			if ($temp > 9)
				$total = $total + floor($temp/10) + $temp % 10;
			else
				$total = $total + $temp;
		}

		// Now calculate the check digit itself.
		$total = 10 - $total % 10;
		$total = chr($total+64);

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatnumber,8,1))
			return true;
		else
			return false;
	}

	// Personal number (NIF) (starting with numeric of Y or Z)
	else if ($i == 2)
	{
		$tempnumber = $vatnumber;
		if (substr($tempnumber, 0, 1) == 'Y') $tempnumber = preg_replace("/Y/", "1", $tempnumber);
		if (substr($tempnumber, 0, 1) == 'Z') $tempnumber = preg_replace("/Z/", "2", $tempnumber);

		$s = "TRWAGMYFPDXBNJZSQVHLCKE";
		return $tempnumber[8] == $s[substr($tempnumber, 0, 8) % 23];
	}

	// Personal number (NIF) (starting with K, L, M, or X)
	else if ($i == 3)
	{
		$s = "TRWAGMYFPDXBNJZSQVHLCKE";
		return $vatnumber[8] == $s[substr($vatnumber, 1, 8) % 23];
	}
	else return false;
}

function EUVATCheckDigit ($vatnumber) {

  // We know litle about EU numbers apart from the fact that the first 3 digits
  // represent the country, and that there are nine digits in $total.
  return true;
}

function FIVATCheckDigit ($vatnumber) {

  // Checks the check digits of a Finnish VAT number.

  $total = 0;
  $multipliers = Array(7,9,10,5,8,4,2);

  // Extract the next digit and multiply by the counter.
  for ($i = 0; $i < 7; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

  // Establish check digit.
  $total = 11 - $total % 11;
  if ($total > 9) {$total = 0;};

  // Compare it with the last character of the VAT number. If it is the same,
  // then it's a valid check digit.
  if ($total == substr($vatnumber,7,1))
    return true;
  else
    return false;
}

function FRVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a French VAT number.
	if (!preg_match("/^\d{11}$/", $vatnumber )) return true;

	// Extract the last nine digits as an integer.
	$total = substr($vatnumber, 2);

	// Establish check digit.
	// $total = ($total*100+12) % 97;
	// The standard PHP functions cannot cope with a VAT number
	// like FR00300076965 as it is essential for the computation
	// to work correctly as the input number is < PHP_INT_MAX
	$total = modLargeNumber($total . "12", 97);

	// Compare it with the last character of the VAT number. If it is the same,
	// then it's a valid check digit.

	if ($total == substr($vatnumber,0,2))
		return true;
	else
		return false;
}

function HRVATCheckDigit($vatnumber) {

  // Checks the check digits of a Croatian VAT number using ISO 7064, MOD 11-10 for check digit.

  $product = 10;
  $sum = 0;
  $checkdigit = 0;

  for ($i = 0; $i < 10; $i++) {

	// Extract the next digit and implement the algorithm
	$sum = ($vatnumber[$i] + $product) % 10;
	if ($sum == 0) {$sum = 10; }
	$product = (2 * $sum) % 11;
  }

  // Now check that we have the right check digit
  if (($product + substr($vatnumber,10,1)*1) % 10 == 1)
	return true;
  else
	return false;
}

function HUVATCheckDigit ($vatnumber)
{

	// Checks the check digits of a Hungarian VAT number.

	$total = 0;
	$multipliers = Array(9,7,3,1,9,7,3);

	// Extract the next digit and multiply by the counter.
	for ($i = 0; $i < 7; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

	// Establish check digit.
	$total = 10 - $total % 10;
	if ($total == 10) $total = 0;

	// Compare it with the last character of the VAT number. If it is the same,
	// then it's a valid check digit.
	if ($total == substr($vatnumber,7,1))
		return true;
	else
		return false;
}

function IEVATCheckDigit ($vatnumber)
{
/*
		  // We cannot perform check digit calculation on type 3 so simply let them through.
	  if (/^\d{7}[A-Z][AH]$/.test(vatnumber)) return true;
	  
	  var total = 0; 
	  var multipliers = [8,7,6,5,4,3,2];
	  
	  // If the code is in the old format, we need to convert it to the new.
	  if (/^\d[A-Z\*\+]/.test(vatnumber)) vatnumber = "0" + vatnumber.substring(2,7) + vatnumber.substring(0,1) + vatnumber.substring(7,8);
		
	  // Extract the next digit and multiply by the counter.
	  for (var i = 0; i < 7; i++) total = total + Number(vatnumber.charAt(i)) * multipliers[i];
	  
	  // Establish check digit using modulus 23, and translate to char. equivalent.
	  total = total % 23;
	  if (total == 0)
		total = "W"
	  else
		total = String.fromCharCode(total+64);
	  // Compare it with the last character of the VAT number. If it is the same, then it's a valid 
	  // check digit.
	  if (total == vatnumber.slice (7,8)) 
		return true
	  else 
		return false;
*/

	// Checks the check digits of an Irish VAT number.

	// We cannot perform check digit calculation on type 3 so simply let them through.
	if (preg_match("/^\d{7}[A-Z][AH]$/", $vatnumber)) return true;

	$total = 0;
	$multipliers = Array(8,7,6,5,4,3,2);

	// If the code is in the old format, we need to convert it to the new.
	if (preg_match( "/^\d[A-Z\*\+]/", $vatnumber ))
		$vatnumber = "0" . substr($vatnumber, 2,5) . substr($vatnumber, 0,1) . substr($vatnumber, 7,1);

	// Extract the next digit and multiply by the counter.
	for ($i = 0; $i < 7; $i++)
	{
		$total = $total + $vatnumber[$i] * $multipliers[$i];
	}

	// Establish check digit using modulus 23, and translate to char. equivalent.
	$total = $total % 23;
	if ($total == 0)
		$total = "W";
	else
		$total = chr($total+64);

	// Compare it with the last character of the VAT number. If it is the same,
	// then it's a valid check digit.
	if ($total == substr($vatnumber,7,1))
		return true;
	else
		return false;
}

function ITVATCheckDigit ($vatnumber)
{
	// Checks the check digits of an Italian VAT number.

	$total = 0;
	$multipliers = Array(1,2,1,2,1,2,1,2,1,2);
	$temp;

	// The last three digits are the issuing office, and cannot exceed more 201
	$temp= substr($vatnumber, 0,7);
	if ($temp==0) return false;
	$temp=substr($vatnumber,7,3);
	if (($temp<1) || ($temp>201) && $temp != 999 && $temp != 888) return false;

	// Extract the next digit and multiply by the appropriate
	for ($i = 0; $i < 10; $i++)
	{
		$temp = $vatnumber[$i] * $multipliers[$i];
		if ($temp > 9)
			$total = $total + floor($temp/10) + $temp % 10;
		else
			$total = $total + $temp;
	}

	// Establish check digit.
	$total = 10 - $total % 10;
	if ($total > 9) {$total = 0;};

	// Compare it with the last character of the VAT number. If it is the same,
	// then it's a valid check digit.
	if ($total == substr($vatnumber,10,1))
		return true;
	else
		return false;

}

function LTVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a Lithuanian VAT number.

	// Only do check digit validation for standard VAT numbers
	if (strlen($vatnumber) == 9)
	{
		// 8th character must be one
		if (!preg_match("/^\d{7}1/", $vatnumber)) return false;

		// Extract the next digit and multiply by the counter+1.
		$total = 0;
		for ($i = 0; $i < 8; $i++) $total = $total + $vatnumber[$i] * ($i+1);

		// Can have a double check digit calculation!
		if ($total % 11 == 10)
		{
			$multipliers = Array(3,4,5,6,7,8,9,1);
			$total = 0;
			for ($i = 0; $i < 8; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];
		}

		// Establish check digit.
		$total = $total % 11;
		if ($total == 10) {$total = 0;};

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatnumber,8,1))
			return true;
		else
			return false;

	}
	// 12 character VAT numbers are for temporarily registered taxpayers
	else {

		// 11th character must be one
		if (!preg_match("/^\d{10}1/", $vatnumber)) return false;
		// Extract the next digit and multiply by the counter+1.
		$total = 0;
		$multipliers = Array(1,2,3,4,5,6,7,8,9,1,2);
		for ($i = 0; $i < 11; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

		// Can have a double check digit calculation!
		if ($total % 11 == 10)
		{
			$multipliers = Array(3,4,5,6,7,8,9,1,2,3,4);
			$total = 0;
			for ($i = 0; $i < 11; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];
		}

		// Establish check digit.
		$total = $total % 11;
		if ($total == 10) {$total = 0;};

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatnumber,11,1))
			return true;
		else
			return false;
	}
}

function LUVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a Luxembourg VAT number.
	if (substr($vatnumber,0,6) % 89 == substr($vatnumber,6,2))
		return true;
	else
		return false;
}

function LVVATCheckDigit ($vatnumber)
{

	// Checks the check digits of a Latvian VAT number.

	// Differentiate between legal entities and natural bodies. For the latter we simply check that
	// the first six digits correspond to valid DDMMYY dates.
	if (preg_match( "/^[0-3]/", $vatnumber))
	{
		if (preg_match("/^[0-3][0-9][0-1][0-9]/", $vatnumber))
		  return true;
		else
		  return false;
	}

	$total = 0;
	$multipliers = Array(9,1,4,8,3,10,2,5,7,6);

	// Extract the next digit and multiply by the counter.
	for ($i = 0; $i < 10; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

	// Establish check digits by getting modulus 11.
	if ($total%11 == 4 && $vatnumber[0] ==9) $total = $total - 45;
	if ($total%11 == 4)
		$total = 4 - $total%11;
	else if ($total % 11 > 4)
		$total = 14 - $total%11;
	else if ($total % 11 < 4)
		$total = 3 - $total%11;

	// Compare it with the last character of the VAT number. If it is the same,
	// then it's a valid check digit.
	if ($total == substr($vatnumber,10,1))
		return true;
	else
		return false;
}

function MTVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a Maltese VAT number.

	$total = 0;
	$multipliers = Array(3,4,6,7,8,9);

	// Extract the next digit and multiply by the counter.
	for ($i = 0; $i < 6; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

	// Establish check digits by getting modulus 37.
	$total = 37 - $total % 37;

	// Compare it with the last character of the VAT number. If it is the same,
	// then it's a valid check digit.
	if ($total == substr($vatnumber,6,2) * 1)
		return true;
	else
		return false;
}

function NLVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a Dutch VAT number.

	$total = 0;                                 //
	$multipliers = Array(9,8,7,6,5,4,3,2);

	// Extract the next digit and multiply by the counter.
	for ($i = 0; $i < 8; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

	// Establish check digits by getting modulus 11.
	$total = $total % 11;
	if ($total > 9) {$total = 0;};

	// Compare it with the last character of the VAT number. If it is the same,
	// then it's a valid check digit.
	if ($total == substr($vatnumber,8,1))
		return true;
	else
		return false;
}

function NOVATCheckDigit($vatnumber) {

  // Checks the check digits of a Norwegian VAT number.
  // See http://www.brreg.no/english/coordination/number.html

  $total = 0;
  $multipliers = array(3,2,7,6,5,4,3,2);

  // Extract the next digit and multiply by the counter.
  for ($i = 0; $i < 8; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

  // Establish check digits by getting modulus 11. Check digits > 9 are invalid
  $total = 11 - $total % 11;
  if ($total == 11) {$total = 0;}
  if ($total < 10) {

	// Compare it with the last character of the VAT number. If it is the same, then it's a valid
	// check digit.
	if ($total == substr($vatnumber,8,1))
		return true;
	else
		return false;
  }
}

function PLVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a Polish VAT number.

	$total = 0;
	$multipliers = Array(6,5,7,2,3,4,5,6,7);

	// Extract the next digit and multiply by the counter.
	for ($i = 0; $i < 9; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

	// Establish check digits subtracting modulus 11 from 11.
	$total = $total % 11;
	if ($total > 9) {$total = 0;};

	// Compare it with the last character of the VAT number. If it is the same, then it's a valid
	// check digit.
	if ($total == substr($vatnumber,9,1))
		return true;
	else
		return false;
}

function PTVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a Portugese VAT number.

	$total = 0;
	$multipliers = Array(9,8,7,6,5,4,3,2);

	// Extract the next digit and multiply by the counter.
	for ($i = 0; $i < 8; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

	// Establish check digits subtracting modulus 11 from 11.
	$total = 11 - $total % 11;
	if ($total > 9) {$total = 0;};

	// Compare it with the last character of the VAT number. If it is the same, then it's a valid
	// check digit.
	if ($total == substr($vatnumber,8,1))
		return true;
	else
		return false;
}

function ROVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a Romanian VAT number.

	$multipliers = Array(7,5,3,2,1,7,5,3,2,1);

	// Extract the next digit and multiply by the counter.
	$VATlen = strlen($vatnumber);
	$multipliers = array_splice($multipliers, -$VATlen);

	$total = 0;
	for ($i = 0; $i < strlen($vatnumber)-1; $i++)
	{
		$total = $total + $vatnumber[$i] * $multipliers[$i];
	}

	// Establish check digits by getting modulus 11.
	$total = (10 * $total) % 11;
	if ($total == 10) $total = 0;

	// Compare it with the last character of the VAT number. If it is the same, then it's a valid
	// check digit.
	if ($total == substr($vatnumber,strlen($vatnumber)-1, 1))
		return true;
	else
		return false;
}

function RSVATCheckDigit($vatnumber) {

  // Checks the check digits of a Serbian VAT number using ISO 7064, MOD 11-10 for check digit.

  $product = 10;
  $sum = 0;
  $checkdigit = 0;

  for ($i = 0; $i < 8; $i++) {

	// Extract the next digit and implement the algorithm
	$sum = ($vatnumber[$i] + $product) % 10;
	if ($sum == 0) { $sum = 10; }
	$product = (2 * $sum) % 11;
  }

  // Now check that we have the right check digit
  if (($product + $vatnumber[8] * 1) % 10 == 1)
	return true;
  else
	return false;
}

function SEVATCheckDigit ($vatnumber)
{
	// Calculate R where R = R1 + R3 + R5 + R7 + R9, and Ri = INT(Ci/5) + (Ci*2) modulo 10
	$R = 0;
	$digit;
	for ($i = 0; $i < 9; $i=$i+2) {
		$digit = $vatnumber[$i] + 0;
		$R = $R + floor($digit / 5)  + (($digit * 2) % 10);
	}

	// Calculate S where S = C2 + C4 + C6 + C8
	$S = 0;
	for ($i = 1; $i < 9; $i=$i+2)
		$S = $S + $vatnumber[$i] + 0;

	// Calculate the Check Digit
	$cd = (10 - ($R + $S) % 10) % 10;

	// Compare it with the 10th character of the VAT number. If it is the same, then it's a valid
	// check digit.

	if ($cd == substr($vatnumber, 9,1))
		return true;
	else
		return false;

}

function SIVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a Slovenian VAT number.

	$total = 0;
	$multipliers = Array(8,7,6,5,4,3,2);

	// Extract the next digit and multiply by the counter.
	for ($i = 0; $i < 7; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

	// Establish check digits by subtracting 97 from $total until negative.
	$total = 11 - $total % 11;
	if ($total > 9) {$total = 0;};

	// Compare the number with the last character of the VAT number. If it is the
	// same, then it's a valid check digit.
	if ($total == substr($vatnumber,7,1))
		return true;
	else
		return false;
}

function SKVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a Slovak VAT number.

	// Check that the modulus of the whole VAT number is 0 - else error
	if (modLargeNumber($vatnumber, 11) == 0)
		return true;
	else
		return false;

}

function UKVATCheckDigit ($vatnumber)
{
	// Checks the check digits of a UK VAT number.
	$multipliers = Array(8,7,6,5,4,3,2);

	// Government departments
	if ($vatnumber.substr(0,2) == 'GD')
	{
		if ($vatnumber.substr(2,3) < 500)
			return true;
		else
			return false;
	}

	// Health authorities
	if ($vatnumber.substr(0,2) == 'HA')
	{
		if ($vatnumber.substr(2,3) > 499)
			return true;
		else
			return false;
	}

	// Standard and commercial numbers
		$total = 0;

		// 0 VAT numbers disallowed!
		if (($vatnumber + 0) == 0) return false;

		// Check range is OK for modulus 97 calculation
		$no = substr($vatnumber, 0,7);

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 7; $i++) $total = $total + $vatnumber[$i] * $multipliers[$i];

		// Old numbers use a simple 97 modulus, but new numbers use an adaptation of that (less
		// 55). Our VAT number could use either system, so we check it against both.

		// Establish check digits by subtracting 97 from $total until negative.
		$cd = $total;
		while ($cd > 0) {$cd = $cd - 97;}

		// Get the absolute value and compare it with the last two characters of the
		// VAT number. If the same, then it is a valid traditional check digit.
		$cd = abs($cd);
		if ($cd == substr($vatnumber,7,2) && $no < 9990001 && ($no < 100000 || $no > 999999) && ($no < 9490001 || $no > 9700000)) return true;

		// Now try the new method by subtracting 55 from the check digit if we can - else add 42
		if ($cd >= 55)
			$cd = $cd - 55;
		else
			$cd = $cd + 42;

		if ($cd == substr($vatnumber,7,2) && $no > 1000000)
			return true;
		else
			return false;

	// We don't check 12 and 13 digit UK numbers - not only can we not find any,
	// but the information found on the format is contradictory.

	return true;
}

// This is a generalised function to mod any large number
function modLargeNumber($largeNumber, $modulo, $chunkSize = 6)
{
	// Create an array of digits from the number
	$parts = str_split($largeNumber, $chunkSize);

	// Corner case: There may only be one digit so mod it and remove from the array
	$result = $parts[0] % $modulo;
	unset($parts[0]);

	// For each of the remaining digits use the algorithm
	foreach($parts as $part)
	{
		$result = ($result * pow(10, strlen($part)) % $modulo + $part % $modulo) % $modulo;
	}

	return $result;
}

?>
