<?php
/*
	services_rsyncd_module.php

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2016 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Portions of freenas (http://www.freenas.org).
	Copyright (c) 2005-2011 by Olivier Cochard <olivier@freenas.org>.
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

$pgtitle = array(gettext("Services"), gettext("Rsync"), gettext("Server"), gettext("Modules"));

if ($_POST) {
	$pconfig = $_POST;

	if (isset($_POST['apply']) && $_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			$retval |= updatenotify_process("rsyncd", "rsyncd_process_updatenotification");
			config_lock();
// WORKAROUND CAUSE
//			$retval |= rc_update_service("rsyncd");
// WORKARAOUND START
			$name = 'rsyncd';
			$running = rc_is_service_running($name); // Check if service is running
			$enabled = rc_is_service_enabled($name); // Check if service is enabled

			// Update rc.conf and execute rc script
			if (0 == $enabled) {
				rc_update_rcconf($name, "enable");
				switch ($running) {
					case 0:
						mwexec2("nohup /etc/rc.d/rsyncd restart >/dev/null 2>&1 &", $output, $retval);
//						$retval = rc_restart_service($name);
						break;
					case 1:
						mwexec2("nohup /etc/rc.d/rsyncd start >/dev/null 2>&1 &", $output, $retval);
//						$retval = rc_exec_script_async();
//						$retval = rc_start_service($name);
						break;
				}
			} else {
				// Stop service if necessary
				if (0 == $running) {
					$retval |= rc_stop_service($name);
				}
				rc_update_rcconf($name, "disable");
			}
// WORKAROUND END
			$retval |= rc_update_service("mdnsresponder");
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			updatenotify_delete("rsyncd");
		}
	}
}

if (!isset($config['rsyncd']['module']) || !is_array($config['rsyncd']['module']))
	$config['rsyncd']['module'] = array();

array_sort_key($config['rsyncd']['module'], "name");
$a_module = &$config['rsyncd']['module'];

if (isset($_GET['act']) && $_GET['act'] === "del") {
	updatenotify_set("rsyncd", UPDATENOTIFY_MODE_DIRTY, $_GET['uuid']);
	header("Location: services_rsyncd_module.php");
	exit;
}

function rsyncd_process_updatenotification($mode, $data) {
	global $config;

	$retval = 0;

	switch ($mode) {
		case UPDATENOTIFY_MODE_NEW:
		case UPDATENOTIFY_MODE_MODIFIED:
			break;
		case UPDATENOTIFY_MODE_DIRTY:
			$cnid = array_search_ex($data, $config['rsyncd']['module'], "uuid");
			if (FALSE !== $cnid) {
				unset($config['rsyncd']['module'][$cnid]);
				write_config();
			}
			break;
	}

	return $retval;
}
?>
<?php include("fbegin.inc");?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
		<td class="tabnavtbl">
  		<ul id="tabnav">
				<li class="tabact"><a href="services_rsyncd.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Server");?></span></a></li>
			  <li class="tabinact"><a href="services_rsyncd_client.php"><span><?=gettext("Client");?></span></a></li>
			  <li class="tabinact"><a href="services_rsyncd_local.php"><span><?=gettext("Local");?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav2">
				<li class="tabinact"><a href="services_rsyncd.php"><span><?=gettext("Settings");?></span></a></li>
				<li class="tabact"><a href="services_rsyncd_module.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Modules");?></span></a></li>
			</ul>
		</td>
	</tr>
  <tr>
    <td class="tabcont">
      <form action="services_rsyncd_module.php" method="post">
        <?php if (!empty($savemsg)) print_info_box($savemsg);?>
        <?php if (updatenotify_exists("rsyncd")) print_config_change_box();?>
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
          	<td width="15%" class="listhdrlr"><?=gettext("Name");?></td>
            <td width="35%" class="listhdrr"><?=gettext("Path");?></td>
            <td width="20%" class="listhdrr"><?=gettext("Comment");?></td>
            <td width="10%" class="listhdrr"><?=gettext("List");?></td>
            <td width="10%" class="listhdrr"><?=gettext("Access Mode");?></td>
            <td width="10%" class="list"></td>
          </tr>
  			  <?php foreach($a_module as $modulev):?>
  			  <?php $notificationmode = updatenotify_get_mode("rsyncd", $modulev['uuid']);?>
          <tr>
            <td class="listlr"><?=htmlspecialchars($modulev['name']);?>&nbsp;</td>
            <td class="listr"><?=htmlspecialchars($modulev['path']);?>&nbsp;</td>
            <td class="listr"><?=htmlspecialchars($modulev['comment']);?>&nbsp;</td>
            <td class="listbg"><?=htmlspecialchars(isset($modulev['list'])?gettext("Yes"):gettext("No"));?></td>
            <td class="listbg"><?=htmlspecialchars($modulev['rwmode']);?>&nbsp;</td>
            <?php if (UPDATENOTIFY_MODE_DIRTY != $notificationmode):?>
            <td valign="middle" nowrap="nowrap" class="list">
              <a href="services_rsyncd_module_edit.php?uuid=<?=$modulev['uuid'];?>"><img src="edit.png" title="<?=gettext("Edit module");?>" border="0" alt="<?=gettext("Edit module");?>" /></a>
              <a href="services_rsyncd_module.php?act=del&amp;uuid=<?=$modulev['uuid'];?>" onclick="return confirm('<?=gettext("Do you really want to delete this module?");?>')"><img src="delete.png" title="<?=gettext("Delete module");?>" border="0" alt="<?=gettext("Delete module");?>" /></a>
            </td>
						<?php else:?>
						<td valign="middle" nowrap="nowrap" class="list">
							<img src="delete.png" border="0" alt="" />
						</td>
						<?php endif;?>
          </tr>
          <?php endforeach;?>
          <tr>
            <td class="list" colspan="5"></td>
            <td class="list"><a href="services_rsyncd_module_edit.php"><img src="add.png" title="<?=gettext("Add module");?>" border="0" alt="<?=gettext("Add module");?>" /></a></td>
          </tr>
        </table>
        <?php include("formend.inc");?>
      </form>
    </td>
  </tr>
</table>
<?php include("fend.inc");?>