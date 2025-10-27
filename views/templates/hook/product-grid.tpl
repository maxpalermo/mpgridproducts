{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">


<style>
    .calender-text-top {
        color: #f5f5f5;
    }

    .calender-text-bottom {
        color: #303030;
    }

    .gap-0 {
        gap: 0;
    }

    .gap-2 {
        gap: 1rem;
    }

    .gap-4px {
        gap: 4px;
    }

    .icon-box {
        display: inline-flex;
        align-items: center;
        justify-content: start;
        border: 1px solid #d0d0d0;
        border-radius: 3px;
        padding: 4px;
        background-color: #f5f5f5;
        color: #303030;
        font-size: 16px;
        font-weight: 600;
        margin-right: 8px;
    }
</style>

<div id="mp-grid-products" class="mp-grid-products"
     data-ajax-url="{$ajax_url}"
     data-category-id="{if isset($smarty.get.id_category)}{$smarty.get.id_category|intval}{else}0{/if}"
     data-search-query="{if isset($smarty.get.s)}{$smarty.get.s|escape:'html':'UTF-8'}{else}{/if}"
     data-items-per-page="{$mpgridproducts_items_per_page}"
     data-order-by="{$mpgridproducts_order_by}"
     data-order-way="{$mpgridproducts_order_way}">

    <div class="mp-grid-header">
        <div class="mp-grid-title">
            <h2>{l s='Products' d='Modules.Mpgridproducts.Shop'}</h2>
        </div>
        <div class="mp-grid-controls">
            <div class="mp-grid-pagination-info">
                <span class="mp-grid-showing">
                    {l s='Showing' d='Modules.Mpgridproducts.Shop'} <span class="mp-grid-from">1</span> - <span class="mp-grid-to">10</span> {l s='of' d='Modules.Mpgridproducts.Shop'} <span class="mp-grid-total">{if isset($listing.pagination) && isset($listing.pagination.total_items)}{$listing.pagination.total_items}{else}0{/if}</span> {l s='items' d='Modules.Mpgridproducts.Shop'}
                </span>
            </div>
            <div class="mp-grid-per-page">
                <label for="mp-grid-limit">{l s='Show' d='Modules.Mpgridproducts.Shop'}</label>
                <select id="mp-grid-limit" class="form-control">
                    <option value="10" {if $mpgridproducts_items_per_page == 10}selected="selected" {/if}>10</option>
                    <option value="20" {if $mpgridproducts_items_per_page == 20}selected="selected" {/if}>20</option>
                    <option value="50" {if $mpgridproducts_items_per_page == 50}selected="selected" {/if}>50</option>
                    <option value="100" {if $mpgridproducts_items_per_page == 100}selected="selected" {/if}>100</option>
                </select>
            </div>
        </div>
    </div>

    <div class="mp-grid-table-container">
        <input type="hidden" id="token" value="{$tokenString}">
        <table class="mp-grid-table table table-striped">
            <thead>
                <tr>
                    <th class="mp-grid-col-name">{l s='Prodotto' d='Modules.Mpgridproducts.Shop'}</th>
                    <th class="mp-grid-col-reference">{l s='Riferimento' d='Modules.Mpgridproducts.Shop'}</th>
                    <th class="mp-grid-col-price">{l s='Prezzo' d='Modules.Mpgridproducts.Shop'}</th>
                    <th class="mp-grid-col-stock">{l s='Quantità' d='Modules.Mpgridproducts.Shop'}</th>
                    <th class="mp-grid-col-date-shipping">{l s='Data consegna' d='Modules.Mpgridproducts.Shop'}</th>
                    <th class="mp-grid-col-actions">{l s='Azioni' d='Modules.Mpgridproducts.Shop'}</th>
                </tr>
            </thead>
            <tbody>
                <tr class="mp-grid-loading">
                    <td colspan="6" class="text-center">
                        <div class="mp-grid-loader">
                            <i class="material-icons">cached</i> {l s='Caricamento prodotti...' d='Modules.Mpgridproducts.Shop'}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mp-grid-footer">
        <div class="mp-grid-pagination">
            <nav>
                <ul class="pagination">
                    <li class="page-item disabled mp-grid-prev">
                        <a class="page-link" href="#" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                            <span class="sr-only">{l s='Previous' d='Modules.Mpgridproducts.Shop'}</span>
                        </a>
                    </li>
                    {if isset($listing.pagination) && isset($listing.pagination.pages_count) && $listing.pagination.pages_count > 0}
                        {for $page=1 to $listing.pagination.pages_count}
                            <li class="page-item {if $page == 1}active{/if} mp-grid-page-{$page}"><a class="page-link" href="#">{$page}</a></li>
                        {/for}
                    {else}
                        <li class="page-item active mp-grid-page-1"><a class="page-link" href="#">1</a></li>
                    {/if}
                    <li class="page-item mp-grid-next">
                        <a class="page-link" href="#" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                            <span class="sr-only">{l s='Next' d='Modules.Mpgridproducts.Shop'}</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div style="display: none">
        <input type="hidden" id="mp-grid-fetch-product-info-url" value="{$fetchProductInfoURL}">
    </div>

    <!-- Template for product row -->
    <template id="mp-grid-product-template">
        <tr class="mp-grid-product" data-id-product="{literal}{{id}}{/literal}">
            <!-- Name column -->
            <td class="mp-grid-col-name">
                <div class="form-group">
                    {literal}{{icon_toolbar}}{/literal}
                </div>
                <a href="{literal}{{url}}{/literal}" class="mp-grid-product-name">
                    {literal}{{name}}{/literal}
                </a>
                <div class="mp-grid-product-desc">
                    {literal}{{pfu_name}} <strong>{{pfu_price}}</strong>{/literal}
                </div>
            </td>
            <!-- Reference column -->
            <td class="mp-grid-col-reference" style="max-width: 320px;">
                <div class="reference">{literal}{{reference}}{/literal}</div>
                <div class="image">
                    <img src="{literal}{{brand_image}}{/literal}" alt="{literal}{{brand_name}}{/literal}" class="img-fluid" style="max-height: 48px; object-fit: fill;">
                </div>
            </td>
            <!-- Price column -->
            <td class="mp-grid-col-price justify-content-end" style="width: 72px;">
                {literal}{{price_tax_exc_formatted}}{/literal}
            </td>
            <!-- Stock column -->
            <td class="mp-grid-col-stock justify-content-center" style="width: 92px;">
                <div class="form-group">
                    <input type="number" class="form-control cart-quantity" value="1" min="1" max="{literal}{{stock}}{/literal}" name="mp-grid-stock-input[]" style="background-color: #f5f5f5; color: 303030; border: 1px solid #808080; border-radius: 5px; text-align: right;">
                </div>
                <div class="stock-container">
                    <span>Magazzino:</span>
                    <span class="badge" style="background-color: #4cbb6c; color: #F5F5F5; border: 1px solid #dddddd;">
                        <strong>{literal}{{stock}}{/literal}</strong>
                    </span>
                </div>
            </td>
            <!-- Date shipping column -->
            <td class="mp-grid-col-date-shipping" style="text-align: center; width: 72px;">
                <svg class="calender" xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 32 32">
                    <path class="calender-icon" fill="#808080" fill-rule="nonzero" d="M23 4h2a3 3 0 0 1 3 3v18a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h2v1a2 2 0 1 0 4 0V4h6v1a2 2 0 1 0 4 0V4zM11 2a1 1 0 0 1 1 1v2a1 1 0 0 1-2 0V3a1 1 0 0 1 1-1zm10 0a1 1 0 0 1 1 1v2a1 1 0 0 1-2 0V3a1 1 0 0 1 1-1zM6 16v7.889c0 1.227.995 2.222 2.222 2.222h15.556A2.222 2.222 0 0 0 26 23.89V16H6z"></path>
                    <text class="calender-text-top" x="9" y="14" font-size="9px" fill="white">{literal}{{month_delivery}}{/literal}</text>
                    <text class="calender-text-bottom" x="9" y="25" fill="#808080" font-size="11px" font-weight="900">{literal}{{day_delivery}}{/literal}</text>
                </svg>
            </td>
            <!-- Actions column -->
            <td class="mp-grid-col-actions d-flex justify-content-end align-items-center" style="max-width: 150px;">
                <a href="javascript:void(0);" class="btn btn-warning btn-sm-circle mp-grid-info-row" title="{l s='Info' d='Modules.Mpgridproducts.Shop'}">
                    <i class="material-icons">info</i>
                </a>
                <a href="{literal}{{url}}{/literal}" class="btn btn-info btn-sm-circle mp-grid-view" title="{l s='Vedi' d='Modules.Mpgridproducts.Shop'}">
                    <i class="material-icons">search</i>
                </a>
                <a href="javascript:void(0);" data-id_product="{literal}{{id}}{/literal}" data-id_product_attribute="0" data-quantity="1" data-url="{$frontControllerAddToCartUrl}" class="btn btn-success btn-sm-circle mp-grid-add-to-cart" title="{l s='Aggiungi al carrello' d='Modules.Mpgridproducts.Shop'}">
                    <i class="material-icons">shopping_cart</i>
                </a>
            </td>
        </tr>
    </template>
    <!-- End of template -->

    <!-- Template for product informations row -->
    <template id="mp-grid-product-info-template">
        <tr class="mp-grid-product-info" data-id-product="{literal}{{id}}{/literal}">
            <td colspan="6" class="text-center">
                <div class="mp-grid-loader">
                    <i class="material-icons">cached</i> {l s='Caricamento informazioni...' d='Modules.Mpgridproducts.Shop'}
                </div>
            </td>
        </tr>
    </template>
    <!-- End of template -->
</div>