{*
* 2025 MP Soft
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
* @author    MP Soft
* @copyright 2025 MP Soft
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

<style>
    .d-flex {
        display: flex;
    }

    .align-items-center {
        align-items: center;
    }

    .justify-content-center {
        justify-content: center;
    }

    .justify-content-end {
        justify-content: end;
    }

    .btn-sm-circle {
        width: 32px;
        height: 32px;
        max-width: 32px;
        max-height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-sm-circle:hover {
        background-color: #0056b3;
        color: #fff;
    }

    .btn-sm-circle i {
        font-size: 0.9rem !important;
    }

    .badge {
        border-radius: 5%;
        padding: 5px;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #0056b3;
        width: auto;
    }

    .badge-success {
        color: #fff;
        background-color: #0056b3;
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
                    <th class="mp-grid-col-image">{l s='Tipo' d='Modules.Mpgridproducts.Shop'}</th>
                    <th class="mp-grid-col-name">{l s='Prodotto' d='Modules.Mpgridproducts.Shop'}</th>
                    <th class="mp-grid-col-reference">{l s='Riferimento' d='Modules.Mpgridproducts.Shop'}</th>
                    <th class="mp-grid-col-price">{l s='Prezzo' d='Modules.Mpgridproducts.Shop'}</th>
                    <th class="mp-grid-col-stock">{l s='Magazzino' d='Modules.Mpgridproducts.Shop'}</th>
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
            <td class="mp-grid-col-type">
                {assign var="types" value=["directions_car", "airpost_shuttle", "local_shipping"]}
                {assign var="randomIndex" value=rand(0,2)}
                <span class="material-icons">
                    {$types[$randomIndex]}
                </span>
            </td>
            <td class="mp-grid-col-name">
                <a href="{literal}{{url}}{/literal}" class="mp-grid-product-name">
                    {literal}{{name}}{/literal}
                </a>
                <div class="mp-grid-product-desc">
                    {literal}{{description_short}}{/literal}
                </div>
            </td>
            <td class="mp-grid-col-reference">
                <div class="reference">{literal}{{reference}}{/literal}</div>
                <div class="image">
                    <img src="{literal}{{brand_image}}{/literal}" alt="{literal}{{brand_name}}{/literal}" class="img-fluid" style="max-height: 48px; object-fit: fill;">
                </div>
            </td>
            <td class="mp-grid-col-price justify-content-end">
                {literal}{{price_formatted}}{/literal}
            </td>
            <td class="mp-grid-col-stock justify-content-center">
                <div class="form-group">
                    <input type="number" class="form-control stock-input" value="1" min="1" max="{literal}{{stock}}{/literal}" name="mp-grid-stock-input[]">
                </div>
                <div class="stock-container">
                    <span class="badge badge-success">
                        {literal}{{stock}}{/literal}
                    </span>
                </div>
            </td>
            <td class="mp-grid-col-date-shipping justify-content-center">
                {literal}{{date_shipping}}{/literal}
            </td>
            <td class="mp-grid-col-actions d-flex justify-content-end align-items-center">
                <a href="{literal}{{url}}{/literal}" class="btn btn-info btn-sm-circle mp-grid-view" title="{l s='View' d='Modules.Mpgridproducts.Shop'}">
                    <i class="material-icons">search</i>
                </a>
                <a href="javascript:void(0);" data-id_product="{literal}{{id}}{/literal}" data-id_product_attribute="0" data-quantity="1" data-url="{$frontControllerAddToCartUrl}" class="btn btn-success btn-sm-circle mp-grid-add-to-cart" title="{l s='Add' d='Modules.Mpgridproducts.Shop'}">
                    <i class="material-icons">shopping_cart</i>
                </a>
                <a href="javascript:void(0);" class="btn btn-warning btn-sm-circle mp-grid-info-row" title="{l s='Info' d='Modules.Mpgridproducts.Shop'}">
                    <i class="material-icons">info</i>
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