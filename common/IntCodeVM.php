<?php

	require_once(dirname(__FILE__) . '/../common/VM.php');

	/**
	 * Simple IntCode VM
	 */
	class IntCodeVM extends VM {
		/**
		 * Init the opcodes.
		 */
		protected function init() {
			/**
			 * add
			 *   - 1 X Y Z
			 *
			 * sets memory position Z to the value of memory position X + memory position Y.
			 *
			 * @param $vm VM to execute on.
			 * @param $args Args for this instruction.
			 */
			$this->instrs['1'] = ['ADD', 3, function($vm, $args) {
				[$x, $y, $z] = $args;
				$vm->setData($z, ($vm->getData($x) + $vm->getData($y)));
			}];

			/**
			 * mul
			 *   - 2 X Y Z
			 *
			 * sets memory position Z to the value of memory position X * memory position Y.
			 *
			 * @param $vm VM to execute on.
			 * @param $args Args for this instruction.
			 */
			$this->instrs['2'] = ['MUL', 3, function($vm, $args) {
				[$x, $y, $z] = $args;
				$vm->setData($z, ($vm->getData($x) * $vm->getData($y)));
			}];

			/**
			 * halt
			 *   - 99
			 *
			 * Halt.
			 *
			 * @param $vm VM to execute on.
			 * @param $args Args for this instruction.
			 */
			$this->instrs['99'] = ['HALT', 0, function($vm, $args) {
				$vm->jump(count($this->data)); // Jump to end to halt.
			}];
		}

		/**
		 * Step a single instruction.
		 *
		 * @return True if we executed something, else false if we have no more
		 *         to execute.
		 */
		function doStep() {
			$next = $this->data[$this->location];
			[$name, $argCount, $ins] = $this->getInstr($next);

			$args = array_slice($this->data, ($this->location + 1), $argCount);
			$this->location += $argCount;

			if ($this->debug) {
				echo sprintf('(%4s)   %-20s', $this->location, static::instrToString([$name . '{' . $next . '}', $args])), "\n";
				usleep($this->sleep);
			}

			$ins($this, $args);
		}

		/**
		 * Parse instruction file into instruction array.
		 *
		 * @param $data Data to parse/
		 */
		public static function parseInstrLines($input) {
			return explode(',', $input);
		}
	}
