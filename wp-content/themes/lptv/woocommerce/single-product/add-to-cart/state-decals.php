<?php
// $statedecal comes from the including template (lptvplate.php), product meta
// `_plate_statedecal`, format "filename.png;character" - e.g. Berlin-style products
// store "d1.png;>". The character is inserted into the plate text (like any other
// special symbol) when the decal icon is clicked; lpgenI_symbol.php resolves that
// character back to this same image file and composites it in color.
$stateDecalRaw = trim((string) $statedecal);
if ($stateDecalRaw === '') {
    return;
}

$stateDecalParts = explode(';', $stateDecalRaw);
$stateDecalFile  = trim($stateDecalParts[0]);
$stateDecalChar  = isset($stateDecalParts[1]) ? trim($stateDecalParts[1]) : '';

if ($stateDecalFile === '' || $stateDecalChar === '') {
    return;
}
?>
<div class="mt-4" data-decal-type="state">
    <div class="decalslabel">State Decal</div>
    <div id="statedecal" class="symbolclick" rel="<?php echo esc_attr($stateDecalChar); ?>" data-id="state">
        <img src="<?php echo DIR_WS_CATALOG; ?>decals/<?php echo esc_attr($stateDecalFile); ?>" alt="State Decal" />
        <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>decals/<?php echo esc_attr($stateDecalFile); ?>" alt="State Decal"></div>
    </div>
</div>
<script>
    jQuery(function($) {
        $('[data-decal-type="state"] .symbolclick').click(function() {
            let $el = $(this);
            if (!$el.hasClass('selected')) {
                let symbol = $el.attr('rel');
                if (symbol) {
                    appendSymbol(symbol);
                }
            }
            $('[data-decal-type="state"] .symbolclick').removeClass('selected');
            $el.addClass('selected');
        });
    });
</script>
