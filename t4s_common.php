<?php

function executeCurl($url) {

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
	curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"); 
	curl_setopt ($ch, CURLOPT_TIMEOUT, 60); 	
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 0); 
	curl_setopt ($ch, CURLOPT_REFERER, $url); 

	$response = curl_exec($ch);
	if(curl_errno($ch))
	{
		echo 'error:' . curl_error($ch);
	}
	curl_close($ch);
	
	return $response;

}

function getClientCalendarCategories($id) {

	$response = executeCurl("https://t4s.inspiredonline.com/ajax/plugin_ajax.php?action=cal-cats&client=".$id);
	$lines = explode("<br>", $response);
	
	$cal_cats = array();
	
	foreach ($lines as $line) {
		$item = explode("[t4s]", $line);
		if (count($item) == 2) {
			$cal_cats[$item[0]] = $item[1];
		}
	}
	
	return $cal_cats;
}


function getClientDonationsCategories($id, $campaign = false, $return = false) {
	if ($campaign) $camp = "&camp=true";
	$response = executeCurl("https://t4s.inspiredonline.com/ajax/plugin_ajax.php?action=don-cats&client=".$id.$camp);
	$lines = explode("<br>", $response);
	
	$cal_cats = array();
	
	if (!$return) {
		foreach ($lines as $line) {
			echo $line."|";
		}
	} else {
		return $lines;
	}
}


function getClientDonationsFunds($id, $cat, $return = false) {
	if ($cat != 'all') $category = "&cat=".$cat;
	$response = executeCurl("https://t4s.inspiredonline.com/ajax/plugin_ajax.php?action=don-funds&client=".$id.$category);
	$lines = explode("<br>", $response);
	
	$cal_cats = array();
	
	if (!$return) {
		foreach ($lines as $line) {
			echo $line."|";
		}
	} else {
		return $lines;
	}
	
}


function sortByOrder($a, $b) {
    return strtotime($b['date_submitted']) - strtotime($a['date_submitted']);
}


function getT4SCookie() {

	global $wpdb;
    $table = $wpdb->prefix."options";
	
	//No inputs, no prepare()
	$query = "SELECT option_value FROM ".$table." WHERE option_name = 't4s_cookie'";
	$result = mysql_query($query);
	$row = mysql_fetch_assoc($result);
	
	$_SESSION['t4s_cookie'] = $row['option_value'];
	
}

function setT4SCookie($ch) {
	
	$cookiejar = $_SESSION['t4s_cookie'];
	$cookiefile = tempnam(sys_get_temp_dir(), 'T4S');
	file_put_contents($cookiefile, $cookiejar);
	
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
	
	$_SESSION['t4s_cookie_file'] = $cookiefile;
		
	return $ch;

}

function updateT4SCookie() {
			
	global $wpdb;
    $table = $wpdb->prefix."options";
	$nc = file_get_contents($_SESSION['t4s_cookie_file']);
		
	if ($nc != "") {	
		$query = $wpdb->prepare("UPDATE ".$table." SET option_value = '%s' WHERE option_name = 't4s_cookie' LIMIT 1", $nc);
		mysql_query($query) or die(mysql_error());
	} 
}


function updateT4Shash() {
	
	global $wpdb;
    $table = $wpdb->prefix."options";
	
	$rand = md5(date('Y-m-d H:i:s').rand(0,9999999999));
	$query = $wpdb->prepare("UPDATE ".$table." SET option_value = '%s' WHERE option_name = 't4s_hash' LIMIT 1", $rand);
	mysql_query($query);
	
}


function getT4Shash() {

	global $wpdb;
	$table = $wpdb->prefix."options";
	
	$query = "SELECT option_value FROM ".$table." WHERE option_name = 't4s_hash'";
	$result = mysql_query($query);
	
	if (!$result) {
		updateT4Shash(1);
		return "trash";
	} else if (mysql_num_rows($result) == 0) {
		updateT4Shash(2);
		return "trash";
	}
	
	$row = mysql_fetch_assoc($result);
	
	if ($row['option_value'] == "") {
		updateT4Shash(3);
		return "trash";
	}
	
	return $row['option_value'];

}


function checkT4Shash($hash) {

	global $wpdb;
	$table = $wpdb->prefix."options";
	
	$query = "SELECT option_value FROM ".$table." WHERE option_name = 't4s_hash'";
	$result = mysql_query($query);
	
	if (!$result) {
		return false;
	} else if (mysql_num_rows($result) == 0) {
		return false;
	}
	
	$row = mysql_fetch_assoc($result);
	
	if ($row['option_value'] == "") {		
		return false;
	}
				
	if ($row['option_value'] == $hash) {
		return true;
	} else {
		return false;
	}

}


function throwT4SError($message) {

	echo $message;
	exit();

}


function getT4SSettings($args = null) {

	//ARGS are never user's input 

	global $wpdb;	
	$prefix = $wpdb->prefix;

	if ($args == null) {
		$query = "SELECT * FROM ".$prefix."options WHERE option_name LIKE 't4s_%'";
	} else {
		$where = "= ";
		if (is_array($args)) {
			foreach ($args as $a) {
				$where .= $wpdb->prepare('%s', $a)." OR option_name = ";
			}
			$where = substr($where, 0, strlen($where)-17);
		} else {
			$where = "= '".mysql_real_escape_string($args)."'";
		}
		$query = "SELECT * FROM ".$prefix."options WHERE option_name ".$where;
	}
	
	$result = mysql_query($query) or die(mysql_error());

	$settings = array();
	
	if (mysql_num_rows($result) > 0) {
		for ($i=0;$i<mysql_num_rows($result);$i++) {
			$row = mysql_fetch_assoc($result);			
			$settings[$row['option_name']] = $row['option_value'];
		}
	} else {
		throwT4SError('Unable to find required settings. Please contact us for further assistance.');
	}
	
	if (count($settings) == 1) {
		return $row['option_value'];
	}
	
	return $settings;

}


function generateT4SShortcode($atts) {

	global $wpdb;

	extract( shortcode_atts( array(
		'mode' => 'mode',
		'view' => 'view',
		'max' => 'max',
		'homepage' => 'homepage',
		'cat' => 'cat',
		'header' => 'header',
		'footer' => 'footer',
		'fund' => 'fund',
		'campaign' => 'fund',
		'homepage' => 'homepage',
		't4sdomobj' => 't4sdomobj'
	), $atts ) );
	
	if (strlen($t4sdomobj) < 3) {
		$t4sdomobj = "divT4S";
	}
	
	$setts = getT4SSettings();	
	$clientID = $setts['t4s_clientID'];
	$apiKey = $setts['t4s_apiKey'];
	
	$url = "https://t4s.inspiredonline.com/api/?t4sjs=1&t4sdomobj=".$t4sdomobj."&t4scid=".$clientID."&t4sapk=".$apiKey."&randid=".rand(1,999999); 
	
	if ($mode != 'mode') $url .= "&t4smod=".urlencode($mode);
	if ($max != 'max') $url .= "&t4smax=".urlencode($max);
	if ($fund != 'fund') {
		$url .= "&t4sfid=".urlencode($fund);
	} else if ($cat != 'cat') {
		$url .= "&t4scat=".urlencode($cat);
	} else if ($campaign != 'fund') {
		$url .= "&t4sfid=".urlencode($campaign);
	}
	
	if ($view != 'view') {
		$url .= "&t4sdisp=".urlencode($view);
	}
	if ($header != 'header') $url .= "&t4swheader=".urlencode($header);
	if ($footer != 'footer') $url .= "&t4swfooter=".urlencode($footer);
	
	if ($homepage == 'yes') $url .= "&homepage=yes";
	
	$url .= "&t4su=".curPageURL();
		
	wp_register_style( 'prefix-style', plugins_url('css/correctional.css', __FILE__) );
    wp_enqueue_style( 'prefix-style' );	

	?>

	<div id='<?php echo $t4sdomobj; ?>' style="position: relative"></div>
	<script type="text/javascript">
		
		if(typeof jQuery == 'undefined'){
			document.write('<script src="https://t4s.inspiredonline.com/_isuite/v/<?php echo getVersion(); ?>/assets/js/jquery-1.8.0.min.js" type="text/javascript" ></'+'script>');
			document.write('<script src="https://t4s.inspiredonline.com/_isuite/v/<?php echo getVersion(); ?>/assets/js/jquery-ui-1.8.23.custom.min.js" type="text/javascript" ></'+'script>');
		}
		
	
		if (typeof jQuery.ui == 'undefined') {
			var oScriptElem = document.createElement("script");
			oScriptElem.type = "text/javascript";
			oScriptElem.src = "https://t4s.inspiredonline.com/_isuite/v/<?php echo getVersion(); ?>/assets/js/jquery-ui-1.8.23.custom.min.js";
			document.head.insertBefore(oScriptElem, document.getElementsByTagName("script")[0]);
		}
	</script>
	<script type="text/javascript" src="<?php echo $url; ?>"></script>
	
	<?php

}


function sendEmailToT4SAdmins($subject, $content, $arr) {

	global $wpdb; 
	
	$to = "";
	
	//GET T4S ACCOUNT EMAIL
	$query = "SELECT option_name, option_value FROM ".$wpdb->prefix."options WHERE option_name = 't4s_login'";
	$result = mysql_query($query);
	if ($result) {
		if (mysql_num_rows($result) == 1) {
			$row = mysql_fetch_assoc($result);
			if (strlen($row['option_value']) > 1) {
				$to = $row['option_value'].",";
			}
		}
	}

	//GET BLOG ACCOUNT EMAIL
	$to .= get_option('admin_email');
		
	$header = "From: noreply@".get_site_url()."\r\n"; 
	$header.= "MIME-Version: 1.0\r\n"; 
	$header.= "Content-Type: text/plain; charset=utf-8\r\n"; 
	$header.= "X-Priority: 1\r\n"; 
	
	$content = "Dear Administrator,

".$content."

Here are the error details:
".convertArrayToText($arr)."

If you have any questions or need further support, please forward this email to support@inspiredonline.com.";
	
	mail($to, $subject, $content);
	
}


function convertArrayToText($arr) {

	$output = "";

	foreach ($arr as $k => $v) {	
		$output .= "
		".strip_tags($k).": ".strip_tags($v);	
	}
	return $output;

}


function getVersion() {

	$x = executeCurl('https://t4s.inspiredonline.com/version-check.php');
	return $x;

}


add_action( 'wp_ajax_t4s_update_settings', 'updateT4SSettings' );


function curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 
 $pageURL = strip_tags($pageURL);
 if (strlen($pageURL) > 256) $pageURL = "";
 
 return $pageURL;
}

?> 