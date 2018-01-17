<div class="simpleblog-relatedposts-product-column">
	<p class="title_block">
		{l s='Related posts' mod='ph_relatedposts'}
	</p>
	<ul>
		{foreach $related_posts_column_product as $post}
		<li>
			<a href="{$post.url|escape:'html':'UTF-8'}" title="{$post.title|escape:'html':'UTF-8'}">
				{$post.title|escape:'html':'UTF-8'}
			</a>
		</li>
		{/foreach}
	</ul>
</div><!-- .simpleblog-relatedposts-product-column -->