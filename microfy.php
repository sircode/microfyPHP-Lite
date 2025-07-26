<?php

/**
 * microfyPHP
 * microfy.php
 * v0.1.4 
 * Author: SirCode
 */

// paths
namespace Sircode\Microfy;
use DOMDocument;
/**
 * ──────────────────────────────────────────────────────────────────────────────
 *   arrays.php
 * ──────────────────────────────────────────────────────────────────────────────
 */

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

/**
 * ──────────────────────────────────────────────────────────────────────────────
 *   db.php - General array accessor
 * ──────────────────────────────────────────────────────────────────────────────
 */

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

function db_exists(PDO $pdo, string $html_table, string $column, $value)
{
    $sql = "SELECT 1 FROM `$html_table` WHERE `$column` = ? LIMIT 1";
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

/**
 * ──────────────────────────────────────────────────────────────────────────────
 *   debug.php
 * ──────────────────────────────────────────────────────────────────────────────
 */


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



/**
 * ──────────────────────────────────────────────────────────────────────────────
 *   env.php
 * ──────────────────────────────────────────────────────────────────────────────
 */
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


/**
 * ──────────────────────────────────────────────────────────────────────────────
 *   html.php
 * ──────────────────────────────────────────────────────────────────────────────
 */
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

function build_html_table_safe($array, $class = '', $id = '')
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

function build_html_table(array $rows, array $allow_raw_cols = [], string $cssClass  = '', string $id = '')
{

    $array = $rows;
    $class = $cssClass;

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


/**
 * Universal table builder.
 *
 * @param array          $rows             List of rows (
 *                                         either indexed arrays or associative arrays
 *                                       )
 * @param array|string[] $allow_raw_cols   Keys (for assoc rows) or column‑indexes 
 *                                         (for indexed rows) to skip escaping
 * @param array|string   $attrs            Either a string of CSS classes, or an array 
 *                                         of HTML attributes (class, id, data-*)
 * @return string
 */
function build_html_table_universal(
    array $rows,
    array $allow_raw_cols = [],
    $attrs = []
): string {
    if (empty($rows)) {
        return tag('p', tag('em', 'No data.'));
    }

    // Normalize $attrs into an attribute array
    if (is_string($attrs)) {
        $attrs = ['class' => $attrs];
    }

    // Decide if rows are associative or indexed
    $first = reset($rows);
    $isAssoc = array_keys($first) !== range(0, count($first) - 1);

    // Build headers
    if ($isAssoc) {
        $headers = array_keys($first);
    } else {
        // indexed rows: numeric columns → use 1-based labels or empty
        $headers = array_map(fn($i) => "Col{$i}", array_keys($first));
    }
    $thCells = array_map(fn($h) => tag('th', htmlspecialchars((string)$h)), $headers);
    $thead   = tag('thead', tag('tr', $thCells));

    // Build body
    $bodyRows = [];
    foreach ($rows as $row) {
        $tds = [];
        foreach ($headers as $i => $col) {
            $cell = $isAssoc ? ($row[$col] ?? '') : ($row[$i] ?? '');
            $key  = $isAssoc ? $col : $i;
            $raw  = in_array($key, $allow_raw_cols, true);
            $content = $raw
                ? $cell
                : htmlspecialchars((string)$cell, ENT_QUOTES);
            $tds[] = tag('td', $content);
        }
        $bodyRows[] = tag('tr', $tds);
    }
    $tbody = tag('tbody', $bodyRows);

    return tag('table', $thead . $tbody, $attrs);
}



/**
 * ──────────────────────────────────────────────────────────────────────────────
 *   other.php
 * ──────────────────────────────────────────────────────────────────────────────
 */


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


// --- list counter ---
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


/**
 * ──────────────────────────────────────────────────────────────────────────────
 *   response.php
 * ──────────────────────────────────────────────────────────────────────────────
 */

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


/**
 * ──────────────────────────────────────────────────────────────────────────────
 *   strings.php
 * ──────────────────────────────────────────────────────────────────────────────
 */

function slugify($string)
{
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    return trim($string, '-');
}


/**
 * ──────────────────────────────────────────────────────────────────────────────
 *   style.php
 * ──────────────────────────────────────────────────────────────────────────────
 */

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

/**
 * ──────────────────────────────────────────────────────────────────────────────
 *   html_helpers.php
 * ──────────────────────────────────────────────────────────────────────────────
 */

// Core wrappers (you’d have defined these already)
function tag(string $tag, $content = '', array $attrs = [], bool $selfClose = false): string
{
    $attrStrings = [];
    foreach ($attrs as $k => $v) {
        $attrStrings[] = sprintf('%s="%s"', $k, htmlspecialchars((string)$v, ENT_QUOTES));
    }
    $attrString = $attrStrings ? ' ' . implode(' ', $attrStrings) : '';
    if ($selfClose) {
        return "<{$tag}{$attrString} />";
    }
    if (is_array($content)) {
        $content = implode('', $content);
    }
    return "<{$tag}{$attrString}>{$content}</{$tag}>";
}

function html_tag(string $tag, $content = '', array $attrs = [], bool $selfClose = false): string
{
    return tag($tag, $content, $attrs, $selfClose);
}

// Basic paired elements
function html_html($content = '', array $attrs = []): string
{
    return html_tag('html', $content, $attrs);
}
function html_head($content = '', array $attrs = []): string
{
    return html_tag('head', $content, $attrs);
}
function html_body($content = '', array $attrs = []): string
{
    return html_tag('body', $content, $attrs);
}
function html_header($content = '', array $attrs = []): string
{
    return html_tag('header', $content, $attrs);
}
function html_footer($content = '', array $attrs = []): string
{
    return html_tag('footer', $content, $attrs);
}
function html_section($content = '', array $attrs = []): string
{
    return html_tag('section', $content, $attrs);
}
function html_article($content = '', array $attrs = []): string
{
    return html_tag('article', $content, $attrs);
}
function html_nav($content = '', array $attrs = []): string
{
    return html_tag('nav', $content, $attrs);
}
function html_aside($content = '', array $attrs = []): string
{
    return html_tag('aside', $content, $attrs);
}

function html_div($content = '', array $attrs = []): string
{
    return html_tag('div', $content, $attrs);
}
function html_span($content = '', array $attrs = []): string
{
    return html_tag('span', $content, $attrs);
}

function html_h1($content = '', array $attrs = []): string
{
    return html_tag('h1', $content, $attrs);
}
function html_h2($content = '', array $attrs = []): string
{
    return html_tag('h2', $content, $attrs);
}
function html_h3($content = '', array $attrs = []): string
{
    return html_tag('h3', $content, $attrs);
}
function html_h4($content = '', array $attrs = []): string
{
    return html_tag('h4', $content, $attrs);
}
function html_h5($content = '', array $attrs = []): string
{
    return html_tag('h5', $content, $attrs);
}
function html_h6($content = '', array $attrs = []): string
{
    return html_tag('h6', $content, $attrs);
}

function html_p($content = '', array $attrs = []): string
{
    return html_tag('p', htmlspecialchars((string)$content), $attrs);
}
function html_blockquote($content = '', array $attrs = []): string
{
    return html_tag('blockquote', $content, $attrs);
}
function html_pre($content = '', array $attrs = []): string
{
    return html_tag('pre', htmlspecialchars((string)$content), $attrs);
}
function html_code($content = '', array $attrs = []): string
{
    return html_tag('code', htmlspecialchars((string)$content), $attrs);
}

function html_ul(array $items, array $attrs = []): string
{
    $lis = array_map(fn($i) => tag('li', $i), $items);
    return html_tag('ul', $lis, $attrs);
}
function html_ol(array $items, array $attrs = []): string
{
    $lis = array_map(fn($i) => tag('li', $i), $items);
    return html_tag('ol', $lis, $attrs);
}
function html_li($content = '', array $attrs = []): string
{
    return html_tag('li', $content, $attrs);
}

function html_dl(array $terms, array $attrs = []): string
{
    // $terms = [['term'=>'T','desc'=>'D'], ...]
    $children = [];
    foreach ($terms as $t) {
        $children[] = tag('dt', htmlspecialchars((string)$t['term']));
        $children[] = tag('dd', htmlspecialchars((string)$t['desc']));
    }
    return html_tag('dl', $children, $attrs);
}

// Table elements
function html_table($content = '', array $attrs = []): string
{
    return html_tag('table', $content, $attrs);
}
function html_thead($content = '', array $attrs = []): string
{
    return html_tag('thead', $content, $attrs);
}
function html_tbody($content = '', array $attrs = []): string
{
    return html_tag('tbody', $content, $attrs);
}
function html_tr($content = '', array $attrs = []): string
{
    return html_tag('tr', $content, $attrs);
}
function html_th($content = '', array $attrs = []): string
{
    return html_tag('th', $content, $attrs);
}
function html_td($content = '', array $attrs = []): string
{
    return html_tag('td', $content, $attrs);
}



// Form elements
function html_form($content = '', array $attrs = []): string
{
    return html_tag('form', $content, $attrs);
}
function html_label($content = '', array $attrs = []): string
{
    return html_tag('label', htmlspecialchars((string)$content), $attrs);
}
function html_input(array $attrs = []): string
{
    return html_tag('input', '', $attrs, true);
}
function html_textarea($content = '', array $attrs = []): string
{
    return html_tag('textarea', htmlspecialchars((string)$content), $attrs);
}
function html_select(array $options, array $attrs = []): string
{
    $opts = [];
    foreach ($options as $value => $text) {
        $opts[] = tag('option', htmlspecialchars((string)$text), ['value' => (string)$value]);
    }
    return html_tag('select', $opts, $attrs);
}
function html_button($content = '', array $attrs = []): string
{
    return html_tag('button', $content, $attrs);
}

// Self‑closing elements
function html_br(array $attrs = []): string
{
    return html_tag('br', '', $attrs, true);
}
function html_hr(array $attrs = []): string
{
    return html_tag('hr', '', $attrs, true);
}
function html_img(array $attrs = []): string
{
    return html_tag('img', '', $attrs, true);
}
function html_meta(array $attrs = []): string
{
    return html_tag('meta', '', $attrs, true);
}
function html_link(array $attrs = []): string
{
    return html_tag('link', '', $attrs, true);
}
function html_script($content = '', array $attrs = []): string
{
    return html_tag('script', $content, $attrs);
}
function html_style($content = '', array $attrs = []): string
{
    return html_tag('style', $content, $attrs);
}

/**
 * Pretty‑print a chunk of HTML.
 *
 * @param string $html Unformatted HTML fragment (returned by your helpers).
 * @return string Formatted HTML with line breaks and indentation.
 */
function pretty_html(string $html): string
{
    $dom = new DOMDocument();

    // Tidy up whitespace and force formatting
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput       = true;

    // Load fragment (use HTML-ENTITIES hack for UTF‑8)
    @$dom->loadHTML(
        '<?xml encoding="utf-8"?>'
            . $html,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    // Save and strip the XML declaration
    $out = $dom->saveHTML();
    return preg_replace('/^<\?xml.*?\?>\s*/', '', $out);
}



