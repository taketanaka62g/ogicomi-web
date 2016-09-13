<?php

/**
 * プラグインコンテンツモデル
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2015, baserCMS Users Community <http://sites.google.com/site/baserusers/>
 *
 * @copyright		Copyright 2008 - 2015, baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Baser.Model
 * @since			baserCMS v 0.1.0
 * @license			http://basercms.net/license/index.html
 */
/**
 * Include files
 */

/**
 * メニューモデル
 *
 * @package Baser.Model
 */
class PluginContent extends AppModel {

/**
 * クラス名
 *
 * @var string
 * @access public
 */
	public $name = 'PluginContent';

/**
 * ビヘイビア
 * 
 * @var array
 * @access public
 */
	public $actsAs = array('BcCache');

/**
 * データベース接続
 *
 * @var string
 * @access public
 */
	public $useDbConfig = 'baser';

/**
 * バリデーション
 *
 * @var array
 * @access public
 */
	public $validate = array(
		'name' => array(
			array('rule' => array('alphaNumericPlus'),
				'message' => 'コンテンツ名は半角英数字、ハイフン、アンダースコアのみで入力してください。'),
			array('rule' => array('isUnique'),
				'on' => 'create',
				'message' => '入力されたコンテンツ名は既に使用されています。'),
			array('rule' => array('maxLength', 50),
				'message' => 'コンテンツ名は50文字以内で入力してください。')
		),
		'content_id' => array(
			array('rule' => array('notEmpty'),
				'message' => 'コンテンツIDを入力してください。',
				'on' => 'update')
		),
		'plugin' => array(
			array('rule' => array('alphaNumericPlus'),
				'message' => 'プラグイン名は半角英数字、ハイフン、アンダースコアのみで入力してください。'),
			array('rule' => array('notEmpty'),
				'message' => 'プラグイン名を入力してください。'),
			array('rule' => array('maxLength', 20),
				'message' => 'プラグイン名は20文字以内で入力してください。')
		)
	);

/**
 * PluginContentのデータをURLか取得
 * プラグイン名の書き換え
 * DBに登録したデータを元にURLのプラグイン名部分を書き換える。
 * 
 * @param $url
 * @return array Or false
 * @access public
 */
	public function currentPluginContent($url) {
		if (!$url) {
			return false;
		}

		$url = preg_replace('/^\//', '', $url);
		if (strpos($url, '/') !== false) {
			list($name) = explode('/', $url);
		} else {
			$name = $url;
		}

		return $pluginContent = $this->find('first', array(
			'fields' => array('name', 'plugin'),
			'conditions' => array('PluginContent.name' => $name)
		));

	}

}
