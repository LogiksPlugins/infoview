<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!isset($formConfig['infoview']['groups'])) {
	$formConfig['infoview']['groups']=[];
}

$noTab=[];

// printArray($formConfig['infoview']['groups']);
foreach($formConfig['infoview']['groups'] as $a=>$b) {
	if(!isset($b['label'])) $b['label']=toTitle($a);
	if(!isset($b['group'])) $b['group']=str_replace(" ","_",$b['label']);

	$b['fieldkey']=$a;

	if($formConfig['secure']) {
		$access=checkUserRoles($formConfig['srckey'],$b['group'],"ACCESS");
		if(!$access) {
			$noTab[]=$b['group'];
// 			continue;
		}
	}

	$fieldGroups[$b['group']][]=$b;
}

$groups=array_keys($fieldGroups);
// printArray($fieldGroups);printArray($groups);
$hiddenItems=[];
$infoHash=md5(rand());
echo '<div id="'.$infoHash.'" class="infoviewContainer infoviewContainerTabs" data-dcode="'.$dcode.'" data-dtuid="'.$dtuid.'"><ul class="nav nav-tabs">';
foreach ($groups as $nx=>$fkey) {
	$groupConfig=$fieldGroups[$fkey];
	$title=toTitle(_ling($fkey));
	$xtraIcon="";
	if($nx==0) {
		echo "<li role='presentation' class='active'><a href='#{$fkey}' role='tab' aria-controls='{$fkey}' data-toggle='tab' onclick='viewpaneContentShown(this)' >{$title}</a></li>";
	} else {
		if(in_array($fkey,$noTab)) {
			$xtraAttribute='class="disabled"';
		} else {
			$xtraAttribute='';
		}
		if(in_array($fkey,$noTab)) {
			$xtraIcon='<i class="fa fa-exclamation-triangle pull-right" title="No available permission for this information"></i>';
		}
		if(isset($groupConfig[0]) && isset($groupConfig[0]['hidden']) && $groupConfig[0]['hidden']) {
			$hiddenItems[]="<li {$xtraAttribute} role='presentation'><a href='#{$fkey}' role='tab' aria-controls='{$fkey}'  data-toggle='tab' onclick='viewpaneContentShown(this)'>{$xtraIcon}{$title}</a></li>";
		} else {
			echo "<li {$xtraAttribute} role='presentation'><a href='#{$fkey}' role='tab' aria-controls='{$fkey}'  data-toggle='tab' onclick='viewpaneContentShown(this)'>{$xtraIcon}{$title}</a></li>";
		}
	}
}
if(count($hiddenItems)>0) {
	echo '<li role="presentation" class="dropdown pull-right">';
	echo '<a href="#" class="dropdown-toggle" id="myInfoTabMenu1" data-toggle="dropdown" aria-controls="myInfoTabMenu1-contents" aria-expanded="true">';
	echo 'Others<span class="caret"></span>';
	echo '</a>';
	echo '<ul class="dropdown-menu" aria-labelledby="myInfoTabMenu1" id="myInfoTabMenu1-contents">';
	foreach($hiddenItems as $hItem) {
		echo $hItem;
	}
	echo '</ul>';
	echo '</li>';
}
echo '</ul>';
//echo '<form class="form validate" method="POST" enctype="multipart/form-data" data-infoviewkey="'.$formConfig["infoviewkey"].'" data-glink="'.$formConfig['gotolink'].'" >';
echo '<div class="infoview-content"><div class="tab-content">';
foreach ($groups as $nx=>$fkey) {
	if(in_array($fkey,$noTab)) {
		continue;
	}
	if($nx==0) {
		echo "<div role='tabpanel' class='tab-pane active' id='{$fkey}'>";
	} else {
		echo "<div role='tabpanel' class='tab-pane' id='{$fkey}'>";
	}
	echo '<div class="infoviewbox"><div class="infoviewbox-content">';
	echo "<div class='row'>";
	echo getInfoViewFieldset($fieldGroups[$fkey],$formData,$formConfig['dbkey']);
	echo "</div>";
	echo '</div></div>';
	echo "</div>";
}
echo '</div>';
echo '<hr class="hr-normal">';
echo '<div class="form-actions form-actions-padding"><div class="text-right">';
echo getInfoViewActions($formConfig['buttons']);
echo '</div></div>';
echo '</div>';
echo '</div>';
?>
