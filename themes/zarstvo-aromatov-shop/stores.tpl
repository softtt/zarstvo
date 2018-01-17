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

{capture name=path}{l s='Our stores'}{/capture}

<h1 class="page-heading">
	{l s='Our stores'}
</h1>

<div id="map"></div>

{if $stores|@count}
    <p class="store-title">
        <strong class="dark">
            {l s='Here you can find our store locations. Please feel free to contact us:'}
        </strong>
    </p>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="address">{l s='City'}</th>
                <th class="address">{l s='Information'}</th>
            </tr>
        </thead>
        {foreach $stores as $store}
            <tr class="store-small">
                <td><b>{$store['city']}</b></td>
                <td class="address">
                    <span {if isset($addresses_style[$key])} class="{$addresses_style[$key]}"{/if}>
                        {l s='Address:'} {$store['address1']}
                    </span>
                    {if $store.phone}<br/><span>{l s='Phone:'} {$store.phone|escape:'html':'UTF-8'}{/if}
                    {if $store.fax}<br/><span>{l s='Fax:'} {$store.fax|escape:'html':'UTF-8'}{/if}
                    {if $store.email}<br/><span>{l s='Email:'} {$store.email|escape:'html':'UTF-8'}{/if}
                    {if $store.note}<br/><span>{$store.note|escape:'html':'UTF-8'|nl2br}{/if}
                </td>
            </tr>
        {/foreach}
    </table>
{/if}

<div id="legal-information">
    <h3 class="page-subheading">Юридическая информация</h3>
    <p><b>Полное наименование:</b> Индивидуальный предприниматель Максимов Евгений Олегович</p>
    <p><b>Юридический адрес:</b> 295493, Р.Крым, г.Симферополь, пгт Грэсовский, ул.Космическая, дом 14/10, кв.99</p>
    <p><b>ИНН/КПП:</b> 910200024539</p>
    <p><b>ОГРН:</b> 314910216200278</p>
    <p><b>Расчётный счет:</b> 40802810041380000095</p>
    <p><b>Корреспондентский счет:</b> 30101810335100000607</p>
    <p><b>Банк:</b> РНКБ (ОАО), г.МОСКВА</p>
    <p><b>БИК банка:</b> 043510607</p>
</div>

{strip}
{addJsDef map=''}
{addJsDef markers=array()}
{addJsDef infoWindow=''}
{addJsDef locationSelect=''}
{addJsDef defaultLat=$defaultLat}
{addJsDef defaultLong=$defaultLong}
{addJsDef hasStoreIcon=$hasStoreIcon}
{addJsDef distance_unit=$distance_unit}
{addJsDef img_store_dir=$img_store_dir}
{addJsDef img_ps_dir=$img_ps_dir}
{addJsDef searchUrl=$searchUrl}
{addJsDef logo_store=$logo_store}
{addJsDefL name=translation_1}{l s='No stores were found. Please try selecting a wider radius.' js=1}{/addJsDefL}
{addJsDefL name=translation_2}{l s='store found -- see details:' js=1}{/addJsDefL}
{addJsDefL name=translation_3}{l s='stores found -- view all results:' js=1}{/addJsDefL}
{addJsDefL name=translation_4}{l s='Phone:' js=1}{/addJsDefL}
{addJsDefL name=translation_5}{l s='Get directions' js=1}{/addJsDefL}
{addJsDefL name=translation_6}{l s='Not found' js=1}{/addJsDefL}
{/strip}

{*{/if}*}
