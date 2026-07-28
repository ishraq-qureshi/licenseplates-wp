<?php
//asdbg_break("localhost", 9000);
include('includes/configure.php');
// Passed in Parameters
$productId = (isset($productId) ? rawurldecode($productId) : "test");
//$productId="EURUS0B";

//$homePath = "http://www.licenseplates.tv";
//$homePath = "http://sigma.in.olmisoft.com/kuz/plates/www";
$homePath = 'https://www.calplates.com';
//$homePath = "http://dev1.gepcom.com/ag2";
//$homePath = "http://localhost/lpgen";
//CGI call to lpgen
$cgiURL = "$homePath/lpgenI.php";

// DB params
//$server="apple.webcast1.com";
$server="localhost";
$user="pasha";
$pass="";
$database="licenseplateszencart";
$table="zen2_lpgen_info";

/*if (@mysql_ping() === false) {
    mysql_connect($server,$user,$pass);
    mysql_select_db($database);
    $disconnectOnShutDown = true;
} else {
    $disconnectOnShutDown = false;
}*/

$unique = "productId";

$q = "SELECT $table. *, zen2_products.products_custom
    FROM zen2_products, $table
    WHERE $table.productId = zen2_products.products_model AND $table.productId = '$productId'";

//$q="select * from $table where $unique='$productId'";

$qselect=$db->Execute($q);
$qselect=$qselect->fields;

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

if ($disconnectOnShutDown ) {
    mysql_close();
}

$numLine = 0;
if ( isset($xPos1) ){
    $numLine++;
}
if ( isset($xPos2) && ($xPos2 != 0) ){
    $numLine++;
}

//Compute text aligment for applet
if ( isset($yPos1) && ($yPos1 == $yPos2) ){
    $textAlign = "HORIZ";
}
else{
    $textAlign = "VERT";
}

$legendURL = "$homePath/images/legends/legend$font1.png";
//echo "<br><br>|||$productId|||<br><br>";


   $product_type = '';
   switch ($products_custom)
   {
     case "3":
       $product_type = 'chain';
       break;

     case "2":
       $product_type = 'frame';
       break;

     case "1":
     default:
       $product_type = 'plate';
       break;
   }
?>

<SCRIPT language="javascript">

  function PutImage2()
  {
var isOpera = !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;
    // Opera 8.0+ (UA detection to detect Blink/v8-powered Opera)
var isFirefox = typeof InstallTrigger !== 'undefined';   // Firefox 1.0+
var isSafari = Object.prototype.toString.call(window.HTMLElement).indexOf('Constructor') > 0;
    // At least Safari 3+: "[object HTMLElementConstructor]"
var isChrome = !!window.chrome && !isOpera;              // Chrome 1+
var isIE = /*@cc_on!@*/false || !!document.documentMode; // At least IE6
screenwidth=$(window).width();
	if(isChrome)
	var browser='Chrome';
	if(isFirefox)
	var browser='Firefox';
    var text1 = $('#tfield1').val();
    var text2 = $('#tfield2').val();
    var pid = $('#pid').val();
    var url = "<?php echo $homePath;?>/lpgenI.php";
    var font = <?php if($prod_qwe['font_choose'] == '1') echo "$('#fontc').val();"; else echo '""';?>;
    var pars = "?productId=" + pid + "&text1=" + encodeURIComponent(text1) + "&text2=" + encodeURIComponent(text2) + "&font=" + font+'&browser='+browser+'&screenwidth='+screenwidth;

    var image_elem = document.getElementById('kuzim');
    image_elem.src = url + pars;
  }

  function PutImage1(decal)
  {
var isOpera = !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;
    // Opera 8.0+ (UA detection to detect Blink/v8-powered Opera)
var isFirefox = typeof InstallTrigger !== 'undefined';   // Firefox 1.0+
var isSafari = Object.prototype.toString.call(window.HTMLElement).indexOf('Constructor') > 0;
    // At least Safari 3+: "[object HTMLElementConstructor]"
var isChrome = !!window.chrome && !isOpera;              // Chrome 1+
var isIE = /*@cc_on!@*/false || !!document.documentMode; // At least IE6
screenwidth=$(window).width();
 // alert(isFirefox);alert(isChrome);return false;
 /* 	var name	=	document.getElementById('tfield1').value;
	var fontc = document.getElementById('fontc').value;*/
	var p = $( "#kuzim" );
var position = p.position();
	m1=position.left;
	m2=position.top;

	img1=$('#draggableHelper img').attr('src');
	img2=$('#draggableHelper1 img').attr('src');
	dl1=$('#draggableHelper').css('left');
	dt1=$('#draggableHelper').css('top');
	dl2=$('#draggableHelper1').css('left');
	dt2=$('#draggableHelper1').css('top');

	img3=$('#draggableHelper3 img').attr('src');
	dl3=$('#draggableHelper3').css('left');
	dt3=$('#draggableHelper3').css('top');
		
	img4=$('#draggableHelper4 img').attr('src');
	dl4=$('#draggableHelper4').css('left');
	dt4=$('#draggableHelper4').css('top');
		
	img5=$('#draggableHelper5 img').attr('src');
	dl5=$('#draggableHelper5').css('left');
	dt5=$('#draggableHelper5').css('top');
		
	img6=$('#draggableHelper6 img').attr('src');
	dl6=$('#draggableHelper6').css('left');
	dt6=$('#draggableHelper6').css('top');
		
	img7=$('#draggableHelper7 img').attr('src');
	dl7=$('#draggableHelper7').css('left');
	dt7=$('#draggableHelper7').css('top');
		
	img8=$('#draggableHelper8 img').attr('src');
	dl8=$('#draggableHelper8').css('left');
	dt8=$('#draggableHelper8').css('top');
		
	img9=$('#draggableHelper9 img').attr('src');
	dl9=$('#draggableHelper9').css('left');
	dt9=$('#draggableHelper9').css('top');
		
	
	if(isChrome)
	var browser='Chrome';
	if(isFirefox)
	var browser='Firefox';

	/*alert( "left: " + m1 + ", top: " + m2 +'----'+$('#kuzim').css('width'));
	alert(dl1+'----'+dt1);
	alert(dl2+'----'+dt2);
	return false;*/
  	var name	=	$('#tfield1').val();
	var fontc = $('#fontc').val();
  	//alert(name);//return false;
	/*var value=0;
	var topval=0;
	var valuetwo=0;
	var topvaltwo=0;
	var e = document.getElementById("choosetext");
	var strtype = e.options[e.selectedIndex].value;
	var rightvalue = document.getElementById('right').value;
	var leftvalue = document.getElementById('left').value;
	var rightvaluetwo = document.getElementById('righttwo').value;
	var leftvaluetwo = document.getElementById('lefttwo').value;
	var topvalue = document.getElementById('top').value;
	var bottomvalue = document.getElementById('bottom').value;
	var topvaluetwo = document.getElementById('toptwo').value;
	var bottomvaluetwo = document.getElementById('bottomtwo').value;
	var fontc = document.getElementById('fontc').value;
	
	//alert(strtype);
	if(name!='' && strtype==1){
		if(name=='right'){			
			rightvalue=parseInt(rightvalue)+5;
			document.getElementById('right').value=rightvalue;
		}
		if(name=='left'){			
			leftvalue=parseInt(leftvalue)+5;
			document.getElementById('left').value=leftvalue;
		}		
		if(name=='top'){			
			topvalue=parseInt(topvalue)-5;
			document.getElementById('top').value=topvalue;
		}
		if(name=='bottom'){			
			bottomvalue=parseInt(bottomvalue)-5;
			document.getElementById('bottom').value=bottomvalue;
		}		
		//alert(document.getElementById('left').value);
	}
	value=parseInt(rightvalue)-parseInt(leftvalue);
	topval=parseInt(topvalue)-parseInt(bottomvalue);//alert(topvalue+"==="+bottomvalue+"===="+topval);
	if(name!='' && strtype==2){
		if(name=='right'){			
			rightvaluetwo=parseInt(rightvaluetwo)+5;
			document.getElementById('righttwo').value=rightvaluetwo;
		}
		if(name=='left'){			
			leftvaluetwo=parseInt(leftvaluetwo)+5;
			document.getElementById('lefttwo').value=leftvaluetwo;
		}
		if(name=='top'){			
			topvaluetwo=parseInt(topvaluetwo)-5;
			document.getElementById('toptwo').value=topvaluetwo;
		}
		if(name=='bottom'){			
			bottomvaluetwo=parseInt(bottomvaluetwo)-5;
			document.getElementById('bottomtwo').value=bottomvaluetwo;
		}
		
	}
	valuetwo=parseInt(rightvaluetwo)-parseInt(leftvaluetwo);
	topvaltwo=parseInt(topvaluetwo)-parseInt(bottomvaluetwo);
    var text1 = document.forms[1].tfield1.value;
	var text2 = document.forms[1].tfield2.value;
    var text1font = document.getElementById('fontsize1').value;
	var text2font = document.getElementById('fontsize2').value;
	if(text1font=='Text1 Font Size')text1font=0;
	if(text2font=='Text2 Font Size')text2font=0;*/
//	var text2 = document.forms[1].tfield2.value;
 	var text2	=	$('#tfield2').val();
	var url = "<?php echo $homePath;?>/lpgenI.php";
//    var pid = document.forms[1].pid.value;
    var pid = $('#pid').val();
	/*var image = document.getElementById('uplod').value;
	if(image!=''){
		var imgsplit=image.split('images/pngs/');
	}
    var url = "lpgenI.php";*/
    var font = <?php if($prod_qwe['font_choose'] == '1') echo "document.cart_quantity.font_field.value;"; else echo '""';?>;
	var catPath = '<?php echo $_GET['cPath'];?>';
	
	if(decal){
//alert(decal);
//alert('decal');
var decIcon = "";
var selectedYear = "";

$( ".active" ).each( function( index, element ){
if(index==0){
	 decIcon = $( this ).attr('id');
}

if(index==1){
	 selectedYear = $( this ).html().trim();
}
    
});
//alert(decIcon +"----"+selectedYear);


		var pars = "?productId=" + pid + "&text1=" + encodeURIComponent(name)+ "&text2=" + encodeURIComponent(text2)+"&fontc="+fontc+'&mainimage='+encodeURIComponent(img1)+'&batchimage='+encodeURIComponent(img2)+'&mainleft='+Math.round(m1)+'&mainright='+Math.round(m2)+'&d1left='+Math.round(parseInt(dl1))+'&d2left='+Math.round(parseInt(dl2))+'&d1top='+Math.round(parseInt(dt1))+'&d2top='+Math.round(parseInt(dt2))+'&browser='+browser+'&screenwidth='+screenwidth+'&font='+font+'&catPath='+catPath+'&batchimage3='+encodeURIComponent(img3)+'&d3left='+Math.round(parseInt(dl3))+'&d3top='+Math.round(parseInt(dt3))+'&batchimage4='+encodeURIComponent(img4)+'&d4left='+Math.round(parseInt(dl4))+'&d4top='+Math.round(parseInt(dt4))+'&batchimage5='+encodeURIComponent(img5)+'&d5left='+Math.round(parseInt(dl5))+'&d5top='+Math.round(parseInt(dt5))+'&batchimage6='+encodeURIComponent(img6)+'&d6left='+Math.round(parseInt(dl6))+'&d6top='+Math.round(parseInt(dt6))+'&batchimage7='+encodeURIComponent(img7)+'&d7left='+Math.round(parseInt(dl7))+'&d7top='+Math.round(parseInt(dt7))+'&batchimage8='+encodeURIComponent(img8)+'&d8left='+Math.round(parseInt(dl8))+'&d8top='+Math.round(parseInt(dt8))+'&batchimage9='+encodeURIComponent(img9)+'&d9left='+Math.round(parseInt(dl9))+'&d9top='+Math.round(parseInt(dt9))+'&decal=true&decalname='+encodeURIComponent(decIcon)+"&selectedYear="+encodeURIComponent(selectedYear);


	} else{
		var pars = "?productId=" + pid + "&text1=" + encodeURIComponent(name)+ "&text2=" + encodeURIComponent(text2)+"&fontc="+fontc+'&mainimage='+encodeURIComponent(img1)+'&batchimage='+encodeURIComponent(img2)+'&mainleft='+Math.round(m1)+'&mainright='+Math.round(m2)+'&d1left='+Math.round(parseInt(dl1))+'&d2left='+Math.round(parseInt(dl2))+'&d1top='+Math.round(parseInt(dt1))+'&d2top='+Math.round(parseInt(dt2))+'&browser='+browser+'&screenwidth='+screenwidth+'&font='+font+'&catPath='+catPath+'&batchimage3='+encodeURIComponent(img3)+'&d3left='+Math.round(parseInt(dl3))+'&d3top='+Math.round(parseInt(dt3))+'&batchimage4='+encodeURIComponent(img4)+'&d4left='+Math.round(parseInt(dl4))+'&d4top='+Math.round(parseInt(dt4))+'&batchimage5='+encodeURIComponent(img5)+'&d5left='+Math.round(parseInt(dl5))+'&d5top='+Math.round(parseInt(dt5))+'&batchimage6='+encodeURIComponent(img6)+'&d6left='+Math.round(parseInt(dl6))+'&d6top='+Math.round(parseInt(dt6))+'&batchimage7='+encodeURIComponent(img7)+'&d7left='+Math.round(parseInt(dl7))+'&d7top='+Math.round(parseInt(dt7))+'&batchimage8='+encodeURIComponent(img8)+'&d8left='+Math.round(parseInt(dl8))+'&d8top='+Math.round(parseInt(dt8))+'&batchimage9='+encodeURIComponent(img9)+'&d9left='+Math.round(parseInt(dl9))+'&d9top='+Math.round(parseInt(dt9))+'&decal=false';
	}
	//alert(pars);
	//var pars = "?productId=" + pid + "&text1=" + encodeURIComponent(text1)+ "&text2=" + encodeURIComponent(text2) + "&font=" + font+"&text1font="+text1font+"&text2font="+text2font+"&right="+value+"&top="+topval+"&texttype="+strtype+"&righttwo="+valuetwo+"&toptwo="+topvaltwo+"&fontc="+fontc;
	/*if(image!=''){
    	pars = "?img="+imgsplit[1]+"&productId=" + pid + "&text1=" + encodeURIComponent(text1)+ "&text2=" + encodeURIComponent(text2) + "&font=" + font+"&text1font="+text1font+"&text2font="+text2font+"&right="+value+"&top="+topval+"&texttype="+strtype+"&righttwo="+valuetwo+"&toptwo="+topvaltwo+"&fontc="+fontc;
	}*/
    var image_elem = document.getElementById('kuzim');
    image_elem.src = url + pars;
	
	if(decal){
	$.ajax({

			url: url + pars,

			type: 'GET',

			async: true,

			success: function (data) {
				//alert(url + pars);
				document.cart_quantity.submit();
				return true;
			}
			
			

		});
	}else{
	$.ajax({

			url: url + pars,

			type: 'GET',

			async: true,

			success: function (data) {
				
				//document.cart_quantity.submit();

			}

			

		});
	}
	//this.form.submit();
	return true;
  }
</SCRIPT>
<div class="customize" style="display:none; visibility:hidden; height:0px;">
<?php
// Image To Category
/*
$category_cust = array(
           array('categories_id' => 30, 'name' => 'License Plate Frames'),
           array('categories_id' => 287, 'name' => 'Designer Key Chains')
           );
if ($prod_qwe['products_custom'] == "2" ) {
    echo '<img src="includes/templates/classic/images/design/customize_this_frame.gif" alt="" width="153" height="17" border="0">';
} else {
    if(zen_product_in_category($_GET['products_id'], $category_cust[1]['categories_id'])) {
        echo zen_image(DIR_WS_TEMPLATES . 'classic/images/design/customize_this_key_chain.gif', '');
    } else {
        echo zen_image(DIR_WS_TEMPLATES . 'classic/images/design/customize_this_plate.gif', '');
    }
    //echo '<img src="includes/templates/classic/images/design/customize_this_plate.gif" alt="" width="167" height="25" border="0">';
}*/
?>
</div>
<div class="col col_4_of_4">
<div class="col col_4_of_4">
<div><div class="col col_4_of_4">
<div class="col col_2_of_4">
<div id="textwriteblock" style="display:block">
<div class="customize_button">
<?php  if ($numLine == "2"){
       $funcName = "PutImage2()";
    } else {
        $funcName = "PutImage1()";
    }
	$funcName = "PutImage1(true)";
	?>
	<span class="col col_1_of_3" style="display:none">Color</span>
<select name="fontc" id="fontc" onchange="PutImage1(false);" style="display:none">
	<option value="0" selected="selected">Select Color</option>
	<option value="000000">Black</option>
	<option value="000099">Blue</option>
	<option value="00ffff">Cyan</option>
	<option value="009900">Green</option>
	<option value="ff00ff">Magenta</option>
	<option value="ff0000">Red</option>
	<option value="ffff00">Yellow</option>
	<option value="ffffff">White</option>
</select>

	<?php
    //echo '<input type="text" class="border_black" id="tfield1" name="tfield1" maxlength="'.$maxChar1.'" size="'.$maxChar1.'" style="width: 160px; height:17px;" onkeyup="PutImage1()" />';
    echo '<!--<span class="col col_1_of_2" style="margin-left:0px">'.zen_image(DIR_WS_IMAGES . 'customize.png', '').'</span>--><div style="max-width: 170px;font-weight: bold;text-align: center;line-height: 20px;color:#000; font-size: 14px;">TYPE IN TEXT BOX BELOW</div><input type="text" class="border_black a'.$productId.'" autocomple="off"  onkeyup="PutImage1(false)" id="tfield1" name="tfield1" maxlength="'.$maxChar1.'" style="width: 160px; height:17px;" /><br />';
   // echo '<div style="max-width: 200px;font-weight: bold;text-align: center;line-height: 20px;color:#000;">PLEASE ENTER YOUR CUSTOM LETTERS/NUMBERS INTO THE BOX BELOW</div>';
    if ($numLine == "2") {
         //echo '<input type="text" class="border_black"  id="tfield2" name="tfield2" maxlength="'.$maxChar2.'" size="'.$maxChar2.'" style="height:17px; margin-top: 5px;">';
         echo '<!--<span class="col col_1_of_2" style="margin-left:0px">&nbsp; </span>--><input type="text" class="border_black a'.$productId.'" autocomple="off" id="tfield2" name="tfield2" maxlength="'.$maxChar2.'" onkeyup="PutImage1(false)" style="width: 160px; height:17px; margin-top: 5px;">';
    } else {
        //echo "&nbsp;<input type='hidden' id='tfield2' name='tfield2' maxlength='$maxChar2' size='$maxChar2' style='height:25px;' value=''>";
        echo "&nbsp;<input type='text' id='tfield2' name='tfield2' maxlength='' autocomple='off' style='width: 160px; height:25px; display:none' value=''>";
    }
	    
       // choose font
    if($prod_qwe['font_choose'] == '1') {
	
		echo '<span class="col col_1_of_2" style="margin-left:0px;height:27px; margin-top:15px">'.zen_image(DIR_WS_IMAGES . 'font.jpg', '').'</span><br />';
        echo zen_draw_pull_down_menu('font_field', $CUSTOM_FONTS_ARRAY[$prod_qwe['products_custom']], '0',' id="font-fields" onchange="PutImage1(false);"');
    }

    echo "<input type='hidden' name='pid' id='pid' value='$productId'>";
?>
<input type='hidden' id='right' value='0' /> 
<input type='hidden' id='left' value='0' /> 
<input type='hidden' id='top' value='0' /> 
<input type='hidden' id='bottom' value='0' /> 
<input type='hidden' id='righttwo' value='0' /> 
<input type='hidden' id='lefttwo' value='0' /> 
<input type='hidden' id='toptwo' value='0' /> 
<input type='hidden' id='bottomtwo' value='0' /> 
<?php
for ($tempcounter=3;$tempcounter<10;$tempcounter++){
?>
<input type='hidden' id='right<?php echo $tempcounter;?>' value='0' /> 
<input type='hidden' id='left<?php echo $tempcounter;?>' value='0' /> 
<input type='hidden' id='top<?php echo $tempcounter;?>' value='0' /> 
<input type='hidden' id='bottom<?php echo $tempcounter;?>' value='0' /> 
<?php
}
?>
<input type="hidden" name="screenwidth" id="screenwidth" value="0" />
</div>
</div>
</div>

<div class="col col_2_of_4">

   			<div class="col col_2_of_4 minicart">You have <?php echo $_SESSION['cart']->count_contents(); ?> <?php echo $_SESSION['cart']->count_contents() == "1" ? "item " : "items " ; ?> in your <a href="/index.php?main_page=shopping_cart" target="_top">basket</a> <br /><br /><br />

				<a href="/index.php?main_page=shopping_cart" target="_top" class="cssButton" style="background: #000 none repeat scroll 0 0;">View Cart</a>

<?php
echo "<style type=\"text/css\">
@font-face {font-family: '".strtolower($prod_qwe['productId'])."';src: url('/fonts/truetype/".$font1.".ttf');}
.".strtolower($prod_qwe['productId'])."{font-family:".strtolower($prod_qwe['productId']).";}
</style>";
?>
			</div>

		</div>