<div class="post-block simpleblog-related-products">
	<h3 class="block-title">{l s='Related products' mod='ph_simpleblog'}</h3>

	<div class="simpleblog-related-products-wrapper">
		{if Configuration::get('PH_BLOG_RELATED_PRODUCTS_USE_DEFAULT_LIST')}
		{include file="$tpl_dir./product-list.tpl" products=$related_products is_blog=true}
		{else}
		{include file="./product-list.tpl" products=$related_products is_blog=true}
		{/if}
	</div><!-- .simpleblog-related-products-wrapper -->
</div><!-- .simpleblog-related-products -->