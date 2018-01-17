{if isset($custom_block_column_posts) && count($custom_block_column_posts)}
<div id="blog_for_prestashop_column" class="block">
	<p class="title_block">{$title}</p>
	
	<div class="block_content {if $layout == 'list'}products-block{elseif $layout == 'simple_list'}list-block{else}{/if}">
		<ul>
			{foreach from=$custom_block_column_posts item=post}
			<li class="clearfix">
				{if $layout == 'list'}
					{if isset($post.banner) && Configuration::get('PH_BLOG_DISPLAY_THUMBNAIL')}
					<a class="products-block-image" href="{$post.url|escape:'html':'UTF-8'}" title="{$post.title|escape:'html':'UTF-8'}">
						<img src="{$post.banner_thumb|escape:'html':'UTF-8'}" alt="{$post.title|escape:'html':'UTF-8'}" class="img-responsive" style="max-width: {$img_max_width}px!important;" />
					</a>
					{/if}
					<div class="product-content">
						<h5>
							<a class="product-name" href="{$post.url|escape:'html':'UTF-8'}" title="{l s='Read' mod='ph_blog_column_custom'} {$post.title|escape:'html':'UTF-8'}">
								{$post.title|escape:'html':'UTF-8'}
							</a>
						</h5>
						{if Configuration::get('PH_BLOG_DISPLAY_DESCRIPTION')}
						<p class="product-description">
							{$post.short_content|truncate:120:'...'|strip_tags:'UTF-8'}
						</p>
						{/if}
					</div>
				{elseif $layout == 'simple_list'}
				<a href="{$post.url|escape:'html':'UTF-8'}" title="{l s='Read' mod='ph_blog_column_custom'} {$post.title|escape:'html':'UTF-8'}">
					{$post.title|escape:'html':'UTF-8'}
				</a>
				{else}
					
				{/if}
			</li>
			{/foreach}
		</ul>
		{if $visit_blog eq true}
		<p class="text-center">
			<a class="btn btn-default" href="{$link->getModuleLink('ph_simpleblog', 'list')}" title="{l s='Visit our blog' mod='ph_blog_column_custom'}">{l s='Visit our blog' mod='ph_blog_column_custom'}</a>
		</p>
		{/if}
	</div>
</div>	
{/if}