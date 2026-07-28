<?php

error_reporting( E_ALL );

header('content-type: application/json; charset=utf-8');
header("access-control-allow-origin: *");
$_SESSION['captured-data']=json_encode($_GET);
$text_symbol_include = 0;

$_SESSION['mon'] = $_GET;
$_GET['text1']=str_replace("%20"," ",$_GET['text1']);
$_GET['text2']=str_replace("%20"," ",$_GET['text2']);
if(strpos($_GET['text1'],'@'))$text_symbol_include = 1;
else if(strpos($_GET['text1'],'#'))$text_symbol_include = 1;
else if(strpos($_GET['text1'],'$'))$text_symbol_include = 1;
else if(strpos($_GET['text1'],'%'))$text_symbol_include = 1;
else if(strpos($_GET['text1'],'^'))$text_symbol_include = 1;
else if(strpos($_GET['text1'],'<'))$text_symbol_include = 1;
else if(strpos($_GET['text1'],'>'))$text_symbol_include = 1;


//if($text_symbol_include==1 || !isset($_GET['text1']))
//require('includes/application_top.php');

require('lpgenI_symbol.php');

/*else if($_GET['catPath']=='1065_27')
require('lpgenI_symbol.php');
else
require('lpgenI_deacal.php');*/
?>
