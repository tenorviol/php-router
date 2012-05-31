<?php
/**
 * Runs a script in the root directory most closely matching the request uri.
 *
 * Inputs:
 *
 *  $_SERVER['ROUTER']['root']     routed php script directory
 *  $_SERVER['ROUTER']['prefix']   URI prefix (optional)
 *  $_SERVER['ROUTER']['404']      not found script (optional)
 *
 * Outputs:
 *
 *  $_SERVER['ROUTER']['uri']      route path
 *  $_SERVER['ROUTER']['route']    exploded route path array
 *  $_SERVER['ROUTER']['script']   full path to the route script run
 */

// input
$uri     = $_SERVER['REQUEST_URI'];
$root    = $_SERVER['ROUTER']['root'];
$prefix  = empty($_SERVER['ROUTER']['prefix']) ? null : $_SERVER['ROUTER']['prefix'];
$default = empty($_SERVER['ROUTER']['404'])    ? null : $_SERVER['ROUTER']['404'];

// remove prefix from uri
if ($prefix) {
  $len = strlen($prefix);
  if (strncasecmp($uri, $prefix, $len) !== 0) {
    throw new RuntimeException("Request uri, '$uri', does not match expected prefix, '$prefix'", 500);
  }
  $uri = substr($uri, $len);
  if ($uri === false) {  // request uri === prefix
    $uri = '/';
  }
}

// ensure uri begins with leading slash
if ($uri[0] !== '/') {
  throw new RuntimeException("Request uri, '$uri', must begin with '/'", 500);
}

// normalize uri
$q   = strpos($uri, '?');
$uri = $q === false ? $uri : substr($uri, 0, $q);            // remove querystring
$uri = urldecode($uri);                                      // remove uri encoding
$uri = preg_replace('#//+#', '/', $uri);                     // remove multi slashes
$uri = preg_replace('#(^|/)\\.(?=/|$)#', '', $uri);          // remove self directories
$uri = preg_replace('#(/[^/]+)?/\\.\\.(?=/|$)#', '', $uri);  // remove parent directories

// filter non-utf8
// http://www.w3.org/International/questions/qa-forms-utf-8.en.php
if (!preg_match('/^('
      ."[\x20-\x7E]"                         # ASCII
      ."|[\xC2-\xDF][\x80-\xBF]"             # non-overlong 2-byte
      ."|\xE0[\xA0-\xBF][\x80-\xBF]"         # excluding overlongs
      ."|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}"  # straight 3-byte
      ."|\xED[\x80-\x9F][\x80-\xBF]"         # excluding surrogates
      ."|\xF0[\x90-\xBF][\x80-\xBF]{2}"      # planes 1-3
      ."|[\xF1-\xF3][\x80-\xBF]{3}"          # planes 4-15
      ."|\xF4[\x80-\x8F][\x80-\xBF]{2}"      # plane 16
      .')*$/', $uri)) {
  throw new RuntimeException("Request uri, '$uri', contains an invalid utf8 sequence", 500);
}

// route
$route = explode('/', $uri);

// output route
$_SERVER['ROUTER']['route']    = $route;
$_SERVER['ROUTER']['route'][0] = $uri;   // 0 element holds the full uri

// lower-case filenames
$route = array_map('strtolower', $route);  
$route[0] = $root;

// binary find largest route directory
$low  = 1;
$high = count($route);
while ($high > $low) {
  $mid = $low + (int)ceil(($high - $low) / 2);
  $dir = implode('/', array_slice($route, 0, $mid));
  if (is_dir($dir)) {
    $low = $mid;
  } else {
    $high = $mid - 1;
  }
}
$dir = implode('/', array_slice($route, 0, $low));

// named script
$script = null;
if (isset($route[$low])) {
  $path = $dir.'/'.$route[$low].'.php';
  if (file_exists($path)) {
    $script = $path;
  }
}

// index script
if (!$script) {
  $path = "$dir/index.php";
  if (file_exists($path)) {
    $script = $path;
  } else {
    // default script
    $script = $default;
  }
}

// output script
$_SERVER['ROUTER']['script'] = $script;

if (!$script) {
  throw new RuntimeException("No route for uri, '$uri'", 404);
}

// run script
include $script;
