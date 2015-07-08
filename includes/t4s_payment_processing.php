<?php


function grab_T4Sform($atts){

	global $wpdb;	
	$prefix = $wpdb->prefix;

	$settings = getT4SSettings();
	
	$paypal = $settings['t4s_paypal_email'];
	$auth_id = $settings['t4s_auth_id'];
	$auth_key = $settings['t4s_auth_key'];
	$gateway = $settings['t4s_payment_processor'];
	
	
	//1. DETECT SUBMISSION OF AUTH.NET FORM
	
	//RECORD AUTH.NET PAYMENTS
	if ($_POST['x_login'] != '') {

		$fid = intval($_POST['form_id']);
		$ip = strip_tags($_SERVER['REMOTE_ADDR']);
		if (strlen($ip) > 16) $ip = "0";
	
		$query = $wpdb->prepare("INSERT INTO t4s_forms_submissions (data, date_submitted, ip, form_id) VALUES ('%s', '".date('Y-m-d H:i:s')."', '%s', '%s')", json_encode($_POST), $ip,	$fid);
		
		mysql_query($query) or die("Internal error. Please contact us for assistance.");
		
		$id = mysql_insert_id();
		
		//IF USER-DEFINED PMT, GET PROPER AUTH HASH
		if (isset($_POST['price'])) {	
		
			require('lib/Authorize/AuthorizeNet.php'); 
			$api_login_id = $auth_id;
			$transaction_key = $auth_key;
			$amount = floatval($_POST['price'].".".$_POST['price2']);
			$fp_timestamp = time();
			$fp_sequence = "T4S" . time(); 
			$fingerprint = AuthorizeNetSIM_Form::getFingerprint($api_login_id, $transaction_key, $amount, $fp_sequence, $fp_timestamp);
			
		} else {
		
			$fingerprint = esc_html($_POST['x_fp_hash']);
			if (strlen($fingerprint) > 1024) $fingerprint = "";
			
			$fp_sequence = esc_html($_POST['x_fp_sequence']);
			if (strlen($fp_sequence) > 1024) $fp_sequence = "";
			
			$fp_timestamp = esc_html($_POST['x_fp_timestamp']);
			if (strlen($fp_timestamp) > 1024) $fp_timestamp = "";
			
			$amount = floatval($_POST['x_amount']);
						
		}
		
		$login = esc_html($_POST['x_login']);
		if (strlen($login) > 128) $login = "";
		
		$desc = esc_html($_POST['x_login']);
		if (strlen($desc) > 128) $desc = "";
		
		$invoice = esc_html($_POST['x_invoice_num']);
		if (strlen($invoice) > 128) $invoice = "";
		
		?>

		<form method='post' id='t4s_form' action="https://secure.authorize.net/gateway/transact.dll">
			<input type='hidden' name="x_login" value="<?php echo $login; ?>" />
			<input type='hidden' name="x_fp_hash" value="<?php echo $fingerprint; ?>" />
			<input type='hidden' name="x_description" value="<?php echo $desc; ?>" />
			<input type='hidden' name="x_amount" value="<?php echo $amount; ?>" />
			<input type='hidden' name="x_fp_timestamp" value="<?php echo $fp_timestamp; ?>" />
			<input type='hidden' name="x_fp_sequence" value="<?php echo $fp_sequence; ?>" />
			<input type='hidden' name="x_version" value="3.1">
			<input type='hidden' name="x_show_form" value="payment_form">
			<input type='hidden' name="x_test_request" value="true" />
			<input type='hidden' name="x_method" value="cc">
			<input type='hidden' name='x_custom' value='<?php echo $id; ?>'>
			<input type='hidden' name='custom' value='<?php echo $id; ?>-<?php echo $id; ?>'>
			<input type='hidden' name='x_invoice_num' value='<?php echo $invoice; ?>'>
			<input type="hidden" name="x_relay_url" value="<?php echo admin_url( 'admin-ajax.php' ).'?action=t4s_auth_ipn_callback'; ?>" >
			
			Redirecting to Authorize.NET... If you are not redirected within 5 seconds, please <input type="submit" value="Click here" style="display: inline">.
			
		</form>
		
		<script type="text/javascript">
			document.forms['t4s_form'].submit();
		</script>
		
		<?php
		
		exit();
		
	}

	//2. DETECT SUBMISSION OF PAYPAL FORM
	
	//RECORD PAYPAL PAYMENTS
	if ($_POST['cmd'] == '_xclick') {

		$fid = intval($_POST['form_id']);
		
		$business = $_POST['business'];
		if ( strlen($business) > 128 ) $business = "";
		
		$item_name = $_POST['item_name'];
		if ( strlen($item_name) > 128 ) $item_name = "";
		
		$amount = floatval($_POST['amount']);
		
		$return = $_POST['return'];
		if ( strlen($return) > 128 ) $return = "";
		
		$custom = $_POST['custom'];
		if ( strlen($custom) > 128 ) $custom = "";
	
		$ip = strip_tags($_SERVER['REMOTE_ADDR']);
		if (strlen($ip) > 16) $ip = "0";
	
		$query = $wpdb->prepare("INSERT INTO t4s_forms_submissions (data, date_submitted, ip, form_id) VALUES ('%s', '".date('Y-m-d H:i:s')."', '%s', '%s')", json_encode($_POST), $ip,	$fid);
		
		mysql_query($query) or die("Internal error. Please contact us for further assistance.");
		
		$id = mysql_insert_id();

		?>

			<form name="t4s_form" action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="<?php echo $business; ?>">
				<input type="hidden" name="currency_code" value="USD">
				<input type="hidden" name="item_name" value="<?php echo $item_name; ?>">
				<input type="hidden" name="amount" value="<?php echo $amount; ?>">
				<input type="hidden" name="notify_url" value="<?php echo admin_url( 'admin-ajax.php' ).'?action=t4s_ppl_ipn_callback'; ?>">
				<input type="hidden" name="return" value="<?php echo $return; ?>">
				<input type="hidden" name="custom" value="<?php echo $custom; ?>-<?php echo $id; ?>">
			Redirecting to PayPal... If you are not redirected within 5 seconds, please <input type="submit" value="Click here" style="display: inline">.
			
		</form>
		
		<script type="text/javascript">
			document.forms['t4s_form'].submit();
		</script>
		
		<?php
		
		exit();
	}

	//3. DETECT SUBMISSION OF NO-PAYMENT FORM

	//RECORD NO-PAYMENT-REQUIRED SUBMISSIONS
	if ($_POST['nopmt'] == 'true') {
	
		$fid = intval($_POST['form_id']);
		
		$thankyou = strip_tags($_POST['thankyou']);
		if ( strlen($thankyou) > 128 ) $thankyou = "";

		$ip = strip_tags($_SERVER['REMOTE_ADDR']);
		if (strlen($ip) > 16) $ip = "0";
		
		$query = $wpdb->prepare("INSERT INTO t4s_forms_submissions (data, date_submitted, ip, form_id) VALUES ('%s', '".date('Y-m-d H:i:s')."', '%s', '%s')", json_encode($_POST), $ip, $fid);
		
		mysql_query($query) or die("Internal error. Please contact us for assistance.");
		
		if ($_POST['thankyou'] != '') {
			echo "<script type='text/javascript'> window.location = '".$thankyou."'; </script>";
		} else {
			echo "Thank you! <a href='#' onClick='history.back()'>Go back</a>";
		}
		
		exit();

	}

	//4. NO SUBMISSION: DISPLAY FORM
	
	extract( shortcode_atts( array('formid' => ''), $atts ) );	
				
	$fid = intval($atts['formid']);
				
	$sql2 = $wpdb->prepare("SELECT * FROM t4s_forms WHERE id = '%d'", $fid);

	$result2 = mysql_query($sql2);
	$row2 = mysql_fetch_assoc($result2);
	
	$desc = $row2['description'];
	$price = $row2['price'];
	$return = str_replace("http://http://", "http://", "http://".$row2['thank_you']);
	
	$sql = $wpdb->prepare("SELECT * FROM t4s_forms_fields WHERE t4s_forms_Id='%d' ORDER BY ordernum ASC", $fid);

	$result = mysql_query($sql);

	if (mysql_num_rows($result) > 0) {
		$preview .= $desc."<br>";
		$preview .= "<table>";
		while($row=mysql_fetch_array($result)){
			$varg=$row['name'];
			$options='';
			if($row['name'] == 'textbox' ){ 
				$preview .= "<tr><td>".stripslashes($row['label'])."</td><td><input type='textbox' name='cv_".$row['id']."'></td></tr>";
			} else if($row['name'] == 'textarea' ){ 
				$preview .= "<tr><td>".stripslashes($row['label'])."</td><td><textarea name='cv_".$row['id']."'></textarea></td></tr>";
			} else if($row['name'] == 'checkbox' ){ 
				$preview .= "<tr><td>".stripslashes($row['label'])."</td><td><input type='checkbox' name='cv_".$row['id']."'></td></tr>";
			} else if($row['name'] == 'select' ){ 
				$preview .= "<tr><td>".stripslashes($row['label'])."</td><td><select name='cv_".$row['id']."'>";
				$preview .= generateOptions($row['id'], 'select');
				$preview .= $row['label']." </select></td></tr>";
			} else if($row['name'] == 'radio' ){ 
				$preview .= "<tr><td>".stripslashes($row['label'])."</td><td>";	
				$preview .= generateOptions($row['id'], 'radio');
				$preview .= "</td></tr>";
			}
		}
		$preview .= "</table>";
	}
	
	$nopmt = false;
	if ($row2['pmt_type'] == 'fixed') {
		$preview .= "<strong>Total Due: $".money_format('%i', $price)."</strong><br>";
	} else if ($row2['pmt_type'] == 'user') {
		$preview .= "<strong>Please specify how much you would like to pay: <b>$</b><input type='text' name='price' id='price'  value='0' size=6 onChange='checkNumeric(this.id)'>.<input type='text' name='price2' id='price2'  value='00' size=2 onChange='checkNumeric(this.id)'> <br/><br/>
		
		<script type='text/javascript'>						
			function checkNumeric(id) {
				if (isNaN(document.getElementById(id).value)) {
					alert('Amount to Charge must be numeric!');
					document.getElementById(id).value = '';
				} else {
					if (document.getElementById('amount')) {
						document.getElementById('amount').value = document.getElementById('price').value + '.'+ document.getElementById('price2').value;
					} else {
						document.getElementById('x_amount').value = document.getElementById('price').value + '.' + document.getElementById('price2').value;
					}
				}
			}
		</script>";
	} else {
		$preview .= "<strong>No payment is necessary</strong><br/><br/><input type='submit' value='Finish'>";
		$nopmt = true;
	}

	if ($gateway == 2 && !$nopmt) {
	
		$api_login_id = $auth_id;
		$transaction_key = $auth_key;
		$amount = $price;
		$fp_timestamp = time();
		$fp_sequence = "T4S" . time(); // Enter an invoice or other unique number.
		$fingerprint = AuthorizeNetSIM_Form::getFingerprint($api_login_id, $transaction_key, $amount, $fp_sequence, $fp_timestamp);
		
		
		$query = $wpdb->prepare("INSERT INTO t4s_authorize_payments (form_id, description, amount) VALUES ('%d', '%s', '%s')", $fid, $desc, $amount);
		
		mysql_query($query) or die("An error had occured. Please contact us for further assistance.");
		
		$invoice = mysql_insert_id();
		if ($api_login_id == '') {
			echo "Payment gateway must be specified and credentials entered before this form could be displayed.";
		} else {
		
		?>
		
		<form method='post'>
			
			<?php echo $preview; ?>
			
			<input type='hidden' name="x_login" value="<?php echo $api_login_id; ?>" />		
			<input type='hidden' name="x_description" value="<?php echo $desc; ?>" />
			<?php if ($row2['pmt_type'] != 'user') { ?>
				<input type='hidden' id="x_amount" name="x_amount" value="<?php echo $amount; ?>" />
				<input type='hidden' name="x_fp_hash" value="<?php echo $fingerprint; ?>" />
				<input type='hidden' name="x_fp_timestamp" value="<?php echo $fp_timestamp; ?>" />
				<input type='hidden' name="x_fp_sequence" value="<?php echo $fp_sequence; ?>" />
			<?php } ?>			
			<input type='hidden' name="x_version" value="3.1">
			<input type='hidden' name="x_show_form" value="payment_form">
			<input type='hidden' name="x_test_request" value="true" />
			<input type='hidden' name="x_method" value="cc">
			<input type="hidden" name="custom" value="<?php echo $fid; ?>">
			<input type='hidden' name='x_invoice_num' value='<?php echo $invoice; ?>'>
			<input type="hidden" name="x_relay_url" value="<?php admin_url( 'admin-ajax.php' ).'?action=t4s_auth_ipn_callback'; ?>" >
			<input type='submit' value="Checkout">
		</form>
	
	<?php
		}
	} else if (!$nopmt) {
		//DISPLAY PAYPAL FORM				
		?>
		
		<form name="_xclick" method="post">
		
			<?php echo $preview; ?>
		
			<input type="hidden" name="cmd" value="_xclick">
			<input type="hidden" name="business" value="<?php echo $paypal; ?>">
			<input type="hidden" name="currency_code" value="USD">
			<input type="hidden" name="item_name" value="<?php echo $desc; ?>">
			<input type="hidden" id="amount" name="amount" value="<?php echo $price; ?>">
			<input type="hidden" name="notify_url" value="<?php echo admin_url( 'admin-ajax.php' ).'?action=t4s_ppl_ipn_callback'; ?>">
			<?php if ($return != "") { ?> <input type="hidden" name="return" value="<?php echo $return; ?>"> <?php } ?>
			<input type="hidden" name="custom" value="<?php echo $fid; ?>">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
		</form>
		
		<?php
	
	} else {
		//DISPLAY NO-PAYMENT FORM
		?>
		
		<form name="_xclick" method="post">	
			<?php echo $preview; ?>
			<input type="hidden" name='nopmt' value="true">
			<input type="hidden" name='thankyou' value="<?php echo $return; ?>">
			<input type="hidden" name='form_id' value="<?php echo $fid; ?>">
		</form>
		
		<?php
		
	}
	
}



function generateOptions($id, $type) {

	$query = $wpdb->prepare("SELECT * FROM t4s_forms_options WHERE test_fields_id = '%s' ORDER BY ordernum ASC", $id);
	$result = mysql_query($query) or die(mysql_error());
	
	for ($i=0;$i<mysql_num_rows($result);$i++) {
		$row = mysql_fetch_assoc($result);
		if ($type == 'select') {
			$output .= "<option value='".$row['description']."'>".stripslashes($row['description'])."</option>";
		} else {
			$output .= "<input type='radio' name='cv_".$id."' value='".$row['description']."'> ".stripslashes($row['description']);
		}
	}
	
	return $output;

}



function getPaymentRecords($id = null, $limit = null) {

	global $wpdb;

	$records = array();
	
	//get all paypal payments
	if ($id != null) {
		$query = $wpdb->prepare("SELECT * FROM t4s_paypal_payments WHERE data LIKE '!,\"custom\":\"%d-!'", $id);
	} else {
		$query = "SELECT * FROM t4s_paypal_payments";
	}
	
	$query = str_replace("!", "%", $query);
	$result = mysql_query($query);	
		
	for ($i=0;$i<mysql_num_rows($result);$i++) { 
		$row = mysql_fetch_assoc($result);
		$records[] = json_decode($row['data']);		
	}
	
	//get all authorize payments
	if ($id != null) {
		$query2 = $wpdb->prepare("SELECT * FROM t4s_authorize_payments WHERE form_id = '%d' AND status = 1", $id);
	} else {
		$query2 = "SELECT * FROM t4s_authorize_payments WHERE status = 1";
	}	
	
	$result2 = mysql_query($query2);
	for ($i=0;$i<mysql_num_rows($result2);$i++) {
		$row = mysql_fetch_assoc($result2);
		$records[] = json_decode($row['response']);		
	}
		
	//get all no-payment-necessary submissions		
	if ($id) {
	
		$query3 = $wpdb->prepare("SELECT * FROM t4s_forms_submissions WHERE form_id = '%s'", $id);
	
		$result3 = mysql_query($query3);
		for ($i=0;$i<mysql_num_rows($result3);$i++) {
			$row = mysql_fetch_assoc($result3);
			$records_free[] = $row;		
		}
		
   } else {
	 
		$query3 = "SELECT * FROM t4s_forms_submissions ORDER BY id DESC";
	
		$result3 = mysql_query($query3);
		for ($i=0;$i<mysql_num_rows($result3);$i++) {
			$row = mysql_fetch_assoc($result3);
			$x = json_decode($row['data']);
			if ($x->nopmt == true) {
				$records_free[] = $row;	
			}
		}
	 
	 }
	 
	//process records and sort by date	
	$final = array();
	foreach ($records as $rec) {
	
		$custom = explode("-", $rec->custom);
		$q = $wpdb->prepare("SELECT * FROM t4s_forms_submissions WHERE id = '%s'", $custom[1]);
		$r = mysql_query($q);
		$row_temp = mysql_fetch_assoc($r);
		$row_temp['first_name'] = $rec->first_name;
		$row_temp['last_name'] = $rec->last_name;
		
		if ($rec->first_name == "" && $rec->last_name == "") {
			$row_temp['first_name'] = $rec->x_first_name;
			$row_temp['last_name'] = $rec->x_last_name;
		}

		$final[] = $row_temp;
		
	}
	
	
	if ($records_free) {
		foreach ($records_free as $rec) {
			if ($limit) {
				if (count($final) < 5) {
					$final[] = $rec;		
				}
			} else {
				$final[] = $rec;
			}
		}
	}
	
	usort($final, 'sortByOrder');

	return $final;

}


function displayPaymentRecords($final, $title) {

	global $wpdb;

	$ignore_list = array('cmd', 'business', 'currency_code', 'notify_url', 'return', 'custom', 'submit_x', 'submit_y', 'x_login', 'x_fp_hash', 'x_fp_timestamp', 'x_fp_sequence', 'x_version', 'x_show_form', 'x_test_request', 'x_method', 'x_invoice_num', 'x_relay_url', 'form_id', 'thankyou', 'nopmt');

	echo "<br/><h2>".$title."</h2><br>";
	
	if (isset($_GET['form'])) {
		echo "<a href='".admin_url( 'admin.php?page=t4s-form-payments')."'>[ Go Back ]</a><br/><br/>";
	}

	foreach ($final as $f) {
		$gateway = "";
		$output = "";
		$form_name = "";
		
		$arr = explode(" ", $f['date_submitted']);
		$dt = date('F jS, Y', strtotime($arr[0]));
		$data = json_decode($f['data']);
		
		foreach ($data as $key => $value) {
			if (!in_array($key, $ignore_list)) {
				
				$fd = false;
				if (strpos($key,'cv_') !== false) {
					$fd = true;
				}
				$term = str_replace("cv_", "", $key);
				$query = $wpdb->prepare("SELECT label FROM t4s_forms_fields WHERE id = '%s'", $term);
				$res = mysql_query($query);
				$rname = mysql_fetch_assoc($res);
				if ($key == 'amount' || $key == 'x_amount') {
					$output .= "<b>Paid</b> ";
					if ($key == 'amount') {
						$gateway = 'PayPal';
					} else {
						$gateway = 'Authorize.NET';
					}
				} else if ($key == 'item_name' || $key == 'x_description') {
					$query = $wpdb->prepare("SELECT * FROM t4s_forms WHERE id = '%s'", stripslashes($data->custom));					
					$result = mysql_query($query);
					$row = mysql_fetch_assoc($result);
					$form_name = "<b>Form Name:</b> ".stripslashes($row['name'])."<br/><b>Full Name: </b>".stripslashes($f['first_name'])." ".stripslashes($f['last_name'])."<br/>";
				} else {
					if (!$fd || ($fd && $_GET['page'] != 't4s-form-creator')) $output .= "<b>".stripslashes($rname['label'])."</b>";
				}
				if ($key != 'item_name' && $key != 'x_description' && $key != 'price' && $key != 'price2') {
					if (!$fd || ($fd && $_GET['page'] != 't4s-form-creator')) $output .= "</b>: ".stripslashes($value)."<br/>";
				}
				if ($key == 'price' && $gateway != 'PayPal') {
					if (!$fd || ($fd && $_GET['page'] != 't4s-form-creator')) $output .= "<b>Paid</b>: ".$data->price.".".$data->price2."<br/>";
				}
			} else if ($key == 'form_id' && $data->nopmt == 'true') {
					$query = $wpdb->prepare("SELECT * FROM t4s_forms WHERE id = '%s'", stripslashes($value));					
					$result = mysql_query($query);
					$row = mysql_fetch_assoc($result);
					$form_name = "<b>Form Name:</b> ".$row['name']."<br/>";
			}
		}
		
		$output .= "<br><br>";
		
		echo $form_name.$output;
	}
	
	if (isset($_GET['form'])) {
		echo "<a href='".admin_url( 'admin.php?page=t4s-form-payments')."'>[ Go Back ]</a>";
	}


}


function returnPaymentRecords($final) {

	global $wpdb;
	
	$ignore_list = array('cmd', 'business', 'currency_code', 'notify_url', 'return', 'custom', 'submit_x', 'submit_y', 'x_login', 'x_fp_hash', 'x_fp_timestamp', 'x_fp_sequence', 'x_version', 'x_show_form', 'x_test_request', 'x_method', 'x_invoice_num', 'x_relay_url', 'form_id', 'thankyou', 'nopmt');
	
	$results = array();
	
	foreach ($final as $f) {
		$gateway = "";
		$output = "";
		$form_name = "";
		
		$arr = explode(" ", $f['date_submitted']);
		$dt = date('F jS, Y', strtotime($arr[0]));
		
		//RES date
		$res_date = $dt;
		
		$data = json_decode($f['data']);
		
		foreach ($data as $key => $value) {
			if (!in_array($key, $ignore_list)) {
				
				$fd = false;
				if (strpos($key,'cv_') !== false) {
					$fd = true;
				}
				$term = str_replace("cv_", "", $key);
				$query = $wpdb->prepare("SELECT label FROM t4s_forms_fields WHERE id = '%s'", $term);
				$res = mysql_query($query);
				$rname = mysql_fetch_assoc($res);
				//USER-DEFINED PAYMENT WILL NOT HAVE amount OR x_amount => WORK WITH PRICE
				if ($key == 'amount' || $key == 'x_amount' || $key == 'price') {					
					if ($key == 'amount') {
						$gateway = 'PayPal';
					} else if ($key == 'x_amount') {
						$gateway = 'Authorize.NET';
					} else {
						if ($data->x_relay_url) {
							$gateway = 'Authorize.NET';
							$key = 'x_amount';
							$value = $data->price.".".$data->price2;
							$temp = array($key, $res_gateway, $res_form, $res_name, $value, $rname['label'], $res_date);
							$result[] = $temp;
							continue;
						} else {
							$gateway = 'PayPal';
							$key = 'amount';
							$value = $data->price.".".$data->price2;
							$temp = array($key, $res_gateway, $res_form, $res_name, $value, $rname['label'], $res_date);
							$result[] = $temp;
							continue;
						}
					}
					//RES Gateway
					$res_gateway = $gateway;
				} else if ($key == 'item_name' || $key == 'x_description') {
					//RES Form Name
					$res_form = $value;
					//RES Name
					$res_name = $f['first_name']." ".$f['last_name'];
				} else {
					if (!$fd || ($fd && $_GET['page'] != 't4s-form-creator')) $output = $rname['label'];
				}
				if ($key != 'item_name' && $key != 'x_description') {
					if (!$fd || ($fd && $_GET['page'] != 't4s-form-creator')) $output = $value;
				}
				
				$temp = array($key, $res_gateway, $res_form, $res_name, $output, $rname['label'], $res_date);
				$result[] = $temp;
				
			}
			
		}
		
		if (count($result) > 0) {
			$results[] = $result;
			$result = null;
		}
		
	}	
	
	return $results;

}

?>