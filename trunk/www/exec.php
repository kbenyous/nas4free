<?php
/*
	exec.php

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2017 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Exec+ v1.02-000 - Copyright 2001-2003, All rights reserved
	Created by technologEase (http://www.technologEase.com).

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies,
	either expressed or implied, of the NAS4Free Project.
*/
require 'auth.inc';
require 'guiconfig.inc';

// Function: is Blank
// Returns true or false depending on blankness of argument.
function isBlank($arg) {
	return preg_match("/^\s*$/",$arg);
}
function hasContent(string $test = '') {
	return (false != preg_match('/\S/',$test));
}
$pgtitle = [gtext('Tools'),gtext('Execute Command')];
?>
<?php include 'fbegin.inc';?>
<script type="text/javascript">
//<![CDATA[
$(window).on("load", function() {
	// Init onsubmit()
	$("#iform").submit(function() {
		spinner();
	});
	$("#txtCommand").click(function () { txtCommand_onKey(event) });
});

	// Create recall buffer array (of encoded strings).
	<?php
	if(!isset($_POST['txtRecallBuffer']) || isBlank( $_POST['txtRecallBuffer'] )):
		echo "   var arrRecallBuffer = new Array;\n";
	else:
		echo "   var arrRecallBuffer = new Array(\n";
		$arrBuffer = explode('&',$_POST['txtRecallBuffer']);
		for ($i=0;$i < (count($arrBuffer)-1);$i++):
			echo "      '",$arrBuffer[$i],"',\n";
		endfor;
		echo "      '",$arrBuffer[count($arrBuffer) - 1],"'\n";
		echo "   );\n";
	endif;
	?>
	// Set pointer to end of recall buffer.
	var intRecallPtr = arrRecallBuffer.length;

	// Functions to extend String class.
	function str_encode() { return escape( this ) }
	function str_decode() { return unescape( this ) }

	// Extend string class to include encode() and decode() functions.
	String.prototype.encode = str_encode
	String.prototype.decode = str_decode

	// Function: is Blank
	// Returns boolean true or false if argument is blank.
	function isBlank( strArg ) { return strArg.match( /^\s*$/ ) }

	// Function: frmExecPlus onSubmit (event handler)
	// Builds the recall buffer from the command string on submit.
	function frmExecPlus_onSubmit( form ) {
		if (!isBlank(form.txtCommand.value)) {
			// If this command is repeat of last command, then do not store command.
			if (form.txtCommand.value.encode() == arrRecallBuffer[arrRecallBuffer.length-1]) { return true }
			// Stuff encoded command string into the recall buffer.
			if (isBlank(form.txtRecallBuffer.value))
				form.txtRecallBuffer.value = form.txtCommand.value.encode();
			else
				form.txtRecallBuffer.value += '&' + form.txtCommand.value.encode();
		}
		return true;
	}

	// Function: btnRecall onClick (event handler)
	// Recalls command buffer going either up or down.
	function btnRecall_onClick( form, n ) {
		// If nothing in recall buffer, then error.
		if (!arrRecallBuffer.length) {
			alert( 'Nothing to recall!' );
			form.txtCommand.focus();
			return;
		}
		// Increment recall buffer pointer in positive or negative direction
		// according to <n>.
		intRecallPtr += n;
		// Make sure the buffer stays circular.
		if (intRecallPtr < 0) { intRecallPtr = arrRecallBuffer.length - 1 }
		if (intRecallPtr > (arrRecallBuffer.length - 1)) { intRecallPtr = 0 }
		// Recall the command.
		form.txtCommand.value = arrRecallBuffer[intRecallPtr].decode();
	}

	// Function: Reset onClick (event handler)
	// Resets form on reset button click event.
	function Reset_onClick( form ) {
		// Reset recall buffer pointer.
		intRecallPtr = arrRecallBuffer.length;
		// Clear form (could have spaces in it) and return focus ready for cmd.
		form.txtCommand.value = '';
		form.txtCommand.focus();
		return true;
	}

	// hansmi, 2005-01-13
	function txtCommand_onKey(e) {
		if(!e) var e = window.event; // IE-Fix
		var code = (e.keyCode?e.keyCode:(e.which?e.which:0));
		if(!code) return;
		var f = document.getElementsByName('frmExecPlus')[0];
		if(!f) return;
		switch(code) {
			case 38: // up
				btnRecall_onClick(f, -1);
				break;
			case 40: // down
				btnRecall_onClick(f, 1);
				break;
		}
	}
//]]>
</script>
<form action="<?=$_SERVER['SCRIPT_NAME'];?>" method="post" enctype="multipart/form-data" name="frmExecPlus" id="frmExecPlus" onsubmit="return frmExecPlus_onSubmit(this);">
	<table id="area_data"><tbody><tr><td id="area_data_frame">
		<?php
		print_info_box(gtext('This is a very powerful tool. Use at your own risk!'));
		?>
		<table class="area_data_settings">
			<colgroup>
				<col class="area_data_settings_col_tag">
				<col class="area_data_settings_col_data">
			</colgroup>
			<thead>
				<?php html_titleline2(gtext('Command'));?>
			</thead>
			<tfoot>
			</tfoot>
			<tbody>
				<?php
				html_inputbox2('txtCommand',gtext('Command'),'','',false,80,false,false,1024,gtext('Enter Command'));
				?>
				<tr>
					<td class="celltag"><?=gtext('Control');?></td>
					<td class="celldata">
						<input type="hidden" name="txtRecallBuffer" value="<?=!empty($_POST['txtRecallBuffer']) ? $_POST['txtRecallBuffer'] : '';?>"/>
						<input type="button" class="formbtn" name="btnRecallPrev" value="&lt;" onclick="btnRecall_onClick( this.form, -1 );"/>
						<input type="submit" class="formbtn" value="<?=gtext('Execute');?>"/>
						<input type="button" class="formbtn" name="btnRecallNext" value="&gt;" onclick="btnRecall_onClick( this.form,  1 );"/>
						<input type="button" class="formbtn" value="<?=gtext('Clear');?>" onclick="return Reset_onClick( this.form );"/>
					</td>
				</tr>
			</tbody>
		</table>
		<table class="area_data_settings">
			<colgroup>
				<col class="area_data_settings_col_tag">
				<col class="area_data_settings_col_data">
			</colgroup>
			<thead>
				<?php html_separator2();?>
				<?php html_titleline2(gtext('PHP Command'));?>
			</thead>
			<tfoot>
			</tfoot>
			<tbody>
				<tr>
					<td class="celltag"><?=gtext('PHP Command');?></td>
					<td class="celldata"><textarea id="txtPHPCommand" name="txtPHPCommand" rows="3" cols="49" wrap="off"><?=htmlspecialchars(!empty($_POST['txtPHPCommand']) ? $_POST['txtPHPCommand'] : '');?></textarea></td>
				</tr>
				<tr>
					<td class="celltag"><?=gtext('Control');?></td>
					<td class="celldata">
						<input type="submit" class="formbtn" value="<?=gtext('Execute');?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<?php
		if(isset($_POST['txtCommand'])):
			if(!isBlank($_POST['txtCommand'])):?>
				<table class="area_data_settings">
					<colgroup>
						<col class="area_data_settings_col_tag">
						<col class="area_data_settings_col_data">
					</colgroup>
					<thead>
						<?php html_separator2();?>
						<?php html_titleline2(gtext('Command Output'));?>
					</thead>
					<tbody>
					</tbody>
				</table>
				<?php
				echo '<div>','<pre class="celldata">';
					echo "\$ ",htmlspecialchars($_POST['txtCommand']),"\n";
					putenv('PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin');
					putenv('COLUMNS=1024');
					putenv("SCRIPT_FILENAME=" .strtok($_POST['txtCommand'],' ')); /* PHP scripts */
					$ph = popen($_POST['txtCommand'],'r');
					while($line = fgets($ph)):
						echo htmlspecialchars($line);
					endwhile;
					pclose($ph);
				echo '</pre>','</div>';
			endif;
		endif;
		?>
		<?php
		if(isset($_POST['txtPHPCommand'])):
			if(!isBlank($_POST['txtPHPCommand'])):?>
				<table class="area_data_settings">
					<colgroup>
						<col class="area_data_settings_col_tag">
						<col class="area_data_settings_col_data">
					</colgroup>
					<thead>
						<?php html_separator2();?>
						<?php html_titleline2(gtext('PHP Command Output'));?>
					</thead>
					<tbody>
					</tbody>
				</table>
				<?php
				echo '<div>','<pre class="celldata">';
					require_once('config.inc');
					require_once('functions.inc');
					require_once('util.inc');
					require_once('rc.inc');
					require_once('email.inc');
					require_once('tui.inc');
					require_once('array.inc');
					require_once('services.inc');
					require_once('zfs.inc');
					echo eval($_POST['txtPHPCommand']);
				echo '</pre>','</div>';
			endif;
		endif;
		?>
</td></tr></tbody></table>
<?php include 'formend.inc';?>
</form>
<script type="text/javascript">
//<![CDATA[
document.forms[0].txtCommand.focus();
//]]>
</script>
<?php include 'fend.inc';?>
