<?php

//AJAX
add_action('wp_ajax_t4s_auth_ipn_callback', 't4s_auth_ipn_callback');
add_action('wp_ajax_nopriv_t4s_auth_ipn_callback', 't4s_auth_ipn_callback');

function t4s_auth_ipn_callback() {

	global $wpdb;
	
	$invoice = intval(esc_html($_POST['x_invoice_num']));
	
	$query = $wpdb->prepare("UPDATE t4s_authorize_payments SET response = '%s' WHERE id = '%s'", json_encode($_REQUEST), $invoice);
	mysql_query($query);
	

	if (mysql_errno()) {
		sendEmailToT4SAdmins("T4S Plugin Error: Unable to Record Authorize.NET Payment!", "[ERROR AUTH/IPN 001] T4S Plugin was unable to access the database to record a payment. A payment made to you was not properly recorded.",$_POST);
		exit();
	}
	
	if ($_POST['x_response_code'] == '1') {
	
		//VERIFYING AUTH.NET RESPONSE
		
		//No inputs and no prepare()
		$query = "SELECT option_name, option_value FROM ".$wpdb->prefix."options WHERE option_name = 't4s_auth_key' OR option_name = 't4s_auth_id' OR option_name = 't4s_auth_md5'";
		$result = mysql_query($query);
		if (!$result) {
			sendEmailToT4SAdmins("T4S Plugin Error: Unable to Retrieve Authorize.NET Settings!", "[ERROR AUTH/IPN 002] T4S Plugin was unable to retrieve your Authorize.NET credentials to record a payment. A payment made to you was not properly recorded. ", $_POST);
			exit();
		}
		if (mysql_num_rows($result) != 3) {
			sendEmailToT4SAdmins("T4S Plugin Error: Unable to Retrieve Authorize.NET Settings!", "[ERROR AUTH/IPN 003] T4S Plugin was unable to retrieve all of your Authorize.NET credentials to record a payment. A payment made to you was not properly recorded.", $_POST);
			exit();
		}
		
		for ($i=0;$i<mysql_num_rows($result);$i++) {
			$row = mysql_fetch_assoc($result);
			if ($row['option_name'] == 't4s_auth_key') $x_tran_key = $row['option_value'];
			if ($row['option_name'] == 't4s_auth_id') $x_loginid = $row['option_value'];
			if ($row['option_name'] == 't4s_auth_md5') $x_hash = $row['option_value'];
		}
		
		$md5 = esc_html($_POST['x_MD5_Hash']);
		if (strlen($md5) > 128) $md5 = "";
		
		$transId = esc_html($_POST['x_trans_id']);
		if (strlen($transId) > 64) $transId = "";
		
		$amount = floatval($_POST['x_amount']);		
		
		$myMD5 = strtoupper(md5("$x_hash$x_loginid$transId$amount"));
		
		//PAYMENT VALIDATED
		if ($myMD5 == $md5) { 
		
			$query = $wpdb->prepare("UPDATE t4s_authorize_payments SET status = '1' WHERE id = '%s'", $invoice);
			mysql_query($query);
			
			if (mysql_errno()) {
				sendEmailToT4SAdmins("T4S Plugin Error: Unable to Record Successful Authorize.NET Payment!", "[ERROR AUTH/IPN 004] T4S Plugin was unable to update the status of a payment as processed. A payment made to you was not properly recorded. ", $_POST);
				exit();
			}
		  
			$query = $wpdb->prepare("SELECT * FROM t4s_authorize_payments WHERE id = '%s'", $invoice);
			$result = mysql_query($query);
			
			if (!$result) {
				sendEmailToT4SAdmins("T4S Plugin Error: Unable to Record Authorize.NET Payment!", "[ERROR AUTH/IPN 005] T4S Plugin was unable to retrieve the original form usbmission record. A payment made to you was not properly recorded.", $_POST);
				exit();
			}
			
			if (mysql_num_rows($result) > 0) {
			
				$row = mysql_fetch_assoc($result);
				$query2 = $wpdb->prepare("SELECT * FROM t4s_forms WHERE id='%s'", $row['form_id']);
				$result2 = mysql_query($query2);
				
				if (!$result2) {
					sendEmailToT4SAdmins("T4S Plugin Error: Unable to look up the original form for an Authorize.NET Payment!", "[ERROR AUTH/IPN 006] T4S Plugin was unable to retrieve the original form associated with this payment. The payment is recorded but is not linked to any currently active forms. ", $_POST);
					exit();
				}
				
				if (mysql_num_rows($result2) > 0) {
				
					$row2 = mysql_fetch_assoc($result2);
					$body = "
						Item(s) Sold: ".esc_html($row['description'])."
						Payment Status: ".esc_html($_POST['x_response_reason_text'])."
						Total Paid: ".esc_html($_POST['x_amount'])."
						Payer's Email: ".esc_html($_POST['x_email'])."
						
						Please log in to your Authorize.NET account and verify this transaction before processing the order.";
					
					$subject = "Payment Completed: Tools 4 Shuls Wordpress Plugin Notification";
					$headers = "From: support@tools4shuls.com";
					
					try {
						$receiver_email = getT4SSettings('t4s_login');						
					} catch(Exception $e) {
					}
					
					$admin_email = get_option('admin_email');
					
					if ($receiver_email != $admin_email) {
						$receiver_email .= ",".$admin_email;
					}	
					
					mail($receiver_email, $subject, $body, $headers);
										
					if (strlen($row2['thank_you'] > 0)) {					
						header('Location: '.str_replace("http://http://", "http://", "http://".$row2['thank_you']));
					} else {
						echo "Thank you for your payment!";
					}
					
					exit();
					
				} else {
					sendEmailToT4SAdmins("T4S Plugin Error: Unable to look up the original form for an Authorize.NET Payment!", "[ERROR AUTH/IPN 007] T4S Plugin was unable to retrieve the original form associated with this payment.  The payment is recorded but is not linked to any currently active forms.  ", $_POST);
					exit();
				}
			} else {				
				sendEmailToT4SAdmins("T4S Plugin Error: Unable to Process Authorize.NET Payment!", "[ERROR AUTH/IPN 008] T4S Plugin was unable to retrieve the original form submission record. A payment made to you was not properly recorded. ", $_POST);
				exit();
				
			}
		} else {
			sendEmailToT4SAdmins("T4S Plugin Error: Unable to Verify Authorize.NET Payment!", "[ERROR AUTH/IPN 009] T4S Plugin was unable to verify a processed payment. This may be a result of an error or a fraud attempt. Please review your Authorize.NET logs and take appropriate action. ", $_POST);
			exit();
		}
	}

}
?>