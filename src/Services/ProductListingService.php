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

namespace MpSoft\MpGridProducts\Services;

use Doctrine\DBAL\Connection;
use MpSoft\MpGridProducts\Adapter\PriceListAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment as TwigEnvironment;

/**
 * Servizio per la gestione dei prodotti nella griglia
 */
class ProductListingService
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var PriceListAdapter
     */
    private $priceListAdapter;

    /**
     * @var TwigEnvironment
     */
    private $twig;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Costruttore
     * 
     * @param RequestStack $requestStack
     * @param PriceListAdapter $priceListAdapter
     * @param TwigEnvironment $twig
     * @param Connection $connection
     */
    public function __construct(
        RequestStack $requestStack,
        PriceListAdapter $priceListAdapter,
        TwigEnvironment $twig,
        Connection $connection
    ) {
        $this->requestStack = $requestStack;
        $this->priceListAdapter = $priceListAdapter;
        $this->twig = $twig;
        $this->connection = $connection;
    }

    /**
     * Elabora i prodotti per la visualizzazione nella griglia
     * 
     * @param array $products Lista dei prodotti
     * @return array Lista dei prodotti elaborata
     */
    public function processProductsForGrid(array $products): array
    {
        foreach ($products as &$product) {
            // Aggiungi prezzo e stock dal PriceListAdapter
            $product = $this->priceListAdapter->getPrice($product);
        }

        return $products;
    }

    /**
     * Ottiene il contenuto del wrapper del controller
     * 
     * @return string
     */
    public function getProductListingWrapperContent(): string
    {
        try {
            // Qui puoi implementare la logica che prima era nel controller
            // Ad esempio, puoi renderizzare un template Twig

            return $this->twig->render('@Modules/mpgridproducts/views/templates/admin/product_listing_wrapper.html.twig');
        } catch (\Exception $e) {
            // Log dell'errore
            \PrestaShopLogger::addLog(
                'Errore nel servizio ProductListingService: ' . $e->getMessage(),
                2, // Livello di errore
                null,
                'ProductListingService',
                null,
                true
            );

            return '';
        }
    }
}
