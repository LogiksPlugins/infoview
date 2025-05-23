<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("findInfoView")) {
  function findInfoView($file) {
		$fileName=$file;
		if(!file_exists($file)) {
			$file=str_replace(".","/",$file);
		}
		
		$fsArr=[
					$file,
					APPROOT.APPS_MISC_FOLDER."infoviews/{$file}.json",
      		APPROOT.APPS_MISC_FOLDER."forms/{$file}.json",
			];
			
		if(isset($_REQUEST['forSite']) && defined("CMS_SITENAME")) {
			$fsArr[]=ROOT."apps/".CMS_SITENAME."/".APPS_MISC_FOLDER."infoviews/{$file}.json";
			$fsArr[]=ROOT."apps/".CMS_SITENAME."/".APPS_MISC_FOLDER."forms/{$file}.json";
		}
		
		$fArr = explode("/",$file);
		if(count($fArr)>1) {
			$fPath = checkModule($fArr[0]);
			if($fPath) {
				unset($fArr[0]);
				$fsArr[] = dirname($fPath)."/infoviews/".implode("/",$fArr).".json";
				$fsArr[] = dirname($fPath)."/forms/".implode("/",$fArr).".json";
			}
		}
		
		$file=false;
		foreach ($fsArr as $fs) {
			if(file_exists($fs)) {
				$file=$fs;
				break;
			}
		}
		if(!file_exists($file)) {
			return false;
		}

		$formConfig=json_decode(file_get_contents($file),true);

		$formConfig['sourcefile']=$file;
		if(isset($formConfig['singleton']) && $formConfig['singleton']) {
			$formConfig['infoviewkey']=md5(session_id().$file);
		} else {
			$formConfig['infoviewkey']=md5(session_id().time().$file);
		}
		$formConfig['srckey']=$fileName;
		if(!isset($formConfig['dbkey'])) $formConfig['dbkey']="app";

		return $formConfig;
	}

	function printInfoView($formConfig,$dbKey="app",$whereCondition=false,$params=[]) {
		if(!is_array($formConfig)) $formConfig=findInfoView($formConfig);

		if(!is_array($formConfig) || count($formConfig)<=2) {
			trigger_logikserror("Corrupt InfoView defination");
			return false;
		}
    
  	if($params==null) $params=[];
		$formConfig=array_merge($formConfig,$params);
		
		if(!isset($formConfig['infoviewkey'])) $formConfig['infoviewkey']=md5(session_id().time());
		
		if(!isset($formConfig['infoview'])) $formConfig['infoview']=[];
		
		$formConfig['infoviewcode']=md5($_SESSION['SESS_USER_ID'].$formConfig['sourcefile']);
		$formConfig['infoviewuid']=md5($formConfig['sourcefile']);

		$formConfig['dbkey']=$dbKey;

		if(isset($formConfig['dbkey'])) $dbKey = _replace($formConfig['dbkey']);
		else $formConfig['dbkey']=$dbKey;

		if(!isset($formConfig['infoview']['template'])) {
			$formConfig['template']="tabbed";
		} else {
			$formConfig['template']=$formConfig['infoview']['template'];
		}
		
		if(!isset($formConfig['gotolink'])) {
			$formConfig['gotolink']="";
		}
		
		if(!isset($formConfig['config'])) {
			$formConfig['config']=[];
		}
		if(!isset($formConfig['buttons'])) {
			$formConfig['buttons']=[];
		}
		if(!isset($formConfig['secure'])) {
			$formConfig['secure']=true;
		}

		if(strtolower(getConfig("APPS_STATUS"))!="production" && strtolower(getConfig("APPS_STATUS"))!="prod") {
			setConfig("INFOVIEWTABLE_SHOW_DISABLED_TABS","true");
		}

		$fieldGroups=[];
		foreach ($formConfig['fields'] as $fieldKey => $fieldset) {
			if(!isset($fieldset['label'])) $fieldset['label']=_ling($fieldKey);
			if(!isset($fieldset['width'])) $fieldset['width']=6;
			if(!isset($fieldset['group'])) $fieldset['group']="default";
			
			$fieldset['group']=str_replace(" ","_",$fieldset['group']);

			$fieldset['fieldkey']=$fieldKey;

			if(!isset($fieldGroups[$fieldset['group']])) $fieldGroups[$fieldset['group']]=[];

			$formConfig['fields'][$fieldKey]=$fieldset;
			$fieldGroups[$fieldset['group']][]=$fieldset;
		}
		
		if(!isset($formConfig['actions'])) $formConfig['actions']=[];
		
		if(isset($formConfig['gotolink']) && strlen($formConfig['gotolink'])>0) {
			$formConfig['actions']['cancel']=[
								"type"=>"button",
								"label"=>"Go Back",
								"icon"=>"<i class='fa fa-angle-left form-icon right'></i>"
							];
		}
		
		$formData=[];
		if(isset($formConfig['data']) && count($formConfig['data'])>0) {
			$formData=$formConfig['data'];
		}
		
    			$source=$formConfig['source'];
			switch ($source['type']) {
				case 'sql':
					if(isset($formConfig['config']['GUID_LOCK']) && $formConfig['config']['GUID_LOCK']===true) {
						$whereCondition["guid"]=$_SESSION['SESS_GUID'];
					}

					$formConfig['fields'] = array_filter($formConfig['fields'], function($key){
											return ((strpos($key, '__') !== 0) && !(isset($value['nodb']) && $value['nodb']));
									}, ARRAY_FILTER_USE_KEY );
					$sqlCols=array_keys($formConfig['fields']);
					$sqlCols[]="id";

					if(isset($source['cols']) && $source['cols']) {
						if(!is_array($source['cols'])) $source['cols'] = explode(',',$source['cols']);

						$sqlCols = array_merge($source['cols'], $sqlCols);
						$sqlCols = array_unique($source['cols']);
					}
					
					$sql=_db($dbKey)->_selectQ($source['table'],$sqlCols,$whereCondition);
// 					exit($sql->_SQL());
					//$data=$sql->_get();
					//echo $sql->_SQL();printArray([$formConfig['fields'],$whereCondition]);
					
					$res=_dbQuery($sql,$dbKey);
					if($res) {
						$data=_dbData($res,$dbKey);
						_dbFree($res,$dbKey);
						if(isset($data[0])) {
							$formData=$data[0];
							$formConfig['source']['where_auto']=$whereCondition;
						} else {
							$formData=[];
						}
					} else {
						trigger_logikserror(_db($dbKey)->get_error());
					}
					//printArray($data);exit($sql->_SQL());
					
				break;
				case 'php':
					$file=APPROOT.$source['file'];
					if(file_exists($file) && is_file($file)) {
						$formData=include_once($file);
					} else {
						trigger_error("Form Data Source File Not Found");
					}
				break;
				case "plugin":
					if(isset($formConfig['source']['plugin']) && strlen($formConfig['source']['plugin'])>0) {
						$pluginPath = checkModule($formConfig['source']['plugin']);
						if($pluginPath) {
							$pluginPath = dirname($pluginPath);

							$dataFiles = [
									$pluginPath."/data_forms.php",
									$pluginPath."/data.php",
								];

							$finalFile = false;
							foreach($dataFiles as $f) {
								if($finalFile) continue;
								if(file_exists($f)) {
									$finalFile = $f;
								}
							}

							if($finalFile) {
								if(!isset($formConfig['source']['where'])) $formConfig['source']['where'] = [];
								
								$cols = $formConfig['fields'];
								$where = $formConfig['source']['where'];
								foreach($where as $a=>$b) {
									$where[$a] = _replace($b);
								}

								$formConfig['mode'] = "fetch";
								$formData=include($finalFile);
							} else {
								//displayFormMsg("Sorry, Form Data - Plugin Does Not Support Plugin:Data Requirements");
							}
						} else {
							// displayFormMsg("Sorry, Form Data - Plugin Not Found");
						}
					} else {
						// displayFormMsg("Sorry, Form Submit Source Plugin Not Found.");
					}
				break;
			}
		
		$formData=processFormData($formData,$formConfig);
		if(isset($formData['id'])) {
			$_ENV['INFOVIEW-REFID']=$formData['id'];
		} else {
			$_ENV['INFOVIEW-REFID']=0;
		}
		
		$formConfig['data']=$formData;

		$formConfig['mode']="update";
		
		$formKey=$formConfig['infoviewkey'];
		$_SESSION['INFOVIEW'][$formKey]=$formConfig;
		$_ENV['FORMKEY']=$formKey;

		//Loading Form Template
		$templateArr=[
				$formConfig['template'],
				__DIR__."/templates/{$formConfig['template']}.php"
			];
		
		$dcode=$_ENV['INFOVIEW-REFHASH'];
		$dtuid=$formKey;
		
		//printArray($templateArr);return;
		foreach ($templateArr as $f) {
			if(file_exists($f) && is_file($f)) {
				if(isset($formConfig['hooks']) && isset($formConfig['hooks']['preload'])) {
					if(isset($formConfig['hooks']['preload']['modules'])) {
						loadModules($formConfig['hooks']['preload']['modules']);
					}
					if(isset($formConfig['hooks']['preload']['api'])) {
						foreach ($formConfig['hooks']['preload']['api'] as $apiModule) {
							loadModuleLib($apiModule,'api');
						}
					}
					if(isset($formConfig['hooks']['preload']['helpers'])) {
						loadHelpers($formConfig['hooks']['preload']['helpers']);
					}
					if(isset($formConfig['hooks']['preload']['method'])) {
						if(!is_array($formConfig['hooks']['preload']['method'])) $formConfig['hooks']['preload']['method']=explode(",",$formConfig['hooks']['preload']['method']);
						foreach($formConfig['hooks']['preload']['method'] as $m) call_user_func($m,$formConfig);
					}
					if(isset($formConfig['hooks']['preload']['file'])) {
						if(!is_array($formConfig['hooks']['preload']['file'])) $formConfig['hooks']['preload']['file']=explode(",",$formConfig['hooks']['preload']['file']);
						foreach($formConfig['hooks']['preload']['file'] as $m) {
							if(file_exists($m)) include $m;
							elseif(file_exists(APPROOT.$m)) include APPROOT.$m;
						}
					}
				}
				
// 				printArray($formConfig);return;
				include __DIR__."/vendors/autoload.php";
				echo _css(["bootstrap.datetimepicker",'infoview']);
				if(isset($formConfig['infoview']['style']) && strlen($formConfig['infoview']['style'])>0) {
					echo _css(["infoview/{$formConfig['infoview']['style']}",$formConfig['infoview']['style']]);
				}

				if(isset($_SESSION['INFOVIEW'][$_ENV['FORMKEY']]['data'])) {
					if(is_array($_SESSION['INFOVIEW'][$_ENV['FORMKEY']]['data'])) {
						foreach($_SESSION['INFOVIEW'][$_ENV['FORMKEY']]['data'] as $a=>$b) {
							$_REQUEST[$a]=$b;
						}
					}
				}
				
				include $f;
				
				echo _js(["bootstrap.datetimepicker",'infoview']);
				if(isset($formConfig['infoview']['script']) && strlen($formConfig['infoview']['script'])>0) {
					echo _js(["infoview/{$formConfig['infoview']['script']}",$formConfig['infoview']['script']]);
				}
				return true;
			}
		}
		trigger_logikserror("InfoView Template Not Found",null,404);
  	}
	
	function processFormData($formData,$formConfig) {
		$formFields=$formConfig['fields'];
		
		foreach($formFields as $a=>$b) {
			if(!isset($b['type'])) continue;
			
			switch($b['type']) {
				
			}
		}
		
// 		printArray($formData);
// 		printArray($formFields);
		return $formData;
	}
  
  function getInfoViewFieldset($fields,$data=[],$dbKey="app") {
		if(!is_array($fields)) return false;
		//printArray($fields);
		
		$noLabelFields=["widget","ajaxwidget","source","module","static2","header","avatar"];

		$html="";//<fieldset>
		foreach ($fields as $field) {
			if(!isset($field['fieldkey'])) {
				continue;
			}
			if(substr($field['fieldkey'],0,2)=="__") continue;
			
			if(isset($field['infoview']) && !$field['infoview']) {
				continue;
			}
			
			if(isset($field['policy']) && strlen($field['policy'])>0) {
				$allow=checkUserPolicy($field['policy']);
				if(!$allow) continue;
			}
			if(isset($fields['security'])) $field['security']=$fields['security'];
			
			if(!isset($field['label'])) {
				$fieldKey=$field['fieldkey'];
				$field['label']=_ling($fieldKey);
			} else {
				$field['label']=_ling($field['label']);
			}

			if(!isset($field['width'])) $field['width']=6;
			
			if(isset($field['hidden']) && $field['hidden']==true) {
// 				$html.=$field['label'];
// 				$html.="<div class='col-sm-{$field['width']} col-lg-{$field['width']} hidden'>";
			} else {
				
			}

			if(isset($field['noview']) && $field['noview']===true) {
				continue;
			}
			
			$html.="<div class='col-sm-{$field['width']} col-lg-{$field['width']} field-container'>";
			
			if(!isset($field['type'])) $field['type']="text";
			
			if(!in_array($field['type'],$noLabelFields)) {
				$html.="<div class='form-group'>";
				if(substr($field['label'],0,2)!="__") $html.="<label>{$field['label']}";
				
				if(isset($field['required']) && $field['required']==true) {
					$html.="<span class='span-required'>*</span>";
				}
				if(isset($field['tips']) && strlen($field['tips'])>1) {
					$html.="<a href='{$field['tips']}' target=_blank class='field-tips pull-right fa fa-question-circle'></a>";
				}
				if(substr($field['label'],0,2)!="__") $html.="</label>";
				
				$html.=getInfoViewField($field,$data,$dbKey);
				$html.="</div>";
			} else {
				$html.=getInfoViewField($field,$data,$dbKey);
			}
			
			$html.="</div>";
		}
		$html.="";//</fieldset>

		return $html;
	}

	function getInfoViewField($fieldinfo,$data,$dbKey="app") {
		if(isset($fieldinfo['policy']) && strlen($fieldinfo['policy'])>0) {
			$allow=checkUserPolicy($fieldinfo['policy']);
			if(!$allow) return "";
		}
		
		$formKey=$fieldinfo['fieldkey'];
		if(!isset($data[$formKey])) {
			if(isset($fieldinfo['default'])) {
				$data[$formKey]=$fieldinfo['default'];
			} else {
				$data[$formKey]="";
			}
		}
		$fieldinfo["dbkey"] = $dbKey;
		$fieldinfo["data"] = $data;
		if(!isset($fieldinfo['type'])) $fieldinfo['type']="text";
		if(!isset($fieldinfo['label'])) $fieldinfo['label']=_ling($formKey);
		if(!isset($fieldinfo['placeholder'])) $fieldinfo['placeholder']="";

		$html="";

		$class="form-control field-{$fieldinfo['type']} field-{$formKey}";
		$xtraAttributes=[];

		if(isset($fieldinfo['class']) && strlen($fieldinfo['class'])>0) {
			$class.=" ".$fieldinfo['class'];
		}
		
		if(isset($fieldinfo['multiple']) && $fieldinfo['multiple']==true) {
			$class.=" multiple";
			$xtraAttributes[]="multiple";
		}
		
		if(isset($fieldinfo['src']) && strlen($fieldinfo['src'])>0) {
			$xtraAttributes[]="src='{$fieldinfo['src']}'";
		}

		if(!isset($fieldinfo['no-option'])) {
			$fieldinfo['no-option']="Select ".toTitle($formKey);
		}
		$noOption=_ling($fieldinfo['no-option']);

		$xtraAttributes=trim(implode(" ", $xtraAttributes));
		
		$typeArr=explode("@",$fieldinfo['type']);
		$typeS=current($typeArr);
		switch ($typeS) {
			case 'dataMethod': case 'selectAJAX': 
// 				printArray($fieldinfo);
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				break;
			case 'dataSelectorFromTable':
				if(!isset($fieldinfo['groupBy']))	$fieldinfo['groupBy']=false;
				if(!isset($fieldinfo['where'])) $fieldinfo['where']=[];

				foreach ($fieldinfo['where'] as $key => $value) {
					$fieldinfo['where'][$key] = _replace($value);
				}

				$sqlData=_db()->_selectQ($fieldinfo['table'],$fieldinfo['columns'],$fieldinfo['where']);

				if(isset($fieldinfo['multiple']) && $fieldinfo['multiple']==true) {
					$sqlData = $sqlData->_groupby(["group"=>$fieldinfo['groupBy'],"having"=>"value IN ({$data[$formKey]})"]);
				} else {
					$sqlData = $sqlData->_groupby(["group"=>$fieldinfo['groupBy'],"having"=>"value={$data[$formKey]}"]);
				}

				$sqlData=$sqlData->_GET();
				if($sqlData && isset($sqlData[0])) {
					$temp = [];
					foreach ($sqlData as $key => $value) {
						$temp[] = $value['title'];
					}
					$data[$formKey] = implode(",", $temp);
				}
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				break;
			case 'dataSelectorFromUniques':
				if(!isset($fieldinfo['where'])) $fieldinfo['where']=[];

				foreach ($fieldinfo['where'] as $key => $value) {
					$fieldinfo['where'][$key] = _replace($value);
				}

				// $sqlData=_db()->_selectQ($fieldinfo['table'],$fieldinfo['columns'],$fieldinfo['where'])
				// 		->_groupby(["group"=>$fieldinfo['col1'],"having"=>"value={$data[$formKey]}"])->_GET();
						
				$sqlData=_db()->_selectQ($fieldinfo['table'],$fieldinfo['columns'],$fieldinfo['where']);

				if(isset($fieldinfo['multiple']) && $fieldinfo['multiple']==true) {
					$sqlData = $sqlData->_groupby(["group"=>$fieldinfo['col1'],"having"=>"value IN ({$data[$formKey]})"]);
				} else {
					$sqlData = $sqlData->_groupby(["group"=>$fieldinfo['col1'],"having"=>"value={$data[$formKey]}"]);
				}

				$sqlData=$sqlData->_GET();
				if($sqlData && isset($sqlData[0])) {
					$temp = [];
					foreach ($sqlData as $key => $value) {
						$temp[] = $value['title'];
					}
					$data[$formKey] = implode(",", $temp);
				}
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				break;
			case 'dataSelector':
				//$data[$formKey] = _replace($data[$formKey]);
				
				$sqlData=_db()->_selectQ(_dbTable("lists"),"title,value",["groupid"=>$fieldinfo['groupid'],"value"=>$data[$formKey]])->_GET();
				if(isset($sqlData[0])) {
					$data[$formKey]=$sqlData[0]['title'];
				}
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				break;
			case 'dropdown': case 'select': 
				if(isset($fieldinfo['options']) && isset($fieldinfo['options'][$data[$formKey]])) {
					$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$fieldinfo['options'][$data[$formKey]]}</div>";
				} else {
					$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				}
				break;
			case 'radiolist': case 'checkboxlist':  
				if(!isset($fieldinfo['options'])) $fieldinfo['options']=[];
				
				$html.="<div class='fieldlist {$fieldinfo['type']}' $xtraAttributes name='{$formKey}' data-value=\"".$data[$formKey]."\" data-selected=\"".$data[$formKey]."\">";
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				$html.="</div>";
				break;
				
			case 'textarea': case 'longtext': case 'richtextarea': case 'markup':
				$data[$formKey]=stripslashes(str_replace("\\r\\n","",$data[$formKey]));
				$data[$formKey]=stripslashes(str_replace("&amp%3B","&amp;",$data[$formKey]));
				$data[$formKey]=stripslashes(str_replace("%3B",";",$data[$formKey]));
				$html.="<pre class='{$class}' $xtraAttributes name='{$formKey}'>".$data[$formKey]."</pre>";
				break;
			
			case 'color': 
				$html.="<input class='{$class}' $xtraAttributes name='{$formKey}' value=\"".$data[$formKey]."\" placeholder='{$fieldinfo['placeholder']}' type='{$fieldinfo['type']}' disabled>";
				break;
			
			case 'radio': case 'checkbox': 
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				break;
			case 'date': case 'datetime': case 'month': case 'year'://case 'datetime-local': case 'week': 
				if($fieldinfo['type']!="time") {
					if($data[$formKey]==null || strlen($data[$formKey])<=1 || $data[$formKey]==0) $data[$formKey]="";
					else $data[$formKey]=_pDate($data[$formKey],"d/m/Y");
				}
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-calendar pull-left'></i></div>";
				break;
			case 'time':
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-clock-o pull-left'></i></div>";
				break;
			
			case 'currency':
				if(!isset($fieldinfo['currency_type'])) $fieldinfo['currency_type']="mxx";
				
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-money fa-{$fieldinfo['currency_type']} pull-left'></i></div>";
				break;
			case 'creditcard':case 'debitcard':case 'moneycard':
				if(!isset($fieldinfo['card_type'])) $fieldinfo['card_type']="credit-card";
				
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-{$fieldinfo['card_type']} pull-left'></i></div>";
				break;
			case 'email':
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-mail pull-left'></i></div>";
				break;
			case 'tel':case 'phone':
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-phone pull-left'></i></div>";
				break;
			case 'mobile':
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-mobile pull-left'></i></div>";
				break;
			case 'url':
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-globe pull-left'></i></div>";
				break;
			case 'social':case 'brand':
				if(isset($typeArr[1]) && strlen($typeArr[1])>0) {
					$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-{$typeArr[1]} pull-left'></i></div>";
				} else {
					$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				}
				break;
			case 'number':
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				break;
			case 'barcode':
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-barcode pull-left'></i></div>";
				break;
			case 'qrcode': 
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-qrcode pull-left'></i></div>";
				break;
			case 'search': 
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]} <i class='fa fa-search1 pull-left'></i></div>";
				break;
			case 'password':
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>*** <i class='fa fa-keys pull-left'></i></div>";
				break;
				
			case 'text': case 'range': 
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				break;

			case 'file':case 'files':case 'attachment':
				$fieldHash=md5($formKey.time());
				if(isset($fieldinfo['multiple']) && $fieldinfo['multiple']==true) {
					$html.="<div name='{$formKey}' class='file-input file-input-attachment file-field-{$fieldinfo['type']} file-input-multiple' $xtraAttributes><div class='file-preview'>";
				
					$html.="<div class='file-preview-thumbnails' data-fhash='{$fieldHash}' >";
					
					if(isset($data[$formKey]) && strlen($data[$formKey])>0) {
						$mediaArr=explode(",",$data[$formKey]);
						foreach($mediaArr as $m) {
							$media=searchMedia($m);
							if($media) {
								$html.="<div class='file-preview-thumb'>";
								$html.="<i class='fileicon fa ".getFileIcon($media['src'])."'></i>";
								$html.="<span class='filename'>{$media['name']}</span>";
								$html.="<input name='{$formKey}[]' type='hidden' class='hidden' value='{$media['raw']}' >";
								$html.="</div>";
							} else {

							}
						}
					}
					
					$html.="</div>";
					$html.="</div></div>";
				} else {
					$html.="<div name='{$formKey}' class='file-input file-input-attachment file-field-{$fieldinfo['type']}' $xtraAttributes><div class='file-preview'>";
				
					$html.="<div class='file-preview-thumbnails' data-fhash='{$fieldHash}' >";
					
					if(isset($data[$formKey]) && strlen($data[$formKey])>0) {
						$media=searchMedia($data[$formKey]);
						if($media) {
							$html.="<div class='file-preview-thumb'>";
							$html.="<i class='fileicon fa ".getFileIcon($media['src'])."'></i>";
							$html.="<span class='filename'>{$media['name']}</span>";
							$html.="<input name='{$formKey}' type='hidden' class='hidden' value='{$media['raw']}' >";
							$html.="</div>";
						} else {
							
						}
					}
					
					$html.="</div>";
					$html.="</div></div>";
				}
				break;
			case "avatar":
				$fieldHash=md5($formKey.time());
				// $data[$formKey]="http://practice.dev.silkdemo.in/apps/app1/usermedia/profile_images/root.jpg";

				$fieldinfo['multiple']=false;
				if(!isset($fieldinfo['avatar_size'])) $fieldinfo['avatar_size'] = 120;

				$html.="<div name='{$formKey}' class='file-input file-field-{$fieldinfo['type']}' $xtraAttributes><div class='file-preview' style='height:{$fieldinfo['avatar_size']}px;width:{$fieldinfo['avatar_size']}px;    margin: auto;'>";

				$html.="<div class='file-drop-avatar' data-fhash='{$fieldHash}'><div class='file-upload'>";
				if(isset($data[$formKey]) && strlen($data[$formKey])>0) {
					if(substr($data[$formKey],0,7)=="http://" || substr($data[$formKey],0,8)=="https://") {
						$media=[
							"url"=>$data[$formKey]
						];
					} else {
						$media=searchMedia($data[$formKey]);
					}
					if($media) {
						$html.="<img src='{$media['url']}' alt='avatar' />";

					} else {
						$html.="<i class='fa fa-photo fa-3x'></i>";
					}
				} else {
					$html.="<i class='fa fa-photo fa-3x'></i>";
				}
				$html.="</div>";
				$html.="</div>";
				$html.="</div></div>";
				break;
			case 'photo':case 'photos':case 'image':case 'gallery':
				$fieldHash=md5($formKey.time());
				
				if($fieldinfo['type']=="avatar") {
					$fieldinfo['multiple']=false;
				}
				
				if(isset($fieldinfo['multiple']) && $fieldinfo['multiple']==true) {
					$html.="<div name='{$formKey}' class='file-input file-field-{$fieldinfo['type']} file-input-multiple' $xtraAttributes><div class='file-preview'>";
			
					$html.="<div class='file-preview-thumbnails' data-fhash='{$fieldHash}' >";
					
					if(isset($data[$formKey]) && strlen($data[$formKey])>0) {
						$mediaArr=explode(",",$data[$formKey]);
						foreach($mediaArr as $m) {
							$media=searchMedia($m);
							if($media) {
								$html.="<div class='file-preview-thumb'>";
								if($media['ext']=="png" || $media['ext']=="gif" || $media['ext']=="jpg" || $media['ext']=="jpeg") {
									$html.="<img src='{$media['url']}' style='max-height: 200px;' />";
								} else {
									$html.="<i class='fileicon fa ".getFileIcon($media['src'])."'></i>";
								}
								$html.="<input name='{$formKey}[]' type='hidden' class='hidden' value='{$media['raw']}' >";
								$html.="</div>";
							} else {

							}
						}
					}
					
					$html.="</div>";
					$html.="</div></div>";
				} else {
					$html.="<div name='{$formKey}' class='file-input file-field-{$fieldinfo['type']}' $xtraAttributes><div class='file-preview'>";
				
					$html.="<div class='file-preview-thumbnails' data-fhash='{$fieldHash}' >";
					
					if(isset($data[$formKey]) && strlen($data[$formKey])>0) {
						$media=searchMedia($data[$formKey]);
						if($media) {
							$html.="<div class='file-preview-thumb'>";
							if($media['ext']=="png" || $media['ext']=="gif" || $media['ext']=="jpg" || $media['ext']=="jpeg") {
								$html.="<img src='{$media['url']}' />";
							} else {
								$html.="<i class='fileicon fa ".getFileIcon($media['src'])."'></i>";
							}
							$html.="<input name='{$formKey}' type='hidden' class='hidden' value='{$media['raw']}' >";
							$html.="</div>";
						} else {
							
						}
					}
					
					$html.="</div>";
					$html.="</div></div>";
				}
				break;
			
			case 'jsonfield':
				if(!isset($fieldinfo['columns'])) $fieldinfo['columns']="key,value";
				if(!is_array($fieldinfo['columns'])) $fieldinfo['columns']=array_flip(explode(",",$fieldinfo['columns']));
					
				$html.="<div class='table-responsive'>";
				$html.="<table class='table table-condensed jsonField' name='{$formKey}'>";
				if(isset($fieldinfo['noheader']) && $fieldinfo['noheader']) {
					$html.="<thead class='hidden'><tr>";
				} else {
					$html.="<thead><tr>";
				}
				$html.="<th width=25px></th>";
				foreach($fieldinfo['columns'] as $key=>$cols) {
					if(!is_array($cols)) $cols=[];
					if(!isset($cols['label'])) $cols['label']=toTitle($key);
					if(!isset($cols['type'])) $cols['type']="text";
					$html.="<th class='text-center col' name='{$key}' type='{$cols['type']}'>{$cols['label']}</th>";
				}
				$html.="</tr></thead>";
				
				$html.="<tbody>";
				if(isset($data[$formKey]) && strlen($data[$formKey])>2) {
					$jsonData=json_decode(stripslashes($data[$formKey]),true);
					if(!$jsonData) {
						$jsonData=json_decode(stripcslashes($data[$formKey]),true);
					}
					if(!$jsonData) {
						$jsonData=json_decode($data[$formKey],true);
					}
					if($jsonData) {
						foreach($jsonData as $dx) {
							$hx=[];
							$hx[]="<td width=25px><i class='fa fa-bars reorderRow'></i></td>";
							foreach($dx as $dx1=>$dx2) {
								$hx[]="<td><div class='form-control-static field-{$formKey}' name='{$formKey}[{$dx1}][]' $xtraAttributes>{$dx2}</div></td>";
							}
							$html.="<tr>".implode("",$hx)."</tr>";
						}
					} else {
						$html.="<tr><td colspan=10>{$data[$formKey]}</td></tr>";
					}
				}
				$html.="</tbody>";
				
				$html.="</table>";
				//$html.="<input class='{$class}' $xtraAttributes name='{$formKey}' value=\"".$data[$formKey]."\" placeholder='{$fieldinfo['placeholder']}' type='password'>";
				//$html.="<div class='input-group-addon'></div>";
				$html.="</div>";
				break;
				
			case 'widget':
				if(isset($fieldinfo['src'])) {
					$_ENV['INFOVIEW']=$fieldinfo;
					$_ENV['INFOVIEW_DATA']=$data;
					ob_start();
					loadWidget($fieldinfo['src']);
					$html.=ob_get_contents();
					ob_end_clean();
				} else {
					$html.="Widget '{$fieldinfo['src']}' not found.";
				}
				break;
			case 'ajaxwidget':
				if(isset($fieldinfo['src'])) {
					$_ENV['INFOVIEW']=$fieldinfo;
					$_ENV['INFOVIEW_DATA']=$data;
					// ob_start();
					// loadWidget($fieldinfo['src']);
					// $html.=ob_get_contents();
					// ob_end_clean();

					$infoviewWidgetID = "INFOVIEW_WIDGET_".uniqid();
					if(!isset($_SESSION['INFOVIEW_WIDGET'])) $_SESSION['INFOVIEW_WIDGET'] = [];
					$_SESSION['INFOVIEW_WIDGET'][$infoviewWidgetID] = [
							"INFOVIEW"=> $_ENV['INFOVIEW'], 
							"INFOVIEW_DATA"=> $_ENV['INFOVIEW_DATA'], 
							"INFOVIEW_SLUG"=> _slug("a/b/c/d/e/f"),
							"INFOVIEW_REQUEST"=> $_REQUEST,
						];

					ob_start();
					?>
					<div class='modules infoview ajaxwidget widget infoTableGrid' data-widgetid='<?=$infoviewWidgetID?>' data-cmd='ajaxloadWidget'></div>
					<script>
						function ajaxloadWidget(widgetPanel) {
        			var widgetID =  $(widgetPanel).data('widgetid');
        			$(widgetPanel).html("<div class='ajaxloading ajaxloading5'></div>");
							$(widgetPanel).load(_link("popup/webcomps")+"widgetid="+widgetID)
						}
					</script>
					<?php
					$html.=ob_get_contents();
					ob_end_clean();
				} else {
					$html.="Widget '{$fieldinfo['src']}' not found.";
				}
				break;
			case 'module':
				if(isset($fieldinfo['src'])) {
					$_ENV['INFOVIEW']=$fieldinfo;
					$_ENV['INFOVIEW_DATA']=$data;
					$src=explode(".",$fieldinfo['src']);
					if(count($src)>1 && strlen($src[1])>0) {
						ob_start();
						loadModuleComponent($src[0],$src[1]);
						$html.=ob_get_contents();
						ob_end_clean();
					} else {
						ob_start();
						loadModule($fieldinfo['src']);
						$html.=ob_get_contents();
						ob_end_clean();
					}
				} else {
					$html.="Module '{$fieldinfo['src']}' not found.";
				}
				break;
			case 'source':
				if(isset($fieldinfo['src'])) {
					if(file_exists($fieldinfo['src'])) {
						$_ENV['INFOVIEW']=$fieldinfo;
						$_ENV['INFOVIEW_DATA']=$data;
						ob_start();
						include $fieldinfo['src'];
						$html.=ob_get_contents();
						ob_end_clean();
					} elseif(file_exists(APPROOT.$fieldinfo['src'])) {
						ob_start();
						include APPROOT.$fieldinfo['src'];
						$html.=ob_get_contents();
						ob_end_clean();
					} else {
						$html.="Source '".basename($fieldinfo['src'])."' not found.";
					}
				} else {
					$html.="Source '".basename($fieldinfo['src'])."' not defined.";
				}
				break;
			case 'template':
				if(isset($fieldinfo['src'])) {
					if(file_exists($fieldinfo['src'])) {
						ob_start();
						_template($fieldinfo['src']);
						$html.=ob_get_contents();
						ob_end_clean();
					} elseif(file_exists(APPROOT."misc/templates/".$fieldinfo['src'])) {
						ob_start();
						_template(APPROOT."misc/templates/".$fieldinfo['src']);
						$html.=ob_get_contents();
						ob_end_clean();
					} elseif(file_exists(APPROOT.$fieldinfo['src'])) {
						ob_start();
						_template(APPROOT.$fieldinfo['src']);
						$html.=ob_get_contents();
						ob_end_clean();
					} else {
						$html.="Template '".basename($fieldinfo['src'])."' not defined.";
					}
				} else {
					$html.="Template '".basename($fieldinfo['src'])."' not defined.";
				}
				break;
				
			case 'static':
				$content=$fieldinfo['placeholder'];
				if(isset($data[$formKey]) && strlen($data[$formKey])>1) $content=$data[$formKey];
				
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$content}</div>";
				break;
			
			default:
				$html.="<div class='form-control-static field-{$formKey}' $xtraAttributes>{$data[$formKey]}</div>";
				break;
		}
		
		return $html;
	}
  
  function getInfoViewActions($actions=[]) {
    $html="";
		foreach ($actions as $key => $button) {
			if(!isset($button['type'])) $button['type']="button";

			if(isset($button['policy']) && strlen($button['policy'])>0) {
				$allow=checkUserPolicy($button['policy']);
				if(!$allow) continue;
			}
			if(!isset($button['class'])) $button['class']="btn btn-primary";
			if(isset($button['label'])) $label=$button['label'];
			else $label=toTitle(_ling($key));

			if(isset($button['icon']))  $icon=$button['icon'];
			else $icon="";

			if(strlen($icon)>0 && $icon == strip_tags($icon)) {
				$icon="<i class='{$icon}'></i> ";
			}

			$key = _replace($key);

			switch($button['type']) {
				case "dropdown":
					$html.="<div class='btn-group'><button type='button' class='btn btn-primary dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>{$icon}{$label} <span class='caret'></span></button><ul class='dropdown-menu'>";

  				if(isset($button['dropdown'])) {
  					foreach($button['dropdown'] as $key1=>$button1) {
  						if(!isset($button1['type'])) $button1['type']="button";

							if(isset($button1['policy']) && strlen($button1['policy'])>0) {
								$allow=checkUserPolicy($button1['policy']);
								if(!$allow) continue;
							}
							if(!isset($button1['class'])) $button1['class']="btn btn-primary";
							if(!isset($button1['label'])) $button1['label']=toTitle(_ling($key1));

							if(!isset($button1['icon'])) $button1['icon']="";

							if(strlen($button1['icon'])>0 && $button1['icon'] == strip_tags($button1['icon'])) {
								$icon1="<i class='{$button1['icon']}'></i> ";
							}

							$key1 = _replace($key1);

							$html.="<li><a href='#' cmd='{$key1}' class='{$button1['class']}' data-refid='"._replace("#refid#")."'>{$icon1}{$button1['label']}</a></li>";
  					}
  				}

  				$html.="</ul></div>";
				break;
				case "button":
				default:
					$html.="<button type='{$button['type']}' cmd='{$key}' class='{$button['class']}' data-refid='"._replace("#refid#")."'>{$icon}{$label}</button>";
				break;
			}
		}
		return $html;
  }
}
if(!function_exists("searchMedia")) {
	function searchMedia($media) {
		if(isset($_REQUEST['forsite'])) {
			$fs=_fs($_REQUEST['forsite'],[
					"driver"=>"local",
					"basedir"=>ROOT.APPS_FOLDER.$_REQUEST['forsite']."/".APPS_USERDATA_FOLDER
				]);
		} else {
			$fs=_fs();
			$fs->cd(APPS_USERDATA_FOLDER);
		}
		$mediaDir=$fs->pwd();
		
		if(file_exists($media)) {
			$ext=explode(".",$media);
			$mediaName=explode("_",basename($media));
			$mediaName=array_slice($mediaName,1);
			$mediaName=implode("_",$mediaName);
			return [
				"name"=>$mediaName,
				"raw"=>$media,
				"src"=>$media,
				"url"=>getWebPath($media)."?".filemtime($media),
				"size"=>filesize($media)/1024,
				"ext"=>strtolower(end($ext)),
			];
		} elseif(file_exists($mediaDir.$media)) {
			$ext=explode(".",$media);
			$mediaName=explode("_",basename($media));
			$mediaName=array_slice($mediaName,1);
			$mediaName=implode("_",$mediaName);
			return [
				"name"=>$mediaName,
				"raw"=>$media,
				"src"=>$mediaDir.$media,
				"url"=>getWebPath($mediaDir.$media)."?".filemtime($mediaDir.$media),
				"size"=>filesize($mediaDir.$media)/1024,
				"ext"=>strtolower(end($ext)),
			];
		} else {
			return false;
		}
	}
}

if(!function_exists("getFileIcon")) {
	function getFileIcon($file) {
		if($file==null || strlen($file)<=0) return "";
	
		$ext=explode(".",$file);
		$ext=strtolower(end($ext));

		if(strlen($ext)<=0) return "fa-file";

		switch(strtolower($ext)) {
			case "png":case "gif":case "jpg":case "jpeg":case "bmp":
				return "fa-file-image-o";
				break;
			case "mp3":case "ogg":case "wav":case "aiff":case "wma":
				return "fa-file-audio-o";
				break;
			case "mp4":case "mpeg":case "mpg":case "avi":case "mov":case "wmv":
				return "fa-file-video-o";
				break;
			case "doc":case "txt":case "rdf":case "odt":
				return "fa-file-word-o";
				break;
			case "xls":case "ods":
				return "fa-file-excel-o";
				break;
			case "zip":case "tar":case "bz":case "bz2":case "gz":case "rar":case "zip":
				return "fa-file-zip-o";
				break;
			case "pdf":
				return "fa-file-pdf-o";
				break;
			case "php":case "html":case "js":case "css":case "java":case "py":case "c":case "cpp":case "sql":
				return "fa-file-code-o";
				break;
			default:
				return "fa-file";
		}
	}
}
