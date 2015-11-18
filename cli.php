<?php

require_once 'lib/Console.php';
require_once 'lib/Db.php';
require_once 'app/App.php';
require_once 'app/Migration.php';

$console = new Console($argv);
$db = new Db('localhost', 'root', 'root', 'traffics_task');

$app = new App();
$app->run($console, $db);