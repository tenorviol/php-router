<?php

$router = $_SERVER['ROUTER'];

// simplify the prefix dump tests
unset($router['prefix']);

echo json_encode($router);
