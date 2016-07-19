<?php

//ARRAY OF ALL EXPECTED CENTER CODES
$ccodes = array("AB", "AS", "AU", "AY", "BG", "BY", "CD", "CS", "DF", "DU", "FD", "FS", "GA", "GL", "GN", "HS", "JB", "JK", "JV", "LB", "LC", "LR", "LU", "NG", "OL", "PH", "PL", 
"SA", "SS", "ST", "TA", "TT", "WF", "WN");

//ARRAY OF ALL EXPECTED PDS3 DBF FILES
$tables = array('11','21','31','32','33','34','35','36','37','39','ACCOUNTS','ALT','APPL','APPLTEST','AUDACC','AUDCONF','AUDDONR','AUDPASS','AUDPAY','AUDPHY','AUDRESL','AUDSAMP','AUDSAMPD','AUDSHOTD','AUDTITR','AUDUNIT','BAYERPO','BAYMAT','CANGENE','CARRIER','CITYST','CONFTEST','CONSIGN','CONTAIN','CONVPLAS','DONRLOG','DONRNO','EMPSTAT','EQUE','ERORDATA','EXCEPT','GMML','HOT','HOTREASN','IMPXCODE','INPNGI','INPSTX','INSTLAB','LABS','LKBKCONS','LKBKOTH','MKTSRC','MLGM','OLDORNEW','OPERGOOD','ORGCHRT','PACKCOL','PACKTEST','PASSLOG','PASSWORD','PAYLOG','PAYMENT','PHYSICAL','PLASMA','PLRPT','PRINTR','PROGMULT','PRTDESC','QUALIFY','RECCPDS','RECCPDSB','RECOHIST','REGDONR','REJDONR','REJREASN','REPACK','RESLOTH','RESULTS','SA','SAMPLE','SB','SC','SD','SE','SERVDT','SF','SG','SH','SHIP','SHIPNO','SHIPTEST','SI','SJ','SK','SL','SM','SN','SO','SOFTGOOD','SOFTITEM','SP','SPEC','SPECHK','SQ','SR','SS','ST','SU','SV','SW','SX','SY','TETANUS','TIMES','UNAUTH','UNIT','UNITBON','UNITNO','VIG','VIRLTEST','VITALS','VOLDRAW','WCONFIG','WGTLOSS');

//ARRAY OF ALL EXPECTED QUEENS VILLAGE DBF FILES
$qv_tables = array('BAGS','BAYMAT','CALCGROS','CARTNO','CARTON','CARTTYPE','COMPANY','CUSTOMER','CVTTYPE','ENTREXCL','ENTRY','ERORDATA','INST','INVOICE','LKBKDET','LKBKSUM','LOCATION','LOT','PROGMULT','SELLER','SPECPRIC','SUPPLIER','SUPPRIC','TRANSACT','TYPE','WCONFIG');

//OPEN ERROR LOG FILE FOR WRITING
$bu_error_log = fopen('backup_errors.log', 'w');
//OPEN VALIDATION LOG FILE FOR WRITING
$bu_v_log = fopen('bu_validation.log', 'w');

//INITIALIZE $c_code_errs ARRAY 
$c_code_errs = array();

//ITERATE ON EACH CENTER CODE
foreach ($ccodes as $ccode) {
        
        //ITERATE ON EACH PDS3 DBF FILE
        foreach ($tables as $table) {
                $dbfile = 'import/' . $ccode . '/P:/BACKUP/PDS3/PDS3DATA/' . $table . '.DBF';
                //CHECK FOR DBF FILE EXISTENCE AND WRITE AFFIRMATIVE MESSAGE LINE TO ERROR LOG IF FOUND
                if (file_exists($dbfile)) {
                        $d_line = "Expected File for: " . $ccode . " - " . $table . ".DBF transferred to MySQL server\n";
                        fputs($bu_v_log, $d_line);
                //WRITE ERROR MESSAGE TO ERROR AND VALIDATION LOG AND ADD ENTRY TO c_code_errs ARRAY IF NOT FOUND 
                } else {
                        $d_line = "Expected File for: " . $ccode . " - " . $table . ".DBF missing\n";
                        fputs($bu_error_log, $d_line);
                        fputs($bu_v_log, $d_line);
                        $c_code_errs[$ccode][0] = 1;
                }
        }

        //CHECK FOR SIZE.TXT FILE EXISTENCE AND OPEN FOR READING IF FOUND
        $vfile = 'import/' . $ccode . '/P:/BACKUP/PDS3/SIZE.TXT';
        if (file_exists($vfile)) {
                $vfile = fopen('import/' . $ccode . '/P:/BACKUP/PDS3/SIZE.TXT', 'r');
                $count = 0;
                
                //ITERATE ON EACH LINE OF SIZE.TXT
                while(! feof($vfile)) {
                        $count++;
                        $line = fgets($vfile);
                        
                        //SKIP DOS OUTPUT IN FIRST 5 LINES AND SKIP BLANK LINES 
                        if (($count > 5) AND (substr($line,0,1) != ' ') AND (substr($line,0,1) != '')) {
                                //READ DBF FILE NAME FROM SIZE.TXT
                                $dbffile = substr($line, 39, ( strpos($line, PHP_EOL)));
                                $dbffile = substr($dbffile,0,-2);
                                //READ FILE SIZE IN BYTES FROM SIZE.TXT AND REMOVE SPACES AND COMMAS TO MATCH LINUX FORMAT
                                $dbfsize = substr($line, 20, 18);
                                $dbfsize = str_replace(' ', '', $dbfsize);
                                $dbfsize = str_replace(',', '', $dbfsize);
                                $file = "/var/www/html/import/import/" . $ccode . "/" . $dbffile;
                                $mysqlsize = filesize($file);
                                /*COMPARE FILE SIZE, IF THEY DO NOT MATCH, WRITE TO THE ERROR FILE AND ADD ENTRY TO c_code_errs.
                                WRITE ALL COMPARISONS TO VALIDATION LOG FILE*/
                                if ($dbfsize != $mysqlsize) {
                                        $error_line = $ccode . "," . $dbffile . "," . $dbfsize . "," . $mysqlsize . "\n";
                                        fputs($bu_error_log, $error_line);
                                        $c_code_errs[$ccode][1] = 2;
                                }
                                $v_line = "Center Code: " . $ccode . ",File: " . $dbffile . ", Bytes on PDS3: " . $dbfsize . ", Bytes on MySQL server: " . $mysqlsize . "\n"; 
                                fputs($bu_v_log, $v_line);
                        }
                }

        } else {
                //IF DBF FILE DOES NOT EXIST, LOG IN ERROR AND VALIDATION LOG FILE 
                $error_line = "Missing SIZE.TXT for: " . $ccode . "\n";
                fputs($bu_error_log, $error_line);
                fputs($bu_v_log, $error_line);
                $c_code_errs[$ccode][2] = 3;
        }
}

//ITERATE ON EACH FILE EXPECTED FROM QUEENS VILLAGE
foreach ($qv_tables as $table) {
        $dbfile = 'import/QV/' . $table . 'QV.DBF';
        //CHECK FOR DBF FILE EXISTENCE AND WRITE AFFIRMATIVE MESSAGE LINE TO ERROR LOG IF FOUND
        if (file_exists($dbfile)) {
                $d_line = "Expected File for: QV - " . $table . ".DBF transferred to MySQL server\n";
                fputs($bu_v_log, $d_line);
        //WRITE ERROR MESSAGE TO ERROR AND VALIDATION LOG AND ADD SINGLE ERROR ENTRY TO c_code_errs ARRAY IF NOT FOUND 
        } else {
                $d_line = "Expected File for: QV - " . $table . ".DBF missing\n";
                fputs($bu_error_log, $d_line);
                fputs($bu_v_log, $d_line);
                $c_code_errs["QV"][3] = 4;
        }
}

$ccodes[] = 'QV';

//OVERWRITE EXISTING form_val.xml FILE
$form_val = fopen('../extraction/form_val.xml','w');
fclose($form_val);

//WRITE ERRORS FROM $c_code_errs to form_val.xml 
$xml = new DOMDocument();
$xcenters = $xml->createElement("centers");
foreach($ccodes as $ccode) {
        $xcenter = $xml->createElement($ccode);
        foreach($c_code_errs as $errccode => $errarr) {
                if ($errccode == $ccode) {
                        foreach ($errarr as $errccode) {
                                $xerrcode = $xml->createElement("code",$errccode);
                                $xcenter->appendChild($xerrcode);
                        }
                }
        }
        $xcenters->appendChild($xcenter);
}
$xml->appendChild($xcenters);
$xml->formatOutput = true;
$xml->preserveWhiteSpace = false;
$xml->save('../extraction/form_val.xml');

?>
