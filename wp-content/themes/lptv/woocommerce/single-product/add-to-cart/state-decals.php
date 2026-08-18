<div class="decalslabel">State Decal</div>
<?php
// $statedecal is set by the including template (lptvplate.php) from the product's
// _plate_statedecal meta, format: "filename.png;triggerchar[,filename2.png;triggerchar2,...]"
$statedecalEntries = array_filter(array_map('trim', explode(',', (string) $statedecal)));

foreach ($statedecalEntries as $decalEntry) {
    $decalParts = array_pad(explode(';', $decalEntry), 2, '');
    $filename = strtolower(trim($decalParts[0]));
    $trigger = $decalParts[1];
    if ($filename === '' || $trigger === '') {
        continue;
    }
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
?>
    <!-- click is wired up by the shared '.symbolclick, .decalyear' handler in edecal.php, via the rel attribute -->
    <div id="<?php echo esc_attr($baseName); ?>" class="symbolclick customizeproductimage imgselector" data-id="<?php echo esc_attr($baseName); ?>" rel="<?php echo esc_attr($trigger); ?>">
        <img src="<?php echo DIR_WS_CATALOG; ?>decals/<?php echo esc_attr($filename); ?>" alt="Decal" />
        <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/<?php echo esc_attr($filename); ?>" alt="Decal"></div>
    </div>
<?php } ?>
