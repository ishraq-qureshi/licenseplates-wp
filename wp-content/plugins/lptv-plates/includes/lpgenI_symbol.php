<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// include wp config
require_once "../../../../wp-load.php";

if (! (isset($_GET['productId']) and ! empty($_GET['productId']))) {
	return;
}

// try to debug empty image


$text11 = json_encode($_GET);
$text22 = json_encode($_GET);
$text223 = json_decode($text11);

// replace doubled backslashes
$text1 = str_replace('\\\\', '\\', $text1);
$text2 = str_replace('\\\\', '\\', $text2);

// use wp_unslash to remove WordPress-added slashes from $_GET
$text1 = isset($_GET['text1']) ? wp_unslash($_GET['text1']) : '';
// $text2 = isset($_GET['text2']) ? strtolower(wp_unslash($_GET['text2'])) : '';

$text2 = isset($_GET['text2']) ? wp_unslash($_GET['text2']) : '';

// $text1 = str_replace('|', ',', $text1);
$text1 = str_replace(
    ['TVGTSYMBOL', 'TVLTSYMBOL'],
    ['>', '<'],
    $text1
);
$text1 = str_replace(
    ['TVPIPESYMBOL'],
    ['|'],
    $text1
);

$text2 = str_replace(
    ['TVGTSYMBOL', 'TVLTSYMBOL'],
    ['>', '<'],
    $text2
);
$text2 = str_replace(
    ['TVPIPESYMBOL'],
    ['|'],
    $text2
);

// replace hyphen with dot for text1

// $text1 = str_replace('-', '.', $text1);
// $text1 = str_replace('|', '.', $text1);
$font = isset($_GET['font']) ? wp_unslash($_GET['font']) : '';
$font_choose = isset($_GET['font_choose']) ? wp_unslash($_GET['font_choose']) : '';


function base64_new_encode($data)
{
	if (is_string($data)) {
		$newData = base64_encode($data);

		return (str_replace('/', '-', $newData));
	}
}

// Passed in Parameters
$productId = (isset($_GET['productId']) ? rawurldecode($_GET['productId']) : "test");

$table     = "zen2_lpgen_info";
$imagePath = "images/pngs";
$fontPath  = dirname(__FILE__) . "/fonts/truetype/";

$table_prefix  = 'wpdb';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$temp_visitor_ip  = $_SERVER['REMOTE_ADDR'];
$temp_capturedata = json_encode($_GET);
/*if(empty(session_id())){
	session_start();
}*/
$temp_session_id = session_id();
$ctime           = time();

$query = "SELECT * FROM " . $table_prefix . "_postmeta AS meta
JOIN " . $table_prefix . "_postmeta AS meta2 ON meta.post_id=meta2.post_id
 WHERE meta.meta_key='_plate_template_id' and meta.meta_value='$productId'";
 
try {
	$rows = $mysqli->query($query);
} catch (Exception $e) {
	var_dump($e->getMessage());
}
// compile it to one row
$r = [];
while ($data = $rows->fetch_assoc()) {
	foreach ($data as $key => $val) {
		$nKey       = $data['meta_key'];
		$nKey       = str_replace('_plate_', '', $nKey);
		$r[$nKey] = $val;
	}
}

$minChar1 = $r["minChar1"];
$maxChar1 = $r["maxChar1"];
$xPos1    = $r["xPos1"];
$yPos1    = $r["yPos1"];

if ($_SERVER['REMOTE_ADDR'] == '157.119.89.89') {
	//echo $q;  $xPos1-=40;exit;
}
//$r = $_GET;
$params = $r;
if ($_GET['font1']) {
	//$params = $_GET;
}

$slug = isset($params['original_uri']) ? $params['original_uri'] : '';
// do not convert to lowercase for twoline products
if (stripos($slug, 'twoline') === false && 
    stripos($slug, 'two-line') === false &&
    stripos($slug, 'his-hers') === false &&
    stripos($slug, 'great-britian-euro-e-6198') === false &&
    stripos($slug, 'great-britian-euro-e-6199') === false &&
    stripos($slug, 'britain-uk-square-license-plate-issued-between-1903-1972-for-your-auto-truck-lorry') === false &&
    stripos($slug, 'britain-uk-rear-square-license-plate-issued-between-1973-and-august-2001-for-your-truck-lorry-embossed-with-your-custom-number') === false &&
    stripos($slug, 'britainuk-euro-front-2039') === false &&
    stripos($slug, 'great-britain-euro-eec-rexlex-yellow-motorcycle-license-plate-issued-from-january-1-2007-to-present-embossed-with-your-custom-number') === false &&
    stripos($slug, 'two-line-custom-laser-black-license-plate-mirror-gold-text-personalized-just-for-you-or-for-a-great-gift') === false &&
    stripos($slug, 'great-britain-euro-eec-motorcycle-6804') === false 
	) {
    $text2 = strtolower($text2);
}

$font1       = $params["font1"];
$fontSize1   = $params["fontSize1"];
$fontColor1  = $params["fontColor1"];
$font1a      = $params["font1a"];
$fontSize1a  = $params["fontSize1a"];
$fontColor1a = $params["fontColor1a"];
$minChar2    = $params["minChar2"];
$maxChar2    = $params["maxChar2"];
$xPos2       = $params["xPos2"];
$yPos2       = $params["yPos2"];
$font2       = $params["font2"];
$fontSize2   = $params["fontSize2"];
$fontColor2  = $params["fontColor2"];
$font2a      = $params["font2a"];
$fontSize2a  = $params["fontSize2a"];
$fontColor2a = $params["fontColor2a"];


//1 = "use images for fonts" 0 = "use true type fonts"
$type = $params["fontType"];
if (strtolower($font1) == 'nautical') {
    $type = 1; // image based
} else {
    $type = 0; // normal TTF
}






if ("" == $font1a) {
	$font1a = $font1;
}
if ("" == $font2) {
	$font2 = $font1;
}
if ("" == $font2a) {
	$font2a = $font2;
}

if ($font_choose && empty($where) && isset($_GET['font']) && ! empty($_GET['font']) && file_exists(strtolower($fontPath . $_GET['font'] . ".ttf"))) {
	$font1 = $font1a = $font2 = $font2a = $_GET['font'];
}

// These fonts have no hyphen glyph at U+002D; their period glyph (U+002E) renders as the visual dash bar.
// Map '-' to '.' only for these fonts so the correct dash visual appears.
$dotDashFonts = ['ag_newyorknew', 'ag_army', 'ag_chinanew', 'ag_montana75', 'ag_ohio35', 'ag_ohio_69', 'ag_pr67'];
$activeFonts  = array_map('strtolower', array_filter([$font1, $font1a, $font2, $font2a]));

if (array_intersect($dotDashFonts, $activeFonts)) {
    $text1 = str_replace('-', ',', $text1);
    $text1 = str_replace('|', ' ', $text1);
}

mysqli_close($mysqli);

$textangle = "0";
// Build Image Path
//$pic = strtolower("$imagePath/blank$productId" . ".png");
$lowerProductId = strtolower($productId);
$pic = __DIR__ . "/$imagePath/blank$lowerProductId" . ".png";

// check if image exists, if not, try uppercase
if (!file_exists($pic)) {
	$upperProductId = strtoupper($productId);
	$pic = __DIR__ . "/$imagePath/blank$upperProductId" . ".png";
}

try {
	// create pic
	$pic = imagecreatefrompng($pic);
	//die(imagesx($pic).' | '.imagesy($pic));
} catch (Exception $e) {
	var_dump($e->getMessage());
	die;
}


/* r2j */
$array1 = array('\'', '"', '%', '&', '(', ')', '/', '=', '<', '>');
$array2 = array('&#39', '&#34', '&#37', '&#38', '&#40', '&#41', '&#47', '&#61', '&#60', '&#62');

/*$_GET['text1'] = str_replace($array2, $array1, $_GET['text1']);
$_GET['text2'] = str_replace($array2, $array1, $_GET['text2']);
$text1 = _htmlkarakter(getTextRequest('text1'));
$text2 = getTextRequest('text2');*/
//$text1 = $_GET['text1'];
//$text2 = $_GET['text2'];

//echo '<pre>';
//print_r($_GET);
//exit();


// use true type fonts
if ($_GET['screenwidth'] == 1600) {
	if (isset($_GET['d1top']) && $_GET['d1top'] == 678) {
		$img1y1 = -1600;
	}
	if (isset($_GET['d2top']) && $_GET['d2top'] == 678) {
		$img2y1 = -1600;
	}
	if (isset($_GET['d3top']) && $_GET['d3top'] == 678) {
		$img3y1 = -1600;
	}
	if (isset($_GET['d4top']) && $_GET['d4top'] == 678) {
		$img4y1 = -1600;
	}
	if (isset($_GET['d5top']) && $_GET['d5top'] == 678) {
		$img5y1 = -1600;
	}
	if (isset($_GET['d6top']) && $_GET['d6top'] == 678) {
		$img6y1 = -1600;
	}
	if (isset($_GET['d7top']) && $_GET['d7top'] == 678) {
		$img7y1 = -1600;
	}
	if (isset($_GET['d8top']) && $_GET['d8top'] == 678) {
		$img8y1 = -1600;
	}
	if (isset($_GET['d9top']) && $_GET['d9top'] == 678) {
		$img9y1 = -1600;
	}
}
// --- Decal character resolution -------------------------------------------------
// Clicking a decal icon on the product page inserts a placeholder character into
// text1/text2 at the cursor (':' for emission-test decal, ';' for safety-test decal,
// plus a per-product character for the state decal, e.g. Berlin's `_plate_statedecal`
// meta is "d1.png;>" - filename + character). The product's custom font draws a
// single-color glyph for these characters; here we resolve which real colored decal
// image should be composited in their place instead of relying on the font glyph.
$decalsBase      = dirname(__DIR__) . '/decals/';
$themeDecalsBase = dirname(__DIR__, 3) . '/themes/lptv/images/decals/';

$emissionDecalImages = [
	'2017' => $decalsBase . 'decal2.png',
	'2018' => $decalsBase . 'decal11.png',
	'2019' => $decalsBase . 'decal4.png',
	'2020' => $decalsBase . 'decal5.png',
	'2021' => $decalsBase . 'decal1.png',
	'2022' => $themeDecalsBase . 'e_decal_2022.png',
	'2023' => $themeDecalsBase . 'e_decal_2023.png',
	'2024' => $themeDecalsBase . 'e_decal_2024.png',
	'2025' => $themeDecalsBase . 'e_decal_2025.png',
	'2026' => $themeDecalsBase . 'e_decal_2026.png',
];
$safetyDecalImages = [
	'2017' => $decalsBase . 'decal7.png',
	'2018' => $decalsBase . 'decal12.png',
	'2019' => $decalsBase . 'decal9.png',
	'2020' => $decalsBase . 'decal10.png',
	'2021' => $decalsBase . 'decal6.png',
	'2022' => $themeDecalsBase . 's_decal_2022.png',
	'2023' => $themeDecalsBase . 's_decal_2023.png',
	'2024' => $themeDecalsBase . 's_decal_2024.png',
	'2025' => $themeDecalsBase . 's_decal_2025.png',
	'2026' => $themeDecalsBase . 's_decal_2026.png',
];

$edecalYear = isset($_GET['edecal_year']) ? preg_replace('/\D/', '', $_GET['edecal_year']) : '';
$sdecalYear = isset($_GET['sdecal_year']) ? preg_replace('/\D/', '', $_GET['sdecal_year']) : '';

// character => [image path, target width, target height]
$decalCharImages = [];
if ($edecalYear && isset($emissionDecalImages[$edecalYear]) && file_exists($emissionDecalImages[$edecalYear])) {
	$decalCharImages[':'] = [$emissionDecalImages[$edecalYear], 24, 27];
}
if ($sdecalYear && isset($safetyDecalImages[$sdecalYear]) && file_exists($safetyDecalImages[$sdecalYear])) {
	$decalCharImages[';'] = [$safetyDecalImages[$sdecalYear], 26, 27];
}

// state decal: product meta stores "filename.png;character"
$stateDecalRaw = isset($r['statedecal']) ? trim($r['statedecal']) : '';
if ($stateDecalRaw !== '') {
	$stateDecalParts = explode(';', $stateDecalRaw);
	$stateDecalFile  = trim($stateDecalParts[0]);
	$stateDecalChar  = isset($stateDecalParts[1]) ? trim($stateDecalParts[1]) : '';
	if ($stateDecalChar !== '' && $stateDecalFile !== '') {
		$stateDecalPath = $decalsBase . $stateDecalFile;
		if (file_exists($stateDecalPath)) {
			$decalCharImages[$stateDecalChar] = [$stateDecalPath, 27, 27];
		}
	}
}

/**
 * Composite a decal PNG (resizing if needed, preserving alpha transparency) onto $dst.
 */
function lptv_composite_decal($dst, $src_path, $dst_x, $dst_y, $target_w, $target_h)
{
	$src = @imagecreatefrompng($src_path);
	if (!$src) {
		return;
	}
	imagesavealpha($src, true);
	imagealphablending($src, false);

	$src_w = imagesx($src);
	$src_h = imagesy($src);

	if ($src_w === $target_w && $src_h === $target_h) {
		$resized = $src;
	} else {
		$resized = imagecreatetruecolor($target_w, $target_h);
		imagesavealpha($resized, true);
		imagealphablending($resized, false);
		$transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
		imagefill($resized, 0, 0, $transparent);
		imagecopyresampled($resized, $src, 0, 0, 0, 0, $target_w, $target_h, $src_w, $src_h);
		imagedestroy($src);
	}

	imagealphablending($dst, true);
	imagecopy($dst, $resized, (int) $dst_x, (int) $dst_y, 0, 0, $target_w, $target_h);
	imagedestroy($resized);
}

if ($type == 0) {

	// Allocate Font Colors
	if (isset($fontColor1)) {
		$fontColor1 = imagecolorallocate($pic, hexdec(substr($fontColor1, 0, 2)), hexdec(substr($fontColor1, 2, 2)), hexdec(substr($fontColor1, 4, 2)));
	}
	if (isset($fontColor1a)) {
		$fontColor1a = imagecolorallocate($pic, hexdec(substr($fontColor1a, 0, 2)), hexdec(substr($fontColor1a, 2, 2)), hexdec(substr($fontColor1a, 4, 2)));
	}
	if (isset($fontColor2)) {
		$fontColor2 = imagecolorallocate($pic, hexdec(substr($fontColor2, 0, 2)), hexdec(substr($fontColor2, 2, 2)), hexdec(substr($fontColor2, 4, 2)));
	}
	if (isset($fontColor2a)) {
		$fontColor2a = imagecolorallocate($pic, hexdec(substr($fontColor2a, 0, 2)), hexdec(substr($fontColor2a, 2, 2)), hexdec(substr($fontColor2a, 4, 2)));
	}

	// $text1 = trim(getTextRequest('text1'));
	if (isset($text1) && $text1 != '') {
		//$text =strtoupper($text1);
		$text = $text1;
		if (strlen($text1) <= $minChar1) { // use large font
			createImg($font1a, $fontColor1a, $fontSize1a, $xPos1, $yPos1, $text);
		} else { // use small font
			createImg($font1, $fontColor1, $fontSize1, $xPos1, $yPos1, $text);
		}
	}

	// $text2 = trim(getTextRequest('text2'));
	if (isset($text2) && $text2 != '') {
		//$text = strtoupper($text2);
		$text = $text2;
		if (strlen($text2) <= $minChar2) { // use large font

			createImg($font2a, $fontColor2a, $fontSize2a, $xPos2, $yPos2, $text);
		} else { // use small font
			createImg($font2, $fontColor2, $fontSize2, $xPos2, $yPos2, $text);
		}
	}
} else { //use images for fonts

	$space = 10;
	if (isset($text1)) {
		$len = strlen($text1);

		$xpos = $xPos1;
		$ypos = $yPos1;
		for ($i = 0; $i < $len; $i++) {
			$tmpVal       = "";
			$tmpVal       = substr($text1, $i, 1);
			$src_path     = "$imagePath/$font1/$tmpVal.png";
			if (!file_exists($src_path)) {
				continue; // skip unsupported characters
			}
			$pngimage_src = @ImageCreateFromPNG($src_path);
			if (!$pngimage_src) {
				continue;
			}
			if ($i == 0) {
				//Calc image length and position
				$picLen = ($len * imagesx($pngimage_src)) + (($len - 1) * $space);
				$xpos   = $xpos - $picLen / 2;
			} else {
				$xpos += (imagesx($pngimage_src) + $space);
			}
			//int ImageCopy (resource dst_im, resource src_im, int dst_x, int dst_y, int src_x, int src_y, int src_w, int src_h)
			//Copy a part of src_im onto dst_im starting at the x,y coordinates src_x, src_y with a width of src_w and a height of src_h. The portion defined will be copied onto the x,y coordinates, dst_x and dst_y.
			@ImageCopy($pic, $pngimage_src, $xpos, $ypos, 0, 0, imagesx($pngimage_src), imagesy($pngimage_src));
		}
	}
}
header("content-type: image/png");

//session_start();osCsid
//$sessionid = session_id();
//session_register("t");
// session_start();
// $sess_id_temp = session_id();
// if (! @$sess_id) {
// 	setcookie("sess_id", $sess_id_temp, time() + 3600);
// }

//session_start();
//$sessionid=session_id();
//$sessionid="cplate";
//$text1 = ereg_replace(" ","",$text1);
//$text2 = ereg_replace(" ","",$text2);
//$fon = "";
if ($font_choose && isset($_GET['font']) && $_GET['font'] != '') {
	$fon = $_GET['font'];
}
//echo '<pre>';
//print_r($_GET);


$textfinal = $productId . "_" . $text1 . "_" . $text2 . "_" . $fon;
$textfinal = base64_new_encode($textfinal);

// $orderpath                                                    = "./orders_temp/$textfinal.png";
// $custom_img_text                                              = $_GET['text1'] . $_GET['text2'] . $_GET['font'];
// $custom_img_text                                              = $text1 . $text2 . $_GET['font'];
// $_SESSION['custom_images_text']                               = json_encode($custom_img_text);
// $_SESSION['image_' . $_GET['productId'] . $custom_img_text] = $orderpath;
// //exit();
// if ($text1 || $text2) {
// 	imagepng($pic, $orderpath);
// }
imagepng($pic);
//imagedestroy($pic);

function createImg($font, $fontColor, $fontSize, $xPos, $yPos, $text)
{
	global $pic;
	global $fontPath;
	global $decalCharImages;
	$textangle = "0";
	//echo $text; exit();
	// Build Font Path

	// Build Font Path
		// Build Font Path
	$font = $fontPath . $font . ".ttf";
	
	// check if font file exists
	if (!file_exists($font)) {

		// try strtolower case
		$font = strtolower($font);
		if (!file_exists($font)) {
			error_log("lpgenI_symbol.php: Font file not found: $font");
			return 'font not found'.$font; // skip rendering this text
		}

 	}

	//    print "font = $font";
	//Modify the font size when upgrading to gd2 lib
	$fontSize = ($fontSize / 96) * 72;

	// Calc X/Y Positions
	//    echo "FONT = $font";

	$bbox = imagettfbbox(floor($fontSize), $textangle, $font, $text);
	if ($bbox === false) {
		error_log("lpgenI_symbol.php: imagettfbbox failed for font: $font, text: $text");
		return;
	}

	list($pos_blx, $pos_bly, $pos_brx, $pos_bry, $pos_trx, $pos_try, $pos_tlx, $pos_tly) = $bbox;
	$textwidth  = $pos_brx - $pos_blx;
	$textheight = $pos_bly - $pos_tly;
	$start_x    = ($xPos - $textwidth / 2.0);
	//$start_y = ($yPos - $textheight);
	$start_y = $yPos;

	// Does this string contain a decal placeholder character (inserted by clicking a
	// decal icon on the product page)? If not, render exactly as before (unchanged
	// behavior/kerning for the vast majority of non-decal products). If so, walk the
	// string character-by-character so we can composite the real colored decal image
	// in place of that character's (single-color) font glyph.
	$hasDecalChar = false;
	if (!empty($decalCharImages)) {
		foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
			if (isset($decalCharImages[$ch])) {
				$hasDecalChar = true;
				break;
			}
		}
	}

	if (!$hasDecalChar) {
		//die($pic.' | '.ceil($fontSize).' | '.$textangle.' | '.$start_x.' | '.$start_y.' | '.$fontColor.' | '.$font.' | '.$text);
		// write text to Image
		$result = imagettftext($pic, floor($fontSize), $textangle, $start_x, $start_y, $fontColor, $font, "$text");
		if ($result === false) {
			error_log("lpgenI_symbol.php: imagettftext failed for font: $font, text: $text");
		}
	} else {
		$penX = $start_x;
		foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
			if (isset($decalCharImages[$ch])) {
				list($decalPath, $decalW, $decalH) = $decalCharImages[$ch];
				$decalY = $start_y - $decalH + floor($fontSize * 0.05);
				lptv_composite_decal($pic, $decalPath, $penX, $decalY, $decalW, $decalH);
				$penX += $decalW;
				continue;
			}
			$charBbox = imagettfbbox(floor($fontSize), $textangle, $font, $ch);
			if ($charBbox === false) {
				continue;
			}
			$charWidth = $charBbox[2] - $charBbox[0];
			$result    = imagettftext($pic, floor($fontSize), $textangle, $penX, $start_y, $fontColor, $font, $ch);
			if ($result === false) {
				error_log("lpgenI_symbol.php: imagettftext failed for font: $font, char: $ch");
			}
			$penX += $charWidth;
		}
	}

	$imgname = rand('111111111', '999999999');

	$orderpath = "./images/pngs/test/new_" . $imgname . ".png";

	imagepng($pic, $orderpath);


	//$_SESSION['image_'.count($products)]=$orderpath;

	$_SESSION['image_' . $_GET['productId']] = $orderpath;
	//    imagettftext($pic, floor($fontSize), $textangle, $start_x, $start_y, $fontColor, $font, "$textwidth");
}
// function createImg($font, $fontColor, $fontSize, $xPos, $yPos, $text)
// {
//     global $pic;
//     global $fontPath;
//     $textangle = "0";
//     //echo $text; exit();
//     // Build Font Path

// 	// Build Font Path
// 		// Build Font Path

    
//     $font = $fontPath . $font . ".ttf";
    
//     if (!file_exists($font)) {
//         // try strtolower case
//         $font = strtolower($font);
//         if (!file_exists($font)) {
//             error_log("lpgenI_symbol.php: Font file not found: $font");
//             return 'font not found'.$font;
//         }
//     }
//     //    print "font = $font";
//     //Modify the font size when upgrading to gd2 lib
//     $fontSize = ($fontSize / 96) * 72;
//     // Calc X/Y Positions
// 	//    echo "FONT = $font";

//     $bbox = imagettfbbox(floor($fontSize), $textangle, $font, $text);
//     if ($bbox === false) {
//         error_log("lpgenI_symbol.php: imagettfbbox failed for font: $font, text: $text");
//         return;
//     }

//     list($pos_blx, $pos_bly, $pos_brx, $pos_bry, $pos_trx, $pos_try, $pos_tlx, $pos_tly) = $bbox;
//     $textwidth  = $pos_brx - $pos_blx;
//     $textheight = $pos_bly - $pos_tly;
//     $start_x    = ($xPos - $textwidth / 2.0);
//     $start_y = $yPos;
//     //die($pic.' | '.ceil($fontSize).' | '.$textangle.' | '.$start_x.' | '.$start_y.' | '.$fontColor.' | '.$font.' | '.$text);
// 	// write text to Image
    


    
//     $segments = explode('-', $text);
    
//     if (count($segments) == 1) {
        
//         $result = imagettftext($pic, floor($fontSize), $textangle, $start_x, $start_y, $fontColor, $font, "$text");
//         if ($result === false) {
//             error_log("lpgenI_symbol.php: imagettftext failed for font: $font, text: $text");
//         }
//     } else {
        
//         $totalWidth = 0;
//         $partWidths = [];
//         $hyphenWidth = floor($fontSize * 0.25);
//         $hyphenGap   = floor($fontSize * 0.08);
//         $hyphenCount = count($segments) - 1;

//         foreach ($segments as $seg) {
//             $b = $seg !== '' ? imagettfbbox(floor($fontSize), 0, $font, $seg) : false;
//             $w = $b ? ($b[2] - $b[0]) : 0;
//             $partWidths[] = $w;
//             $totalWidth += $w;
//         }
//         $totalWidth += $hyphenCount * ($hyphenWidth + $hyphenGap * 2);

//         $currentX = $xPos - $totalWidth / 2.0;

//         foreach ($segments as $i => $seg) {
//             if ($seg !== '') {
//                 imagettftext($pic, floor($fontSize), 0, (int)$currentX, $start_y, $fontColor, $font, $seg);
//                 $currentX += $partWidths[$i];
//             }
//             if ($i < $hyphenCount) {
//                 $currentX += $hyphenGap;
//                 $hyphenH = max(3, floor($fontSize * 0.08));
//                 $hyphenY = $start_y - floor($fontSize * 0.40);
//                 imagefilledrectangle($pic, (int)$currentX, (int)$hyphenY, (int)($currentX + $hyphenWidth), (int)($hyphenY + $hyphenH), $fontColor);
//                 $currentX += $hyphenWidth + $hyphenGap;
//             }
//         }
//     }
    


    
//     $imgname = rand('111111111', '999999999');

//     $orderpath = "./images/pngs/test/new_" . $imgname . ".png";

//     imagepng($pic, $orderpath);
//     //$_SESSION['image_'.count($products)]=$orderpath;
//     $_SESSION['image_' . $_GET['productId']] = $orderpath;
//     //    imagettftext($pic, floor($fontSize), $textangle, $start_x, $start_y, $fontColor, $font, "$textwidth");

// }

function _htmlkarakter($string)
{
	$string = str_replace(array('&#37', '&#34'), array(
		'%',
		'"'
	), htmlspecialchars_decode($string, ENT_NOQUOTES));

	return $string;
}

function getTextRequest($var)
{
	$result = '';
	if (isset($_POST[$var])) {
		$result = $_POST[$var];
	} elseif (isset($_GET[$var])) {
		$result = $_GET[$var];
	}
	if (get_magic_quotes_gpc()) {
		$result = stripslashes($result);
	}

	return trim($result);
}

function getTextDbArray($row, $var)
{
	$result = $row[$var];
	if (get_magic_quotes_runtime()) {
		$result = stripslashes($result);
	}

	return trim($result);
}
