<?php

namespace SynacoreChallenge;

class VM
{

	protected $_memory;
	protected $_registers;
	protected $_stack;
	protected $_programCounter = null;

	public function __construct($memorySize = 32768, $numberOfRegisters = 8)
	{
		$this->_memory = new \SplFixedArray($memorySize);
		$this->_registers = new \SplFixedArray($numberOfRegisters);
		$this->_stack = new \SplStack();
	}

	public function load($path)
	{
		$fp = fopen($path, 'r');
		$data = fread($fp, filesize($path));
		$instructions = unpack("v*", $data);

		if (count($instructions) > $this->_memory->getSize()) {
			throw new \Exception("This file won't fit in the VM memory");
		}

		for ($i = 1, $address = 0; $i <= count($instructions); $i++, $address++) {
			$this->_memory[$address] = $instructions[$i];
		}

		$this->_resetCounter();

		return true;
	}

	protected function _resetCounter()
	{
		$this->_programCounter = 0;
	}

	public function run()
	{
	}

	protected function _noop()
	{
		$this->_programCounter++;
	}
}
