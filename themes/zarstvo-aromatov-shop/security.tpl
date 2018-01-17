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

{capture name=path}
    <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
        {l s='My account'}
    </a>
    <span class="navigation-pipe">
        {$navigationPipe}
    </span>
    <span class="navigation_page">
        {l s='My security'}
    </span>
{/capture}
<div class="box">
    <h1 class="page-subheading">
        {l s='My security'}
    </h1>

    {include file="$tpl_dir./errors.tpl"}

    {if isset($confirmation) && $confirmation}
        <p class="alert alert-success">
            {l s='Your personal information has been successfully updated.'}
            
        </p>
    {else}
        <p class="info-title">
            {l s='You can change your password here.'}
        </p>
        <p class="required">
            <sup>*</sup>{l s='Required field'}
        </p>
        <form action="{$link->getPageLink('security', true)|escape:'html':'UTF-8'}" method="post" class="std" enctype="multipart/form-data">
            <fieldset>
                <div class="required form-group">
                    <label for="old_passwd" class="required">
                        {l s='Current Password'}
                    </label>
                    <input class="is_required validate form-control" type="password" data-validate="isPasswd" name="old_passwd" id="old_passwd" />
                </div>
                <div class="password form-group">
                    <label for="passwd">
                        {l s='New Password'}
                    </label>
                    <input class="is_required validate form-control" type="password" data-validate="isPasswd" name="passwd" id="passwd" />
                </div>
                <div class="password form-group">
                    <label for="confirmation">
                        {l s='Confirmation'}
                    </label>
                    <input class="is_required validate form-control" type="password" data-validate="isPasswd" name="confirmation" id="confirmation" />
                </div>
                <div class="form-group">
                    <button type="submit" name="submitSecurity" class="btn btn-default button button-medium">
                        <span>{l s='Save'}</span>
                    </button>
                </div>
            </fieldset>
        </form> <!-- .std -->
    {/if}
</div>
<ul class="footer_links clearfix">
    <li>
        <a class="btn btn-default button button-small" href="{$link->getPageLink('my-account', true)}">
            <span>
                {l s='Back to your account'}
            </span>
        </a>
    </li>
    <li>
        <a class="btn btn-default button button-small" href="{$base_dir}">
            <span>
                {l s='Home'}
            </span>
        </a>
    </li>
</ul>