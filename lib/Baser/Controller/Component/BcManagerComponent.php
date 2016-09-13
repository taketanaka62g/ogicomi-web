<?php

/**
 * BcManagerコンポーネント
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2015, baserCMS Users Community <http://sites.google.com/site/baserusers/>
 *
 * @copyright		Copyright 2008 - 2015, baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Baser.Controller.Component
 * @since			baserCMS v 0.1.0
 * @license			http://basercms.net/license/index.html
 */
App::uses('PageCategory', 'Model');
App::uses('Page', 'Model');
App::uses('Plugin', 'Model');
App::uses('User', 'Model');
App::uses('File', 'Utility');
App::uses('Component', 'Controller');
App::uses('ConnectionManager', 'Model');

class BcManagerComponent extends Component {
/**
 * Controller
 * 
 * @var Controller 
 */
	public $Controller = null;
	
/**
 * 
 * @param Controller $controller
 */
	public function startup(Controller $controller) {
		parent::startup($controller);
		$this->Controller = $controller;
	}
	
/**
 * baserCMSのインストール
 * 
 * @param type $dbConfig
 * @param type $adminUser
 * @param type $adminPassword
 * @param type $adminEmail
 * @return boolean 
 */
	public function install($siteUrl, $dbConfig, $adminUser = array(), $baseUrl = '', $dbDataPattern = '') {
		if (!$dbDataPattern) {
			$dbDataPattern = Configure::read('BcApp.defaultTheme') . '.default';
		}

		$result = true;

		// キャッシュ削除
		clearAllCache();

		// 一時フォルダ作成
		checkTmpFolders();

		if ($dbConfig['datasource'] == 'sqlite' || $dbConfig['datasource'] == 'csv') {
			switch ($dbConfig['datasource']) {
				case 'sqlite':
					$dbFolderPath = APP . 'db' . DS . 'sqlite';
					break;
				case 'csv':
					$dbFolderPath = APP . 'db' . DS . 'csv';
					break;
			}
			$Folder = new Folder();
			if (!is_writable($dbFolderPath) && !$Folder->create($dbFolderPath, 00777)) {
				$this->log('データベースの保存フォルダの作成に失敗しました。db フォルダの書き込み権限を見なおしてください。');
				$result = false;
			}
		}

		// SecritySaltの設定
		$securitySalt = $this->setSecuritySalt();
		$securityCipherSeed = $this->setSecurityCipherSeed();

		// インストールファイル作成
		if (!$this->createInstallFile($securitySalt, $securityCipherSeed, $siteUrl)) {
			$this->log('インストールファイル生成に失敗しました。設定フォルダの書き込み権限を見なおしてください。');
			$result = false;
		}

		// データベース設定ファイル生成
		if (!$this->createDatabaseConfig($dbConfig)) {
			$this->log('データベースの設定ファイル生成に失敗しました。設定フォルダの書き込み権限を見なおしてください。');
			$result = false;
		}

		// データベース初期化
		if (!$this->constructionDb($dbConfig, $dbDataPattern)) {
			$this->log('データベースの初期化に失敗しました。データベースの設定を見なおしてください。');
			$result = false;
		}

		if ($adminUser) {
			// サイト基本設定登録
			if (!$this->setAdminEmail($adminUser['email'])) {
				$this->log('サイト基本設定への管理者メールアドレスの設定処理が失敗しました。データベースの設定を見なおしてください。');
			}
			// ユーザー登録
			$adminUser['password_1'] = $adminUser['password'];
			$adminUser['password_2'] = $adminUser['password'];
			if (!$this->addDefaultUser($adminUser)) {
				$this->log('初期ユーザーの作成に失敗しました。データベースの設定を見なおしてください。');
				$result = false;
			}
		}

		// データベースの初期更新
		if (!$this->executeDefaultUpdates($dbConfig)) {
			$this->log('データベースのデータ更新に失敗しました。データベースの設定を見なおしてください。');
			$result = false;
		}

		// コアプラグインのインストール
		if(!$this->installCorePlugin($dbConfig, $dbDataPattern)) {
			$this->log('コアプラグインのインストールに失敗しました。');
			$result = false;
		}

		// テーマを配置
		if (!$this->deployTheme()) {
			$this->log('テーマの配置に失敗しました。テーマフォルダの書き込み権限を確認してください。');
			$result = false;
		}

		// テーマに管理画面のアセットへのシンボリックリンクを作成する
		if (!$this->deployAdminAssets()) {
			$this->log('管理システムのアセットファイルの配置に失敗しました。テーマフォルダの書き込み権限を確認してください。');
		}

		// アップロード用初期フォルダを作成する
		if (!$this->createDefaultFiles()) {
			$this->log('アップロード用初期フォルダの作成に失敗しました。files フォルダの書き込み権限を確認してください。');
			$result = false;
		}

		// エディタテンプレート用の画像を配置
		if (!$this->deployEditorTemplateImage()) {
			$this->log('エディタテンプレートイメージの配置に失敗しました。files フォルダの書き込み権限を確認してください。');
			$result = false;
		}

		//SiteConfigを再設定
		loadSiteConfig();

		// ページファイルを生成
		$this->createPageTemplates();

		return $result;
	}

/**
 * コアプラグインをインストールする
 *
 * TODO 引数となる $dbDataPattern は、BcManager::installPlugin() で利用できる仕様となっていない
 * @return bool
 */
	public function installCorePlugin($dbConfig, $dbDataPattern) {
		$result = true;
		$corePlugins = Configure::read('BcApp.corePlugins');
		$this->connectDb($dbConfig, 'plugin');
		foreach ($corePlugins as $corePlugin) {
			CakePlugin::load($corePlugin);
			if (!$this->installPlugin($corePlugin, $dbDataPattern)) {
				$this->log("コアプラグイン" . $corePlugin . "のインストールに失敗しました。");
				$result = false;
			}
		}
		return $result;
	}
/**
 * データベースに接続する
 *
 * @param array $config
 * @return DboSource $db
 * @access public
 */
	public function connectDb($config, $name = 'baser') {
		if ($name == 'plugin') {
			$config['prefix'] .= Configure::read('BcEnv.pluginDbPrefix');
		}

		if (!$datasource = $this->getDatasourceName($config['datasource'])) {
			return ConnectionManager::getDataSource($name);
		}

		$result = ConnectionManager::create($name, array(
				'datasource' => $datasource,
				'persistent' => false,
				'host' => $config['host'],
				'port' => $config['port'],
				'login' => $config['login'],
				'password' => $config['password'],
				'database' => $config['database'],
				'schema' => $config['schema'],
				'prefix' => $config['prefix'],
				'encoding' => $config['encoding']));

		if ($result) {
			return $result;
		} else {
			return ConnectionManager::getDataSource($name);
		}
	}

/**
 * datasource名を取得
 *
 * @param string datasource name.postgre.mysql.etc.
 * @return string
 * @access public
 */
	public function getDatasourceName($datasource = null) {
		$name = false;
		switch ($datasource) {
			case 'postgres' :
				$name = 'Database/BcPostgres';
				break;
			case 'mysql' :
				$name = 'Database/BcMysql';
				break;
			case 'sqlite' :
				$name = 'Database/BcSqlite';
				break;
			case 'csv' :
				$name = 'Database/BcCsv';
				break;
			default :
		}
		return $name;
	}

/**
 * 実際の設定用のDB名を取得する
 *
 * @param string $type
 * @param string $name
 * @return string
 * @access	public
 */
	public function getRealDbName($type, $name) {
		if (preg_match('/^\//', $name)) {
			return $name;
		}
		/* dbName */
		if (!empty($type) && !empty($name)) {
			if ($type == 'sqlite') {
				return APP . 'db' . DS . 'sqlite' . DS . $name . '.db';
			} elseif ($type == 'csv') {
				return APP . 'db' . DS . 'csv' . DS . $name;
			}
		}

		return $name;
	}

/**
 * テーマ用のページファイルを生成する
 *
 * @access	protected
 */
	public function createPageTemplates() {
		$Page = ClassRegistry::init('Page');
		clearAllCache();
		$pages = $Page->find('all', array('recursive' => -1));
		if ($pages) {
			foreach ($pages as $page) {
				$Page->create($page);
				$Page->afterSave(true);
			}
		}
		return true;
	}

/**
 * データベースのデータに初期更新を行う
 */
	public function executeDefaultUpdates($dbConfig) {
		$result = true;
		if (!$this->_updatePluginStatus($dbConfig)) {
			$this->log('プラグインの有効化に失敗しました。');
			$result = false;
		}
		return $result;
	}

/**
 * プラグインのステータスを更新する
 *
 * @return boolean
 * @access	protected
 */
	protected function _updatePluginStatus($dbConfig) {
		$db = $this->_getDataSource('baser', $dbConfig);
		$db->truncate('plugins');

		$version = getVersion();
		$Plugin = new Plugin();
		$corePlugins = Configure::read('BcApp.corePlugins');

		$result = true;
		$priority = intval($Plugin->getMax('priority')) + 1;
		foreach ($corePlugins as $corePlugin) {
			$data = array();
			include BASER_PLUGINS . $corePlugin . DS . 'config.php';
			$data['Plugin']['name'] = $corePlugin;
			$data['Plugin']['title'] = $title;
			$data['Plugin']['version'] = $version;
			$data['Plugin']['status'] = true;
			$data['Plugin']['db_inited'] = false;
			$data['Plugin']['priority'] = $priority;
			$Plugin->create($data);
			if (!$Plugin->save()) {
				$result = false;
			}
			$priority++;
		}
		return $result;
	}

/**
 * サイト基本設定に管理用メールアドレスを登録する
 * 
 * @param string $email
 * @return boolean
 * @access public 
 */
	public function setAdminEmail($email) {
		App::uses('SiteConfig', 'Model');
		$data['SiteConfig']['email'] = $email;
		$SiteConfig = new SiteConfig();
		return $SiteConfig->saveKeyValue($data);
	}

/**
 * 初期ユーザーを登録する
 * 
 * @param array $user
 * @return boolean 
 */
	public function addDefaultUser($user, $securitySalt = '') {
		if ($securitySalt) {
			Configure::write('Security.salt', $securitySalt);
		}

		$user += array(
			'real_name_1' => $user['name']
		);
		$user = array_merge(array(
			'name' => '',
			'real_name_1' => '',
			'email' => '',
			'user_group_id' => 1,
			'password_1' => '',
			'password_2' => ''
			), $user);

		$User = new User();

		$user['password'] = $user['password_1'];
		$User->create($user);

		return $User->save();
	}

/**
 * データベース設定ファイル[database.php]を保存する
 *
 * @param	array	$options
 * @return boolean
 * @access private
 */
	public function createDatabaseConfig($options = array()) {
		if (!is_writable(APP . 'Config' . DS)) {
			return false;
		}

		$options = array_merge(array(
			'datasource'	=> '',
			'host'			=> 'localhost',
			'port'			=> '',
			'login'			=> 'dummy',
			'password'		=> 'dummy',
			'database'		=> 'dummy',
			'prefix'		=> '',
			'schema'		=> '',
			'encoding'		=> 'utf8'
			), $options);

		extract($options);

		$datasource = $this->getDatasourceName($datasource);

		$dbfilename = APP . 'Config' . DS . 'database.php';
		$file = new File($dbfilename);

		if ($file !== false) {

			if ($file->exists()) {
				$file->delete();
			}

			$file->create();
			$file->open('w', true);
			$file->write("<?php\n");
			$file->write("//\n");
			$file->write("// Database Configuration File created by baserCMS Installation\n");
			$file->write("//\n");
			$file->write("class DATABASE_CONFIG {\n");
			$file->write('public $baser = array(' . "\n");
			$file->write("\t'datasource' => '" . $datasource . "',\n");
			$file->write("\t'persistent' => false,\n");
			$file->write("\t'host' => '" . $host . "',\n");
			$file->write("\t'port' => '" . $port . "',\n");
			$file->write("\t'login' => '" . $login . "',\n");
			$file->write("\t'password' => '" . $password . "',\n");
			$file->write("\t'database' => '" . $database . "',\n");
			$file->write("\t'schema' => '" . $schema . "',\n");
			$file->write("\t'prefix' => '" . $prefix . "',\n");
			$file->write("\t'encoding' => '" . $encoding . "'\n");
			$file->write(");\n");

			$file->write('public $plugin = array(' . "\n");
			$file->write("\t'datasource' => '" . $datasource . "',\n");
			$file->write("\t'persistent' => false,\n");
			$file->write("\t'host' => '" . $host . "',\n");
			$file->write("\t'port' => '" . $port . "',\n");
			$file->write("\t'login' => '" . $login . "',\n");
			$file->write("\t'password' => '" . $password . "',\n");
			$file->write("\t'database' => '" . $database . "',\n");
			$file->write("\t'schema' => '" . $schema . "',\n");
			$file->write("\t'prefix' => '" . $prefix . Configure::read('BcEnv.pluginDbPrefix') . "',\n");
			$file->write("\t'encoding' => '" . $encoding . "'\n");
			$file->write(");\n");

			$file->write('public $test = array(' . "\n");
			$file->write("\t'datasource' => '" . $datasource . "',\n");
			$file->write("\t'persistent' => false,\n");
			$file->write("\t'host' => '" . $host . "',\n");
			$file->write("\t'port' => '" . $port . "',\n");
			$file->write("\t'login' => '" . $login . "',\n");
			$file->write("\t'password' => '" . $password . "',\n");
			$file->write("\t'database' => '" . $database . "',\n");
			$file->write("\t'schema' => '" . $schema . "',\n");
			$file->write("\t'prefix' => '" . $prefix . Configure::read('BcEnv.testDbPrefix') . "',\n");
			$file->write("\t'encoding' => '" . $encoding . "'\n");
			$file->write(");\n");
			$file->write("}\n");

			$file->close();
			return true;
		} else {
			return false;
		}
	}

/**
 * インストール設定ファイルを生成する
 * 
 * @return boolean 
 * @access public
 */
	public function createInstallFile($securitySalt, $secrityCipherSeed, $siteUrl = "") {
		$installFileName = APP . 'Config' . DS . 'install.php';

		if (!$siteUrl) {
			$siteUrl = siteUrl();
		}
		$installCoreData = array("<?php",
			"Configure::write('Security.salt', '{$securitySalt}');",
			"Configure::write('Security.cipherSeed', '{$secrityCipherSeed}');",
			"Configure::write('Cache.disable', false);",
			"Configure::write('Cache.check', true);",
			"Configure::write('Session.save', 'session');",
			"Configure::write('BcEnv.siteUrl', '{$siteUrl}');",
			"Configure::write('BcEnv.sslUrl', '');",
			"Configure::write('BcApp.adminSsl', false);",
			"Configure::write('BcApp.mobile', false);",
			"Configure::write('BcApp.smartphone', false);",
			"Cache::config('default', array('engine' => 'File'));",
			"Configure::write('debug', 0);"
		);
		if (file_put_contents($installFileName, implode("\n", $installCoreData))) {
			return chmod($installFileName, 0666);
		} else {
			return false;
		}
	}

/**
 * セキュリティ用のキーを生成する
 *
 * @param	int $length
 * @return string キー
 * @access	protected
 */
	public function setSecuritySalt($length = 40) {
		$keyset = "abcdefghijklmABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$randkey = "";
		for ($i = 0; $i < $length; $i++) {
			$randkey .= substr($keyset, rand(0, strlen($keyset) - 1), 1);
		}
		Configure::write('Security.salt', $randkey);
		return $randkey;
	}

/**
 * セキュリティ用の数字キーを生成する
 *
 * @param	int $length
 * @return string 数字キー
 * @access	public
 */
	public function setSecurityCipherSeed($length = 29) {
		$keyset = "0123456789";
		$randkey = "";
		for ($i = 0; $i < $length; $i++) {
			$randkey .= substr($keyset, rand(0, strlen($keyset) - 1), 1);
		}
		Configure::write('Security.cipherSeed', $randkey);
		return $randkey;
	}

/**
 * baserCMSコアのデータベースを構築する
 * 
 * @param array $dbConfig データベース設定名
 * @param string $dbDataPattern データパターン
 * @return boolean
 */
	public function constructionDb($dbConfig, $dbDataPattern = '') {

		$coreExcludes = array('users', 'dblogs', 'plugins');

		if (!$dbDataPattern) {
			$dbDataPattern = Configure::read('BcApp.defaultTheme') . '.default';
		}

		if (strpos($dbDataPattern, '.') === false) {
			$this->log("データパターンの形式が不正です。");
			return false;
		}

		list($theme, $pattern) = explode('.', $dbDataPattern);

		if (!$this->constructionTable('Core', 'baser', $dbConfig)) {
			$this->log("コアテーブルの構築に失敗しました。");
			return false;
		}

		if ($theme == 'core') {
			if (!$this->loadDefaultDataPattern('baser', $dbConfig, $pattern, $theme, 'core', $coreExcludes)) {
				$this->log("コアの初期データのロードに失敗しました。");
				return false;
			}
		} else {
			if (!$this->loadDefaultDataPattern('baser', $dbConfig, $pattern, $theme, 'core', $coreExcludes)) {
				$this->log("コアの初期データのロードに失敗しました。");
				return false;
			}
		}

		if (!$this->initSystemData($dbConfig)) {
			$this->log('システムデータの初期化に失敗しました。');
			return false;
		}

		return true;
	}

/**
 * 全ての初期データセットのリストを取得する
 * 
 * @return array 
 */
	public function getAllDefaultDataPatterns() {
		$patterns = array();

		// コア
		$patterns = $this->getDefaultDataPatterns();

		// コアテーマ
		$Folder = new Folder(BASER_CONFIGS . 'theme');
		$files = $Folder->read(true, true);
		foreach ($files[0] as $theme) {
			if ($theme != 'empty') {
				$patterns = array_merge($patterns, $this->getDefaultDataPatterns($theme));
			}
		}

		// 外部テーマ
		$Folder = new Folder(BASER_THEMES);
		$files = $Folder->read(true, true, false);
		foreach ($files[0] as $theme) {
			if ($theme != 'empty') {
				$patterns = array_merge($patterns, $this->getDefaultDataPatterns($theme));
			}
		}

		return $patterns;
	}

/**
 * 初期データのセットを取得する
 * 
 * @param string $theme
 * @param array $options
 * @return array 
 */
	public function getDefaultDataPatterns($theme = 'core', $options = array()) {
		$options = array_merge(array('useTitle' => true), $options);
		extract($options);

		$themePath = $dataPath = $title = '';
		$dataPath = dirname(BcUtil::getDefaultDataPath('Core', $theme));

		if($theme != 'core' && $dataPath == dirname(BcUtil::getDefaultDataPath('Core'))) {
			return array();
		}
		
		if (is_dir(BASER_THEMES . $theme)) {
			$themePath = BASER_THEMES . $theme . DS;
		} elseif (is_dir(BASER_CONFIGS . 'theme' . DS . $theme)) {
			$themePath = BASER_CONFIGS . 'theme' . DS . $theme . DS;
		}

		if ($themePath) {
			if (file_exists($themePath . 'config.php')) {
				include $themePath . 'config.php';
			}
		} else {
			$title = 'コア';
		}

		if (!$title) {
			$title = $theme;
		}

		$patterns = array();
		$Folder = new Folder($dataPath);
		$files = $Folder->read(true, true);
		if ($files[0]) {
			foreach ($files[0] as $pattern) {
				if ($useTitle) {
					$patternName = $title . ' ( ' . $pattern . ' )';
				} else {
					$patternName = $pattern;
				}
				$patterns[$theme . '.' . $pattern] = $patternName;
			}
		}
		return $patterns;
	}

/**
 * 初期データを読み込む
 * 
 * @param string $dbConfigKeyName
 * @param array $dbConfig
 * @param string $pattern
 * @param string $theme
 * @param string $plugin
 * @return boolean 
 */
	public function loadDefaultDataPattern($dbConfigKeyName, $dbConfig, $pattern, $theme = 'core', $plugin = 'core', $excludes = array()) {
		$db = $this->_getDataSource($dbConfigKeyName, $dbConfig);

		$path = BcUtil::getDefaultDataPath($plugin, $theme, $pattern);

		if (!$path) {
			return false;
		}

		$corePath = BcUtil::getDefaultDataPath($plugin, 'core', 'default');
		
		$targetTables = array();
		if($corePath) {
			$Folder = new Folder($corePath);
			$files = $Folder->read(true, true);
			$targetTables = $files[1];
		}
		$Folder = new Folder($path);
		$files = $Folder->read(true, true, true);
		if(!$targetTables) {
			$targetTables = $files[1];
		}

		$result = true;

		foreach ($targetTables as $targetTable) {
			$targetTable = basename($targetTable, '.csv');
			$loaded = false;
			if (!in_array($targetTable, $excludes)) {
				// 初期データ投入
				foreach ($files[1] as $file) {
					if (!preg_match('/\.csv$/', $file)) {
						continue;
					}
					$table = basename($file, '.csv');
					if ($table == $targetTable) {
						if (!$db->loadCsv(array('path' => $file, 'encoding' => 'SJIS'))) {
							$this->log($file . ' の読み込みに失敗。');
							$result = false;
						} else {
							$loaded = true;
							break;
						}
					}
				}
				// 存在しなかった場合は、コアのファイルを読み込む
				if (!$loaded && $corePath) {
					if (!$db->loadCsv(array('path' => $corePath . DS . $targetTable . '.csv', 'encoding' => 'SJIS'))) {
						$this->log($corePath . DS . $targetTable . ' の読み込みに失敗。');
						$result = false;
					}
				}
			}
		}
		
		App::uses('Page', 'Model');
		App::uses('PageCategory', 'Model');
		$Page = new Page();
		$Page->PageCategory = new PageCategory();
		
		// モバイルのID書き換え（ClearDB対策）
		$agents = array(1 => 'mobile', 2 => 'smartphone');
		foreach ($agents as $key => $agent) {
			$agentId = $Page->PageCategory->getAgentId($agent);
			if($agentId != $key) {
				$pages = $Page->find('all', array('conditions' => array('Page.page_category_id' => $key), 'recursive' => -1));
				foreach($pages as $page) {
					$page['Page']['page_category_id'] = $agentId;
					$Page->fileSave = false;
					$Page->contentSaving = false;
					$Page->set($page);
					if(!$Page->save()) {
						$result = false;
					}
				}
			}
		}
		return $result;
	}
	
/**
 * システムデータを初期化する
 * 
 * @param string $dbConfigKeyName
 * @param array $dbConfig 
 */
	public function initSystemData($dbConfig = null, $options = array()) {
		
		$options = array_merge(array('excludeUsers' => false), $options);
		
		$db = $this->_getDataSource('baser', $dbConfig);
		$corePath = BASER_CONFIGS . 'data' . DS . 'default';
		$result = true;

		/* page_categories の初期データをチェック＆設定 */
		$PageCategory = ClassRegistry::init('PageCategory');
		if(!$PageCategory->find('count', array('PageCategory.name' => 'mobile', 'PageCategory.parent_id' => null))) {
			$pageCategories = $db->loadCsvToArray($corePath . DS . 'page_categories.csv', 'SJIS');
			foreach($pageCategories as $pageCategory) {
				if($pageCategory['name'] == 'mobile') {
					$PageCategory->save($pageCategory);
					break;
				}
			}
		}
		if(!$PageCategory->find('count', array('PageCategory.name' => 'smartphone', 'PageCategory.parent_id' => null))) {
			$pageCategories = $db->loadCsvToArray($corePath . DS . 'page_categories.csv', 'SJIS');
			foreach($pageCategories as $pageCategory) {
				if($pageCategory['name'] == 'smartphone') {
					$PageCategory->save($pageCategory);
					break;
				}
			}
		}

		/* user_groupsの初期データをチェック＆設定 */
		$UserGroup = ClassRegistry::init('UserGroup');
		if(!$UserGroup->find('count', array('UserGroup.name' => 'admins'))) {
			$userGroups = $db->loadCsvToArray($corePath . DS . 'user_groups.csv', 'SJIS');
			foreach($userGroups as $userGroup) {
				if($userGroup['name'] == 'admins') {
					$UserGroup->save($userGroup);
					break;
				}
			}
		}

		/* users は全てのユーザーを削除 */
		//======================================================================
		// ユーザーグループを新しく読み込んだ場合にデータの整合性がとれない可能性がある為
		//======================================================================
		if(!$options['excludeUsers']) {
			if (!$db->truncate('users')) {
				$this->log('users テーブルの初期化に失敗。');
				$result = false;
			}
		}
		
		/* site_configs の初期データをチェック＆設定 */
		$SiteConfig = ClassRegistry::init('SiteConfig');
		if (!$SiteConfig->updateAll(array('SiteConfig.value' => null), array('SiteConfig.name' => 'email')) ||
			!$SiteConfig->updateAll(array('SiteConfig.value' => null), array('SiteConfig.name' => 'google_analytics_id')) ||
			!$SiteConfig->updateAll(array('SiteConfig.value' => true), array('SiteConfig.name' => 'first_access')) ||
			!$SiteConfig->deleteAll(array('SiteConfig.name' => 'version'), false)) {
			$this->log('site_configs テーブルの初期化に失敗');
			$result = false;
		}

		return $result;
	}

/**
 * テーブルを構築する
 *
 * @param string	$path
 * @param string	$dbConfigKeyName
 * @param string	$dbConfig
 * @param string	$dbDataPattern
 * @return boolean
 * @access public
 */
	public function constructionTable($plugin, $dbConfigKeyName = 'baser', $dbConfig = null) {

		$db = $this->_getDataSource($dbConfigKeyName, $dbConfig);
		$datasource = strtolower(preg_replace('/^Database\/Bc/', '', $db->config['datasource']));

		if (@!$db->connected && $datasource != 'csv') {
			return false;
		} elseif ($datasource == 'csv') {
			// CSVの場合はフォルダを作成する
			$Folder = new Folder($db->config['database'], true, 00777);
		} elseif ($datasource == 'sqlite') {
			$db->connect();
			chmod($db->config['database'], 0666);
		}

		$path = BcUtil::getSchemaPath($plugin);
		
		// DB構築
		$Folder = new Folder($path);
		$files = $Folder->read(true, true, true);
		if (isset($files[1])) {
			foreach ($files[1] as $file) {

				if (!preg_match('/\.php$/', $file)) {
					continue;
				}
				if (!$db->createTableBySchema(array('path' => $file))) {
					return false;
				}
			}
		}

		return true;
	}

/**
 * 全てのテーブルを削除する
 * 
 * @param array $dbConfig 
 * @return boolean
 * @access public
 */
	public function deleteAllTables($dbConfig = null) {
		$result = true;
		if (!$this->deleteTables('baser', $dbConfig)) {
			$result = false;
		}
		if ($dbConfig) {
			$dbConfig['prefix'] .= Configure::read('BcEnv.pluginDbPrefix');
		}
		if (!$this->deleteTables('plugin', $dbConfig)) {
			$result = false;
		}
		return $result;
	}

/**
 * プラグインも含めて全てのテーブルをリセットする
 * 
 * プラグインは有効となっているもののみ
 * 現在のテーマでないテーマの梱包プラグインを検出できない為
 * 
 * @param array $dbConfig 
 * @return boolean
 */
	public function resetAllTables($dbConfig = null, $excludes = array()) {
		$result = true;
		if (!$this->resetTables('baser', $dbConfig, 'core', $excludes)) {
			$result = false;
		}
		if ($dbConfig) {
			$dbConfig['prefix'] .= Configure::read('BcEnv.pluginDbPrefix');
		}

		$Plugin = ClassRegistry::init('Plugin');
		$plugins = $Plugin->find('all', array('conditions' => array('Plugin.status' => true)));
		$plugins = Hash::extract($plugins, '{n}.Plugin.name');
		foreach ($plugins as $plugin) {
			if (!$this->resetTables('plugin', $dbConfig, $plugin, $excludes)) {
				$result = false;
			}
		}

		return $result;
	}

/**
 * テーブルをリセットする
 * 
 * @param type $dbConfigKeyName
 * @param type $dbConfig
 * @return boolean 
 */
	public function resetTables($dbConfigKeyName = 'baser', $dbConfig = null, $plugin = 'core', $excludes = array()) {
		$db = $this->_getDataSource($dbConfigKeyName, $dbConfig);
		$dbConfig = $db->config;
		$db->reconnect();
		$sources = $db->listSources();
		$result = true;
		
		$pluginTables = array();
		if($plugin != 'core') {
			$path = BcUtil::getSchemaPath($plugin);
			$Folder = new Folder($path);
			$files = $Folder->read(true, true, false);
			if(empty($files[1])) {
				return true;
			}
			foreach($files[1] as $file) {
				if(preg_match('/\.php$/', $file)) {
					$pluginTables[] = preg_replace('/\.php/', '', $file);
				}
			}
		}
				
		foreach ($sources as $source) {
			if (preg_match("/^" . $dbConfig['prefix'] . "([^_].+)$/", $source, $matches)) {
				$table = $matches[1];
				if ($plugin == 'core') {
					if (preg_match("/^" . Configure::read('BcEnv.pluginDbPrefix') . "/", $table)) {
						continue;
					}
				} else {
					// プラグインの場合は対象プラグイン名が先頭にない場合スキップ
					if (!in_array($table, $pluginTables)) {
						// メールプラグインの場合、先頭に、「mail_」 がなくとも 末尾にmessagesがあれば対象とする
						if ($plugin != 'Mail') {
							continue;
						} elseif (!preg_match("/messages$/", $table)) {
							continue;
						}
					}
				}
				if (!in_array($table, $excludes)) {
					if (!$db->truncate($table)) {
						$result = false;
					}
				}
			}
		}
		return $result;
	}

/**
 * テーブルを削除する
 * 
 * @param string $dbConfigKeyName
 * @param array $dbConfig
 * @return boolean
 * @access public
 * TODO 処理を DboSource に移動する
 * TODO コアのテーブルを削除する際、プレフィックスだけでは、プラグインを識別できないので、プラグインのテーブルも削除されてしまう。
 * 		その為、プラグインのテーブルを削除しようとすると存在しない為、Excerptionが発生してしまい。処理が停止してしまうので、
 * 		try で実行し、catch はスルーしている。
 */
	public function deleteTables($dbConfigKeyName = 'baser', $dbConfig = null) {
		$db = $this->_getDataSource($dbConfigKeyName, $dbConfig);
		$dbConfig = $db->config;

		/* 削除実行 */
		// TODO schemaを有効活用すればここはスッキリしそうだが見送り
		$datasource = strtolower(preg_replace('/^Database\/Bc/', '', $db->config['datasource']));
		switch ($datasource) {
			case 'mysql':
				$sources = $db->listSources();
				foreach ($sources as $source) {
					if (preg_match("/^" . $dbConfig['prefix'] . "([^_].+)$/", $source)) {
						$sql = 'DROP TABLE ' . $source;
						try {
							$db->execute($sql);
						} catch (Exception $e) {
						}
					}
				}
				break;

			case 'postgres':
				$sources = $db->listSources();
				foreach ($sources as $source) {
					if (preg_match("/^" . $dbConfig['prefix'] . "([^_].+)$/", $source)) {
						$sql = 'DROP TABLE ' . $source;
						try {
							$db->execute($sql);
						} catch (Exception $e) {
						}
					}
				}
				// シーケンスも削除
				$sql = "SELECT sequence_name FROM INFORMATION_SCHEMA.sequences WHERE sequence_schema = '{$dbConfig['schema']}';";
				$sequences = array();
				try {
					$sequences = $db->query($sql);
				} catch (Exception $e) {
				}
				if ($sequences) {
					$sequences = Hash::extract($sequences, '0.sequence_name');
					foreach ($sequences as $sequence) {
						if (preg_match("/^" . $dbConfig['prefix'] . "([^_].+)$/", $sequence)) {
							$sql = 'DROP SEQUENCE ' . $sequence;
							try {
								$db->execute($sql);
							} catch (Exception $e) {
							}
						}
					}
				}
				break;

			case 'sqlite':
				@unlink($dbConfig['database']);
				break;

			case 'csv':
				$folder = new Folder($dbConfig['database']);
				$files = $folder->read(true, true, true);
				foreach ($files[1] as $file) {
					if (basename($file) != 'empty') {
						@unlink($file);
					}
				}
				break;
		}
		return true;
	}

/**
 * データソースを取得する
 * 
 * @param string $configKeyName
 * @param array $dbConfig
 * @return DataSource
 * @access public
 */
	public function _getDataSource($dbConfigKeyName = 'baser', $dbConfig = null) {
		if ($dbConfig) {
			$dbConfig['datasource'] = $this->getDatasourceName($dbConfig['datasource']);
			$db = ConnectionManager::create($dbConfigKeyName, $dbConfig);
			if (!$db) {
				$db = ConnectionManager::getDataSource($dbConfigKeyName);
			}
		} else {
			$db = ConnectionManager::getDataSource($dbConfigKeyName);
		}

		return $db;
	}

/**
 * テーマを配置する
 *
 * @param string $theme
 * @return boolean
 * @access public
 */
	public function deployTheme($theme = null) {
		$Folder = new Folder(BASER_CONFIGS . 'theme');
		
		if ($theme) {
			if (is_array($theme)) {
				$sources = $theme;
			} else {
				$sources = array($theme);
			}
		} else {
			$files = $Folder->read();
			$sources = $files[0];
		}

		$result = true;
		foreach ($sources as $theme) {
			$targetPath = WWW_ROOT . 'theme' . DS . $theme;
			$sourcePath = BASER_CONFIGS . 'theme' . DS . $theme;
			$Folder->delete($targetPath);
			if ($Folder->copy(array('to' => $targetPath, 'from' => $sourcePath, 'mode' => 00777, 'skip' => array('_notes')))) {
				if (!$Folder->create($targetPath . DS . 'Pages', 00777)) {
					$result = false;
				}
			} else {
				$result = false;
			}
		}
		$Folder = null;
		return $result;
	}

/**
 * エディタテンプレート用のアイコン画像をデプロイ
 * 
 * @return boolean
 * @access public
 */
	public function deployEditorTemplateImage() {
		$path = WWW_ROOT . 'files' . DS . 'editor' . DS;
		if (!is_dir($path)) {
			$Folder = new Folder();
			$Folder->create($path, 0777);
		}

		$src = BASER_WEBROOT . 'img' . DS . 'admin' . DS . 'ckeditor' . DS;
		$Folder = new Folder($src);
		$files = $Folder->read(true, true);
		$result = true;
		if (!empty($files[1])) {
			foreach ($files[1] as $file) {
				if (copy($src . $file, $path . $file)) {
					@chmod($path . $file, 0666);
				} else {
					$result = false;
				}
			}
		}
		return $result;
	}

/**
 * アップロード用初期フォルダを作成する
 */
	public function createDefaultFiles() {
		$dirs = array('blog', 'editor', 'theme_configs');
		$path = WWW_ROOT . 'files' . DS;
		$Folder = new Folder();

		$result = true;
		foreach ($dirs as $dir) {
			if (!is_dir($path . $dir)) {
				if (!$Folder->create($path . $dir, 0777)) {
					$result = false;
				}
			}
		}
		return $result;
	}

/**
 * 設定ファイルをリセットする
 * 
 * @return boolean 
 * @access public
 */
	public function resetSetting() {
		$result = true;
		if (file_exists(APP . 'Config' . DS . 'database.php')) {
			if (!unlink(APP . 'Config' . DS . 'database.php')) {
				$result = false;
			}
		}
		if (file_exists(APP . 'Config' . DS . 'install.php')) {
			if (!unlink(APP . 'Config' . DS . 'install.php')) {
				$result = false;
			}
		}
		return $result;
	}

/**
 * テーマのページテンプレートを初期化する 
 * 
 * @return boolean
 * @access public
 */
	public function resetThemePages() {
		$result = true;
		$themeFolder = new Folder(WWW_ROOT . 'theme');
		$themeFiles = $themeFolder->read(true, true, true);
		foreach ($themeFiles[0] as $theme) {
			$pagesFolder = new Folder($theme . DS . 'Pages');
			$pathes = $pagesFolder->read(true, true, true);
			foreach ($pathes[0] as $path) {
				if (basename($path) != 'admin') {
					$folder = new Folder();
					if (!$folder->delete($path)) {
						$result = false;
					}
					$folder = null;
				}
			}
			foreach ($pathes[1] as $path) {
				if (basename($path) != 'empty') {
					if (!unlink($path)) {
						$result = false;
					}
				}
			}
			$pagesFolder = null;
		}
		$themeFolder = null;
		return $result;
	}

/**
 * files フォルダを初期化する
 * 
 * @return boolean
 */
	public function resetFiles() {
		return $this->resetEmptyFolder(WWW_ROOT . 'files');
	}
	
/**
 * 管理画面用のアセットフォルダ（img / js / css）を初期化する
 * 
 * @return boolean
 */
	public function resetAdminAssets() {
		$paths = array(
			WWW_ROOT . 'img' . DS . 'admin',
			WWW_ROOT . 'css' . DS . 'admin',
			WWW_ROOT . 'js' . DS . 'admin'
		);
		$result = true;
		foreach($paths as $path) {
			if(is_dir($path)) {
				$Folder = new Folder($path);
				if(!$Folder->delete()) {
					$result = false;
				}
				$Folder = null;
			}
		}
		return $result;
	}
	
/**
 * empty ファイルを梱包したフォルダをリセットする
 * 
 * empty ファイルを残して内包するファイルとフォルダを全て削除する
 * 
 * @param string $path
 * @return boolean
 */
	public function resetEmptyFolder($path) {
		$result = true;
		$Folder = new Folder($path);
		$files = $Folder->read(true, true, true);
		$Folder = null;
		if(!empty($files[0])) {
			foreach($files[0] as $file) {
				$Folder = new Folder();
				if (!$Folder->delete($file)) {
					$result = false;
				}
				$Folder = null;
			}
		}
		if(!empty($files[1])) {
			foreach($files[1] as $file) {
				if(basename($file) != 'empty') {
					$Folder = new Folder();
					if (!$Folder->delete($file)) {
						$result = false;
					}
					$Folder = null;
				}
			}
		}
		return $result;
	}
	
/**
 * baserCMSをリセットする
 * 
 * @param array $dbConfig 
 * @access public
 */
	public function reset($dbConfig) {
		$result = true;

		if (BC_INSTALLED) {
			// 設定ファイルを初期化
			if (!$this->resetSetting()) {
				$result = false;
				$this->log('設定ファイルを正常に初期化できませんでした。');
			}
			// テーブルを全て削除
			if (!$this->deleteAllTables($dbConfig)) {
				$result = false;
				$this->log('データベースを正常に初期化できませんでした。');
			}
		}

		// テーマのページテンプレートを初期化
		if (!$this->resetThemePages()) {
			$result = false;
			$this->log('テーマのページテンプレートを初期化できませんでした。');
		}
		
		// files フォルダの初期化
		if (!$this->resetFiles()) {
			$result = false;
			$this->log('files フォルダを初期化できませんでした。');
		}
		
		// files フォルダの初期化
		if (!$this->resetAdminAssets()) {
			$result = false;
			$this->log('img / css / js フォルダを初期化できませんでした。');
		}
		
		ClassRegistry::flush();
		clearAllCache();

		return $result;
	}

/**
 * インストール設定を書き換える
 *
 * @param	string	$key
 * @param	string	$value
 * @return	boolean
 * @access	public
 */
	public function setInstallSetting($key, $value) {
		/* install.php の編集 */
		$setting = "Configure::write('" . $key . "', " . $value . ");\n";
		$key = str_replace('.', '\.', $key);
		$pattern = '/Configure\:\:write[\s]*\([\s]*\'' . $key . '\'[\s]*,[\s]*([^\s]*)[\s]*\);(\n|)/is';
		$file = new File(APP . 'Config' . DS . 'install.php');
		if (file_exists(APP . 'Config' . DS . 'install.php')) {
			$data = $file->read();
		} else {
			$data = "<?php\n";
		}
		if (preg_match($pattern, $data)) {
			$data = preg_replace($pattern, $setting, $data);
		} else {
			$data = $data . "\n" . $setting;
		}
		$return = $file->write($data);
		$file->close();
		return $return;
	}

/**
 * 環境チェック
 * 
 * @return array 
 */
	public function checkEnv() {
		if (function_exists('apache_get_modules')) {
			$rewriteInstalled = in_array('mod_rewrite', apache_get_modules());
		} else {
			$rewriteInstalled = -1;
		}

		$status = array(
			'encoding'			=> mb_internal_encoding(),
			'phpVersion'		=> phpversion(),
			'phpMemory'			=> intval(ini_get('memory_limit')),
			'safeModeOff'		=> !ini_get('safe_mode'),
			'configDirWritable'	=> is_writable(APP . 'Config' . DS),
			'pluginDirWritable'	=> is_writable(APP . 'Plugin' . DS),
			'themeDirWritable'	=> is_writable(WWW_ROOT . 'theme'),
			'filesDirWritable'	=> is_writable(WWW_ROOT . 'files'),
			'imgDirWritable'	=> is_writable(WWW_ROOT . 'img'),
			'jsDirWritable'	=> is_writable(WWW_ROOT . 'js'),
			'cssDirWritable'	=> is_writable(WWW_ROOT . 'css'),
			'imgAdminDirExists'	=> is_dir(WWW_ROOT . 'img' . DS . 'admin'),
			'jsAdminDirExists'	=> is_dir(WWW_ROOT . 'js' . DS . 'admin'),
			'cssAdminDirExists'	=> is_dir(WWW_ROOT . 'css' . DS . 'admin'),
			'tmpDirWritable'	=> is_writable(TMP),
			'dbDirWritable'		=> is_writable(APP . 'db'),
			'phpActualVersion'	=> preg_replace('/[a-z-]/', '', phpversion()),
			'phpGd'				=> extension_loaded('gd'),
			'phpPdo'			=> extension_loaded('pdo'),
			'phpXml'			=> extension_loaded('xml'),
			'apacheRewrite'		=> $rewriteInstalled,
		);
		$check = array(
			'encodingOk'	=> (preg_match('/UTF-8/i', $status['encoding']) ? true : false),
			'gdOk'			=> $status['phpGd'],
			'pdoOk'			=> $status['phpPdo'],
			'xmlOk'			=> $status['phpXml'],
			'phpVersionOk'	=> version_compare(preg_replace('/[a-z-]/', '', $status['phpVersion']), Configure::read('BcRequire.phpVersion'), '>='),
			'phpMemoryOk'	=> ((($status['phpMemory'] >= Configure::read('BcRequire.phpMemory')) || $status['phpMemory'] == -1) === true)
		);

		if (!$status['configDirWritable']) {
			@chmod(APP . 'Config' . DS, 0777);
			$status['configDirWritable'] = is_writable(APP . 'Config' . DS);
		}
		if (!$status['pluginDirWritable']) {
			@chmod(APP . 'Plugin' . DS, 0777);
			$status['pluginDirWritable'] = is_writable(APP . 'Plugin' . DS);
		}
		if (!$status['themeDirWritable']) {
			@chmod(WWW_ROOT . 'theme', 0777);
			$status['themeDirWritable'] = is_writable(WWW_ROOT . 'theme');
		}
		if (!$status['filesDirWritable']) {
			@chmod(WWW_ROOT . 'files', 0777);
			$status['filesDirWritable'] = is_writable(WWW_ROOT . 'files');
		}
		if (!$status['imgDirWritable']) {
			@chmod(WWW_ROOT . 'img', 0777);
			$status['imgDirWritable'] = is_writable(WWW_ROOT . 'img');
		}
		if (!$status['cssDirWritable']) {
			@chmod(WWW_ROOT . 'css', 0777);
			$status['cssDirWritable'] = is_writable(WWW_ROOT . 'css');
		}
		if (!$status['jsDirWritable']) {
			@chmod(WWW_ROOT . 'js', 0777);
			$status['jsDirWritable'] = is_writable(WWW_ROOT . 'js');
		}
		if (!$status['tmpDirWritable']) {
			@chmod(TMP, 0777);
			$status['tmpDirWritable'] = is_writable(TMP);
		}
		if (!$status['dbDirWritable']) {
			@chmod(APP . 'db', 0777);
			$status['dbDirWritable'] = is_writable(APP . 'db');
		}

		return $status + $check;
	}

/**
 * DB接続チェック
 * 
 * @param	string	$datasource 'MySQL' or 'Postgres' or 'SQLite' or 'CSV'
 * @param	string	$database データベース名 SQLiteの場合はファイルパス CSVの場合はディレクトリへのパス
 * @param	string	$host テキストDB or localhostの場合は不要
 * @param	string	$port 接続ポート テキストDBの場合は不要
 * @param	string	$login 接続ユーザ名 テキストDBの場合は不要
 * @param	string	$password 接続パスワード テキストDBの場合は不要
 * 
 * @throws Exception
 * @throws PDOException
 * @return boolean
 * @access private
 */
	public function checkDbConnection($config) {
		extract($config);

		$datasource = strtolower($datasource);

		try {
			if ($datasource == 'mysql') {
				$dsn = "mysql:dbname={$database}";
				if ($host) {
					$dsn .= ";host={$host}";
				}
				if ($port) {
					$dsn .= ";port={$port}";
				}
				$pdo = new PDO($dsn, $login, $password);
			} elseif ($datasource == 'postgres') {
				$dsn = "pgsql:dbname={$database}";
				if ($host) {
					$dsn .= ";host={$host}";
				}
				if ($port) {
					$dsn .= ";port={$port}";
				}
				$pdo = new PDO($dsn, $login, $password);
			} elseif ($datasource == 'sqlite') {
				// すでにある場合
				if (file_exists($database)) {
					if (!is_writable($database)) {
						throw new Exception("データベースファイルに書き込み権限がありません。");
					}
					// ない場合
				} else {
					if (!is_writable(dirname($database))) {
						throw new Exception("データベースの保存フォルダに書き込み権限がありません。");
					}
				}
				$dsn = "sqlite:" . $database;
				$pdo = new PDO($dsn);
			} elseif ($datasource == 'csv') {
				if (is_writable($database)) {
					return true;
				}
				throw new Exception("データベースの保存フォルダに書き込み権限がありません。");
			} else {
				// ドライバが見つからない
				throw new Exception("ドライバが見つかりません Driver is not defined.(MySQL|Postgres|SQLite|CSV)");
			}
		} catch (PDOException $e) {
			throw new PDOException($e);
		}

		// 接続できたよ
		if ($pdo) {
			// disconnect
			unset($pdo);
			return true;
		}
	}

/**
 * 管理システムアセットへのシンボリックリンクをテーマフォルダ内に作成したかチェックする
 * 作成してないものがひとつでもあると false を返す
 * 
 * @return boolean
 * @deprecated since version 3.0.1
 */
	public function isCreatedAdminAssetsSymlink() {
		// Windowsの場合シンボリックリンクをサポートしないのでそのままtrueを返す
		if (DS == '\\') {
			return true;
		}

		$viewPath = getViewPath();
		$css = $viewPath . 'css' . DS . 'admin';
		$js = $viewPath . 'js' . DS . 'admin';
		$img = $viewPath . 'img' . DS . 'admin';
		$result = true;
		if (!is_dir($css) && !is_link($css)) {
			$result = false;
		}
		if (!is_dir($js) && !is_link($js)) {
			$result = false;
		}
		if (!is_dir($img) && !is_link($img)) {
			$result = false;
		}
		return $result;
	}
	
/**
 * テーマに管理システム用アセットを配置する
 * 
 * @return boolean
 */
	public function deployAdminAssets() {
		$viewPath = WWW_ROOT;
		$adminCss = BASER_WEBROOT . 'css' . DS . 'admin';
		$adminJs = BASER_WEBROOT . 'js' . DS . 'admin';
		$adminImg = BASER_WEBROOT . 'img' . DS . 'admin';
		$css = $viewPath . 'css' . DS . 'admin';
		$js = $viewPath . 'js' . DS . 'admin';
		$img = $viewPath . 'img' . DS . 'admin';
		$result = true;
		$Folder = new Folder();
		if(!$Folder->copy(array(
			'from'	=> $adminCss,
			'to'	=> $css,
			'mode'	=> 0777
		))) {
			$result = false;
		}
		if(!$Folder->copy(array(
			'from'	=> $adminJs,
			'to'	=> $js,
			'mode'	=> 0777
		))) {
			
		}
		if(!$Folder->copy(array(
			'from'	=> $adminImg,
			'to'	=> $img,
			'mode'	=> 0777
		))) {
			$result = false;
		}
		return $result;
	}
	
/**
 * プラグインをインストールする
 * 
 * @param string $name
 * @return boolean
 */
	public function installPlugin($name, $dbDataPattern = '') {

		$paths = App::path('Plugin');
		$exists = false;
		foreach($paths as $path) {
			if (file_exists($path . $name)) {
				$exists = true;
				break;
			}
		}
		
		if(!$exists) {
			return false;
		}
		
		$this->Plugin = ClassRegistry::init('Plugin');
		$data = $this->Plugin->find('first', array('conditions' => array('name' => $name)));
		$title = '';

		if (empty($data['Plugin']['db_inited'])) {
			$initPath = $path . $name . DS . 'Config' . DS . 'init.php';
			if (file_exists($initPath)) {
				$this->initPlugin($initPath, $dbDataPattern);
			}
		}
		$configPath = $path . $name . DS . 'config.php';
		if(file_exists($configPath)) {
			include $configPath;
		}

		if(empty($title)) {
			if(!empty($data['Plugin']['title'])) {
				$title = $data['Plugin']['title'];
			} else {
				$title = $name;
			}
		}
		
		if ($data) {
			// 既にインストールデータが存在する場合は、DBのバージョンは変更しない
			$data = array_merge($data['Plugin'], array(
				'name'		=> $name,
				'title'		=> $title,
				'status'	=> true,
				'db_inited'	=> true
			));
			$this->Plugin->set($data);
		} else {
			$corePlugins = Configure::read('BcApp.corePlugins');
			if (in_array($name, $corePlugins)) {
				$version = getVersion();
			} else {
				$version = getVersion($name);
			}

			$priority = intval($this->Plugin->getMax('priority')) + 1;
			$data = array('Plugin' => array(
				'name'		=> $name,
				'title'		=> $title,
				'status'	=> true,
				'db_inited'	=> true,
				'version'	=> $version,
				'priority' => $priority
			));
			$this->Plugin->create($data);
		}

		// データを保存
		if ($this->Plugin->save()) {
			return true;
		} else {
			return false;
		}
	}

/**
 * プラグインを初期化
 *
 * @param $_path
 */
	public function initPlugin($_path, $dbDataPattern = '') {
		if (file_exists($_path)) {
			try {
				include $_path;
			} catch (Exception $e) {
				$this->log($e->getMessage());
			}
		}
	}
/**
 * プラグインをアンインストールする
 * 
 * @param string $name
 * @return boolean
 */
	public function uninstallPlugin($name) {
		
		$Plugin = ClassRegistry::init('Plugin');
		$data = $Plugin->find('first', array('conditions' => array('Plugin.name' => $name), 'recursive' => -1));
		$data['Plugin']['status'] = false;
		if ($Plugin->save($data)) {
			clearAllCache();
			return true;
		} else {
			return false;
		}
		
	}
	
}
