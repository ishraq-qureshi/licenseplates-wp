<?php
error_reporting(0);
/*if(!$_SERVER['HTTP_REFERER'] || stristr($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) === false)
    return;
*/
if(!(isset($_GET['productId']) and !empty($_GET['productId'])))
    return;
require('includes/application_top.php');
//if (file_exists('includes/configure.php')) {
  /**
   * load the main configure file.
   */
//  include('includes/configure.php');
//}
include('includes/text-funcs.php');
//include('includes/functions/functions_general.php');
//include('includes/functions/encode.php');
// Passed in Parameters
//$country = zen_db_prepare_input($_POST['zone_country_id']);
$productId = (isset($_GET['productId']) ? rawurldecode($_GET['productId']) : "test");
//$productId = zen_db_input($productId);
//$country = zen_db_prepare_input($_POST['zone_country_id']);
//$productId="EURUS0B";
//echo $productId;
// DB params
$table="zen2_lpgen_info";
$imagePath = "images/pngs";
$fontPath = dirname(__FILE__) . "/fonts/truetype/";
//$fontPath = "c:/__cvs/LicensePlates/fonts/";
// FONT SELECT
$temp_visitor_ip=$_SERVER['REMOTE_ADDR'];
$temp_capturedata=json_encode($_GET);
/*if(empty(session_id())){
	session_start();
}*/
$temp_session_id=session_id();
$ctime=time();

/*$tempq= "SELECT * FROM customize_log WHERE products_model='$productId' and sess_id='$temp_session_id' and visitor_ip='$temp_visitor_ip' and customize_date='$ctime' and ptype='decal'";
$qselect = $db->execute($tempq);
if($qselect && count($qselect->fields)>0){
	$temp_capture_data_sql="UPDATE customize_log set `customize_date`='$temp_capturedata' WHERE product_model='$productId' and sess_id='$temp_session_id' and visitor_ip='$temp_visitor_ip' and customize_date='$ctime' and ptype='decal'";
	$qselect = $db->execute($temp_capture_data_sql);

} else{*/
	$temp_capture_data_sql="INSERT INTO customize_log(`sess_id`,`customize_data`, `visitor_ip`,`product_model`,`customize_date`,`ptype`) values ('$temp_session_id','$temp_capturedata','$temp_visitor_ip', '$productId',$ctime, 'decal')";
	$qselect = $db->execute($temp_capture_data_sql);
//}
$tempq= "SELECT * FROM zen2_products WHERE products_model='$productId'";
$qselect = $db->execute($tempq);
$qselect =$qselect->fields;
$font_choose = $qselect["font_choose"];
$where = '';
if($font_choose && isset($_GET['font']) && $_GET['font'] != '') {
	$tempq="SELECT * FROM $table WHERE productId='$productId' AND font1='".$_GET['font']."'";
  $qselect = $db->execute($tempq);
  if(mysql_num_rows($qselect) > 0) $where = " AND font1='".$_GET['font']."'";
}
$unique = "productId";
$q = "select * from $table where $unique='$productId'".$where;
$qselect=$db->execute($q);
/*$r = mysql_fetch_assoc($qselect);
$minChar1=mysql_result($qselect,0,"minChar1");
$maxChar1=mysql_result($qselect,0,"maxChar1");
$xPos1=mysql_result($qselect,0,"xPos1");
$yPos1=mysql_result($qselect,0,"yPos1");
$font1=mysql_result($qselect,0,"font1");
$fontSize1=mysql_result($qselect,0,"fontSize1");
$fontColor1=mysql_result($qselect,0,"fontColor1");
$font1a=mysql_result($qselect,0,"font1a");
$fontSize1a=mysql_result($qselect,0,"fontSize1a");
$fontColor1a=mysql_result($qselect,0,"fontColor1a");
$minChar2=mysql_result($qselect,0,"minChar2");
$maxChar2=mysql_result($qselect,0,"maxChar2");
$xPos2=mysql_result($qselect,0,"xPos2");
$yPos2=mysql_result($qselect,0,"yPos2");
$font2=mysql_result($qselect,0,"font2");
$fontSize2=mysql_result($qselect,0,"fontSize2");
$fontColor2=mysql_result($qselect,0,"fontColor2");
$font2a=mysql_result($qselect,0,"font2a");
$fontSize2a=mysql_result($qselect,0,"fontSize2a");
$fontColor2a=mysql_result($qselect,0,"fontColor2a");
//1 = "use images for fonts" 0 = "use true type fonts"
$type = mysql_result($qselect,0,"fontType");
$qselect=$db->Execute($q);*/
$qselect=$qselect->fields;
/*echo '<pre>';
echo $q;
print_r($qselect);
print_r($r);
echo '</pre>';*/
$minChar1=$qselect['minChar1'];
$maxChar1=$qselect['maxChar1'];
$xPos1=$qselect['xPos1'];
$yPos1=$qselect['yPos1'];
$font1=$qselect['font1'];
$fontSize1=$qselect['fontSize1'];
$fontColor1=$qselect['fontColor1'];
$font1a=$qselect['font1a'];
$fontSize1a=$qselect['fontSize1a'];
$fontColor1a=$qselect['fontColor1a'];
$minChar2=$qselect['minChar2'];
$maxChar2=$qselect['maxChar2'];
$xPos2=$qselect['xPos2'];
$yPos2=$qselect['yPos2'];
$font2=$qselect['font2'];
$fontSize2=$qselect['fontSize2'];
$fontColor2=$qselect['fontColor2'];
$font2a=$qselect['font2a'];
$fontSize2a=$qselect['fontSize2a'];
$fontColor2a=$qselect['fontColor2a'];
if($_GET['text2font'] > 0)$fontSize2=$_GET['text2font'];
if($_GET['text1font'] > 0)$fontSize1=$_GET['text1font'];
if($_GET['fontc'] !='0')$fontColor1=$fontColor2='#000000';//$_GET['fontc'];
if ("" == $font1a) $font1a = $font1;
if ("" == $font2)  $font2  = $font1;
if ("" == $font2a) $font2a = $font2;
if($font_choose && empty($where) && isset($_GET['font']) && !empty($_GET['font']) && file_exists(strtolower($fontPath . $_GET['font'] . ".ttf"))) {
    $font1 = $font1a = $font2 = $font2a = $_GET['font'];
}
//mysql_close();
$textangle = "0";
// Build Image Path
$prod_blank = strtolower("$imagePath/blank$productId" . ".png");
if(strpos($_GET['text1'],'@')){
$prod_blank = strtolower("$imagePath/blank$productId" . "2.png");
} else if(strpos($_GET['text1'],'#')){
$prod_blank = strtolower("$imagePath/blank$productId" . "3.png");
} else if(strpos($_GET['text1'],'$')){
$prod_blank = strtolower("$imagePath/blank$productId" . "4.png");
} else if(strpos($_GET['text1'],'%')){
$prod_blank = strtolower("$imagePath/blank$productId" . "5.png");
} else if(strpos($_GET['text1'],'^')){
$prod_blank = strtolower("$imagePath/blank$productId" . "6.png");
} else if(!isset($_GET['text1'])){
$prod_blank = strtolower("$imagePath/blank$productId" . ".png");
}
if(isset($_GET['img']) && $_GET['img']!='')
$prod_blank = "$imagePath/".$_GET['img'];
$_GET['text1']=str_replace("@"," ",$_GET['text1']);
$_GET['text1']=str_replace("#"," ",$_GET['text1']);
$_GET['text1']=str_replace("$"," ",$_GET['text1']);
$_GET['text1']=str_replace("%"," ",$_GET['text1']);
$_GET['text1']=str_replace("^"," ",$_GET['text1']);
// create pic
$prod_blank = imagecreatefrompng($prod_blank);
//die(imagesx($pic).' | '.imagesy($pic));
/* r2j */
$pic = imagecreatetruecolor(imagesx($prod_blank), imagesy($prod_blank));
imagealphablending($pic,true);
imagesavealpha($pic, true);

imagecopy($pic, $prod_blank, 0, 0, 0, 0,imagesx($prod_blank), imagesy($prod_blank));

//imagecopymerge_alpha($pic, $prod_blank, 0, 0, 0, 0, imagesx($prod_blank), imagesy($prod_blank),100);

$array1 = array('\'', '"', '%', '&', '(', ')', '/', '=', '<', '>');
$array2 = array('&#39', '&#34', '&#37', '&#38', '&#40', '&#41', '&#47', '&#61', '&#60', '&#62');
if(!empty($_GET['text1']))
$_GET['text1'] = str_replace($array2, $array1, $_GET['text1']);
if(!empty($_GET['text2']))
$_GET['text2'] = str_replace($array2, $array1, $_GET['text2']);
$text1 = trim(getTextRequest('text1'));
$text2 = trim(getTextRequest('text2'));
$img1 = imagecreatefrompng(substr($_GET['mainimage'],1,(strlen($_GET['mainimage'])-1)));
$img2 = imagecreatefrompng(substr($_GET['batchimage'],1,(strlen($_GET['batchimage'])-1)));
$imgflag3=false;
$imgflag4=false;
$imgflag5=false;
$imgflag6=false;
$imgflag7=false;
$imgflag8=false;
$imgflag9=false;
$img3='';
$img4='';
$img5='';
$img6='';
$img7='';
$img8='';
$img9='';

if(!empty($_GET['batchimage3']) && $_GET['batchimage3']!='undefined') $imgflag3=true;
if(!empty($_GET['batchimage4']) && $_GET['batchimage4']!='undefined') $imgflag4=true;
if(!empty($_GET['batchimage5']) && $_GET['batchimage5']!='undefined') $imgflag5=true;
if(!empty($_GET['batchimage6']) && $_GET['batchimage6']!='undefined') $imgflag6=true;
if(!empty($_GET['batchimage7']) && $_GET['batchimage7']!='undefined') $imgflag7=true;
if(!empty($_GET['batchimage8']) && $_GET['batchimage8']!='undefined') $imgflag8=true;
if(!empty($_GET['batchimage9']) && $_GET['batchimage9']!='undefined') $imgflag9=true;
if($imgflag3)$img3 = imagecreatefrompng(substr($_GET['batchimage3'],1,(strlen($_GET['batchimage3'])-1)));
if($imgflag4)$img4 = imagecreatefrompng(substr($_GET['batchimage4'],1,(strlen($_GET['batchimage4'])-1)));
if($imgflag5)$img5 = imagecreatefrompng(substr($_GET['batchimage5'],1,(strlen($_GET['batchimage5'])-1)));
if($imgflag6)$img6 = imagecreatefrompng(substr($_GET['batchimage6'],1,(strlen($_GET['batchimage6'])-1)));
if($imgflag7)$img7 = imagecreatefrompng(substr($_GET['batchimage7'],1,(strlen($_GET['batchimage7'])-1)));
if($imgflag8)$img8 = imagecreatefrompng(substr($_GET['batchimage8'],1,(strlen($_GET['batchimage8'])-1)));
if($imgflag9)$img9 = imagecreatefrompng(substr($_GET['batchimage9'],1,(strlen($_GET['batchimage9'])-1)));
$img1x1=$_GET['d1left']-$_GET['mainleft']+100;
$img1y1=$_GET['d1top']-$_GET['mainright']-69;
$img2x1=$_GET['d2left']-$_GET['mainleft']+100;
$img2y1=$_GET['d2top']-$_GET['mainright']-69;
$img3x1=$_GET['d3left']-$_GET['mainleft']+150;
$img3y1=$_GET['d3top']-$_GET['mainright']-69;
$img4x1=$_GET['d4left']-$_GET['mainleft']+200;
$img4y1=$_GET['d4top']-$_GET['mainright']-69;
$img5x1=$_GET['d5left']-$_GET['mainleft']+250;
$img5y1=$_GET['d5top']-$_GET['mainright']-69;
$img6x1=$_GET['d6left']-$_GET['mainleft']+300;
$img6y1=$_GET['d6top']-$_GET['mainright']-69;
$img7x1=$_GET['d7left']-$_GET['mainleft']+350;
$img7y1=$_GET['d7top']-$_GET['mainright']-69;
$img8x1=$_GET['d8left']-$_GET['mainleft']+400;
$img8y1=$_GET['d8top']-$_GET['mainright']-69;
$img9x1=$_GET['d9left']-$_GET['mainleft']+450;
$img9y1=$_GET['d9top']-$_GET['mainright']-69;
if($_GET['screenwidth']>=1600){
	if(isset($_GET['d1top']) && ($_GET['d1top']<=680 ))$img1y1=-1600;
	if(isset($_GET['d2top']) && ($_GET['d1top']<=734 ))$img2y1=-2000;
	if(isset($_GET['d3top']) && ($_GET['d1top']<=680 ))$img3y1=-1600;
	if(isset($_GET['d4top']) && ($_GET['d1top']<=680 ))$img4y1=-1600;
	if(isset($_GET['d5top']) && ($_GET['d1top']<=680 ))$img5y1=-1600;
	if(isset($_GET['d6top']) && ($_GET['d1top']<=680 ))$img6y1=-1600;
	if(isset($_GET['d7top']) && ($_GET['d1top']<=680 ))$img7y1=-1600;
	if(isset($_GET['d8top']) && ($_GET['d1top']<=680 ))$img8y1=-1600;
	if(isset($_GET['d9top']) && ($_GET['d1top']<=680 ))$img9y1=-1600;
}
if($type == 0){
    if ( isset($fontColor1) )
        $fontColor1 = imagecolorallocate($pic, hexdec(substr($fontColor1, 0, 2)), hexdec(substr($fontColor1, 2, 2)), hexdec(substr($fontColor1, 4, 2)));
    if ( isset($fontColor1a) )
        $fontColor1a = imagecolorallocate($pic, hexdec(substr($fontColor1a, 0, 2)), hexdec(substr($fontColor1a, 2, 2)), hexdec(substr($fontColor1a, 4, 2)));
    if ( isset($fontColor2) )
        $fontColor2 = imagecolorallocate($pic, hexdec(substr($fontColor2, 0, 2)), hexdec(substr($fontColor2, 2, 2)), hexdec(substr($fontColor2, 4, 2)));
    if ( isset($fontColor2a) )
        $fontColor2a = imagecolorallocate($pic, hexdec(substr($fontColor2a, 0, 2)), hexdec(substr($fontColor2a, 2, 2)), hexdec(substr($fontColor2a, 4, 2)));
    if ( isset($text1) && $text1 != ''){
        //$text =strtoupper($text1);
        $text = $text1;
        if ( strlen($text1 ) <= $minChar1 ){ // use large font
            createImg( $font1a, $fontColor1a, $fontSize1, $xPos1+$_GET['right'], $yPos1+$_GET['top'], $text,$defaultarray,'','',$img3,$img4,$img5,$img6,$img7,$img8,$img9,$img1x1,$img1y1,$img2x1,$img2y1,$img3x1,$img3y1,$img4x1,$img4y1,$img5x1,$img5y1,$img6x1,$img6y1,$img7x1,$img7y1,$img8x1,$img8y1,$img9x1,$img9y1,false);
        } else { // use small font
            createImg( $font1, $fontColor1,  $fontSize1, $xPos1+$_GET['right'], $yPos1+$_GET['top'], $text,$defaultarray,'','',$img3,$img4,$img5,$img6,$img7,$img8,$img9,$img1x1,$img1y1,$img2x1,$img2y1,$img3x1,$img3y1,$img4x1,$img4y1,$img5x1,$img5y1,$img6x1,$img6y1,$img7x1,$img7y1,$img8x1,$img8y1,$img9x1,$img9y1,false);
        }
    }
    if ( isset($text2) && $text2 != ''){ 
        //$text = strtoupper($text2);
        $text = $text2;
        if ( strlen($text2 ) <= $minChar2 ){ // use large font
           createImg( $font2a, $fontColor1a, $fontSize2 , $xPos2+3+$_GET['righttwo'], $yPos2+$_GET['toptwo'], $text,$defaultarray,'','',$img1x1,$img1y1,$img2x1,$img2y1,$img3x1,$img3y1,$img4x1,$img4y1,$img5x1,$img5y1,$img6x1,$img6y1,$img7x1,$img7y1,$img8x1,$img8y1,$img9x1,$img9y1,false);
        } else { // use small font
           createImg( $font2, $fontColor1, $fontSize2, $xPos2+3+$_GET['righttwo'], $yPos2+$_GET['toptwo'], $text,$defaultarray,'','',$img1x1,$img1y1,$img2x1,$img2y1,$img3x1,$img3y1,$img4x1,$img4y1,$img5x1,$img5y1,$img6x1,$img6y1,$img7x1,$img7y1,$img8x1,$img8y1,$img9x1,$img9y1,false);
        }
    }
} else {}
 $tempwidth=55;
 if($_SERVER['REMOTE_ADDR']=='157.119.88.122'){
	//echo '<pre>';print_r($_GET);exit;
}

if(strtolower($_GET['browser'])=='chrome'){
$tempwidth=0;
	if($_GET['screenwidth']>=1600){
		$img1x1=$img1x1-159;
		$img2x1=$img2x1-103;
		$img3x1=$img3x1-103;
		$img4x1=$img4x1-103;
		$img5x1=$img5x1-103;
		$img6x1=$img6x1-103;
		$img7x1=$img7x1-103;
		$img8x1=$img8x1-103;
		$img9x1=$img9x1-103;
	}else if($_GET['screenwidth']>1247){
		$img1x1=$img1x1-144;
		$img2x1=$img2x1-89;
		$img3x1=$img3x1-89;
		$img4x1=$img4x1-89;
		$img5x1=$img5x1-89;
		$img6x1=$img6x1-89;
		$img7x1=$img7x1-89;
		$img8x1=$img8x1-89;
		$img9x1=$img9x1-89;
	}
	else {//if($_GET['screenwidth']>1024)
		$img1x1=$img1x1-158;
		$img2x1=$img2x1-5;
	}
}else if(strtolower($_GET['browser'])=='undefined'){
	if($_GET['screenwidth']>1247){
			$img1x1=$img1x1-144;
			$img2x1=$img2x1-144;
			$img3x1=$img3x1-89;
			$img4x1=$img4x1-89;
			$img5x1=$img5x1-89;
			$img6x1=$img6x1-89;
			$img7x1=$img7x1-89;
			$img8x1=$img8x1-89;
			$img9x1=$img9x1-89;
	}else {//if($_GET['screenwidth']>1024)
			$img1x1=$img1x1-100;
			//$img2x1=$img2x1-5;
	}
}else{
	if($_GET['screenwidth']>1247){
		/*$img1x1=$img1x1-144;
		$img2x1=$img2x1-89;
		$img3x1=$img3x1-89;
		$img4x1=$img4x1-89;
		$img5x1=$img5x1-89;
		$img6x1=$img6x1-89;
		$img7x1=$img7x1-89;
		$img8x1=$img8x1-89;
		$img9x1=$img9x1-89;*/
	}else {//if($_GET['screenwidth']>1024)
		$img1x1=$img1x1-100;
		//$img2x1=$img2x1-5;
	}

}
if($_GET['decal']=='true'){
//$img1x1=130;
//$img1y1=71;
imagealphablending($pic,true);
imagesavealpha($pic, false);

imagecopymerge_alpha($pic, $img1, $img1x1+5, $img1y1+5, 0, 0, imagesx($img1), imagesy($img1),100);
imagealphablending($pic,true);
imagesavealpha($pic, false);
imagecopymerge_alpha($pic, $img2, $img2x1+$tempwidth, $img2y1+5, 0, 0, imagesx($img2), imagesy($img2),100);
imagealphablending($pic,true);
imagesavealpha($pic, false);
if($imgflag3) imagecopymerge_alpha($pic, $img3, $img3x1+$tempwidth, $img3y1+5, 0, 0, imagesx($img3), imagesy($img3),100);
imagealphablending($pic,true);
imagesavealpha($pic, false);
if($imgflag4)imagecopymerge_alpha($pic, $img4, $img4x1+$tempwidth, $img4y1+5, 0, 0, imagesx($img4), imagesy($img4),100);
imagealphablending($pic,true);
imagesavealpha($pic, false);
if($imgflag5)imagecopymerge_alpha($pic, $img5, $img5x1+$tempwidth, $img5y1+5, 0, 0, imagesx($img5), imagesy($img5),100);
imagealphablending($pic,true);
imagesavealpha($pic, false);
if($imgflag6)imagecopymerge_alpha($pic, $img6, $img6x1+$tempwidth, $img6y1+5, 0, 0, imagesx($img6), imagesy($img6),100);
imagealphablending($pic,true);
imagesavealpha($pic, false);
if($imgflag7)imagecopymerge_alpha($pic, $img7, $img7x1+$tempwidth, $img7y1+5, 0, 0, imagesx($img7), imagesy($img7),100);
imagealphablending($pic,true);
imagesavealpha($pic, false);
if($imgflag8)imagecopymerge_alpha($pic, $img8, $img8x1+$tempwidth, $img8y1+5, 0, 0, imagesx($img8), imagesy($img8),100);
imagealphablending($pic,true);
imagesavealpha($pic, false);
if($imgflag9)imagecopymerge_alpha($pic, $img9, $img9x1+$tempwidth, $img9y1+5, 0, 0, imagesx($img9), imagesy($img9),100);
}
//if($_SERVER['REMOTE_ADDR']!='125.63.111.79'){
header("content-type: image/png");
//}
//session_start();osCsid
//$sessionid = session_id();
//session_register("t");
session_start();
$sess_id_temp=session_id();
if(!@$sess_id)
setcookie ("sess_id",$sess_id_temp,time()+3600);
//session_start();
//$sessionid=session_id();
//$sessionid="cplate";
//$text1 = ereg_replace(" ","",$text1);
//$text2 = ereg_replace(" ","",$text2);
$fon = "";
if($font_choose  && isset($_GET['font']) && $_GET['font'] != '') {
    $fon = $_GET['font'];
}
$textfinal=$productId."_".$text1."_".$text2."_".$fon;
$textfinal=base64_new_encode($textfinal);
$orderpath = "./orders_temp/$textfinal.png";
if($text1||$text2)
imagepng($pic,$orderpath);
if($_SERVER['REMOTE_ADDR']=='122.180.38.236'){
$pic=$prod_blank;
}
imagepng($pic);
createImg('', '', '', '', '', '','','','','','','','', '', '', '', '', '','','','','','','','', '', '', '', '', '','','','',false, true);
imagedestroy($pic);
function createImg($font, $fontColor, $fontSize, $xPos, $yPos, $text,$defaultarray,$img1='',$img2='',$img3='',$img4='',$img5='',$img6='',$img7='',$img8='',$img9='',$img1x1='',$img1y1='',$img2x1='',$img2y1='',$img3x1='',$img3y1='',$img4x1='',$img4y1='',$img5x1='',$img5y1='',$img6x1='',$img6y1='',$img7x1='',$img7y1='',$img8x1='',$img8y1='',$img9x1='',$img9y1='',$regenerate=true, $filewrite=false) {
/*if($_SERVER['REMOTE_ADDR']=='125.63.111.79'){
echo $font.', '.$fontColor.', '. $fontSize.', '. $xPos.', '. $yPos.', '. $text.', '.$defaultarray.', '.$img1.', '.$img2.', '.$img3.', '.$img4.', '.$img5.', '.$img6.', '.$img7.', '.$img8.', '.$img9.', '.$img1x1.', '.$img1y1.', '.$img2x1.', '.$img2y1.', '.$img3x1.', '.$img3y1.', '.$img4x1.', '.$img4y1.', '.$img5x1.', '.$img5y1.', '.$img6x1.', '.$img6y1.', '.$img7x1.', '.$img7y1.', '.$img8x1.', '.$img8y1.', '.$img9x1.', '.$img9y1.', '.$regenerate.', '. $filewrite.', ';exit;
}*/
    $textangle = "0";
    global $pic;
    global $fontPath;
//	echo $img1x1.' - '.$img1y1. ' ;'.$img2x1.' - '.$img2y1. ' ;'.$img3x1.' - '.$img3y1. ' ;'.$img4x1.' - '.$img4y1. ' ;'.$img5x1.' - '.$img5y1. ' ;'.$img6x1.' - '.$img6y1. ' ;'.$img7x1.' - '.$img7y1. ' ;'.$img8x1.' - '.$img8y1. ' ;'.$img9x1.' - '.$img9y1. ' ;' ;exit;
if(!$filewrite){
    // Build Font Path
    $font = strtolower($fontPath . $font . ".ttf");
//    print "font = $font";
    //Modify the font size when upgrading to gd2 lib
    $fontSize = ($fontSize/96) * 72;
    // Calc X/Y Positions
//    echo "FONT = $font";

	//echo floor($fontSize).'++'. $textangle.'--'. $font .'=='. $text;
//exit;

    list($pos_blx, $pos_bly, $pos_brx, $pos_bry, $pos_trx, $pos_try, $pos_tlx, $pos_tly) = imagettfbbox(floor($fontSize), $textangle, $font, $text);
    $textwidth = $pos_brx - $pos_blx;
    $textheight = $pos_bly - $pos_tly;
    $start_x = ($xPos - $textwidth/2.0 );
    //$start_y = ($yPos - $textheight);
    $start_y = $yPos;
//die($pic.' | '.ceil($fontSize).' | '.$textangle.' | '.$start_x.' | '.$start_y.' | '.$fontColor.' | '.$font.' | '.$text);
    // write text to Image
//	echo $pic.' | '.floor($fontSize).' | '.$textangle.' | '.$start_x.' | '.$start_y.' | '.$fontColor.' | '.$font.' | '."$text";exit;
    imagettftext($pic, floor($fontSize), $textangle, $start_x, $start_y, $fontColor, $font, "$text");
if($img1!='' && $regenerate && ($img1x1 !='70' && $img1y1!='106')){	
	imagecopymerge_alpha($pic, $img1, $img1x1+5, $img1y1+5, 0, 0, imagesx($img1), imagesy($img1),100);
}
if($img2!='' && $regenerate && ($img2x1 !='70' && $img2y1!='156')){
//	imagecopymerge_alpha($pic, $img2, $img2x1+55, $img2y1+5, 0, 0, imagesx($img2), imagesy($img2),100);
}
if($img3!='' && $regenerate && ($img3x1 !='70' && $img3y1!='177')){
	imagecopymerge_alpha($pic, $img3, $img3x1+55, $img3y1+5, 0, 0, imagesx($img3), imagesy($img3),100);
}
if($img4!='' && $regenerate && ($img4x1 !='764' && $img4y1!='227')){
	imagecopymerge_alpha($pic, $img4, $img4x1+55, $img4y1+5, 0, 0, imagesx($img4), imagesy($img4),100);
}
if($img5!='' && $regenerate && ($img5x1 !='764' && $img5y1!='227')){
	imagecopymerge_alpha($pic, $img5, $img5x1+55, $img5y1+5, 0, 0, imagesx($img5), imagesy($img5),100);
}
if($img6!='' && $regenerate){
	imagecopymerge_alpha($pic, $img6, $img6x1+55, $img6y1+5, 0, 0, imagesx($img6), imagesy($img6),100);
}
if($img7!='' && $regenerate){
	imagecopymerge_alpha($pic, $img7, $img7x1+55, $img7y1+5, 0, 0, imagesx($img7), imagesy($img7),100);
}
if($img8!='' && $regenerate){
	imagecopymerge_alpha($pic, $img8, $img8x1+55, $img8y1+5, 0, 0, imagesx($img8), imagesy($img8),100);
}
if($img9!='' && $regenerate){
	imagecopymerge_alpha($pic, $img9, $img9x1+55, $img9y1+5, 0, 0, imagesx($img9), imagesy($img9),100);
}	
	$imgname=rand('111111111','999999999');
	$orderpath = "./images/pngs/test/new_".$imgname.".png";
	imagepng($pic,$orderpath); 
	$products = $_SESSION['cart']->get_products();
	//$_SESSION['image_'.count($products)]=$orderpath;  
	$custom_img_text = $_GET['text1'].$_GET['text2'].$_GET['font'];
	$_SESSION['image_'.$_GET['productId'].$custom_img_text]=$orderpath;  
}else{
if($img2!='' && $regenerate && ($img2x1 !='70' && $img2y1!='156')){
	imagecopymerge_alpha($pic, $img2, $img2x1+55, $img2y1+5, 0, 0, imagesx($img2), imagesy($img2),100);
}
	$imgname=rand('111111111','999999999');
	$orderpath = "./images/pngs/test/new_".$imgname.".png";
	imagepng($pic,$orderpath); 
	$products = $_SESSION['cart']->get_products();
	$custom_img_text = $_GET['text1'].$_GET['text2'].$_GET['font'];
	//$_SESSION['image_'.count($products)]=$orderpath;  
	$_SESSION['image_'.$_GET['productId'].$custom_img_text] = $orderpath;  
	
}
}
function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
    if(!isset($pct)){
        return false;
    }
    $pct /= 100;
    // Get image width and height
    $w = imagesx( $src_im );
    $h = imagesy( $src_im );
    // Turn alpha blending off
    imagealphablending( $src_im, true );
    imagesavealpha($src_im, false);

	// Find the most opaque pixel in the image (the one with the smallest alpha value)
    $minalpha = 127;
    for( $x = 0; $x < $w; $x++ )
    for( $y = 0; $y < $h; $y++ ){
        $alpha = ( imagecolorat( $src_im, $x, $y ) >> 24 ) & 0xFF;
        if( $alpha < $minalpha ){
            $minalpha = $alpha;
        }
    }
    //loop through image pixels and modify alpha for each
    for( $x = 0; $x < $w; $x++ ){
        for( $y = 0; $y < $h; $y++ ){
            //get current alpha value (represents the TANSPARENCY!)
            $colorxy = imagecolorat( $src_im, $x, $y );
            $alpha = ( $colorxy >> 24 ) & 0xFF;
            //calculate new alpha
            if( $minalpha !== 127 ){
                $alpha = 127 + 127 * $pct * ( $alpha - 127 ) / ( 127 - $minalpha );
            } else {
                $alpha += 127 * $pct;
            }
            //get the color index with new alpha
            $alphacolorxy = imagecolorallocatealpha( $src_im, ( $colorxy >> 16 ) & 0xFF, ( $colorxy >> 8 ) & 0xFF, $colorxy & 0xFF, $alpha );
            //set pixel with the new color + opacity
            if( !imagesetpixel( $src_im, $x, $y, $alphacolorxy ) ){
                return false;
            }
        }
    }
    // The image copy
    imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
}
?>