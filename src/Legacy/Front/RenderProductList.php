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

namespace MpSoft\MpGridProducts\Legacy\Front;

use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Search\SearchProductSearchProvider;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class RenderProductList
{
    private $products;
    private $pagination;
    private $context;
    private $module;

    public function __construct($products, $pagination)
    {
        $this->products = $products;
        $this->pagination = $pagination;
        $this->context = \Context::getContext();
        $this->module = \Module::getInstanceByName('mpgridproducts');
    }

    public function renderProductList()
    {
        // Get pagination parameters
        $page = (int) \Tools::getValue('page', 1);
        $limit = (int) \Tools::getValue('limit', 10);

        // Get category ID if available
        $categoryId = (int) \Tools::getValue('category_id', 0);

        // Get search query if available
        $searchString = \Tools::getValue('search_query', '');

        // Get sort options
        $orderBy = \Tools::getValue('order_by', 'position');
        $orderWay = \Tools::getValue('order_way', 'asc');

        // Validazione dei parametri di ordinamento
        $validOrderByValues = ['position', 'name', 'price', 'date_add', 'reference', 'id_product'];
        $validOrderWayValues = ['asc', 'desc'];

        if (!in_array($orderBy, $validOrderByValues)) {
            $orderBy = 'position';
        }

        if (!in_array(strtolower($orderWay), $validOrderWayValues)) {
            $orderWay = 'asc';
        }

        // Get filter parameters (from faceted search)
        $filters = \Tools::getValue('filters', []);
        if (!empty($filters) && is_string($filters)) {
            $filters = json_decode($filters, true);
        }

        try {
            // Crea un contesto di ricerca
            $searchContext = new ProductSearchContext($this->context);

            // Crea una query di ricerca
            $query = new ProductSearchQuery();
            $query->setResultsPerPage($limit);
            $query->setPage($page);

            // Imposta l'ordinamento corretto
            // SortOrder richiede entity, field e direction
            // Esempio: product, name, asc
            $entity = 'product';
            $field = $orderBy;
            $direction = $orderWay;
            $query->setSortOrder(new SortOrder($entity, $field, $direction));

            // Imposta la categoria se fornita
            if ($categoryId > 0) {
                $query->setIdCategory($categoryId);
                $category = new \Category($categoryId, $this->context->language->id);
                $searchProvider = new CategoryProductSearchProvider(
                    $this->context->getTranslator(),
                    $category
                );
            } elseif (!empty($searchString)) {
                // Pagina di ricerca
                $query->setSearchString($searchString);
                $searchProvider = new SearchProductSearchProvider(
                    $this->context->getTranslator()
                );
            } else {
                // Default a tutti i prodotti
                // Utilizziamo il controller di categoria con ID 2 (Home)
                $category = new \Category(2, $this->context->language->id);
                $searchProvider = new CategoryProductSearchProvider(
                    $this->context->getTranslator(),
                    $category
                );
            }

            // Applica i filtri se disponibili
            if (!empty($filters)) {
                // Applica i filtri alla query
                $this->applyFiltersToQuery($query, $filters);

                // Utilizza anche l'hook per la compatibilità con altri moduli
                \Hook::exec('productSearchProvider', [
                    'query' => $query,
                    'filters' => $filters
                ]);
            }

            // Esegui la ricerca
            $result = $searchProvider->runQuery($searchContext, $query);

            // Ottieni i prodotti dal risultato
            $rawProducts = $result->getProducts();

            // Formatta i prodotti per la risposta
            $formattedProducts = [];

            // Utilizza un approccio più semplice per formattare i prodotti
            foreach ($rawProducts as $rawProduct) {
                // Estrai le informazioni essenziali dal prodotto
                $pricelistRow = $this->getPriceListRow($rawProduct['id_product']);
                if (!$pricelistRow) {
                    $pricelistRow = [];
                }

                $product = [
                    'id' => $rawProduct['id_product'],
                    'name' => $rawProduct['name'],
                    'description_short' => $rawProduct['description_short'],
                    'price' => $rawProduct['price'],
                    'price_formatted' => $this->context->getCurrentLocale()->formatPrice(
                        $rawProduct['price'],
                        \Currency::getIsoCodeById($this->context->currency->id)
                    ),
                    'reference' => isset($rawProduct['reference']) ? $rawProduct['reference'] : '',
                    'manufacturer' => isset($rawProduct['manufacturer_name']) ? $rawProduct['manufacturer_name'] : '',
                    'brand_name' => isset($rawProduct['manufacturer_name']) ? $rawProduct['manufacturer_name'] : '',
                    'brand_image' => _PS_IMG_ . 'm/' . $rawProduct['id_manufacturer'] . '.jpg',
                    'stock' => \StockAvailable::getQuantityAvailableByProduct($rawProduct['id_product']),
                    'date_shipping' => $pricelistRow['delivery_time'] ?? '',
                    'url' => $this->context->link->getProductLink($rawProduct['id_product']),
                    'add_to_cart_url' => $this->context->link->getPageLink('cart', null, null, [
                        'add' => 1,
                        'id_product' => $rawProduct['id_product'],
                        'token' => \Tools::getToken(false)
                    ])
                ];

                // Aggiungi l'immagine se disponibile
                if (isset($rawProduct['id_image']) && $rawProduct['id_image']) {
                    $product['image'] = $this->context->link->getImageLink(
                        $rawProduct['link_rewrite'],
                        $rawProduct['id_image'],
                        'home_default'
                    );
                } else {
                    $product['image'] = $this->context->link->getImageLink('', 'en-default', 'home_default');
                }

                $formattedProducts[] = $product;
            }

            // Prepara la risposta
            $response = [
                'products' => $formattedProducts,
                'pagination' => [
                    'total_items' => $result->getTotalProductsCount(),
                    'items_shown_from' => ($page - 1) * $limit + 1,
                    'items_shown_to' => min($page * $limit, $result->getTotalProductsCount()),
                    'current_page' => $page,
                    'pages_count' => ceil($result->getTotalProductsCount() / $limit),
                ]
            ];

            // Restituisci la risposta come JSON
            $this->json($response);

        } catch (\Exception $e) {
            // Gestisci eventuali errori
            $this->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function json($params)
    {
        header('Content-Type: application/json');
        exit(
            json_encode($params)
        );
    }

    /**
     * Get products based on filters and pagination
     */
    /**
     * Applica i filtri alla query di ricerca
     * 
     * @param ProductSearchQuery $query La query di ricerca
     * @param array $filters I filtri da applicare
     */
    protected function applyFiltersToQuery($query, $filters)
    {
        // Gestione dei filtri per categoria
        if (isset($filters['category'])) {
            $categoryIds = array_map('intval', $filters['category']);
            if (!empty($categoryIds)) {
                $query->setIdCategory($categoryIds[0]);
            }
        }

        // Gestione dei filtri per produttore/marca
        if (isset($filters['manufacturer'])) {
            $manufacturerIds = array_map('intval', $filters['manufacturer']);
            if (!empty($manufacturerIds)) {
                $query->setIdManufacturer($manufacturerIds[0]);
            }
        }

        // Gestione dei filtri per fornitore
        if (isset($filters['supplier'])) {
            $supplierIds = array_map('intval', $filters['supplier']);
            if (!empty($supplierIds)) {
                $query->setIdSupplier($supplierIds[0]);
            }
        }

        // Gestione dei filtri per prezzo
        if (isset($filters['price'])) {
            $priceRange = $filters['price'];
            if (count($priceRange) >= 2) {
                $minPrice = (float) $priceRange[0];
                $maxPrice = (float) $priceRange[1];

                // Imposta il filtro di prezzo utilizzando l'encoder di facetedsearch
                $encodedFacets = $query->getEncodedFacets();
                if ($encodedFacets) {
                    $encodedFacets .= '/price-' . $minPrice . '-' . $maxPrice;
                } else {
                    $encodedFacets = 'price-' . $minPrice . '-' . $maxPrice;
                }
                $query->setEncodedFacets($encodedFacets);
            }
        }

        // Gestione di altri filtri (attributi, caratteristiche, tag, ecc.)
        foreach ($filters as $filterType => $filterValues) {
            if (!in_array($filterType, ['category', 'manufacturer', 'supplier', 'price'])) {
                // Per gli altri tipi di filtri, aggiungiamo all'encodedFacets
                $encodedFacets = $query->getEncodedFacets();
                $filterValueString = implode('-', $filterValues);

                if ($encodedFacets) {
                    $encodedFacets .= '/' . $filterType . '-' . $filterValueString;
                } else {
                    $encodedFacets = $filterType . '-' . $filterValueString;
                }

                $query->setEncodedFacets($encodedFacets);
            }
        }
    }

    public function getPriceListRow($idT24)
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();
        $query
            ->select('*')
            ->from('product_tyre_pricelist')
            ->where('id_t24 = ' . (int) $idT24);
        $result = $db->getRow($query);
        if (!$result) {
            return false;
        }

        return $result;
    }
}
