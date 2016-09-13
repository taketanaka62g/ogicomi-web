<?php

/**
 * run all controller baser mail tests
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2015, baserCMS Users Community <http://sites.google.com/site/baserusers/>
 * @package         Mail.Test.Case
 * @copyright       Copyright 2008 - 2015, baserCMS Users Community
 * @link            http://basercms.net baserCMS Project
 * @since           baserCMS v 3.0.0-beta
 * @license         http://basercms.net/license/index.html
 */
class MailAllControllerTest extends CakeTestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return CakeTestSuite
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Helper tests');
		$path = dirname(__FILE__) . DS;
		$suite->addTestDirectory($path . 'Controller' . DS);
		$suite->addTestDirectory($path . 'Controller' . DS . 'Component' . DS);
		return $suite;
	}

}
