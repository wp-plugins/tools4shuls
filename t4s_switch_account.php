<?php
	
	//CHECK FOR ADMIN WP LOGIN
	if (!current_user_can('manage_options')) {
		exit();
	} 

	global $wpdb;
	
	if (isset($_POST['t4s_login']) && $_POST['t4s_login'] != '') {
			
		executeCurl(admin_url().'admin.php?page=t4s&t4spage=manage/logout');
	
		$login = esc_html($_POST['t4s_login']);
		if (strlen($login) > 99) $login = "";
		
		$pwd = esc_html($_POST['t4s_pwd']);
		if (strlen($pwd) > 99) $pwd = "";
	
		$query = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_login'", $login);
		$wpdb->query($query);

		$query = $wpdb->prepare("UPDATE ".$wpdb->prefix."options SET option_value = '%s' WHERE option_name = 't4s_password'", sha1(sha1(md5(trim($pwd)))));
		$wpdb->query($query);

		$url = admin_url().'admin.php?page=t4s';
		
		echo "<script type='text/javascript'>window.location = '".$url."';</script>";
		
	} else { 
	
		include("includes/t4s_credentials_form.php"); 
		
	}
?>