<?php
/*
	disks_raid_gconcat_edit.php

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2016 The NAS4Free Project <info@nas4free.org>.
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
require("auth.inc");
require("guiconfig.inc");

$sphere_scriptname = basename(__FILE__);
$sphere_header = 'Location: '.$sphere_scriptname;
$sphere_header_parent = 'Location: disks_raid_gconcat.php';
$sphere_notifier = 'raid_gconcat';
$sphere_array = [];
$sphere_record = [];
$checkbox_member_name = 'checkbox_member_array';
$checkbox_member_array = [];
$checkbox_member_record = [];
$gt_record_loc = gettext('RAID device is already in use.');
$gt_record_opn = gettext('RAID device can be removed.');
$prerequisites_ok = true;
$img_path = [
	'add' => 'images/add.png',
	'mod' => 'images/edit.png',
	'del' => 'images/delete.png',
	'loc' => 'images/locked.png',
	'unl' => 'images/unlocked.png'
];

$mode_page = ($_POST) ? PAGE_MODE_POST : (($_GET) ? PAGE_MODE_EDIT : PAGE_MODE_ADD); // detect page mode
if (PAGE_MODE_POST == $mode_page) { // POST is Cancel or not Submit => cleanup
	if ((isset($_POST['Cancel']) && $_POST['Cancel']) || !(isset($_POST['Submit']) && $_POST['Submit'])) {
		header($sphere_header_parent);
		exit;
	}
}

if ((PAGE_MODE_POST == $mode_page) && isset($_POST['uuid']) && is_uuid_v4($_POST['uuid'])) {
	$sphere_record['uuid'] = $_POST['uuid'];
} else {
	if ((PAGE_MODE_EDIT == $mode_page) && isset($_GET['uuid']) && is_uuid_v4($_GET['uuid'])) {
		$sphere_record['uuid'] = $_GET['uuid'];
	} else {
		$mode_page = PAGE_MODE_ADD; // Force ADD
		$sphere_record['uuid'] = uuid();
	}
}

if (!(isset($config['gconcat']['vdisk']) && is_array($config['gconcat']['vdisk']))) {
	$config['gconcat']['vdisk'] = [];
}
array_sort_key($config['gconcat']['vdisk'], 'name');
$sphere_array = &$config['gconcat']['vdisk'];

$index = array_search_ex($sphere_record['uuid'], $sphere_array, 'uuid'); // find index of uuid
$mode_updatenotify = updatenotify_get_mode($sphere_notifier, $sphere_record['uuid']); // get updatenotify mode for uuid
$mode_record = RECORD_ERROR;
if (false !== $index) { // uuid found
	if ((PAGE_MODE_POST == $mode_page || (PAGE_MODE_EDIT == $mode_page))) { // POST or EDIT
		switch ($mode_updatenotify) {
			case UPDATENOTIFY_MODE_NEW:
				$mode_record = RECORD_NEW_MODIFY;
				break;
			case UPDATENOTIFY_MODE_MODIFIED:
			case UPDATENOTIFY_MODE_UNKNOWN:
				$mode_record = RECORD_MODIFY;
				break;
		}
	}
} else { // uuid not found
	if ((PAGE_MODE_POST == $mode_page) || (PAGE_MODE_ADD == $mode_page)) { // POST or ADD
		switch ($mode_updatenotify) {
			case UPDATENOTIFY_MODE_UNKNOWN:
				$mode_record = RECORD_NEW;
				break;
		}
	}
}
if (RECORD_ERROR == $mode_record) { // oops, someone tries to cheat, over and out
	header($sphere_header_parent);
	exit;
}

$a_sraid = get_conf_sraid_disks_list();
$a_sdisk = get_conf_disks_filtered_ex('fstype', 'softraid');
if (!sizeof($a_sdisk)) {
	$errormsg = gettext('You must add disks first.');
	$prerequisites_ok = false;
}

$a_device = [];
foreach ($a_sdisk as $r_sdisk) {
	$helpinghand = $r_sdisk['devicespecialfile'] . (isset($r_sdisk['zfsgpt']) ? $r_sdisk['zfsgpt'] : '');
	$a_device[$helpinghand] = [
		'name' => htmlspecialchars($r_sdisk['name']),
		'uuid' => $r_sdisk['uuid'],
		'model' => htmlspecialchars($r_sdisk['model']),
		'devicespecialfile' => htmlspecialchars($helpinghand),
		'partition' => ((isset($r_sdisk['zfsgpt']) && (!empty($r_sdisk['zfsgpt'])))? $r_sdisk['zfsgpt'] : gettext('Entire Device')),
		'controller' => $r_sdisk['controller'].$r_sdisk['controller_id'].' ('.$r_sdisk['controller_desc'].')',
		'size' => $r_sdisk['size'],
		'serial' => $r_sdisk['serial'],
		'desc' => htmlspecialchars($r_sdisk['desc'])
	];
}

if (PAGE_MODE_POST == $mode_page) { // We know POST is "Submit", already checked
	unset($input_errors);

	// populate everything that is valid for all $mode_record (pre)
	$sphere_record['desc'] = (isset($_POST['desc']) ? $_POST['desc'] : '');
	// populate based on $mode_record
	switch ($mode_record) {
		case RECORD_NEW:
			$sphere_record['name'] = (isset($_POST['name']) ? substr($_POST['name'], 0, 15) : ''); // Make sure name is only 15 chars long (GEOM limitation).
			$sphere_record['type'] = 'JBOD';
			$sphere_record['init'] = isset($_POST['init']);
			$sphere_record['device'] = (isset($_POST[$checkbox_member_name]) ? $_POST[$checkbox_member_name] : []);
			break;
		case RECORD_NEW_MODIFY:
			$sphere_record['name'] = (isset($_POST['name']) ? substr($_POST['name'], 0, 15) : ''); // Make sure name is only 15 chars long (GEOM limitation).
			$sphere_record['type'] = 'JBOD';
			$sphere_record['init'] = isset($_POST['init']);
			$sphere_record['device'] = (isset($_POST[$checkbox_member_name]) ? $_POST[$checkbox_member_name] : []);
			break;
		case RECORD_MODIFY:
			$sphere_record['name'] = $sphere_array[$index]['name'];
			$sphere_record['type'] = $sphere_array[$index]['type'];
			$sphere_record['init'] = false;
			$sphere_record['device'] = $sphere_array[$index]['device'];
			break;
	}
	// populate everything that is valid for all $mode_record (post)
	$sphere_record['devicespecialfile'] = "/dev/concat/{$sphere_record['name']}";

	// input validation
	$reqdfields = ['name'];
	$reqdfieldsn = [gettext('Raid Name')];
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	// logic validation
	if ($prerequisites_ok && empty($input_errors)) { // check for a valid RAID name.
		if (($sphere_record['name'] && !is_validaliasname($sphere_record['name']))) {
			$input_errors[] = gettext('The name of the RAID may only consist of the characters a-z, A-Z, 0-9.');
		}
	}
	if ($prerequisites_ok && empty($input_errors)) { // check for existing RAID names
		switch ($mode_record) { // verify config
			case RECORD_NEW:
				foreach ($a_sraid as $r_sraid) { // RAID name must not exist in config at all
					if ($r_sraid['name'] === $sphere_record['name']) {
						$input_errors[] = gettext('The name of the RAID is already in use.');
						break; // break loop
					}
				}
				break;
			case RECORD_NEW_MODIFY: 
				if ($sphere_record['name'] !== $sphere_array[$index]['name']) { // if the RAID name has changed it shouldn't be found in config
					foreach ($a_sraid as $r_sraid) {
						if ($r_sraid['name'] === $sphere_record['name']) {
							$input_errors[] = gettext('The name of the RAID is already in use.');
							break; // break loop
						}
					}
				}
				break;
			case RECORD_MODIFY: 
				if ($sphere_record['name'] !== $sphere_array[$index]['name']) { // should never happen because sphere_record['name'] should be set to $sphere_array[$index]['name']
					$input_errors[] = gettext('The name of the RAID cannot be changed.');
				}
				break;
		}
	}
	if ($prerequisites_ok && empty($input_errors)) { // check the number of disk for RAID volume
		if (count($sphere_record['device']) < 2) {
			$input_errors[] = gettext('A minimum of 2 disks is required to build a JBOD.');
		}
	}
	// process POST
	if ($prerequisites_ok && empty($input_errors)) {
		switch ($mode_record) {
			case RECORD_NEW:
				if ($sphere_record['init']) { // create new RAID
					updatenotify_set($sphere_notifier, UPDATENOTIFY_MODE_NEW, $sphere_record['uuid']);
				} else { // existing RAID
					updatenotify_set($sphere_notifier, UPDATENOTIFY_MODE_MODIFIED, $sphere_record['uuid']);
				}
				unset($sphere_record['init']); // lifetime ends here
				$sphere_array[] = $sphere_record;
				break;
			case RECORD_NEW_MODIFY:
				if ($sphere_record['init']) { // create new RAID
				} else { // existing RAID
					updatenotify_clear($sphere_notifier, $sphere_record['uuid']); // clear NEW
					updatenotify_set($sphere_notifier, UPDATENOTIFY_MODE_MODIFIED, $sphere_record['uuid']);
				}
				unset($sphere_record['init']); // lifetime ends here
				$sphere_array[$index] = $sphere_record;
				break;
			case RECORD_MODIFY:
				if (UPDATENOTIFY_MODE_UNKNOWN == $mode_updatenotify) {
					updatenotify_set($sphere_notifier, UPDATENOTIFY_MODE_MODIFIED, $sphere_record['uuid']);
				}
				unset($sphere_record['init']); // lifetime ends here
				$sphere_array[$index] = $sphere_record;
				break;
		}
		write_config();
		header($sphere_header_parent);
		exit;
	}
} else { // EDIT / ADD
	switch ($mode_record) {
		case RECORD_NEW:
			$sphere_record['name'] = '';
			$sphere_record['type'] = 'JBOD';
			$sphere_record['init'] = false;
			$sphere_record['device'] = [];
			$sphere_record['devicespecialfile'] = '';
			$sphere_record['desc'] = gettext('Software gconcat JBOD');
			break;
		case RECORD_NEW_MODIFY:
			$sphere_record['name'] = $sphere_array[$index]['name'];
			$sphere_record['type'] = $sphere_array[$index]['type'];
			$sphere_record['init'] = true; // it must have been set because the previous status of RECORD_NEW_MODIFY can only be RECOD_NEW.
			$sphere_record['device'] = $sphere_array[$index]['device'];
			$sphere_record['devicespecialfile'] = $sphere_array[$index]['devicespecialfile'];
			$sphere_record['desc'] = $sphere_array[$index]['desc'];
			break;
		case RECORD_MODIFY:
			$sphere_record['name'] = (isset($sphere_array[$index]['name']) ? $sphere_array[$index]['name'] : '');
			$sphere_record['type'] = (isset($sphere_array[$index]['type']) ? $sphere_array[$index]['type'] : '');;
			$sphere_record['init'] = false;
			$sphere_record['device'] = (isset($sphere_array[$index]['device']) ? $sphere_array[$index]['device'] : []);
			$sphere_record['devicespecialfile'] = $sphere_array[$index]['devicespecialfile'];
			$sphere_record['desc'] = (isset($sphere_array[$index]['desc']) ? $sphere_array[$index]['desc'] : '');
			break;
	}
}

$pgtitle = array(gettext('Disks'), gettext('Software RAID'), gettext('JBOD'), (RECORD_NEW !== $mode_record) ? gettext('Edit') : gettext('Add'));
?>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
//<![CDATA[
// Disable submit button and give its control to checkbox array.
$(window).on("load", function() {
	$("#type").prop("disabled", true); 	// disable type field
	controlsubmitbutton(this,'<?=$checkbox_member_name;?>[]'); // Init submit button
	$("input[name='<?=$checkbox_member_name;?>[]").click(function() { // set event on member checkboxes
		controlsubmitbutton(this, '<?=$checkbox_member_name;?>[]');
	});
	$("#togglemembers").click(function() { // set event on toggle checkbox
		togglecheckboxesbyname(this, "<?=$checkbox_member_name;?>[]");
	});
	$("#submit_button").click(function() { // set event on submit button
		enable_change(true);
	});
	<?php if (RECORD_MODIFY == $mode_record):?>
		enable_change(false); // Disable controls that should not be modified anymore in edit mode.
	<?php endif;?>
});
function enable_change(enable_change) {
	document.iform.name.disabled = !enable_change;
	document.iform.type.disabled = !enable_change;
	document.iform.init.disabled = !enable_change;
}
function togglecheckboxesbyname(ego, triggerbyname) {
	var a_trigger = document.getElementsByName(triggerbyname);
	var n_trigger = a_trigger.length;
	var sb_disable = true;
	var i = 0;
	var n = 0;
	for (; i < n_trigger; i++) {
		if ((a_trigger[i].type == 'checkbox') && !a_trigger[i].disabled) {
			a_trigger[i].checked = !a_trigger[i].checked;
			if (a_trigger[i].checked) {
				n++;
			}
		}
	}
	if (n > 1) {
		sb_disable = false;
	}
	$("#submit_button").prop("disabled", sb_disable);
	if (ego.type == 'checkbox') { ego.checked = false; }
}
function controlsubmitbutton(ego, triggerbyname) {
	var a_trigger = document.getElementsByName(triggerbyname);
	var n_trigger = a_trigger.length;
	var sb_disable = true;
	var i = 0;
	var n = 0;
	for (; i < n_trigger; i++) {
		if ((a_trigger[i].type === 'checkbox') && a_trigger[i].checked) {
			n++;
		}
	}
	if (n > 1) {
		sb_disable = false;
	}
	$("#submit_button").prop("disabled", sb_disable);
}
//]]>
</script>
<table id="area_navigator">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabact"><a href="disks_raid_gconcat.php" title="<?=gettext('Reload page');?>" ><span><?=gettext("JBOD");?></span></a></li>
				<li class="tabinact"><a href="disks_raid_gstripe.php"><span><?=gettext('RAID 0');?></span></a></li>
				<li class="tabinact"><a href="disks_raid_gmirror.php"><span><?=gettext('RAID 1');?></span></a></li>
				<li class="tabinact"><a href="disks_raid_graid5.php"><span><?=gettext('RAID 5');?></span></a></li>
				<li class="tabinact"><a href="disks_raid_gvinum.php"><span><?=gettext('RAID 0/1/5');?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav2">
				<li class="tabact"><a href="disks_raid_gconcat.php" title="<?=gettext('Reload page');?>" ><span><?=gettext('Management');?></span></a></li>
				<li class="tabinact"><a href="disks_raid_gconcat_tools.php"><span><?=gettext('Tools');?></span></a></li>
				<li class="tabinact"><a href="disks_raid_gconcat_info.php"><span><?=gettext('Information'); ?></span></a></li>
			</ul>
		</td>
	</tr>
</table>
<table id="area_data">
	<tr>
		<td id="area_data_frame">
			<form action="<?=$sphere_scriptname;?>" method="post" name="iform" id="iform">
				<?php 
					if (!empty($errormsg)) { print_error_box($errormsg); }
					if (!empty($input_errors)) { print_input_errors($input_errors); }
					if (file_exists($d_sysrebootreqd_path)) { print_info_box(get_std_save_message(0)); }
				?>
				<table id="area_data_settings">
					<colgroup>
						<col id="area_data_settings_col_tag">
						<col id="area_data_settings_col_data">
					</colgroup>
					<thead>
						<?php html_titleline2(gettext('Settings'));?>
					</thead>
					<tbody>
						<?php
							$notnewandnotnewmodify = !((RECORD_NEW === $mode_record) || (RECORD_NEW_MODIFY === $mode_record));
							html_inputbox2('name', gettext('Raid Name'), $sphere_record['name'], '', true, 15, $notnewandnotnewmodify); // readonly if not new and not new-modify
							html_inputbox2('type', gettext('Type'), $sphere_record['type'], '', false, 4, true); // fixed text 'JBOD', no modification at all
							$helpinghand = [
								[gettext('Do not activate this option if you want to add an already existing RAID again.')],
								[gettext('All data will be lost when you activate this option!'), 'red']
							];
							html_checkbox2('init', gettext('Initialize'), !empty($sphere_record['init']) ? true : false, gettext('Create and initialize RAID.'), $helpinghand, false, $notnewandnotnewmodify);
							html_inputbox2('desc', gettext('Description'), $sphere_record['desc'], gettext('You may enter a description here for your reference.'), false, 48);
							html_separator2();
						?>
					</tbody>
				</table>
				<table id="area_data_selection">
					<colgroup>
						<col style="width:5%"> <!--// checkbox -->
						<col style="width:10%"><!--// Device -->
						<col style="width:10%"><!--// Partition -->
						<col style="width:15%"><!--// Model -->
						<col style="width:10%"><!--// Serial -->
						<col style="width:10%"><!--// Size -->
						<col style="width:20%"><!--// Controller -->
						<col style="width:15%"><!--// Description -->
						<col style="width:5%"> <!--// Icons -->
					</colgroup>
					<thead>
						<?php html_titleline2(gettext('Device List'), 9);?>
						<tr>
							<td class="lhelc">
								<?php if ((RECORD_NEW === $mode_record) || (RECORD_NEW_MODIFY === $mode_record)):?>
									<input type="checkbox" id="togglemembers" name="togglemembers" title="<?=gettext('Invert Selection');?>"/>
								<?php else:?>
									<input type="checkbox" id="togglemembers" name="togglemembers" disabled="disabled"/>
								<?php endif;?>
							</td>
							<td class="lhell"><?=gettext('Device');?></td>
							<td class="lhell"><?=gettext('Partition');?></td>
							<td class="lhell"><?=gettext('Model');?></td>
							<td class="lhell"><?=gettext('Serial Number');?></td>
							<td class="lhell"><?=gettext('Size');?></td>
							<td class="lhell"><?=gettext('Controller');?></td>
							<td class="lhell"><?=gettext('Name');?></td>
							<td class="lhebl">&nbsp;</td>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($a_device as $r_device):?>
							<?php $isnotmemberofasraid = (false === array_search_ex($r_device['devicespecialfile'], $a_sraid, 'device'));?>
							<?php $ismemberofthissraid = (isset($sphere_record['device']) && is_array($sphere_record['device']) && in_array($r_device['devicespecialfile'], $sphere_record['device']));?>
							<?php if (($isnotmemberofasraid || $ismemberofthissraid) && ((RECORD_NEW == $mode_record) || (RECORD_NEW_MODIFY == $mode_record))):?>
								<tr>
									<td class="lcelc">
										<?php if ($ismemberofthissraid):?>
											<input type="checkbox" name="<?=$checkbox_member_name;?>[]" value="<?=$r_device['devicespecialfile'];?>" id="<?=$r_device['uuid'];?>" checked="checked"/>
										<?php else:?>
											<input type="checkbox" name="<?=$checkbox_member_name;?>[]" value="<?=$r_device['devicespecialfile'];?>" id="<?=$r_device['uuid'];?>"/>
										<?php endif;?>	
									</td>
									<td class="lcell"><?=htmlspecialchars($r_device['name']);?>&nbsp;</td>
									<td class="lcell"><?=htmlspecialchars($r_device['partition']);?>&nbsp;</td>
									<td class="lcell"><?=htmlspecialchars($r_device['model']);?>&nbsp;</td>
									<td class="lcell"><?=htmlspecialchars($r_device['serial']);?>&nbsp;</td>
									<td class="lcell"><?=htmlspecialchars($r_device['size']);?>&nbsp;</td>
									<td class="lcell"><?=htmlspecialchars($r_device['controller']);?>&nbsp;</td>
									<td class="lcell"><?=htmlspecialchars($r_device['desc']);?>&nbsp;</td>
									<td class="lcebcd">
										<?php if ($ismemberofthissraid):?>
											<img src="<?=$img_path['unl'];?>" title="<?=gettext($gt_record_opn);?>" alt="<?=gettext($gt_record_opn);?>" />
										<?php else:?>
											&nbsp;
										<?php endif;?>
									</td>
								</tr>
							<?php endif;?>
							<?php if ($ismemberofthissraid && (RECORD_MODIFY == $mode_record)):?>
								<tr>
									<td class="<?=!$ismemberofthissraid ? "lcelc" : "lcelcd";?>">
										<input type="checkbox" name="<?=$checkbox_member_name;?>[]" value="<?=$r_device['devicespecialfile'];?>" id="<?=$r_device['uuid'];?>" checked="checked" disabled="disabled"/>
									</td>
									<td class="<?=!$ismemberofthissraid ? "lcell" : "lcelld";?>"><?=htmlspecialchars($r_device['name']);?>&nbsp;</td>
									<td class="<?=!$ismemberofthissraid ? "lcell" : "lcelld";?>"><?=htmlspecialchars($r_device['partition']);?>&nbsp;</td>
									<td class="<?=!$ismemberofthissraid ? "lcell" : "lcelld";?>"><?=htmlspecialchars($r_device['model']);?>&nbsp;</td>
									<td class="<?=!$ismemberofthissraid ? "lcell" : "lcelld";?>"><?=htmlspecialchars($r_device['serial']);?>&nbsp;</td>
									<td class="<?=!$ismemberofthissraid ? "lcell" : "lcelld";?>"><?=htmlspecialchars($r_device['size']);?>&nbsp;</td>
									<td class="<?=!$ismemberofthissraid ? "lcell" : "lcelld";?>"><?=htmlspecialchars($r_device['controller']);?>&nbsp;</td>
									<td class="<?=!$ismemberofthissraid ? "lcell" : "lcelld";?>"><?=htmlspecialchars($r_device['desc']);?>&nbsp;</td>
									<td valign="middle" nowrap="nowrap" class="lcebcd">
										<img src="<?=$img_path['loc'];?>" title="<?=gettext($gt_record_loc);?>" alt="<?=gettext($gt_record_loc);?>" />
									</td>
								</tr>
							<?php endif;?>
						<?php endforeach;?>
					</tbody>
				</table>
				<div id="submit">
					<input name="Submit" id="submit_button" type="submit" class="formbtn" value="<?=(RECORD_NEW != $mode_record) ? gettext('Save') : gettext('Add');?>"/>
					<input name="Cancel" id="cancel_button" type="submit" class="formbtn" value="<?=gettext('Cancel');?>" />
					<input name="uuid" type="hidden" value="<?=$sphere_record['uuid'];?>" />
				</div>
				<?php include("formend.inc");?>
			</form>
		</td>
	</tr>
</table>
<?php include("fend.inc");?>
