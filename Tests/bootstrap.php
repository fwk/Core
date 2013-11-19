<?php
$vendorDir = __DIR__ . '/../vendor';

if (!@include($vendorDir . '/autoload.php')) {
    die("You must set up the project dependencies, run the following commands:
wget http://getcomposer.org/composer.phar
php composer.phar install
");
}

define("TEST_RESOURCES_DIR", __DIR__ . DIRECTORY_SEPARATOR . 'Resources');
