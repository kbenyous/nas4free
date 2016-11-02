<?php
/*
	status_graph.php

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

$pgtitle = array(gtext("Status"), gtext("Graph"),gtext("System Load"));

$curif = "lan";
if (isset($_GET['if']) && $_GET['if'])
	$curif = $_GET['if'];
$ifnum = get_ifname($config['interfaces'][$curif]['if']);
$graph_gap = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'; 
$graph_width = 397;
$graph_height = 220;
?>
<?php include("fbegin.inc");?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
		<td class="tabnavtbl">
		<div align="center">
  		<ul id="tabnav">
				<li class="tabact"><a href="status_graph.php" title="<?=gtext('Reload page');?>"><span><?=gtext("System Load");?></span></a></li>
				<li class="tabinact"><a href="status_graph_cpu.php"><span><?=gtext("CPU Load");?></span></a></li>
  		</ul>
		</div>
  	</td>
	</tr>
  <tr>
    <td class="tabcont">
<?php
$ifdescrs = array('lan' => 'LAN');
for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
	$ifdescrs['opt' . $j] = $config['interfaces']['opt' . $j]['descr'];
}
?>
<?=gtext("Graph shows last 120 seconds");?>
<div align="center" style="min-width:840px;">
<br>
<object id="graph"
        data="status_graph2.php?ifnum=<?=$ifnum;?>&amp;ifname=<?=rawurlencode($ifdescrs[$curif]);?>"
        type="image/svg+xml"
        width="<?=$graph_width;?>"
        height="<?=$graph_height;?>">
	 <param name="src" value="graph.php?ifnum=<?=$ifnum;?>&amp;ifname=<?=rawurlencode($ifdescrs[$curif]);?>" />
	 Your browser does not support this svg object type!<br /> You need to update your browser or use Internet Explorer 9 or higher.<br />
</object>

<?php
echo $graph_gap;
for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
	$ifdescrs = $config['interfaces']['opt' . $j]['descr'];
	$ifnum = $config['interfaces']['opt' . $j]['if'];
	echo '<object id="graph1"
	data="status_graph2.php?ifnum='.$ifnum.'&amp;ifname='.rawurlencode($ifdescrs).'"
	type="image/svg+xml"
	width="'.$graph_width.'"
	height="'.$graph_height.'>';
	echo '<param name="src" value="graph.php?ifnum='.$ifnum.'&amp;ifname='.rawurlencode($ifdescrs).'" />';
	echo 'Your browser does not support this svg object type!<br /> You need to update your browser or use Internet Explorer 9 or higher.';
	echo '</object>';
	$test = $j % 2;
	if ($test != 0) { echo '<br /><br /><br />'; }     /* add line breaks after second graph ... */
	else { echo $graph_gap; }                          /* or the gap between two graphs */
}
?>
	<object id="graph1" data="status_graph_cpu2.php" type="image/svg+xml" width="<?=$graph_width;?>" height="<?=$graph_height;?>">
	<param name="src" value="status_graph_cpu2.php">
	</object>

</div>
</td></tr></table>
<?php include("fend.inc");?>
