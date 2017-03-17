<?php
chdir('/var/www/html/import/sync');

if (empty($argv[1])) {
    echo "Please enter schema\n";
    exit();
} else {
    $schema = $argv[1];
}

if (empty($argv[2])) {
    echo "Please enter dl-true or dl-false after schema to indicate whether files need to be downloaded or not\n";
    exit();
} else {
    if ($argv[2] != "dl-true" && $argv[2] != "dl-false") {
        echo "Typo in dl-true or dl-false after schema to indicate whether files need to be downloaded or not\n";
        exit();
    } else {
        $manual = $argv[2];
    }
}

if (empty($argv[3])) {
    echo "Please enter all or select after dl-???? to indicate all sites or just sites in files\n";
    exit();
} else {
    if ($argv[3] != "all" && $argv[3] != "select") {
        echo "Typo in dl-true or dl-false after schema to indicate whether files need to be downloaded or not\n";
        exit();
    } else {
        $all_select = $argv[3];
    }
}

$user = exec('whoami');
if ($user != 'root') {
    echo "Script must be run as root user\n";
    exit();
}

if ($all_select == 'select') {
    system('mv ../import ../importbu && mkdir ../import');
}

//INCLUDES
include '../../gfunctions.php';

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
$date = date('Ymd');
$yest = date('Ymd')-1;

// download files if dl-true
if ($manual == 'dl-true') {
    system('vpnc default.conf');
    foreach ($ccodes as $ip => $ccode) {
        system('rm -f '.$ccode.'*.tar.gz');
        $smb_text ="smbclient \/\/10.1.".$ip.".94\/PDS3\/ -c 'cd SYNC; get ".$ccode.$date.".tar.gz' -U \" \"%\" \"";
        system($smb_text);
    }
    system('vpnc-disconnect');
    system('rm -f RENEDATA*.ZIP');
    system('sftp -b dl.txt bpluser@10.2.1.10');
}

$paths = glob("*.tar.gz");
$pathtext = "Sites downloaded- ".implode(", ", $paths);
// email centers that fail
system('echo '.$pathtext.' | mutt -s "download done" tyler.moseley@bplgroup.com -c ted.johnson@bplgroup.com');

// untar center files
foreach ($paths as $path) {
    $dirname = basename($path, ".tar.gz");
    $ccodedir = substr($dirname,0,2);
    system('tar -zxvf '.$path);
    system('mkdir -p ../import/'.$ccodedir);
    system('cp '.$dirname.'/*.DBF  ../import/'.$ccodedir.'/');
    system('zip -j '.$dirname.'.zip '.$dirname.'/*.*');
    system('rm -R '.$dirname);
    system('cp '.$dirname.'.zip /share/');
    system('mv '.$dirname.'.zip /share/archive/');
}

// unzip RENEDATA file
system('rm -f ../import/QV/*.*');
system('mkdir -p ../import/QV');
system('unzip -P udave -d ../import/QV RENEDATA*.ZIP "*.DBF" ');
system('cp RENEDATA*.ZIP /share/');
system('cp RENEDATA*.ZIP /share/archive/');
chdir('/var/www/html/import/import/QV');
system("rename 's/.DBF/QV.DBF/' *.DBF");

chdir('/var/www/html/import');
system('bash /usr/scripts/lockout.sh LOCK');
system('php convert_and_import.php '.$schema);
system('php index.php '.$schema);
system('bash /usr/scripts/lockout.sh UNLOCK');
chdir('/var/www/html/ext_bi');
system('php bi.php '.$schema);

if ($all_select == 'select') {
    system('trash -R ../import && mv ../importbu ../import');
}

?>
