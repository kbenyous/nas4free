<?php
/*
	services_minidlna.php

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2017 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright
	   notice, this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE NAS4FREE PROJECT ``AS IS'' AND ANY
	EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
	IN NO EVENT SHALL THE NAS4FREE PROJECT OR ITS CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
	ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
	THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies,
	either expressed or implied, of the NAS4Free Project.
*/
require 'auth.inc';
require 'guiconfig.inc';
require 'services.inc';

$sphere_scriptname = basename(__FILE__);
$sphere_header = 'Location: '.$sphere_scriptname;
$sphere_record = [];
$a_message = [];
$sphere_default = [
	'enable' => false,
	'name' => '',
	'if' => '',
	'port' => '8200',
	'home' => '',
	'notify_int' => '300',
	'strict' => false,
	'loglevel' => 'info',
	'tivo' => false,
	'content' => [],
	'container' => 'B',
	'inotify' => true
];
$gt_apply_confirm = gtext('Do you want to apply these settings?');
$input_errors = [];
//	sunrise
array_make_branch($config,'minidlna','content');
$sphere_array = &$config['minidlna'];
sort($sphere_array['content']);
// we need information about other DLNA services
array_make_branch($config,'upnp','content');
/*	calculate initial page mode and page action.
 *	at the end of this section a valid page mode and a valid page action are available.
 *	page_action cancel is switched to view mode.
 *	mode_page: page_action:
 *		PAGE_MODE_EDIT: edit
 *		PAGE_MODE_POST: enable, disable, rescan, save
 *		PAGE_MODE_VIEW: view
 */
$mode_page = ($_POST) ? PAGE_MODE_POST : PAGE_MODE_VIEW;
switch($mode_page):
	case PAGE_MODE_POST:
		if(isset($_POST['submit'])):
			$page_action = $_POST['submit'];
			switch($page_action):
				case 'edit':
					$mode_page = PAGE_MODE_EDIT;
					break;
				case 'rescan':
					break;
				case 'save':
					break;
				case 'rows.enable':
					$page_action = 'enable';
					break;
				case 'rows.disable':
					$page_action = 'disable';
					break;
				case 'cancel':
					$mode_page = PAGE_MODE_VIEW;
					$page_action = 'view';
					break;
				default:
					$mode_page = PAGE_MODE_VIEW;
					$page_action = 'view';
					break;
			endswitch;
		else:
			$mode_page = PAGE_MODE_VIEW;
			$page_action = 'view';
		endif;
		break;
	case PAGE_MODE_VIEW:
		$page_action = 'view';
		break;
	case PAGE_MODE_EDIT:
		$mode_page = PAGE_MODE_VIEW;
		$page_action = 'view';
		break;			
	default:
		$mode_page = PAGE_MODE_VIEW;
		$page_action = 'view';
		break;
endswitch;
//	get configuration data, depending on the source
switch($page_action):
	case 'save':
		$source = $_POST;
		break;
	default:
		$source = $sphere_array;
		break;
endswitch;
$sphere_record['enable'] = isset($source['enable']);
$sphere_record['name'] = $source['name'] ?? $sphere_default['name'];
$sphere_record['if'] = $source['if'] ?? $sphere_default['if'];
$sphere_record['port'] = $source['port'] ?? $sphere_default['port'];
$sphere_record['home'] = $source['home'] ?? $sphere_default['home'];
$sphere_record['notify_int'] = $source['notify_int'] ?? $sphere_default['notify_int'];
$sphere_record['strict'] = isset($source['strict']);
$sphere_record['loglevel'] = $source['loglevel'] ?? $sphere_default['loglevel'];
$sphere_record['tivo'] = isset($source['tivo']);
$sphere_record['content'] = $source['content'] ?? $sphere_default['content'];
$sphere_record['container'] = $source['container'] ?? $sphere_default['container'];
$sphere_record['inotify'] = isset($source['inotify']);
//	process enable and rescan
switch($page_action):
	case 'enable':
		if($sphere_record['enable']):
			$mode_page = PAGE_MODE_VIEW;
			$page_action = 'view'; 
		else: // enable and run a full validation
			$sphere_record['enable'] = true;
			$page_action = 'save'; // continue with save procedure
		endif;
		break;
	case 'rescan':
		if($sphere_record['enable']):
			mwexec_bg('service minidlna rescan');
			$a_message[] = gtext('A rescan has been issued.');
		endif;
		$mode_page = PAGE_MODE_VIEW;
		$page_action = 'view';
		break;
endswitch;
//	process save and disable
switch($page_action):
	case 'save':
		//	validate name
		if(is_string($sphere_record['name'])):
			if(preg_match('/\S/',$sphere_record['name'])):
			else:
				$input_errors[] = gtext('The name of the media server cannot be empty.');
				$sphere_record['name'] = '';
			endif;
		else:
			$input_errors[] = gtext('The name of the media server is missing.');
			$sphere_record['name'] = '';
		endif;
		//	validate interface
		if(is_string($sphere_record['if'])):
			if(preg_match('/\S/',$sphere_record['if'])):
				// check if if is on the list of interfaces
				if(true):
				else:
					$input_errors[] = gtext('The interface is unknown.');
				endif;
			endif;
		else:
			$sphere_record['if'] = '';
		endif;
		//	check port range.
		if(!is_string($sphere_record['port'])):
			$sphere_record['port'] = $sphere_default['port'];
		endif;
		if((1024 > $sphere_record['port']) || (65535 < $sphere_record['port'])):
			$input_errors[] = sprintf(gtext("Port number must be in the range between %d and %d."),1024,65535);
		endif;
		//	check home folder
		if(!is_string($sphere_record['home'])):
			$sphere_record['home'] = '';
		endif;
		if(preg_match('/\S/',$sphere_record['home'])):
			if(is_dir($sphere_record['home'])):
			else:
				$input_errors[] = gtext('The location of the "Database Directory" is not a valid location.');
			endif;
		else:
			$input_errors[] = gtext('Please define the location for the database directory.');
		endif;
		if(!is_string($sphere_record['notify_int'])):
			$sphere_record['notify_int'] = '300';
		endif;
		if(!is_string($sphere_record['loglevel'])):
			$sphere_record['loglevel'] = 'info';
		endif;
		if(!is_array($sphere_record['content'])):
			$sphere_record['content'] = [];
		endif;
		if(empty($sphere_record['content'])):
			$input_errors[] = gtext('Please define one or more content locations.');
		endif;
		if(!is_string($sphere_record['container'])):
			$sphere_record['container'] = 'B';
		endif;
		// all checks passed
		if(empty($input_errors)):
			$sphere_array = $sphere_record;
			write_config();
			$retval = 0;
			chown($sphere_record['home'],'dlna');
			chmod($sphere_record['home'],0755);
			config_lock();
			$retval != rc_stop_service('minidlna');
			$retval = $retval << 1;
			$retval |=  rc_update_service('minidlna');
			$retval = $retval << 1;
			$retval |= rc_update_service('mdnsresponder');
			config_unlock();
			$a_message[] = gtext('Changes have been applied.');
			$mode_page = PAGE_MODE_VIEW;
			$page_action = 'view';
		else:
			$mode_page = PAGE_MODE_EDIT;
			$page_action = 'edit';
		endif;
		break;
	case 'disable':
		if($sphere_record['enable']): // if enabled, disable it
			$sphere_record['enable'] = false;
			$sphere_array = $sphere_record;
			write_config();
			$retval = 0;
			config_lock();
			$retval |= rc_update_service('minidlna');
			$retval |= rc_update_service('mdnsresponder');
			config_unlock();
			$a_message[] = gtext('MniDLNA has been disabled.');
		endif;
		$mode_page = PAGE_MODE_VIEW;
		$page_action = 'view';
		break;
endswitch;
//	determine final page mode
switch($mode_page):
	case PAGE_MODE_EDIT:
		break;
/*
	case PAGE_MODE_VIEW:
 */
	default:
		if(isset($config['system']['skipviewmode'])):
			$mode_page = PAGE_MODE_EDIT;
			$page_action = 'edit';
		else:
			$mode_page = PAGE_MODE_VIEW;
			$page_action = 'view';
		endif;
		break;
endswitch;
//	list of configured interfaces
$a_interface = get_interface_list();
$l_interfaces = [];
foreach($a_interface as $k_interface => $ifinfo):
	$ifinfo = get_interface_info($k_interface);
	switch($ifinfo['status']):
		case 'up':
		case 'associated':
			$l_interfaces[$k_interface] = $k_interface;
			break;
	endswitch;
endforeach;
//	list of container types
$l_container = [
	'.' => gtext('Standard'),
	'B' => gtext('Browse Directory'),
	'M' => gtext('Music'),
	'V' => gtext('Video'),
	'P' => gtext('Pictures')
];
//	list of log levels
$l_loglevel = [
	'off' => gtext('Off'),
	'fatal' => gtext('Fatal'),
	'error' => gtext('Error'),
	'warn' => gtext('Warning'),
	'info' => gtext('Info'),
	'debug' => gtext('Debug')
];
//	identifiy enabled DLNA services
$dlna_count = 0;
$dlna_count += (isset($config['minidlna']['enable'])) ? 1 : 0;
$dlna_count += (isset($config['upnp']['enable'])) ? 2 : 0;
//	everything greater than 1 indicates that another DLNA service is running somewhere else
//	every odd number indicates that this DLNA service is enabled.
switch($dlna_count):
	case 0:
		$dlna_option = 0; // DLNA can be enabled, no access to link
		break;
	case 1:
		$dlna_option = 1; // DLNA can be disabled, access to link
		break;
	default:
		if($dlna_count & 1):
			$dlna_option = 3; // Warning, DLNA can be disabled, access to link
			$a_message[] = gtext('More than one DLNA/UPnP service is active. This configuration might cause issues.');
		else:
			$dlna_option = 2; // Warning, DLNA no access to enable, no access to link
			$a_message[] = gtext('Another DLNA/UPnP service is already running. Enabling MiniDLNA might cause issues.');
		endif;
		break;
endswitch;
$pgtitle = [gtext('Services'),gtext('DLNA/UPnP MiniDLNA')];
include 'fbegin.inc';
switch($mode_page):
	case PAGE_MODE_VIEW:
?>
<script type="text/javascript">
//<![CDATA[
$(window).on("load", function() {
	$("#iform").submit(function() {
		spinner();
	});
});
//]]>
</script>
<?php
		break;
	case PAGE_MODE_EDIT:
?>
<script type="text/javascript">
//<![CDATA[
$(window).on("load", function() {
	$("#iform").submit(function() {
		onsubmit_content();
		spinner();
	});
	$("#button_save").click(function () {
		return confirm("<?=$gt_apply_confirm;?>");
	});
});
//]]>
</script>
<?php
		break;
endswitch;	
?>
<table id="area_navigator"><tbody><tr><td class="tabnavtbl">
	<ul id="tabnav">
		<li class="tabinact"><a href="services_fuppes.php"><span><?=gtext('Fuppes')?></span></a></li>
		<li class="tabact"><a href="services_minidlna.php"><span><?=gtext('MiniDLNA');?></span></a></li>
	</ul>
</td></tr></tbody></table>
<table id="area_data"><tbody><tr><td id="area_data_frame"><form action="<?=$sphere_scriptname;?>" method="post" name="iform" id="iform">
<?php 
	if(!empty($input_errors)):
		print_input_errors($input_errors);
	endif;
	foreach($a_message as $r_message):
		print_info_box($r_message);
	endforeach;
?>
	<table class="area_data_settings">
		<colgroup>
			<col class="area_data_settings_col_tag">
			<col class="area_data_settings_col_data">
		</colgroup>
		<thead>
<?php
			switch($mode_page):
				case PAGE_MODE_VIEW:
					html_titleline2(gtext('MiniDLNA A/V Media Server'));
					break;
				case PAGE_MODE_EDIT:
					html_titleline_checkbox2('enable',gtext('MiniDLNA A/V Media Server'),$sphere_record['enable'],gtext('Enable'));
					break;
			endswitch;
?>
		</thead>
		<tbody>
<?php
			switch($mode_page):
				case PAGE_MODE_VIEW:
					html_text2('enable',gtext('Service Enabled'),$sphere_record['enable'] ? gtext('Yes') : gtext('No'));
					html_text2('name',gtext('Name'), htmlspecialchars($sphere_record['name']));
					html_text2('if',gtext('Interface Selection'), htmlspecialchars($sphere_record['if']));
					html_text2('port',gtext('Port'), htmlspecialchars($sphere_record['port']));
					html_text2('notify_int',gtext('Broadcast Interval'), htmlspecialchars($sphere_record['notify_int']));
					html_text2('home',gtext('Database Directory'), htmlspecialchars($sphere_record['home']));
					$helpinghand = implode("\n",$sphere_record['content']);
					html_textarea2('content',gtext('Content Locations'),$helpinghand,'',false,67,5,true,false);
					html_checkbox2('inotify',gtext('Inotify'),$sphere_record['inotify'],'','',false,true);
					html_text2('container',gtext('Container'),$l_container[$sphere_record['container']] ?? '');
					html_checkbox2('strict',gtext('Strict DLNA'),$sphere_record['strict'],'','',false,true);
					html_checkbox2('tivo',gtext('TiVo Support'),$sphere_record['tivo'],'','',false,true);
					html_text2('loglevel',gtext('Log Level'),$l_loglevel[$sphere_record['loglevel']] ?? '');
					break;
				case PAGE_MODE_EDIT:
					html_inputbox2('name',gtext('Name'),$sphere_record['name'],gtext('Give your media library a friendly name.'),true,35,false,false,35,gtext('Media server name'));
					html_combobox2('if',gtext('Interface Selection'),$sphere_record['if'],$l_interfaces,gtext('Select which interface to use. (Only selectable if your server has more than one interface)'),true);
					html_inputbox2('port',gtext('Port'),$sphere_record['port'],sprintf(gtext('Port to listen on. Only dynamic or private ports can be used (from %d through %d). Default port is %d.'),1025,65535, 8200),true,5);
					html_inputbox2('notify_int',gtext('Broadcast Interval'),$sphere_record['notify_int'],gtext('Broadcasts its availability every N seconds on the network. (Default 300 seconds)'),true,5);
					html_filechooser2('home',gtext('Database Directory'),$sphere_record['home'],gtext('Location of the media content database.'),$g['media_path'],true,67);
					html_minidlnabox2('content',gtext('Content Locations'),$sphere_record['content'],gtext('Manage content locations.'),$g['media_path'],true);
					html_checkbox2('inotify',gtext('Inotify'),$sphere_record['inotify'],gtext('Enable inotify.'),gtext('Use inotify monitoring to automatically discover new files.'),false);
					html_combobox2('container',gtext('Container'),$sphere_record['container'],$l_container,gtext('Use different container as root of the tree.'),false,false,'');
					html_checkbox2('strict',gtext('Strict DLNA'),$sphere_record['strict'],gtext('Enable to strictly adhere to DLNA standards.'),gtext('This will allow server-side downscaling of very large JPEG images, it can impact JPEG serving performance on some DLNA products.'),false);
					html_checkbox2('tivo',gtext('TiVo Support'),$sphere_record['tivo'],gtext('Enable TiVo support.'),gtext('This will support streaming .jpg and .mp3 files to a TiVo supporting HMO.'),false);
					html_combobox2('loglevel',gtext('Log Level'),$sphere_record['loglevel'],$l_loglevel,'',false,false,'');
					break;
			endswitch;
			if($dlna_option & 1):
				html_separator2();
				html_titleline2(gtext('MiniDLNA Media Server WebGUI'));
				$if = get_ifname($sphere_record['if']);
				$ipaddr = get_ipaddr($if);
				$url = htmlspecialchars(sprintf('http://%s:%s/status',$ipaddr,$sphere_record['port']));
				$text = sprintf('<a href="%s" target="_blank">%s</a>',$url,$url);
				html_text2('url',gtext('URL'),$text);
			endif;
?>
		</tbody>
	</table>
	<div id="submit">
<?php
		switch($mode_page):
			case PAGE_MODE_VIEW;
				echo html_button_edit(gtext('Edit'));
				if($dlna_option & 1):
					echo html_button_rescan(gtext('Rescan'));
				endif;
				if($sphere_record['enable']):
					echo html_button_disable_rows(gtext('Disable'));
				else:
					echo html_button_enable_rows(gtext('Enable'));
				endif;
				break;
			case PAGE_MODE_EDIT:
				echo html_button_save(gtext('Apply'));
				echo html_button_cancel(gtext('Cancel'));
				break;
		endswitch;
?>
	</div>
<?php
include 'formend.inc';
?>
</form></td></tr></tbody></table>
<?php
include 'fend.inc';
?>
