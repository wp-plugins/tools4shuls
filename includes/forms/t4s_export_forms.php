<?php

//AJAX
add_action('wp_ajax_t4s_export_forms_callback', 't4s_export_forms_callback');

function t4s_export_forms_callback() {

	error_reporting(E_ALL);
	ini_set('display_errors', '0');

	global $wpdb;
	
	include_once(plugin_dir_path( dirname( __FILE__ ) )."/lib/xlsxwriter.class.php");
	
	if (!$_GET && !$_POST) {
		exit();
	}

	$hash = getT4Shash();

	if ($hash != $_GET['hash']) {
		exit();
	}

	$fid = intval($_GET['form']);

	$sql2 = $wpdb->prepare("SELECT * FROM t4s_forms WHERE id='%d'",$fid);
	$result2 = mysql_query($sql2);

	$row2 = mysql_fetch_assoc($result2);

	$file_name = $row2['name'];

	//Get all fields names AKA headers
	$query = $wpdb->prepare("SELECT * FROM t4s_forms_fields WHERE t4s_forms_Id = '%d' ORDER BY ordernum", $fid);
	$result = mysql_query($query);

	$fields = array();
	for ($i=0;$i<mysql_num_rows($result);$i++) {
		$row = mysql_fetch_assoc($result);
		$fields[$row['id']] = $row['label'];
	}

	$q = $wpdb->prepare("SELECT name from t4s_forms WHERE id = '%s'", $fid);
	$r = mysql_query($q);
	$r2 = mysql_fetch_assoc($r);		
			
	$tmp = array();
	$cnt = 1;
	$header = array(0 => '');
	foreach ($fields as $k => $v) {
		$field_header[$cnt] = $v;
		$header[$v] = $cnt;
		$cnt++;
	}

	$writer = new XLSXWriter();
	$writer->writeSheetRow('Sheet1', array($r2['name']) );
	$writer->writeSheetRow('Sheet1', $field_header );

	//Get actual submission data
	$final = getPaymentRecords($fid);	
	$recs = returnPaymentRecords($final);

	foreach ($recs as $r) {

		$tmp = array();
		
		foreach ($header as $v => $k) {
			$sub = "";
			$date = "";
			foreach ($r as $f) {
				if ($f[0] != 'item_name' && $f[0] != 'amount' && $f[0] != 'x_amount' && $k > 0) {
					if ($f[5] == $v) {
						$tmp[$k] = $f[4];
					}
				} else if ($f[0] == 'amount' || $f[0] == 'x_amount') {
					$sub = "$".$f[4]." via ".$f[1]." by ".$f[3];
					$date = $f[6];
				}
			}		
		}
			
		$tmp[count($header)] = $date;
		$tmp[count($header)+1] = $sub;
		
		$writer->writeSheetRow('Sheet1', $tmp );
		
	}


	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="' . $file_name . '.xlsx"');
	$writer->writeToStdOut('$file_name.xlsx');
	exit();

}

?>