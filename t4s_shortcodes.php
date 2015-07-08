<?php

//CHECK FOR ADMIN WP LOGIN
if (!current_user_can('manage_options')) {
	exit();
} 

global $wpdb;

$rndint = rand(1,999999);

?>

<h1>Installation and Shortcodes</h1>

general instructions here

<br/><br/>

<h2>Shortcode Generator</h2>

<?php if (!isset($_POST['t4s_short_gen_mod'])) { ?>

Which module would you like to create a shortcode for? <br/>

<form method='POST'>
	<select name='t4s_short_gen_mod'>
		<option value="cal">Calendar</option>
		<option value="don">Donations</option>
		<option value="for">Forms</option>
	</select>
	<br/>
	<input type="submit" value="Next">
</form>

<?php } else if (isset($_POST['submission'])) {
		
			$view = stripslashes(esc_html($_POST['t4sdisp']));
			if (strlen($view) > 12) $view = "";
		
			$mod = stripslashes(esc_html($_POST['t4s_short_gen_mod']));
			if (strlen($mod) > 12) $mod = "";
			
			$max = intval(stripslashes(esc_html($_POST['t4smax'])));
		
			$cat = intval(stripslashes(esc_html($_POST['t4scat'])));
					
			$head = str_replace("'", '"', htmlentities($_POST['t4swheader']));			
			
			$foot = str_replace("'", '"', htmlentities($_POST['t4swfooter']));

			$fid = intval(stripslashes(esc_html($_POST['t4sfid'])));
		
			$don_cats = stripslashes(esc_html($_POST['don-cats']));
			
			$test = explode(",", $don_cats);
			foreach ($test as $t) {
				$t = intval($t);
				if ($t == 0) {
					$don_cats = "";
				}
			}
			
			$don_funds = stripslashes(esc_html($_POST['don-funds']));
			$test = explode(",", $don_funds);
			foreach ($test as $t) {
				$t = intval($t);
				if ($t == 0) {
					$don_funds = "";
				}
			}
					
		
			echo "<b>Your code</b>:<br/><br/>
				  [t4s mode='".$mod."' view='".$view."'";
				  
			if ($max != '') echo " max='".$max."'";
			if ($cat != '') echo " cat='".$cat."'";
			if ($head != '') echo " header='".$head."'";
			if ($foot != '') echo " footer='".$foot."'";
			if ($fid != '') echo " fund='".$fid."'";
			
			if ($don_cats != '') echo " cat='".$don_cats."'";
			if ($don_funds != '') echo " fund='".$don_funds."'";
			
			if ($_POST['homepage'] == 'on') echo " homepage='yes'";
			
			$rand_id = "t4s-sc-".strtotime(date('Y-m-d H:i:s'));
			
			echo " t4sdomobj='".$rand_id."'";
			
			echo "] <br/><br/><a href='?page=t4s-installation-and-shortcodes'>Start Over</a>";

	} else if ($_POST['t4s_short_gen_mod'] == 'cal') { 
	
		$url = "https://t4s.inspiredonline.com/ajax/get_calendar_path.php?client_id=".getT4SSettings('t4s_clientID');
		$cal_link = executeCurl($url);
	
	?>

		<form method='POST'>
			<input name='t4s_short_gen_mod' type="hidden" value="cal">
			<table class="short_gen_table">
				<tr>
					<td>Calendar Mode</td>
					<td>
						<select name='t4sdisp' id="t4sdisp<?php echo $rndint; ?>" onChange="t4sDisplay(<?php echo $rndint; ?>)">
							<option value='full'>Full Calendar</option>
							<option value='upcoming'>Calendar Widget</option>
						</select>
					</td>
				</tr>
				<tr id='t4scat-row<?php echo $rndint; ?>' style="visibility: hidden">
					<td>Category to Show</td>
					<td>	
						<select name='t4scat' id='t4scat<?php echo $rndint; ?>' disabled="disabled">	
							<option value=''>All</option>
							<?php
								//GET CLIENT'S CATEGORIES
								$id = getT4SSettings('t4s_clientID');
								$cal_cats = getClientCalendarCategories($id);
								
								foreach ($cal_cats as $k => $v) {
									echo "<option value='".$k."'>".$v."</option>";
								}
							?>
						</select>
					</td>
				</tr>			
				<tr>
					<td>Text to be shown before the calendar</td>
					<td>
						<textarea id="t4swheader" name="t4swheader" cols=30 rows=3></textarea>
					</td>
				</tr>
				<tr>
					<td>Text to be shown after the calendar</td>
					<td>
						<textarea id="t4swfooter" name="t4swfooter" cols=30 rows=3><a href='<?php echo $cal_link; ?>'>view full calendar</a></textarea>
					</td>
				</tr>		
				<tr>
					<td>This widget will be shown on the homepage</td>
					<td>
						<input type='checkbox' name='homepage'>
					</td>
				</tr>
			</table>
			<input type="hidden" name="submission" value="true">
			<input type="submit" value="Generate">
		</form>

		
		<script type="text/javascript">
			var total = 0;
			function forceNumeric(id) {
				if (isNaN(document.getElementById(id).value)) {
					alert('Please enter a valid number for Maximum Records to Return!');
					document.getElementById(id).value = "";
				}
			}
			function t4sDisplay(el) {	

				var x = document.getElementById("t4sdisp"+el).value;
				var cat = document.getElementById("t4scat"+el);
				var y = document.getElementById("t4scat-row"+el);

				if (x == 'full') {
					cat.disabled = 'disabled';
					y.style.visibility = 'hidden';
				} else {
					cat.disabled = null;
					y.style.visibility = 'visible';
				}
				
				calTypeChange(el);
			}
			function calTypeChange(el) {
				var x = document.getElementById("t4sdisp"+el).value;
				var y = document.getElementById("t4smax"+el);
				var z = document.getElementById("t4smax-row"+el);
				
				if (x == 'full') {
					y.value = "";
					z.style.visibility = 'hidden';
				} else {
					y.value = "10";
					z.style.visibility = 'visible';
				}
			}
		</script>
		
<?php } else if ($_POST['t4s_short_gen_mod'] == 'don') { ?>
		
		<form method='POST'>
		<input name='t4s_short_gen_mod' type="hidden" value="don">
		
		<div style="shortcode-left">
			<h2>Donations Mode</h2>
		</div>
		<div style="shortcode-left">
			<select name='t4sdisp' id="t4sdisp" onChange="getDonCats(this.value)">
				<option value='full'>Full</option>
				<option value='Campaigns'>Campaigns Only</option>
			</select>
		</div>
		
		<div id="don-div-0">
		
		</div>
		
		<script type="text/javascript">

		var x = "";
		var x2 = "";
		var div0 = document.getElementById('don-div-0');
		
		function executePost(data, fnct) {
			jQuery.post(ajaxurl, data, function(response) {
				if (fnct == 'don-cats') {
					x = response;
					fillDonationsCategories();					
				}
				if (fnct == 'don-funds') {
					x2 = response;
					fillDonationsFunds();					
				}
			});
		};

		
		getDonCats('full');
		
		function getDonCats(val) {	
			if (val == 'Campaigns') {
				div0.innerHTML = "<h2>Categories</h2><select id='don-cats' onChange='getDonFunds(this.value)'><option value='179'>Campaigns</option></select><div id='don-div-1'></div>";
				getDonFunds('179');
			} else {
				var data = {
					action: 't4s_action',
					task: 'don-cats',
					user: '<?php echo getT4SSettings('t4s_clientID');?>',
					val: val
				};			
				executePost(data, 'don-cats');		
			}
		}
		
		
		function fillDonationsCategories() {
			var y = x.split("|");
			var out = "";
			var length = y.length;
			for (var i = 0; i < length; i++) {
			  element = y[i];
			  var z = element.split("[t4s]");
			  if (z[1]) {
				out = out + "<option value='"+z[0]+"'>"+z[1]+"</option>";
			  }
			}
			
			getDonFunds('all');			
			
			div0.innerHTML = "<h2>Categories</h2><select id='don-cats' name='don-cats' onChange='getDonFunds(this.value)'><option value='all'>All</option>"+out+"</select><div id='don-div-1'></div>";
			
		}
		
		
		function getDonFunds(val) {	
			var data = {
				action: 't4s_action',
				task: 'don-funds',
				user: '<?php echo getT4SSettings('t4s_clientID');?>',
				val: val
			};			
				executePost(data, 'don-funds');		
		}
		
		
		function fillDonationsFunds() {
			var y = x2.split("|");
			var out = "";
			
			var length = y.length;
			for (var i = 0; i < length; i++) {
			  element = y[i];
			  var z = element.split("[t4s]");
			  if (z[1]) {
				out = out + "<option value='"+z[0]+"'>"+z[1]+"</option>";
			  }
			}						
			
			var output = "<h2>Funds</h2><select id='don-funds' name='don-funds'><option value=''>All</option>"+out+"</select>";
			
			document.getElementById('don-div-1').innerHTML = output;
		}
		
		</script>
	
		<input type="hidden" name="submission" value="true">
			<input type="submit" value="Generate">
		</form>
<?php } else if ($_POST['t4s_short_gen_mod'] == 'for') { 

			//NO INPUTS, NO prepare() NEEDED
			$query  = "SELECT * FROM t4s_forms WHERE active = 1 ORDER BY id ASC";
			$result = mysql_query($query);
			$output = "<table class='t4s-tables-list'>
						<tr>
							<td><b>Form Name</b></td>							
							<td><b>Short Code</b></td>
						</tr>";
			if (mysql_num_rows($result) > 0) {
				for ($i=0;$i<mysql_num_rows($result);$i++) {
					$row = mysql_fetch_assoc($result);
					
					$output .= "<tr>
									<td>".esc_html(stripslashes($row['name']))."</td>									
									<td><i>[showT4Sform formId=".esc_html($row['id'])."]</i></td>
								</tr>";
				}	
			}
			$output .= "</table>";
			
			echo $output;
			echo "<br/><br/><a href='?page=t4s-installation-and-shortcodes'>Start Over</a>";

	  } 