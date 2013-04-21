<?php

require __DIR__ . '/src/VM.php';
require 'Log.php';

$logger = Log::factory('file', 'vm.log', 'Challenge VM');

$machine = new \SynacoreChallenge\VM();
$machine->setLogger($logger);
$machine->load(__DIR__ . '/challenge.bin');

try {
	$machine->run();
} catch (\Exception $e) {
	echo "\n\nCaught exception: " . $e->getMessage() . "\n\n";
}
