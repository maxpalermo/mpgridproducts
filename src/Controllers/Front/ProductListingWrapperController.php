<?php
/**
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
 */

namespace MpSoft\MpGridProducts\Controllers\Front;

use MpSoft\MpGridProducts\Adapter\PriceListAdapter;

class ProductListingWrapperController
{
    private $controller;
    private $context;
    private $module;

    public function __construct()
    {
        $this->context = \Context::getContext();
        $this->controller = \Tools::getValue('controller');
        $this->module = \Module::getInstanceByName('mpgridproducts');
    }


    /**
     * @param array $params
     * @return string
     */
    public function renderProductList($params)
    {
        // Get the current controller
        $controller = $this->context->controller;

        // Only apply on category, search, manufacturer and supplier pages
        if (
            !($controller->php_self == 'category' ||
                $controller->php_self == 'search' ||
                $controller->php_self == 'manufacturer' ||
                $controller->php_self == 'supplier')
        ) {
            return '';
        }

        // Initialize listing array
        $listing = [
            'products' => [],
            'pagination' => [
                'total_items' => 0,
                'items_shown_from' => 1,
                'items_shown_to' => 0,
                'pages_count' => 1,
                'current_page' => 1
            ]
        ];

        // Get products from hook parameters if available
        if (isset($params['products']) && is_array($params['products'])) {
            $listing['products'] = $params['products'];
        } elseif (method_exists($controller, 'getTemplateVarListing')) {
            // Fallback to controller method
            $controllerListing = $controller->getTemplateVarListing();
            if (isset($controllerListing['products'])) {
                $listing['products'] = $controllerListing['products'];
            }
        }


        //Aggiorno i listini dei prodotti selezionati
        $priceListAdapter = new PriceListAdapter($listing);
        foreach ($listing['products'] as $key => &$product) {
            $product = $priceListAdapter->updateProductValues($product);
        }


        // Get pagination from hook parameters if available
        if (isset($params['pagination']) && is_array($params['pagination'])) {
            $listing['pagination'] = $params['pagination'];
        } elseif (method_exists($controller, 'getTemplateVarListing')) {
            // Fallback to controller method
            $controllerListing = $controller->getTemplateVarListing();
            if (isset($controllerListing['pagination'])) {
                $listing['pagination'] = $controllerListing['pagination'];
            }
        }

        // Update pagination values
        if (!empty($listing['products'])) {
            $count = count($listing['products']);
            $listing['pagination']['items_shown_to'] = $listing['pagination']['items_shown_from'] + $count - 1;
            $listing['pagination']['total_items'] = max($listing['pagination']['total_items'], $count);
        }

        // Assign template variables
        $tplParams = [
            'listing' => $listing,
            'ajax_url' => $this->context->link->getModuleLink($this->module->name, 'Cron'),
            'mpgridproducts_items_per_page' => (int) \Configuration::get('MPGRIDPRODUCTS_ITEMS_PER_PAGE'),
            'mpgridproducts_order_by' => \Configuration::get('MPGRIDPRODUCTS_DEFAULT_ORDER_BY'),
            'mpgridproducts_order_way' => \Configuration::get('MPGRIDPRODUCTS_DEFAULT_ORDER_WAY'),
            'fetchProductInfoURL' => $this->getFrontLink(),
            'frontControllerAddToCartUrl' => $this->context->link->getPageLink('cart'),
            'frontControllerAddToCartUrl2' => $this->context->link->getModuleLink($this->module->name, 'Cron'),
            'tokenString' => \Tools::getToken(false)
        ];

        // Return our template instead of the default one
        $tplPath = $this->module->getLocalPath() . 'views/templates/hook/product-grid.tpl';
        $template = $this->context->smarty->createTemplate($tplPath);
        $template->assign($tplParams);

        return $template->fetch();
    }

    public function getFrontLink()
    {
        $url = $this->context->link->getModuleLink($this->module->name, 'Cron');
        return $url;
    }

}