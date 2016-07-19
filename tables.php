<?PHP 

//path array for all paths and filenames (import/??/*.DBF)
$paths = glob("import/??/*.DBF");
//filenames array for all filenames 
$filenames = array_map('basename', $paths);
//dbftables array for all table types in /import/?? folders
$dbftables = array_unique($filenames);

//loop on each unique dbftables filename and set each to $file variable
foreach($dbftables as $file) { 
  
    //fileinstances array to store path of all locations of looped file
    $fileinstances = glob("import/??/" . $file);
    
    foreach($fileinstances as $fileinstance) {
       
        //variable for center code
        $ccode = substr($fileinstance,7,2);
        
        //variable for table name
        $tbl = basename("$fileinstance", ".DBF");
   
	$ctable[$tbl][] = $ccode;
       
     }
}

while (list($table, $sites) = each($ctable)) {
        echo $table . "," . count($sites) . "\n";
}

?>
