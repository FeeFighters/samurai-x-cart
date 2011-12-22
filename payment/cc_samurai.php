<?php
/* vim: set ts=4 sw=4 sts=4 et: */
/*****************************************************************************\
+-----------------------------------------------------------------------------+
| X-Cart                                                                      |
| Copyright (c) 2001-2011 Ruslan R. Fazlyev <rrf@x-cart.com>                  |
| All rights reserved.                                                        |
+-----------------------------------------------------------------------------+
| PLEASE READ  THE FULL TEXT OF SOFTWARE LICENSE AGREEMENT IN THE "COPYRIGHT" |
| FILE PROVIDED WITH THIS DISTRIBUTION. THE AGREEMENT TEXT IS ALSO AVAILABLE  |
| AT THE FOLLOWING URL: http://www.x-cart.com/license.php                     |
|                                                                             |
| THIS  AGREEMENT  EXPRESSES  THE  TERMS  AND CONDITIONS ON WHICH YOU MAY USE |
| THIS SOFTWARE   PROGRAM   AND  ASSOCIATED  DOCUMENTATION   THAT  RUSLAN  R. |
| FAZLYEV (hereinafter  referred to as "THE AUTHOR") IS FURNISHING  OR MAKING |
| AVAILABLE TO YOU WITH  THIS  AGREEMENT  (COLLECTIVELY,  THE  "SOFTWARE").   |
| PLEASE   REVIEW   THE  TERMS  AND   CONDITIONS  OF  THIS  LICENSE AGREEMENT |
| CAREFULLY   BEFORE   INSTALLING   OR  USING  THE  SOFTWARE.  BY INSTALLING, |
| COPYING   OR   OTHERWISE   USING   THE   SOFTWARE,  YOU  AND  YOUR  COMPANY |
| (COLLECTIVELY,  "YOU")  ARE  ACCEPTING  AND AGREEING  TO  THE TERMS OF THIS |
| LICENSE   AGREEMENT.   IF  YOU    ARE  NOT  WILLING   TO  BE  BOUND BY THIS |
| AGREEMENT, DO  NOT INSTALL OR USE THE SOFTWARE.  VARIOUS   COPYRIGHTS   AND |
| OTHER   INTELLECTUAL   PROPERTY   RIGHTS    PROTECT   THE   SOFTWARE.  THIS |
| AGREEMENT IS A LICENSE AGREEMENT THAT GIVES  YOU  LIMITED  RIGHTS   TO  USE |
| THE  SOFTWARE   AND  NOT  AN  AGREEMENT  FOR SALE OR FOR  TRANSFER OF TITLE.|
| THE AUTHOR RETAINS ALL RIGHTS NOT EXPRESSLY GRANTED BY THIS AGREEMENT.      |
|                                                                             |
| The Initial Developer of the Original Code is Ruslan R. Fazlyev             |
| Portions created by Ruslan R. Fazlyev are Copyright (C) 2001-2011           |
| Ruslan R. Fazlyev. All Rights Reserved.                                     |
+-----------------------------------------------------------------------------+
\*****************************************************************************/

/**
 * "Samurai" payment module (credit card processor)
 *
 * @category   X-Cart
 * @package    X-Cart
 * @subpackage Payment interface
 * @author     GIS Team
 * @copyright  
 * @license    http://www.x-cart.com/license.php X-Cart license agreement
 * @version    $Id: cc_samurai.php,v 0.1.3 2011/12/12 15:24:54 aim Exp $
 * @link       http://www.x-cart.com/
 * @see        ____file_see____
 */

if ( !defined('XCART_START') ) { header("Location: ../"); die("Access denied"); }

require_once('samurai-client-php/Samurai.php');

$file_error = "";

// Assigning a value for Test or Live
if($module_params['testmode'] == 'Y')
	$sandbox_mode = true;
	else
	 $sandbox_mode = false;	

// Initialization of required values
Samurai::setup(array(
  'sandbox'          => $sandbox_mode,
  'merchantKey'      => $module_params['param01'],
  'merchantPassword' => $module_params['param02'],
  'processorToken'   => $module_params['param03']
));

	
// Assigning the values for an arguments	
	$payment_method = array(
      'first_name'    => $bill_firstname,
      'last_name'     => $bill_lastname,
      'address_1'     => $userinfo['b_address'],
      'address_2'     => '',
      'city'          => $userinfo['b_city'],
      'state'         => $userinfo['b_state'],
      'zip'           => $userinfo['b_zipcode'],
      'card_number'   => $userinfo['card_number'],
      'cvv'           => $userinfo['card_cvv2'],
      'expiry_month'  => $userinfo['card_expire_Month'],
      'expiry_year'   => $userinfo['card_expire_Year'],
      'custom'  	  => $bill_name,
      'sandbox'       => $sandbox_mode
    ); 

/* Create the payment method. */
$paymentMethod = Samurai_PaymentMethod::create($payment_method);


// check an error has occured
if ($paymentMethod->hasErrors()) {
		
//echo "attributes ok <br /><br />";

foreach($paymentMethod->errors as $context => $errors) {
    foreach($errors as $error) {
      //$file_error .= $error->context . "\n";
      $file_error .= $error->description . "\n";
    }
  }

$bill_output['code'] = 2;
$bill_output['billmes'] = $file_error;

}
else
{
/* Create the purchase transaction. */
$bill_ref = md5(time());
$processor = Samurai_Processor::theProcessor();
$purchase  = $processor->purchase(
							 $paymentMethod->token, 
							 $cart['total_cost'],
							 array(
							 	'descriptor'         => $bill_name,
							 	'customer_reference' => $bill_name,
							 	'billing_reference'  => $bill_ref
							 ));


// check transaction success
if ($purchase->isSuccess()) {
	//print_r($purchase);
	
$bill_output['code'] = 1;
$bill_output['billmes'] = 'Transaction Success';
$bill_output['billmes'] .= " (Ref.ID: ". $purchase->attributes['reference_id']."  Bill Ref: ".$bill_ref.")";
} 
else {
	
	//print_r($purchase);
	//echo '<br /> trnId: ' . $purchase->errors['reference_id'];

foreach($purchase->errors as $context => $errors) {
    foreach($errors as $error) {
          $file_error .= $error->description . "\n";
    }
  }

	
	$bill_output['code'] = 2;
	$bill_output['billmes'] = $file_error;
}

}
   
?>