<?php

// This script triggers a PHP warning/notice
echo $undefinedVariable;
var_dump((object)['name' => 'Tests', 'memory' => sys_getloadavg()]);
throw new Exception('This is a test exception');
