<?php
/**
 * sidebox
 */
?>

<div id="Alfa" >
	<div id="sidebox">
		<div id="GlobalMenus" class="Left-GlobalMenus">
			<?php $this->BcBaser->element('global_menu') ?>
		</div>
	</div>

	<div id="sidebox">
		<?php $this->BcBaser->widgetArea() ?>
	</div>
<!--
	<div id="sidebox">
		<div id="sidebox-bnr">
			<?php $this->BcBaser->img('./sidebox/icons_banner_01.png', array('url' => '/#')); ?>
		</div>
		<div id="sidebox-bnr2">
			<?php $this->BcBaser->img('./sidebox/icons_banner_02.png', array('url' => '/#')); ?>
		</div>
	</div>
-->

	<div id="sidebox">
<!--
		<div id="sidebox-bnr3"><?php $this->BcBaser->img('./sidebox/sidebox_logo.png'); ?></div>
		<div id="sidebox-txt" class="sidebox-text">baserCMS</div>
		<div id="sidebox-bnr4">
			<?php $this->BcBaser->link($this->BcBaser->getImg('./sidebox/sidebox_contact.png', array('title' => 'お問い合わせ（月曜休館日）', 'alt' => 'お問い合わせ')), '/contact'); ?>
		</div>
-->
		<div id="sidebox-txt">
			<p class="sidebox-telfax">TEL:0250-24-5776</p>
			<p class="sidebox-text">受付時間 9:00～22:00</p>
			<p class="sidebox-telfax">FAX:0250-24-5776</p>
			<p class="sidebox-text">受付時間 24時間</p>
		</div>
	</div>

	<!--FB
	<div id="fb-root"></div>
	<script>(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/ja_JP/all.js#xfbml=1";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
	</script>
	<div class="fb-like-box" data-href="http://www.facebook.com/basercms" data-height="300" data-width="214" data-show-faces="true" data-stream="false" data-border-color="#DDDDDD" data-header="false"></div>
	FB_END-->

</div><!--Alfa-->
