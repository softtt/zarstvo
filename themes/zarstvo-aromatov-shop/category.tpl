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
{include file="$tpl_dir./errors.tpl"}
{if isset($category)}
    {if $category->id AND $category->active}
        <h1 class="page-heading{if (isset($subcategories) && !$products) || (isset($subcategories) && $products) || !isset($subcategories) && $products} product-listing{/if}">
            <span class="cat-name">{$category->name|escape:'html':'UTF-8'}{if isset($categoryNameComplement)}&nbsp;{$categoryNameComplement|escape:'html':'UTF-8'}{/if}</span>
        </h1>

        <div class="content_sortPagiBar clearfix">
            <div class="sortPagiBar clearfix">
                {include file="./product-sort.tpl"}
                <div class="top-pagination-content clearfix">
                    {include file="$tpl_dir./pagination.tpl"}
                </div>
                {include file="$tpl_dir./category-count.tpl"}
            </div>
        </div>

        {hook h='displayOnCategoryPage'}

        {include file="./product-list.tpl" products=$products}
        <div class="content_sortPagiBar">
            <div class="bottom-pagination-content clearfix">
                {include file="./pagination.tpl" paginationId='bottom'}
            </div>
        </div>
        {*
        {if $category->description }
            <div class="content_scene_cat">
                <!-- Category image -->
                <div class="content_scene_cat_bg">
                    {if $category->description}
                        <div class="cat_desc">
                        {if Tools::strlen($category->description) > 700}
                            <div id="category_description_short" class="rte">{$description_short}</div>
                            <div id="category_description_full" class="unvisible rte">{$category->description}</div>
                            <a href="{$link->getCategoryLink($category->id_category, $category->link_rewrite)|escape:'html':'UTF-8'}" class="lnk_more">{l s='More'}</a>
                        {else}
                            <div class="rte">{$category->description}</div>
                        {/if}
                        </div>
                    {/if}
                 </div>
            </div>
        {/if}
        *}
    {elseif $category->id}
        <p class="alert alert-warning">{l s='This category is currently unavailable.'}</p>
    {/if}
{/if}
