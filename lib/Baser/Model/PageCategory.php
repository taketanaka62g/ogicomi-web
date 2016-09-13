<?php

/**
 * ページカテゴリーモデル
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
 * ページカテゴリーモデル
 *
 * @package Baser.Model
 */
class PageCategory extends AppModel {

/**
 * クラス名
 *
 * @var string
 * @access public
 */
	public $name = 'PageCategory';

/**
 * データベース接続
 *
 * @var string
 * @access public
 */
	public $useDbConfig = 'baser';

/**
 * actsAs
 * 
 * @var array
 * @access public
 */
	public $actsAs = array('Tree', 'BcCache');

/**
 * hasMany
 * 
 * @var array
 * @access public
 */
	public $hasMany = array('Page' => array('className' => 'Page',
			'conditions' => '',
			'order' => 'Page.sort',
			'limit' => '',
			'foreignKey' => 'page_category_id',
			'dependent' => false,
			'exclusive' => false,
			'finderQuery' => ''));

/**
 * ページカテゴリフォルダのパスリスト
 * キーはカテゴリID
 * キャッシュ用
 * 
 * @var mixed
 * @access protected
 */
	protected $_pageCategoryPathes = -1;

/**
 * エージェントカテゴリのID
 * 
 * @var array
 * @access	protected
 */
	protected $_agentId = array();

/**
 * 保存時に関連ページを更新するかどうか
 * 
 * @var boolean
 * @access public
 */
	public $updateRelatedPage = true;

/**
 * バリデーション
 *
 * @var array
 * @access public
 */
	public $validate = array(
		'name' => array(
			array('rule' => array('minLength', 1),
				'message' => 'ページカテゴリ名を入力してください。',
				'required' => true),
			'alphaNumericPlus' => array(
				'rule' => 'alphaNumericPlus',
				'message' => 'ページカテゴリ名は半角英数字とハイフン、アンダースコアのみで入力してください。'
			),
			array('rule' => array('maxLength', 50),
				'message' => 'ページカテゴリ名は50文字以内で入力してください。'),
			array('rule' => array('duplicatePageCategory'),
				'message' => '入力されたページカテゴリー名は、同一階層に既に登録されています。')
		),
		'title' => array(
			array('rule' => array('minLength', 1),
				'message' => 'ページカテゴリタイトルを入力してください。',
				'required' => true),
			array('rule' => array('maxLength', 255),
				'message' => 'ページカテゴリタイトルは255文字以内で入力してください。')
		)
	);

/**
 * コントロールソースを取得する
 *
 * @param string $field フィールド名
 * @param array $options オプション
 * @return array コントロールソース
 * @access public
 */
	public function getControlSource($field, $options = array()) {
		switch ($field) {

			case 'parent_id':

				$conditions = array();
				if (isset($options['conditions'])) {
					$conditions = $options['conditions'];
				}

				if (!empty($options['excludeParentId'])) {
					if (!is_array($options['excludeParentId'])) {
						$options['excludeParentId'] = array($options['excludeParentId']);
					}
					$excludeIds = array();
					foreach ($options['excludeParentId'] as $excludeParentId) {
						$children = $this->children($excludeParentId);
						if ($children) {
							$excludeIds = am($excludeIds, Hash::extract($children, '{n}.PageCategory.id'));
						}
						$excludeIds[] = $excludeParentId;
					}
					$conditions['NOT']['PageCategory.id'] = $excludeIds;
				}

				$parentIds = array();
				if (!empty($options['parentId'])) {
					if (!is_array($options['parentId'])) {
						$options['parentId'] = array($options['parentId']);
					}
					foreach ($options['parentId'] as $parentId) {
						$children = $this->children($parentId);
						if ($children) {
							$parentIds = am($parentIds, Hash::extract($children, '{n}.PageCategory.id'));
						} else {
							return array();
						}
					}
				}

				if ($parentIds) {
					$conditions['PageCategory.id'] = $parentIds;
				}

				$parents = $this->generateTreeList($conditions);
				$controlSources['parent_id'] = array();

				$excludeIds = array();
				if (!Configure::read('BcApp.mobile')) {
					$excludeIds = array_merge($excludeIds, $this->getAgentCategoryIds('mobile'));
				}
				if (!Configure::read('BcApp.smartphone')) {
					$excludeIds = array_merge($excludeIds, $this->getAgentCategoryIds('smartphone'));
				}

				foreach ($parents as $key => $parent) {
					if ($parent && !in_array($key, $excludeIds)) {
						if (preg_match("/^([_]+)/i", $parent, $matches)) {
							$parent = preg_replace("/^[_]+/i", '', $parent);
							$prefix = str_replace('_', '&nbsp&nbsp&nbsp', $matches[1]);
							$parent = $prefix . '└' . $parent;
						}
						$controlSources['parent_id'][$key] = $parent;
					}
				}
				break;
			case 'owner_id':
				$UserGroup = ClassRegistry::init('UserGroup');
				$controlSources['owner_id'] = $UserGroup->find('list', array('fields' => array('id', 'title'), 'recursive' => -1));
				break;
		}

		if (isset($controlSources[$field])) {
			return $controlSources[$field];
		} else {
			return false;
		}
	}

/**
 * beforeSave
 * 
 * @return boolean
 * @access public
 */
	public function beforeSave($options = array()) {

		// セーフモードの場合はフォルダの自動生成は行わない
		if (ini_get('safe_mode')) {
			return true;
		}
		// 新しいページファイルのパスを取得する
		$newPath = $this->createPageCategoryFolder($this->data);
		if ($this->exists()) {
			$oldPath = $this->createPageCategoryFolder($this->find('first', array('conditions' => array('id' => $this->id))));
			if ($newPath != $oldPath) {
				$dir = new Folder();
				$ret = $dir->move(array('to' => $newPath, 'from' => $oldPath, 'chmod' => 0777));
			} else {
				if (!is_dir($newPath)) {
					$dir = new Folder();
					$ret = $dir->create($newPath, 0777);
				}
				$ret = true;
			}
		} else {
			$dir = new Folder();
			$ret = $dir->create($newPath, 0777);
		}

		return $ret;
	}

/**
 * afterSave
 * 
 * @param boolean $created
 * @return void
 * @access public
 */
	public function afterSave($created, $options = array()) {
		if (!$created && $this->updateRelatedPage) {
			$this->updateRelatedPageUrlRecursive($this->data['PageCategory']['id']);
		}
	}

/**
 * ページカテゴリのフォルダを生成してパスを返す
 * 
 * @param array $data ページカテゴリデータ
 * @return mixid カテゴリのパス / false
 * @access public
 */
	public function createPageCategoryFolder($data) {
		$path = $this->getPageCategoryFolderPath($data);
		$folder = new Folder();
		if ($folder->create($path, 0777)) {
			return $path;
		} else {
			return false;
		}
	}

/**
 * カテゴリフォルダのパスを取得する
 * 
 * @param array $data ページカテゴリデータ
 * @return string $path
 * @access public
 */
	public function getPageCategoryFolderPath($data) {
		if (isset($data['PageCategory'])) {
			$data = $data['PageCategory'];
		}

		$path = $pagesPath = getViewPath() . 'Pages' . DS;
		$categoryName = $data['name'];
		$parentId = $data['parent_id'];

		if ($parentId) {
			$categoryPath = $this->getPath($parentId);
			if ($categoryPath) {
				foreach ($categoryPath as $category) {
					$path .= $category['PageCategory']['name'] . DS;
				}
			}
		}
		return $path . $categoryName;
	}

/**
 * 同一階層に同じニックネームのカテゴリがないかチェックする
 * 同じテーマが条件
 * 
 * @param array $check
 * @return boolean
 * @access public
 */
	public function duplicatePageCategory($check) {
		$parentId = $this->data['PageCategory']['parent_id'];
		if ($parentId) {
			$conditions['PageCategory.parent_id'] = $parentId;
		} else {
			$conditions['OR'] = array('PageCategory.parent_id' => '');
			$conditions['OR'] = array('PageCategory.parent_id' => null);
		}

		$children = $this->find('all', array('conditions' => $conditions));

		if ($children) {
			foreach ($children as $child) {
				if ($this->exists()) {
					if ($this->id == $child['PageCategory']['id']) {
						continue;
					}
				}
				if ($child['PageCategory']['name'] == $check[key($check)]) {
					return false;
				}
			}
		}
		return true;
	}

/**
 * 関連するページデータをカテゴリ無所属に変更し保存する
 * 
 * @param boolean $cascade
 * @return boolean
 * @access public
 */
	public function beforeDelete($cascade = true) {
		parent::beforeDelete($cascade);
		$id = $this->data['PageCategory']['id'];
		if ($this->releaseRelatedPagesRecursive($id)) {
			$path = $this->createPageCategoryFolder($this->find('first', array('conditions' => array('id' => $id))));
			$folder = new Folder();
			$folder->delete($path);
			return true;
		} else {
			return false;
		}
	}

/**
 * 関連するページのカテゴリを解除する（再帰的）
 * 
 * @param int $categoryId ページカテゴリーID
 * @return boolean
 * @access public
 */
	public function releaseRelatedPagesRecursive($categoryId) {
		if (!$this->releaseRelatedPages($categoryId)) {
			return false;
		}
		$children = $this->children($categoryId);
		$ret = true;
		foreach ($children as $child) {
			if (!$this->releaseRelatedPages($child['PageCategory']['id'])) {
				$ret = false;
			}
		}
		return $ret;
	}

/**
 * 関連するページのカテゴリを解除する
 * 
 * @param int $categoryId ページカテゴリーID
 * @return boolean
 * @access public
 */
	public function releaseRelatedPages($categoryId) {
		$pages = $this->Page->find('all', array('conditions' => array('Page.page_category_id' => $categoryId), 'recursive' => -1));
		$ret = true;
		if ($pages) {
			foreach ($pages as $page) {
				$page['Page']['page_category_id'] = '';
				$page['Page']['url'] = $this->Page->getPageUrl($page);
				$this->Page->set($page);
				if (!$this->Page->save()) {
					$ret = false;
				}
			}
		}
		return $ret;
	}

/**
 * 関連するページデータのURLを更新する
 * 
 * @param string $categoryId ページカテゴリーID
 * @return void
 * @access public
 */
	public function updateRelatedPageUrlRecursive($categoryId) {
		if (!$this->updateRelatedPageUrl($categoryId)) {
			return false;
		}
		$children = $this->children($categoryId);

		$ret = true;
		foreach ($children as $child) {
			if (!$this->updateRelatedPageUrl($child['PageCategory']['id'])) {
				$ret = false;
			}
		}
		return $ret;
	}

/**
 * 関連するページデータのURLを更新する
 * 
 * @param string $id ページカテゴリーID
 * @return void
 * @access public
 */
	public function updateRelatedPageUrl($id) {
		if (!$id) {
			return;
		}
		$pages = $this->Page->find('all', array('conditions' => array('Page.page_category_id' => $id), 'recursive' => -1));
		$result = true;
		// ページデータのURLを更新
		if ($pages) {
			$this->Page->saveFile = false;
			foreach ($pages as $page) {
				$page['Page']['url'] = $this->Page->getPageUrl($page);
				$this->Page->set($page);
				if (!$this->Page->save()) {
					$result = false;
				}
			}
		}
		return $result;
	}

/**
 * カテゴリフォルダのパスから対象となるデータが存在するかチェックする
 * 存在する場合は ページカテゴリーID を返す
 * 
 * @param string $path カテゴリフォルダのパス
 * @return int
 * @access public
 */
	public function getIdByPath($path) {
		if ($this->_pageCategoryPathes == -1) {
			$this->_pageCategoryPathes = array();
			$pageCategories = $this->find('all');
			if ($pageCategories) {
				foreach ($pageCategories as $pageCategory) {
					$this->_pageCategoryPathes[$pageCategory['PageCategory']['id']] = $this->getPageCategoryFolderPath($pageCategory);
				}
			}
		}

		if (in_array($path, $this->_pageCategoryPathes)) {
			return array_search($path, $this->_pageCategoryPathes);
		} else {
			return false;
		}
	}

/**
 * モバイル用のカテゴリIDをリストで取得する
 * 
 * @param string $type ページカテゴリーのタイプ
 * @param boolean $top 一番上の親カテゴリも取得するかどうか
 * @return array $ids
 * @access public
 */
	public function getAgentCategoryIds($type = 'mobile', $top = true) {
		$agentId = $this->getAgentId($type);
		if (!$agentId) {
			return array();
		}
		$ids = array();
		if ($top) {
			$ids[] = $agentId;
		}

		$children = $this->children($agentId, false, array('PageCategory.id'), array('PageCategory.id'));
		if ($children) {
			$children = Hash::extract($children, '{n}.PageCategory.id');
			$ids = am($ids, $children);
		}
		return $ids;
	}

/**
 * エージェントカテゴリのIDを取得する
 * 
 * @param int $targetId
 * @return int
 * @access public
 */
	public function getAgentId($type = 'mobile') {
		if (!isset($this->_agentId[$type])) {
			$result = $this->find('first', array('fields' => 'PageCategory.id', 'conditions' => array(
				'PageCategory.name' => $type,
				'PageCategory.parent_id' => null
			)));
			if ($result) {
				$this->_agentId[$type] = $result['PageCategory']['id'];
			} else {
				return false;
			}
		}

		return $this->_agentId[$type];
	}

/**
 * PCのIDを元にモバイル・スマホの相対階層のIDを取得する
 * 
 * @param string type ユーザーエージェントのタイプ
 * @param int $id ページカテゴリーID	
 * @return mixed $categoryId Or false
 */
	public function getAgentRelativeId($type, $id) {
		if (!$id) {
			// ルート
			$agentId = $this->getAgentId($type);
			if($agentId) {
				return (int) $agentId;
			} else {
				return false;
			}
		} else {
			$path = $this->getPath($id, array('name'), -1);
			if(!is_array($path)) {
				return false;
			}
			$path = Hash::extract($path, '{n}.PageCategory.name');
			if($path[0] == $type) {
				return false;
			}
			$path = implode(DS, $path);
			$path = getViewPath() . 'Pages' . DS . $type . DS . $path;
		}
		$categoryId = $this->getIdByPath($path);

		return $categoryId;
	}

/**
 * ツリーリストを取得する
 * 
 * @param array $fields 出力するページカテゴリーのフィールド名
 * @param int $id ページカテゴリーID
 * @return array
 * @access public 
 */
	public function getTreeList($fields, $id) {
		$this->recursive = -1;
		$pageCategories = array();
		$pageCategories[] = $pageCategory = $this->read($fields, $id);
		if (!empty($pageCategory['PageCategory']['parent_id'])) {
			$parents = $this->getTreeList($fields, $pageCategory['PageCategory']['parent_id']);
			$pageCategories = am($parents, $pageCategories);
		}
		return $pageCategories;
	}

/**
 * 新しいカテゴリが追加できる状態かチェックする
 * 
 * @param int $userGroupId ユーザグループID	
 * @param boolean $rootEditable 編集可/不可
 * @return boolean
 * @access public
 */
	public function checkNewCategoryAddable($userGroupId, $rootEditable) {
		$newCatAddable = false;
		$ownerCats = $this->find('count', array(
			'conditions' => array(
				'OR' => array(
					array('PageCategory.owner_id' => null),
					array('PageCategory.owner_id' => $userGroupId)
				),
				array('PageCategory.id <>' => $this->getAgentId('mobile')),
				array('PageCategory.id <>' => $this->getAgentId('smartphone'))
		)));

		if ($ownerCats || $rootEditable) {
			$newCatAddable = true;
		}
		return $newCatAddable;
	}

/**
 * ページカテゴリーをコピーする
 * 
 * @param int $id ページカテゴリーID
 * @param array $data ページカテゴリーのデータ
 * @return mixed page Or false
 */
	public function copy($id = null, $data = array()) {
		if ($id) {
			$data = $this->find('first', array('conditions' => array('PageCategory.id' => $id), 'recursive' => -1));
		}
		$data['PageCategory']['name'] .= '_copy';
		$data['PageCategory']['title'] .= '_copy';
		unset($data['PageCategory']['id']);
		unset($data['PageCategory']['created']);
		unset($data['PageCategory']['modified']);

		$this->create($data);
		$result = $this->save();
		if ($result) {
			return $result;
		} else {
			if (isset($this->validationErrors['name'])) {
				return $this->copy(null, $data);
			} else {
				return false;
			}
		}
	}

/**
 * ページカテゴリタイプを取得する
 * 1:PC / 2:ケータイ / 3:スマフォ
 * 
 * @param int $id
 * @return string 
 * @access public
 */
	public function getType($id) {
		$types = array('' => '1', Configure::read('BcAgent.mobile.prefix') => '2', Configure::read('BcAgent.smartphone.prefix') => '3');
		$path = $this->getPath($id, array('name'));
		unset($path[count($path) - 1]);

		if (!empty($path[0]['PageCategory']['name'])) {
			if (isset($types[$path[0]['PageCategory']['name']])) {
				return $types[$path[0]['PageCategory']['name']];
			}
		}

		return 1;
	}

}
