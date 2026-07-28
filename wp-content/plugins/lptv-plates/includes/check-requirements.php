<?php
/**
 * diagnostic script to check if all requirements are met for lpgenI_symbol.php
 * run this on production to identify missing libraries or configuration issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PHP GD Library Diagnostic ===\n\n";

// check if GD is loaded
if (!extension_loaded('gd')) {
    echo "❌ CRITICAL: GD extension is NOT loaded\n";
    echo "   Install with: apt-get install php-gd (Ubuntu/Debian)\n";
    echo "   Or: yum install php-gd (CentOS/RHEL)\n\n";
    exit(1);
} else {
    echo "✅ GD extension is loaded\n\n";
}

// get GD info
$gdInfo = gd_info();
echo "=== GD Configuration ===\n";
foreach ($gdInfo as $key => $value) {
    $status = $value ? '✅' : '❌';
    echo "$status $key: " . ($value === true ? 'Yes' : ($value === false ? 'No' : $value)) . "\n";
}
echo "\n";

// check critical functions
echo "=== Required Functions ===\n";
$requiredFunctions = [
    'imagecreatefrompng' => 'Create image from PNG',
    'imagettftext' => 'TrueType text rendering (CRITICAL)',
    'imagettfbbox' => 'TrueType bounding box (CRITICAL)',
    'imagecolorallocate' => 'Color allocation',
    'imagepng' => 'PNG output',
    'imagecopy' => 'Image copying',
];

$missingFunctions = [];
foreach ($requiredFunctions as $func => $desc) {
    if (function_exists($func)) {
        echo "✅ $func() - $desc\n";
    } else {
        echo "❌ $func() - $desc - MISSING!\n";
        $missingFunctions[] = $func;
    }
}
echo "\n";

// check FreeType support specifically
if (!isset($gdInfo['FreeType Support']) || !$gdInfo['FreeType Support']) {
    echo "❌ CRITICAL: FreeType Support is NOT enabled\n";
    echo "   This is required for imagettftext() and imagettfbbox()\n";
    echo "   Recompile PHP with --with-freetype or install php-gd with freetype\n\n";
} else {
    echo "✅ FreeType Support is enabled\n\n";
}

// check file paths
echo "=== File System Checks ===\n";
$baseDir = dirname(__FILE__);
echo "Base directory: $baseDir\n\n";

$paths = [
    'images/pngs' => 'Base images directory',
    'images/pngs/test' => 'Test output directory',
    'fonts/truetype' => 'TrueType fonts directory',
];

foreach ($paths as $path => $desc) {
    $fullPath = $baseDir . '/' . $path;
    if (file_exists($fullPath)) {
        if (is_dir($fullPath)) {
            if (is_readable($fullPath)) {
                $writable = is_writable($fullPath) ? '(writable)' : '(NOT writable)';
                echo "✅ $desc: $fullPath $writable\n";
            } else {
                echo "⚠️  $desc: $fullPath (NOT readable)\n";
            }
        } else {
            echo "⚠️  $desc: $fullPath (exists but not a directory)\n";
        }
    } else {
        echo "❌ $desc: $fullPath (does NOT exist)\n";
    }
}
echo "\n";

// check for sample fonts
echo "=== Font Files Check ===\n";
$fontDir = $baseDir . '/fonts/truetype';
if (is_dir($fontDir)) {
    $fonts = glob($fontDir . '/*.ttf');
    if (count($fonts) > 0) {
        echo "✅ Found " . count($fonts) . " TrueType font(s):\n";
        foreach ($fonts as $font) {
            $readable = is_readable($font) ? '✅' : '❌';
            echo "   $readable " . basename($font) . "\n";
        }
    } else {
        echo "⚠️  No .ttf files found in $fontDir\n";
    }
} else {
    echo "❌ Font directory does not exist\n";
}
echo "\n";

// test image creation
echo "=== Image Creation Test ===\n";
$testImage = imagecreatetruecolor(100, 100);
if ($testImage) {
    echo "✅ Can create test image\n";
    imagedestroy($testImage);
} else {
    echo "❌ Cannot create test image\n";
}
echo "\n";

// test TrueType rendering (if possible)
if (function_exists('imagettftext') && isset($gdInfo['FreeType Support']) && $gdInfo['FreeType Support']) {
    $testFonts = glob($fontDir . '/*.ttf');
    if (count($testFonts) > 0) {
        echo "=== TrueType Rendering Test ===\n";
        $testFont = $testFonts[0];
        $testImg = imagecreatetruecolor(200, 50);
        $white = imagecolorallocate($testImg, 255, 255, 255);
        $black = imagecolorallocate($testImg, 0, 0, 0);
        imagefill($testImg, 0, 0, $white);
        
        try {
            $bbox = imagettfbbox(20, 0, $testFont, "TEST");
            imagettftext($testImg, 20, 0, 10, 30, $black, $testFont, "TEST");
            echo "✅ TrueType rendering works with " . basename($testFont) . "\n";
            imagedestroy($testImg);
        } catch (Exception $e) {
            echo "❌ TrueType rendering failed: " . $e->getMessage() . "\n";
            imagedestroy($testImg);
        }
    }
}
echo "\n";

// summary
echo "=== Summary ===\n";
if (count($missingFunctions) > 0) {
    echo "❌ FAILED: Missing required functions\n";
    echo "   Install php-gd package on your system\n";
} elseif (!isset($gdInfo['FreeType Support']) || !$gdInfo['FreeType Support']) {
    echo "❌ FAILED: FreeType support is missing\n";
    echo "   You need php-gd compiled with FreeType support\n";
} else {
    echo "✅ All PHP requirements are met\n";
    echo "   If images are still empty, check:\n";
    echo "   1. Database query returns data (check error logs)\n";
    echo "   2. Base PNG images exist in images/pngs/\n";
    echo "   3. Font files (.ttf) exist in fonts/truetype/\n";
    echo "   4. File permissions allow reading/writing\n";
}
