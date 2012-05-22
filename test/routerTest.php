<?php

define('BASE', dirname(__DIR__));
define('ROOT', BASE.'/root');

class routerTest extends PHPUnit_Framework_TestCase {
  public function routeTests() {
    return array(
      array('/helloworld', 'Hello world!'),
      array('/', ROOT),
      array('/dump', json_encode(array(
        'root'   => ROOT,
        'uri'    => '/dump',
        'route'  => array('dump'),
        'script' => ROOT.'/dump.php',
      ))),
      array('/Dump/foobie/12345?orange=banana', json_encode(array(
        'root'   => ROOT,
        'uri'    => '/Dump/foobie/12345',
        'route'  => array('Dump', 'foobie', '12345'),
        'script' => ROOT.'/dump.php',
      ))),
    );
  }

  /**
   * @dataProvider routeTests
   */
  public function testRouter($uri, $output) {
    $_SERVER['REQUEST_URI'] = $uri;
    $this->expectOutputString($output);
    $_SERVER['ROUTER'] = array('root' => BASE.'/root');
    include BASE.'/lib/router.php';
  }

  public function testRouter404() {
    $_SERVER['REQUEST_URI'] = '/nowhere';
    $_SERVER['ROUTER'] = array(
      'root' => __DIR__,
      '404'  => ROOT.'/helloworld.php',
    );
    $this->expectOutputString('Hello world!');
    include BASE.'/lib/router.php';
  }

  /**
   * @expectedException RuntimeException
   */
  public function testRouterNoRoute() {
    $_SERVER['REQUEST_URI'] = '/nowhere';
    $_SERVER['ROUTER'] = array(
      'root' => __DIR__,
    );
    include BASE.'/lib/router.php';
  }
}