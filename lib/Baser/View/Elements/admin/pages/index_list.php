<?php
/**
 * [ADMIN] Ajaxページ一覧
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2015, baserCMS Users Community <http://sites.google.com/site/baserusers/>
 *
 * @copyright		Copyright 2008 - 2015, baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Baser.View
 * @since			baserCMS v 2.0.0
 * @license			http://basercms.net/license/index.html
 */
?>


<!-- pagination -->
<?php $this->BcBaser->element('pagination') ?>

<!-- ListTable -->
<table cellpadding="0" cellspacing="0" class="list-table sort-table" id="ListTable">
	<thead>
		<tr>
			<th class="list-tool">

	<div>
<?php if ($newCatAddable): ?>
		<?php $this->BcBaser->link($this->BcBaser->getImg('admin/btn_add.png', array('width' => 69, 'height' => 18, 'alt' => '新規追加', 'class' => 'btn')), array('action' => 'add')) ?>
<?php endif ?>
<?php if (!$sortmode): ?>
		<?php $this->BcBaser->link($this->BcBaser->getImg('admin/btn_sort.png', array('width' => 65, 'height' => 14, 'alt' => '並び替え', 'class' => 'btn')), array('sortmode' => 1)) ?>
<?php else: ?>
		<?php $this->BcBaser->link($this->BcBaser->getImg('admin/btn_normal.png', array('width' => 65, 'height' => 14, 'alt' => 'ノーマル', 'class' => 'btn')), array('sortmode' => 0)) ?>
<?php endif ?>
	</div>
<?php if ($this->BcBaser->isAdminUser()): ?>
		<div>
			<?php echo $this->BcForm->checkbox('ListTool.checkall') ?>
			<?php echo $this->BcForm->input('ListTool.batch', array('type' => 'select', 'options' => array('publish' => '公開', 'unpublish' => '非公開', 'del' => '削除'), 'empty' => '一括処理')) ?>
			<?php echo $this->BcForm->button('適用', array('id' => 'BtnApplyBatch', 'disabled' => 'disabled')) ?>
		</div>
<?php endif ?>
</th>
<?php if (!$sortmode): ?>
	<th><?php echo $this->Paginator->sort('id', array('asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => '昇順', 'title' => '昇順')) . ' NO', 'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => '降順', 'title' => '降順')) . ' NO'), array('escape' => false, 'class' => 'btn-direction')) ?></th>
	<th>
		<?php echo $this->Paginator->sort('page_category_id', array('asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => '昇順', 'title' => '昇順')) . ' カテゴリ', 'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => '降順', 'title' => '降順')) . ' カテゴリ'), array('escape' => false, 'class' => 'btn-direction')) ?><br />
		<?php echo $this->Paginator->sort('title', array('asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => '昇順', 'title' => '昇順')) . ' タイトル', 'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => '降順', 'title' => '降順')) . ' タイトル'), array('escape' => false, 'class' => 'btn-direction')) ?>
		&nbsp;(&nbsp;<?php echo $this->Paginator->sort('name', array('asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => '昇順', 'title' => '昇順')) . ' ページ名', 'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => '降順', 'title' => '降順')) . ' ページ名'), array('escape' => false, 'class' => 'btn-direction')) ?>&nbsp;)
	</th>
	<th><?php echo $this->Paginator->sort('status', array('asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => '昇順', 'title' => '昇順')) . ' 公開状態', 'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => '降順', 'title' => '降順')) . ' 公開状態'), array('escape' => false, 'class' => 'btn-direction')) ?></th>
	<th><?php echo $this->Paginator->sort('author_id', array('asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => '昇順', 'title' => '昇順')) . ' 作成者', 'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => '降順', 'title' => '降順')) . ' 作成者'), array('escape' => false, 'class' => 'btn-direction')) ?></th>
	<th>
		<?php echo $this->Paginator->sort('created', array('asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => '昇順', 'title' => '昇順')) . ' 登録日', 'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => '降順', 'title' => '降順')) . ' 登録日'), array('escape' => false, 'class' => 'btn-direction')) ?><br />
		<?php echo $this->Paginator->sort('modified', array('asc' => $this->BcBaser->getImg('admin/blt_list_down.png', array('alt' => '昇順', 'title' => '昇順')) . ' 更新日', 'desc' => $this->BcBaser->getImg('admin/blt_list_up.png', array('alt' => '降順', 'title' => '降順')) . ' 更新日'), array('escape' => false, 'class' => 'btn-direction')) ?>
	</th>
<?php else: ?>
	<th>NO</th>
	<th>カテゴリー<br />
		タイトル&nbsp;(&nbsp;ページ名)
	</th>
	<th>公開状態</th>
	<th>作成者</th>
	<th>登録日<br />更新日</th>
<?php endif ?>
</tr>
</thead>
<tbody>
	<?php if (!empty($datas)): ?>
		<?php foreach ($datas as $key => $data): ?>
			<?php $this->BcBaser->element('pages/index_row', array('data' => $data, 'count' => ($key + 1))) ?>
		<?php endforeach; ?>
	<?php else: ?>
		<tr>
			<td colspan="6"><p class="no-data">データがありません。</p></td>
		</tr>
	<?php endif; ?>
</tbody>
</table>

<!-- list-num -->
<?php $this->BcBaser->element('list_num') ?>
