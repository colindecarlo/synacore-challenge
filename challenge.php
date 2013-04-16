<?php

require __DIR__ . '/src/VM.php';

$machine = new \SynacoreChallenge\VM();
$machine->load(__DIR__ . '/challenge.bin');

try {
	$machine->run();
} catch (\Exception $e) {
	echo "\n\nCaught exception: " . $e->getMessage() . "\n\n";
}
