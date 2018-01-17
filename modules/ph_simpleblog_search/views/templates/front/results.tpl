<div class="ph_simpleblog simpleblog-search">

    <div class="search_query">
        <h1 class="page-subheading">
            Поиск по фразе&nbsp;
            <span class="lighter">
            "{$blog_search_query|escape: 'html':'UTF-8'}"
        </span>
                <span class="heading-counter">
                    {if $total_posts_found % 10 == 1 }
                        найден {$total_posts_found|intval}
                        результат
                    {else}
                        найдено {$total_posts_found|intval}
                        {if $total_posts_found % 10 < 5 }
                            результата

                        {else}
                        результатов
                        {/if}
                    {/if}
                          </span>
        </h1>
    </div>

    {if isset($posts) && count($posts)}
        <div class="row simpleblog-posts" itemscope="itemscope" itemtype="http://schema.org/Blog">
            {foreach from=$posts item=post}

                <div class="simpleblog-post-item simpleblog-post-type-{$post.post_type|escape:'html':'UTF-8'}" itemscope="itemscope" itemtype="http://schema.org/BlogPosting" itemprop="blogPost">

                    <div class="post-item">
                        {assign var='post_type' value=$post.post_type}

                        <div class="post-title">
                            <h2 itemprop="headline">
                                <a href="{$post.url|escape:'html':'UTF-8'}" title="{l s='Ссылка на' mod='ph_simpleblog'} {$post.title|escape:'html':'UTF-8'}">
                                    {$post.title|escape:'html':'UTF-8'}
                                </a>
                            </h2>
                        </div><!-- .post-title -->

                        <div class="post-additional-info post-meta-info">
                            {if isset($post.author) && !empty($post.author) && Configuration::get('PH_BLOG_DISPLAY_AUTHOR')}
                                <span class="post-author">
                                    <i class="icon-blog icon-blog-pen"></i> <span itemprop="author" itemscope="itemscope" itemtype="http://schema.org/Person">{$post.author|escape:'html':'UTF-8'}</span>
                                </span>
                            {/if}
                            {if Configuration::get('PH_BLOG_DISPLAY_DATE')}
                                <span class="post-date">
                                    <i class="icon-blog icon-blog-calendar"></i> <time itemprop="datePublished" datetime="{$post.date_add|date_format:'%d.%m.%Y'}">{$post.date_add|date_format:'%d.%m.%Y'}</time>
                                </span>
                            {/if}
                            <span class="post-comments">
                                <i class="icon-blog icon-blog-text"></i> Комментариев: <span class="comment-count">{$post.comments|escape:'html':'UTF-8'}</span>
                            </span>

                        </div><!-- .post-additional-info post-meta-info -->

                        {if isset($post.banner) && Configuration::get('PH_BLOG_DISPLAY_THUMBNAIL')}
                            <div class="post-thumbnail" itemscope itemtype="http://schema.org/ImageObject">
                                <a href="{$post.url|escape:'html':'UTF-8'}" title="{l s='Permalink to' mod='ph_simpleblog'} {$post.title|escape:'html':'UTF-8'}" itemprop="contentUrl">
                                    {if $blogLayout eq 'full'}
                                        <img src="{$post.banner_wide|escape:'html':'UTF-8'}" alt="{$post.title|escape:'html':'UTF-8'}" class="img-responsive" itemprop="thumbnailUrl" />
                                    {else}
                                        <img src="{$post.banner_thumb|escape:'html':'UTF-8'}" alt="{$post.title|escape:'html':'UTF-8'}" class="img-responsive" itemprop="thumbnailUrl"/>
                                    {/if}
                                </a>
                            </div><!-- .post-thumbnail -->
                        {/if}

                        {if Configuration::get('PH_BLOG_DISPLAY_DESCRIPTION')}
                            <div class="post-content" itemprop="text">
                                {$post.short_content|strip_tags:'UTF-8'}...

                                {if Configuration::get('PH_BLOG_DISPLAY_MORE')}
                                    <br />
                                    <div class="post-read-more">
                                        <br />
                                        <a href="{$post.url|escape:'html':'UTF-8'}" title="{l s='Читать далее' mod='ph_simpleblog'}">
                                            {l s='Читать далее' mod='ph_simpleblog'}
                                        </a>
                                    </div><!-- .post-read-more -->
                                {/if}
                                <br />
                                <div class="share_buttons_for_blog_list">
                                    <div class="addthis_sharing_toolbox"></div>
                                </div>
                            </div><!-- .post-content -->
                        {/if}

                    </div><!-- .post-item -->
                </div><!-- .simpleblog-post-item -->

                <div class="col-xs-12">
                    <div class="border-bottom-margin">&nbsp;
                    </div>
                </div>

            {/foreach}
        </div><!-- .row -->
            {if $pagination!= false}
                              <!-- Pagination -->
                <div id="pagination" class="pagination simpleblog-pagination">
                    <ul class="pagination">
                        {if $pagination['current'] != 1}
                            {assign var='p_previous' value=$pagination['current']-1}
                            <li id="pagination_previous" class="pagination_previous"><a href="{$worker2_link|escape:'html':'UTF-8'}?search_blog_query={$blog_search_query|escape:'html':'UTF-8'}&search_blog_page={$p_previous|escape:'html':'UTF-8'}" rel="prev">{l s='Назад' mod='ph_simpleblog'}</a></li>
                        {else}
                            <li id="pagination_previous" class="disabled pagination_previous"><span>{l s='Назад' mod='ph_simpleblog'}</span></li>
                        {/if}
                        {if $pagination['current']>1}
                            <li><a href="{$worker2_link|escape:'html':'UTF-8'}?search_blog_query={$blog_search_query|escape:'html':'UTF-8'}&search_blog_page=1">1</a></li>
                        {/if}

                        {if $pagination['before_left_sibling']==true}
                            <li class="truncate"><span>...</span></li>
                        {/if}

                        {if $pagination['left_left_sibling']>0}
                            <li><a href="{$worker2_link|escape:'html':'UTF-8'}?search_blog_query={$blog_search_query|escape:'html':'UTF-8'}&search_blog_page={$pagination['left_left_sibling']|intval}">{$pagination['left_left_sibling']|intval}</a></li>
                        {/if}

                        {if $pagination['left_sibling']>0}
                            <li><a href="{$worker2_link|escape:'html':'UTF-8'}?search_blog_query={$blog_search_query|escape:'html':'UTF-8'}&search_blog_page={$pagination['left_sibling']|intval}">{$pagination['left_sibling']|intval}</a></li>
                        {/if}

                        <li class="current"><span>{$pagination['current']|escape:'htmlall':'UTF-8'}</span></li>

                        {if $pagination['right_sibling']>0}
                            <li><a href="{$worker2_link|escape:'html':'UTF-8'}?search_blog_query={$blog_search_query|escape:'html':'UTF-8'}&search_blog_page={$pagination['right_sibling']|intval}">{$pagination['right_sibling']|intval}</a></li>
                        {/if}

                        {if $pagination['right_right_sibling']>0}
                            <li><a href="{$worker2_link|escape:'html':'UTF-8'}?search_blog_query={$blog_search_query|escape:'html':'UTF-8'}&search_blog_page={$pagination['right_right_sibling']|intval}">{$pagination['right_right_sibling']|intval}</a></li>
                        {/if}

                        {if $pagination['after_right_sibling']==true}
                            <li class="truncate"><span>...</span></li>
                        {/if}

                        {if $pagination['last'] > 1 AND $pagination['current'] != $pagination['last']}
                            <li><a href="{$worker2_link|escape:'html':'UTF-8'}?search_blog_query={$blog_search_query|escape:'html':'UTF-8'}&search_blog_page={$pagination['last']|intval}">{$pagination['last']|intval}</a></li>
                            {assign var='p_next' value=$pagination['current']+1}
                            <li id="pagination_next" class="pagination_next"><a href="{$worker2_link|escape:'html':'UTF-8'}?search_blog_query={$blog_search_query|escape:'html':'UTF-8'}&search_blog_page={$p_next|escape:'html':'UTF-8'}" rel="next">{l s='Вперед' mod='ph_simpleblog'}</a></li>
                        {else}
                            <li id="pagination_next" class="disabled pagination_next"><span>{l s='Вперед' mod='ph_simpleblog'}</span></li>
                        {/if}
                    </ul>
                </div>
                <!-- /Pagination -->
            {/if}
    {else}
        <p class="warning alert alert-warning">{l s='К сожалению, по вашему запросу ничего не найдено. Попробуйте еще раз.' mod='ph_simpleblog'}</p>
    {/if}
</div><!-- .ph_simpleblog -->
<script>
    var currentBlog = 'search';
    $(window).load(function() {
        $('body').addClass('simpleblog simpleblog-'+currentBlog);
    });
</script>