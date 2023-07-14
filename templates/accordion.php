<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!isset($formConfig['infoview']['groups'])) {
	$formConfig['infoview']['groups']=[];
}

$noTab=[];

// printArray($formConfig['infoview']['groups']);
foreach($formConfig['infoview']['groups'] as $a=>$b) {
	if(isset($b['disabled']) && $b['disabled']) continue;
	if(!isset($b['label'])) $b['label']=toTitle($a);
	if(!isset($b['group'])) $b['group']=str_replace(" ","_",$b['label']);

	$b['fieldkey']=$a;

	if(isset($b['policy'])) {
		$access=checkUserPolicy($b['policy']);
		if(!$access) {
			$noTab[]=$b['group'];
			// continue;
		}
	}

	$fieldGroups[$b['group']][]=$b;
}

$groups=array_keys($fieldGroups);
// printArray($fieldGroups);printArray($groups);
$hiddenItems=[];
$infoHash=md5(rand());
$accordionID=$formConfig['infoviewkey'];

echo '<div class="infoviewContainer infoviewContainerAccordion" data-dcode="'.$dcode.'" data-dtuid="'.$dtuid.'">';
echo '<div class="panel-group infoview-content" id="accordion'.$accordionID.'" role="tablist" aria-multiselectable="true">';

foreach ($groups as $nx=>$fkey) {
	$title=toTitle(_ling($fkey));
	$panelID=md5($fkey);

	if(in_array($fkey,$noTab)) {
		if(getConfig("INFOVIEWTABLE_SHOW_DISABLED_TABS")!="true") {
			continue;
		}
	}

	echo '<div class="panel panel-default">';

	echo '<div class="panel-heading" role="tab" id="heading'.$panelID.'">';
	echo '<h4 class="panel-title">';
	
	if(in_array($fkey,$noTab)) {
		echo '<i class="fa fa-exclamation-triangle pull-right" title="No available permission for this information"></i>';
	}
	
	if($nx==0) {
		echo '<a role="button" data-toggle="collapse" aria-expanded="true" aria-controls="collapse'.$panelID.'" data-parent="#accordion'.$accordionID.'" href="#collapse'.$panelID.'" onclick="viewpaneContentShown(this)" >'.$title.'</a>';
	} else {
		echo '<a role="button" data-toggle="collapse" aria-expanded="false" aria-controls="collapse'.$panelID.'" data-parent="#accordion'.$accordionID.'" href="#collapse'.$panelID.'" onclick="viewpaneContentShown(this)" >'.$title.'</a>';
	}
	echo '</h4>';
	echo '</div>';
	
	if(in_array($fkey,$noTab)) {
		echo '</div>';
		continue;
	}
	
	if($nx==0) {
		echo '<div id="collapse'.$panelID.'" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading'.$panelID.'">';
	} else {
		echo '<div id="collapse'.$panelID.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading'.$panelID.'">';
	}

	echo '<div class="panel-body">';
	echo '<div class="infoviewbox"><div class="infoviewbox-content">';
	echo "<div class='row'>";
	$fieldGroups[$fkey]['security']['module']=$formConfig['srckey'];
	$fieldGroups[$fkey]['security']['activity']=$fkey;
	echo getInfoViewFieldset($fieldGroups[$fkey],$formData,$formConfig['dbkey']);
	echo "</div>";
	echo '</div></div>';
	echo '</div>';
	echo '</div>';

	echo '</div>';
}

echo '<hr class="hr-normal">';
echo '<div class="form-actions form-actions-padding"><div class="text-right">';
echo getInfoViewActions($formConfig['buttons']);
echo '</div></div>';

echo '</div>';
echo '</div>';
?>
