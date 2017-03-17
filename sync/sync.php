<?php

$schema = $argv[1]
        or die("Please enter schema\n");

//INCLUDES
include '../../gfunctions.php';

$schema = check_schema_update($schema);
$link = mysql_connect($schema);

$mig_sql = "
    SELECT C_CODE
    , ip_node
    FROM ".$schema.".CCODNDDR
    WHERE migrated = '0' 
";
$mig_query = mysqli_query($link,$mig_sql)
	or die ("Error in mig_sql" . mysqli_error($link));

while ($mig_arr = mysqli_fetch_array($mig_query, MYSQLI_ASSOC)) {
    $ccodes[$mig_arr['ip_node']] = $mig_arr['C_CODE'];
}
$date = '20170312';
system('vpnc default.conf');

// Loop on each ip and ccode to download site backups
foreach ($ccodes as $ip => $ccode) {
    $smb_text ="smbclient \/\/10.1.".$ip.".94\/PDS3\/ -c 'cd SYNC; get ".$ccode.$date.".tar.gz' -U \" \"%\" \"";
    system($smb_text);
}

system('vpnc-disconnect');

?>
