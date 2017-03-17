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

foreach ($ccodes as $ip => $ccode) {
    system('tar -zxvf '.$ccode.$date.'.tar.gz');
    system('mkdir -p ../import/'.$ccode);
    system('cp '.$ccode.$date.'/*.DBF  ../import/'.$ccode.'/');
    system('rm -R '.$ccode.$date);
}



?>
