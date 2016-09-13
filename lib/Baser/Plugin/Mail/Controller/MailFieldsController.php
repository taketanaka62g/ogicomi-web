<?php

/**
 * メールフィールドコントローラー
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2015, baserCMS Users Community <http://sites.google.com/site/baserusers/>
 *
 * @copyright		Copyright 2008 - 2015, baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Mail.Controller
 * @since			baserCMS v 0.1.0
 * @license			http://basercms.net/license/index.html
 */
/**
 * Include files
 */

/**
 * メールフィールドコントローラー
 *
 * @package Mail.Controller
 */
class MailFieldsController extends MailAppController {

/**
 * クラス名
 *
 * @var string
 * @access public
 */
	public $name = 'MailFields';

/**
 * モデル
 *
 * @var array
 * @access public
 */
	public $uses = array('Mail.MailField', 'Mail.MailContent', 'Mail.Message');

/**
 * ヘルパー
 *
 * @var array
 * @access public
 */
	public $helpers = array('BcHtml', 'BcTime', 'BcForm', 'BcText', 'BcCsv');

/**
 * コンポーネント
 *
 * @var array
 * @access public
 */
	public $components = array('BcAuth', 'Cookie', 'BcAuthConfigure');

/**
 * ぱんくずナビ
 *
 * @var array
 * @access public
 */
	public $crumbs = array(
		array('name' => 'メールフォーム管理', 'url' => array('plugin' => 'mail', 'controller' => 'mail_contents', 'action' => 'index'))
	);

/**
 * サブメニューエレメント
 *
 * @var string
 * @access public
 */
	public $subMenuElements = array();

/**
 * beforeFilter
 *
 * @return void
 * @access public
 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->MailContent->recursive = -1;
		$this->mailContent = $this->MailContent->read(null, $this->params['pass'][0]);
		$this->crumbs[] = array('name' => $this->mailContent['MailContent']['title'] . '管理', 'url' => array('plugin' => 'mail', 'controller' => 'mail_fields', 'action' => 'index', $this->params['pass'][0]));
	}

/**
 * beforeRender
 *
 * @return void
 * @access public
 */
	public function beforeRender() {
		parent::beforeRender();
		$this->set('mailContent', $this->mailContent);
	}

/**
 * [ADMIN] メールフィールド一覧
 *
 * @param int $mailContentId
 * @return void
 * @access public
 */
	public function admin_index($mailContentId) {
		if (!$mailContentId || !$this->mailContent) {
			$this->setMessage('無効な処理です。', true);
			$this->redirect(array('controller' => 'mail_contents', 'action' => 'index'));
		}

		$conditions = $this->_createAdminIndexConditions($mailContentId);
		$datas = $this->MailField->find('all', array('conditions' => $conditions, 'order' => 'MailField.sort'));
		$this->set('datas', $datas);

		$this->_setAdminIndexViewData();

		if ($this->RequestHandler->isAjax() || !empty($this->query['ajax'])) {
			$this->render('ajax_index');
			return;
		}

		$this->set('publishLink', '/' . $this->mailContent['MailContent']['name'] . '/index');
		$this->subMenuElements = array('mail_fields');
		$this->pageTitle = '[' . $this->mailContent['MailContent']['title'] . '] メールフィールド一覧';
		$this->help = 'mail_fields_index';
	}

/**
 * 一覧の表示用データをセットする
 * 
 * @return void
 * @access protected
 */
	protected function _setAdminIndexViewData() {
		/* セッション処理 */
		if (isset($this->params['named']['sortmode'])) {
			$this->Session->write('SortMode.MailField', $this->params['named']['sortmode']);
		}

		/* 並び替えモード */
		if (!$this->Session->check('SortMode.MailField')) {
			$this->set('sortmode', 0);
		} else {
			$this->set('sortmode', $this->Session->read('SortMode.MailField'));
		}
	}

/**
 * [ADMIN] メールフィールド追加
 *
 * @param int $mailContentId
 * @return void
 * @access public
 */
	public function admin_add($mailContentId) {
		if (!$mailContentId || !$this->mailContent) {
			$this->setMessage('無効な処理です。', true);
			$this->redirect(array('controller' => 'mail_contents', 'action' => 'index'));
		}

		if (!$this->request->data) {
			$this->request->data = $this->_getDefaultValue();
		} else {

			/* 登録処理 */
			$data = $this->request->data;
			if (is_array($data['MailField']['valid_ex'])) {
				$data['MailField']['valid_ex'] = implode(',', $data['MailField']['valid_ex']);
			}
			$data['MailField']['mail_content_id'] = $mailContentId;
			$data['MailField']['no'] = $this->MailField->getMax('no', array('MailField.mail_content_id' => $mailContentId)) + 1;
			$data['MailField']['sort'] = $this->MailField->getMax('sort') + 1;
			$this->MailField->create($data);
			if ($this->MailField->validates()) {
				if ($this->Message->addMessageField($this->mailContent['MailContent']['name'], $data['MailField']['field_name'])) {
					// データを保存
					if ($this->MailField->save(null, false)) {
						$this->setMessage('新規メールフィールド「' . $data['MailField']['name'] . '」を追加しました。', false, true);
						$this->redirect(array('controller' => 'mail_fields', 'action' => 'index', $mailContentId));
					} else {
						$this->setMessage('データベース処理中にエラーが発生しました。', true);
					}
				} else {
					$this->setMessage('データベースに問題があります。メール受信データ保存用テーブルの更新処理に失敗しました。', true);
				}
			} else {
				$this->setMessage('入力エラーです。内容を修正してください。', true);
			}
		}

		$this->subMenuElements = array('mail_fields');
		$this->pageTitle = '[' . $this->mailContent['MailContent']['title'] . '] 新規メールフィールド登録';
		$this->help = 'mail_fields_form';
		$this->render('form');
	}

/**
 * [ADMIN] 編集処理
 *
 * @param int $mailContentId
 * @param int $id
 * @return void
 * @access public
 */
	public function admin_edit($mailContentId, $id) {
		if (!$id && empty($this->request->data)) {
			$this->setMessage('無効なIDです。', true);
			$this->redirect(array('action' => 'index'));
		}

		if (empty($this->request->data)) {
			$data = $this->MailField->read(null, $id);
			$data['MailField']['valid_ex'] = explode(',', $data['MailField']['valid_ex']);
			$this->request->data = $data;
		} else {
			$old = $this->MailField->read(null, $id);
			$data = $this->request->data;
			if (is_array($data['MailField']['valid_ex'])) {
				$data['MailField']['valid_ex'] = implode(',', $data['MailField']['valid_ex']);
			}
			$this->MailField->set($data);
			if ($this->MailField->validates()) {
				$ret = true;
				if ($old['MailField']['field_name'] != $data['MailField']['field_name']) {
					$ret = $this->Message->renameMessageField($this->mailContent['MailContent']['name'], $old['MailField']['field_name'], $data['MailField']['field_name']);
				}
				if ($ret) {
					/* 更新処理 */
					if ($this->MailField->save(null, false)) {
						$this->setMessage('メールフィールド「' . $data['MailField']['name'] . '」を更新しました。', false, true);
						$this->redirect(array('action' => 'index', $mailContentId));
					} else {
						$this->setMessage('データベース処理中にエラーが発生しました。', true);
					}
				} else {
					$this->setMessage('データベースに問題があります。メール受信データ保存用テーブルの更新処理に失敗しました。', true);
				}
			} else {
				$this->setMessage('入力エラーです。内容を修正してください。', true);
			}
		}

		/* 表示設定 */
		$this->subMenuElements = array('mail_fields');
		$this->pageTitle = '[' . $this->mailContent['MailContent']['title'] . '] メールフィールド編集：　' . $this->request->data['MailField']['name'];
		$this->help = 'mail_fields_form';
		$this->render('form');
	}

/**
 * [ADMIN] 削除処理（Ajax）
 *
 * @param int $mailContentId
 * @param int $id
 * @return void
 * @access public
 */
	public function admin_ajax_delete($mailContentId, $id = null) {
		/* 除外処理 */
		if (!$id) {
			$this->ajaxError(500, '無効な処理です。');
		}
		// メッセージ用にデータを取得
		$mailField = $this->MailField->read(null, $id);

		/* 削除処理 */
		if ($this->Message->delMessageField($this->mailContent['MailContent']['name'], $mailField['MailField']['field_name'])) {
			if ($this->MailField->delete($id)) {
				$this->MailField->saveDbLog('メールフィールド「' . $mailField['MailField']['name'] . '」 を削除しました。');
				exit(true);
			}
		} else {
			$this->ajaxError(500, 'データベースに問題があります。メール受信データ保存用テーブルの更新処理に失敗しました。');
		}
		exit();
	}

/**
 * [ADMIN] 削除処理
 *
 * @param int $mailContentId
 * @param int $id
 * @return void
 * @access public
 */
	public function admin_delete($mailContentId, $id = null) {
		/* 除外処理 */
		if (!$id) {
			$this->setMessage('無効なIDです。', true);
			$this->redirect(array('action' => 'admin_index'));
		}

		// メッセージ用にデータを取得
		$mailField = $this->MailField->read(null, $id);

		/* 削除処理 */
		if ($this->Message->delMessageField($this->mailContent['MailContent']['name'], $mailField['MailField']['field_name'])) {
			if ($this->MailField->delete($id)) {
				$this->setMessage('メールフィールド「' . $mailField['MailField']['name'] . '」 を削除しました。', false, true);
			} else {
				$this->setMessage('データベース処理中にエラーが発生しました。', true);
			}
		} else {
			$this->setMessage('データベースに問題があります。メール受信データ保存用テーブルの更新処理に失敗しました。', true);
		}

		$this->redirect(array('action' => 'index', $mailContentId));
	}

/**
 * 一括削除
 * 
 * @param array $ids
 * @return boolean
 * @access protected
 */
	protected function _batch_del($ids) {
		if ($ids) {
			foreach ($ids as $id) {

				// メッセージ用にデータを取得
				$mailField = $this->MailField->read(null, $id);
				$mailContentName = $this->MailContent->field('name', array('MailContent.id' => $mailField['MailField']['mail_content_id']));
				/* 削除処理 */
				if ($this->Message->delMessageField($mailContentName, $mailField['MailField']['field_name'])) {
					if ($this->MailField->delete($id)) {
						$this->MailField->saveDbLog('メールフィールド「' . $mailField['MailField']['name'] . '」 を削除しました。');
					}
				}
			}
		}

		return true;
	}

/**
 * フォームの初期値を取得する
 *
 * @return string
 * @access protected
 */
	protected function _getDefaultValue() {
		$data['MailField']['type'] = 'text';
		$data['MailField']['use_field'] = 1;
		$data['MailField']['no_send'] = 0;
		return $data;
	}

/**
 * フィールドデータをコピーする
 *
 * @param int $mailContentId
 * @param int $Id
 * @return void
 * @access protected
 * @deprecated admin_ajax_copy に移行
 */
	public function admin_copy($mailContentId, $id) {
		/* 除外処理 */
		if (!$id) {
			$this->setMessage('無効なIDです。', true);
			$this->redirect(array('action' => 'index'));
		}

		/* コピー対象フィールドデータを読み込む */
		$mailField = $this->MailField->read(null, $id);
		if (!$mailField) {
			$this->setMessage('無効なIDです。', true);
			$this->redirect(array('action' => 'index'));
		}

		// 不要な値をリセットする
		unset($mailField['MailField']['id']);
		unset($mailField['MailField']['modified']);
		unset($mailField['MailField']['created']);

		// メッセージ用
		$oldName = $mailField['MailField']['name'];

		// 項目名とフィールド名は識別用に__n形式のナンバーを付加する
		$mailField['MailField']['field_name'] = $this->__getNewValueOnCopy('field_name', $mailField['MailField']['field_name']);
		$mailField['MailField']['name'] = $this->__getNewValueOnCopy('name', $mailField['MailField']['name']);
		$mailField['MailField']['no'] = $this->MailField->getMax('no', array('MailField.mail_content_id' => $mailContentId)) + 1;
		$mailField['MailField']['sort'] = $this->MailField->getMax('sort') + 1;

		// データを保存
		$this->MailField->create($mailField);
		if ($this->MailField->save()) {
			$this->setMessage('メールフィールド「' . $oldName . '」 をコピーしました。', false, true);
			$this->Message->construction($mailContentId);
		} else {
			$this->setMessage('コピー中にエラーが発生しました。', true);
		}

		$this->redirect(array('action' => 'index', $mailContentId));
	}

/**
 * フィールドデータをコピーする
 *
 * @param int $mailContentId
 * @param int $Id
 * @return void
 * @access protected
 */
	public function admin_ajax_copy($mailContentId, $id) {
		/* 除外処理 */
		if (!$id || !$mailContentId) {
			$this->ajaxError(500, '無効な処理です。');
		}

		$result = $this->MailField->copy($id);
		if ($result) {
			$this->Message->construction($mailContentId);
			$this->set('data', $result);
		} else {
			$this->ajaxError(500, 'データベースに問題があります。メール受信データ保存用テーブルの更新処理に失敗しました。');
		}
	}

/**
 * メッセージCSVファイルをダウンロードする
 * 
 * @param int $mailContentId
 * @return void
 * @access public
 */
	public function admin_download_csv($mailContentId) {
		if (!$mailContentId || !$this->mailContent) {
			$this->setMessage('無効な処理です。', true);
			$this->redirect(array('controller' => 'mail_contents', 'action' => 'index'));
		}

		$this->Message->alias = Inflector::camelize($this->mailContent['MailContent']['name'] . '_message');
		$this->Message->tablePrefix .= $this->mailContent['MailContent']['name'] . '_';
		$this->Message->schema(true);
		$this->Message->cacheSources = false;
		$messages = $this->Message->convertMessageToCsv($mailContentId, $this->Message->find('all'));
		$this->set('messages', $messages);
		$this->set('contentName', $this->mailContent['MailContent']['name']);
	}

/**
 * 並び替えを更新する [AJAX]
 *
 * @param int $mailContentId
 * @return boolean
 * @access	public
 */
	public function admin_ajax_update_sort($mailContentId) {
		if (!$mailContentId) {
			$this->ajaxError(500, '無効な処理です。');
		}

		if ($this->request->data) {
			$conditions = $this->_createAdminIndexConditions($mailContentId);
			if ($this->MailField->changeSort($this->request->data['Sort']['id'], $this->request->data['Sort']['offset'], $conditions)) {
				exit(true);
			} else {
				$this->ajaxError(500, $this->MailField->validationErrors);
			}
		} else {
			$this->ajaxError(500, '無効な処理です。');
		}
		exit();
	}

/**
 * 管理画面ページ一覧の検索条件を取得する
 *
 * @param array $mailContentId
 * @return string
 * @access protected
 */
	protected function _createAdminIndexConditions($mailContentId) {
		$conditions = array('MailField.mail_content_id' => $mailContentId);
		return $conditions;
	}

/**
 * [ADMIN] 無効状態にする（AJAX）
 * 
 * @param string $blogContentId
 * @param string $blogPostId beforeFilterで利用
 * @param string $blogCommentId
 * @return void
 * @access public
 */
	public function admin_ajax_unpublish($mailContentId, $id) {
		if (!$id) {
			$this->ajaxError(500, '無効な処理です。');
		}
		if ($this->_changeStatus($id, false)) {
			exit(true);
		} else {
			$this->ajaxError(500, $this->MailField->validationErrors);
		}
		exit();
	}

/**
 * [ADMIN] 有効状態にする（AJAX）
 * 
 * @param string $blogContentId
 * @param string $blogPostId beforeFilterで利用
 * @param string $blogCommentId
 * @return void
 * @access public
 */
	public function admin_ajax_publish($mailContentId, $id) {
		if (!$id) {
			$this->ajaxError(500, '無効な処理です。');
		}
		if ($this->_changeStatus($id, true)) {
			exit(true);
		} else {
			$this->ajaxError(500, $this->MailField->validationErrors);
		}
		exit();
	}

/**
 * 一括公開
 * 
 * @param array $ids
 * @return boolean
 * @access protected 
 */
	protected function _batch_publish($ids) {
		if ($ids) {
			foreach ($ids as $id) {
				$this->_changeStatus($id, true);
			}
		}
		return true;
	}

/**
 * 一括非公開
 * 
 * @param array $ids
 * @return boolean
 * @access protected 
 */
	protected function _batch_unpublish($ids) {
		if ($ids) {
			foreach ($ids as $id) {
				$this->_changeStatus($id, false);
			}
		}
		return true;
	}

/**
 * ステータスを変更する
 * 
 * @param int $id
 * @param boolean $status
 * @return boolean 
 */
	protected function _changeStatus($id, $status) {
		$statusTexts = array(0 => '無効', 1 => '有効');
		$data = $this->MailField->find('first', array('conditions' => array('MailField.id' => $id), 'recursive' => -1));
		$data['MailField']['use_field'] = $status;
		$this->MailField->set($data);

		if ($this->MailField->save()) {
			$statusText = $statusTexts[$status];
			$this->MailField->saveDbLog('メールフィールド「' . $data['MailField']['name'] . '」 を' . $statusText . '化しました。');
			return true;
		} else {
			return false;
		}
	}

}
