<?php
// Expects `$colours` in scope from product_details.php (unique colours available for this product).
// Renders only swatches that match the product's available colour variants.
$coloursSafe = (isset($colours) && is_array($colours)) ? $colours : [];

// Overlay palette used for the tinted preview.
// Keys are case-insensitive (we normalize to lower-case before lookup).
$swatchMap = [
    'navy' => ['bg' => '#1a2a4a', 'overlay' => 'rgba(26,42,74,0.68)'],
    'forest' => ['bg' => '#2d5a3d', 'overlay' => 'rgba(45,90,61,0.65)'],
    'burgundy' => ['bg' => '#6e1a2a', 'overlay' => 'rgba(110,26,42,0.65)'],
    'camel' => ['bg' => '#c19a6b', 'overlay' => 'rgba(193,154,107,0.6)'],
    'black' => ['bg' => '#111', 'overlay' => 'rgba(10,10,10,0.72)'],
    'charcoal' => ['bg' => '#3f3f46', 'overlay' => 'rgba(20,20,20,0.65)'],
    'grey' => ['bg' => '#7a7a7a', 'overlay' => 'rgba(120,120,120,0.45)'],
    'red' => ['bg' => '#b91c1c', 'overlay' => 'rgba(185,28,28,0.50)'],
    'blue' => ['bg' => '#1d4ed8', 'overlay' => 'rgba(29,78,216,0.45)'],
    'green' => ['bg' => '#15803d', 'overlay' => 'rgba(21,128,61,0.45)'],
    'brown' => ['bg' => '#6b4f2a', 'overlay' => 'rgba(107,79,42,0.45)'],
    'pink' => ['bg' => '#db7093', 'overlay' => 'rgba(219,112,147,0.45)'],
    'beige' => ['bg' => '#f5f0dc', 'overlay' => 'rgba(245,240,220,0.55)'],
    'white' => ['bg' => '#f8fafc', 'overlay' => 'rgba(255,255,255,0.50)'],
];
?>

<?php
$firstColour = null;
foreach ($coloursSafe as $c) {
    $t = trim((string)$c);
    if ($t !== '') { $firstColour = $t; break; }
}
?>
<div class="mb-3">
    <p class="fw-semibold mb-2">
        Colour: <span id="colourName" class="fw-normal text-muted"><?php echo $firstColour ? htmlspecialchars($firstColour) : '—'; ?></span>
    </p>

    <div class="swatches">
        <?php $isFirst = true; foreach ($coloursSafe as $c): ?>
            <?php
                $cTrim = trim((string)$c);
                if ($cTrim === '') continue;

                $key = mb_strtolower($cTrim);
                $bg = $swatchMap[$key]['bg'] ?? '#888';
                $overlay = $swatchMap[$key]['overlay'] ?? 'transparent';
            ?>
            <div class="swatch<?php echo $isFirst ? ' active' : ''; ?>"
                 style="background:<?php echo htmlspecialchars($bg, ENT_QUOTES, 'UTF-8'); ?>;"
                 data-color="<?php echo htmlspecialchars($overlay, ENT_QUOTES, 'UTF-8'); ?>"
                 data-name="<?php echo htmlspecialchars($cTrim, ENT_QUOTES, 'UTF-8'); ?>"
                 data-overlay="productOverlay"
                 onclick="setColour(this)"
                 title="<?php echo htmlspecialchars($cTrim, ENT_QUOTES, 'UTF-8'); ?>"></div>
            <?php $isFirst = false; ?>
        <?php endforeach; ?>
    </div>
</div>
<script>
(function() {
    function initColourOverlay() {
        var active = document.querySelector('.swatches .swatch.active');
        if (active && typeof setColour === 'function') setColour(active);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initColourOverlay);
    } else {
        initColourOverlay();
    }
})();
</script>
