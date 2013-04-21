<?php

require __DIR__ . '/src/VM.php';
require 'Log.php';

$level = PEAR_LOG_INFO;
$logger = Log::factory('file', 'vm.log', 'Challenge VM', null, $level);

$machine = new \SynacoreChallenge\VM();
$machine->setLogger($logger);
$machine->load(__DIR__ . '/challenge.bin');

try {
	$machine->run();
} catch (\Exception $e) {
	echo "\n\nCaught exception: " . $e->getMessage() . "\n\n";
}
