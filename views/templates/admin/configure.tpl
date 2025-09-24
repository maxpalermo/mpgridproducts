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

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-cogs"></i> {l s='MP Grid Products Configuration' d='Modules.Mpgridproducts.Admin'}
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <p class="alert alert-info">
                    {l s='Configure the MP Grid Products module to customize how products are displayed in the grid.' d='Modules.Mpgridproducts.Admin'}
                </p>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-cog"></i> {l s='General Settings' d='Modules.Mpgridproducts.Admin'}
    </div>
    <div class="panel-body">
        <form id="module_form" class="defaultForm form-horizontal" action="{$current|escape:'html':'UTF-8'}&amp;configure=mpgridproducts" method="post" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="submitMpGridProducts" value="1" />

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Enable Grid View' d='Modules.Mpgridproducts.Admin'}
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="MPGRIDPRODUCTS_ENABLE" id="MPGRIDPRODUCTS_ENABLE_on" value="1" {if $MPGRIDPRODUCTS_ENABLE}checked="checked" {/if}>
                        <label for="MPGRIDPRODUCTS_ENABLE_on">{l s='Yes' d='Admin.Global'}</label>
                        <input type="radio" name="MPGRIDPRODUCTS_ENABLE" id="MPGRIDPRODUCTS_ENABLE_off" value="0" {if !$MPGRIDPRODUCTS_ENABLE}checked="checked" {/if}>
                        <label for="MPGRIDPRODUCTS_ENABLE_off">{l s='No' d='Admin.Global'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">
                        {l s='Enable or disable the grid view for product listings.' d='Modules.Mpgridproducts.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Default Products Per Page' d='Modules.Mpgridproducts.Admin'}
                </label>
                <div class="col-lg-9">
                    <select name="MPGRIDPRODUCTS_ITEMS_PER_PAGE" class="form-control fixed-width-xl">
                        <option value="10" {if $MPGRIDPRODUCTS_ITEMS_PER_PAGE == 10}selected="selected" {/if}>10</option>
                        <option value="20" {if $MPGRIDPRODUCTS_ITEMS_PER_PAGE == 20}selected="selected" {/if}>20</option>
                        <option value="50" {if $MPGRIDPRODUCTS_ITEMS_PER_PAGE == 50}selected="selected" {/if}>50</option>
                        <option value="100" {if $MPGRIDPRODUCTS_ITEMS_PER_PAGE == 100}selected="selected" {/if}>100</option>
                    </select>
                    <p class="help-block">
                        {l s='Set the default number of products to display per page.' d='Modules.Mpgridproducts.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Default Sort Order' d='Modules.Mpgridproducts.Admin'}
                </label>
                <div class="col-lg-9">
                    <select name="MPGRIDPRODUCTS_DEFAULT_ORDER_BY" class="form-control fixed-width-xl">
                        <option value="position" {if $MPGRIDPRODUCTS_DEFAULT_ORDER_BY == 'position'}selected="selected" {/if}>{l s='Position' d='Modules.Mpgridproducts.Admin'}</option>
                        <option value="name" {if $MPGRIDPRODUCTS_DEFAULT_ORDER_BY == 'name'}selected="selected" {/if}>{l s='Name' d='Modules.Mpgridproducts.Admin'}</option>
                        <option value="price" {if $MPGRIDPRODUCTS_DEFAULT_ORDER_BY == 'price'}selected="selected" {/if}>{l s='Price' d='Modules.Mpgridproducts.Admin'}</option>
                        <option value="date_add" {if $MPGRIDPRODUCTS_DEFAULT_ORDER_BY == 'date_add'}selected="selected" {/if}>{l s='Date added' d='Modules.Mpgridproducts.Admin'}</option>
                    </select>
                    <p class="help-block">
                        {l s='Set the default sort order for products.' d='Modules.Mpgridproducts.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Default Sort Direction' d='Modules.Mpgridproducts.Admin'}
                </label>
                <div class="col-lg-9">
                    <select name="MPGRIDPRODUCTS_DEFAULT_ORDER_WAY" class="form-control fixed-width-xl">
                        <option value="asc" {if $MPGRIDPRODUCTS_DEFAULT_ORDER_WAY == 'asc'}selected="selected" {/if}>{l s='Ascending' d='Modules.Mpgridproducts.Admin'}</option>
                        <option value="desc" {if $MPGRIDPRODUCTS_DEFAULT_ORDER_WAY == 'desc'}selected="selected" {/if}>{l s='Descending' d='Modules.Mpgridproducts.Admin'}</option>
                    </select>
                    <p class="help-block">
                        {l s='Set the default sort direction for products.' d='Modules.Mpgridproducts.Admin'}
                    </p>
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" name="submitMpGridProducts" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Save' d='Admin.Actions'}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-list"></i> {l s='Column Settings' d='Modules.Mpgridproducts.Admin'}
    </div>
    <div class="panel-body">
        <form id="columns_form" class="defaultForm form-horizontal" action="" method="post" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="submitMpGridProductsColumns" value="1" />

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Visible Columns' d='Modules.Mpgridproducts.Admin'}
                </label>
                <div class="col-lg-9">
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="MPGRIDPRODUCTS_COL_IMAGE" value="1" {if $MPGRIDPRODUCTS_COL_IMAGE}checked="checked" {/if}>
                                {l s='Image' d='Modules.Mpgridproducts.Admin'}
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="MPGRIDPRODUCTS_COL_MANUFACTURER" value="1" {if $MPGRIDPRODUCTS_COL_MANUFACTURER}checked="checked" {/if}>
                                {l s='Brand' d='Modules.Mpgridproducts.Admin'}
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="MPGRIDPRODUCTS_COL_NAME" value="1" {if $MPGRIDPRODUCTS_COL_NAME}checked="checked" {/if}>
                                {l s='Product Name' d='Modules.Mpgridproducts.Admin'}
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="MPGRIDPRODUCTS_COL_REFERENCE" value="1" {if $MPGRIDPRODUCTS_COL_REFERENCE}checked="checked" {/if}>
                                {l s='Reference' d='Modules.Mpgridproducts.Admin'}
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="MPGRIDPRODUCTS_COL_PRICE" value="1" {if $MPGRIDPRODUCTS_COL_PRICE}checked="checked" {/if}>
                                {l s='Price' d='Modules.Mpgridproducts.Admin'}
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="MPGRIDPRODUCTS_COL_ACTIONS" value="1" {if $MPGRIDPRODUCTS_COL_ACTIONS}checked="checked" {/if}>
                                {l s='Actions' d='Modules.Mpgridproducts.Admin'}
                            </label>
                        </div>
                    </div>
                    <p class="help-block">
                        {l s='Select which columns to display in the product grid.' d='Modules.Mpgridproducts.Admin'}
                    </p>
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" name="submitMpGridProductsColumns" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Save' d='Admin.Actions'}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-plug"></i> {l s='Tyres24 API Settings' d='Modules.Mpgridproducts.Admin'}
    </div>
    <div class="panel-body">
        <form id="api_form" class="defaultForm form-horizontal" action="" method="post" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="submitMpGridProductsApi" value="1" />

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='API URL' d='Modules.Mpgridproducts.Admin'}
                </label>
                <div class="col-lg-9">
                    <input type="text" name="MPGRIDPRODUCTS_TYRES24_API_URL" value="{$MPGRIDPRODUCTS_TYRES24_API_URL|escape:'html':'UTF-8'}" class="form-control" />
                    <p class="help-block">
                        {l s='Enter the base URL for the Tyres24 API.' d='Modules.Mpgridproducts.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='API Key' d='Modules.Mpgridproducts.Admin'}
                </label>
                <div class="col-lg-9">
                    <input type="text" name="MPGRIDPRODUCTS_TYRES24_API_KEY" value="{$MPGRIDPRODUCTS_TYRES24_API_KEY|escape:'html':'UTF-8'}" class="form-control" />
                    <p class="help-block">
                        {l s='Enter your Tyres24 API key for authentication.' d='Modules.Mpgridproducts.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='API Timeout (seconds)' d='Modules.Mpgridproducts.Admin'}
                </label>
                <div class="col-lg-9">
                    <input type="number" name="MPGRIDPRODUCTS_TYRES24_API_TIMEOUT" value="{$MPGRIDPRODUCTS_TYRES24_API_TIMEOUT|intval}" class="form-control fixed-width-xl" min="1" max="120" />
                    <p class="help-block">
                        {l s='Set the timeout for API requests in seconds (1-120).' d='Modules.Mpgridproducts.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Tempo di aggiornamento (minuti)' d='Modules.Mpgridproducts.Admin'}
                </label>
                <div class="col-lg-9">
                    <input type="number" name="MPGRIDPRODUCTS_TYRES24_CACHE_TIME" value="{$MPGRIDPRODUCTS_TYRES24_CACHE_TIME|intval}" class="form-control fixed-width-xl" min="5" max="1440" />
                    <p class="help-block">
                        {l s='Imposta il tempo in minuti dopo il quale invalidare la cache e richiedere nuovi dati all\'API (5-1440).' d='Modules.Mpgridproducts.Admin'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Percentuale di ricarica' d='Modules.Mpgridproducts.Admin'}
                </label>
                <div class="col-lg-9">
                    <div class="input-group fixed-width-xl">
                        <input type="number" name="MPGRIDPRODUCTS_TYRES24_PRICE_LOAD" value="{$MPGRIDPRODUCTS_TYRES24_PRICE_LOAD|floatval}" class="form-control" min="1" max="2" step="0.01" />
                        <span class="input-group-addon">x</span>
                    </div>
                    <p class="help-block">
                        {l s='Imposta il moltiplicatore da applicare ai prezzi dei pneumatici (es. 1.15 per una ricarica del 15%).' d='Modules.Mpgridproducts.Admin'}
                    </p>
                </div>
            </div>

            <div class="panel-footer">
                <button type="submit" name="submitMpGridProductsApi" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> {l s='Save' d='Admin.Actions'}
                </button>
            </div>
        </form>
    </div>
</div>