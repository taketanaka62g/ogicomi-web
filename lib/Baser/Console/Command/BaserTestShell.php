<?php

/**
 * Custom TestShell Command
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2015, baserCMS Users Community <http://sites.google.com/site/baserusers/>
 *
 * @copyright		Copyright 2008 - 2015, baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Baser.Console.Command
 * @since			baserCMS v 3.0.0-beta
 * @license			http://basercms.net/license/index.html
 * 
 */
App::uses('TestShell', 'Console/Command');
App::uses('BaserTestSuiteDispatcher', 'TestSuite');
App::uses('BaserTestSuiteCommand', 'TestSuite');
App::uses('BaserTestLoader', 'TestSuite');

/**
 * Provides a CakePHP wrapper around PHPUnit.
 * Adds in CakePHP's fixtures and gives access to plugin, app and core test cases
 *
 * @package       Baser.Console.Command
 */
class BaserTestShell extends TestShell {

/**
 * Initialization method installs PHPUnit and loads all plugins
 *
 * @return void
 * @throws Exception
 */
	public function initialize() {
		$this->_dispatcher = new BaserTestSuiteDispatcher();
		$sucess = $this->_dispatcher->loadTestFramework();
		if (!$sucess) {
			throw new Exception(__d('cake_dev', 'Please install PHPUnit framework <info>(http://www.phpunit.de)</info>'));
		}
	}

/**
 * Parse the CLI options into an array CakeTestDispatcher can use.
 *
 * @return array Array of params for CakeTestDispatcher
 */
	protected function _parseArgs() {
		if (empty($this->args)) {
			return;
		}
		$params = array(
			'core' => false,
			'baser' => false,
			'app' => false,
			'plugin' => null,
			'output' => 'text',
		);

		if (strpos($this->args[0], '.php')) {
			$category = $this->_mapFileToCategory($this->args[0]);
			$params['case'] = $this->_mapFileToCase($this->args[0], $category);
		} else {
			$category = $this->args[0];
			if (isset($this->args[1])) {
				$params['case'] = $this->args[1];
			}
		}

		if ($category === 'core') {
			$params['core'] = true;
		} elseif ($category === 'baser') {
			$params['baser'] = true;
		} elseif ($category === 'app') {
			$params['app'] = true;
		} else {
			$params['plugin'] = $category;
		}

		return $params;
	}

/**
 * Runs the test case from $runnerArgs
 *
 * @param array $runnerArgs list of arguments as obtained from _parseArgs()
 * @param array $options list of options as constructed by _runnerOptions()
 * @return void
 */
	protected function _run($runnerArgs, $options = array()) {
		restore_error_handler();
		restore_error_handler();

		$testCli = new BaserTestSuiteCommand('BaserTestLoader', $runnerArgs);
		$testCli->run($options);
	}

/**
 * Shows a list of available test cases and gives the option to run one of them
 *
 * @return void
 */
	public function available() {
		$params = $this->_parseArgs();
		$testCases = BaserTestLoader::generateTestList($params);
		$baser = $params['baser'];
		$app = $params['app'];
		$plugin = $params['plugin'];

		$title = "Core Test Cases:";
		$category = 'core';
		if ($baser) {
			$title = "Baser Test Cases:";
			$category = 'baser';
		} elseif ($app) {
			$title = "App Test Cases:";
			$category = 'app';
		} elseif ($plugin) {
			$title = Inflector::humanize($plugin) . " Test Cases:";
			$category = $plugin;
		}

		if (empty($testCases)) {
			$this->out(__d('cake_console', "No test cases available \n\n"));
			return $this->out($this->OptionParser->help());
		}

		$this->out($title);
		$i = 1;
		$cases = array();
		foreach ($testCases as $testCaseFile => $testCase) {
			$case = str_replace('Test.php', '', $testCase);
			$this->out("[$i] $case");
			$cases[$i] = $case;
			$i++;
		}

		while ($choice = $this->in(__d('cake_console', 'What test case would you like to run?'), null, 'q')) {
			if (is_numeric($choice) && isset($cases[$choice])) {
				$this->args[0] = $category;
				$this->args[1] = $cases[$choice];
				$this->_run($this->_parseArgs(), $this->_runnerOptions());
				break;
			}

			if (is_string($choice) && in_array($choice, $cases)) {
				$this->args[0] = $category;
				$this->args[1] = $choice;
				$this->_run($this->_parseArgs(), $this->_runnerOptions());
				break;
			}

			if ($choice == 'q') {
				break;
			}
		}
	}

/**
 * Find the test case for the passed file. The file could itself be a test.
 *
 * @param string $file
 * @param string $category 
 * @param boolean $throwOnMissingFile 
 * @access protected
 * @return array(type, case)
 * @throws Exception
 */
	protected function _mapFileToCase($file, $category, $throwOnMissingFile = true) {
		if (!$category || (substr($file, -4) !== '.php')) {
			return false;
		}

		$_file = realpath($file);
		if ($_file) {
			$file = $_file;
		}

		$testFile = $testCase = null;

		if (preg_match('@Test[\\\/]@', $file)) {

			if (substr($file, -8) === 'Test.php') {

				$testCase = substr($file, 0, -8);
				$testCase = str_replace(DS, '/', $testCase);

				if ($testCase = preg_replace('@.*Test\/Case\/@', '', $testCase)) {

					if ($category === 'core') {
						$testCase = str_replace('lib/Cake', '', $testCase);
					}
					if ($category === 'baser') {
						$testCase = str_replace('lib/Baser', '', $testCase);
					}

					return $testCase;
				}

				throw new Exception(__d('cake_dev', 'Test case %s cannot be run via this shell', $testFile));
			}
		}

		$file = substr($file, 0, -4);
		if ($category === 'core') {

			$testCase = str_replace(DS, '/', $file);
			$testCase = preg_replace('@.*lib/Cake/@', '', $file);
			$testCase[0] = strtoupper($testCase[0]);
			$testFile = CAKE . 'Test/Case/' . $testCase . 'Test.php';

			if (!file_exists($testFile) && $throwOnMissingFile) {
				throw new Exception(__d('cake_dev', 'Test case %s not found', $testFile));
			}

			return $testCase;
		} elseif ($category === 'baser') {

			$testCase = str_replace(DS, '/', $file);
			$testCase = preg_replace('@.*lib/Baser/@', '', $file);
			$testCase[0] = strtoupper($testCase[0]);
			$testFile = BASER . 'Test/Case/' . $testCase . 'Test.php';

			if (!file_exists($testFile) && $throwOnMissingFile) {
				throw new Exception(__d('cake_dev', 'Test case %s not found', $testFile));
			}

			return $testCase;
		}

		if ($category === 'app') {
			$testFile = str_replace(APP, APP . 'Test/Case/', $file) . 'Test.php';
		} else {
			$testFile = preg_replace(
				"@((?:plugins|Plugin)[\\/]{$category}[\\/])(.*)$@", '\1Test/Case/\2Test.php', $file
			);
		}

		if (!file_exists($testFile) && $throwOnMissingFile) {
			throw new Exception(__d('cake_dev', 'Test case %s not found', $testFile));
		}

		$testCase = substr($testFile, 0, -8);
		$testCase = str_replace(DS, '/', $testCase);
		$testCase = preg_replace('@.*Test/Case/@', '', $testCase);

		return $testCase;
	}

/**
 * For the given file, what category of test is it? returns app, core or the name of the plugin
 *
 * @param string $file
 * @access protected
 * @return string
 */
	protected function _mapFileToCategory($file) {
		$_file = realpath($file);
		if ($_file) {
			$file = $_file;
		}

		$file = str_replace(DS, '/', $file);
		if (strpos($file, 'lib/Cake/') !== false) {
			return 'core';
		} elseif (strpos($file, 'lib/Baser/') !== false) {
			return 'core';
		} elseif (preg_match('@(?:plugins|Plugin)/([^/]*)@', $file, $match)) {
			return $match[1];
		}
		return 'app';
	}

}
