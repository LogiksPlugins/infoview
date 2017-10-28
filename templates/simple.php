<?php
if(!defined('ROOT')) exit('No direct script access allowed');

echo '<div class="formbox infoviewBox"><div class="formbox-content infoview-content">';
echo "<div class='row'>";
echo getInfoViewFieldset($formConfig['fields'],$formData,$formConfig['dbkey']);
echo "</div>";
echo '<hr class="hr-normal">';
echo '<div class="form-actions form-actions-padding"><div class="text-right">';
echo getInfoViewActions($formConfig['buttons']);
echo '</div></div>';
echo '</div></div>';
?>