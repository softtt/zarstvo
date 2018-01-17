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
{assign var='post_type' value=$post->post_type}

{include file="$tpl_dir./errors.tpl"}

{if isset($smarty.get.confirmation)}
	<div class="success alert alert-success">
	{if $smarty.get.confirmation == 1}
		{l s='Ваш комментарий добавлен.' mod='ph_simpleblog'}
	{else}
		{l s='Ваш комментарий добавлен, но будет виден только после проверки модератором.' mod='ph_simpleblog'}
	{/if}
	</div><!-- .success alert alert-success -->
{/if}

<div itemscope="itemscope" itemtype="http://schema.org/Blog" itemprop="mainContentOfPage">
	<div class="ph_simpleblog simpleblog-single {if !empty($post->featured_image)}with-cover{else}without-cover{/if} simpleblog-single-{$post->id_simpleblog_post|intval}" itemscope="itemscope" itemtype="http://schema.org/BlogPosting" itemprop="blogPost">
		<h1 itemprop="headline">
			{$post->title|escape:'html':'UTF-8'}
		</h1>

		<div class="post-additional-info post-meta-info">
			{if isset($post->author) && !empty($post->author) && Configuration::get('PH_BLOG_DISPLAY_AUTHOR')}
				<span class="post-author">
										<i class="icon-blog icon-blog-pen"></i> <span itemprop="author" itemscope="itemscope" itemtype="http://schema.org/Person">{$post->author|escape:'html':'UTF-8'}</span>
									</span>
			{/if}
			{if Configuration::get('PH_BLOG_DISPLAY_DATE')}
				<span class="post-date">
										<i class="icon-blog icon-blog-calendar"></i> <time itemprop="datePublished" datetime="{$post->date_add|date_format:'%d.%m.%Y'}"> {$post->date_add|date_format:'%d.%m.%Y'}</time>
									</span>
			{/if}
			<span class="post-comments">
									<i class="icon-blog icon-blog-text"></i>  Комментариев: <span class="comment-count">{$post->comments|escape:'html':'UTF-8'}</span>
								</span>

		</div><!-- .post-additional-info post-meta-info -->
		{if isset($post->featured_image) && $post->featured_image != ''}
		<div class="post-featured-image" itemscope itemtype="http://schema.org/ImageObject">

				<a href="{$post->featured_image|escape:'html':'UTF-8'}" title="{$post->title|escape:'html':'UTF-8'}" class="fancybox" itemprop="contentUrl">
					<img src="{$post->featured_image|escape:'html':'UTF-8'}" alt="{$post->title|escape:'html':'UTF-8'}" class="img-responsive" itemprop="thumbnailUrl" />
				</a>

		</div><!-- .post-featured-image -->
		{/if}
		<div class="post-content rte" itemprop="text">
			{$post->content}
		</div><!-- .post-content -->

		{if $post_type == 'gallery' && sizeof($post->gallery)}
		<div class="post-gallery">
			{foreach $post->gallery as $image}
			<a rel="post-gallery-{$post->id_simpleblog_post|intval}" class="fancybox" href="{$gallery_dir|escape:'html':'UTF-8'}{$image.image|escape:'html':'UTF-8'}.jpg" title="{l s='View full' mod='ph_simpleblog'}"><img src="{$gallery_dir|escape:'html':'UTF-8'}{$image.image|escape:'html':'UTF-8'}.jpg" class="img-responsive" /></a>
			{/foreach}
		</div><!-- .post-gallery -->
		{elseif $post_type == 'video'}
		<div class="post-video" itemprop="video">
			{$post->video_code}
		</div><!-- .post-video -->
		{/if}
		<br />
		<div class="share_buttons_for_blog_list">
			<div class="addthis_sharing_toolbox"></div>
		</div>
		<br />

		{if Configuration::get('PH_BLOG_DISPLAY_RELATED') && $related_products}
			{include file="./related-products.tpl"}
		{/if}

		<div id="displayPrestaHomeBlogAfterPostContent">
			{hook h='displayPrestaHomeBlogAfterPostContent'}
		</div><!-- #displayPrestaHomeBlogAfterPostContent -->
		
		{* Native comments *}
		{if $allow_comments eq true && Configuration::get('PH_BLOG_NATIVE_COMMENTS')}
			{include file="./comments/layout.tpl"}
		{/if}

		{* Facebook comments *}
		{if $allow_comments eq true && !Configuration::get('PH_BLOG_NATIVE_COMMENTS')}
			{include file="./comments/facebook.tpl"}
		{/if}
				
	</div><!-- .ph_simpleblog -->
</div><!-- schema -->

{if Configuration::get('PH_BLOG_FB_INIT')}
<script>
var lang_iso = '{$lang_iso}_{$lang_iso|@strtoupper}';
{literal}(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/"+lang_iso+"/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));
{/literal}
</script>
{/if}
<script>
$(function() {
	$('body').addClass('simpleblog simpleblog-single');
});
</script>