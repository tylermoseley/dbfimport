<?php

$share_files = glob('/share/*[0-9].{zip,ZIP}', GLOB_BRACE);
foreach($share_files as $file){
    $paths[substr(basename($file),0,2)][] = $file;
}
foreach($paths as $site => $files){
    echo $site;
    print_r($files);
    unset($paths[$site][count($files)-1]);
}
foreach($paths as $site => $files){
    foreach($files as $file) {
        system('mv '.$file.' /share/archive/');
    }
}
//var_dump(getopt());

//if (empty($argv[1])) {
//    echo "Please enter schema\n";
//    exit();
//} else {
//    $schema = $argv[1];
//}
//
//if (empty($argv[2])) {
//    echo "Please enter dl-true or dl-false after schema to indicate whether files need to be downloaded or not\n";
//    exit();
//} else {
//    if ($argv[2] != "dl-true" && $argv[2] != "dl-false") {
//        echo "Typo in dl-true or dl-false after schema to indicate whether files need to be downloaded or not\n";
//        exit();
//    } else {
//        $manual = $argv[2];
//    }
//}
//
//if (empty($argv[3])) {
//    echo "Please enter all or select after dl-???? to indicate all sites or just sites in files\n";
//    exit();
//} else {
//    if ($argv[3] != "all" && $argv[3] != "select") {
//        echo "Typo in all or select after dl-???? to indicate all sites or just non-migrated (per schema selection)\n";
//        exit();
//    } else {
//        $all_select = $argv[3];
//    }
//}

?>

