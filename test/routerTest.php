<?php

define('BASE', dirname(__DIR__));
define('LIB' , BASE.'/lib');
define('ROOT', BASE.'/root');

class routerTest extends PHPUnit_Framework_TestCase {
  public function routeTests() {
    return array(
      array('/helloworld', 'Hello world!'),
      array('/', ROOT),
      array('/..', ROOT),
      array('/../..', ROOT),
      array('/../../..', ROOT),
      array('/../../../', ROOT),
      array('/a/b/c/d/../../../..', ROOT),
      array('/./././.', ROOT),
      array('/dump', json_encode(array(
        'root'   => ROOT,
        'route'  => array('/dump', 'dump'),
        'script' => ROOT.'/dump.php',
      ))),
      array('/Dump/foobie/12345?orange=banana', json_encode(array(
        'root'   => ROOT,
        'route'  => array('/Dump/foobie/12345', 'Dump', 'foobie', '12345'),
        'script' => ROOT.'/dump.php',
      ))),
      array('/nowhere/../Dump/foobie/12345?orange=banana', json_encode(array(
        'root'   => ROOT,
        'route'  => array('/Dump/foobie/12345', 'Dump', 'foobie', '12345'),
        'script' => ROOT.'/dump.php',
      ))),
      array('/中国', 'Middle Land'),
    );
  }

  /**
   * @dataProvider routeTests
   */
  public function testRouter($uri, $output) {
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['ROUTER'] = array('root' => ROOT);
    $this->expectOutputString($output);
    include LIB.'/router.php';
  }

  /**
   * @dataProvider routeTests
   */
  public function testRouterWithPrefix($uri, $output) {
    $prefix = '/'.uniqid();
    $_SERVER['REQUEST_URI'] = $prefix.$uri;
    $_SERVER['ROUTER'] = array(
      'root'   => ROOT,
      'prefix' => $prefix,
    );
    $this->expectOutputString($output);
    include LIB.'/router.php';
  }

  public function testRouterWithPrefixButNoSuffix() {
    $prefix = '/'.uniqid();
    $_SERVER['REQUEST_URI'] = $prefix;
    $_SERVER['ROUTER'] = array(
      'root'   => ROOT,
      'prefix' => $prefix,
    );
    $this->expectOutputString(ROOT);
    include LIB.'/router.php';
  }

  public function testRouter404() {
    $_SERVER['REQUEST_URI'] = '/nowhere';
    $_SERVER['ROUTER'] = array(
      'root' => __DIR__,
      '404'  => ROOT.'/helloworld.php',
    );
    $this->expectOutputString('Hello world!');
    include LIB.'/router.php';
  }

  /**
   * @expectedException RuntimeException
   */
  public function testRouterNoRoute() {
    $_SERVER['REQUEST_URI'] = '/nowhere';
    $_SERVER['ROUTER'] = array(
      'root' => __DIR__,
    );
    include LIB.'/router.php';
  }

  /**
   * @expectedException RuntimeException
   */
  public function testImproperPrefix() {
    $_SERVER['REQUEST_URI'] = '../lib/router.php';
    $_SERVER['ROUTER'] = array(
      'root' => __DIR__,
    );
    include LIB.'/router.php';
  }
}
