<?php define('DIR_WS_CATALOG', '/wp-content/plugins/lptv-plates/'); ?>

<?php if ($edecal == 'Y'): ?>
    <div data-decal-type="emission">
        <div class="decalslabel">Emission Test Decal</div>
        <div style="width:60%;">
            <div class="grid grid-cols-5" style=width:100%;>
                <div id="decal2" class="symbolclick  " rel=":" data-id="2017">
                    <img src="<?php echo DIR_WS_CATALOG; ?>decals/decal2.png" alt="Decal" />
                    <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/decal2.png" alt="Decal"></div>
                </div>
                <div id="decal11" class="symbolclick  " rel=":" data-id="2018">
                    <img src="<?php echo DIR_WS_CATALOG; ?>decals/decal11.png" alt="Decal" />
                    <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/decal11.png" alt="Decal"></div>
                </div>
                <div id="decal4" class="symbolclick  " rel=":" data-id="2019">
                    <img src="<?php echo DIR_WS_CATALOG; ?>decals/decal4.png" alt="Decal" />
                    <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/decal4.png" alt="Decal"></div>
                </div>
                <div id="decal5" class="symbolclick  " rel=":" data-id="2020">
                    <img src="<?php echo DIR_WS_CATALOG; ?>decals/decal5.png" alt="Decal" />
                    <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/decal5.png" alt="Decal"></div>
                </div>
                <div id="decal1" class="symbolclick   " rel=":" data-id="2021">
                    <img src="<?php echo DIR_WS_CATALOG; ?>decals/decal1.png" alt="Decal" />
                    <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/decal1.png" alt="Decal"></div>
                </div>

            </div>
            <div class="grid grid-cols-5 text-center" style="width:100%;border-top: 1px solid #ccc;">
                <div id="adecal2" class="decalyear " data-id="2017">
                    2017
                </div>
                <div id="adecal11" class="decalyear " data-id="2018">
                    2018
                </div>
                <div id="adecal4" class="decalyear " data-id="2019">
                    2019
                </div>
                <div id="adecal5" class="decalyear " data-id="2020">
                    2020
                </div>
                <div id="adecal1" class="decalyear " data-id="2021">
                    2021
                </div>
            </div>

            <!-- new decals -->
            <div style="width:100%;border-top: 1px solid #ccc;" class="grid grid-cols-5">
                <div id="e_decal_2022" class="symbolclick  " rel=":" data-id="2022">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/decals/e_decal_2022.png" alt="Decal" style="width: 24px; height: 27px;" />
                    <div class="largedecal"><img src="<?php echo get_template_directory_uri(); ?>/images/decals/e_decal_2022.png" alt="Decal 2022" width="110px" /></div>
                </div>
                <div id="e_decal_2023" class="symbolclick  " rel=":" data-id="2023">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/decals/e_decal_2023.png" alt="Decal" style="width: 24px; height: 27px;" />
                    <div class="largedecal"><img src="<?php echo get_template_directory_uri(); ?>/images/decals/e_decal_2023.png" alt="Decal 2023" width="110px" /></div>
                </div>
                <div id="e_decal_2024" class="symbolclick  " rel=":" data-id="2024">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/decals/e_decal_2024.png" alt="Decal" style="width: 24px; height: 27px;" />
                    <div class="largedecal"><img src="<?php echo get_template_directory_uri(); ?>/images/decals/e_decal_2024.png" alt="Decal 2024" width="110px" /></div>
                </div>
                <div id="e_decal_2025" class="symbolclick  " rel=":" data-id="2025">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/decals/e_decal_2025.png" alt="Decal" style="width: 24px; height: 27px;" />
                    <div class="largedecal"><img src="<?php echo get_template_directory_uri(); ?>/images/decals/e_decal_2025.png" alt="Decal 2025" width="110px" /></div>
                </div>
                <div id="e_decal_2026" class="symbolclick  " rel=":" data-id="2026">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/decals/e_decal_2026.png" alt="Decal" style="width: 24px; height: 27px;" />
                    <div class="largedecal"><img src="<?php echo get_template_directory_uri(); ?>/images/decals/e_decal_2026.png" alt="Decal 2026" width="110px" /></div>
                </div>
            </div>

            <div class="grid grid-cols-5 text-center" style="width:100%;border-top: 1px solid #ccc;">
                <div id="adecal2" class="decalyear " data-id="2022">
                    2022
                </div>
                <div id="adecal11" class="decalyear " data-id="2023">
                    2023
                </div>
                <div id="adecal4" class="decalyear " data-id="2024">
                    2024
                </div>
                <div id="adecal5" class="decalyear " data-id="2025">
                    2025
                </div>
                <div id="adecal1" class="decalyear " data-id="2026">
                    2026
                </div>
            </div>

        </div>
    </div>
<?php endif; ?>

<?php if ($saftydecal == 'Y'): ?>
    <div class="mt-4" data-decal-type="safety">
        <div class="decalslabel">Safety Test Decal</div>
        <div style="width:60%;">
            <div class="grid grid-cols-5" style="width:100%;">
                <div id="decal7" class="symbolclick  " rel=";" data-id="2017">
                    <img src="<?php echo DIR_WS_CATALOG; ?>decals/decal7.png" alt="Decal" />
                    <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/decal7.png" alt="Decal"></div>
                </div>
                <div id="decal12" class="symbolclick  " rel=";" data-id="2018">
                    <img src="<?php echo DIR_WS_CATALOG; ?>decals/decal12.png" alt="Decal" />
                    <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/decal12.png" alt="Decal"></div>
                </div>
                <div id="decal9" class="symbolclick  " rel=";" data-id="2019">
                    <img src="<?php echo DIR_WS_CATALOG; ?>decals/decal9.png" alt="Decal" />
                    <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/decal9.png" alt="Decal"></div>
                </div>
                <div id="decal10" class="symbolclick  " rel=";" data-id="2020">
                    <img src="<?php echo DIR_WS_CATALOG; ?>decals/decal10.png" alt="Decal" />
                    <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/decal10.png" alt="Decal"></div>
                </div>
                <div id="decal6" class="symbolclick  " rel=";" data-id="2021">
                    <img src="<?php echo DIR_WS_CATALOG; ?>decals/decal6.png" alt="Decal" />
                    <div class="largedecal"><img src="<?php echo DIR_WS_CATALOG; ?>largedecal/decal6.png" alt="Decal"></div>
                </div>
            </div>
            <div class="grid grid-cols-5 text-center" style="width:100%;border-top: 1px solid #ccc;">
                <div id="adecal2" class="decalyear " data-id="2017">
                    2017
                </div>
                <div id="adecal11" class="decalyear " data-id="2018">
                    2018
                </div>
                <div id="adecal4" class="decalyear " data-id="2019">
                    2019
                </div>
                <div id="adecal5" class="decalyear " data-id="2020">
                    2020
                </div>
                <div id="adecal1" class="decalyear " data-id="2021">
                    2021
                </div>
            </div>

            <div class="grid grid-cols-5" style="width:100%;border-top: 1px solid #ccc;">
                <div id="s_decal_2022" class="symbolclick  " rel=";" data-id="2022">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/decals/s_decal_2022.png" alt="Decal" style="width: 28px;" />
                    <div class="largedecal"><img src="<?php echo get_template_directory_uri(); ?>/images/decals/s_decal_2022.png" alt="Decal 2022" width="110px" /></div>
                </div>
                <div id="s_decal_2023" class="symbolclick  " rel=";" data-id="2023">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/decals/s_decal_2023.png" alt="Decal" style="width: 28px;" />
                    <div class="largedecal"><img src="<?php echo get_template_directory_uri(); ?>/images/decals/s_decal_2023.png" alt="Decal 2023" width="110px" /></div>
                </div>
                <div id="s_decal_2024" class="symbolclick  " rel=";" data-id="2024">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/decals/s_decal_2024.png" alt="Decal" style="width: 28px;" />
                    <div class="largedecal"><img src="<?php echo get_template_directory_uri(); ?>/images/decals/s_decal_2024.png" alt="Decal 2024" width="110px" /></div>
                </div>
                <div id="s_decal_2025" class="symbolclick  " rel=";" data-id="2025">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/decals/s_decal_2025.png" alt="Decal" style="width: 28px;" />
                    <div class="largedecal"><img src="<?php echo get_template_directory_uri(); ?>/images/decals/s_decal_2025.png" alt="Decal 2025" width="110px" /></div>
                </div>
                <div id="s_decal_2026" class="symbolclick  " rel=";" data-id="2026">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/decals/s_decal_2026.png" alt="Decal" style="width: 28px;" />
                    <div class="largedecal"><img src="<?php echo get_template_directory_uri(); ?>/images/decals/s_decal_2026.png" alt="Decal 2026" width="110px" /></div>
                </div>
            </div>

            <div class="grid grid-cols-5 text-center" style="width:100%;border-top: 1px solid #ccc;">
                <div id="adecal2" class="decalyear " data-id="2022">
                    2022
                </div>
                <div id="adecal11" class="decalyear " data-id="2023">
                    2023
                </div>
                <div id="adecal4" class="decalyear " data-id="2024">
                    2024
                </div>
                <div id="adecal5" class="decalyear " data-id="2025">
                    2025
                </div>
                <div id="adecal1" class="decalyear " data-id="2026">
                    2026
                </div>
            </div>

        </div>
    </div>
<?php endif; ?>


<script>
    jQuery(function($) {

        // Berlin-style products (window.lptvSyncDecalYears, set in lptvplate.php) show one
        // combined emission+state badge instead of two independent icons - see eecdber.gif.
        function isSyncMode() {
            return window.lptvSyncDecalYears === true
                && $('[data-decal-type="emission"]').length > 0
                && $('[data-decal-type="state"]').length > 0;
        }

        function handleSyncClick($el, clickedType) {
            let $emissionScope = $('[data-decal-type="emission"]');
            let $stateScope = $('[data-decal-type="state"]');
            // the state trigger char is data-driven (from _plate_statedecal), never hardcoded
            let stateSymbol = $stateScope.find('[data-symbol]').first().attr('data-symbol');

            let year;
            if (clickedType === 'emission') {
                year = $el.attr('data-id');
            } else {
                year = jQuery('#_edecal_year').val()
                    || $emissionScope.find('.symbolclick').first().attr('data-id');
            }

            // insert the STATE trigger char idempotently - never the emission ':' char
            let plateText = jQuery(`#${lastFocus}`);
            let v = plateText.val() || '';
            if (stateSymbol && v.indexOf(stateSymbol) === -1) {
                plateText.val(v + stateSymbol);
                plateText.trigger('keyup');
            }

            // record the chosen year under the emission field regardless of which
            // element was clicked - the server reads this for the stacked badge
            setDecalYear('emission', year);

            $emissionScope.find('.symbolclick, .decalyear').removeClass('selected');
            $emissionScope.find(`[data-id="${year}"]`).addClass('selected');
            $stateScope.find('.symbolclick').addClass('selected');

            $(document).trigger('lptv:decalChanged');
        }

        $('.symbolclick, .decalyear').click(function() {
            let $el = $(this);
            let $scope = $el.closest('[data-decal-type]');
            let type = $scope.attr('data-decal-type') || 'unknown';

            if (isSyncMode() && (type === 'emission' || type === 'state')) {
                handleSyncClick($el, type);
                return;
            }

            let symbol = $el.attr('rel');
            let year = $el.attr('data-id');
            if (symbol) {
                appendSymbol(symbol);
            }

            // scope the "selected" highlight to this picker's own section only, so the
            // emission and safety rows (which reuse the same data-id year labels) never
            // cross-highlight each other
            $scope.find('.symbolclick, .decalyear').removeClass('selected');
            $scope.find(`.symbolclick[data-id="${year}"]`).addClass('selected');
            $scope.find(`.decalyear[data-id="${year}"]`).addClass('selected');

            setDecalYear(type, year);

            // year-only clicks (e.g. clicking a plain "2019" label) don't insert a new
            // character, so keyup on the text input never fires; fire our own refresh event
            $(document).trigger('lptv:decalChanged');
        });

    });
</script>

<style>
    .mb-8 {
        margin: 0 0 24px 0;
    }

    .decalyear {
        font-size: 12.02px;
        cursor: pointer;
        color: #d9232e;
        font-weight: bold;
        letter-spacing: .1px;
    }

    .decalslabel {
        margin: 8px 16px;
        font-size: 14px;
    }

    .symbolclick {
        position: relative;
        text-align: center;
    }

    .symbolclick.selected {
        box-shadow: 0 0 0 2px rgb(60, 197, 94);
    }

    .decalyear.selected {
        box-shadow: 1px 1px 1px 2px rgb(60, 197, 94);
    }

    .symbolclick:hover {
        cursor: pointer;
    }

    .largedecal {
        position: absolute;
        top: 20px;
        left: 0;
        display: none;
    }

    .symbolclick:hover .largedecal {
        display: block;
        z-index: 1;
    }


    .grid {
        display: grid;
    }

    .grid-cols-5 {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }
</style>