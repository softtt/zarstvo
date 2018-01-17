{capture name=path}<a href="{smartblog::GetSmartBlogLink('smartblog')}">{l s='All Blog News' mod='smartblog'}</a>
    {if $title_category != ''}
    <span class="navigation-pipe">{$navigationPipe}</span>{$title_category}{/if}{/capture}
    {if $postcategory == ''}
        {if $title_category != ''}
             <p class="error">{l s='No Post in Category' mod='smartblog'}</p>
        {else}
             <p class="error">{l s='No Post in Blog' mod='smartblog'}</p>
        {/if}
    {else}
	{if $smartdisablecatimg == '1'}
                  {assign var="activeimgincat" value='0'}
                    {$activeimgincat = $smartshownoimg}
        {if $title_category != ''}
           {foreach from=$categoryinfo item=category}
            <div id="sdsblogCategory">
               {if ($cat_image != "no" && $activeimgincat == 0) || $activeimgincat == 1}
                   <img alt="{$category.post_title}" src="{$modules_dir}/smartblog/images/category/{$cat_image}-home-default.jpg" class="imageFeatured">
               {/if}
                {$category.description}
            </div>
             {/foreach}
        {/if}
    {/if}
    <div id="smartblogcat" class="block">
{foreach from=$postcategory item=post}
    {include file="./category_loop.tpl" postcategory=$postcategory}
{/foreach}
    </div>
    {if !empty($pagenums)}
    <div class="row">
    <div class="post-page col-md-12 bottom-pagination-content">
        <ul class="pagination">
            {for $k=0 to $pagenums}
                {if $title_category != ''}
                    {assign var="options" value=null}
                    {$options.page = $k+1}
                    {$options.id_category = $id_category}
                    {$options.slug = $cat_link_rewrite}
                {else}
                    {assign var="options" value=null}
                    {$options.page = $k+1}
                {/if}

                {if $k == 0}
                    {if $c > 1}
                    {assign var="options_prev" value=null}
                        {if $title_category != ''}
                            {$options_prev.page = $c-1}
                            {$options_prev.id_category = $id_category}
                            {$options_prev.slug = $cat_link_rewrite}
                            </li><li id="pagination_previous" class="smartblog_category_pagination">
                                <a href="{smartblog::GetSmartBlogLink('smartblog_list_pagination',$options_prev)}" rel="prev">
                                    {l s='Previous'}
                                </a>
                            </li>
                        {else}
                            {$options_prev.page = $c-1}
                            <li id="pagination_previous" class="pagination_previous">
                                <a href="{smartblog::GetSmartBlogLink('smartblog_list_pagination',$options_prev)}" rel="prev">
                                    {l s='Previous'}
                                </a>
                            </li>
                        {/if}
                    {else}
                        <li id="pagination_previous" class="disabled pagination_previous">
                            <span>
                                {l s='Previous'}
                            </span>
                        </li>
                    {/if}
                {/if}

                {if ($k+1) == $c}
                    <li class="active current">
                        <span class="page-active">
                            <span>{$k+1}</span>
                        </span>
                    </li>
                {else}
                        {if $title_category != ''}
                            <li>
                                <a class="page-link" href="{smartblog::GetSmartBlogLink('smartblog_category_pagination',$options)}">
                                    <span>{$k+1}</span>
                                </a>
                            </li>
                        {else}
                            <li>
                                <a class="page-link" href="{smartblog::GetSmartBlogLink('smartblog_list_pagination',$options)}">
                                    <span>
                                        {$k+1}
                                    </span>
                                </a>
                            </li>
                        {/if}
                {/if}

                {if $k == $pagenums}
                    {if ($c < $pagenums+1)}
                        {assign var="options_next" value=null}
                        {if $title_category != ''}
                            {$options_next.page = $c+1}
                            {$options_next.id_category = $id_category}
                            {$options_next.slug = $cat_link_rewrite}
                            </li><li id="pagination_next" class="pagination_next">
                                <a href="{smartblog::GetSmartBlogLink('smartblog_category_pagination',$options_next)}" rel="next">
                                    {l s='Next'}
                                </a>
                            </li>
                        {else}
                            {$options_next.page = $c+1}
                            <li id="pagination_next" class="pagination_next">
                                <a href="{smartblog::GetSmartBlogLink('smartblog_list_pagination',$options_next)}" rel="next">
                                    {l s='Next'}
                                </a>
                            </li>
                        {/if}
                    {else}
                        <li id="pagination_next" class="disabled pagination_next">
                            <span>
                                {l s='Next'}
                            </span>
                        </li>
                    {/if}
                {/if}
           {/for}
        </ul>
  </div>
  </div> {/if}
 {/if}
{if isset($smartcustomcss)}
    <style>
        {$smartcustomcss}
    </style>
{/if}
