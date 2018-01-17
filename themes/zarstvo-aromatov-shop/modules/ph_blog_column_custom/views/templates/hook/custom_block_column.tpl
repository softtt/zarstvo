{if isset($custom_block_column_posts) && count($custom_block_column_posts)}
<div id="blog_for_prestashop_column" class="block">
	<p class="recent_post_title text-center">{*$title*}ПОСЛЕДНИЕ ЗАПИСИ</p>
	
	<div class="block_content {if $layout == 'list'}recent-posts-block{elseif $layout == 'simple_list'}list-block{else}{/if}">
		<ul>
			{foreach from=$custom_block_column_posts item=post}
			<li class="clearfix">
					{if isset($post.banner) && Configuration::get('PH_BLOG_DISPLAY_THUMBNAIL')}
					<a class="recent_post_img" href="{$post.url|escape:'html':'UTF-8'}" title="{$post.title|escape:'html':'UTF-8'}">
						<img src="{$post.banner_thumb|escape:'html':'UTF-8'}" alt="{$post.title|escape:'html':'UTF-8'}" class="img-responsive"  />
					</a>
					{/if}
					<div class="recent-post-content overflow-visible">
						<h5>
							<a class="recent_post_name" href="{$post.url|escape:'html':'UTF-8'}" title="{l s='Читать' mod='ph_blog_column_custom'} {$post.title|escape:'html':'UTF-8'}">
								{$post.title|escape:'html':'UTF-8'}
							</a>
						</h5>
						<span class="recent_post_date">
							<i class="icon-blog icon-blog-calendar"></i> <time itemprop="datePublished" datetime="{$post.date_add|date_format:'%d.%m.%Y'}">{$post.date_add|date_format:'%d.%m.%Y'}</time>
						</span>
					</div>

			</li>
			{/foreach}
		</ul>
	</div>
</div>	
{/if}