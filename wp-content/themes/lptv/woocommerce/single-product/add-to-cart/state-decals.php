<div class="decalslabel">State Decal</div>
<?php
$statedecals = ['tirol.png'];

foreach ($statedecals as $val) {
    $filename1 = explode(";", strtolower($val));
    $filename = explode(".", strtolower($filename1[0]));
    $filetemp = explode(".", strtolower($filename1[0]));
    $filename = $val[0];
    if ($filetemp[0] == '') $filetemp[0] = 'd';
    if ($filename1[1] == '"') {
?>

        <div id="<?php echo $filename; ?>" class="symbolclick customizeproductimage imgselector <?php echo $decal20; ?>" onClick="changeimg('<?php echo $filetemp[0]; ?>')" rel='<?php echo $filename1[1]; ?>'>
        <?php } else { ?>
            <div id="<?php echo $filename; ?>" class="symbolclick customizeproductimage imgselector <?php echo $decal20; ?>" onClick="changeimg('<?php echo $filetemp[0]; ?>')" rel="<?php echo $filename1[1]; ?>">
            <?php } ?>
            <img src="<?php echo DIR_WS_CATALOG; ?>decals/<?php echo $filename1[0]; ?>" alt="Decal" />
            <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/<?php echo $filename1[0]; ?>" alt="Decal"></div>
            </div>

        <?php } ?>