<?PHP 

$schema = $argv[1]
        or die("Please enter schema\n");

date_default_timezone_set('America/Chicago');
echo "Began at: ". date('m/d/Y h:i:sa') ."\n";
$starttime = microtime(true);

//DB Connection variables
$db_host = 'localhost';
$db_uname = 'root';
$db_passwd = 'plaut0mati0n';
$db = $schema;
$link = mysqli_connect($db_host,$db_uname, $db_passwd, $db)
or die ("Could not connect to database, error: " . mysqli_error($link));  
 
//path array for all paths and filenames (import/??/*.DBF)
$paths = glob("import/??/*.DBF");
//filenames array for all filenames 
$filenames = array_map('basename', $paths);
//dbftables array for all table types in /import/?? folders
$dbftables = array_unique($filenames);

//loop on each unique dbftables filename and set each to $file variable
foreach($dbftables as $file) {
    
    echo "processing...." . $file . "\n";
    flush();
    
    //test to see if files are foxuser.dbf or fp25eror.dbf,
    //if so skip (these are foxpro dbfs that will not be used
    //and are a different format that doesnt play well with PHP)
    if ($file == "FOXUSER.DBF"){
        continue;
    }
    if ($file == "FP25EROR.DBF"){
        continue;
    }
    if ($file == "SAMPLEX.DBF"){
        continue;
    }
    //variable for table name
    $tblname = substr($file,0,-4);
    
    //drop the table before entering data
    $dropsql = "DROP TABLE IF EXISTS `$tblname`;";
    mysqli_query($link,$dropsql)
    or die ("Drop table if exists failed for table: " . $tblname . ". Error: " . mysqli_error($link));
    
    //fileinstances array to store path of all locations of looped file
    $fileinstances = glob("import/??/" . $file);
    
	//INITIALIZE $dup_preventer ARRAY 
	$dup_preventer = array();
	
    foreach($fileinstances as $fileinstance) { 
       
        //variable for center code
        $ccode = substr($fileinstance,7,2);
        
        //variable for table name
        $tbl = basename("$fileinstance", ".DBF");
        
        // Open dbf
        $dbaseopen = dbase_open($fileinstance, 0);
        if (!$dbaseopen){
            echo "Could not open DBF: " . $fileinstance . "\n";
			//ENTER CODE 5 FOR ANY CENTER WITH A DBF THAT FAILS TO OPEN
			if (!in_array($ccode,$dup_preventer)) {
				$xml = new DOMDocument();
				$xml->preserveWhiteSpace = false;
				$xml->formatOutput = true;
				$xml->load('../extraction_dev/form_val.xml');
				$xpath = new DOMXpath($xml);
				$pathtext = "//centers/" . $ccode;
				$centpaths = $xpath->query($pathtext);
				$xerrcode = $xml->createElement("code","5");
				foreach ($centpaths as $centpath) {
					$centpath->appendChild($xerrcode);
				}
				$xml->save('../extraction_dev/form_val.xml');
				$dup_preventer[] = $ccode;
			}
			continue;
            }
      
        // fields array variable for fields in dbf
        $fields = dbase_get_header_info($dbaseopen);
    
        //empty array for new fields
        $line = array();
        
        //Create C_CODE Field
        $line[]= "`C_CODE` VARCHAR(2)";
    
        //loop through each field and assign $field variable to each field
        foreach($fields as $field) {
            //convert .dbf field type to closest mysql equivalent and populate $line
            //array with sql statement name and types for all fields
            switch($field['type'])
            {
                case 'character':
                $line[]= "`$field[name]` VARCHAR( $field[length] )";
                break;
                case 'number':
                $line[]= "`$field[name]` DECIMAL( $field[length] , $field[precision] )";
                break;
                case 'boolean':
                $line[]= "`$field[name]` BOOL";
                break;
                case 'date':
                $line[]= "`$field[name]` DATE";
                break;
                case 'memo':
                //$line[]= "`$field[name]` TEXT";
                break;
                }
            }
        //glue string together with comma between
        $str = implode(",",$line);   
    
        //create variable for sql statement
        $createsql = "CREATE TABLE IF NOT EXISTS `$tbl` ( $str );";
        //run sql statement to create table structures
        mysqli_query($link,$createsql)
        or die ("Couldn't create table structure for table: " . $tbl . ". Error: " . mysqli_error($link));
        set_time_limit(0);

        $lock = "LOCK TABLE `" . $tbl . "` WRITE;";
        mysqli_query($link,$lock)
        or die ("Lock Table failed for table: " . $tbl . ". Error: " . mysqli_error($link));
        
        $trans = "START TRANSACTION;";
        mysqli_query($link,$trans)
        or die ("Start transaction failed for table: " . $tbl . ". Error: " . mysqli_error($link));
        
	//create variable for number of records in dbf
        $totalrecords = dbase_numrecords($dbaseopen);

        for ($i=1; $i<=$totalrecords; $i++){
           //records array variable to hold records
    
           $records = @dbase_get_record_with_names($dbaseopen,$i);
          
            $insql = "INSERT HIGH_PRIORITY INTO $db.$tbl VALUES (";
            $insql .= "'" . $ccode . "',";
            if ($records["deleted"] == '1') {
				continue;
			}
            foreach ($records as $record => $head){
                  if ($record == 'deleted'){
                    continue;
                  }
            $insql .= "'" . addslashes(trim($head)) . "',";   
            }
            $insql = substr($insql, 0, -1);
            $insql .= ')';
            
            mysqli_query($link,$insql)
            or die ("Could not insert record, error: " . mysqli_error($link));
            set_time_limit(0);        
        }
        $commit = "COMMIT;";
        mysqli_query($link,$commit)
        or die ("Commit failed for table: " . $tbl . ". Error: " . mysqli_error($link));
        set_time_limit(0);
        
        $unlock = "UNLOCK TABLES;";
        mysqli_query($link,$unlock)
        or die ("UNLOCK failed for table: " . $tbl . ". Error: " . mysqli_error($link));

      //close DBF
        dbase_close($dbaseopen);
    }
}
//WRITE ENTRY IN FORM VALIDATION FOR SCRIPT EXECUTION
$xml = new DOMDocument();
$xml->preserveWhiteSpace = false;
$xml->formatOutput = true;
$xml->load('../extraction/form_val.xml');
$xpath = new DOMXpath($xml);
$pathtext = '//centers';
$centpaths = $xpath->query($pathtext);
$xerrcode = $xml->createElement('complete','true');
foreach ($centpaths as $centpath) {
    $centpath->appendChild($xerrcode);
}
$xml->save('../extraction/form_val.xml');

//WRITE ELAPSED TIMES TO LOG
date_default_timezone_set('America/Chicago');
$endtime = microtime(true);
$elapsedtime = $endtime - $starttime;
echo "Completed at: " . date('m/d/Y h:i:sa') . "\n";
echo "Elapsed time: " . gmdate("H:i:s", $elapsedtime) . "\n";
?>
