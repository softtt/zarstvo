{*
* 2007-2015 PrestaShop
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
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{if !isset($content_only) || !$content_only}

                        </div><!-- #center_column -->

                        {if isset($right_column_size) && !empty($right_column_size)}
                            <div id="right_column" class="col-xs-12 col-sm-{$right_column_size|intval} column">{$HOOK_RIGHT_COLUMN}</div>
                        {/if}


                    </div><!-- .row -->
                    {if $page_name == 'category' && isset($category)}
                        {if $category->description }
                            <div class="content_scene_cat">
                                <!-- Category image -->
                                <div class="content_scene_cat_bg">
                                    {if $category->description}
                                        <div class="cat_desc">
                                            <div class="rte">{$category->description}</div>
                                        </div>
                                    {/if}
                                 </div>
                            </div>
                        {/if}
                    {/if}
                </div><!-- #columns -->
            </div><!-- .columns-container -->
            {if isset($HOOK_FOOTER)}
                <!-- Footer -->
                <div class="footer-container">
                    <footer id="footer">
                        {$HOOK_FOOTER}
                    </footer>
                </div><!-- #footer -->
            {/if}
        </div><!-- #page -->
{/if}
{include file="$tpl_dir./global.tpl"}
        <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5587c73c6dd55d32" async="async"></script>
        {if $smarty.server.HTTP_HOST == 'ru.zarstvo-shop.com'}
            <!-- BEGIN JIVOSITE CODE {literal} -->
            <script type='text/javascript'>
            (function(){ var widget_id = 'Tvh25OOeeB';
            var s = document.createElement('script'); s.type = 'text/javascript'; s.async = true; s.src = '//code.jivosite.com/script/widget/'+widget_id; var ss = document.getElementsByTagName('script')[0]; ss.parentNode.insertBefore(s, ss);})();</script>
            <!-- {/literal} END JIVOSITE CODE -->
        {/if}
    </body>
</html>
