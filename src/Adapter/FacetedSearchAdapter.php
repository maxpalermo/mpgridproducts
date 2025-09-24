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

namespace MpSoft\MpGridProducts\Adapter;

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class FacetedSearchAdapter
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->context = \Context::getContext();
    }

    /**
     * Apply faceted search filters to the product search query
     *
     * @param ProductSearchQuery $query
     * @param array $filters
     * @return ProductSearchQuery
     */
    public function applyFilters(ProductSearchQuery $query, array $filters)
    {
        // Apply filters from faceted search
        if (!empty($filters)) {
            // Check if ps_facetedsearch module is installed and active
            if (\Module::isInstalled('ps_facetedsearch') && \Module::isEnabled('ps_facetedsearch')) {
                $facetedSearch = \Module::getInstanceByName('ps_facetedsearch');
                
                if (method_exists($facetedSearch, 'getSelectedFilters')) {
                    // Get selected filters
                    $selectedFilters = $facetedSearch->getSelectedFilters();
                    
                    // Apply filters to query
                    if (!empty($selectedFilters)) {
                        // Here we would apply the filters to the query
                        // This is a simplified implementation
                        // In a real scenario, you would need to adapt this to your specific needs
                        
                        // Example: Apply category filter
                        if (isset($selectedFilters['category'])) {
                            $query->setIdCategory($selectedFilters['category']);
                        }
                        
                        // Example: Apply manufacturer filter
                        if (isset($selectedFilters['manufacturer'])) {
                            $query->setIdManufacturer($selectedFilters['manufacturer']);
                        }
                        
                        // Example: Apply price range filter
                        if (isset($selectedFilters['price'])) {
                            $query->setPriceRanges($selectedFilters['price']);
                        }
                    }
                }
            }
        }
        
        return $query;
    }

    /**
     * Get products based on search criteria and filters
     *
     * @param int $page
     * @param int $limit
     * @param string $orderBy
     * @param string $orderWay
     * @param array $filters
     * @param int $categoryId
     * @param string $searchString
     * @return array
     */
    public function getProducts($page, $limit, $orderBy, $orderWay, $filters = [], $categoryId = 0, $searchString = '')
    {
        // Create search context
        $searchContext = new ProductSearchContext($this->context);
        
        // Create search query
        $query = new ProductSearchQuery();
        $query->setResultsPerPage($limit);
        $query->setPage($page);
        
        // Set sort order
        $query->setSortOrder(new SortOrder($orderBy, $orderWay));
        
        // Apply filters
        $query = $this->applyFilters($query, $filters);
        
        // Set category if provided
        if ($categoryId > 0) {
            $query->setIdCategory($categoryId);
        }
        
        // Set search query if provided
        if (!empty($searchString)) {
            $query->setSearchString($searchString);
        }
        
        // Get search provider based on context
        $searchProvider = null;
        
        if ($categoryId > 0) {
            // Category page
            // Ottieni l'oggetto Category dal categoryId
            $category = new \Category($categoryId, $this->context->language->id);
            $searchProvider = new \PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider(
                $this->context->getTranslator(),
                $category
            );
        } elseif (!empty($searchString)) {
            // Search page
            $searchProvider = new \PrestaShop\PrestaShop\Adapter\Search\SearchProductSearchProvider(
                $this->context->getTranslator()
            );
        } else {
            // Default to all products
            $searchProvider = new \PrestaShop\PrestaShop\Adapter\ProductSearchProvider\ProductSearchProvider();
        }
        
        // Execute search
        $result = $searchProvider->runQuery($searchContext, $query);
        
        // Format products
        return $this->formatProducts($result);
    }

    /**
     * Format products for the grid
     *
     * @param ProductSearchResult $searchResult
     * @return array
     */
    private function formatProducts(ProductSearchResult $searchResult)
    {
        $products = $searchResult->getProducts();
        $formattedProducts = [];
        
        foreach ($products as $product) {
            $formattedProducts[] = [
                'id' => $product['id_product'],
                'name' => $product['name'],
                'price' => $product['price'],
                'price_formatted' => $product['price_amount'],
                'image' => isset($product['cover']['bySize']['home_default']['url']) ? 
                    $product['cover']['bySize']['home_default']['url'] : '',
                'description_short' => $product['description_short'],
                'reference' => $product['reference'],
                'manufacturer' => $product['manufacturer_name'],
                'url' => $product['url'],
                'add_to_cart_url' => $this->context->link->getPageLink('cart', null, null, [
                    'add' => 1,
                    'id_product' => $product['id_product'],
                    'token' => \Tools::getToken(false)
                ])
            ];
        }
        
        return [
            'products' => $formattedProducts,
            'pagination' => [
                'total_items' => $searchResult->getTotalProductsCount(),
                'items_shown_from' => ($searchResult->getPage() - 1) * $searchResult->getResultsPerPage() + 1,
                'items_shown_to' => min($searchResult->getPage() * $searchResult->getResultsPerPage(), $searchResult->getTotalProductsCount()),
                'current_page' => $searchResult->getPage(),
                'pages_count' => ceil($searchResult->getTotalProductsCount() / $searchResult->getResultsPerPage()),
            ]
        ];
    }
}
