<?php

add_action( 'wp_ajax_t4s_update_form_fields_order', 't4s_update_form_fields_order' );

function t4s_update_form_fields_order() {
	
	global $wpdb;
	
	foreach ($_POST['x'] as $k => $v) {
	
		$k = intval($k);
		$v = intval($k);
	
		$query = $wpdb->prepare("UPDATE t4s_forms_fields SET ordernum = '%d' WHERE id = '%d'", $k, $v);
					
		mysql_query($query);
	}
	
	wp_die();
	
}

function admin_interface_function() {

	//CHECK FOR ADMIN WP LOGIN
	if (!current_user_can('manage_options')) {
		exit();
	} 
			
	global $wpdb;	
	$prefix = $wpdb->prefix;
	
	$sid = intval($_GET['editform']);	
	
	$_SESSION['formID'] = $sid;

	//DETECT NEW ELEMENT CREATION
	if (isset($_POST['type'])) {
		
		//CREATE THE BASIC ELEMENT
		$query = $wpdb->prepare("SELECT COUNT(id) as total FROM t4s_forms_fields WHERE t4s_forms_Id = '%s'", $_SESSION['formID']);
		$result = mysql_query($query);
		$total = 0;
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_assoc($result);
			$total = $row['total'];
		}
		
		
		//IF EDITING FIELDS: $_POST['edit_id'] is numeric
		if (is_numeric($_POST['edit_id'])) {
		
			$query = $wpdb->prepare("SELECT ordernum FROM t4s_forms_fields WHERE id = '%s'", $_POST['edit_id']);
			$result = mysql_query($query);
			$row = mysql_fetch_assoc($result);
			$total = $row['ordernum'];
		
			$query = $wpdb->prepare("DELETE FROM t4s_forms_fields WHERE id = '%s' LIMIT 1", $_POST['edit_id']);
			mysql_query($query);
					
			$query = $wpdb->prepare("DELETE FROM t4s_forms_options WHERE test_fields_id = '%s'", $_POST['edit_id']);
			mysql_query($query);
		
		}
		
		$name = esc_html($_POST['newName']);
		if (strlen($name) > 64) $name = "";
		
		$type = esc_html($_POST['type']);
		if (strlen($type) > 32) $type = "";

		$sql = $wpdb->prepare("INSERT INTO t4s_forms_fields (label, name, content, t4s_forms_Id, ordernum) VALUES('%s', '%s', '', '%d', '%d')", $name, $type, $_SESSION['formID'], $total);

		mysql_query($sql);
				
		$autoGen = mysql_insert_id();
		$_SESSION['fieldId'] = $autoGen;
		
		//CREATE OPTIONS FOR SELECT AND RADIO LISTS
		if ($_POST['type'] == 'select' || $_POST['type'] == 'radio') {
			foreach ($_POST as $k => $v) {
				if (substr($k, 0, 5) == 'label') {
				
					$k = esc_html($k);
					if (strlen($k) > 64) $k = "";
				
					$id = intval(str_replace("label_", "", $k));
										
					$v = esc_html($v);
					if (strlen($v) > 64) $v = "";
					
					$order = intval(esc_html($_POST['order_'.$id]));
					
					$query = $wpdb->prepare("INSERT INTO t4s_forms_options (value, description, test_fields_id, ordernum) VALUES ('%s','%s', '%s', '%s')", $v, $v, $autoGen, $order);
					mysql_query($query) or die(mysql_error());
				}
			}
		
		}

	}

	//DETECT ELEMENT DELETION
	if (isset($_GET['delete'])) {

		$did = intval($_GET['delete']);
	
		$sql = $wpdb->prepare("DELETE FROM t4s_forms_fields WHERE id='%s' LIMIT 1", $did);
		mysql_query($sql);		
		
		$sql = $wpdb->prepare("DELETE FROM t4s_forms_options WHERE test_fields_id = '%d'", $did);
		mysql_query($sql);		
	}

	
	if ( isset($_GET['editform']) ) {
		$_SESSION['formID']= intval($_GET['editform']);
	}
	
	if (!isset($_SESSION['formID'])) {
		$query = "SELECT MIN(id) FROM t4s_forms";
		$result = mysql_query($query);
		if ($result) {
			if (mysql_num_rows($result) > 0) {
				$row = mysql_fetch_assoc($result);
				$_SESSION['formID'] = $row['id'];
			}
		}
	}

	//check for a new form creation
	if (isset($_POST['fname'])) {
	
		$name = esc_html($_POST['fname']);
		if (strlen($name > 64)) $name = "noname";
	
		$query = $wpdb->prepare("INSERT INTO t4s_forms (`name`, content, active) VALUES ('%s', '', '1')", $name);
		mysql_query($query) or die(mysql_error());
		$fid = mysql_insert_id();
		echo "<script type='text/javascript'>window.location='?page=t4s-form-creator&editform=".$fid."';</script>";
	}
	
	//check for form deletion
	if (isset($_GET['deleteform'])) {
		$did = intval($_GET['deleteform']);
		$query = $wpdb->prepare("UPDATE t4s_forms SET active = 0 WHERE id = '%d' LIMIT 1",$did);
		mysql_query($query);
		$msg = "<span style='color: green'>Form deleted</span>";
	}
	
	
	//check for form duplication
	if (isset($_GET['duplicateform'])) {
	
		$did = intval($_GET['duplicateform']);
	
		$query = $wpdb->prepare("SELECT * FROM t4s_forms WHERE id = '%d'", $did);
		$result = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_assoc($result);
		
		$query = $wpdb->prepare("INSERT INTO t4s_forms (`name`, `description`, `active`) VALUES ('%s Copy', '%s', 1)", $row['name'], $row['description']);
		mysql_query($query) or die(mysql_error());
		
		$form_id = mysql_insert_id();
		
		$sql = $wpdb->prepare("SELECT * FROM t4s_forms_fields WHERE t4s_forms_Id = '%d'", $row['id']);
		$res = mysql_query($sql) or die(mysql_error());

		for ($i=0;$i<mysql_num_rows($res);$i++) {
		
			$r = mysql_fetch_assoc($res);
			$q = $wpdb->prepare("INSERT INTO t4s_forms_fields (`label`, `name`, t4s_forms_Id, ordernum) VALUES ('%s', '%s', '%d', '%d')", $r['label'], $r['name'], $form_id, $r['ordernum']);
			mysql_query($q) or die(mysql_error());
			
			$field_id = mysql_insert_id();
			
			if ($r['name'] == 'select' || $r['name'] == 'radio') {
				$q = $wpdb->prepare("SELECT * FROM t4s_forms_options WHERE test_fields_id = '%d'", $r['id']);
				$res2 = mysql_query($q) or die(mysql_error());
				
				for ($j=0;$j<mysql_num_rows($res2);$j++) {
					$r2 = mysql_fetch_assoc($res2);
					$q = $wpdb->prepare("INSERT INTO t4s_forms_options (`value`, description, test_fields_id, ordernum) VALUES ('%s', '%s', %d, %d)", $r2['value'], $r2['description'], $field_id, $r2['ordernum']);
					mysql_query($q) or die(mysql_error());
				}
			}
			
		}		
		
		$msg = "<span style='color: green'>Success!</span>";
		
		echo "<script type='text/javascript'>window.location='?page=t4s-form-creator&editform=".$form_id."';</script>";
	}
	
	//SHOW FIELDS ASSOCIATED WITH CURRENT FORM
	$sql = $wpdb->prepare("SELECT * FROM t4s_forms_fields WHERE t4s_forms_Id='%d' ORDER BY ordernum ASC", $_SESSION['formID']);
	$result=mysql_query($sql);
	
	$preview = "<style>
	.formCreatorSpan {
		width: 250px;
		min-width: 250px;
		display: table-cell;
		cursor: pointer;
	}
	.ui-sortable-handle {
		border-bottom: 1px solid;
	}
	</style><ul style='background-color: white' id='previewTable'>";
	while($row=mysql_fetch_array($result)){		
		$options='';
		
		if($row['name'] == 'textbox' ){ 
			$preview .= "<li><div class='formCreatorSpan'>".stripslashes($row['label'])."</div><div class='formCreatorSpan'><input type='textbox'></div>";
		} else if($row['name'] == 'textarea' ){ 
			$preview .= "<li><div class='formCreatorSpan'>".stripslashes($row['label'])."</div><div class='formCreatorSpan'><textarea></textarea></div>";
		} else if($row['name'] == 'checkbox' ){ 
			$preview .= "<li><div class='formCreatorSpan'>".stripslashes($row['label'])."</div><div class='formCreatorSpan'><input type='checkbox'></div>";
		} else if($row['name'] == 'select' ){ 
			$preview .= "<li><div class='formCreatorSpan'>".stripslashes($row['label'])."</div><div class='formCreatorSpan'><select>";
			$preview .= generateOptions($row['id'], 'select');
			$preview .= $row['label']." </select></div>";
		} else if($row['name'] == 'radio' ){ 
			$preview .= "<li><div class='formCreatorSpan'>".stripslashes($row['label'])."</div><div class='formCreatorSpan'>";	
			$preview .= generateOptions($row['id'], 'radio');
			$preview .= "</div>";
		}

		$preview.="[ <a href='?page=t4s-form-creator&edit=".$row['id']."&editform=".$_SESSION['formID']."'>Edit</a> ]</span> [ <a href='?page=t4s-form-creator&delete=".$row['id']."&editform=".$_SESSION['formID']."' onClick='if (!confirm(\"Are you sure you want to delete this field?\")) return false'>Delete</a> ] <input type='hidden' class='sortableID' value='".$row['id']."'></li>";		
	}
	$preview .= "</ul>";
	
	//check for form update
	if ((isset($_POST['description']) || isset($_POST['price'])) && (isset($_SESSION['formID']))) {
	
		$desc = esc_html($_POST['description']);
		if (strlen($desc) > 256) $desc = "description too long";
		
		$price = floatval($_POST['price'].".".$_POST['price2']);
		
		$thank_you = esc_html($_POST['thank_you']);
		if (strlen($thank_you) > 256) $thank_you = "";
		
		$type = $_POST['pmt_type'];
		if (strlen($type) > 24) $type = "";
		
		$query = $wpdb->prepare("UPDATE t4s_forms SET description='%s', price='%s', thank_you = '%s', pmt_type = '%s' WHERE id='%d'", $desc, $price, $thank_you, $type, $_SESSION['formID']);

		mysql_query($query);
		echo "<br/>Form Updated.<br/>";
	}
	
	//display current form or empty form
	$query = $wpdb->prepare("SELECT * FROM t4s_forms WHERE id = '%d'", $_SESSION['formID']);
	$result2 = mysql_query($query);
	$row2 = mysql_fetch_assoc($result2);
	
	$cents = "00";
	if ($row2['price'] != '') {
		$arr = explode(".", $row2['price']);
		$dollars = $arr[0];
		$cents = $arr[1];
	}
	
	if ($row2['pmt_type'] == 'none') $ch1 = ' checked="checked" ';
	if ($row2['pmt_type'] == 'user') $ch2 = ' checked="checked" ';
	if ($row2['pmt_type'] == 'fixed') $ch3 = ' checked="checked" ';
	
	$preview2 = "
					<form method='POST' id='regForm' action='?page=t4s-form-creator&editform=".$_SESSION['formID']."'>
					
						<div style='width: 500px; background-color: #c8cbcf; margin-top: 20px;padding: 20px'>
							<b>Amount to Charge</b>: <br/>
						
							<input type='radio' name='pmt_type' value='none'".$ch1."> This form does not require a payment <i>(a thank-you page is highly recommended)</i><br/>
							<input type='radio' name='pmt_type' value='user'".$ch2."> The user will enter the amount to pay <br/>
							<input type='radio' name='pmt_type' value='fixed'".$ch3."> Charge the following pre-determined amount to all users: 					
							<b>$</b><input type='text' name='price' id='price'  value='".$dollars."' size=6 onChange='checkNumeric(this.id)'>.<input type='text' name='price2' id='price2'  value='".$cents."' size=2 onChange='checkNumeric(this.id)'><br/>
						
						</div>
						<div style='width: 500px; background-color: #c8cbcf; margin-top: 20px; padding: 20px'>
							<b>Form Description</b>: <input type='text' name='description' id='description' value='".stripslashes($row2['description'])."'><br/>
							<i>not displayed to public. used internally to help keep track of your forms</i><br/><br/>
							<b>'Thank You' page</b>: <input type='text' name='thank_you' id='thank_you' value='".stripslashes($row2['thank_you'])."'><br/><i>* thank-you page does not work with Authorize.Net; leave blank for default</i><br/><br/>
							
							<input type='submit' value='Save'>
							<br/><br/>To call this form, use the following code: <i>[showT4Sform formId=".$row2['id']."]</i>
						</div>
					</form>
					
					<script src='//code.jquery.com/ui/1.11.4/jquery-ui.js'></script>
					
					<script type='text/javascript'>						
						function checkNumeric(id) {
							if (isNaN(document.getElementById(id).value)) {
								alert('Amount to Charge must be numeric!');
								document.getElementById(id).value = '';
							} 
						}
						
						jQuery('#previewTable').sortable({
							deactivate: function( event, ui ) {
								var x = [];
								jQuery( '.sortableID' ).each(function( index ) {
									x[index] = jQuery(this).val();
								});
								
								var data = {
									'action': 't4s_update_form_fields_order',
									'form': '".$_SESSION['formID']."',
									'x': x		
								};
									
								// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
								jQuery.post(ajaxurl, data, function(response) {
								
								});
							}
						});;
						
					</script>";
	
	//display forms list
	$sql = "SELECT * FROM t4s_forms WHERE active = 1";
	$result = mysql_query($sql);
	while($row=mysql_fetch_array($result)){ 
		if($row['id']==$_SESSION['formID']){ $selected='SELECTED=SELECTED';} else { $selected=''; if($_SESSION['formID']==''){ $_SESSION['formID']=$row['id']; } } $options.="<option ".$selected." value='".$row['id']."'> ".stripslashes($row['name'])." </option>"; 
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
	echo "<div style='padding:20px;font-size:12px;'>";
				echo "<h1 style='display: inline'>Form Creator";
				if (isset($_GET['editform'])) {
					echo ": ".stripslashes($row2['name'])."</h1> <a href='?page=t4s-form-creator'>[ Go Back ]</a><br/><br/>";
				} else {
					echo "</h1><br/><br/>".$msg."<br/>";
				}
				if (isset($_GET['editform'])) {
					if (is_numeric($_GET['editform'])) {
						echo "<div style='float:left;background-color:#ffffff;padding:25px;margin-right: 30px;width: 500px'><h3>Form Preview:</h3>".$preview."</div>";				
						formElementSelect();
						echo $preview2;
					}
				} else {
					echo " <form method='POST'>Create a New Form: <input type='text' id='fname' name='fname' value='form name'> <input type='submit' value='Create'></form>";						
						echo "<hr /><br/><br/>
							  <h2>Existing Forms</h2>";
					showFormsList();
					echo "<br/><br/>";					
				}
				echo "</div>";
	?>
	</div>
	<?php
}


function showFormsList() {

	//CHECK FOR ADMIN WP LOGIN
	if (!current_user_can('manage_options')) {
		exit();
	} 

	$query  = "SELECT * FROM t4s_forms WHERE active = 1 ORDER BY id ASC";
	$result = mysql_query($query);
	$output = "<style>.t4s-tables-list tr td { padding: 5px } </style>
				<table class='t4s-tables-list'>
				<tr>
					<td><b>Form Name</b></td>
					<td><b>Description</b></td>
					<td><b>Submissions</b></td>
					<td><b>Options</b></td>
					<td><b>Short Code</b></td>
				</tr>";
				
	if (mysql_num_rows($result) > 0) {
		for ($i=0;$i<mysql_num_rows($result);$i++) {
			$row = mysql_fetch_assoc($result);
			
			$output .= "<tr>
							<td>".stripslashes($row['name'])."</td>
							<td>".stripslashes($row['description'])."</td>
							<td>".count(returnPaymentRecords(getPaymentRecords($row['id'])))." <a href='?page=t4s-form-payments&form=".$row['id']."'>[view]</a></td>
							<td><a href='?page=t4s-form-creator&editform=".$row['id']."'>[Edit]</a> <a href='?page=t4s-form-creator&deleteform=".$row['id']."' onClick='if (!confirm(\"YOU ARE ABOUT TO DELETE THIS FORM. Proceed?\")) return false'>[Delete]</a> <a href='?page=t4s-form-creator&duplicateform=".$row['id']."'>[Duplicate]</a></td>
							<td><i>[showT4Sform formId=".$row['id']."]</i></td>
						</tr>";
		}	
	}
	$output .= "</table>";
	
	echo $output;

}

function formElementSelect() {

	//CHECK FOR ADMIN WP LOGIN
	if (!current_user_can('manage_options')) {
		exit();
	} 

	if (!isset($_GET['edit'])) {
		echo "<h2>Add a new form element</h2>";
	} else {
		echo "<h2>Editing an element</h2>";
	}

?>

	<div id='elementDiv'>

	<script type='text/javascript'>
		var saveBtn = " <input type='submit' value='Save Form Field'><div style='clear: both'></div>"
		var formBegin = "<form method='POST' action='?page=t4s-form-creator&editform=<?php echo $_SESSION['formID']; ?>'>";
		var formEnd = "</form>";
		var x = document.getElementById('elementDiv');
		
		function addTextbox(id,lbl) {
			
			var str_id = "";
			var val = "";
			if (lbl != null) val = lbl;
			
			if (id != null & lbl != null) {
				str_id = "<input type='hidden' name='edit_id' value='"+id+"'>";
			}
		
			var txt = "Please provide a label or name for your textbox: <input type='textbox' name='newName' value='"+val+"'><input type='hidden' name='type' value='textbox'>"+str_id;
			x.innerHTML = formBegin + txt + saveBtn + formEnd;
		}
		
		function addTextarea(id, lbl) {
		
			var str_id = "";
			var val = "";
			if (lbl != null) val = lbl;
			
			if (id != null & lbl != null) {
				str_id = "<input type='hidden' name='edit_id' value='"+id+"'>";
			}
		
			var txt = "Please provide a label or name for your textarea: <input type='textbox' name='newName' value='"+val+"'><input type='hidden' name='type' value='textarea'>"+str_id;
			x.innerHTML = formBegin + txt + saveBtn + formEnd;
		}
		
		function addCheckbox(id, lbl) {
		
			var str_id = "";
			var val = "";
			if (lbl != null) val = lbl;
			
			if (id != null & lbl != null) {
				str_id = "<input type='hidden' name='edit_id' value='"+id+"'>";
			}
		
			var txt = "Please provide a label or name for your checkbox: <input type='textbox' name='newName' value='"+val+"'><input type='hidden' name='type' value='checkbox'>"+str_id;
			x.innerHTML = formBegin + txt + saveBtn + formEnd;
		}
		
		function addList(list, id, lbl) {
		
			var str_id = "";
			var val = "";
			if (lbl != null) val = lbl;
		
			var txt = "Please provide a label or name for your "+list+" list: <input type='textbox' name='newName' value='"+val+"'><input type='hidden' name='type' value='"+list+"'>";
				
			if (id != null) {
				str_id = "<input type='hidden' name='edit_id' value='"+id+"'>";
			}
			
			var table = document.createElement('TABLE');	
			table.id = 'selectTable';
			table.style.display = 'inline';
			
			<?php if (!isset($_GET['edit'])) { ?>
				addSelectRow(table, 'Yes');
				addSelectRow(table, 'No');
				addSelectRow(table, 'Maybe');
			<?php } else { 
										
					$query = $wpdb->prepare("SELECT * FROM t4s_forms_options WHERE test_fields_id = '%d' ORDER BY ordernum ASC", intval($_GET['edit']));
					$result = mysql_query($query);
					for ($i=0;$i<mysql_num_rows($result);$i++) {
						$row = mysql_fetch_assoc($result);
						echo "addSelectRow(table, '".$row['value']."'); ";
					}				
				  } ?>
				
			var addTxt = "<input type='button' onClick='addSelectRow(document.getElementById(\"selectTable\"))' value='Add an Option' style='margin-left: 20px'>"+str_id;
			
			x.innerHTML = "<br/>";
			x.appendChild(table);
			x.innerHTML = formBegin + txt + x.innerHTML + addTxt + saveBtn + formEnd ;
			
			
		}
		
		function deleteSelectRow(id) {
		
			var row = document.getElementById("row_"+id);
			row.parentNode.removeChild(row);
		
		}
		
		function addSelectRow(table, val) {
		
			var row=table.insertRow(-1);
			
			var cell1=row.insertCell(0);
			var cell2=row.insertCell(1);
			var cell3=row.insertCell(2);
															
			var order = table.rows.length-1;
			
			if (val == null) val = '';
			
			row.id = "row_"+order;
			
			cell1.innerHTML = "<input type='text' name='label_"+order+"' id='label_"+order+"' value='"+val+"'>";
			cell2.innerHTML = "<input type='text' name='order_"+order+"' id='label_"+order+"' value='"+order+"' size=2>";
			cell3.innerHTML = "<a href='#' onClick='deleteSelectRow("+order+")'>delete option</a>";
			
		}
		
	</script>

<?php
	if (!isset($_GET['edit'])) {

?>

			<table cellpadding=3>
				<tr><td>I am a Textbox:</td><td><input type='textbox'></td><td><a href='#' onClick='addTextbox()'>add a Textbox to my Form</a></td></tr>
				<tr><td>I am a Textarea:</td><td><textarea></textarea></td><td><a href='#' onClick='addTextarea()'>add a Textarea to my Form</a></td></tr>
				<tr><td>I am a Checkbox:</td><td><input type='checkbox'> select this</td><td><a href='#' onClick='addCheckbox()'>add a Checkbox to my Form</a></td></tr>
				<tr><td>I am a Select List:</td><td><select><option>Yes</option><option>No</option></select></td><td><a href='#' onClick='addList("select")'>add a Select List to my Form</a></td></tr>
				<tr><td>I am a Radio List:</td><td><input type='radio' name='ex'> yes <input type='radio' name='ex'> no </td><td><a href='#' onClick='addList("radio")'>add a Radio List to my Form</a></td></tr>
			</table>
	
		
<?php 

	} else {
	
		echo "<div id='elementDiv'></div>";
		
		$query = $wpdb->prepare("SELECT * FROM t4s_forms_fields WHERE id = '%d'", intval($_GET['edit']));
		$result = mysql_query($query) or die(mysql_error());
		
		$row = mysql_fetch_assoc($result);
		
		if ($row['name'] == 'textbox') echo "<script type='text/javascript'>addTextbox('".$row['id']."', '".stripslashes($row['label'])."');</script>";
		if ($row['name'] == 'textarea') echo "<script type='text/javascript'>addTextarea('".$row['id']."', '".stripslashes($row['label'])."');</script>";
		if ($row['name'] == 'checkbox') echo "<script type='text/javascript'>addCheckbox('".$row['id']."', '".stripslashes($row['label'])."');</script>";
		if ($row['name'] == 'select') echo "<script type='text/javascript'>addList('select', '".$row['id']."', '".stripslashes($row['label'])."');</script>";
		if ($row['name'] == 'radio') echo "<script type='text/javascript'>addList('radio', '".$row['id']."', '".stripslashes($row['label'])."');</script>";

	}
	
?>

	</div>

<?php
}
?>