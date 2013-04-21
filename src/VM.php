<?php

namespace SynacoreChallenge;

class VM
{
	const TOTAL_OPERATIONS = 22;
	const MAX_INT = 32767;

	protected $_memory;
	protected $_registers;
	protected $_numberOfRegisters;
	protected $_stack;
	protected $_opCodes;
	protected $_programCounter = null;
	protected $_modulo;

	protected $_logger = null;

	public function __construct($memorySize = 32768, $numberOfRegisters = 8)
	{
		$this->_memory = new \SplFixedArray($memorySize);
		$this->_numberOfRegisters = 8;
		$this->_registers = $this->_initRegisters();
		$this->_opCodes = $this->_initOpCodes();
		$this->_stack = new \SplStack();
		$this->_modulo = self::MAX_INT + 1;
	}

	public function setLogger(\Log $logger)
	{
		$this->_logger = $logger;
	}

	protected function _log($message, $level = PEAR_LOG_INFO)
	{
		if ($this->_logger) {
			$this->_logger->log($message, $level);
		}
	}

	protected function _initOpCodes()
	{
		$opCodes = new \SplFixedArray(self::TOTAL_OPERATIONS);

		$opCodes[0] = 'halt';
		$opCodes[1] = 'set';
		$opCodes[2] = 'push';
		$opCodes[3] = 'pop';
		$opCodes[4] = 'eq';
		$opCodes[5] = 'gt';
		$opCodes[6] = 'jmp';
		$opCodes[7] = 'jt';
		$opCodes[8] = 'jf';
		$opCodes[9] = 'add';
		$opCodes[10] = 'mult';
		$opCodes[11] = 'mod';
		$opCodes[12] = 'and';
		$opCodes[13] = 'or';
		$opCodes[14] = 'not';
		$opCodes[15] = 'rmem';
		$opCodes[16] = 'wmem';
		$opCodes[17] = 'call';
		$opCodes[18] = 'ret';
		$opCodes[19] = 'out';
		$opCodes[20] = 'in';
		$opCodes[21] = 'noop';

		return $opCodes;
	}

	protected function _initRegisters()
	{
		$registers = new \SplFixedArray($this->_numberOfRegisters);
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
		$instruction = $this->_getRawInstruction();

		if ($instruction <= self::MAX_INT) {
			return $instruction;
		} else if ($this->_isRegister($instruction)) {
			$register = $this->_getRegisterId($instruction);
			return $this->_registers[$register];
		}

		throw new \Exception("Invalid Instruction %s", $instruction);
	}

	protected function _isRegister($instruction)
	{
		return $instruction > self::MAX_INT && $instruction <= self::MAX_INT + $this->_numberOfRegisters;
	}

	protected function _getRawInstruction()
	{
		$instruction = $this->_memory[$this->_programCounter];
		$this->_log(sprintf("[PC %s] instruction %s", $this->_programCounter, $instruction), PEAR_LOG_DEBUG);
		$this->_programCounter++;

		return $instruction;
	}

	protected function _getRegisterId($instruction)
	{
		$registerZeroAddress = self::MAX_INT + 1;
		return $instruction - $registerZeroAddress;
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
	 * set: 1 a b
	 *   set register <a> to the value of <b>
	 */
	protected function _set()
	{
		$targetRegister = $this->_getTargetRegister();
		$value = $this->_getNextInstruction();

		$this->_setRegister($targetRegister, $value);
	}

	protected function _setRegister($target, $value)
	{
		$this->_registers[$target] = $value;
	}

	protected function _getTargetRegister()
	{
		$instruction = $this->_getRawInstruction();

		if ($instruction <= 32767 || $instruction > 32775) {
			throw new \Exception("Invalid register address %s", $instruction);
		}

		return $this->_getRegisterId($instruction);
	}

	/**
	 * push: 2 a
	 *   push <a> onto the stack
	 */
	protected function _push()
	{
		$value = $this->_getNextInstruction();
		$this->_stack->push($value);
	}

	/**
	 * pop: 3 a
	 *   remove the top element from the stack and write it into <a>; empty stack = error
	 */
	protected function _pop()
	{
		$targetRegister = $this->_getTargetRegister();
		try {
			$value = $this->_stack->pop();
			$this->_setRegister($targetRegister, $value);
		} catch (\RuntimeException $e) {
			printf("ERROR - The stack is empty.\n");
			$this->_halt();
		}
	}

	/**
	 * eq: 4 a b c
	 *   set <a> to 1 if <b> is equal to <c>; set it to 0 otherwise
	 */
	protected function _eq()
	{
		$targetRegister = $this->_getTargetRegister();
		$leftSide = $this->_getNextInstruction();
		$rightSide = $this->_getNextInstruction();

		$result = $leftSide == $rightSide ? 1 : 0;
		$this->_setRegister($targetRegister, $result);
	}

	/**
	 * gt: 5 a b c
	 *   set <a> to 1 if <b> is greater than <c>; set it to 0 otherwise
	 */
	protected function _gt()
	{
		$targetRegister = $this->_getTargetRegister();
		$leftSide = $this->_getNextInstruction();
		$rightSide = $this->_getNextInstruction();

		$result = $leftSide > $rightSide ? 1 : 0;
		$this->_setRegister($targetRegister, $result);
	}

	/**
	 * jmp: 6 a
	 *   jump to <a>
	 */
	protected function _jmp()
	{
		$nextAddress = $this->_getNextInstruction();
		$this->_setProgramCounter($nextAddress);
	}

	protected function _setProgramCounter($address)
	{
		$this->_programCounter = $address;
	}


	/**
	 * jt: 7 a b
	 *   if <a> is nonzero, jump to <b>
	 */
	protected function _jt()
	{
		$test = (int)$this->_getNextInstruction();
		$nextAddress = $this->_getNextInstruction();

		if ($test !== 0) {
			$this->_setProgramCounter($nextAddress);
		}
	}

	/**
	 * jf: 8 a b
	 *   if <a> is zero, jump to <b>
	 */
	protected function _jf()
	{
		$test = (int)$this->_getNextInstruction();
		$nextAddress = $this->_getNextInstruction();

		if ($test === 0) {
			$this->_setProgramCounter($nextAddress);
		}
	}

	/**
	 * add: 9 a b c
	 *   assign into <a> the sum of <b> and <c> (modulo 32768)
	 */
	protected function _add()
	{
		$targetRegister = $this->_getTargetRegister();
		$value1 = $this->_getNextInstruction();
		$value2 = $this->_getNextInstruction();

		$sum = ($value1 + $value2) % $this->_modulo;
		$this->_setRegister($targetRegister, $sum);
	}

	/**
	 * mult: 10 a b c
	 *   store into <a> the product of <b> and <c> (modulo 32768)
	 */
	protected function _mult()
	{
		$targetRegister = $this->_getTargetRegister();
		$value1 = $this->_getNextInstruction();
		$value2 = $this->_getNextInstruction();

		$product = ($value1 * $value2) % $this->_modulo;
		$this->_setRegister($targetRegister, $product);
	}
		
	/**
	 * mod: 11 a b c
	 *   store into <a> the remainder of <b> divided by <c>
	 */
	protected function _mod()
	{
		$targetRegister = $this->_getTargetRegister();
		$value1 = $this->_getNextInstruction();
		$value2 = $this->_getNextInstruction();

		$remainder = $value1 % $value2;
		$this->_setRegister($targetRegister, $remainder);
	}

	/**
	 * and: 12 a b c
	 *   stores into <a> the bitwise and of <b> and <c>
	 */
	protected function _and()
	{
		$targetRegister = $this->_getTargetRegister();
		$value1 = $this->_getNextInstruction();
		$value2 = $this->_getNextInstruction();

		$result = $value1 & $value2;
		$this->_setRegister($targetRegister, $result);
	}

	/**
	 * or: 13 a b c
	 *   stores into <a> the bitwise or of <b> and <c>
	 */
	protected function _or()
	{
		$targetRegister = $this->_getTargetRegister();
		$value1 = $this->_getNextInstruction();
		$value2 = $this->_getNextInstruction();

		$result = $value1 | $value2;
		$this->_setRegister($targetRegister, $result);
	}

	/**
	 * not: 14 a b
	 *   stores 15-bit bitwise inverse of <b> in <a>
	 */
	protected function _not()
	{
		$targetRegister = $this->_getTargetRegister();
		$value = $this->_getNextInstruction();
		$mask = self::MAX_INT;
		$neg = (~ $value) & $mask;

		$this->_setRegister($targetRegister, $neg);
	}

	/**
	 * rmem: 15 a b
	 *   read memory at address <b> and write it to <a>
	 */
	protected function _rmem()
	{
		$targetRegister = $this->_getTargetRegister();
		$address = $this->_getNextInstruction();
		$mem = $this->_memory[$address];

		$this->_setRegister($targetRegister, $mem);
	}

	/**
	 * wmem: 16 a b
	 *   write the value from <b> into memory at address <a>
	 */
	protected function _wmem()
	{
		$address = $this->_getNextInstruction();
		$value = $this->_getNextInstruction();

		$this->_memory[$address] = $value;
	}

	/**
	 * call: 17 a
	 *   write the address of the next instruction to the stack and jump to <a>
	 */
	protected function _call()
	{
		$nextInstructionAddress = $this->_programCounter + 1;
		$this->_stack->push($nextInstructionAddress);
		$this->_jmp();
	}

	/**
	 * ret: 18
	 *   remove the top element from the stack and jump to it; empty stack = halt
	 */
	protected function _ret()
	{
		try {
			$address = $this->_stack->pop();
			$this->_setProgramCounter($address);
		} catch (\RuntimeException $e) {
			printf("The stack is empty.\n");
			$this->_halt();
		}
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
	 * in: 20 a
	 * read a character from the terminal and write its ascii code to <a>;
	 *
	 * it can be assumed that once input starts, it will continue until a newline is encountered;
	 * this means that you can safely read whole lines from the keyboard
	 * and trust that they will be fully read
	 */
	protected function _in()
	{
		// get the current tty state
		$state = `stty -g`;

		system("stty -icanon");

		$char = fread(STDIN, 1);
		$code = ord($char);

		$instruction = $this->_getRawInstruction();
		if ($this->_isRegister($instruction)) {
			$register = $this->_getRegisterId($instruction);
			$this->_registers[$register] = $code;
		} else {
			$this->_memory[$instruction] = $code;
		}

		// reset the terminal
		system("stty '" . trim($state) . "'");
	}

	/**
	 * noop: 21
	 *   no operation
	 */
	protected function _noop()
	{
		return;
	}

	protected function _peak($numberOfAddresses)
	{
		$peak = array();
		for ($i = 0; $i < $numberOfAddresses; $i++) {
			$address = $this->_programCounter + $i;
			$peak[] = $this->_memory[$address];
		}
		$this->_log(sprintf("Peaked: %s", implode(' ', $peak)));
	}

}
