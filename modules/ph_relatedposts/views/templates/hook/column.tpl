<section id="simpleblog-relatedposts-column" class="block">
	<p class="title_block">
		{l s='Related posts' mod='ph_relatedposts'}
	</p>
	<div class="block_content list-block">
		<ul>
			{foreach $related_posts_column as $post}
			<li>
				<a href="{$post.url|escape:'html':'UTF-8'}" title="{$post.title|escape:'html':'UTF-8'}">
					{$post.title|escape:'html':'UTF-8'}
				</a>
			</li>
			{/foreach}
		</ul>
	</div>
</section>