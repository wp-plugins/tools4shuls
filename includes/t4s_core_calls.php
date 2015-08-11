<?php

function query_T4S() {
		
	global $wpdb;
	$is_login = false;
			
	$t4s_creds = array();
	
	//DETECT CREDENTIALS UPDATE
	if (isset($_POST['t4s_login']) && $_POST['t4s_login'] != '') {
	
		$login = strip_tags($_POST['t4s_login']);
		if (strlen($login) > 128) $login = "";
		
		$pwd = strip_tags($_POST['t4s_pwd']);
		if (strlen($pwd) > 128) $pwd = "";
		
		$query = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_login'", $login);
		mysql_query($query);
		$query = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_password'", sha1(sha1(md5(trim($pwd)))));
		mysql_query($query);
		
	}
	
	//GET STORED LOGIN INFO
	$t4s_settings = getT4SSettings();
	$t4s_creds['login'] = $t4s_settings['t4s_login'];
	$t4s_creds['password'] = $t4s_settings['t4s_password'];

	
	//IF LOGIN OR PASSWORD ARE MISSING, DISPLAY CREDENTIALS FORM
	if (($t4s_creds['login'] == '') || ($t4s_creds['password'] == '')) {
		include("t4s_credentials_form.php");	
		exit();
	} else {		
		
		$settings = array();
		
		if (!isset($_GET['t4spage']) && !isset($_GET['date']) && !isset($_GET['year']) && !isset($_GET['s_keyword'])) {
			$url = "https://t4s.inspiredonline.com/manage/login";		
			$settings["loginEmail"] = $t4s_creds['login']; 
			$settings["loginPassword"] = $t4s_creds['password'];
			$settings["submitLogin"] = 'Log In';	
			$settings["rememberLogin"] = 'on';	
			$settings["plugin-login"] = "1";
			$settings = http_build_query($settings);
			$is_login = true;
		} else if (isset($_GET['s_keyword'])) {
		
			$keyword = strip_tags($_GET['s_keyword']);
			if (strlen($keyword) > 128) $keyword = "";
			
			$category = strip_tags($_GET['s_category']);
			if (strlen($category) > 128) $category = "";
			
			$start_date = strip_tags($_GET['s_start_date']);
			if (strlen($start_date) > 32) $start_date = "";
			
			$end_date = strip_tags($_GET['s_end_date']);
			if (strlen($end_date) > 32) $end_date = "";
			
			$njc = strip_tags($_GET['s_njc']);
			if (strlen($njc) > 32) $njc = "";
			
			$x = strip_tags($_GET['x']);
			if (strlen($x) > 32) $x = "";
			
			$y = strip_tags($_GET['y']);
			if (strlen($y) > 32) $y = "";
			
			$t4spage = "calendar/list?s_keyword=".$keyword."&s_category=".$category."&embed=1&s_start_date=".$start_date."&s_end_date=".$end_date."&s_njc=".$njc."&x=".$x."&y=".$y;
			$url = "https://t4s.inspiredonline.com/manage/".$t4spage;
		} else if (isset($_GET['date']) && isset($_GET['t4scat'])) {
		
			$dte = strip_tags($_GET['date']);
			if (strlen($dte) > 32) $dte = "";
			
			$t4scat = strip_tags($_GET['t4scat']);
			if (strlen($t4scat) > 32) $t4scat = "";
			
			$x = strip_tags($_GET['x']);
			if (strlen($x) > 32) $x = "";
			
			$y = strip_tags($_GET['y']);
			if (strlen($y) > 32) $y = "";
		
			$t4spage = "calendar/month?date=".$dte."&t4scat=".$t4scat."&embed=1&x=".$x."&y=".$y;
			$url = "https://t4s.inspiredonline.com/manage/".$t4spage;
			
		} else if (isset($_GET['month']) && isset($_GET['year'])) {
		
			$month = strip_tags($_GET['month']);
			if (strlen($month) > 24) $month = "";
			
			$year = intval($_GET['year']);
			
			$x = strip_tags($_GET['x']);
			if (strlen($x) > 32) $x = "";
			
			$y = strip_tags($_GET['y']);
			if (strlen($y) > 32) $y = "";
		
			$t4spage = "calendar/month?month=".$month."&year=".$year."&x=".$x."&y=".$y;
			$url = "https://t4s.inspiredonline.com/manage/".$t4spage;
			
		} else {
		
			$t4spage = strip_tags($_GET['t4spage']);
			if (strlen($t4spage) > 64) $t4spage = "";
			$t4spage = str_replace('\"', '', $t4spage);
			$t4spage = str_replace('./', '/', $t4spage);
			
			//custom cases for calendar top menu buttons	
			if ((strncmp($t4spage, 'month', 5)==0) || (strncmp($t4spage, 'edit?event', 10)==0) || (strncmp($t4spage, 'event', 5)==0) || (strncmp($t4spage, 'options', 7)==0 )|| (strncmp($t4spage, 'day', 3)==0) || ($t4spage == 'options') || ($t4spage == 'list') || (strncmp($t4spage, 'edit?form',9)==0) || (strncmp($t4spage, 'edit?dup',8)==0) ) {
				$t4spage = "/calendar/".$t4spage;
			}
			
			if ((strncmp($t4spage, 'campaign', 8)==0) || (strncmp($t4spage, 'edit-campaign', 13)==0) || (strncmp($t4spage, 'list?p=1', 8)==0)) {
				$t4spage = "/donations/".$t4spage;
			}
				
			//custom case for category dropdown select
			if ((strncmp($t4spage, 'date', 4)==0)) {
				$t4spage = "/calendar/".$t4spage;
			}
							
			if (substr($t4spage, 0, 1) == "/") {
				$t4spage = substr($t4spage, 1, strlen($t4spage)-1);
			}
						
			if ($t4spage == 'edit') {
				$t4spage = "calendar/edit";
			}
						
			if (strpos($t4spage,'https://t4s.inspiredonline.com') === false) {
				$url = "https://t4s.inspiredonline.com/manage/".$t4spage;
			} else {
				$url = $t4spage;
			}
							
			foreach ($_POST as $k => $v) {
			
				$k = strip_tags($k);
				$v = strip_tags($v);
				if (strlen($k) > 64) $k = "";
				if (strlen($v) > 128) $v = "";
			
				$settings[$k] = $v;
			}
						
			foreach ($_FILES as $k => $v) {
			
				$k = strip_tags($k);
				$v = strip_tags($v);
				if (strlen($k) > 64) $k = "";
				if (strlen($v) > 256) $v = "";
			
				$settings[$k] = '@'.$v['tmp_name'];
			}
			

			$settings['dummy'] = 'entry';
			if (isset($settings['import_cal'])) {
				$settings['import_url'] = $settings['import_cal'];
			}
			if (count($settings) > 1) {

				if (!isset($_POST['import_url'])) {
					$settings = http_build_query($settings);
				}

			}
		}
		

		$url = urldecode($url);

		if ($_GET['del'] == '1') {
			$url .= "&del=1";
		}
		
		if (isset($_GET['month'])) {
		
			$month = strip_tags($_GET['month']);
			if (strlen($month) > 24) $month = "";
		
			$url .= "&month=".$month;
		}
		
		if (isset($_GET['year'])) {
		
			$year = intval($_GET['year']);
		
			$url .= "&year=".$year;
		}
		
		if (isset($_GET['d'])) {
		
			$d = intval($_GET['d']);
		
			$url .= "&d=".$d;
		}
		
		if (isset($_GET['c'])) {
		
			$c = intval($_GET['c']);
		
			$url .= "&c=".$c;
		}
		
		if (isset($_GET['g'])) {
		
			$g = intval($_GET['g']);
		
			$url .= "&g=".$g;
		}
		
		if (isset($_GET['delete_campaign'])) {
			$delc = intval($_GET['delete_campaign']);
			$url .= "?delete_campaign=".$delc;
		}
		
		if (isset($_GET['action'])) {
			if ($_GET['action'] == 'rsvp_open' || $_GET['action'] == 'rsvp_close') {
				if (strpos($url,'?') !== false) {
					$url .= "&action=".$_GET['action'];
				} else {
					$url .= "?action=".$_GET['action'];
				}
			} 
		}
		
		if (isset($_GET['event_id'])) {
			$eid = intval($_GET['event_id']);
			$url .= "&event_id=".$eid;
		}
		
		
		
		//custom case for event search /calendar
		if (isset($_GET['s_keyword'])) {
			$regexp = "/s_start_date=([^\"]*)&s_njc/siU";
			$matches = array();
			$result = preg_match_all($regexp, $url, $matches);	
			$s_start = urlencode($matches[0][0]);		
			$s_start = str_replace("%3D", "=", $s_start);
			$s_start = str_replace("%26", "&", $s_start);						
			$url = str_replace($matches[0][0], $s_start, $url);
		}	
			
		
		$url = str_replace("/manage/manage/", "/manage/", $url);
		$url = str_replace("/login.php", "plugin-login.php", $url);
		
		
		$ch = curl_init($url);
		
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $settings);	
		
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"); 
		curl_setopt ($ch, CURLOPT_TIMEOUT, 60); 	
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1); 
		
		
		if (current_user_can('manage_options')) {
			getT4Scookie();
		}
		

		$ch = setT4SCookie($ch);
		
		curl_setopt ($ch, CURLOPT_REFERER, $url); 
			
		$response = curl_exec($ch);

		updateT4SCookie();
				
		$uri = $_SERVER['REQUEST_URI'];
		if (strlen($uri) > 256) $uri = "";
		
		$host = $_SERVER['HTTP_HOST'];
		if (strlen($host) > 128) $host = "";
				
		$uri = explode("?", $uri);
		$url = 'https://'.$host.''.$uri[0].'?page=t4s&t4spage=';	
		
		$counter = 0;		
		
		if (strpos($response,'Login Failed') !== false) {
			echo "<br><br><h2>LOGIN FAILED: YOUR T4S CREDENTIALS ARE INVALID</h2><br>";
			include("t4s_credentials_form.php");
			exit();
		} else {
						
			$response = processT4SResponse($response);		
			
			$response_orig = $response;
						
			if ($is_login) {			
				updateT4SSettings();				
			}
			
		}
		
		$counter = 0;
				
		while (strpos($response,'Keep me logged in') !== false) {
			$response = curl_exec($ch);
			$counter++;
			if ($counter == 3) {
				if (!isset($_GET['reauth'])) {
					echo "Unable to authenticate. Attempting to log in...
							<script type='text/javascript'>window.location = '".admin_url( 'admin.php?page=t4s&reauth=1')."';</script>
					";
					exit();
				} else if ($_GET['reauth'] == '1') {
					echo "Unable to authenticate. Final attempt to log in...
							<script type='text/javascript'>window.location = '".admin_url( 'admin.php?page=t4s&reauth=2')."';</script>
					";
					exit();
				} else {
					echo "Unable to authenticate.";
					include("includes/t4s_credentials_form.php");
					exit();
				}
			}
		} 
		curl_close($ch);
		
		
		if (isset($_GET['reauth'])) {
			echo "<script type='text/javascript'>window.location = '".admin_url( 'admin.php?page=t4s')."';</script>";
			exit();
		}
		
						
		return $response_orig;
		
	}
}


function updateT4SSettings() {

	global $wpdb;
		
	// GET THE CLIENT ID AND API KEY
	$url = "https://t4s.inspiredonline.com/_isuite/v/".getVersion()."/app_logic/manage/calendar/get_credentials.php";
	$ch = curl_init($url);
	$settings = http_build_query(Array());
	
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $settings);	
	
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
	curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"); 
	curl_setopt ($ch, CURLOPT_TIMEOUT, 60); 	
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1); 

	if (current_user_can('manage_options')) {
		getT4Scookie();
	} 
	
	$ch = setT4SCookie($ch);
	
	curl_setopt ($ch, CURLOPT_REFERER, $url); 
	
	$response = curl_exec($ch);
							
	$tmp = explode("|", $response);
	
	$userId = intval($tmp[0]);
	
	$apiKey = strip_tags($tmp[1]);
	if (strlen($apiKey) > 64) $apiKey = "";
	
	$subscr_id = intval($tmp[2]);
		
	$subscr_amount = floatval($tmp[3]);
	
	$subscr_date = strip_tags($tmp[4]);
	if (strlen($subscr_date) > 24) $subscr_date = "";
	
	$pmt = floatval($tmp[5]);
	
	$auth_id = strip_tags($tmp[6]);
	if (strlen($auth_id) > 36) $auth_id = "";
	
	$auth_key = strip_tags($tmp[7]);
	if (strlen($auth_key) > 36) $auth_key = "";
	
	$ppl =  strip_tags($tmp[8]);
	if (strlen($ppl) > 48) $ppl = "";
	
	$md5 =  strip_tags($tmp[9]);
	if (strlen($md5) > 64) $md5 = "";
	
	$calURL =  strip_tags($tmp[10]);
	if (strlen($calURL) > 256) $calURL = "";
				
	$query3 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_clientID'", $userId);
	$query4 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_apiKey'", $apiKey);
	$query5 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_subscr_id'", $subscr_id);
	$query6 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_subscr_amount'", $subscr_amount);
	$query7 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_subscr_date'", $subscr_date);
	$query8 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_payment_processor'", $pmt);
	$query9 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_auth_id'", $auth_id);
	$query10 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_auth_key'", $auth_key);
	$query11 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_paypal_email'", $ppl);
	$query12 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_auth_md5'", $md5);
	$query13 = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_cal_url'", $calURL);

	mysql_query($query3) or die("Unable to save your credentials."); 
	mysql_query($query4) or die("Unable to save your credentials.");
	mysql_query($query5) or die("Unable to save your credentials.");
	mysql_query($query6) or die("Unable to save your credentials.");
	mysql_query($query7) or die("Unable to save your credentials.");
	mysql_query($query8) or die("Unable to save your credentials.");
	mysql_query($query9) or die("Unable to save your credentials.");
	mysql_query($query10) or die("Unable to save your credentials.");
	mysql_query($query11) or die("Unable to save your credentials.");
	mysql_query($query12) or die("Unable to save your credentials.");
	mysql_query($query13) or die("Unable to save your credentials.");
	
}



function loadAllCSS($text) {

	$regexp = "/<link\s[^>]*href=\"([^\"]*)\"[^>]*>/siU";
	$matches = array();
	$result = preg_match_all($regexp, $text, $matches);

	$counter = 0;
	foreach ($matches[1] as $m) {
		$tmp = explode("&t4spage=", $m);
		
		$tst = explode(".css", $tmp[1]);
		$tmp[1] = $tst[0].".css";

		wp_register_style( 't4sCSS'.$counter, $tmp[1] );
		$counter++;
	}	
	
	for ($i=0;$i<=$counter;$i++) {
		wp_enqueue_style( 't4sCSS'.$i );
	}
	
	wp_register_style('t4sCorrectional', plugins_url( 'tools4shuls/css/correctional.css'));
	wp_enqueue_style('t4sCorrectional');
	
	echo "<script type='text/javascript'>
			var pluginsUrl = '".plugins_url('/tools4shuls/')."';
			";
		
	require('../wp-content/plugins/tools4shuls/js/correctional.js');
	
	echo "</script>";
	
	return $text;

}


function loadAllScripts($text) {

	$regexp = "/<script\s[^>]*src=\"([^\"]*)\"[^>]*>(.*)<\/script>/siU";
	$matches = array();
	$result = preg_match_all($regexp, $text, $matches);
	
	
	foreach ($matches[0] as $m) {
		if (strpos($m,'jquery') === false) {
			echo $m;
		} 
		
		if (strpos($m,'-ui-1.8.23')!== false) {
			echo $m;
		}
		
		if (strpos($m,'jquery-1.8.0.min.js')!== false) {
			echo $m;
		}			
	}
				
	return $text;

}


function stripHeadandBodyTags($text) {

	$begin  = strpos($text, "<body>")+6;
	$end = strrpos($text, "</body>");
	$diff = $end-$begin;
		
	$output = substr($text, $begin, $diff);
	
	$output .= '
			<script type="text/javascript">
				jQuery(function() {
					jQuery( "#datepicker" ).datepicker();
				});
			</script>';
	
	return $output;

}


function hideCertainLinks($text) {

	$text = str_replace("[logout]", "", $text);
	$text = str_replace('$(', 'jQuery(', $text);
	
	return $text;

}


function replaceAllLinks($text, $url) {

	$text = str_replace('href="', 'href="'.$url, $text);
	$text = str_replace("href='", "href='".$url, $text);
	
	$text = str_replace('location="', 'location="'.$url, $text);
	$text = str_replace("location='", "location='".$url, $text);
	
	$text = str_replace('location = "', 'location = "'.$url, $text);
	$text = str_replace("location = '", "location = '".$url, $text);
	
	$text = str_replace('url: "ajax?', 'url: "'.plugins_url( 'tools4shuls/api/t4s_plugin_ajax.php?'), $text);
	$text = str_replace("url: 'ajax?", "url: '".plugins_url( 'tools4shuls/api/t4s_plugin_ajax.php?'), $text);
			
	$text = str_replace('$.ajax', 'jQuery.ajax', $text);	

	$x = admin_url( 'options-general.php?page=t4s&t4spage=javascript: void(0)');
	$x2 = admin_url( 'options-general.php?page=t4s&t4spage=javascript:void(0);');
	$x3 = admin_url( 'options-general.php?page=t4s&t4spage=#');
		
	$text = str_replace($x, '#', $text);
	$text = str_replace($x2, '#', $text);
	$text = str_replace($x3, '#', $text);
	
	$text = str_replace('ui-state-active" href="#"', '<a class="ui-state-default" href="javascript: void(0)"', $text);
	
	$t4sp = strip_tags($_GET['t4spage']);
	if (strlen($t4sp) > 24) $t4sp = "";
	
	$xyz = urldecode($t4sp);
	
	if ((strncmp($xyz, 'donations', 9)==0) || (strncmp($xyz, 'campaign', 8)==0) || (strncmp($xyz, 'edit-campaign', 13)==0) || (strncmp($xyz, 'list?p=1', 8)==0)) {
		$text = str_replace('t4spage=summary', 't4spage=donations/summary', $text);
		$text = str_replace('t4spage=list', 't4spage=donations/list', $text);
		$text = str_replace('t4spage=funds', 't4spage=donations/funds', $text);
		$text = str_replace('t4spage=options', 't4spage=donations/options', $text);
		$text = str_replace('t4spage=campaigns', 't4spage=donations/campaigns', $text);
		$text = str_replace('t4spage=create-category', 't4spage=donations/create-category', $text);
		$text = str_replace('t4spage=edit-category', 't4spage=donations/edit-category', $text);
		$text = str_replace('t4spage=create-fund', 't4spage=donations/create-fund', $text);
		$text = str_replace('t4spage=edit-fund', 't4spage=donations/edit-fund', $text);
		$text = str_replace('t4spage=https://t4s.inspiredonline.com/manage/donations/contacts', 't4spage=donations/contacts', $text);
	} 
	
	$text = str_replace("t4spage=https://t4s.inspiredonline.com/manage/donations/", 't4spage=donations', $text);
	
	if ((strncmp($xyz, 'event', 5)==0) || (strncmp($xyz, 'edit?event', 10)==0)) {
		$text = str_replace('t4spage=edit', 't4spage=calendar/edit', $text);
		$text = str_replace('t4spage=list', 't4spage=calendar/list', $text);
		$text = str_replace('t4spage=month', 't4spage=calendar/month', $text);
		$text = str_replace('t4spage=options', 't4spage=calendar/options', $text);			
	} 

	
	$regexp = "/donations\/list\?(.*)\"/";
	$matches = array();
	$result = preg_match_all($regexp, $text, $matches);
			
	foreach ($matches[1] as $m) {
		$text = str_replace($m, urlencode($m), $text);
	}
	
	$text = str_replace(admin_url( 'options-general.php?page=t4s&t4spage=javascript:void(0)'), '#', $text);
	
	$text = str_replace("?delete_campaign=", "donations/campaigns&delete_campaign=", $text);
	
	return $text;
}


function replaceAllActions($text, $url) {
		
	$regexp = "/action=\"([^\"]*)\"[^>]/siU";
	$matches = array();
	$result = preg_match_all($regexp, $text, $matches);

	if (count($matches) > 0) {
		if (count($matches[0]) > 0) {
			foreach ($matches as $m) {
				$tmp = str_replace('"', '', $m[0]);
				$text = str_replace('action="'.$m[0], 'action="'.$url.urlencode($tmp).'" ', $text);
				$text = str_replace("action='".$m[0], "action='".$url.urlencode($tmp).'" ', $text);
			}
		}
	}
	

	//custom case for calendar category select
	$url_clean = str_replace('&t4spage=', '', $url);
	$text = str_replace('name="category_search_form">', 'name="category_search_form"><input type="hidden" name="page" value="t4s"><input type="hidden" name="t4spage" value="calendar/month">', $text);
	
	//custom case for calendar event edit form
	if ($_GET['t4spage'] == 'calendar/edit') {
		$text = str_replace('t4spage=edit?', 't4spage=calendar/edit?form=events_rec&', $text);
	}
	
	$text = str_replace('action="month" method="GET">', 'method="GET"><input type="hidden" name="page" value="t4s">', $text);	
	$text = str_replace('name="searchForm">', 'name="searchForm"><input type="hidden" name="page" value="t4s"><input type="hidden" name="t4spage" value="calendar/list">', $text);
	$text = str_replace("action='/manage/calendar/import_preview", "action='".$url.'calendar/import_preview', $text);
	$text = str_replace("action='/manage/calendar/import", "action='".$url."calendar/import", $text);
	$text = str_replace("action='/manage/calendar/options", "action='".$url.'calendar/options', $text);
	$text = str_replace('action="options#rsvpFieldOptions"', '', $text);	
	$text = str_replace('t4spage=edit-category', 't4spage=donations/edit-category', $text);
	$text = str_replace('t4spage=edit-fund', 't4spage=donations/edit-fund', $text);	
	$text = str_replace('t4spage=options?s=tributes', 't4spage=donations/options'.urlencode("?s=tributes"), $text);	
	$text = str_replace('<form action="'.admin_url( 'options-general.php?page=t4s&t4spage=month').'" " method="GET">', '<form action="'.admin_url( 'options-general.php?page=t4s&t4spage=month').'" " method="GET"><input type="hidden" name="page" value="t4s"><input type="hidden" name="t4spage" value="month">', $text);	
	$text = str_replace(' class="mjpCategories"', " class='mjpCategories' style='display: none'", $text);
	$text = str_replace(admin_url( 'admin.php?page=t4s&t4spage=javascript: void(0)'), "javascript: void(0)", $text);	
	
	if (substr($_GET['t4spage'], 0, 27) == 'manage/calendar/event-rsvps') {
		$x = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$arr = explode("t4spage=", $x);
		$t4spage = strip_tags($_GET['t4spage']);
		if (strlen($t4spage) > 128) $t4spage = "";
		$eid = str_replace("manage/calendar/event-rsvps?event_id=", "", $t4spage);
		$y = $arr[0]."t4spage=event-rsvps?event_id=".$eid."&format=xls";
		
		$text = str_replace($y, str_replace("https://", "", plugins_url( 'tools4shuls/api/t4s_plugin_ajax.php?format=xls&event_id='.$eid)), $text);
	}
	
	$text = str_replace("t4spage=rsvp-details?", "t4spage=calendar/rsvp-details?", $text);
	$text = str_replace("t4spage=event-rsvps?event_id", "t4spage=manage/calendar/event-rsvps&event_id", $text);
	$text = str_replace("options%23rsvpFieldOptions", "options/rsvpFieldOptions", $text);
	
	return $text;

}


function replaceSomeJavaScript($text, $url) {

	$t4s_hash = getT4Shash();	

	$text = str_replace("DeleteEvent(''", "DeleteEvent('".$url."'", $text);	
	$text = str_replace("list?format=xls", plugins_url( 'tools4shuls/api/t4s_plugin_ajax.php?format=xls'), $text);
	
	$f_arr = array('SetCategoryVisibility', 'SetGenericCalendarOption', 'DeleteRsvpField', 'SaveRsvpField', 'DonationsRefreshTopDonations', 'RefreshContactList', 'LinkContact', 'AddContactDropdown', 'RemoveContact', 'SaveContact', 'EditContact', 'RefreshNonContactOptions', 'CloseContactUpdate', 'RefreshDonations', 'RefreshDonationsByMonth', 'CreateNewContact', 'RefreshDonationOptions', 'EditRsvpField', 'SaveOrgDetails', 'SaveEmailPreferences', 'SaveGatewayPreferences', 'SavingAuthorizeNetConfig', 'SavingOrgPaypalConfig', 'CreateAdminButtonClick', 'CancelNewAdmin', 'RefreshAdminList', 'ResetPassword', 'SaveOrgWebDetails', 'DeleteCategory', 'DeleteEvent', 'SetPermission', 'CreateNewAdmin', 'AddNewRSVPField', 'AddRSVPPayment', 'DeleteRSVP', 'GoConfirm');
		
	foreach ($f_arr as $f) {
		$text = str_replace($f."(", "Plugin_".$f."('".$t4s_hash."', ", $text);
	}	
		
	$text = str_replace("', )", "')", $text);		
	$text = str_replace("api/t4s_plugin_ajax.php?action=sort_rsvp_fields", "api/t4s_plugin_ajax.php?action=sort_rsvp_fields&hash=".$t4s_hash, $text);	
	$text = str_replace("action=sort_donation_funds", "action=sort_donation_funds&hash=".$t4s_hash, $text);
	$text = str_replace("action=sort_donation_categories", "action=sort_donation_categories&hash=".$t4s_hash, $text);
				
	return $text;

}


function processT4SResponse($response) {

	$uri = explode("?", $_SERVER['REQUEST_URI']);
	$prefix = "http://";
	if (strlen($_SERVER['HTTPS']) > 0) {
		$prefix = "https://";
	}
	$url = $prefix.$_SERVER['HTTP_HOST'].''.$uri[0].'?page=t4s&t4spage=';	
	
	$response = replaceAllLinks($response, $url);
	$response = replaceAllActions($response, $url);
	$response = replaceSomeJavaScript($response, $url);
	$response = loadAllCSS($response);
	$response = loadAllScripts($response);
	$response = stripHeadandBodyTags($response);
	$response = hideCertainLinks($response);
	
	$response = str_replace(admin_url( 'admin.php?page=t4s&t4spage=#'), '#', $response);
		
	if (strpos($_GET['t4spage'],'campaign') === false && isset($_GET['t4spage'])) {
		$regexp = "/t4spage=(.*)\\\\('\")/siU";
		$matches = array();
		$result = preg_match_all($regexp, $response, $matches);
		
		foreach ($matches[1] as $m) {
			if (strpos($m,'&') !== false) {		
				$response = str_replace($m, urlencode($m), $response);
			}
		}
	}

	$x = strpos($response, '<td width="115');	

	$inactive = '<td><a href="javascript:void(0);" onclick="InactiveMemo()"><img src="https://t4s.inspiredonline.com/_isuite/v/'.getVersion().'/assets/images/header-announcements-grey.jpg" width="115" height="105" border="0" /></td><td width="106"><a href="javascript:void(0);" onclick="InactiveMemo()"><img src="https://t4s.inspiredonline.com/_isuite/v/'.getVersion().'/assets/images/header-archive-grey.jpg" width="106" height="105" border="0" /></td><td width="111"><a href="javascript:void(0);" onclick="InactiveMemo()"><img src="https://t4s.inspiredonline.com/_isuite/v/'.getVersion().'/assets/images/header-gallery-grey.jpg" width="111" height="105" border="0" /></td><td><a href="javascript:void(0);" onclick="InactiveMemo()"><img src="https://t4s.inspiredonline.com/_isuite/v/'.getVersion().'/assets/images/header-newsletter-grey.jpg" width="107" height="105" border="0" /></td></tr></table></td></tr><tr><td><table><tr>';
	
	$memo = "<script type='text/javascript'>
				function InactiveMemo() {
					alert('These modules are not currently supported in the Tools 4 Shuls Wordpress plugin.  If you subscribe to them, please visit https://tools4shuls.com/manage to edit content.');
				}
			</script>";
			
	$x1 = strpos($response, '<a href="'.admin_url().'admin.php?page=t4s&t4spage=https://t4s.inspiredonline.com/manage/announcements/"><img src="https://t4s.inspiredonline.com/_isuite/v/'.getVersion().'/assets/images/header-announcements-active.jpg"');
	$x2 = strpos($response, '</table>');
	
	//for home page only, remove large links to other modules
	if (($url_orig == "https://t4s.inspiredonline.com/manage/login") || ($_GET['t4spage'] == 'https://t4s.inspiredonline.com/manage/') || ($_GET['t4spage'] == 'manage') || ($_GET['t4spage'] == 'manage/') || (!isset($_GET['t4spage']))) {
		$orig = $x2;
		$end = "</table>";
		
		$x1 = strpos($response, '<td class="homecalendarbox');
		if (!$x1) $x1 = strpos($response, '<td class="homedonationbox');
		
		if (!$x1) $x1 = strpos($response, '<td class="homedonatebox');
		
		$x2 = strpos($response, '<td class="homeannouncementbox');
		
		if (!$x2) $x2 = strpos($response, '<td class="homeannouncebox');
		
		if (!$x2) $x2 = strpos($response, '<td class="homearchivebox');
		if (!$x2) $x2 = strpos($response, '<td class="homegallerybox');
		if (!$x2) $x2 = strpos($response, '<td class="homenewsletterbox');
		if (!$x2) $x2 = $orig;
		
		$diff = $x2-$x1;
		
		$response = substr($response, 0, $x).$inactive.substr($response, $x1, $diff).$memo.$end;
	} else {

		$response = substr($response, 0, $x).$inactive.substr($response, $x2).$memo.$end;
	
	}
	
	$response = str_replace("</td>ipt language", "</td><script language", $response);
	$response = str_replace("<tr>ipt language", "<script language", $response);

	$response = str_replace("page=t4s&t4spage=https://t4s.inspiredonline.com/", "page=t4s&t4spage=", $response);
	$response = str_replace("page=t4s&t4spage=http://t4s.inspiredonline.com/", "page=t4s&t4spage=", $response);
	
	$response = str_replace("http://http://", "http://", $response);
	
	$response = str_replace(admin_url( '?page=t4s&t4spage=javascript:void(0)'), '#', $response);

	$z = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$y = explode("&t4spage=", $z);
	$x = $y[0]; 
	
	$response = str_replace($x."&t4spage=javascript:", "javascript:", $response);
		
	$response = str_replace('<form action="'.$x.'&t4spage=month" " method="GET">', ' <form action="'.$x.'&t4spage=month" method="GET"><input type="hidden" name="page" value="t4s"><input type="hidden" name="t4spage" value="month">', $response);
	
	//get hash
	$t4s_hash = getT4Shash();	
	$response = str_replace('api/t4s_plugin_ajax.php?format=xls', 'api/t4s_plugin_ajax.php?format=xls&hash='.$t4s_hash, $response);
	
	//hide the top admin bar
	$response = str_replace('<table width="980" border="0" align="center" cellpadding="0" cellspacing="0">
<tr>', '<a href="'.admin_url().'admin.php?page=t4s&t4spage=manage/categories">[ Manage Site Categories ]</a><br/><br/><table width="980" border="0" align="center" cellpadding="0" cellspacing="0"><tr style="display: none">', $response);
	
	$response = str_replace('t4spage=./donations/view', 't4spage=donations/summary', $response);
	
	$upload_dir = wp_upload_dir();
	
	$response = str_replace('https://t4s.inspiredonline.com/manage/asset-browser/browse', $upload_dir['path'], $response);
	$response = str_replace('https://t4s.inspiredonline.com/manage/asset-browser/upload', $upload_dir['path'], $response);
	
	$response = str_replace("event-rsvps&event_id", "event-rsvps?event_id", $response);
	$response = str_replace("t4spage=list", "t4spage=calendar/list", $response);
	
	if (isset($_GET['t4spage'])) {
		if (strpos($_GET['t4spage'],'/administrators') !== false) {
			$response = str_replace("<h2>Administrators", "Tools 4 Shuls has the ability to be managed outside of Wordpress.  If you would like to give staff members or volunteers access to the Tools 4 Shuls admin panel without having them log into wordpress, create an account for them below, assigning them to the appropriate modules, and then have them log in at <a href='http://www.tools4shuls.com/manage' target=_blank>www.tools4shuls.com/manage</a>. <br/><br/><h2>Administrators", $response);
		}
	}
	
	
	$response = str_replace(site_url().'/wp-content/plugins/tools4shuls/api/t4s_plugin_ajax.php?format=xls', admin_url('admin-ajax.php')."?action=core_t4s_callback&format=xls", $response);
	
	return $response;

}

?>