<div itemtype="#" itemscope="" class="sdsarticleCat clearfix">
    <div id="smartblogpost-{$post.id_post}">
    <div class="articleContent">
      <a itemprop="url" title="{$post.post_title}" class="imageFeaturedLink">
        {assign var="activeimgincat" value='0'}
        {$activeimgincat = $smartshownoimg}
        {if ($post.post_img != "no" && $activeimgincat == 0) || $activeimgincat == 1}
          <img itemprop="image" alt="{$post.post_title}" src="{$modules_dir}/smartblog/images/{$post.post_img}.jpg" class="imageFeatured">
        {/if}
      </a>
      <div class="">
          {assign var="options" value=null}
          {$options.id_post = $post.id_post}
          {$options.slug = $post.link_rewrite}
          <p class='sdstitle_block'>
            <a title="{$post.post_title}" href='{smartblog::GetSmartBlogLink('smartblog_post',$options)}'>
              {$post.post_title}
            </a>
            <span class="post_created_at">{$post.created|date_format:'%m.%d.%Y'}</span>
          </p>
          {assign var="options" value=null}
          {$options.id_post = $post.id_post}
          {$options.slug = $post.link_rewrite}
          {assign var="catlink" value=null}
          {$catlink.id_category = $post.id_category}
          {$catlink.slug = $post.cat_link_rewrite}

      </div>
      <span class="short_description" itemprop="description">
        <div>
          {$post.short_description}
        </div>
      </span>
      <div class="sdsreadMore">
        {assign var="options" value=null}
        {$options.id_post = $post.id_post}
        {$options.slug = $post.link_rewrite}
        <span class="more"><a title="{$post.post_title}" href="{smartblog::GetSmartBlogLink('smartblog_post',$options)}" class="r_more">{l s='Read more' mod='smartblog'} </a></span>
      </div>
    </div>
   </div>
</div>
