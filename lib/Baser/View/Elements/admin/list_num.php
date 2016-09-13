<?php
/**
 * [ADMIN] リスト設定リンク
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
$currentNum = '';
if (empty($nums)) {
	$nums = array('10', '20', '50', '100');
}
if (!is_array($nums)) {
	$nums = array($nums);
}
if (!empty($this->passedArgs['num'])) {
	$currentNum = $this->passedArgs['num'];
}
$links = array();
foreach ($nums as $num) {
	if ($currentNum != $num) {
		$links[] = '<span>' . $this->BcBaser->getLink($num, am($this->passedArgs, array('num' => $num, 'page' => null))) . '</span>';
	} else {
		$links[] = '<span class="current">' . $num . '</span>';
	}
}
if ($links) {
	$link = implode('｜', $links);
}
?>
<?php if ($link): ?>
	<div class="list-num">
		<strong>表示件数</strong><p><?php echo $link ?></p>
	</div>
<?php endif ?>
	