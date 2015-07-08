
<h2>Please provide your T4S credentials</h2>

<form method='POST'>
  <table>
    <tr>
	  <td>
		Login: 
	  </td>
	  <td>
		<input type='text' name='t4s_login' id='t4s_login' value='<?php echo $t4s_creds['login'];?>'>
	  </td>
	</tr>
	<tr>
	  <td>
		Password: 
	  </td>
	  <td>
	    <input type='password' name='t4s_pwd' id='t4s_pwd' value=''>
	  </td>
	</tr>
  </table>
  <input type='submit' value='Save'>
</form>

<br/><br/>

No T4S account? <a href='https://tools4shuls.inspiredonline.com/register.php' target=_blank>Register one here!</a>