<?php
/*
   ____       __     ______________     ____            ________         ____________      __          __    ____________
  /\\\\\     /\\\   /\\\\\\\\\\\\\\\   /\\\\\_         /\\\\\\\\\_      /\\\\\\\\\\\\\__  /\\\        /\\\  /\\\\\\\\\\\\\__           
  \/\\\\\\   \/\\\  \/\\\///////////  /\\\///\\\_     /\\\///////\\\    \/\\\/////////\\\ \/\\\       \/\\\ \/\\\/////////\\\        
   \/\\\/\\\  \/\\\  \/\\\________   /\\\/  \///\\\   \///      \//\\\   \/\\\_______\/\\\ \/\\\_______\/\\\ \/\\\_______\/\\\       
    \/\\\//\\\ \/\\\  \/\\\\\\\\\\\  /\\\      \//\\\            /\\\/    \/\\\\\\\\\\\\\/  \/\\\\\\\\\\\\\\\ \/\\\\\\\\\\\\\/       
     \/\\\\//\\\\/\\\  \/\\\///////  \/\\\       \/\\\         /\\\//      \/\\\/////////    \/\\\/////////\\\ \/\\\/////////        
      \/\\\ \//\\\/\\\  \/\\\         \//\\\      /\\\       /\\\//         \/\\\             \/\\\       \/\\\ \/\\\                
       \/\\\  \//\\\\\\  \/\\\          \///\\\__/\\\       /\\\/__________  \/\\\             \/\\\       \/\\\ \/\\\               
        \/\\\   \//\\\\\  \/\\\            \///\\\\\/       /\\\\\\\\\\\\\\\  \/\\\             \/\\\       \/\\\ \/\\\              
         \///     \/////   \///               \/////        \///////////////   \///              \///        \///  \///              
nfo2php 20260120
By MobCat

nfo2php is a simple rendering "engine" that will convert your nfo files and display them as text for a web page.
This conversion maintains the ansii formatting and spacing for the original file.
So all special chars get rendered correctly, for eg block letters like █ 
This conversion is done in place and on the fly, so the original nfo file is not edited or modified in any way.
*/

// Define default filepaths for nfo2php
$configFile  = substr(basename($_SERVER['PHP_SELF']), 0, -4) . ".json";
$nfoFilePath = substr(basename($_SERVER['PHP_SELF']), 0, -4) . ".nfo";

// Load config JSON
if (!file_exists($configFile)) {
    // Show form to create default config
    if ($_POST['create_config'] ?? false) {
        // This is NOT how you build a new json file, but pritty print will new line our convertLinks arrays and I hate that.
        $defaultConfig = '{
    "nfoPath": "'.$nfoFilePath.'",
    "textColor": "FFDF00",
    "screenColor": "222",
    "font": "vga",
    "title": "nfo2php example",
    "description": "Your description for META tag goes here",
    "icon": "https://mobcat.zip/BadRepack/icon.png",
    "customLinks": {
        "MobCat.zip": ["https://mobcat.zip/map"," target=\'_blank\'"],
        "Example 2":  ["http://example.com/file.zip"," download"],
        "Example 3":  ["http://example.com",""]
    },
    "downloadEnabled": true,
    "watermark": true
}';
        echo "<style>body {background-color: #222;color: #FFDF00;font-family: 'Courier New', monospace;}</style>";
        if (file_put_contents($configFile, $defaultConfig)) {
            echo "Created default config file: " . basename($configFile) . "<br>";
            echo "<a href='" . $_SERVER['PHP_SELF'] . "'>Reload page</a>";
        } else {
            echo "Failed to create config file: " . basename($configFile);
        }
        exit;
    }
    echo "<style>body {background-color: #222;color: #FFDF00;font-family: 'Courier New', monospace;}</style>
Config json '" . basename($configFile) . "' not found.<br>
<form method='post'>
<input type='hidden' name='create_config' value='1'>
<button type='submit'>Create Default Config</button>
</form>";
    exit;
}
// Valadate json
$config = json_decode(file_get_contents($configFile), true);
if ($config === null) {
    $jsonError = json_last_error();
    $jsonErrorMsg = json_last_error_msg();
    echo "<style>body {background-color: #222;color: #FFDF00;font-family: 'Courier New', monospace;}</style>";
    die("Bad JSON config '".basename($configFile)."'.<br>Code: $jsonError: $jsonErrorMsg");
}
// Required configuration keys
$requiredKeys = ['font',
                 'textColor', 
                 'screenColor',
                 'title',
                 'description',
                 'icon',
                 'customLinks', 
                 'downloadEnabled', 
                 'watermark'];
foreach ($requiredKeys as $key) {
    if (!array_key_exists($key, $config)) {
        die("ERROR: \"$key\": is missing from ".basename($configFile));
    }
}

// If a custom filepath to an nfo is defined, use that.
// Otherwise yes, dubble check, reuse and reset the default filepath again.
if ($config['nfoPath'] != '' or $config['nfoPath'] != null) {
    $nfoFilePath = $config['nfoPath'];
} else {
    $nfoFilePath = substr(basename($_SERVER['PHP_SELF']), 0, -4) . ".nfo";
}

$alowedFonts = ["cga", "vga", ""];
if(!in_array($config['font'], $alowedFonts)) {
    die("Invalid font config in ".basename($configFile).'<br>Your options are<br>"font": "vga"<br>"font": "cga"<br>"font": ""<br><br>You have chosen: '.$config['font']);
}

// User overide color config with user prefrence
// index.php?text=ffffff
// index.php?screen=000000
// index.php?text=ffffff&screen=000000
if (isset($_GET['text']) && preg_match('/^#?[0-9a-fA-F]{6}$/', $_GET['text'])) {
    $config['textColor'] = ltrim($_GET['text'], '#');
}
if (isset($_GET['screen']) && preg_match('/^#?[0-9a-fA-F]{6}$/', $_GET['screen'])) {
    $config['screenColor'] = ltrim($_GET['screen'], '#');
}

// main formatter
function nfo2php($config, $content) {
    // Define custom formatting map
    $formatMap = [
        // regex pattern        => [opening tag, closing tag, chars to remove]
        '/\/\*([^*]+)\*\//'     => ['<i>', '</i>', 4],                      // /*word*/ -> <i>word</i>
        '/\\\\\*([^*]+)\*\\\\/' => ['<i class="backward">', '</i>', 4],     // \*word*\ -> backward italic
        '/\/\^([^^]+)\^\//'     => ['<span class="mirror">', '</span>', 4], // /^word^/ -> mirrored text
        '/\/\+([^+]+)\+\//'     => ['<b>', '</b>', 4],                      // /+word+/ -> <b>word</b>
        '/\/-([^-]+)-\//'       => ['<s>', '</s>', 4],                      // /-word-/ -> <s>word</s>
        '/\/_([^_]+)_\//'       => ['<u>', '</u>', 4],                      // /_word_/ -> <u>word</u>
        '/\/!([^!]+)!\//'       => ['<span class="invert">', '</span>', 4], // /!word!/ -> inverts text/background
        '/\/~([^~]+)~\//'       => ['CORRUPT', 'CORRUPT', 4]                // /~word~/ -> corrupted text (special handling)
    ];
    
    // Process line by line to handle ascii spacing
    $lines = explode("\n", $content);
    
    foreach ($lines as &$line) {
        $originalLine = $line;
        $totalCharsRemoved = 0;
        
        // Apply formatting and track how many characters we removed
        foreach ($formatMap as $pattern => $formatConfig) {
            $openTag = $formatConfig[0];
            $closeTag = $formatConfig[1];
            $charsRemoved = $formatConfig[2];
            
            // Special handling for corrupt text
            if ($openTag === 'CORRUPT') {
                $line = preg_replace_callback($pattern, function($matches) use (&$totalCharsRemoved, $charsRemoved) {
                    $word = $matches[1];
                    $corruptChars = ['█', '▓', '▒', '░'];
                    $corruptedWord = '';
                    
                    // Replace each character with a corruption character
                    $wordLength = mb_strlen($word, 'UTF-8');
                    for ($i = 0; $i < $wordLength; $i++) {
                        $char = mb_substr($word, $i, 1, 'UTF-8');
                        if ($char === ' ') {
                            $corruptedWord .= ' ';
                        } else {
                            // Use character position to get consistent corruption per character
                            $corruptIndex = ord($char) % count($corruptChars);
                            $corruptedWord .= $corruptChars[$corruptIndex];
                        }
                    }
                    
                    $totalCharsRemoved += $charsRemoved;
                    
                    // Create a clickable spoiler element
                    return '<span class="corrupt" onclick="this.classList.toggle(\'revealed\')">' . 
                           '<span class="corrupt-text">' . $corruptedWord . '</span>' .
                           '<span class="original-text">' . $word . '</span>' .
                           '</span>';
                }, $line);
            } else {
                // Count matches to know how many characters we'll remove
                $matchCount = preg_match_all($pattern, $line);
                $totalCharsRemoved += $matchCount * $charsRemoved;
                
                $line = preg_replace($pattern, $openTag . '$1' . $closeTag, $line);
            }
        }
        
        if ($totalCharsRemoved > 0) {
            // Find sequences of 3 or more spaces and add compensation to the first one found
            $line = preg_replace_callback('/( {3,})/', function($matches) use (&$totalCharsRemoved) {
                if ($totalCharsRemoved > 0) {
                    $extraSpaces = str_repeat(' ', $totalCharsRemoved);
                    $totalCharsRemoved = 0; // Only compensate once per line
                    return $extraSpaces . $matches[1];
                }
                return $matches[1];
            }, $line);
        }
    }
    
    // Rejoin the lines
    $content = implode("\n", $lines);
    
    // Convert custom links
    if (isset($config['customLinks'])) {
        foreach ($config['customLinks'] as $filename => $customUrl) {
            // Escape special regex characters in filename
            $escapedFilename = preg_quote($filename, '/');
            $pattern = '/\b' . $escapedFilename . '\b/';
            $replacement = '<a href="'.$customUrl[0].'"'.$customUrl[1].'>'.$filename.'</a>';
            $content = preg_replace($pattern, $replacement, $content);
        }
    }
    
    // Convert anything that's left that looks like a link
    $content = preg_replace(
        '/(?<!href=")(?<!">)(https?:\/\/[^\s<>"\']+)(?![^<]*<\/a>)/i',
        '<a href="$1">$1</a>',
        $content
    );
    
    return $content;
}


// Load and convert NFO content 
$nfoContent = '';
if (file_exists($nfoFilePath)) {
    $content = file_get_contents($nfoFilePath);
    // Fix for OEM-US / CP437 encoding. If nfo is not formated like this
    // then don't use whatever iconv spat out. 
    $utf8Content = @iconv('CP437', 'UTF-8//IGNORE', $content);
    if ($utf8Content === false) {
        $nfoContent = nfo2php($config, $content);
    } else {
        $nfoContent = nfo2php($config, $utf8Content);
    }
} else {
    $nfoContent = "NFO file not found: " . $nfoFilePath;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1024, initial-scale=1.0, user-scalable=yes">
    <meta property="og:title" content="<?= $config['title'] ?>">
    <meta property='og:type' content='website' />
    <meta property='description' content='<?= $config['description'] ?>' />
    <meta property='og:description' content='<?= $config['description'] ?>' />
    <meta property="og:image" content="<?= $config['icon'] ?>">
    <meta name="theme-color" content="#<?= $config['textColor'] ?>">
    <title><?= $config['title'] ?></title>
    <link rel="icon" type="image/png" href="<?= $config['icon'] ?>">
    <style>
:root {
    --screen: #<?= $config['screenColor'] ?>;
    --text: #<?= $config['textColor'] ?>;
}

@font-face {
    font-family: 'IBM_VGA';
    src: url('https://raw.githubusercontent.com/MobCat/nfo2php/main/WebPlus_IBM_VGA_9x16.woff') format('woff');
    font-weight: normal;
    font-style: normal;
}
@font-face {
    font-family: 'IBM_CGA';
    src: url('https://raw.githubusercontent.com/MobCat/nfo2php/main/WebPlus_IBM_CGAthin.woff') format('woff');
    font-weight: normal;
    font-style: normal;
}


body {
    min-width: 800px;
    background-color: var(--screen);
    color: var(--text);
    margin: 0;
    padding: 20px;
    font-family: 'Courier New', monospace;
}

a:link {
  color: var(--text);
}

a:visited {
  color: var(--text);
}

a:hover {
  color: var(--screen);
  background-color: var(--text);
  padding: 2px 0px;
}

::selection {
  background-color: var(--text);
  color: var(--screen);
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
}

.backward {
    font-style: italic;
    transform: skewX(15deg); 
    display: inline-block;
}

.mirror {
    transform: scaleX(-1);
    display: inline-block;
}

::selection {
  background-color: var(--text);
  color: var(--screen);
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
}

.invert {
    color: var(--screen);
    background-color: var(--text);
}

.invert::selection {
    background-color: var(--screen);
    color: var(--text);
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
}


/* Corrupt text effect - click to reveal spoiler */
.corrupt {
    cursor: help;
    position: relative;
    user-select: none;
}

.corrupt .original-text {
    display: none;
}

.corrupt .corrupt-text {
    display: inline;
}

.corrupt.revealed .original-text {
    display: inline;
    background-color: var(--screen);
    color: var(--text);
}

.corrupt.revealed .corrupt-text {
    display: none;
}

.corrupt:hover {
    opacity: 0.8;
}

        
.nfo-display {
    color: var(--text);
    line-height: 1.0;
    white-space: pre;
    overflow-x: auto;
    padding: 15px;
    letter-spacing: -0.9px;
}
/* Font-specific sizing */
.nfo-display.vga {
    font-family: 'IBM_VGA', monospace;
    font-size: 16px;
    line-height: 16px;
    letter-spacing: -1px;
}

.nfo-display.cga {
    font-family: 'IBM_CGA', monospace;
    font-size: 8px;
    line-height: 8px;
    letter-spacing: -1px;
}

.error {
    color: #C50F1F;
    text-align: center;
    margin-top: 50px;
}

.file-info {
    color: var(--text);
    margin-bottom: 15px;
    font-size: 12px;
}

* { 
    scrollbar-width: thin; 
    scrollbar-color: var(--text) var(--screen);
}

*::-webkit-scrollbar-thumb {
    background-color: var(--text); 
    border-radius: 20px; 
    border: 3px solid var(--screen);
}
    </style>
</head>
<body>
    <div class="nfo-display <?= $config['font'] ?>"><?php echo $nfoContent; ?></div>
    <?php 
        if ($config['downloadEnabled'] and file_exists($nfoFilePath)) { 
            echo "<a href='{$nfoFilePath}' download>Download this NFO</a><br>";
        }
        if ($config['watermark']) { 
            echo "This NFO file was rendered with <a href='https://github.com/MobCat/nfo2php' target='_blank'>nfo2php</a> by MobCat";
        }
    ?><br><br>
</body>
</html>