<?php
echo "<?xml version=\"1.0\" ?>\n";
echo "<confirm>";
$ccodes = array('AB','CS','WF');
$errors = array();
foreach ($ccodes as $ccode) {    
    $xml = new DOMDocument();
    $xml->load('../extraction_dev/form_val.xml');
    $xpath = new DOMXpath($xml);
    $pathtext = "//centers/" . $ccode . "/code";
    $centpaths = $xpath->query($pathtext);
    foreach ($centpaths as $centpath) {
        $errors[$ccode][] = $centpath->nodeValue;
    }    
}
$xpath2 = new DOMXpath($xml);
$pathtext2 = "//centers/complete";
$centpaths2 = $xpath2->query($pathtext2);
if ($centpaths2->length == 0) {
    $errors["fail"][] = "1";
}
$err_text = "\n  <errors>\n";
foreach ($errors as $center => $err) {
    $err_text .= "    <" . $center . ">\n";
    foreach ($err as $error) {
        $err_text .= "      <code>" . $error . "</code>\n";
    }
    $err_text .= "    </" . $center . ">\n";
}
$err_text .= "  </errors>\n";
echo $err_text;
?>