{*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<div id="simpleblog-post-comments" class="post-block">
	<h3 class="block-title">{l s='Комментарии' mod='ph_simpleblog'}{* ({$post->comments|escape:'html':'UTF-8'})*}</h3>

	<div class="post-comments-list">
		{if $post->comments}
			{foreach $comments as $comment}
				{if $comment.depth==0}
					<div class="comment-parent">
						<div id ="comment-no-{$comment.id}" class="row post-comment post-comment-{$comment.id|intval}" data-parent-id="{$comment.id|intval}" data-comment-id="{$comment.id|intval}">
							<div class="post-comment-author-img col-xs-2">
								{*{$comment.author_img}*}
								<img src="{*$comment.author_img|escape:'html':'UTF-8'*}/img/avatar.jpg" alt="{$comment.author|escape:'html':'UTF-8'}" class="img-responsive comment_author_img"  />
							</div>
							<div class="col-xs-10">
								<div class="post-comment-meta">
									<div class="left">
										<div class="post-comment-author">{$comment.name|escape:'html':'UTF-8'}</div>
										<div class="post-comment-date"><i class="icon-blog icon-blog-calendar margin-right-10"></i> {$comment.date_add|date_format:'%d.%m.%Y'}</div>
									</div>
									<div class="right">
										<div class="post-comment-reply">
											<button class="button btn btn-default button-medium">
												<span class="button-reply"><span class="button-reply-icon"> </span>Ответить</span>
											</button>
										</div>
									</div>
								</div><!-- .post-comment-meta -->
								<div class="post-comment-content">
									{$comment.comment|escape:'html':'UTF-8'}
								</div><!-- .post-comment-content -->
							</div>
						</div><!-- .post-comment-->
					</div>
				{else}
					<div class="comment-child">
						<div id ="comment-no-{$comment.id}" class="row post-comment post-comment-{$comment.id|intval} child-from-{$comment.id_parent|intval}" data-comment-id="{$comment.id|intval}" data-is-child-from="{$comment.id_parent|intval}">
							<div class="col-xs-2">
								<div class="child-comment-line">&nbsp;</div>
							</div>
							<div class="post-comment-author-img col-xs-2">
								{*{$comment.author_img}*}
								<img src="{*$comment.author_img|escape:'html':'UTF-8'*}/img/avatar.jpg" alt="{$comment.author|escape:'html':'UTF-8'}" class="img-responsive comment_author_img"  />
							</div>
							<div class="col-xs-8">
								<div class="post-comment-meta">
									<div class="left">
										<div class="post-comment-author">{$comment.name|escape:'html':'UTF-8'}</div>
										<div class="post-comment-date"><i class="icon-blog icon-blog-calendar margin-right-10"></i> {$comment.date_add|date_format:'%d.%m.%Y'}</div>
									</div>
									<div class="right">
										<div class="post-comment-reply">
											<button class="button btn btn-default button-medium">

												<span class="button-reply"><span class="button-reply-icon"> </span>Ответить</span>
											</button>
										</div>
									</div>
								</div><!-- .post-comment-meta -->
								<div class="post-comment-content">
									{$comment.comment|escape:'html':'UTF-8'}
								</div><!-- .post-comment-content -->
							</div>
						</div><!-- .post-comment -->
					</div>

				{/if}
			{/foreach}
		{else}
			<div class="warning alert alert-warning">
				{l s='Комментариев нет.' mod='ph_simpleblog'}
			</div><!-- .warning -->
		{/if}
	</div><!-- .post-comments-list -->

	{* Comment form *}
	{include file="./form.tpl"}
</div><!-- #post-comments -->
<script>
	{literal}
	$(window).load(function() {
			parent_length = $('.comment-parent').length;
			child_length = $('.comment-child').length;
			function _renderLines() {
				if (parent_length>0){ // if we got comments
					var pos1 = $($('.comment-parent')[0]).find('.comment_author_img').offset();
					var centerX = pos1.left + $($('.comment-parent')[0]).find('.comment_author_img').width() / 2;
					centerX = centerX + "px";
					var top = pos1.top + "px";
					if (parent_length>1) { // draw a line between parent comments if > 1
						if (!$('.line-between-parents').length) {
							$('#page').prepend('<div class="comment-line-connect line-between-parents">&nbsp;</div>');
						}
						var pos2 = $($('.comment-parent')[parent_length-1]).find('.comment_author_img').offset();
						var height = pos2.top - pos1.top;
						$('.line-between-parents').css('height', height+"px").css('top', top).css('left', centerX);
					}
					if (child_length>0) {
						if($('.comment-child:last-child').length>0) { // we got an answer to parent comment at the end, no parents after it, draw a line to fold
							if (!$('.line-last-no-parent').length) {
								$('#page').prepend('<div class="comment-line-connect line-last-no-parent">&nbsp;</div>');
							}
							var last_author_img = $($('.comment-child:last-child')).find('.comment_author_img');
							var height2 = last_author_img.offset().top + last_author_img.height()/2 - pos1.top;
							$('.line-last-no-parent').css('height', height2+"px").css('top', top).css('left', centerX);
						}
						if (child_length>1) { // draw a line between child of same parent comments if >1
							parents_childs = {}; // key is parent, value is how much it has childs
							$.each($('.comment-child .row'), function(){
								var parent = $(this).attr('data-is-child-from');
								if (parents_childs[parent]>=0) {
									parents_childs[parent] ++;
								}
								else {
									parents_childs[parent] = 1;
								}

							});
							for (var key in parents_childs) {
							if (parents_childs[key] > 1) { // draw a line in children comment
								if (!$('.answers-line-for-'+key).length){
									$('#page').prepend('<div class="comment-line-connect answers-line-for-'+key+'">&nbsp;</div>');
								}
								var start = $($('.comment-child .child-from-'+key)[0]).find('.comment_author_img');
								var end = $($('.comment-child .child-from-'+key)[parents_childs[key]-1]).find('.comment_author_img');
								var centerX3= start.offset().left + start.width()/2;
								var height3 = end.offset().top - start.offset().top;
								$('.answers-line-for-'+key).css('height', height3+"px").css('top', start.offset().top).css('left', centerX3);
							}
							}
						}
					}
				var doit;
				$(window).resize(function() {
					clearTimeout(doit);
					doit = setTimeout(function() {
						_renderLines();
					}, 200);
				});
				}
			}
		_renderLines();
		var target = window.location.hash;
		if (target.length) { //scroll to comment from link and highlight it for a moment
			$('html, body').animate({
				scrollTop: $(target).offset().top-10
			}, 500);
			$(target).addClass('highlight');
			setTimeout(function() {$(target).removeClass('highlight')}, 3000);
		}
		$(document).on('click', '.button-reply', function() {
			$('#id_parent').val($(this).parents().eq(5).attr('data-comment-id'));
			$('html, body').animate({
				scrollTop: $('.form-comment-heading').offset().top-10
			}, 500);
			setTimeout(function() {$('#comment_content').focus();} , 500);
		});
	});

	{/literal}
</script>
