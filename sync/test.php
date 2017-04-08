<?php

chdir('/var/www/html/import/sync');

$user = exec('whoami');
if ($user != 'root') {
    echo "Script must be run as root user\n";
    exit();
}
    
$options = getopt("s:d:c:b:h", ['help']);
var_dump($options);

$help_keys = array('h'=>false,'help'=>false);
if(count(array_intersect_key($help_keys, $options))>0) {
    echo "\nusage: php sync.php [options] [args] 
    example: \"php sync.php -s allpds3data -d yes -c all\"
    \t(executes sync on allpds3data schema after downloading files and updates all centers)\n
    -s\tspecify schema [required]
    -d\tdownload files (yes or no) [required]
    -c\tall centers (\"all\") or only sites that are marked as not migrated in CCODNDDR for\n\tspecified schema (\"select\") [required]
	-b\tbi interface (yes or no) [required]
";
    exit;
} else if(!array_key_exists('s',$options) || !array_key_exists('d',$options) || !array_key_exists('c',$options) || !array_key_exists('b',$options)){
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
	if($options['b'] == "yes" || $options['b'] == "y" || $options['b'] == "Y" || $options['b'] == "YES") {
        $bi = true;
    } elseif ($options['b'] == "no" || $options['b'] == "n" ||$options['b'] == "N" || $options['b'] == "NO") {
        $bi = false;
    } else {
        echo "Typo in bi interface parameter\n";
        echo "Type php sync.php -h or --help for more information on this error.\n";
        exit;
    }
}

echo $bi ? 'true' : 'false';


?>