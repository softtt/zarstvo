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
<!-- Cross sellings -->
{if isset($orderProducts) && count($orderProducts)}
    <section id="crossselling">
        {if $page_name == 'order-opc'}
            <h3 class="page-subheading">{l s='We recommend' mod='crossselling'}</h3>
        {else}
            <div class="crossselling-heading crossselling">
                <div class="heading">
                    <h1>
                        {if $page_name == 'product'}
                            {l s='Customers who bought this product also bought:' mod='crossselling'}
                        {else}
                            {l s='We recommend' mod='crossselling'}
                        {/if}
                    </h1>
                </div>
                <hr>
            </div>
        {/if}

        {include file="$tpl_dir./product-list.tpl" products=$orderProducts class='crossellings tab-pane' id='blockviewedproducts' slider=1}

    </section>
{/if}
