<?php

use iggyvolz\phppreproccessor\Preprocessor;
require_once __DIR__ . "/vendor/autoload.php";
echo new Preprocessor(__DIR__ . "/test.phpp");