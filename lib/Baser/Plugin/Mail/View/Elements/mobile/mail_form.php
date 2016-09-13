<?php
/**
 * [MOBILE] メールフォーム本体
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2015, baserCMS Users Community <http://sites.google.com/site/baserusers/>
 *
 * @copyright		Copyright 2008 - 2015, baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Mail.View
 * @since			baserCMS v 0.1.0
 * @license			http://basercms.net/license/index.html
 */
?>
<?php /* フォーム開始タグ */ ?>
<?php if (!$freezed): ?>
	<?php echo $this->Mailform->create(null, array('url' => array('plugin' => null, 'controller' => $mailContent['MailContent']['name'], 'action' => 'confirm'))) ?>
<?php else: ?>
	<?php echo $this->Mailform->create(null, array('url' => array('plugin' => null, 'controller' => $mailContent['MailContent']['name'], 'action' => 'submit'))) ?>
<?php endif; ?>
<?php /* フォーム本体 */ ?>
<?php echo $this->BcBaser->element('mail_input', array('blockStart' => 1)) ?>

<br />
<br />
<?php /* 送信ボタン */ ?>
<?php if ($freezed): ?>
	<center>
		<?php echo $this->Mailform->hidden('Message.mode', array('value' => 'Submit')) ?>
		<?php echo $this->Mailform->submit('　送信する　', array('class' => 'button')) ?>
	</center>
	<?php else: ?>
	<center>
		<?php echo $this->Mailform->hidden('Message.mode', array('value' => 'Confirm')) ?>
		<?php echo $this->Mailform->submit('　入力内容を確認する　', array("class" => "button")) ?>
	</center>
<?php endif; ?>

<?php echo $this->Mailform->end() ?>