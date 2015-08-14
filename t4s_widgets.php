<?php
/**
 * Adds T4S_Widget widget.
 */
class T4S_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			't4s_widget', // Base ID
			__('Tools 4 Shuls Calendar Widget', 'text_domain'), // Name
			array( 'description' => __( 'Tools 4 Shuls Calendar Widget', 'text_domain' ), ) // Args
		);
	}


	// Front-end display of widget.
	public function widget( $args, $instance ) {

		global $wpdb;
				
		require_once("includes/t4s_common.php");

		$mode = $instance['mode'];
					
		$cat = $instance['cat'];
		$before = $instance['before'];
		$after = $instance['after'];
		
		$setts = getT4SSettings(array('t4s_clientID', 't4s_apiKey'));	
		$clientID = $setts['t4s_clientID'];
		$apiKey = $setts['t4s_apiKey'];

		$orig_mode = $mode;
		$mode .= "-".rand(1,999999);
		
		$url = "https://t4s.inspiredonline.com/api/?t4sjs=1&t4sdomobj=divT4S-".$mode."&t4scid=".$clientID."&t4sapk=".$apiKey."&t4su=ignore";
	
		$url .= "&t4smod=cal";
		$url .= "&t4sdisp=".urlencode($orig_mode);		
		$url .= "&t4scat=".urlencode($cat);
		$url .= "&t4swheader=".urlencode($before);
		$url .= "&t4swfooter=".urlencode($after);
				
		?>
		<div id='divT4S-<?php echo $mode; ?>' style="position: relative"></div>
		<script type="text/javascript" src="<?php echo $url; ?>"></script>
		<?php
		
	}


	//Back-end widget form.
	public function form( $instance ) {
	
		//CHECK FOR ADMIN WP LOGIN
		if (!current_user_can('manage_options')) {
			exit();
		} 
	
		require_once("includes/t4s_common.php");
	
		if ( isset( $instance[ 'mode' ] ) ) {
			$mode = $instance[ 'mode' ];
		}
		else {
			$mode = __( 'New title', 'text_domain' );
		}
		
		if ( isset( $instance[ 'cat' ] ) ) {
			$cat = $instance[ 'cat' ];
		}
		else {
			$cat = __( 'New title', 'text_domain' );
		}
		
		if ( isset( $instance[ 'before' ] ) ) {
			$before = $instance[ 'before' ];
		}
		else {
			$before = __( 'New title', 'text_domain' );
		}
		
		if ( isset( $instance[ 'after' ] ) ) {
			$after = $instance[ 'after' ];
		}
		else {
			$after = __( 'New title', 'text_domain' );
		}

		?>
			
			<table class="short_gen_table">
				<tr>
					<td>Calendar Mode</td>
					<td>
						<select name="<?php echo $this->get_field_name( 'mode' ); ?>" id="<?php echo $this->get_field_id( 'mode' ); ?>">
							<option value='full' <?php if ($mode == 'full') echo "selected='selected'"; ?>>Full Calendar</option>
							<option value='upcoming' <?php if ($mode == 'upcoming') echo "selected='selected'"; ?>>Calendar Widget</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Category to Show (Calendar widget mode only)</td>
					<td>	
						<select name="<?php echo $this->get_field_name( 'cat' ); ?>" id="<?php echo $this->get_field_id( 'cat' ); ?>">	
							<option value=''>All</option>
							<?php
								//GET CLIENT'S CATEGORIES
								$id = getT4SSettings('t4s_clientID');
								$cal_cats = getClientCalendarCategories($id);
									
								foreach ($cal_cats as $k => $v) {
									echo "<option value='".esc_html($k)."'";
									if ($cat == $k) echo " selected='selected'";
									echo ">".esc_html($v)."</option>";
								}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td>Text to be shown before the calendar</td>
					<td>
						<textarea name="<?php echo $this->get_field_name( 'before' ); ?>" id="<?php echo $this->get_field_id( 'before' ); ?>" cols=10 rows=3><?php echo $before; ?></textarea>
					</td>
				</tr>
				<tr>
					<td>Text to be shown after the calendar</td>
					<td>
						<textarea name="<?php echo $this->get_field_name( 'after' ); ?>" id="<?php echo $this->get_field_id( 'after' ); ?>" cols=10 rows=3><?php echo $after; ?></textarea>
					</td>
				</tr>		
			</table>

		
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
	
		//CHECK FOR ADMIN WP LOGIN
		if (!current_user_can('manage_options')) {
			exit();
		} 
	
		$instance = $new_instance;
		
		return $instance;
	}

} 


class T4S_Widget2 extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			't4s_widget2', // Base ID
			__('Tools 4 Shuls Donations Widget', 'text_domain'), // Name
			array( 'description' => __( 'Tools 4 Shuls Donations Widget', 'text_domain' ), ) // Args
		);
	}


	// Front-end display of widget.

	public function widget( $args, $instance ) {
	
		global $wpdb;
		global $t4sdon_w;
		
		require_once("includes/t4s_common.php");
	
		$mode = $instance['don_mode'];
			
		$setts = getT4SSettings(array('t4s_clientID', 't4s_apiKey'));	
		$clientID = $setts['t4s_clientID'];
		$apiKey = $setts['t4s_apiKey'];
				
		$url = "https://t4s.inspiredonline.com/api/?t4sjs=1&t4sdomobj=divT4S-".$mode."&t4scid=".$clientID."&t4sapk=".$apiKey;
		
		$url .= "&t4smod=don";
		
		if ($mode == 'full') $url .= "&t4sdisp=full";
		if ($mode == 'Campaigns') $url .= "&t4sdisp=Campaigns";		
		if (substr($mode,0,1) == 'c') $url .= "&t4scat=".urlencode(substr($mode, 1, strlen($mode)-1));
		if (substr($mode,0,1) == 'f') $url .= "&t4sfid=".urlencode(substr($mode, 1, strlen($mode)-1));

		if ($t4sdon_w != true) {
		
			?>

			<div id='divT4S-<?php echo $mode; ?>' style="position: relative"></div>
			<script type="text/javascript" src="<?php echo $url; ?>"></script>
			
			<?php
		} else {
			echo "<i>Only one instance of the Donation widget may be active on a page</i>";
		}
		echo "<br/><br/>";
		$t4sdon_w = true;
	}

	
	// Back-end widget form.

	public function form( $instance ) {
	
		//CHECK FOR ADMIN WP LOGIN
		if (!current_user_can('manage_options')) {
			exit();
		} 
	
		require_once("includes/t4s_common.php");
	
		if ( isset( $instance[ 'don_mode' ] ) ) {
			$mode = $instance[ 'don_mode' ];
		}
		else {
			$mode = __( 'full', 'text_domain' );
		}		
		
		$id = getT4SSettings('t4s_clientID');
		$result = getClientDonationsCategories($id, false, true);

		$funds = array();
		foreach ($result as $r) {
			$res = explode("[t4s]", $r);
			$result2 = getClientDonationsFunds($id, $res[0], true);
			if ($result2[0] != "") {
				$rr = explode("[t4s]", $result2[0]);
				$funds[] = $rr;
			}
		}

		$sel = $mode;
			
		?>

		<div style="shortcode-left">
			<select name='<?php echo $this->get_field_name( 'don_mode' ); ?>' id="<?php echo $this->get_field_id( 'don_mode' ); ?>">
				<option value='full' <?php if ($sel == 'full') echo "selected='selected'"; ?>>All Donations Categories</option>
				<option value='Campaigns' <?php if ($sel == 'Campaigns') echo "selected='selected'"; ?>>Show Campaigns Only</option>
				<option value='' disabled='disabled'>Specific Category Display</option>
				<?php showAllCategoriesDonList($result, $sel); ?>
				<option value='' disabled='disabled'>Specific Fund Display</option>
				<?php showAllFundsDonList($funds, $sel); ?>
			</select>
		</div>
		<?php
	}


	public function update( $new_instance, $old_instance ) {
		
		//CHECK FOR ADMIN WP LOGIN
		if (!current_user_can('manage_options')) {
			exit();
		} 
		
		$instance = $new_instance;
			

		return $instance;
	}

} 


function showAllCategoriesDonList($input, $sel) {
	
	foreach ($input as $l) {
		if ($l != "") {
			$r1 = explode("[t4s]", $l);		
			echo "<option value='c".$r1[0]."'";
			if ($sel == "c".$r1[0]) echo " selected='selected' ";
			echo ">&#160;&#160;&#160;&#160;".$r1[1]."</option>";
		}
	}
	
}


function showAllFundsDonList($input, $self) {
	
	foreach ($input as $l) {		
			echo "<option value='f".$l[0]."'";
			if ($sel == "f".$l[0]) echo " selected='selected' ";
			echo ">&#160;&#160;&#160;&#160;".$l[1]."</option>";
	}
	
} 