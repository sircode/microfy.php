<?php
/**
 * microfy.php
 * v0.1.3 
 * Author: SirCode
 */

// paths

function climb_dir(string $path = null, int $levels = 1): string
{
    // 1) Figure out the starting path
    if ($path === null) {
        // debug_backtrace()[0] is this function, [1] is its caller
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $path  = $trace[1]['file'] ?? __FILE__;
    }

    // 2) Normalize to a directory
    $dir = is_dir($path)
        ? rtrim($path, '/\\')
        : dirname($path);

    // 3) Climb up $levels times
    while ($levels-- > 0) {
        $parent = dirname($dir);
        // if we’re already at the root, stop
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }

    return $dir;
}

// --- arrays.php ---
// General array accessor
function get_r(array $array, $key, $default = null)
{
    return $array[$key] ?? $default;
}

// $_GET shortcut
function v($arr, $key, $default = '')
{
    return $arr[$key] ?? $default;
}

function get_var($key, $default = '')
{
    return v($_GET, $key, $default);
}

function post_var($key, $default = '')
{
    return v($_POST, $key, $default);
}

function request_var($key, $default = '')
{
    return $_POST[$key] ?? $_GET[$key] ?? $default; // optional: v(array_merge($_POST, $_GET), ...)
}

// Simple input_vars() helper
function input_vars(array $keys, array $source, string $prefix = ''): array
{
    $result = [];

    foreach ($keys as $key) {
        $result["{$prefix}{$key}"] = $source[$key] ?? '';
    }

    return $result;
}
//input_vars aliases 
function get_vars(array $keys, string $prefix = ''): array
{
    return input_vars($keys, $_GET, $prefix);
}

function post_vars(array $keys, string $prefix = ''): array
{
    return input_vars($keys, $_POST, $prefix);
}

function req_vars(array $keys, string $prefix = ''): array
{
    return input_vars($keys, $_REQUEST, $prefix);
}


/* get_vars_prefixed */

function get_vars_prefixed(array $keys): array
{
    return get_vars($keys, 'get_');
}
extract(get_vars_prefixed(['path', 'id']));



function input_all(array $map, array $source): array
{
    $result = [];

    foreach ($map as $varName => $info) {
        if (is_array($info)) {
            $key     = $info[0];
            $default = $info[1] ?? '';
        } else {
            $key     = $info;
            $default = '';
        }

        // Treat empty string as "no value"
        $result[$varName] = (isset($source[$key]) && $source[$key] !== '')
            ? $source[$key]
            : $default;
    }

    return $result;
}


/* get_all  post_all req_all */

function get_all(array $map): array
{
    return input_all($map, $_GET);
}

function post_all(array $map): array
{
    return input_all($map, $_POST);
}

function req_all(array $map): array
{
    return input_all($map, $_REQUEST);
}


//Hybrid auto-extract with a whitelist
function extract_vars(array $source, array $allow, string $prefix = ''): void
{
    foreach ($allow as $key) {
        $GLOBALS[$prefix . $key] = $source[$key] ?? '';
    }
}


// --- db.php ---
// Connect using PDO
function db_pdo($host, $dbname, $user, $pass, $charset = 'utf8mb4', $driver = 'mysql')
{
    if ($driver === 'pgsql') {
        $dsn = "pgsql:host=$host;dbname=$dbname";
    } else {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        dd("PDO Connection failed: " . $e->getMessage());
    }
}




// Fetch all rows from a query (PDO version)
function db_all(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC); // Explicit associative array
}
//  Fetch a single row
function db_one(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(); // returns one row or false
}

/* db_insert_id() – Last auto-increment ID */

function db_insert_id(PDO $pdo)
{
    return $pdo->lastInsertId();
}

/*  (Optional) db_error() – Pretty-print an error */
function db_error(PDOException $e)
{
    dd("DB Error: " . $e->getMessage());
}

/* db_count() */
function db_count(PDO $pdo, string $sql, array $params = [])
{
    return (int) db_val($pdo, $sql, $params);
}


/* db_val() – Fetch a single value (e.g. COUNT, name, ID) */

function db_val(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn(); // returns scalar or false
}


function db_exec(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params); // returns true/false
}

function db_exists(PDO $pdo, string $table, string $column, $value)
{
    $sql = "SELECT 1 FROM `$table` WHERE `$column` = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$value]);
    return $stmt->fetchColumn() !== false;
}


// --- Connect using MySQLi ---
function db_mysqli($host, $user, $pass, $dbname, $port = 3306)
{
    $mysqli = new mysqli($host, $user, $pass, $dbname, $port);

    if ($mysqli->connect_error) {
        dd("MySQLi Connection failed: " . $mysqli->connect_error);
    }

    return $mysqli;
}

// --- debug.php ---
/* 
pp()	Pretty-print print_r()
ppd()	Pretty-print + die
ppr()	Return string version of print_r
pper()	Echo + return
pd()	Pretty var_dump()
pdd()	Pretty var_dump() + die
pdr()	Return string version of var_dump
d()	Quick var_dump(s)
dd()	Quick var_dump(s) + die
mlog()	Log plain string
log_pr()	Log print_r() output
log_vd()	Log var_dump() output
*/

// --- Pretty Print (print_r)
function pp($data, $limit = null)
{
    echo ppr($data, $limit);
}

function mpp(...$args)
{
    foreach ($args as $arg) {
        echo ppr($arg);
    }
}

function mppd(...$args)
{
    mpp(...$args);
    die();
}

function ppd($data, $limit = null)
{
    pp($data, $limit);
    die();
}

function ppr($data, $limit = null)
{
    $output = print_r($data, true);
    if ($limit !== null) {
        $lines = explode("\n", $output);
        $output = implode("\n", array_slice($lines, 0, $limit));
    }
    return "<pre>$output</pre>";
}

function pper($data, $limit = null)
{
    $output = ppr($data, $limit);
    echo $output;
    return $output;
}

// --- Var Dump (dumps) ---
function pd($var, $label = null)
{
    echo pdr($var, $label);
}

function pdd($var, $label = null)
{
    pd($var, $label);
    die();
}

function pdr($var, $label = null)
{
    ob_start();
    echo "<pre>";
    if ($label) echo "$label:\n";
    var_dump($var);
    echo "</pre>";
    return ob_get_clean();
}

// --- Simple dump shortcuts
function d(...$args)
{
    foreach ($args as $arg) echo pdr($arg);
}

function dd(...$args)
{
    d(...$args);
    die();
}

// --- Logging ---
function mlog($text, $label = null, $file = 'debug.log')
{
    $entry = ($label ? "$label:\n" : "") . $text . "\n";
    file_put_contents($file, $entry, FILE_APPEND);
}

function log_pr($var, $label = null, $file = 'debug_pr.log')
{
    mlog(print_r($var, true), $label, $file);
}

function log_vd($var, $label = null, $file = 'debug_vd.log')
{
    mlog(pdr($var, $label), null, $file);
}


function debug_session()
{
    echo "<div style='font-family: monospace; color: black; background: #f8f8f8; border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Session Name:</strong> " . session_name() . "<br>";
    echo "<strong>Session ID:</strong> " . session_id() . "<br>";
    echo "<strong>\$_SESSION:</strong><br>";
    echo "<pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>";
    echo "</div>";
}



// --- env.php ---
function env($key, $default = null)
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function now($format = 'Y-m-d H:i:s')
{
    return date($format);
}

// --- files.php ---
function jsonf($file, $assoc = true)
{
    if (!file_exists($file)) return null;
    $content = file_get_contents($file);
    return json_decode($content, $assoc);
}

// --- html.php ---
/* links */

function a($href, $text = null, $target = '', $class = '')
{
    if (!preg_match('#^https?://#', $href)) {
        $href = "https://$href";
    }

    $text = $text ?? $href;

    $targetAttr = $target ? " target=\"$target\"" : '';
    $classAttr  = $class  ? " class=\"$class\""   : '';

    return "<a href=\"$href\"$targetAttr$classAttr>$text</a>";
}

function html_table_safe($array, $class = '', $id = '')
{
    if (empty($array)) return "<p><em>No data.</em></p>";

    $idAttr = $id !== '' ? " id='" . htmlspecialchars($id) . "'" : '';

    if ($class !== '') {
        $tableTag = "<table{$idAttr} class='" . htmlspecialchars($class) . "'>";
    } else {
        $tableTag = "<table{$idAttr} border='1' cellpadding='6' cellspacing='0'>";
    }

    $html = $tableTag;

    // Add table header
    $html .= "<thead><tr>";
    foreach (array_keys($array[0]) as $col) {
        $html .= "<th>" . htmlspecialchars($col) . "</th>";
    }
    $html .= "</tr></thead>";

    // Add table body
    $html .= "<tbody>";
    foreach ($array as $row) {
        $html .= "<tr>";
        foreach ($row as $cell) {
            $html .= "<td>" . htmlspecialchars($cell) . "</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</tbody>";

    $html .= "</table>";

    return $html;
}

/**
 * An adjustable table builder: by default it escapes everything,
 * but you can whitelist columns that contain pre-escaped HTML.
 *
 * @param array       $array          The row data
 * @param string[]    $allow_raw_cols List of columns whose contents
 *                                    are already safe HTML
 * @param string      $class          Optional table class
 * @param string      $id             Optional table id
 * @return string     The generated HTML table
 */

function html_table(array $rows, array $allow_raw_cols = [], string $cssClass  = '', string $id = '')
{

    $array = $rows;
    $class = $cssClass ;    

    if (empty($array)) {
        return "<p><em>No data.</em></p>";
    }

    $idAttr = $id !== '' ? " id='" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "'" : '';
    $tableTag = $class !== ''
        ? "<table{$idAttr} class='" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'>"
        : "<table{$idAttr} border='1' cellpadding='6' cellspacing='0'>";

    $html = $tableTag;
    // header
    $html .= "<thead><tr>";
    foreach (array_keys($array[0]) as $col) {
        $html .= "<th>" . htmlspecialchars($col, ENT_QUOTES, 'UTF-8') . "</th>";
    }
    $html .= "</tr></thead>";

    // body
    $html .= "<tbody>";
    foreach ($array as $row) {
        $html .= "<tr>";
        foreach ($row as $col => $cell) {
            if (in_array($col, $allow_raw_cols, true)) {
                // output raw HTML for whitelisted columns
                $html .= "<td>{$cell}</td>";
            } else {
                // escape everything else
                $html .= "<td>" . htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') . "</td>";
            }
        }
        $html .= "</tr>";
    }
    $html .= "</tbody></table>";

    return $html;
}




// --- other.php ---
function c_list(array $items, $reset = false)
{
    static $counter = 1;
    if ($reset) $counter = 1;

    foreach ($items as $item) {
        echo $counter++ . '. ' . $item . '<br>';
    }
}


//Usage
/* 
c_list(['Step A', 'Step B', 'Step C']);
c_list(['One', 'Two'], true); // resets numbering
*/



function load($file)
{
    include_once __DIR__ . "/$file.php";
}

function def($name, $value)
{
    if (!defined($name)) {
        define($name, $value);
    }
    // else {
    //     echo $name . " defined<br>";
    // }
}




// --- response.php ---
function hsc($str)
{
    echo htmlspecialchars($str);
}



function json($data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function ok($msg = 'OK')
{
    json(['status' => 'ok', 'msg' => $msg]);
}

function fail($msg = 'Error')
{
    json(['status' => 'fail', 'msg' => $msg]);
}

// --- strings.php ---
function slugify($string)
{
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    return trim($string, '-');
}

// --- style.php ---
/* Headings */
function h($level, $text, $class = '')
{
    $level = max(1, min(6, (int)$level));
    $classAttr = $class ? " class=\"$class\"" : '';
    echo "<h$level$classAttr>$text</h$level>";
}

/* Inline Elements */
function b($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    return "<b$classAttr>$text</b>";
}

function i($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    return "<i$classAttr>$text</i>";
}

function bi($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    return "<b$classAttr><i>$text</i></b>";
}

function small($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    return "<small$classAttr>$text</small>";
}

function mark($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    return "<mark$classAttr>$text</mark>";
}


/* Block Elements */


function p($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    echo "<p$classAttr>$text</p>";
}

function span($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    echo "<span$classAttr>$text</span>";
}

function div($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    echo "<div$classAttr>$text</div>";
}

function section($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    echo "<section$classAttr>$text</section>";
}

/* pre code */

function code($content, $lang = '')
{
    $class = $lang ? " class=\"language-$lang\"" : '';
    echo "<pre><code$class>" . htmlspecialchars($content) . "</code></pre>";
}

function codejs($text)
{
    code($text, 'js');
}
function codephp($text)
{
    code($text, 'php');
}
function codejson($text)
{
    code($text, 'json');
}
function codehtml($text)
{
    code($text, 'html');
}
function codesql($text)
{
    code($text, 'sql');
}
function codebash($text)
{
    code($text, 'bash');
}
function codec($text)
{
    code($text, 'c');
}


/* Lists */
function ul(array $items, $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    echo "<ul$classAttr>";
    foreach ($items as $item) {
        echo "<li>$item</li>";
    }
    echo "</ul>";
}

function ul_open()
{
    echo "<ul>";
}
function ul_close()
{
    echo "</ul>";
}
function li($text)
{
    echo "<li>$text</li>";
}

/* Line Breaks */
function br(...$args)
{
    if (empty($args)) {
        echo '<br>';
    } else {
        foreach ($args as $arg) {
            echo '<br>' . $arg;
        }
    }
}

// Line after content
function bra(...$args)
{
    if (empty($args)) {
        echo '<br>';
    } else {
        foreach ($args as $arg) {
            echo $arg . '<br>';
        }
    }
}


/* Horizontal Rule before content  */
function hr(...$args)
{
    if (empty($args)) {
        echo '<hr>';
    } else {
        foreach ($args as $arg) {
            echo '<hr>' . $arg;
        }
    }
}


// Horizontal Rule after content
function hra(...$args)
{
    if (empty($args)) {
        echo '<hr>';
    } else {
        foreach ($args as $arg) {
            echo $arg . '<hr>';
        }
    }
}

/* Auto Counter 1. 2. 3. */

function c($text = '')
{
    static $counter = 1;
    echo $counter++ . '. ' . $text;
}

function c_str($text = '')
{
    static $counter = 1;
    return $counter++ . '. ' . $text;
}
