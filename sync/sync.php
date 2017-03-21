<?php

chdir('/var/www/html/import/sync');

$user = exec('whoami');
if ($user != 'root') {
    echo "Script must be run as root user\n";
    exit();
}
    
$options = getopt("s:d:c:h", ['help']);
//var_dump($options);

$help_keys = array('h'=>false,'help'=>false);
if(count(array_intersect_key($help_keys, $options))>0) {
    echo "\nusage: php sync.php [options] [args] 
    example: \"php sync.php -s allpds3data -d yes -c all\"
    \t(executes sync on allpds3data schema after downloading files and updates all centers)\n
    -s\tspecify schema [required]
    -d\tdownload files (bool) [required]
    -s\tall centers (\"all\") or only sites that are marked as not migrated in CCODNDDR for\n\tspecified schema (\"select\") [required]
";
    exit;
} else if(!array_key_exists('s',$options) || !array_key_exists('d',$options) || !array_key_exists('c',$options)){
    echo "\nRequired parameters missing!\n
Type php sync.php -h or --help for more information on this error.\n
";
    exit;
} else {
    $schema = $options['s'];
    if($options['d'] == "yes" || $options['d'] == "y" || $options['d'] == "Y" || $options['d'] == "YES") {
        $dl = true;
    } elseif ($options['d'] == "no" || $options['d'] == "n" ||$options['d'] == "N" || $options['d'] == "NO") {
        $dl = false;
    } else {
        echo "Typo in download parameter\n";
        echo "Type php sync.php -h or --help for more information on this error.\n";
        exit;
    }
    if ($options['c'] != "all" && $options['c']  != "select") {
        echo "Typo in all or select in center [c] parameter to indicate all sites or just non-migrated (per schema selection)\n";
        exit;
    } else {
        $all_select = $options['c'];
    }
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
if ($dl == true) {
    system('vpnc default.conf');
    foreach ($ccodes as $ip => $ccode) {
        system('rm -f '.$ccode.'*.tar.gz');
        $smb_text ="smbclient \/\/10.1.".$ip.".94\/PDS3\/ -c 'cd SYNC; get ".$ccode.$date.".tar.gz' -U \" \"%\" \"";
        system($smb_text);
    }
    system('vpnc-disconnect');
    system('sftp -b dl.txt bpluser@10.2.1.10');
	// trash old QV files
	$rene_files = glob('RENEDATA*.ZIP');
	foreach($rene_files as $rfile){
		$rpaths[] = $rfile;
	}
	unset($rpaths[count($rene_files)-1]);
	foreach($rpaths as $rpath){
		system('trash '.$rpath);
	}

	// email centers that fail
	$f_paths = glob("*.{tar.gz,ZIP}", GLOB_BRACE);
	foreach($f_paths as $f_path){
		$dl_ccodes[] = substr($f_path,0,2);
	}
	$ccode_diff = array_diff($ccodes,$dl_ccodes);
	if(!in_array('RE',$dl_ccodes)){
		$ccode_diff[] = 'QV';
	}
	$pathtext = "Backups complete with the following exceptions: \n".implode(", ", $ccode_diff);
	system('echo '.$pathtext.' | mutt -s "download done" tyler.moseley@bplgroup.com -c ted.johnson@bplgroup.com');
}

// untar center files
$tar_paths = glob("*.tar.gz");
foreach ($tar_paths as $path) {
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

// unzip RENEDATA file and move
system('mkdir -p ../import/QV');
system('unzip -oP udave -d ../import/QV RENEDATA*.ZIP "*.DBF"');
system('cp RENEDATA*.ZIP /share/');
system('cp RENEDATA*.ZIP /share/archive/');
chdir('/var/www/html/import/import/QV');
system("rename -f 's/.DBF/QV.DBF/' *[A-PR-Z][A-UW-Z].DBF");

// move files to archive
$share_files = glob('/share/*[0-9].{zip,ZIP}', GLOB_BRACE);
foreach($share_files as $file){
    $paths[substr(basename($file),0,2)][] = $file;
}
foreach($paths as $site => $files){
    unset($paths[$site][count($files)-1]);
}
foreach($paths as $site => $files){
    foreach($files as $file) {
        system('mv '.$file.' /share/archive/');
    }
}

// run aux scripts
chdir('/var/www/html/import');
system('bash /usr/scripts/lockout.sh LOCK');
system('service mysql restart');
system('php convert_and_import.php '.$schema);
system('php index.php '.$schema);
system('bash /usr/scripts/lockout.sh UNLOCK');
chdir('/var/www/html/ext_bi');
system('php bi.php '.$schema);

if ($all_select == 'select') {
	chdir('/var/www/html/import');
    system('trash -R import && mv importbu import');
}

?>
