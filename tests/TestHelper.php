<?php

/**
 * Tests Helper file
 * 
 * Include this file from all test cases so we can properly run them
 */

// Set the include path
$baseDir = dirname(dirname(__FILE__));
set_include_path($baseDir . '/share/library' . PATH_SEPARATOR . get_include_path());

require_once 'PHPUnit/Framework/TestCase.php';

// Clean up
unset($baseDir);