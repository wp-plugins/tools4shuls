<?php

//AJAX
add_action('wp_ajax_t4s_ppl_ipn_callback', 't4s_ppl_ipn_callback');
add_action('wp_ajax_nopriv_t4s_ppl_ipn_callback', 't4s_ppl_ipn_callback');

function t4s_ppl_ipn_callback() {
	
  global $wpdb;
    
  //reading raw POST data from input stream
  $raw_post_data = file_get_contents('php://input');

  $raw_post_array = explode('&', $raw_post_data);
  
  $myPost = array();
  foreach ($raw_post_array as $keyval)
  {
      $keyval = explode ('=', $keyval);
      if (count($keyval) == 2)
         $myPost[$keyval[0]] = urldecode($keyval[1]);
  }

  // read the post from PayPal system and add 'cmd'
  $req = 'cmd=_notify-validate';
  if(function_exists('get_magic_quotes_gpc'))
  {
       $get_magic_quotes_exits = true;
  } 
  foreach ($myPost as $key => $value)
  {        
       if($get_magic_quotes_exits == true && get_magic_quotes_gpc() == 1)
       { 
            $value = urlencode(stripslashes($value)); 
       }
       else
       {
            $value = urlencode($value);
       }
       $req .= "&$key=$value";
  }

	  
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, 'https://www.paypal.com/cgi-bin/webscr');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: www.paypal.com'));
	$res = curl_exec($ch);
	curl_close($ch);
	 
	// assign posted variables to local variables
	$item_name = esc_html($_POST['item_name']);
	if (strlen($item_name) > 128) $item_name = "";
	
	$item_number = intval(esc_html($_POST['item_number']));
	
	$payment_status = esc_html($_POST['payment_status']);
	if (strlen($payment_status) > 64) $payment_status = "";
		
	$payment_amount = floatval(esc_html($_POST['mc_gross']));
	
	$payment_currency = esc_html($_POST['mc_currency']);
	if (strlen($payment_currency) > 64) $payment_currency = "";
	
	$txn_id = intval(esc_html($_POST['txn_id']));
	
	$receiver_email = esc_html($_POST['receiver_email']);
	if (strlen($receiver_email) > 128) $receiver_email = "";
	
	$payer_email = esc_html($_POST['payer_email']);
	if (strlen($payer_email) > 128) $payer_email = "";
	

	if (strcmp ($res, "VERIFIED") == 0) {
		
		$ip = strip_tags($_SERVER['REMOTE_ADDR']);
		if (strlen($ip) > 16) $ip = "";
		
		$query = $wpdb->prepare("INSERT INTO t4s_paypal_payments (date_submitted, ip_address, data, gateway) VALUES ('".date('Y-m-d H:i:s')."', '%s', '%s', 'Paypal')", $ip, json_encode($_POST));
		
		$result = mysql_query($query);
		
		if (!$result) {
			sendEmailToT4SAdmins("T4S Plugin Error: Unable to Record a Payment!", "[ERROR PPL/IPN 003] T4S Plugin was unable to connect to the database to record a payment. A payment made to you was not properly recorded. ", $_POST);
			exit();
		}
		
		if (mysql_errno()) {
			sendEmailToT4SAdmins("T4S Plugin Error: Unable to Record Successful PayPal Payment!", "[ERROR PPL/IPN 002] T4S Plugin was unable to update the status of a payment. A payment you received was not recorded properly. ", $_POST);
			exit();
		}
	  
		
		$body = "
		Item(s) Sold: ".$item_name."
		Payment Status: ".$payment_status."
		Total Paid: ".$payment_amount."
		Payer's Email: ".$payer_email."
		
		For more information, please log in to your Paypal account.";
		
		$subject = "Payment Completed: Tools 4 Shuls Wordpress Plugin Notification";
		$headers = "From: support@tools4shuls.com";
		
		$admin_email = get_option('admin_email');
		
		if ($admin_email != $receiver_email) {
			$receiver_email .= ",".$admin_email;
		}
		
		try {
			$client_email = getT4SSettings('t4s_login');
			if ($client_email != $admin_email && $client_email != $receiver_email) {
				$receiver_email .= ",".$client_email;
			}
		} catch(Exception $e) {
		}
		
		mail($receiver_email, $subject, $body, $headers);

	} else if (strcmp ($res, "INVALID") == 0) {
		sendEmailToT4SAdmins("T4S Plugin Error: Unable to Verify PayPal Payment!", "[ERROR PPL/IPN 001] T4S Plugin was unable to verify a processed payment. This may be a result of an error or a fraud attempt. Please review your PayPal logs and take appropriate action. ", $_POST);
		exit();
	}

	exit();
}
?>