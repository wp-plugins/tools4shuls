<?php 

//CHECK FOR ADMIN WP LOGIN
if (!current_user_can('manage_options')) {
	exit();
} 

$response = query_T4S();

if (strpos($response,'Login Failed') === false) {

	if (strpos($response,'DOCTYPE html PUBLIC') !== false) {
		updateT4Shash();
	}
	
	?>
	<noscript>
		<style type="text/css">
			.pagecontainer {display:none;}
		</style>
		<div class="noscriptmsg">
			<br/><br/>
			<h1>Please enable JavaScript to work with this plugin.</h1>
		</div>
	</noscript>
	
	<div class='pagecontainer'>
	<?php	
		echo $response;	
	?>
	</div>
	
	<?php
		
} else {

	echo "<br><br><h2>YOUR T4S CREDENTIALS ARE INVALID</h2><br>";
	include("includes/credentials_form.php");

}

?>