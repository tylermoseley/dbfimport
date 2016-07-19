<?PHP

//CONNECTION VARIABLES
$ip = getHostByName(getHostName());
if ($ip == '10.2.1.102') {
    define("DB_HOST", "localhost");
    define("DB_USER", "root");
    define("DB_PASS", "plaut0mati0n");
} else {
    define("DB_HOST", "10.2.1.102");
    define("DB_USER", "remote");
    define("DB_PASS", "t1a2p3");
}
define("DB_NAME", "allpds3data");

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME)
    or die ("Could not connect to database, error: " . mysqli_error($link));

echo "\n";

//SQL
$tables_sql = "
SHOW TABLES
";

//EXECUTE STATEMENT
$tables_query = mysqli_query($link,$tables_sql)
        or die ("Error in tables_sql");

//DO WORK
while ($table = mysqli_fetch_array($tables_query, MYSQLI_ASSOC)) {
	//SQL 
	$ccodes_sql = "
	SELECT 
	IF (EXISTS(SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME = '" . $table["Tables_in_allpds3data"] . "' AND COLUMN_NAME = 'C_CODE'),
		(SELECT COUNT(*) as cnt
			FROM (
			SELECT C_CODE 
			FROM `" . $table["Tables_in_allpds3data"] . "`
			GROUP BY C_CODE
			) as a),'0') as COUNT
	";
	$flag = 1;
	//EXECUTE STATEMENT
	$ccodes_query = mysqli_query($link,$ccodes_sql)
        or $flag = 0;
	
	if($flag != 0) {
		while ($ccode = mysqli_fetch_array($ccodes_query, MYSQLI_ASSOC)) {
			echo $table["Tables_in_allpds3data"] . " - " . $ccode["COUNT"] . "\n";
		}
	}
}

?>
