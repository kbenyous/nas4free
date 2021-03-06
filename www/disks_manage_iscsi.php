<?php
/*
	disks_manage_iscsi.php

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

$pgtitle = [gtext('Disks'),gtext('Management'),gtext('iSCSI Initiator')];

if ($_POST) {
	$pconfig = $_POST;

	if (isset($_POST['apply']) && $_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			$retval |= updatenotify_process("iscsiinitiator", "iscsiinitiator_process_updatenotification");
			config_lock();
			$retval |= rc_update_service("iscsi_initiator");
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			updatenotify_delete("iscsiinitiator");
		}
	}
}
$a_iscsiinit = &array_make_branch($config,'iscsiinit','vdisk');
if(empty($a_iscsiinit)):
	array_sort_key($a_iscsiinit,'name');
endif;

if (isset($_GET['act']) && $_GET['act'] === "del") {
	updatenotify_set("iscsiinitiator", UPDATENOTIFY_MODE_DIRTY, $_GET['uuid']);
	header("Location: disks_manage_iscsi.php");
	exit;
}

function iscsiinitiator_process_updatenotification($mode, $data) {
	global $config;

	$retval = 0;

	switch ($mode) {
		case UPDATENOTIFY_MODE_NEW:
		case UPDATENOTIFY_MODE_MODIFIED:
			break;
		case UPDATENOTIFY_MODE_DIRTY:
			if (is_array($config['iscsiinit']['vdisk'])) {
				$index = array_search_ex($data, $config['iscsiinit']['vdisk'], "uuid");
				if (false !== $index) {
					unset($config['iscsiinit']['vdisk'][$index]);
					write_config();
				}
			}
			break;
	}

	return $retval;
}
?>
<?php include 'fbegin.inc';?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
    <td class="tabnavtbl">
	<ul id="tabnav">
      		<li class="tabinact"><a href="disks_manage.php"><span><?=gtext("HDD Management");?></span></a></li>
		<li class="tabinact"><a href="disks_init.php"><span><?=gtext("HDD Format");?></span></a></li>
      		<li class="tabinact"><a href="disks_manage_smart.php"><span><?=gtext("S.M.A.R.T.");?></span></a></li>
		<li class="tabact"><a href="disks_manage_iscsi.php" title="<?=gtext('Reload page');?>"><span><?=gtext("iSCSI Initiator");?></span></a></li>
	</ul>
    </td>
</tr>
	<tr> 
	<td class="tabcont">
	<form action="disks_manage_iscsi.php" method="post">
		<?php if (!empty($savemsg)) print_info_box($savemsg); ?>
		<?php if (updatenotify_exists("iscsiinitiator")) print_config_change_box();?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<?php html_titleline2(gtext('iSCSI Initiator'), 4);?>
	<tr>
		<td width="25%" class="listhdrlr"><?=gtext("Name"); ?></td>
		<td width="25%" class="listhdrr"><?=gtext("Target Name"); ?></td>
		<td width="25%" class="listhdrr"><?=gtext("Target Address"); ?></td>
		<td width="10%" class="list"></td>
	</tr>
	<?php foreach($a_iscsiinit as $iscsiinit):?>
	<?php $notificationmode = updatenotify_get_mode("iscsiinitiator", $iscsiinit['uuid']);?>
	<tr>
		<td class="listlr"><?=htmlspecialchars($iscsiinit['name']);?>&nbsp;</td>
		<td class="listr"><?=htmlspecialchars($iscsiinit['targetname']);?>&nbsp;</td>
		<td class="listr"><?=htmlspecialchars($iscsiinit['targetaddress']);?>&nbsp;</td>
	<?php if (UPDATENOTIFY_MODE_DIRTY != $notificationmode):?>
	<td valign="middle" nowrap="nowrap" class="list">
		<a href="disks_manage_iscsi_edit.php?uuid=<?=$iscsiinit['uuid'];?>"><img src="images/edit.png" title="<?=gtext("Edit initiator");?>" border="0" alt="<?=gtext("Edit initiator");?>" /></a>
		<a href="disks_manage_iscsi.php?act=del&amp;uuid=<?=$iscsiinit['uuid'];?>" onclick="return confirm('<?=gtext("Do you really want to delete this initiator? All elements that still use it will become invalid (e.g. share)!");?>')"><img src="images/delete.png" title="<?=gtext("Delete initiator"); ?>" border="0" alt="<?=gtext("Delete initiator"); ?>" /></a>
	</td>
	<?php else:?>
		<td valign="middle" nowrap="nowrap" class="list">
		<img src="images/delete.png" border="0" alt="" />
	</td>
	<?php endif;?>
	</tr>
	<?php endforeach;?>
	<tr> 
	<td class="list" colspan="3"></td>
	<td class="list"><a href="disks_manage_iscsi_edit.php"><img src="images/add.png" title="<?=gtext("Add initiator");?>" border="0" alt="<?=gtext("Add initiator");?>" /></a></td>
	</tr>
	</table>
	<?php include 'formend.inc';?>
</form>
</td>
</tr>
</table>
<?php include 'fend.inc';?>
