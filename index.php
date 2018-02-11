<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/api.php";

$slug=_slug("?/src/dcode");

if(isset($slug['src']) && !isset($_REQUEST['src'])) {
	$_REQUEST['src']=$slug['src'];
}
if(isset($slug['dcode'])) {
  $dcode=$slug['dcode'];
} elseif(isset($_REQUEST['dcode'])) {
  $dcode=$_REQUEST['dcode'];
} else {
  echo "<h1 class='errormsg'>Sorry, DCode not defined.</h1>";
  return;
}

if(isset($_REQUEST['src']) && strlen($_REQUEST['src'])>0) {
	$formConfig=findInfoView($_REQUEST['src']);
	
	$_ENV['INFOVIEW-REFHASH']=$dcode;
	
	if($formConfig) {
// 		echo "<div class='formholder' style='width:100%;height:100%;'>";
		printInfoView($formConfig,$formConfig['dbkey'],["md5(id)"=>$dcode]);
// 		echo "</div>";
	} else {
		echo "<h1 class='errormsg'>Sorry, infoview '{$_REQUEST['src']}' not found.</h1>";
	}
} else {
	echo "<h1 class='errormsg'>Sorry, infoview not defined.</h1>";
}

?>