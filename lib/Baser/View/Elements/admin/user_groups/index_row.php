<?php
/**
 * [ADMIN] ユーザーグループ一覧　行
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2014, baserCMS Users Community <http://sites.google.com/site/baserdatas/>
 *
 * @copyright		Copyright 2008 - 2014, baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Baser.View
 * @since			baserCMS v 0.1.0
 * @license			http://basercms.net/license/index.html
 */
?>


<tr>
	<td class="row-tools">
		<?php if ($data['UserGroup']['name'] != 'admins'): ?>
			<?php $this->BcBaser->link($this->BcBaser->getImg('admin/icn_tool_permission.png', array('width' => 24, 'height' => 24, 'alt' => '制限', 'class' => 'btn')), array('controller' => 'permissions', 'action' => 'index', $data['UserGroup']['id']), array('title' => '制限')) ?>
		<?php endif ?>
		<?php $this->BcBaser->link($this->BcBaser->getImg('admin/icn_tool_edit.png', array('width' => 24, 'height' => 24, 'alt' => '編集', 'class' => 'btn')), array('action' => 'edit', $data['UserGroup']['id']), array('title' => '編集')) ?>
		<?php $this->BcBaser->link($this->BcBaser->getImg('admin/icn_tool_copy.png', array('width' => 24, 'height' => 24, 'alt' => 'コピー', 'class' => 'btn')), array('action' => 'ajax_copy', $data['UserGroup']['id']), array('title' => 'コピー', 'class' => 'btn-copy')) ?>
		<?php if ($data['UserGroup']['name'] != 'admins'): ?>
			<?php $this->BcBaser->link($this->BcBaser->getImg('admin/icn_tool_delete.png', array('width' => 24, 'height' => 24, 'alt' => '削除', 'class' => 'btn')), array('action' => 'ajax_delete', $data['UserGroup']['id']), array('title' => '削除', 'class' => 'btn-delete')) ?>
		<?php endif ?>
	</td>
	<td><?php echo $data['UserGroup']['id'] ?></td>
	<td><?php $this->BcBaser->link($data['UserGroup']['name'], array('action' => 'edit', $data['UserGroup']['id'])) ?>
		<?php if ($data['User']): ?><br />
			<?php foreach ($data['User'] as $user): ?>
				<span class="tag"><?php $this->BcBaser->link($this->BcBaser->getUserName($user), array('controller' => 'users', 'action' => 'edit', $user['id'])) ?></span>
			<?php endforeach ?>
		<?php endif ?>
	</td>
	<td><?php echo $data['UserGroup']['title'] ?></td>
	<td><?php echo $this->BcTime->format('Y-m-d', $data['UserGroup']['created']) ?><br />
		<?php echo $this->BcTime->format('Y-m-d', $data['UserGroup']['modified']) ?></td>
</tr>