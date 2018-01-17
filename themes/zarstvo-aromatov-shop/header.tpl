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
<!DOCTYPE HTML>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="{$language_code|escape:'html':'UTF-8'}"><![endif]-->
<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8 ie7" lang="{$language_code|escape:'html':'UTF-8'}"><![endif]-->
<!--[if IE 8]><html class="no-js lt-ie9 ie8" lang="{$language_code|escape:'html':'UTF-8'}"><![endif]-->
<!--[if gt IE 8]> <html class="no-js ie9" lang="{$language_code|escape:'html':'UTF-8'}"><![endif]-->
<html lang="{$language_code|escape:'html':'UTF-8'}">
	<head>
		<meta charset="utf-8" />
		<title>{$meta_title|escape:'html':'UTF-8'}</title>
{if isset($meta_description) AND $meta_description}
		<meta name="description" content="{$meta_description|escape:'html':'UTF-8'}" />
{/if}
{if isset($meta_keywords) AND $meta_keywords}
		<meta name="keywords" content="{$meta_keywords|escape:'html':'UTF-8'}" />
{/if}
		<meta name="generator" content="PrestaShop" />
		<meta name="robots" content="{if isset($nobots)}no{/if}index,{if isset($nofollow) && $nofollow}no{/if}follow" />
		<meta name="viewport" content="width=device-width, minimum-scale=0.25, maximum-scale=1.6, initial-scale=1.0" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<link rel="icon" type="image/vnd.microsoft.icon" href="{$favicon_url}?{$img_update_time}" />
		<link rel="shortcut icon" type="image/x-icon" href="{$favicon_url}?{$img_update_time}" />
		<link rel="alternate" hreflang="ru" href="http://ru.zarstvo-shop.com/" />
{if isset($css_files)}
	{foreach from=$css_files key=css_uri item=media}
		<link rel="stylesheet" href="{$css_uri|escape:'html':'UTF-8'}" type="text/css" media="{$media|escape:'html':'UTF-8'}" />
	{/foreach}
{/if}
{if isset($js_defer) && !$js_defer && isset($js_files) && isset($js_def)}
	{$js_def}
	{foreach from=$js_files item=js_uri}
	<script type="text/javascript" src="{$js_uri|escape:'html':'UTF-8'}"></script>
	{/foreach}
{/if}
		{$HOOK_HEADER}
		<!--[if IE 8]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
		<![endif]-->

		{if $smarty.server.HTTP_HOST == 'ru.zarstvo-shop.com'}
			<!-- Yandex.Metrika counter -->
			<script type="text/javascript">
				var yaParams = {};

			    (function (d, w, c) {
			        (w[c] = w[c] || []).push(function() {
			            try {
			                w.yaCounter31092596 = new Ya.Metrika({
			                    id:31092596,
			                    clickmap:true,
			                    trackLinks:true,
			                    accurateTrackBounce:true,
			                    webvisor:true,
			                    trackHash:true
			                });
			            } catch(e) { }
			        });

			        var n = d.getElementsByTagName("script")[0],
			            s = d.createElement("script"),
			            f = function () { n.parentNode.insertBefore(s, n); };
			        s.type = "text/javascript";
			        s.async = true;
			        s.src = "https://mc.yandex.ru/metrika/watch.js";

			        if (w.opera == "[object Opera]") {
			            d.addEventListener("DOMContentLoaded", f, false);
			        } else { f(); }
			    })(document, window, "yandex_metrika_callbacks");
			</script>
			<noscript><div><img src="https://mc.yandex.ru/watch/31092596" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
			<!-- /Yandex.Metrika counter -->
		{/if}

	</head>
	<body{if isset($page_name)} id="{$page_name|escape:'html':'UTF-8'}"{/if} class="{if isset($page_name)}{$page_name|escape:'html':'UTF-8'}{/if}{if isset($body_classes) && $body_classes|@count} {implode value=$body_classes separator=' '}{/if}{if $hide_left_column} hide-left-column{/if}{if $hide_right_column} hide-right-column{/if}{if isset($content_only) && $content_only} content_only{/if} lang_{$lang_iso}">

	{if !isset($content_only) || !$content_only}
		{if isset($restricted_country_mode) && $restricted_country_mode}
			<div id="restricted-country">
				<p>{l s='You cannot place a new order from your country.'} <span class="bold">{$geolocation_country|escape:'html':'UTF-8'}</span></p>
			</div>
		{/if}
		<div id="page">
			<div class="header-container">
				<header id="header">
					<div class="banner">
						<div class="container">
							<div class="row">
								{hook h="displayBanner"}
							</div>
						</div>
					</div>
					<div class="nav">
						<div class="container">
							<div class="row">
								<nav>{hook h="displayNav"}</nav>
							</div>
						</div>
					</div>
					<div id="header-main-container">
						<div class="container">
							{if isset($HOOK_TOP)}
								{$HOOK_TOP}
							{/if}
							{if $page_name !='index' && $page_name !='pagenotfound'}
								{include file="$tpl_dir./breadcrumb.tpl"}
							{/if}
						</div>
					</div>
				</header>
			</div>
			<div class="columns-container">
				<div id="columns" class="container">
					<div id="top_column" class="center_column col-xs-12 col-sm-12">{hook h="displayTopColumn"}</div>
					<div class="row">
						{if $page_name !='index' && $page_name != 'product' && $page_name !='pagenotfound'}
							<div id="left_column" class="column col-xs-12 col-sm-{$left_column_size|intval}">{hook h="displayLeftColumn"}</div>
							{if isset($left_column_size) && isset($right_column_size)}
								{assign var='cols' value=(12 - $left_column_size - $right_column_size)}
							{else}
								{assign var='cols' value=12}
							{/if}
						{else}
							{assign var='cols' value=12}
						{/if}
						<div id="center_column" class="center_column col-xs-12 col-sm-{$cols|intval}">
	{/if}
