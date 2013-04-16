<?php

namespace SynacoreChallenge;

class VM
{
	const TOTAL_OPERATIONS = 22;

	protected $_memory;
	protected $_registers;
	protected $_stack;
	protected $_opCodes;
	protected $_programCounter = null;

	public function __construct($memorySize = 32768, $numberOfRegisters = 8)
	{
		$this->_memory = new \SplFixedArray($memorySize);
		$this->_registers = $this->_initRegisters($numberOfRegisters);
		$this->_opCodes = $this->_initOpCodes();
		$this->_stack = new \SplStack();
	}

	protected function _initOpCodes()
	{
		$opCodes = new \SplFixedArray(self::TOTAL_OPERATIONS);

		$opCodes[0] = 'halt';
		$opCodes[19] = 'out';
		$opCodes[21] = 'noop';

		return $opCodes;
	}

	protected function _initRegisters($numberOfRegisters)
	{
		$registers = new \SplFixedArray($numberOfRegisters);
		for ($i=0; $i < $registers->getSize(); $i++) {
			$registers[$i] = 0;
		}

		return $registers;
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
		while (true) {
			$opCode = $this->_getNextOpCode();
			$operation = $this->_opCodes[$opCode];

			if (null === $operation) {
				throw new \Exception(sprintf("Undefined opCode (%s)! Write moar code!", $opCode));
			}

			$method = '_' . $operation;
			$this->{$method}();
		}
	}

	protected function _getNextOpCode()
	{
		return $this->_getNextInstruction();
	}

	protected function _getNextInstruction()
	{
		$instruction = $this->_memory[$this->_programCounter];
		$this->_programCounter++;

		return $instruction;
	}

	/**
	 * halt: 0
	 *   stop execution and terminate the program
	 */
	protected function _halt()
	{
		exit();
	}

	/**
	 * out: 19 a
	 *   write the character represented by ascii code <a> to the terminal
	 */
	protected function _out()
	{
		$data = $this->_getNextInstruction();
		echo chr($data);
	}

	/**
	 * noop: 21
	 *   no operation
	 */
	protected function _noop()
	{
		return;
	}
}
