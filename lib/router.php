<?php
/**
 * Runs the script in the root directory most closely matching the request uri.
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
$root    = $_SERVER['ROUTER']['root'];
$prefix  = empty($_SERVER['ROUTER']['prefix']) ? null : $_SERVER['ROUTER']['prefix'];
$default = empty($_SERVER['ROUTER']['404'])    ? null : $_SERVER['ROUTER']['404'];

// normalize the requested uri
$uri = $_SERVER['REQUEST_URI'];
$q   = strpos($uri, '?');
$uri = $q === false ? $uri : substr($uri, 0, $q);            // remove querystring
$uri = urldecode($uri);                                      // undo uri encoding
$uri = preg_replace('#//+#', '/', $uri);                     // remove multi slashes
$uri = preg_replace('#(^|/)\\.(?=/|$)#', '', $uri);          // remove self directories
$uri = preg_replace('#(/[^/]+)?/\\.\\.(?=/|$)#', '', $uri);  // remove parent directories

// output uri
$_SERVER['ROUTER']['uri'] = $uri;

// remove route prefix from uri
if ($prefix) {
  $len = strlen($prefix);
  if (strncasecmp($uri, $prefix, $len) !== 0) {
    throw new RuntimeException("Request uri, '$uri', does not match expected prefix, '$prefix'");
  }
  $uri = substr($uri, $len);
}

// dissect route
$route = isset($uri[0]) && $uri[0] === '/' ? substr($uri, 1) : $uri;  // leading slash
$route = explode('/', $route);

// output route
$_SERVER['ROUTER']['route'] = $route;

// lower-case filenames
$route = array_map('strtolower', $route);

// binary find largest route directory
$low  = 0;
$high = count($route);
while ($high > $low) {
  $mid = $low + (int)ceil(($high - $low) / 2);
  $dir = $root.'/'.implode('/', array_slice($route, 0, $mid));
  if (is_dir($dir)) {
    $low = $mid;
  } else {
    $high = $mid - 1;
  }
}
$dir = $root.'/'.implode('/', array_slice($route, 0, $low));

// find a suitable script
$script = null;
if (isset($route[$low])) {
  $path = $dir.$route[$low].'.php';
  if (file_exists($path)) {
    $script = $path;
  }
}
if (!$script) {
  $path = "$dir/index.php";
  if (file_exists($path)) {
    $script = $path;
  } else {
    $script = $default;
  }
}

// output script
$_SERVER['ROUTER']['script'] = $script;

if (!$script) {
  throw new RuntimeException("No route script found for uri, '$uri'");
}

// run script
include $script;
