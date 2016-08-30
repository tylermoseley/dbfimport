<?php
//START TIME
date_default_timezone_set('America/New_York');
echo "Began at: ". date('m/d/Y h:i:sa') ."\n";
flush();
$starttime = microtime(true);

include 'database.class.php';
define("DB_NAME", "allpds3data");
include '../gfunctions.php';

$database = new database();

$drop_sql = "DROP TABLE IF EXISTS allpds3data.`CARTONS`";
mysqli_query($link,$drop_sql)
	or die ("Error in drop_sql");
echo "cartons dropped\n";
//SELECT STATEMENT
$create_sql = "
CREATE TABLE IF NOT EXISTS allpds3data.`CARTONS` LIKE allpds3data.`21`; 
";

//EXECUTE STATEMENT
$create_query = mysqli_query($link,$create_sql)
	or die("Error in cartons_sql");
echo "cartons created";
//SELECT STATEMENT
$add_sql = "
	ALTER TABLE allpds3data.`CARTONS` ADD `BOXING_KEY` VARCHAR(12) AFTER C_CODE;
";

//EXECUTE STATEMENT
$add_query = mysqli_query($link,$add_sql)
        or die("Error in add_sql" . mysqli_error($link));
echo "boxing key added\n";

//SELECT STATEMENT
$cartons_sql = "
SHOW TABLES LIKE '__';
";

//EXECUTE STATEMENT
$cartons_query = mysqli_query($link,$cartons_sql)
        or die("Error in cartons_sql");
echo "carton tables found\n";
$start_t = "START TRANSACTION;";
mysqli_query($link,$start_t)
	or die("Error in START TRANSACTION");

while ($carton = mysqli_fetch_array($cartons_query, MYSQLI_ASSOC)) {
        $boxs_sql = "
                SELECT * FROM `" . $carton["Tables_in_allpds3data (__)"] . "`;
        ";

        //EXECUTE STATEMENT
        $boxs_query = mysqli_query($link,$boxs_sql)
                        or die("Error in boxs_sql" . mysqli_error($link));

	while ($box = mysqli_fetch_array($boxs_query, MYSQLI_ASSOC)) {
		if ($box["WEEK_NO"] == "") {
			$week = "  ";
		} else {
			$week = str_pad($box["WEEK_NO"],2," ",STR_PAD_LEFT);
		}
		$boxing = $box["YEAR_NO"] . $week . $carton["Tables_in_allpds3data (__)"] . $box["CASE_NO"];
		$insert_sql = "INSERT INTO `CARTONS` VALUES (";
		$insert_sql .= "'" . $box["C_CODE"] . "',";
		$insert_sql .= "'" . $boxing . "',";
		$insert_sql .= "'" . $box["YEAR_NO"] . "',";
		$insert_sql .= "'" . $box["WEEK_NO"] . "',";
		$insert_sql .= "'" . $box["CASE_NO"] . "',";
		$insert_sql .= "'" . $box["TL_UNITS"] . "',";
		$insert_sql .= "'" . $box["TL_NOBLEED"] . "',";
		$insert_sql .= "'" . $box["TL_LITERS"] . "',";
		$insert_sql .= "'" . $box["SHIP_DATE"] . "',";
		$insert_sql .= "'" . $box["SHIP_NO"] . "',";
		if (array_key_exists("TITER", $box)) {
			$insert_sql .= "'" . $box["TITER"] . "',";
		} else {
			$insert_sql .= "'',";
		}
		$insert_sql .= "'" . $box["HOT"] . "',";
		$insert_sql .= "'" . $box["TL_VIRAL"] . "',";
		$insert_sql .= "'" . $box["RECO_DATE"] . "',";
		$insert_sql .= "'" . $box["RECO_TECH"] . "',";
		$insert_sql .= "'" . $box["RECO_FLAG"] . "',";

		$insert_sql = substr($insert_sql, 0, -1);
		$insert_sql .= ")";

		//EXECUTE STATEMENT
		$insert_query = mysqli_query($link,$insert_sql)
      			or die("Error in insert_sql for: " . $carton["Tables_in_allpds3data (__)"] . " - " . mysqli_error($link));
	}
echo $carton["Tables_in_allpds3data (__)"] . "complete\n";
}

$commit = "COMMIT;";
mysqli_query($link,$commit)
	or die("ERROR in COMMIT");

echo "Cartons Table Created\n";

$drop_sql = "DROP TABLE IF EXISTS allpds3data.`INDEXED_LOT`";
mysqli_query($link,$drop_sql)
        or die ("Error in drop_sql");

//SELECT STATEMENT
$create_sql = "
CREATE TABLE IF NOT EXISTS allpds3data.`INDEXED_LOT` LIKE allpds3data.`LOTQV`; 
";

//EXECUTE STATEMENT
$create_query = mysqli_query($link,$create_sql)
        or die("Error in cartons_sql");

//SELECT STATEMENT
$add_sql = "
        ALTER TABLE allpds3data.`INDEXED_LOT` ADD `C_CODE_2` VARCHAR(2) AFTER C_CODE;
";

//EXECUTE STATEMENT
$add_query = mysqli_query($link,$add_sql)
        or die("Error in add_sql" . mysqli_error($link));

//SELECT STATEMENT
$add_sql = "
        ALTER TABLE allpds3data.`INDEXED_LOT` ADD `SHIP_NO` VARCHAR(5) AFTER C_CODE_2;
";

//EXECUTE STATEMENT
$add_query = mysqli_query($link,$add_sql)
        or die("Error in add_sql" . mysqli_error($link));

//SELECT STATEMENT
$add_sql = "
        ALTER TABLE allpds3data.`INDEXED_LOT` ADD `SPLIT` VARCHAR(1) AFTER SHIP_NO;
";

//EXECUTE STATEMENT
$add_query = mysqli_query($link,$add_sql)
        or die("Error in add_sql" . mysqli_error($link));

$start_t = "START TRANSACTION;";
mysqli_query($link,$start_t)
	or die("Error in START TRANSACTION");

$lots_sql = "
SELECT * FROM allpds3data.`LOTQV`;
";

//EXECUTE STATEMENT
$lots_query = mysqli_query($link,$lots_sql)
	or die("Error in boxs_sql" . mysqli_error($link));

while ($lot = mysqli_fetch_array($lots_query, MYSQLI_ASSOC)) {
	$ccode2 = SUBSTR($lot["LOTNO"],0,2);
	$shipno = ltrim(SUBSTR($lot["LOTNO"],4,4),'0');
	$split = SUBSTR($lot["LOTNO"],8,1);
	$insert_sql = "INSERT INTO `INDEXED_LOT` VALUES (";
	$insert_sql .= "'" . $lot["C_CODE"] . "',";
	$insert_sql .= "'" . $ccode2 . "',";
	$insert_sql .= "'" . $shipno . "',";
	$insert_sql .= "'" . $split . "',";
	$insert_sql .= "'" . $lot["LOTNO"] . "',";
	$insert_sql .= "'" . $lot["COMPANY"] . "',";
	$insert_sql .= "'" . $lot["LOCATION"] . "',";
	$insert_sql .= "'" . $lot["TYPE"] . "',";
	$insert_sql .= "'" . str_replace("'","",$lot["SUPPLIER"]) . "',";
	$insert_sql .= "'" . $lot["CONVERT"] . "',";
	$insert_sql .= "'" . $lot["RECDATE"] . "',";
	$insert_sql .= "'" . $lot["NUMB_CARTS"] . "',";
	$insert_sql .= "'" . $lot["NUMB_UNITS"] . "',";
	$insert_sql .= "'" . $lot["LITERS"] . "',";
	$insert_sql .= "'" . $lot["BLNUMBER"] . "',";
	$insert_sql .= "'" . $lot["COSTPL"] . "',";
	$insert_sql .= "'" . $lot["TOTAL_COST"] . "',";
	$insert_sql .= "'" . str_replace("'","",$lot["COMMENT1"]) . "',";
        $insert_sql .= "'" . str_replace("'","",$lot["COMMENT2"]) . "',";
        $insert_sql .= "'" . $lot["BLEEDSTART"] . "',";
        $insert_sql .= "'" . $lot["BLEEDEND"] . "',";
        $insert_sql .= "'" . $lot["SALEPL"] . "',";
        $insert_sql .= "'" . $lot["INVOICE"] . "',";
        $insert_sql .= "'" . $lot["CO_INVOICE"] . "',";
        $insert_sql .= "'" . $lot["SHPDATE"] . "',";
        $insert_sql .= "'" . $lot["INTERCOPL"] . "',";
        $insert_sql .= "'" . $lot["PLAT_CPB"] . "',";
        $insert_sql .= "'" . $lot["PLAT_NOBAG"] . "',";
        $insert_sql .= "'" . $lot["PLAT_REJ"] . "',";
        $insert_sql .= "'" . $lot["PLAT_COST"] . "',";
        $insert_sql .= "'" . $lot["CUSTOMER"] . "',";
        $insert_sql .= "'" . $lot["TOT_SALE"] . "',";
        $insert_sql .= "'" . $lot["TOT_INTER"] . "',";
        $insert_sql .= "'" . $lot["SELLER"] . "',";
        $insert_sql .= "'" . str_replace("'","",$lot["COMMENT3"]) . "',";
        $insert_sql = substr($insert_sql, 0, -1);
	$insert_sql .= ")";

//EXECUTE STATEMENT
$insert_query = mysqli_query($link,$insert_sql)
        or die("Error in insert_sql " . mysqli_error($link));
}

$commit = "COMMIT;";
mysqli_query($link,$commit)
	or die("ERROR in COMMIT");

echo "Indexed Lot Table Created\n";

$tables = array ('CCODNDDR' => array('C_CODE', 'NDDR_CODE'),
		'UNIT' => array('C_CODE', 'UNIT_NO', 'WT_BOTT', 'DONOR_NO', 'C_CODE`, `UNIT_NO', 'C_CODE`, `DONOR_NO', 'C_CODE`, `BOXING_KEY', 'C_CODE`, `DONOR_NO`, `DONOR_DATE'),
		'REGDONR' => array('C_CODE', 'DONOR_NO', 'C_CODE`, `DONOR_NO'),
		'TETANUS' => array('C_CODE', 'UNIT_NO', 'DONOR_NO'),
		'REJDONR' => array('C_CODE', 'DONOR_NO', 'REJ_REASON', 'REJ_PERIOD', 'C_CODE`, `DONOR_NO'),
		'COD_INTER' => array('P_DEF_TEXT', 'P_DEF_PER'),
		'RESULTS' => array('C_CODE', 'UNIT_NO', 'C_CODE`, `UNIT_NO'),
		'GLOBAL_GMML' => array('GMS'),
		'HOT' => array('C_CODE`, `UNIT_NO', 'HOT1', 'C_CODE', 'UNIT_NO'),
		'SAMPLE' => array('DONOR_NO', 'C_CODE', 'SAMP_DATE', 'C_CODE`, `SAMP_NO', 'C_CODE`, `DONOR_NO`, `SAMP_DATE'),
		'RESLOTH' => array('C_CODE`, `SAMP_NO'),
	    	'CARTONS' => array('C_CODE`, `BOXING_KEY', 'C_CODE`, `SHIP_NO', 'C_CODE', 'BOXING_KEY', 'SHIP_NO'),
		'INDEXED_LOT' => array('C_CODE_2', 'SHIP_NO', 'CUSTOMER', 'C_CODE_2`, `SHIP_NO'),
		'PHYSICAL' => array('C_CODE`, `DONOR_NO`, `PHY_DATE', 'C_CODE', 'DONOR_NO', 'PHY_DATE'),
		'AUDUNIT' =>array('C_CODE`, `UNIT_NO', 'C_CODE', 'UNIT_NO'),
		'SHIP' => array('C_CODE', 'SHIP_NO', 'CONS_CODE'),
		'COD_QUAR' => array('PDS3_HOT_CODE'),
		'COD_ETS' => array('PDS3_CONS_CODE'),
		'CONS_CUST' => array('CONS_CODE'),
		'PAYLOG' => array('C_CODE', 'UNIT_NO', 'C_CODE`, `UNIT_NO'),
		'TIMES' => array('C_CODE', 'DONOR_NO', 'DONOR_DATE', 'C_CODE`, `DONOR_NO`, `DONOR_DATE')
		);
$indexes = array();
while ($table = current($tables)) {
	$database->query('SHOW INDEX FROM ' . key($tables));

	$indexes = $database->resultset_assoc();

	$index = array("blank");
	if (empty($indexes)) { 
	} else {
		foreach ($indexes as $indexkey) {
			$index[] = $indexkey["Key_name"];
		}	
	}
	foreach ($table as $field) {
		if (in_array($field, $index)) {
			//DO NOTHING
		} else {
			$database->query('ALTER TABLE ' . key($tables) . ' ADD INDEX (`' . $field . '`)');
			$database->execute();
		}
	}
echo "Index for " . key($tables) . " created\n";
next($tables);
}

echo "Creating fulltext indexes";

$database->query('CREATE FULLTEXT INDEX `idx_REGDONR_LAST`  ON `allpds3data`.`REGDONR` (LAST) COMMENT \'\' ALGORITHM DEFAULT LOCK DEFAULT');
$database->execute();

$database->query('CREATE FULLTEXT INDEX `idx_REGDONR_FIRST`  ON `allpds3data`.`REGDONR` (FIRST) COMMENT \'\' ALGORITHM DEFAULT LOCK DEFAULT');
$database->execute();


echo "Fulltext indexes created";

//RESTART MYSQL SERVICE
$command = shell_exec('service mysql restart');
echo $command;

//END TIME
date_default_timezone_set('America/New_York');
$endtime = microtime(true);
$elapsedtime = $endtime - $starttime;
echo "Completed at: " . date('m/d/Y h:i:sa') . "\n";
echo "Elapsed time: " . gmdate("H:i:s", $elapsedtime) . "\n";
?>
