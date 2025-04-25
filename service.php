<?php
if(!defined('ROOT')) exit('No direct script access allowed');

handleActionMethodCalls();

function _service_component() {
    if(!isset($_REQUEST['widgetid'])) {
        echo "Sorry, WidgetID not defined";
        exit();
    }
    if(!isset($_SESSION['INFOVIEW_WIDGET'][$_REQUEST['widgetid']])) {
        echo "Sorry, WidgetID not found";
        exit();
    }
    $widgetInfo = $_SESSION['INFOVIEW_WIDGET'][$_REQUEST['widgetid']];
    
    $_ENV['INFOVIEW']=$widgetInfo['INFOVIEW'];
    $_ENV['INFOVIEW_DATA']=$widgetInfo['INFOVIEW_DATA'];
    $_ENV['INFOVIEW_SLUG']=$widgetInfo['INFOVIEW_SLUG'];
    $_ENV['INFOVIEW_REQUEST']=$widgetInfo['INFOVIEW_REQUEST'];

    $_REQUEST = array_merge($_REQUEST, $_ENV['INFOVIEW_REQUEST']);

    ob_start();
    loadWidget($_ENV['INFOVIEW']['src']);
    $html=ob_get_contents();
    ob_end_clean();

    echo $html;
}
?>