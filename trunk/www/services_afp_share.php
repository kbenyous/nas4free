<?php
/*
  services_afp_share.php

  Part of NAS4Free (http://www.nas4free.org).
  Copyright (c) 2012-2017 The NAS4Free Project <info@nas4free.org>.
  All rights reserved.

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
require 'co_sphere.php';

function afpshare_process_updatenotification($mode,$data) {
	global $config;

	$retval = 0;
	$sphere = &services_afp_share_get_sphere();
	switch($mode):
		case UPDATENOTIFY_MODE_NEW:
		case UPDATENOTIFY_MODE_MODIFIED:
			break;
		case UPDATENOTIFY_MODE_DIRTY_CONFIG:
		case UPDATENOTIFY_MODE_DIRTY:
			if(false !== ($sphere->row_id = array_search_ex($data,$sphere->grid,$sphere->row_identifier()))):
				unset($sphere->grid[$sphere->row_id]);
				write_config();
			endif;
			break;
	endswitch;
	return $retval;
}
function services_afp_share_get_sphere() {
	global $config;
	$sphere = new co_sphere_grid('services_afp_share','php');
	$sphere->mod = new co_sphere_scriptname($sphere->basename() . '_edit','php');
	$sphere->notifier('afpshare');
	$sphere->row_identifier('uuid');
	$sphere->enadis(false);
	$sphere->lock(false);
	$sphere->sym_add(gtext('Add Share'));
	$sphere->sym_mod(gtext('Edit Share'));
	$sphere->sym_del(gtext('Share is marked for deletion'));
	$sphere->sym_loc(gtext('Share is protected'));
	$sphere->sym_unl(gtext('Share is unlocked'));
	$sphere->cbm_delete(gtext('Delete Selected Shares'));
	$sphere->cbm_delete_confirm(gtext('Do you want to delete selected shares?'));
	$sphere->grid = &array_make_branch($config,'afp','share');
	return $sphere;
}
$sphere = &services_afp_share_get_sphere();
if($_POST):
	if(isset($_POST['apply']) && $_POST['apply']):
		$retval = 0;
		if(!file_exists($d_sysrebootreqd_path)):
			$retval	 |= updatenotify_process($sphere->notifier(),$sphere->notifier_processor());
			config_lock();
			$retval	 |= rc_update_service('netatalk');
			$retval	 |= rc_update_service('mdnsresponder');
			config_unlock();
		endif;
		$savemsg = get_std_save_message($retval);
		if($retval == 0):
			updatenotify_delete($sphere->notifier());
		endif;
		header($sphere->header());
		exit;
	endif;
	if(isset($_POST['submit'])):
		switch($_POST['submit']):
			case 'rows.delete':
				$sphere->cbm_array = $_POST[$sphere->cbm_name] ?? [];
				foreach($sphere->cbm_array as $sphere->cbm_row):
					if(false !== ($sphere->row_id = array_search_ex($sphere->cbm_row,$sphere->grid,$sphere->row_identifier()))):
						$mode_updatenotify = updatenotify_get_mode($sphere->notifier(),$sphere->grid[$sphere->row_id][$sphere->row_identifier()]);
						switch ($mode_updatenotify):
							case UPDATENOTIFY_MODE_NEW:  
								updatenotify_clear($sphere->notifier(),$sphere->grid[$sphere->row_id][$sphere->row_identifier()]);
								updatenotify_set($sphere->notifier(),UPDATENOTIFY_MODE_DIRTY_CONFIG,$sphere->grid[$sphere->row_id][$sphere->row_identifier()]);
								break;
							case UPDATENOTIFY_MODE_MODIFIED:
								updatenotify_clear($sphere->notifier(),$sphere->grid[$sphere->row_id][$sphere->row_identifier()]);
								updatenotify_set($sphere->notifier(),UPDATENOTIFY_MODE_DIRTY,$sphere->grid[$sphere->row_id][$sphere->row_identifier()]);
								break;
							case UPDATENOTIFY_MODE_UNKNOWN:
								updatenotify_set($sphere->notifier(),UPDATENOTIFY_MODE_DIRTY,$sphere->grid[$sphere->row_id][$sphere->row_identifier()]);
								break;
						endswitch;
					endif;
				endforeach;
//				header($sphere->header());
//				exit;
				break;
			case 'rows.enable':
				break;
			case 'rows.diable':
				break;
			case 'rows.toggle':
				break;
		endswitch;
	endif;
endif;
array_sort_key($sphere->grid,'name');
$pgtitle = [gtext('Services'),gtext('AFP'),gtext('Shares')];
include 'fbegin.inc';
?>
<script type="text/javascript">
//<![CDATA[
$(window).on("load", function() {
<?php
//	Init action buttons.
if($sphere->enadis()):
	if($sphere->toggle()):
?>
	$("#toggle_selected_rows").click(function () {
		return confirm('<?=$sphere->cbm_toggle_confirm();?>');
	});
<?php
	else:
?>
	$("#enable_selected_rows").click(function () {
		return confirm('<?=$sphere->cbm_enable_confirm();?>');
	});
	$("#disable_selected_rows").click(function () {
		return confirm('<?=$sphere->cbm_disable_confirm();?>');
	});
<?php
	endif;
endif;
?>
	$("#delete_selected_rows").click(function () {
		return confirm('<?=$sphere->cbm_delete_confirm();?>');
	});
<?php
//	Disable action buttons.
?>
	ab_disable(true);
<?php
//	Init toggle checkbox.
?>
	$("#togglemembers").click(function() {
		cb_tbn(this, "<?=$sphere->cbm_name;?>[]");
	});
<?php
//	Init member checkboxes.
?>
	$("input[name='<?=$sphere->cbm_name;?>[]']").click(function() {
		ab_control(this, '<?=$sphere->cbm_name;?>[]');
	});
<?php
//	Init spinner.
?>
	$("#iform").submit(function() { spinner(); });
	$(".spin").click(function() { spinner(); });
});
function ab_disable(flag) {
<?php
if($sphere->enadis()):
	if($sphere->toggle()):
?>
	$("#toggle_selected_rows").prop("disabled", flag);
<?php
	else:
?>
	$("#enable_selected_rows").prop("disabled", flag);
	$("#disable_selected_rows").prop("disabled", flag);
<?php
	endif;
endif;
?>
	$("#delete_selected_rows").prop("disabled", flag);
}
function cb_tbn(ego, tbn) {
	var cba = $("input[name='"+tbn+"']").filter(":enabled");
	cba.prop("checked", function(_, checked) { return !checked; });
	ab_disable(1 > cba.filter(":checked").length);
	ego.checked = false;
}
function ab_control(ego, tbn) {
	var cba = $("input[name='"+tbn+"']").filter(":enabled");
	ab_disable(1 > cba.filter(":checked").length);
}
//]]>
</script>
<table id="area_navigator"><tbody>
	<tr><td class="tabnavtbl"><ul id="tabnav">
		<li class="tabinact"><a href="services_afp.php"><span><?=gtext('Settings');?></span></a></li>
		<li class="tabact"><a href="<?=$sphere->scriptname();?>" title="<?=gtext('Reload page');?>"><span><?=gtext('Shares');?></span></a></li>
	</ul></td></tr>
</tbody></table>
<table id="area_data"><tbody><tr><td id="area_data_frame"><form action="<?=$sphere->scriptname();?>" method="post" name="iform" id="iform">
<?php
	if(!empty($savemsg)):
		print_info_box($savemsg);
	endif;
	if(file_exists($d_sysrebootreqd_path)):
		print_info_box(get_std_save_message(0));
	endif;
	if(updatenotify_exists($sphere->notifier())):
		print_config_change_box();
	endif;
?>
	<table class="area_data_selection">
		<colgroup>
			<col style="width:5%">
			<col style="width:15%">
			<col style="width:35%">
			<col style="width:35%">
			<col style="width:10%">
		</colgroup>
		<thead>
<?php
			html_titleline2(gtext('Overview'),5);
?>
			<tr>
				<th class="lhelc"><input type="checkbox" id="togglemembers" name="togglemembers" title="<?=gtext('Invert Selection');?>"/></th>
				<th class="lhell"><?=gtext('Name');?></th>
				<th class="lhell"><?=gtext('Path');?></th>
				<th class="lhell"><?=gtext('Comment');?></th>
				<th class="lhebl"><?=gtext('Toolbox');?></th>
			</tr>
		</thead>
		<tfoot>
<?php
			echo html_row_add($sphere->mod->scriptname(),$sphere->sym_add(),5);
?>
		</tfoot>
		<tbody>
<?php
			foreach($sphere->grid as $sphere->row):
				$notificationmode = updatenotify_get_mode($sphere->notifier(),$sphere->row[$sphere->row_identifier()]);
				$notdirty = (UPDATENOTIFY_MODE_DIRTY != $notificationmode) && (UPDATENOTIFY_MODE_DIRTY_CONFIG != $notificationmode);
				$enabled = $sphere->enadis() ? isset($sphere->row['enable']) : true;
				$notprotected = $sphere->lock() ? !isset($sphere->row['protected']) : true;
?>
				<tr>
					<td class="<?=$enabled ? "lcelc" : "lcelcd";?>">
<?php
						if($notdirty && $notprotected):
?>
							<input type="checkbox" name="<?=$sphere->cbm_name;?>[]" value="<?=$sphere->row[$sphere->row_identifier()];?>" id="<?=$sphere->row[$sphere->row_identifier()];?>"/>
<?php
						else:
?>
							<input type="checkbox" name="<?=$sphere->cbm_name;?>[]" value="<?=$sphere->row[$sphere->row_identifier()];?>" id="<?=$sphere->row[$sphere->row_identifier()];?>" disabled="disabled"/>
<?php
						endif;
?>
					</td>
					<td class="<?=$enabled ? "lcell" : "lcelld";?>"><?=htmlspecialchars($sphere->row['name']);?></td>
					<td class="<?=$enabled ? "lcell" : "lcelld";?>"><?=htmlspecialchars($sphere->row['path']);?></td>
					<td class="<?=$enabled ? "lcell" : "lcelld";?>"><?=htmlspecialchars($sphere->row['comment']);?></td>
					<td class="lcebld">
						<table class="area_data_selection_toolbox"><colgroup><col style="width:33%"><col style="width:34%"><col style="width:33%"></colgroup><tbody><tr>
<?php
							$helpinghand = sprintf('%s?%s=%s',$sphere->mod->scriptname(),$sphere->row_identifier(),$sphere->row[$sphere->row_identifier()]);
							echo html_row_toolbox($helpinghand,$sphere->sym_mod(),$sphere->sym_del(),$sphere->sym_loc(),$notprotected,$notdirty);
?>
							<td></td>
							<td></td>
						</tr></tbody></table>
					</td>
				</tr>
<?php
			endforeach;
?>
		</tbody>
	</table>
	<div id="submit">
<?php
		if($sphere->enadis()):
			if($sphere->toggle()):
				echo html_button_toggle_rows($sphere->cbm_toggle());
			else:
				echo html_button_enable_rows($sphere->cbm_enable());
				echo html_button_disable_rows($sphere->cbm_disable());
			endif;
		endif;
		echo html_button_delete_rows($sphere->cbm_delete());
?>
	</div>
	<div id="remarks">
<?php
		html_remark2('note',gtext('Note'),gtext("All shares use the option 'usedots' thus making the filenames .Parent and anything beginning with .Apple illegal."));
?>
	</div>
<?php
	include 'formend.inc';
?>
</form></td></tr></tbody></table>
<?php
include 'fend.inc';
?>
