<?php

// This script triggers a PHP warning/notice
echo $undefinedVariable;
var_dump((object)['name' => 'Test']);
// echo "Script executed with an error\n";
throw new Exception('This is a test exception');
