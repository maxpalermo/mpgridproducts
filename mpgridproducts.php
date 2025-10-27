<?php

/**
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
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

// Autoload files using Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use MpSoft\MpGridProducts\Controllers\Front\ProductListingWrapperController;
use MpSoft\MpGridProducts\Traits\getTwigEnvironment;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class MpGridProducts extends Module implements WidgetInterface
{
    /**
     * @var string
     */
    private $output = '';

    public function __construct()
    {
        $this->name = 'mpgridproducts';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'MP Soft';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('MP Grid Products', [], 'Modules.Mpgridproducts.Admin');
        $this->description = $this->trans('Display products in a customizable grid/table view.', [], 'Modules.Mpgridproducts.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Mpgridproducts.Admin');
    }

    /**
     * Install the module
     *
     * @return bool
     */
    public function install()
    {
        // Set default configuration values
        Configuration::updateValue('MPGRIDPRODUCTS_ENABLE', 1);
        Configuration::updateValue('MPGRIDPRODUCTS_ITEMS_PER_PAGE', 20);
        Configuration::updateValue('MPGRIDPRODUCTS_DEFAULT_ORDER_BY', 'position');
        Configuration::updateValue('MPGRIDPRODUCTS_DEFAULT_ORDER_WAY', 'asc');
        Configuration::updateValue('MPGRIDPRODUCTS_COL_IMAGE', 1);
        Configuration::updateValue('MPGRIDPRODUCTS_COL_BRAND', 1);
        Configuration::updateValue('MPGRIDPRODUCTS_COL_NAME', 1);
        Configuration::updateValue('MPGRIDPRODUCTS_COL_REFERENCE', 1);
        Configuration::updateValue('MPGRIDPRODUCTS_COL_MANUFACTURER', 1);
        Configuration::updateValue('MPGRIDPRODUCTS_COL_PRICE', 1);
        Configuration::updateValue('MPGRIDPRODUCTS_COL_ACTIONS', 1);

        // Impostazioni API Tyres24
        Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_API_URL', 'https://api.tyres24.com/v1');
        Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_API_KEY', '');
        Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_API_TIMEOUT', 30);
        Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_CACHE_TIME', 60);  // 60 minuti di default
        Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_PRICE_LOAD', 1.15);  // 15% di ricarica di default

        return parent::install() &&
            $this->registerHook('displayProductListingWrapper') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('actionFrontControllerSetMedia');
    }

    /**
     * Uninstall the module
     *
     * @return bool
     */
    public function uninstall()
    {
        // Delete configuration values
        Configuration::deleteByName('MPGRIDPRODUCTS_ENABLE');
        Configuration::deleteByName('MPGRIDPRODUCTS_ITEMS_PER_PAGE');
        Configuration::deleteByName('MPGRIDPRODUCTS_DEFAULT_ORDER_BY');
        Configuration::deleteByName('MPGRIDPRODUCTS_DEFAULT_ORDER_WAY');
        Configuration::deleteByName('MPGRIDPRODUCTS_COL_IMAGE');
        Configuration::deleteByName('MPGRIDPRODUCTS_COL_BRAND');
        Configuration::deleteByName('MPGRIDPRODUCTS_COL_NAME');
        Configuration::deleteByName('MPGRIDPRODUCTS_COL_REFERENCE');
        Configuration::deleteByName('MPGRIDPRODUCTS_COL_MANUFACTURER');
        Configuration::deleteByName('MPGRIDPRODUCTS_COL_PRICE');
        Configuration::deleteByName('MPGRIDPRODUCTS_COL_ACTIONS');

        // Elimina le impostazioni API Tyres24
        Configuration::deleteByName('MPGRIDPRODUCTS_TYRES24_API_URL');
        Configuration::deleteByName('MPGRIDPRODUCTS_TYRES24_API_KEY');
        Configuration::deleteByName('MPGRIDPRODUCTS_TYRES24_API_TIMEOUT');
        Configuration::deleteByName('MPGRIDPRODUCTS_TYRES24_CACHE_TIME');
        Configuration::deleteByName('MPGRIDPRODUCTS_TYRES24_PRICE_LOAD');

        return parent::uninstall();
    }

    /**
     * Add CSS and JS to the header
     *
     * @param array $params
     * @return void
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        // Check if the module is enabled
        if (!Configuration::get('MPGRIDPRODUCTS_ENABLE')) {
            return;
        }

        if (
            $this->context->controller->php_self == 'category' ||
            $this->context->controller->php_self == 'search' ||
            $this->context->controller->php_self == 'manufacturer' ||
            $this->context->controller->php_self == 'supplier'
        ) {
            $this->context->controller->registerStylesheet(
                'mpgridproducts-style',
                'modules/' . $this->name . '/views/css/mpgridproducts.css',
                [
                    'media' => 'all',
                    'priority' => 200,
                ]
            );

            $this->context->controller->registerStylesheet(
                'rowproduct-style',
                'modules/' . $this->name . '/views/css/row-product.css',
                [
                    'media' => 'all',
                    'priority' => 200,
                ]
            );

            // Registra Chosen CSS
            $this->context->controller->registerStylesheet(
                'chosen-css',
                'js/jquery/plugins/chosen/jquery.chosen.css',
                [
                    'media' => 'all',
                    'priority' => 150,
                ]
            );

            // Registra Chosen JS
            $this->context->controller->registerJavascript(
                'chosen-js',
                'js/jquery/plugins/chosen/jquery.chosen.js',
                [
                    'position' => 'bottom',
                    'priority' => 150,
                ]
            );

            /*
             * $this->context->controller->registerJavascript(
             *     'mpgridproducts-script',
             *     'modules/' . $this->name . '/views/js/mpgridproducts.js',
             *     [
             *         'position' => 'bottom',
             *         'priority' => 200,
             *     ]
             * );
             */
        }
    }

    /**
     * Hook to replace the product list with our grid
     *
     * @param array $params
     * @return string
     */
    public function hookDisplayProductListingWrapper($params)
    {
        // Check if the module is enabled
        if (!Configuration::get('MPGRIDPRODUCTS_ENABLE')) {
            return '';
        }

        $controller = new ProductListingWrapperController();
        return $controller->renderProductList($params);
    }

    /**
     * Implement renderWidget method from WidgetInterface
     *
     * @param string $hookName
     * @param array $configuration
     * @return string
     */
    public function renderWidget($hookName, array $configuration)
    {
        if ($hookName == 'displayProductListingWrapper') {
            return $this->hookDisplayProductListingWrapper($configuration);
        }

        return '';
    }

    /**
     * Implement getWidgetVariables method from WidgetInterface
     *
     * @param string $hookName
     * @param array $configuration
     * @return array
     */
    public function getWidgetVariables($hookName, array $configuration)
    {
        if ($hookName == 'displayProductListingWrapper') {
            // Check if the module is enabled
            if (!Configuration::get('MPGRIDPRODUCTS_ENABLE')) {
                return [];
            }

            // Get the current controller
            $controller = $this->context->controller;

            // Get configuration values
            $columns = [
                'image' => (bool) Configuration::get('MPGRIDPRODUCTS_COL_IMAGE'),
                'brand' => (bool) Configuration::get('MPGRIDPRODUCTS_COL_BRAND'),
                'name' => (bool) Configuration::get('MPGRIDPRODUCTS_COL_NAME'),
                'reference' => (bool) Configuration::get('MPGRIDPRODUCTS_COL_REFERENCE'),
                'manufacturer' => (bool) Configuration::get('MPGRIDPRODUCTS_COL_MANUFACTURER'),
                'price' => (bool) Configuration::get('MPGRIDPRODUCTS_COL_PRICE'),
                'actions' => (bool) Configuration::get('MPGRIDPRODUCTS_COL_ACTIONS'),
            ];

            // Get the listing variables from the controller
            $listing = [];
            if (method_exists($controller, 'getTemplateVarListing')) {
                $listing = $controller->getTemplateVarListing();
            }

            return [
                'listing' => $listing,
                'ajax_url' => $this->context->link->getModuleLink($this->name, 'Cron'),
                'mpgridproducts_columns' => $columns,
                'mpgridproducts_items_per_page' => (int) Configuration::get('MPGRIDPRODUCTS_ITEMS_PER_PAGE'),
                'mpgridproducts_order_by' => Configuration::get('MPGRIDPRODUCTS_DEFAULT_ORDER_BY'),
                'mpgridproducts_order_way' => Configuration::get('MPGRIDPRODUCTS_DEFAULT_ORDER_WAY'),
            ];
        }

        return [];
    }

    /**
     * Module configuration page
     *
     * @return string
     */
    public function getContent()
    {
        $this->output = '';

        // Process form submission
        if (Tools::isSubmit('submitMpGridProducts')) {
            // Update general settings
            Configuration::updateValue('MPGRIDPRODUCTS_ENABLE', (int) Tools::getValue('MPGRIDPRODUCTS_ENABLE'));
            Configuration::updateValue('MPGRIDPRODUCTS_ITEMS_PER_PAGE', (int) Tools::getValue('MPGRIDPRODUCTS_ITEMS_PER_PAGE'));
            Configuration::updateValue('MPGRIDPRODUCTS_DEFAULT_ORDER_BY', Tools::getValue('MPGRIDPRODUCTS_DEFAULT_ORDER_BY'));
            Configuration::updateValue('MPGRIDPRODUCTS_DEFAULT_ORDER_WAY', Tools::getValue('MPGRIDPRODUCTS_DEFAULT_ORDER_WAY'));

            $this->output .= $this->displayConfirmation($this->trans('Settings updated', [], 'Admin.Notifications.Success'));
        }

        if (Tools::isSubmit('submitMpGridProductsColumns')) {
            // Update column settings
            Configuration::updateValue('MPGRIDPRODUCTS_COL_IMAGE', (int) Tools::getValue('MPGRIDPRODUCTS_COL_IMAGE', 0));
            Configuration::updateValue('MPGRIDPRODUCTS_COL_BRAND', (int) Tools::getValue('MPGRIDPRODUCTS_COL_BRAND', 0));
            Configuration::updateValue('MPGRIDPRODUCTS_COL_NAME', (int) Tools::getValue('MPGRIDPRODUCTS_COL_NAME', 0));
            Configuration::updateValue('MPGRIDPRODUCTS_COL_REFERENCE', (int) Tools::getValue('MPGRIDPRODUCTS_COL_REFERENCE', 0));
            Configuration::updateValue('MPGRIDPRODUCTS_COL_MANUFACTURER', (int) Tools::getValue('MPGRIDPRODUCTS_COL_MANUFACTURER', 0));
            Configuration::updateValue('MPGRIDPRODUCTS_COL_PRICE', (int) Tools::getValue('MPGRIDPRODUCTS_COL_PRICE', 0));
            Configuration::updateValue('MPGRIDPRODUCTS_COL_ACTIONS', (int) Tools::getValue('MPGRIDPRODUCTS_COL_ACTIONS', 0));

            $this->output .= $this->displayConfirmation($this->trans('Column settings updated', [], 'Modules.Mpgridproducts.Admin'));
        }

        if (Tools::isSubmit('submitMpGridProductsApi')) {
            // Update API settings
            Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_API_URL', Tools::getValue('MPGRIDPRODUCTS_TYRES24_API_URL'));
            Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_API_KEY', Tools::getValue('MPGRIDPRODUCTS_TYRES24_API_KEY'));
            Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_API_TIMEOUT', (int) Tools::getValue('MPGRIDPRODUCTS_TYRES24_API_TIMEOUT', 30));
            Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_CACHE_TIME', (int) Tools::getValue('MPGRIDPRODUCTS_TYRES24_CACHE_TIME', 60));
            Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_PRICE_LOAD', (float) Tools::getValue('MPGRIDPRODUCTS_TYRES24_PRICE_LOAD', 1.15));

            $this->output .= $this->displayConfirmation($this->trans('API settings updated', [], 'Modules.Mpgridproducts.Admin'));
        }

        // Load current configuration values
        $this->context->smarty->assign([
            'MPGRIDPRODUCTS_ENABLE' => Configuration::get('MPGRIDPRODUCTS_ENABLE'),
            'MPGRIDPRODUCTS_ITEMS_PER_PAGE' => Configuration::get('MPGRIDPRODUCTS_ITEMS_PER_PAGE'),
            'MPGRIDPRODUCTS_DEFAULT_ORDER_BY' => Configuration::get('MPGRIDPRODUCTS_DEFAULT_ORDER_BY'),
            'MPGRIDPRODUCTS_DEFAULT_ORDER_WAY' => Configuration::get('MPGRIDPRODUCTS_DEFAULT_ORDER_WAY'),
            'MPGRIDPRODUCTS_COL_IMAGE' => Configuration::get('MPGRIDPRODUCTS_COL_IMAGE'),
            'MPGRIDPRODUCTS_COL_BRAND' => Configuration::get('MPGRIDPRODUCTS_COL_BRAND'),
            'MPGRIDPRODUCTS_COL_NAME' => Configuration::get('MPGRIDPRODUCTS_COL_NAME'),
            'MPGRIDPRODUCTS_COL_REFERENCE' => Configuration::get('MPGRIDPRODUCTS_COL_REFERENCE'),
            'MPGRIDPRODUCTS_COL_MANUFACTURER' => Configuration::get('MPGRIDPRODUCTS_COL_MANUFACTURER'),
            'MPGRIDPRODUCTS_COL_PRICE' => Configuration::get('MPGRIDPRODUCTS_COL_PRICE'),
            'MPGRIDPRODUCTS_COL_ACTIONS' => Configuration::get('MPGRIDPRODUCTS_COL_ACTIONS'),
            'MPGRIDPRODUCTS_TYRES24_API_URL' => Configuration::get('MPGRIDPRODUCTS_TYRES24_API_URL'),
            'MPGRIDPRODUCTS_TYRES24_API_KEY' => Configuration::get('MPGRIDPRODUCTS_TYRES24_API_KEY'),
            'MPGRIDPRODUCTS_TYRES24_API_TIMEOUT' => Configuration::get('MPGRIDPRODUCTS_TYRES24_API_TIMEOUT'),
            'MPGRIDPRODUCTS_TYRES24_CACHE_TIME' => Configuration::get('MPGRIDPRODUCTS_TYRES24_CACHE_TIME'),
            'MPGRIDPRODUCTS_TYRES24_PRICE_LOAD' => Configuration::get('MPGRIDPRODUCTS_TYRES24_PRICE_LOAD'),
            'current' => $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name,
        ]);

        // Display configuration form
        return $this->output . $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function getFrontLink()
    {
        $url = $this->context->link->getModuleLink($this->name, 'Cron');
        return $url;
    }

    public function getAdminLink($controller)
    {
        try {
            // Verifichiamo se il container Symfony è disponibile
            $container = SymfonyContainer::getInstance();

            if ($container !== null) {
                $router = $container->get('router');
                $routeName = 'mpgridproduct_' . $controller;

                // Verifica se la route esiste
                $routeCollection = $router->getRouteCollection();
                if ($routeCollection->get($routeName)) {
                    $url = $router->generate($routeName);
                    return $url;
                }
            }
        } catch (ServiceNotFoundException $e) {
            // Il servizio router non è disponibile
        } catch (\Exception $e) {
            // Qualsiasi altra eccezione
        }

        // Fallback: utilizziamo il metodo tradizionale di PrestaShop
        $context = Context::getContext();
        $params = [
            'fc' => 'module',
            'module' => 'mpgridproducts',
            'controller' => $controller
        ];

        return $context->link->getModuleLink('mpgridproducts', $controller, $params);
    }
}
