<?php
/**
 * [ADMIN] ページカテゴリー登録編集フォーム　ヘルプ
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


<p>ページをグルーピングする為のカテゴリ登録を行います。<br />
	ページカテゴリータイトルはTitleタグとして利用されますので、カテゴリを特定するキーワードを登録しましょう。検索エンジン対策として有用です。<br />
	また、各カテゴリは親カテゴリを指定する事ができ、細かく分類分けが可能です。</p>
<div class="example-box">
	<div class="head">（例）カテゴリに属するページのタイトルタグの出力例</div>
	<p>ページカテゴリー「company」のページカテゴリタイトルを「会社案内」とし、そのカテゴリに属するページ「about」のページタイトルを「コンセプト」として登録した場合、<br />タイトルタグの内容は次のようになります。</p>
	<p>出力結果：コンセプト｜会社案内｜サイトタイトル</p>
</div>