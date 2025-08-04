<?php

/**
 * MicrofyPHP
 * microfy.php
 * v0.1.5
 * Author: SirCode
 */

// paths optional
// namespace Sircode\Microfy;
// use DOMDocument;

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
 *   db.php
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
    $sql  = "SELECT 1 FROM `$html_table` WHERE `$column` = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$value]);
    return $stmt->fetchColumn() !== false;
}

function db_insert(PDO $pdo, string $table, array $data)
{
    $cols = array_keys($data);
    $placeholders = array_fill(0, count($data), '?');
    $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    return $pdo->lastInsertId();
}

function db_update(PDO $pdo, string $table, array $data, string $where, array $params = [])
{
    $set = implode(', ', array_map(fn($col) => "`$col` = ?", array_keys($data)));
    $sql = "UPDATE `$table` SET $set WHERE $where";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute(array_merge(array_values($data), $params));
}


function db_delete(PDO $pdo, string $table, string $where, array $params = [])
{
    $sql = "DELETE FROM `$table` WHERE $where";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
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
### 🧩 Debug & Log Function Lexicon

| Function     | Description                           |
|--------------|---------------------------------------|
| `pp()`       | Pretty-print `print_r()`              |
| `ppd()`      | Pretty-print `print_r()` and `die()`  |
| `ppr()`      | Return string version of `print_r()`  |
| `pper()`     | Echo + return string from `print_r()` |
| `pd()`       | Pretty-print `var_dump()`             |
| `pdd()`      | Pretty-print `var_dump()` and `die()` |
| `pdr()`      | Return string version of `var_dump()` |
| `d()`        | Quick `var_dump()` (one or many)      |
| `dd()`       | Quick `var_dump()` and `die()`        |
| `mlog()`     | Log a plain string                    |
| `log_pr()`   | Log `print_r()` output                |
| `log_vd()`   | Log `var_dump()` output               |

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
        $lines  = explode("\n", $output);
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
    if ($label) {
        echo "$label:\n";
    }

    var_dump($var);
    echo "</pre>";
    return ob_get_clean();
}

// --- Simple dump shortcuts
function d(...$args)
{
    foreach ($args as $arg) {
        echo pdr($arg);
    }

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
    if (! file_exists($file)) {
        return null;
    }

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
    if (! preg_match('#^https?://#', $href)) {
        $href = "https://$href";
    }

    $text = $text ?? $href;

    $targetAttr = $target ? " target=\"$target\"" : '';
    $classAttr  = $class ? " class=\"$class\"" : '';

    return "<a href=\"$href\"$targetAttr$classAttr>$text</a>";
}

function build_html_table_safe($array, $class = '', $id = '')
{
    if (empty($array)) {
        return "<p><em>No data.</em></p>";
    }

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

function build_html_table(array $rows, array $allow_raw_cols = [], string $cssClass = '', string $id = '')
{

    $array = $rows;
    $class = $cssClass;

    if (empty($array)) {
        return "<p><em>No data.</em></p>";
    }

    $idAttr   = $id !== '' ? " id='" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "'" : '';
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
    $first   = reset($rows);
    $isAssoc = array_keys($first) !== range(0, count($first) - 1);

    // Build headers
    if ($isAssoc) {
        $headers = array_keys($first);
    } else {
        // indexed rows: numeric columns → use 1-based labels or empty
        $headers = array_map(fn($i) => "Col{$i}", array_keys($first));
    }
    $thCells = array_map(fn($h) => tag('th', htmlspecialchars((string) $h)), $headers);
    $thead   = tag('thead', tag('tr', $thCells));

    // Build body
    $bodyRows = [];
    foreach ($rows as $row) {
        $tds = [];
        foreach ($headers as $i => $col) {
            $cell    = $isAssoc ? ($row[$col] ?? '') : ($row[$i] ?? '');
            $key     = $isAssoc ? $col : $i;
            $raw     = in_array($key, $allow_raw_cols, true);
            $content = $raw
            ? $cell
            : htmlspecialchars((string) $cell, ENT_QUOTES);
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
function clist(array $items, $reset = false)
{
    static $counter = 1;
    if ($reset) {
        $counter = 1;
    }

    $html = '';
    foreach ($items as $item) {
        $html .= $counter++ . '. ' . $item . '<br>';
    }
    return $html;
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
    if (! defined($name)) {
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
    return htmlspecialchars($str);
}

function sendJson($data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function ok($msg = 'OK')
{
    sendJson(['status' => 'ok', 'msg' => $msg]);
}

function fail($msg = 'Error')
{
    sendJson(['status' => 'fail', 'msg' => $msg]);
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
    $level     = max(1, min(6, (int) $level));
    $classAttr = $class ? " class=\"$class\"" : '';
    return "<h$level$classAttr>$text</h$level>";
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
    return "<p$classAttr>$text</p>";
}

function span($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    return "<span$classAttr>$text</span>";
}

function div($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    return "<div$classAttr>$text</div>";
}

function section($text = '', $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    return "<section$classAttr>$text</section>";
}

/* pre code */

function code($content, $lang = '')
{
    $class = $lang ? " class=\"language-$lang\"" : '';
    return "<pre><code$class>" . htmlspecialchars($content) . "</code></pre>";
}

function codejs($text)
{
    return code($text, 'js');
}
function codephp($text)
{
    return code($text, 'php');
}
function codejson($text)
{
    return code($text, 'json');
}
function codehtml($text)
{
    return code($text, 'html');
}
function codesql($text)
{
    return code($text, 'sql');
}
function codebash($text)
{
    return code($text, 'bash');
}
function codec($text)
{
    return code($text, 'c');
}

function dedent($text)
{
    $lines  = explode("\n", $text);
    $indent = null;
    foreach ($lines as $line) {
        if (trim($line) === '') {
            continue;
        }

        preg_match('/^\s*/', $line, $match);
        $spaces = strlen($match[0]);
        $indent = $indent === null ? $spaces : min($indent, $spaces);
    }
    if ($indent > 0) {
        $lines = array_map(fn($line) => substr($line, $indent), $lines);
    }
    return implode("\n", $lines);
}

/* Lists */
function ul(array $items, $class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    $html      = "<ul$classAttr>";
    foreach ($items as $item) {
        $html .= "<li>$item</li>";
    }
    $html .= "</ul>";
    return $html;
}

function ul_open($class = '')
{
    $classAttr = $class ? " class=\"$class\"" : '';
    return "<ul$classAttr>";
}

function ul_close()
{
    return "</ul>";
}

function li($text)
{
    return "<li>$text</li>";
}

/*  */

function br(...$args)
{
    if (empty($args)) {
        return '<br>';
    }

    $html = '';
    foreach ($args as $arg) {
        $html .= '<br>' . $arg;
    }
    return $html;
}

function bra(...$args)
{
    if (empty($args)) {
        return '<br>';
    }

    $html = '';
    foreach ($args as $arg) {
        $html .= $arg . '<br>';
    }
    return $html;
}

function hr(...$args)
{
    if (empty($args)) {
        return '<hr>';
    }

    $html = '';
    foreach ($args as $arg) {
        $html .= '<hr>' . $arg;
    }
    return $html;
}

function hra(...$args)
{
    if (empty($args)) {
        return '<hr>';
    }

    $html = '';
    foreach ($args as $arg) {
        $html .= $arg . '<hr>';
    }
    return $html;
}

/* Auto Counter 1. 2. 3. */

function c($text = '')
{
    static $counter = 1;
    return $counter++ . '. ' . $text;
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
        $attrStrings[] = sprintf('%s="%s"', $k, htmlspecialchars((string) $v, ENT_QUOTES));
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
    return html_tag('p', htmlspecialchars((string) $content), $attrs);
}
function html_blockquote($content = '', array $attrs = []): string
{
    return html_tag('blockquote', $content, $attrs);
}
function html_pre($content = '', array $attrs = []): string
{
    return html_tag('pre', htmlspecialchars((string) $content), $attrs);
}
function html_code($content = '', array $attrs = []): string
{
    return html_tag('code', htmlspecialchars((string) $content), $attrs);
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
        $children[] = tag('dt', htmlspecialchars((string) $t['term']));
        $children[] = tag('dd', htmlspecialchars((string) $t['desc']));
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
    return html_tag('label', htmlspecialchars((string) $content), $attrs);
}
function html_input(array $attrs = []): string
{
    return html_tag('input', '', $attrs, true);
}
function html_textarea($content = '', array $attrs = []): string
{
    return html_tag('textarea', htmlspecialchars((string) $content), $attrs);
}
function html_select(array $options, array $attrs = []): string
{
    $opts = [];
    foreach ($options as $value => $text) {
        $opts[] = tag('option', htmlspecialchars((string) $text), ['value' => (string) $value]);
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

/* echo aliases */

function e_get_r(...$args)
{echo get_r(...$args);}
function e_v(...$args)
{echo v(...$args);}
function e_get_var(...$args)
{echo get_var(...$args);}
function e_post_var(...$args)
{echo post_var(...$args);}
function e_request_var(...$args)
{echo request_var(...$args);}
// DB:
function e_db_one(...$args)
{echo db_one(...$args);}
function e_db_insert_id(...$args)
{echo db_insert_id(...$args);}
function e_db_error(...$args)
{echo db_error(...$args);}
function e_db_count(...$args)
{echo db_count(...$args);}
function e_db_val(...$args)
{echo db_val(...$args);}

// Optional:
function e_db_exists(...$args)
{echo db_exists(...$args);}

function e_ppr(...$args)
{echo ppr(...$args);}
function e_pdr(...$args)
{echo pdr(...$args);}

function e_debug_session(...$args)
{echo debug_session(...$args);}
function e_env(...$args)
{echo env(...$args);}
function e_now(...$args)
{echo now(...$args);}

function e_climb_dir(...$args)
{echo climb_dir(...$args);}
function e_clist(...$args)
{echo clist(...$args);}
function e_def(...$args)
{echo def(...$args);}

function e_ok(...$args)
{echo ok(...$args);}
function e_fail(...$args)
{echo fail(...$args);}
function e_slugify(...$args)
{echo slugify(...$args);}

function e_c_str(...$args)
{echo c_str(...$args);}
function e_hsc(...$args)
{echo hsc(...$args);}
function e_a(...$args)
{echo a(...$args);}
function e_h(...$args)
{echo h(...$args);}
function e_b(...$args)
{echo b(...$args);}
function e_i(...$args)
{echo i(...$args);}
function e_bi(...$args)
{echo bi(...$args);}
function e_small(...$args)
{echo small(...$args);}
function e_mark(...$args)
{echo mark(...$args);}
function e_p(...$args)
{echo p(...$args);}
function e_span(...$args)
{echo span(...$args);}
function e_div(...$args)
{echo div(...$args);}
function e_section(...$args)
{echo section(...$args);}
function e_code(...$args)
{echo code(...$args);}
function e_codejs(...$args)
{echo codejs(...$args);}
function e_codephp(...$args)
{echo codephp(...$args);}
function e_codejson(...$args)
{echo codejson(...$args);}
function e_codehtml(...$args)
{echo codehtml(...$args);}
function e_codesql(...$args)
{echo codesql(...$args);}
function e_codebash(...$args)
{echo codebash(...$args);}
function e_codec(...$args)
{echo codec(...$args);}
function e_ul(...$args)
{echo ul(...$args);}
function e_ul_open(...$args)
{echo ul_open(...$args);}
function e_ul_close(...$args)
{echo ul_close(...$args);}
function e_li(...$args)
{echo li(...$args);}
function e_br(...$args)
{echo br(...$args);}
function e_bra(...$args)
{echo bra(...$args);}
function e_hr(...$args)
{echo hr(...$args);}
function e_hra(...$args)
{echo hra(...$args);}
function e_c(...$args)
{echo c(...$args);}
function e_tag(...$args)
{echo tag(...$args);}
function e_html_tag(...$args)
{echo html_tag(...$args);}
function e_html_html(...$args)
{echo html_html(...$args);}
function e_html_head(...$args)
{echo html_head(...$args);}
function e_html_body(...$args)
{echo html_body(...$args);}
function e_html_header(...$args)
{echo html_header(...$args);}
function e_html_footer(...$args)
{echo html_footer(...$args);}
function e_html_section(...$args)
{echo html_section(...$args);}
function e_html_article(...$args)
{echo html_article(...$args);}
function e_html_nav(...$args)
{echo html_nav(...$args);}
function e_html_aside(...$args)
{echo html_aside(...$args);}
function e_html_div(...$args)
{echo html_div(...$args);}
function e_html_span(...$args)
{echo html_span(...$args);}
function e_html_h1(...$args)
{echo html_h1(...$args);}
function e_html_h2(...$args)
{echo html_h2(...$args);}
function e_html_h3(...$args)
{echo html_h3(...$args);}
function e_html_h4(...$args)
{echo html_h4(...$args);}
function e_html_h5(...$args)
{echo html_h5(...$args);}
function e_html_h6(...$args)
{echo html_h6(...$args);}
function e_html_p(...$args)
{echo html_p(...$args);}
function e_html_blockquote(...$args)
{echo html_blockquote(...$args);}
function e_html_pre(...$args)
{echo html_pre(...$args);}
function e_html_code(...$args)
{echo html_code(...$args);}
function e_html_ul(...$args)
{echo html_ul(...$args);}
function e_html_ol(...$args)
{echo html_ol(...$args);}
function e_html_li(...$args)
{echo html_li(...$args);}
function e_html_dl(...$args)
{echo html_dl(...$args);}
function e_html_table(...$args)
{echo html_table(...$args);}

/*  */
function e_build_html_table(...$args)
{echo build_html_table(...$args);}
function e_build_html_table_safe(...$args)
{echo build_html_table_safe(...$args);}
function e_build_html_table_universal(...$args)
{echo build_html_table_universal(...$args);}

/*  */

function e_html_thead(...$args)
{echo html_thead(...$args);}
function e_html_tbody(...$args)
{echo html_tbody(...$args);}
function e_html_tr(...$args)
{echo html_tr(...$args);}
function e_html_th(...$args)
{echo html_th(...$args);}
function e_html_td(...$args)
{echo html_td(...$args);}
function e_html_form(...$args)
{echo html_form(...$args);}
function e_html_label(...$args)
{echo html_label(...$args);}
function e_html_input(...$args)
{echo html_input(...$args);}
function e_html_textarea(...$args)
{echo html_textarea(...$args);}
function e_html_select(...$args)
{echo html_select(...$args);}
function e_html_button(...$args)
{echo html_button(...$args);}
function e_html_br(...$args)
{echo html_br(...$args);}
function e_html_hr(...$args)
{echo html_hr(...$args);}
function e_html_img(...$args)
{echo html_img(...$args);}
function e_html_meta(...$args)
{echo html_meta(...$args);}
function e_html_link(...$args)
{echo html_link(...$args);}
function e_html_script(...$args)
{echo html_script(...$args);}
function e_html_style(...$args)
{echo html_style(...$args);}
/*  */
function e_pretty_html(...$args)
{echo pretty_html(...$args);}

function e(...$parts)
{
    foreach ($parts as $part) {
        echo $part;
    }
}
