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

namespace MpSoft\MpGridProducts\Controllers\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller per la gestione del wrapper della lista dei prodotti
 */
class ProductListingWrapperController extends FrameworkBundleAdminController
{
    /**
     * @param array $params
     * @return string 
     */
    public function renderProductList($params)
    {
        $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        $twig = $container->get('twig');
        return $twig->render('@Modules/mpgridproducts/views/templates/admin/product_listing_wrapper.html.twig', $params);
    }
}
