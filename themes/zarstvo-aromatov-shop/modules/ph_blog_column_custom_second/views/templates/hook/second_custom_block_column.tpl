{if isset($second_custom_block_column_posts) && count($second_custom_block_column_posts)}
<div id="blog_for_prestashop_column_second" class="block">
	<p class="recent_post_title text-center">{*$title*}ПОСЛЕДНИЕ КОММЕНТАРИИ</p>
	
	<div class="block_content {if $layout == 'list'}last-comments-block{elseif $layout == 'simple_list'}list-block{else}{/if}">
		<ul class="comments_ul">
			{foreach from=$second_custom_block_column_posts item=post}
			<li class="clearfix comment_container">
				<div class="col-xs-4">
					<img src="{*$post.author_img|escape:'html':'UTF-8'*}/img/avatar.jpg" alt="{$post.author|escape:'html':'UTF-8'}" class="img-responsive comment_author_img"  />
				</div>
				<div class="col-xs-8">
					<div class="comment_author_name">
						{$post.author|escape:'html':'UTF-8'}
					</div>
					<div class="comment_text">
						<a href="{$post.post_link}" title="Перейти к комментарию">{$post.comment|escape: 'html':'UTF-8'|truncate:40:"...":true}</a>
					</div>
				</div>
			</li>
			{/foreach}
		</ul>
	</div>
</div>	
{/if}