<!-- BaserPageTagBegin -->
<?php $this->BcBaser->setTitle('') ?>
<?php $this->BcBaser->setDescription('') ?>
<?php $this->BcBaser->setPageEditLink(1) ?>
<!-- BaserPageTagEnd -->

<div class="clearfix" id="news">
<div class="news" style="margin-right:28px;">
<h2 id="newsHead01">ニュース

<div class="body"><?php $this->BcBaser->blogPosts('news', 5) ?></div></h2>
</div>

<div class="news">
<h2 id="newsHead02">イベント情報

<div class="body"><?php $this->BcBaser->blogPosts('event', 5) ?></div></h2>
</div>
</div>
