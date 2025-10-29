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

use MpSoft\MpGridProducts\Adapter\PriceListAdapter;
use MpSoft\MpGridProducts\Helpers\getTwigEnvironment;
use MpSoft\MpGridProducts\Helpers\TwigHelper;
use MpSoft\MpGridProducts\Traits\HumanTimingTrait;

class MpGridProductsCronModuleFrontController extends ModuleFrontController
{
    use HumanTimingTrait;

    private $id_lang;

    /**
     * Process the Ajax request
     */
    public function initContent()
    {
        $this->id_lang = (int) $this->context->language->id;
        $ajax = (int) Tools::getValue('ajax');
        $action = Tools::getValue('action');
        $params = Tools::getAllValues();

        if ($ajax && !preg_match('/Action$/', $action)) {
            $action = Tools::toCamelCase($action) . 'Action';
        }

        if (method_exists($this, $action) && $ajax) {
            $result = $this->$action($params);
            header('Content-Type: application/json');
            http_response_code(200);
            exit(json_encode($result));
        }

        if (method_exists($this, $action) && !$ajax) {
            $result = $this->$action($params);
            return $result;
        }

        if ($ajax) {
            $this->ajaxRender(json_encode([
                'error' => true,
                'message' => 'This page cannot be accessed directly.'
            ]));
        }

        parent::initContent();
    }

    protected function response($data, $httpCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($httpCode);
        exit(json_encode($data));
    }

    public function addToCartAction($params)
    {
        $start = microtime(true);

        $cartController = $this->context->link->getPageLink('cart', Configuration::get('PS_SSL_ENABLED'), $this->id_lang, [
            'add' => 1,
            'id_product' => $params['id_product'] ?? 0,
            'id_product_attribute' => $params['id_product_attribute'] ?? 0,
            'qty' => $params['quantity'] ?? 0,
            'action' => 'update',
        ]);

        $stop = microtime(true);
        $time = $stop - $start;
        $showModalUrl = $this->context->link->getModuleLink('ps_shoppingcart', 'ajax', [
            'action' => 'add-to-cart',
            'id_product' => Tools::getValue('id_product'),
            'id_product_attribute' => Tools::getValue('id_product_attribute'),
            'qty' => Tools::getValue('qty'),
        ]);

        return [
            'time' => $this->getHumanTiming($time),
            'elapsed' => $time,
            'link' => $cartController,
            'showModalUrl' => $showModalUrl,
        ];
    }

    protected function searchProductsAction()
    {
        $measure = \Tools::getValue('measure');
        $tyreCategories = explode(',', \Configuration::get('MPORDERTYRE_CATEGORIES'));
        $homeCategoryId = $tyreCategories[0];

        $categoryUrl = $this->context->link->getCategoryLink($homeCategoryId, null, $this->id_lang);

        $cookie = $this->context->cookie;
        $cookie->__set('MPGRIDPRODUCT_MEASURE_SEARCH_PARAM', $measure);
        $cookie->write();

        Tools::redirect($categoryUrl);
    }

    protected function getProductsListAction()
    {
        // Get pagination parameters from bootstrap-tabl
        return $this->getProductsAction();
    }

    protected function getProductsAction()
    {
        // Get pagination parameters from bootstrap-table
        $offset = (int) Tools::getValue('offset', 0);
        $limit = (int) Tools::getValue('limit', 25);

        $search = json_decode(Tools::getValue('search', ''), true);

        $page = floor($offset / $limit) + 1;

        // Get category ID if available
        $categoryId = (int) Tools::getValue('idCategory', 0);

        // Get sort options from bootstrap-table
        $orderBy = Tools::getValue('sort', 'id_product');
        $orderWay = Tools::getValue('order', 'asc');

        // Validazione dei parametri di ordinamento
        $validOrderByValues = ['name', 'price', 'date_add', 'reference', 'id_product'];
        $validOrderWayValues = ['asc', 'desc'];

        if (!in_array($orderBy, $validOrderByValues)) {
            $orderBy = 'id_product';
        }

        if (!in_array(strtolower($orderWay), $validOrderWayValues)) {
            $orderWay = 'asc';
        }

        // Get search filters from toolbar
        $brandFilter = $search['brand'] ?? '';
        $referenceFilter = $search['reference'] ?? '';
        $ean13Filter = $search['ean13'] ?? '';
        $measureFilter = $search['measure'] ?? '';
        $priceMinFilter = $search['price_min'] ?? '';
        $priceMaxFilter = $search['price_max'] ?? '';
        $deliveryDateFilter = $search['delivery'] ?? '';

        if (!$measureFilter) {
            $cookie = $this->context->cookie;
            $measure = $cookie->__get('MPGRIDPRODUCT_MEASURE_SEARCH_PARAM');
            if ($measure) {
                $measureFilter = $measure;
            }
            $cookie->__unset('MPGRIDPRODUCT_MEASURE_SEARCH_PARAM');
            $cookie->write();
        }

        try {
            // Costruisci la query SQL diretta
            $sql = new \DbQuery();

            $sql->from('product', 'p');
            $sql->innerJoin('product_shop', 'product_shop', 'product_shop.id_product = p.id_product AND product_shop.id_shop = ' . (int) $this->context->shop->id);
            $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int) $this->id_lang . ' AND pl.id_shop = ' . (int) $this->context->shop->id);
            $sql->leftJoin('manufacturer', 'm', 'p.id_manufacturer = m.id_manufacturer');

            // Filtro per categoria
            if ($categoryId > 0 && (
                empty($brandFilter) &&
                empty($referenceFilter) &&
                empty($ean13Filter) &&
                empty($measureFilter) &&
                empty($priceMinFilter) &&
                empty($priceMaxFilter) &&
                empty($deliveryDateFilter)
            )) {
                $sql->innerJoin('category_product', 'cp', 'p.id_product = cp.id_product AND cp.id_category = ' . (int) $categoryId);
                $sql->where('p.id_category_default = ' . (int) $categoryId);
                $sql->where('cp.id_category = ' . (int) $categoryId);
            }

            // Filtri dalla toolbar
            $sql->where('product_shop.active = 1');

            if (!empty($brandFilter)) {
                $sql->where('p.id_manufacturer = ' . (int) $brandFilter);
            }

            if (!empty($referenceFilter)) {
                $sql->where('p.reference LIKE "%' . pSQL($referenceFilter) . '%"');
            }

            if (!empty($ean13Filter)) {
                $ean13Filter = trim(str_replace(' ', '', $ean13Filter));
                $sql->where('p.ean13 LIKE "%' . pSQL($ean13Filter) . '%"');
            }

            if (!empty($priceMinFilter)) {
                $sql->where('product_shop.price >= ' . (float) $priceMinFilter);
            }

            if (!empty($priceMaxFilter)) {
                $sql->where('product_shop.price <= ' . (float) $priceMaxFilter);
            }

            if (!empty($deliveryDateFilter)) {
                $sql->where('pl.delivery_in_stock <= "' . pSQL($deliveryDateFilter) . '"');
            }

            // Filtro per misura (feature)
            if (!empty($measureFilter)) {
                $sql->where('
                    LOWER(pl.name) LIKE "%' . pSQL($measureFilter) . '%" '
                    . 'OR LOWER(p.reference) LIKE "%' . pSQL($measureFilter) . '%"');
            }

            // Copio la DbQuery in una nuova Variabile
            $sqlCount = clone $sql;
            $sqlCount->select('COUNT(DISTINCT p.id_product) as total');
            $sqlCountStr = $sqlCount->build();
            $totalProducts = (int) \Db::getInstance()->getValue($sqlCountStr);

            // Inserisco i campi da selezionare alla query principale
            $sql->select('p.id_product, pl.name, pl.description_short, pl.link_rewrite');
            $sql->select('cl.name as category_name');
            $sql->select('p.reference, p.ean13, p.price, p.id_manufacturer, pl.delivery_in_stock');
            $sql->select('m.name as manufacturer_name');
            $sql->select('image_shop.id_image');
            $sql->select('product_shop.active, product_shop.price as price_tax_exc');
            $sql->leftJoin('image_shop', 'image_shop', 'image_shop.id_product = p.id_product AND image_shop.cover = 1 AND image_shop.id_shop = ' . (int) $this->context->shop->id);
            $sql->leftJoin('category_lang', 'cl', 'cl.id_category=p.id_category_default AND cl.id_lang=' . (int) $this->id_lang . ' AND cl.id_shop=' . (int) $this->context->shop->id);

            // Ordinamento
            $sql->orderBy('p.id_category_default DESC');
            $sql->orderBy('p.' . pSQL($orderBy) . ' ' . pSQL($orderWay));

            // Paginazione
            $sql->limit($limit, $offset);

            // Esegui la query
            $sqlString = $sql->build();
            $rawProducts = \Db::getInstance()->executeS($sqlString);

            if (!$rawProducts) {
                $rawProducts = [];
            }

            // Formatta i prodotti per la risposta
            $formattedProducts = [];

            foreach ($rawProducts as $rawProduct) {
                $product = new \Product($rawProduct['id_product'], false, $this->id_lang);

                // Estrai le informazioni essenziali dal prodotto
                $pricelistRow = [];  // $this->getPriceListRow($rawProduct['id_product']);
                if (!$pricelistRow) {
                    $pricelistRow = [];
                }

                if ($product->delivery_in_stock) {
                    $product->delivery_in_stock = date('Y-m-d', strtotime($product->delivery_in_stock . ' +1 day'));
                }

                // Crea un array con i nomi dei mesi in italiano
                $mesi_italiani = [
                    1 => 'Gen',
                    2 => 'Feb',
                    3 => 'Mar',
                    4 => 'Apr',
                    5 => 'Mag',
                    6 => 'Giu',
                    7 => 'Lug',
                    8 => 'Ago',
                    9 => 'Set',
                    10 => 'Ott',
                    11 => 'Nov',
                    12 => 'Dic'
                ];

                // Modifica la riga che imposta month_delivery
                $monthDelivery = $product->delivery_in_stock
                    ? $mesi_italiani[(int) date('n', strtotime((string) $product->delivery_in_stock))]
                    : 'mag';

                $dayDelivery = $product->delivery_in_stock
                    ? date('d', strtotime($product->delivery_in_stock))
                    : 'sud';

                // Veicolo
                // Inserisco l'icona del tipo di vettura
                $vehicles = [
                    'C1' => 'directions_car',
                    'C2' => 'airport_shuttle',
                    'C3' => 'local_shipping'
                ];
                $frontFeatures = $product->getFrontFeatures($this->id_lang);
                $iconToolbar = $this->createIconToolbar($frontFeatures);

                $pfu = $this->getPfu($product->id);
                $pfuName = $pfu['name'];
                if ($pfu['price']) {
                    $pfuPrice = Context::getContext()->getCurrentLocale()->formatPrice((float) $pfu['price'], Currency::getIsoCodeById($this->context->currency->id));
                } else {
                    $pfuPrice = '';
                }

                $product = [
                    'id' => $rawProduct['id_product'],
                    'name' => $rawProduct['name'],
                    'description_short' => $rawProduct['description_short'],
                    'price' => (float) $rawProduct['price'],
                    'price_formatted' => $this->context->getCurrentLocale()->formatPrice(
                        (float) $rawProduct['price'],
                        Currency::getIsoCodeById($this->context->currency->id)
                    ),
                    'price_tax_exc' => (float) $rawProduct['price_tax_exc'],
                    'price_tax_exc_formatted' => $this->context->getCurrentLocale()->formatPrice(
                        (float) $rawProduct['price_tax_exc'],
                        Currency::getIsoCodeById($this->context->currency->id)
                    ),
                    'reference' => isset($rawProduct['reference']) ? $rawProduct['reference'] : '',
                    'manufacturer' => isset($rawProduct['manufacturer_name']) ? $rawProduct['manufacturer_name'] : '',
                    'brand_name' => isset($rawProduct['manufacturer_name']) ? $rawProduct['manufacturer_name'] : '',
                    'brand_image' => _PS_IMG_ . 'm/' . $rawProduct['id_manufacturer'] . '.jpg',
                    'stock' => StockAvailable::getQuantityAvailableByProduct($rawProduct['id_product']),
                    'date_shipping' => $pricelistRow['delivery_time'] ?? '',
                    'url' => $this->context->link->getProductLink($rawProduct['id_product']),
                    'add_to_cart_url' => $this->context->link->getPageLink('cart', null, null, [
                        'add' => 1,
                        'id_product' => $rawProduct['id_product'],
                        'token' => Tools::getToken(false)
                    ]),
                    'date_delivery' => date('d/m', strtotime($product->delivery_in_stock ?: 'now')),
                    'day_delivery' => $dayDelivery,
                    'month_delivery' => $monthDelivery,
                    'pfu_name' => $pfuName,
                    'pfu_price' => $pfuPrice,
                    'icon_toolbar' => $iconToolbar,
                    'ean13' => $product->ean13,
                    'category_name' => $rawProduct['category_name'],
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

            // Prepara la risposta per bootstrap-table server-side
            $response = [
                'success' => true,
                'total' => $totalProducts,
                'rows' => $formattedProducts,
                'measure' => $measureFilter,
            ];

            // Restituisci la risposta come JSON
            $this->response($response, 200);
        } catch (Exception $e) {
            $this->response([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function createIconToolbar($features)
    {
        $icons = [];
        foreach ($features as $feature) {
            $value = strtolower($feature['value']);
            switch (strtolower($feature['name'])) {
                case 'grandezza':
                    break;
                case 'livello rumore':
                    $icons[] = "
                        <div class=\"icon-box gap-4px\" title=\"Livello rumore: {$value}\">
                            <span class=\"fa fa-volume-up\"></span>
                            <span>$value</span>
                        </div>
                    ";
                    break;
                case 'classe veicolo':
                    switch ($value) {
                        case 'c1':
                            $icons[] = '
                                <div class="icon-box" title="Classe Veicolo: C1">
                                    <span class="fa fa-car"></span>
                                </div>
                            ';
                            break;
                        case 'c2':
                            $icons[] = '
                                <div class="icon-box" title="Classe Veicolo: C2">
                                    <span class="fa fa-bus"></span>
                                </div>
                            ';
                            break;
                        case 'c3';
                            $icons[] = '
                                <div class="icon-box" title="Classe Veicolo: C3">
                                    <span class="fa fa-truck"></span>
                                </div>
                            ';
                            break;
                    }
                    break;
                case 'uso':
                    switch ($value) {
                        case 'pneumatici 4 stagioni':
                            $icons[] = '
                                <div class="icon-box gap-4px" title="Uso: Pneumatici 4 stagioni">
                                    <span class="icon-tyre-sun"></span>
                                    <span class="icon-tyre-snow"></span>
                                </div>
                            ';
                            break;
                        case 'estivi':
                            $icons[] = '
                                <div class="icon-box gap-4px" title="Uso: Estivi">
                                    <span class="icon-tyre-sun"></span>
                                </div>
                            ';
                            break;
                        case 'invernali':
                            $icons[] = '
                                <div class="icon-box gap-4px" title="Uso: Invernali">
                                    <span class="icon-tyre-snow"></span>
                                </div>
                            ';
                            break;
                    }
                    break;
                case 'm+s':
                    break;
            }
        }

        if ($icons) {
            $icons = implode('', $icons);
            return "
                <div class=\"d-flex justify-content-start align-items-center gap-2\">
                    {$icons}
                </div>
            ";
        }

        return '';
    }

    public function getPfu($id_product)
    {
        $id_lang = (int) Context::getContext()->language->id;
        $db = \Db::getInstance();
        $query = new DbQuery();
        $query
            ->select('p.id_product, pl.name, p.price')
            ->from('product_pfu', 'pfu')
            ->innerJoin('product', 'p', 'p.id_product=pfu.id_pfu')
            ->innerJoin('product_lang', 'pl', "pl.id_product=pfu.id_pfu and pl.id_lang = {$id_lang}")
            ->where("pfu.id_product = {$id_product}");

        $result = $db->getRow($query);
        if (!$result) {
            return [
                'id_product' => '',
                'name' => '',
                'price' => '',
            ];
        }

        return $result;
    }

    public function getDateShipping($idT24)
    {
        $db = \Db::getInstance();
        $query = new DbQuery();
        $query
            ->select('delivery_time')
            ->from('product_tyre_pricelist')
            ->where('id_t24 = ' . (int) $idT24);
        $result = $db->getValue($query);
        if (!$result) {
            return '';
        }

        return $result;
    }

    public function getPriceListRow($idT24)
    {
        $db = \Db::getInstance();
        $query = new DbQuery();
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

    private function fetchProductInfoAction($params)
    {
        $id_lang = (int) $this->id_lang;
        $id_product = (int) ($params['id_product'] ?? 0);
        $product = new Product($id_product, false, $id_lang);
        $frontFeatures = $product->getFrontFeatures($id_lang);
        $cover = Product::getCover($id_product);
        $image = new Image($cover['id_image'] ?? 0, $id_lang);
        $imagePath = $image->getImgPath();
        $imageUrl = _PS_IMG_ . 'p/' . $imagePath . '-home_default.jpg';
        $priceList = (new PriceListAdapter())->getPriceLists($product->id);

        $features = $this->formatFeatures($frontFeatures);

        $params = [
            'name' => $product->name,
            'reference' => $product->reference,
            'price' => $product->price,
            'ean13' => $product->ean13,
            'image_url' => $imageUrl,
            'features' => $features,
            'pricesList' => $priceList,
        ];

        $tplPath = $this->module->getLocalPath() . 'views/templates/front/product_info.tpl';
        $tpl = $this->context->smarty->createTemplate($tplPath);
        $tpl->assign($params);
        $productInfo = $tpl->fetch();

        return ['page' => $productInfo];
    }

    public function getProductDetailsAction()
    {
        $id_lang = (int) $this->id_lang;
        $id_product = (int) (\Tools::getValue('idProduct', 0));
        $product = new Product($id_product, false, $id_lang);
        $frontFeatures = $product->getFrontFeatures($id_lang);
        $cover = Product::getCover($id_product);
        $image = new Image($cover['id_image'] ?? 0, $id_lang);
        $imagePath = $image->getImgPath();
        $imageUrl = _PS_IMG_ . 'p/' . $imagePath . '-home_default.jpg';
        $priceList = (new PriceListAdapter())->getPriceLists($product->id);

        $features = $this->formatFeatures($frontFeatures);

        $params = [
            'name' => $product->name,
            'reference' => $product->reference,
            'price' => $product->price,
            'ean13' => $product->ean13,
            'image_url' => $imageUrl,
            'features' => $features,
            'pricesList' => $priceList,
        ];

        $twig = new GetTwigEnvironment($this->module->name);
        $productInfo = $twig->renderTemplate('@ModuleTwig/product-info.html.twig', $params);

        return ['page' => $productInfo];
    }

    private function formatFeatures($features)
    {
        foreach ($features as &$feature) {
            if ($feature['name'] == 'M+S') {
                $value = $feature['value'] == 1 ? 'check' : 'cancel';
                $feature['value'] = "
                    <span class=\"material-icons\">
                        {$value}
                    </span>
                ";
            }

            if (preg_match('/livello rumore/i', $feature['name'])) {
                $icon_decibel = $this->context->link->getBaseLink() . 'modules/mpgridproducts/views/img/tyre_noise.png';
                $value = "
                    <span class=\"decibel\" style=\"width: 64px; display: flex; justify-content: start; gap: 1rem;\">
                        <img src=\"{$icon_decibel}\" alt=\"Livello rumore\" style=\"width: 32px; object-fit: contain;\">
                        <strong>{$feature['value']}</strong>
                    </span>
                ";
                $feature['value'] = $value;
            }
        }

        return $features;
    }

    /**
     * Recupera il listino prezzi da Tyres24 per un codice prodotto specifico
     *
     * @param string $productCode Codice del prodotto
     * @return array Dati del listino prezzi o array con errore
     */
    private function getTyres24PriceListAction($params)
    {
        $productCode = (int) ($params['product_code'] ?? 0);
        try {
            // Verifica che il codice prodotto sia valido
            if (empty($productCode)) {
                return [
                    'error' => true,
                    'message' => 'Il codice prodotto è obbligatorio'
                ];
            }

            // Carica l'adapter per il listino prezzi
            require_once _PS_MODULE_DIR_ . 'mpgridproducts/src/Adapter/PriceListAdapter.php';
            $priceListAdapter = new \MpSoft\MpGridProducts\Adapter\PriceListAdapter();

            // Recupera il listino prezzi
            $priceListData = $priceListAdapter->getPriceList($productCode);

            // Aggiungi informazioni aggiuntive se necessario
            $result = [
                'error' => false,
                'product_code' => $productCode,
                'price_list' => $priceListData,
                'timestamp' => time()
            ];

            // Se disponibile, recupera anche i dettagli del prodotto
            try {
                $productDetails = $priceListAdapter->getProductDetails($productCode);
                $result['product_details'] = $productDetails;
            } catch (\Exception $e) {
                // Ignora l'errore sui dettagli del prodotto, non è critico
                $result['product_details_error'] = $e->getMessage();
            }

            // Se disponibile, recupera anche la disponibilità
            try {
                $availability = $priceListAdapter->checkAvailability($productCode);
                $result['availability'] = $availability;
            } catch (\Exception $e) {
                // Ignora l'errore sulla disponibilità, non è critico
                $result['availability_error'] = $e->getMessage();
            }

            return $result;
        } catch (\Exception $e) {
            // Gestione degli errori
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'product_code' => $productCode
            ];
        }
    }
}
