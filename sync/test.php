<?php
$ccode_diff = array('QV','AB');
	$pathtext = "Backups complete with the following exceptions: ".implode(", ", $ccode_diff);
	system('echo '.$pathtext.' | mutt -s "download done" tyler.moseley@bplgroup.com -c tyler.moseley@me.com');

?>