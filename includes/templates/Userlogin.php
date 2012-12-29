<?php
/**
 * @addtogroup Templates
 */
if( !defined( 'MEDIAWIKI' ) ) die( -1 );

/** */
require_once( 'includes/SkinTemplate.php' );

/**
 * HTML template for Special:Userlogin form
 * @addtogroup Templates
 */
class UserloginTemplate extends QuickTemplate {
	function execute() {
		if( $this->data['message'] ) {
?>
	<div class="<?php $this->text('messagetype') ?>box">
		<?php if ( $this->data['messagetype'] == 'error' ) { ?>
			<h2><?php $this->msg('loginerror') ?>:</h2>
		<?php } ?>
		<?php $this->html('message') ?>
	</div>
	<div class="visualClear"></div>
<?php } ?>

<div id="userloginForm">

	<h2><?php $this->msg('login') ?></h2>
<table border="0" width="100%"><tr><td rowspan="2" style="border-right:1px dashed #cccccc;">
	<h3><?php $this->msg('login') ?></h3>
<form name="userlogin" method="post" action="<?php $this->text('action') ?>">
	<?php $this->html('header'); /* pre-table point for form plugins... */ ?>
	<div id="userloginprompt"><?php  $this->msgWiki('loginprompt') ?></div>
	<table> 
		<tr>
			<td class="mw-label"><label for='wpName1'><?php $this->msg('yourname') ?></label></td>
			<td class="mw-input">
				<input type='text' class='loginText' name="wpName" id="wpName1"
					value="<?php $this->text('name') ?>" size='20' />
			</td>
		</tr>
		<tr>
			<td class="mw-label"><label for='wpPassword1'><?php $this->msg('yourpassword') ?></label></td>
			<td class="mw-input">
				<input type='password' class='loginPassword' name="wpPassword" id="wpPassword1"
					value="" size='20' />
			</td>
		</tr>
	<?php if( $this->data['usedomain'] ) {
		$doms = "";
		foreach( $this->data['domainnames'] as $dom ) {
			$doms .= "<option>" . htmlspecialchars( $dom ) . "</option>";
		}
	?>
		<tr>
			<td class="mw-label"><?php $this->msg( 'yourdomainname' ) ?></td>
			<td class="mw-input">
				<select name="wpDomain" value="<?php $this->text( 'domain' ) ?>">
					<?php echo $doms ?>
				</select>
			</td>
		</tr>
	<?php } ?>
		<tr>
			<td class="mw-input" colspan="2">
				<input type='checkbox' name="wpRemember"
					value="1" id="wpRemember" 
					checked="checked"
					/> <label for="wpRemember"><?php $this->msg('remembermypassword') ?></label>
			</td>
		</tr>
		<tr>
			<td class="mw-submit" colspan="2" >
				<input type='submit' class="button button100 submit_button" onmouseout='button_unswap(this);' onmouseover='button_swap(this);' name="wpLoginattempt" id="wpLoginattempt" value="<?php $this->msg('login') ?>" />
			<?php if( $this->data['useemail'] && $this->data['canreset']) { ?> 
				<input type='submit' class="button white_button_150 submit_button" onmouseout='button_unswap(this);' onmouseover='button_swap(this);' name="wpMailmypassword" id="wpMailmypassword" value="<?php $this->msg('mailmypassword') ?>" />
			 <?php } ?>
			</td>
		</tr>
	</table>
</form>

</td>
<td style='border-bottom:1px dashed #cccccc; text-align: left; padding-left: 20px; vertical-align: top; height:108px;'>
        <h3>Sign Up</h3>
	<?php $this->html('link') ?>
</td></tr>
<tr>
<td style="padding-left:20px">
<?=wfMsg('facebook_account')?>

</td>
</tr>
</table>

</div>
<div id="loginend"><?php $this->msgWiki( 'loginend' ); ?></div>
<?php

	}
}

/**
 * @addtogroup Templates
 */
class UsercreateTemplate extends QuickTemplate {
	function execute() {
			
		if( $this->data['message'] ) {
?>
	<div class="<?php $this->text('messagetype') ?>box">
		<?php if ( $this->data['messagetype'] == 'error' ) { ?>
			<h2><?php $this->msg('loginerror') ?>:</h2>
		<?php } ?>
		<?php $this->html('message') ?>
	</div>
	<div class="visualClear"></div>
<?php } ?>
 <style type='text/css'>
 .hiderow {
   display:none;
 }
 </style>
 <script type='text/javascript'>
   function show_hide_real_name() {
       row = document.getElementById('real_name_row');
       if (document.userlogin2.wpUseRealNameAsDisplay.checked) {
           row.className='';
       } else {
           row.className = 'hiderow'
           document.userlogin2.wpRealName.value = '';
       }
   }   
 </script>
<div id="userlogin">

<form name="userlogin2" id="userlogin2" method="post" action="<?php $this->text('action') ?>">
	<h2><?php $this->msg('createaccount') ?></h2>
	<p id="userloginlink"><?php $this->html('link') ?></p>
	<?php $this->html('header'); /* pre-table point for form plugins... */ ?>
	<table>
		<tr>
			<td class="mw-label"><label for='wpName2'><?php $this->msg('yourname') ?></label></td>
			<td class="mw-input">
				<input type='text' class='loginText' name="wpName" id="wpName2"
					value="<?php $this->text('name') ?>" size='20' tabindex='2'/>
			</td>
		</tr>
		<tr>
			<td class="mw-label"><label for='wpPassword2'><?php $this->msg('yourpassword') ?></label></td>
			<td class="mw-input">
				<input type='password' class='loginPassword' name="wpPassword" id="wpPassword2"
					tabindex="2"
					value="" size='20' />
			</td>
		</tr>
	<?php if( $this->data['usedomain'] ) {
		$doms = "";
		foreach( $this->data['domainnames'] as $dom ) {
			$doms .= "<option>" . htmlspecialchars( $dom ) . "</option>";
		}
	?>
		<tr>
			<td class="mw-label"><?php $this->msg( 'yourdomainname' ) ?></td>
			<td class="mw-input">
				<select name="wpDomain" value="<?php $this->text( 'domain' ) ?>"
					tabindex="3">
					<?php echo $doms ?>
				</select>
			</td>
		</tr>
	<?php } ?>
		<tr>
			<td class="mw-label"><label for='wpRetype'><?php $this->msg('yourpasswordagain') ?></label></td>
			<td class="mw-input">
				<input type='password' class='loginPassword' name="wpRetype" id="wpRetype"
					tabindex="4"
					value=""
					size='20' />
			</td>
		</tr>
		<tr>
			<?php if( $this->data['useemail'] ) { ?>
				<td class="mw-label" ><label for='wpEmail'><?php $this->msg('youremail') ?></label></td>
				<td class="mw-input" >
					<input type='text' class='loginText' name="wpEmail" id="wpEmail"
						tabindex="5"
						value="<?php $this->text('email') ?>" size='20' />
				</td>
			<?php } ?>
			<?php if( $this->data['userealname'] ) { ?>
				</tr>
               <tr>
                   <td colspan=2><input type='checkbox' id='wpUseRealNameAsDisplay' name='wpUseRealNameAsDisplay' onchange='show_hide_real_name();' tabindex='6'/><label for="wpUseRealNameAsDisplay">
                   <?php $this->msg('user_real_name_display'); ?>
                   </label>
               </td>
               </tr>
               
               <tr id='real_name_row' class='hiderow'>
					<td class="mw-label"><label for='wpRealName'><?php $this->msg('yourrealname') ?></label></td>
					<td class="mw-input">
						<input type='text' class='loginText' name="wpRealName" id="wpRealName"
							tabindex="7"
							value="<?php $this->text('realname') ?>" size='20' />
					</td>
				</tr>
			<?php } ?>
		</tr>
		<tr>
			<td></td>
			<td class="mw-input">
				<input type='checkbox' name="wpRemember"
					value="1" id="wpRemember" checked="checked" tabindex='8'
					/> <label for="wpRemember"><?php $this->msg('remembermypassword') ?></label>
			</td>
		</tr>
		<tr>
			<td></td>
			<td class="mw-submit">
				<input type='submit' name="wpCreateaccount" id="wpCreateaccount"
					tabindex="8"
					value="<?php $this->msg('createaccount') ?>" />
				<?php if( $this->data['createemail'] ) { ?>
				<input type='submit' name="wpCreateaccountMail" id="wpCreateaccountMail"
					tabindex="9"
					value="<?php $this->msg('createaccountmail') ?>" />
				<?php } ?>
			</td>
		</tr>
	</table>
<?php 
    if( $this->data['useemail'] ) {
           echo '<div id="login-emailforlost">';
           $this->msgWiki( 'emailforlost' );
            echo '</div>';
        }

if( @$this->haveData( 'uselang' ) ) { ?><input type="hidden" name="uselang" value="<?php $this->text( 'uselang' ); ?>" /><?php } ?>
</form>
</div>
<div id="signupend"><?php $this->msgWiki( 'signupend' ); ?></div>
<?php

	}
}

?>
