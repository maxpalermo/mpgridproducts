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

use Configuration;
use Exception;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingLazyArray;
use PrestaShopLogger;

/**
 * Adapter per la chiamata al listino prezzi di Tyres24 via curl
 */
class PriceListAdapter
{
    /**
     * URL base dell'API di Tyres24
     * 
     * @var string
     */
    private $apiBaseUrl;

    /**
     * Chiave API per l'autenticazione
     * 
     * @var string
     */
    private $apiKey;

    /**
     * Timeout per le chiamate curl in secondi
     * 
     * @var int
     */
    private $timeout;

    /** @var  \Symfony\Component\DependencyInjection\ContainerInterface */
    private $container;

    /** @var  \Doctrine\DBAL\Connection */
    private $connection;

    /** @var array */
    private $productList;


    /**
     * Costruttore
     */
    public function __construct($productList = null)
    {
        $this->productList = $productList;
        $this->apiBaseUrl = Configuration::get('MPGRIDPRODUCTS_TYRES24_API_URL') ?: 'https://api.tyres24.com/v1';
        $this->apiKey = Configuration::get('MPGRIDPRODUCTS_TYRES24_API_KEY') ?: '';
        $this->timeout = (int) Configuration::get('MPGRIDPRODUCTS_TYRES24_API_TIMEOUT', 30);
    }

    public function getPriceLists($productCode)
    {
        $distributorsList = $this->getDistributorsList($productCode);
        $priceLists = $this->getPriceListsByDistributorsList($distributorsList);

        return $priceLists;
    }

    /**
     * Recupera l'elenco dei distributori per un prodotto specifico tramite il suo codice
     * 
     * @param string $productCode Codice del prodotto
     * @return array Dati dei distributori
     * @throws Exception Se si verifica un errore durante la chiamata API
     */
    public function getDistributorsList($productCode)
    {
        if (empty($productCode)) {
            throw new Exception('Il codice prodotto è obbligatorio');
        }

        // Prepara l'URL dell'endpoint
        $endpoint = '/distributorList';
        $url = $this->apiBaseUrl . $endpoint;

        // Prepara i parametri della richiesta
        $params = [
            'itemID' => $productCode,
        ];

        try {
            // Esegui la chiamata curl
            $response = $this->executeCurlRequest($url, $params, $this->apiKey);

            // Analizza la risposta
            return $this->parseResponse($response);
        } catch (Exception $e) {
            // Log dell'errore
            PrestaShopLogger::addLog(
                'Errore nella chiamata al listino prezzi Tyres24: ' . $e->getMessage(),
                2, // Livello di errore
                null,
                'PriceListAdapter',
                null,
                true
            );

            // Rilancia l'eccezione
            throw $e;
        }
    }

    public function getPriceListsByDistributorsList($distributorsList)
    {
        $priceLists = [];
        foreach ($distributorsList as $key => &$distributor) {
            $distributor = $this->filter($distributor);
            if (!$distributor) {
                unset($distributorsList[$key]);
                continue;
            }
        }

        $distributors = $this->sort($distributorsList);

        foreach ($distributors as $distributor) {
            $distributorId = $distributor['distributorId'];
            $priceLists[$distributorId] = [
                'id' => $distributorId,
                'name' => $distributor['name'],
                'country' => $distributor['country'],
                'countryCode' => $distributor['countryCode'],
                'stock' => $distributor['stock'],
                'estimatedDelivery' => $distributor['shippingCosts']['shipping_standard']['estimatedDelivery'],
                'priceList' => $distributor['priceList'],
            ];
        }

        return $priceLists;
    }

    /**
     * Filtra il listino prezzi del fornitore caricando il prezzo di vendita
     * ed prendendo solo i listini ek.
     * @param mixed $distributor
     * @return array Se il distributore non ha listini validi restituisce un array vuoto
     */
    public function filter($distributor)
    {
        $priceLoad = Configuration::get('MPGRIDPRODUCTS_TYRES24_PRICE_LOAD') ?: 1;

        //Controllo se è un distributore italiano
        if (strtoupper($distributor['countryCode']) == 'IT') {
            return [];
        }

        $priceLists = $distributor['priceList'];
        $filteredPriceLists = [];
        $minOrderPrices = [];

        // Filtro i prezzi e applico il caricamento
        foreach ($priceLists as $key => $priceList) {
            if (preg_match('/^ek/', $priceList['type'])) {
                $priceList['value'] *= $priceLoad;
                $minOrder = isset($priceList['min_order']) ? (int) $priceList['min_order'] : 1;

                // Salvo il prezzo più basso per ogni min_order
                if (!isset($minOrderPrices[$minOrder]) || $priceList['value'] < $minOrderPrices[$minOrder]['value']) {
                    $minOrderPrices[$minOrder] = $priceList;
                }

                $filteredPriceLists[] = $priceList;
            }
        }

        if (empty($filteredPriceLists)) {
            return [];
        }

        // Converto l'array associativo in array numerico
        $bestPrices = [];
        foreach ($minOrderPrices as $minOrder => $price) {
            $bestPrices[] = $price;
        }

        // Ordino per min_order crescente
        usort($bestPrices, function ($a, $b) {
            $minOrderA = isset($a['min_order']) ? (int) $a['min_order'] : 1;
            $minOrderB = isset($b['min_order']) ? (int) $b['min_order'] : 1;
            return $minOrderA - $minOrderB;
        });

        // Sostituisco il listino prezzi originale con quello filtrato e ordinato
        $distributor['priceList'] = $bestPrices;


        return $distributor;
    }

    /**
     * Ordina i distributori per il prezzo di vendita
     * @param mixed $distributors
     * @return array
     */
    public function sort($distributors)
    {
        //Ordino dal fornitore più conveniente
        usort($distributors, function ($a, $b) {
            return $a['priceList'][0]['value'] - $b['priceList'][0]['value'];
        });

        return $distributors;
    }

    public function updateProductValues(ProductListingLazyArray $product)
    {
        $productId = (int) $product->getId();
        $range = Configuration::get('MPGRIDPRODUCTS_TYRES24_CACHE_TIME') ?: 60;
        $pfx = _DB_PREFIX_;
        $db = \Db::getInstance();
        $query = "
            SELECT *
            FROM {$pfx}product_tyre_pricelist
            WHERE
                id_t24 = {$productId}
                AND
                date_upd > DATE_SUB(NOW(), INTERVAL {$range} MINUTE)
        ";

        $result = $db->getRow($query);

        if ($result) {
            $product->__set('price', $result['min_order_1']);
            $product->__set('stock', $result['stock']);
            $product->__set('date_shipping', $result['delivery_time']);
        } else {
            $cheapestDistributor = $this->getCheapestDistributor($productId);
            $result = $this->updateTablePricelist($productId, $cheapestDistributor);
            if ($result !== true) {
                PrestaShopLogger::addLog(
                    'Errore nella chiamata al listino prezzi Tyres24: ' . $result,
                    2, // Livello di errore
                    null,
                    'PriceListAdapter',
                    null,
                    true
                );
            }
            $product->__set('price', $cheapestDistributor['priceList'][0]['value']);
            $product->__set('stock', $cheapestDistributor['stock']);
            $product->__set('date_shipping', $cheapestDistributor['estimatedDelivery']);
        }

        $brandName = $product->__get('manufacturer_name');
        $id_brand = \Manufacturer::getIdByName($brandName);
        if (!$id_brand) {
            $product->__set('brand_image', '');
            $product->__set('brand_name', '');
        } else {
            $img_url = _PS_IMG_DIR_ . 'm/' . $id_brand . '.jpg';
            $product->__set('brand_image', $img_url);
            $product->__set('brand_name', $brandName);
        }



        return $product;
    }


    /**
     * Aggiorna la tabella product_tyre_pricelist con i dati del fornitore più conveniente
     * @param mixed $cheapestDistributor
     * @return true|string
     */
    public function updateTablePricelist($id_t24, $cheapestDistributor)
    {
        $pfx = _DB_PREFIX_;
        $db = \Db::getInstance();

        // Inizializza i valori dei listini
        $min_order_1 = 0;
        $min_order_2 = 0;
        $min_order_4 = 0;

        // Cerca nei listini disponibili quelli con min_order specifici
        if (isset($cheapestDistributor['priceList']) && is_array($cheapestDistributor['priceList'])) {
            foreach ($cheapestDistributor['priceList'] as $priceItem) {
                if (isset($priceItem['min_order']) && isset($priceItem['value'])) {
                    // Assegna il valore in base al minOrder
                    switch ($priceItem['min_order']) {
                        case 1:
                            $min_order_1 = (float) $priceItem['value'];
                            // Usa il prezzo per min_order = 1 come prezzo unitario principale
                            $price_unit = $min_order_1;
                            break;
                        case 2:
                            $min_order_2 = (float) $priceItem['value'];
                            break;
                        case 4:
                            $min_order_4 = (float) $priceItem['value'];
                            break;
                    }
                }
            }
        }

        // Se non abbiamo trovato un prezzo per min_order = 1, usa il primo disponibile
        if ($min_order_1 == 0 && isset($cheapestDistributor['priceList'][0]['value'])) {
            $min_order_1 = (float) $cheapestDistributor['priceList'][0]['value'];
        }

        $data = [
            'id_t24' => (int) $id_t24,
            'id_distributor' => (int) $cheapestDistributor['id'],
            'name' => pSQL($cheapestDistributor['name']),
            'country' => pSQL($cheapestDistributor['country']),
            'country_code' => pSQL($cheapestDistributor['countryCode']),
            'id_pricelist' => (int) $cheapestDistributor['priceList'][0]['id'],
            'min_order_1' => $min_order_1,
            'min_order_2' => $min_order_2,
            'min_order_4' => $min_order_4,
            'delivery_time' => $cheapestDistributor['estimatedDelivery'],
            'stock' => (int) $cheapestDistributor['stock'],
            'date_upd' => date('Y-m-d H:i:s')
        ];

        $query = "
            REPLACE INTO {$pfx}product_tyre_pricelist
            (
                id_t24,
                id_distributor,
                name,
                country,
                country_code,
                id_pricelist,
                min_order_1,
                min_order_2,
                min_order_4,
                stock,
                delivery_time,
                active,
                date_add,
                date_upd
            )
            VALUES
            (
                {$data['id_t24']},
                {$data['id_distributor']},
                '{$data['name']}',
                '{$data['country']}',
                '{$data['country_code']}',
                {$data['id_pricelist']},
                {$data['min_order_1']},
                {$data['min_order_2']},
                {$data['min_order_4']},
                {$data['stock']},
                NOW(),
                1,
                NOW(),
                NOW()
            )
        ";

        try {
            $db->execute($query);
            // Utilizziamo min_order_1 come prezzo unitario per aggiornare il prodotto
            $this->updateProductPriceAndStock($id_t24, $data['min_order_1'], $data['stock']);
            return true;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function updateProductPriceAndStock($productId, $price, $stock)
    {
        $product = new \Product($productId);
        $product->price = $price;
        $product->stock_quantity = $stock;
        $product->update();

        \StockAvailable::setQuantity($productId, 0, $stock);
    }

    /**
     * Ottiene il fornitore più conveniente per un prodotto
     * 
     * @param int $productId ID del prodotto
     * @return array|false Dati del fornitore più conveniente o false se non trovato
     */
    public function getCheapestDistributor($productId)
    {
        $distributorsList = $this->getDistributorsList($productId);
        $priceLists = $this->getPriceListsByDistributorsList($distributorsList);

        if (empty($priceLists)) {
            return false;
        }

        $cheapestDistributor = reset($priceLists);

        return $cheapestDistributor;
    }


    /**
     * Esegue una richiesta curl all'API
     * 
     * @param string $url URL dell'endpoint
     * @param array $params Parametri della richiesta
     * @return string Risposta dell'API
     * @throws Exception Se si verifica un errore durante la chiamata curl
     */
    private function executeCurlRequest($url, $params = [], $token = null)
    {

        return '';

        // Inizializza la sessione curl
        $ch = curl_init();

        // Costruisci l'URL con i parametri GET
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        // Imposta le opzioni curl
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disabilita la verifica SSL (in produzione sarebbe meglio abilitarla)
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
        ]);

        if (!empty($token)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-AUTH-TOKEN: ' . $token,
            ]);
        }

        // Esegui la richiesta
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        // Chiudi la sessione curl
        curl_close($ch);

        // Gestisci gli errori
        if ($errno !== 0) {
            throw new Exception('Errore curl: ' . $error . ' (codice: ' . $errno . ')');
        }

        if ($httpCode >= 400) {
            throw new Exception('Errore API HTTP ' . $httpCode . ': ' . $response);
        }

        return $response;
    }

    /**
     * Esegue una richiesta POST curl all'API
     * 
     * @param string $url URL dell'endpoint
     * @param array $data Dati da inviare nel corpo della richiesta
     * @return string Risposta dell'API
     * @throws Exception Se si verifica un errore durante la chiamata curl
     */
    private function executeCurlPostRequest($url, $data = [])
    {
        // Inizializza la sessione curl
        $ch = curl_init();

        // Imposta le opzioni curl
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disabilita la verifica SSL (in produzione sarebbe meglio abilitarla)
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
        ]);

        // Esegui la richiesta
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        // Chiudi la sessione curl
        curl_close($ch);

        // Gestisci gli errori
        if ($errno !== 0) {
            throw new Exception('Errore curl: ' . $error . ' (codice: ' . $errno . ')');
        }

        if ($httpCode >= 400) {
            throw new Exception('Errore API HTTP ' . $httpCode . ': ' . $response);
        }

        return $response;
    }

    /**
     * Analizza la risposta JSON dell'API
     * 
     * @param string $response Risposta JSON
     * @return array Dati analizzati
     * @throws Exception Se si verifica un errore durante l'analisi
     */
    private function parseResponse($response)
    {
        return [];
        // Decodifica la risposta JSON
        $data = json_decode($response, true);

        // Verifica se la decodifica è riuscita
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Errore nella decodifica della risposta JSON: ' . json_last_error_msg());
        }

        // Verifica se la risposta contiene un errore
        if (isset($data['error']) && $data['error'] === true) {
            $errorMessage = isset($data['message']) ? $data['message'] : 'Errore sconosciuto';
            throw new Exception('Errore API: ' . $errorMessage);
        }

        return $data;
    }

    /**
     * Recupera i dettagli di un prodotto specifico tramite il suo codice
     * 
     * @param string $productCode Codice del prodotto
     * @return array Dettagli del prodotto
     * @throws Exception Se si verifica un errore durante la chiamata API
     */
    public function getProductDetails($productCode)
    {
        if (empty($productCode)) {
            throw new Exception('Il codice prodotto è obbligatorio');
        }

        // Prepara l'URL dell'endpoint
        $endpoint = '/products/' . urlencode($productCode);
        $url = $this->apiBaseUrl . $endpoint;

        // Prepara i parametri della richiesta
        $params = [];

        // Aggiungi la chiave API solo se è stata configurata
        if (!empty($this->apiKey)) {
            $params['api_key'] = $this->apiKey;
        }

        try {
            // Esegui la chiamata curl
            $response = $this->executeCurlRequest($url, $params);

            // Analizza la risposta
            return $this->parseResponse($response);
        } catch (Exception $e) {
            // Log dell'errore
            PrestaShopLogger::addLog(
                'Errore nel recupero dei dettagli del prodotto Tyres24: ' . $e->getMessage(),
                2, // Livello di errore
                null,
                'PriceListAdapter',
                null,
                true
            );

            // Rilancia l'eccezione
            throw $e;
        }
    }

    /**
     * Verifica la disponibilità di un prodotto
     * 
     * @param string $productCode Codice del prodotto
     * @return array Dati sulla disponibilità
     * @throws Exception Se si verifica un errore durante la chiamata API
     */
    public function checkAvailability($productCode)
    {
        if (empty($productCode)) {
            throw new Exception('Il codice prodotto è obbligatorio');
        }

        // Prepara l'URL dell'endpoint
        $endpoint = '/availability';
        $url = $this->apiBaseUrl . $endpoint;

        // Prepara i parametri della richiesta
        $params = [
            'product_code' => $productCode,
        ];

        // Aggiungi la chiave API solo se è stata configurata
        if (!empty($this->apiKey)) {
            $params['api_key'] = $this->apiKey;
        }

        try {
            // Esegui la chiamata curl
            $response = $this->executeCurlRequest($url, $params);

            // Analizza la risposta
            return $this->parseResponse($response);
        } catch (Exception $e) {
            // Log dell'errore
            PrestaShopLogger::addLog(
                'Errore nel controllo della disponibilità Tyres24: ' . $e->getMessage(),
                2, // Livello di errore
                null,
                'PriceListAdapter',
                null,
                true
            );

            // Rilancia l'eccezione
            throw $e;
        }
    }

    /**
     * Effettua un ordine per un prodotto
     * 
     * @param string $productCode Codice del prodotto
     * @param int $quantity Quantità da ordinare
     * @param array $orderDetails Dettagli aggiuntivi dell'ordine
     * @return array Dati di conferma dell'ordine
     * @throws Exception Se si verifica un errore durante la chiamata API
     */
    public function placeOrder($productCode, $quantity, $orderDetails = [])
    {
        if (empty($productCode)) {
            throw new Exception('Il codice prodotto è obbligatorio');
        }

        if ($quantity <= 0) {
            throw new Exception('La quantità deve essere maggiore di zero');
        }

        // Prepara l'URL dell'endpoint
        $endpoint = '/orders';
        $url = $this->apiBaseUrl . $endpoint;

        // Prepara i dati della richiesta
        $data = array_merge([
            'product_code' => $productCode,
            'quantity' => $quantity,
        ], $orderDetails);

        // Aggiungi la chiave API solo se è stata configurata
        if (!empty($this->apiKey)) {
            $data['api_key'] = $this->apiKey;
        }

        try {
            // Esegui la chiamata curl POST
            $response = $this->executeCurlPostRequest($url, $data);

            // Analizza la risposta
            return $this->parseResponse($response);
        } catch (Exception $e) {
            // Log dell'errore
            PrestaShopLogger::addLog(
                'Errore nell\'effettuare l\'ordine Tyres24: ' . $e->getMessage(),
                2, // Livello di errore
                null,
                'PriceListAdapter',
                null,
                true
            );

            // Rilancia l'eccezione
            throw $e;
        }

    }
    /**
     * Ottiene o aggiorna i dati di prezzo e stock per un prodotto
     * 
     * @param \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingLazyArray $product
     * @return \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingLazyArray
     */
    public function getPrice($product)
    {
        // Ottieni l'ID del prodotto
        $productId = $product->getId();

        // Ottieni il tempo di cache configurato (in minuti)
        $cacheTime = (int) Configuration::get('MPGRIDPRODUCTS_TYRES24_CACHE_TIME', 60);

        try {
            // Verifica se esiste già un record nella tabella per questo prodotto
            $connection = \Db::getInstance();
            $query = 'SELECT * FROM `' . _DB_PREFIX_ . 'product_tyre_pricelist` '
                . 'WHERE `id_t24` = ' . (int) $productId . ' '
                . 'ORDER BY `price_unit` ASC';

            $results = $connection->executeS($query);

            // Se abbiamo risultati e non è necessario aggiornare
            if (!empty($results)) {
                $lastUpdate = new \DateTime($results[0]['date_upd'] ?: $results[0]['date_add']);
                $now = new \DateTime();
                $diff = $now->diff($lastUpdate);
                $minutesPassed = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;

                // Se i dati sono ancora validi (non è passato il tempo di cache)
                if ($minutesPassed < $cacheTime) {
                    // Usa i dati dal database
                    $product->type = $this->getVehicleTypeFromProductCode($productId);
                    $product->price_unit = (float) $results[0]['price_unit'];
                    $product->stock = (int) $results[0]['stock'];
                    return $product;
                }

                // Altrimenti, aggiorna i dati chiamando le API
            }

            // Chiama le API per ottenere i dati aggiornati
            $cheapestDistributor = $this->getCheapestDistributor($productId);

            if (empty($cheapestDistributor)) {
                // Se non ci sono distributori, imposta valori predefiniti
                $product->product['type'] = ''; // Tipo predefinito
                $product->product['price_unit'] = 0;
                $product->product['stock'] = 0;
                return $product;
            }

            // Aggiorna o inserisci i dati nella tabella
            $this->updateProductTyrePricelistTable($productId, $cheapestDistributor);

            // Imposta i valori nel prodotto
            $product->product['type'] = $this->getVehicleTypeFromProductCode($productId);
            $product->product['price_unit'] = $cheapestDistributor['priceList'][0]['value'] ?? 0;
            $product->product['stock'] = $cheapestDistributor['stock'] ?? 0;

            return $product;

        } catch (Exception $e) {
            // In caso di errore, log e imposta valori predefiniti
            PrestaShopLogger::addLog(
                'Errore nel recupero dei prezzi: ' . $e->getMessage(),
                2, // Livello di errore
                null,
                'PriceListAdapter',
                $productId,
                true
            );

            $product->type = 'directions_car'; // Tipo predefinito
            $product->price_unit = 0;
            $product->stock = 0;
            return $product;
        }
    }

    /**
     * Aggiorna o inserisce i dati nella tabella product_tyre_pricelist
     * 
     * @param int $productId
     * @param array $distributorData
     * @return array I dati del prodotto aggiornato o un array vuoto
     */
    private function updateProductTyrePriceListTable($productId, $distributorData)
    {
        /** @var string */
        $pfx = _DB_PREFIX_;
        /** @var  \Symfony\Component\DependencyInjection\ContainerInterface */
        $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
        /** @var  \Doctrine\DBAL\Connection */
        $connection = $container->get('doctrine.dbal.default_connection');
        /** @var string */
        $dateStart = date('Y-m-d H:i:s');
        /** @var string */
        $dateEnd = date('Y-m-d H:i:s', strtotime('+10 days'));
        /** @var array */
        $productUpdated = [];

        foreach ($distributorData['priceList'] as $pricelist) {
            // Verifica se esiste già un record
            $query = "
                SELECT
                    COUNT(*)
                FROM
                    {$pfx}product_tyre_pricelist
                WHERE
                    id_t24 = :id_t24
                    AND id_distributor = :id_distributor
                    AND id_pricelist = :id_pricelist
            ";

            $result = $connection->executeQuery($query, [
                'id_t24' => $productId,
                'id_distributor' => $distributorData['id'],
                'id_pricelist' => $pricelist['id'],
            ]);

            if ($result->fetchOne() > 0) {
                $updateQuery = "
                    UPDATE
                        {$pfx}product_tyre_pricelist
                    SET
                        name = :name,
                        country = :country,
                        country_code = :country_code,
                        type = :type,
                        min_order = :min_order,
                        price_unit = :price_unit,
                        delivery_time = DATE(NOW()),
                        stock = :stock,
                        active = 1,
                        date_upd = :date_upd
                    WHERE
                        id_t24 = :id_t24
                        AND id_distributor = :id_distributor
                        AND id_pricelist = :id_pricelist
                ";

                $connection->executeStatement($updateQuery, [
                    'name' => $distributorData['name'],
                    'country' => $distributorData['country'],
                    'country_code' => $distributorData['countryCode'],
                    'type' => $pricelist['type'],
                    'min_order' => $pricelist['min_order'],
                    'price_unit' => $pricelist['value'],
                    'stock' => $distributorData['stock'],
                    'id_t24' => $productId,
                    'id_distributor' => $distributorData['id'],
                    'id_pricelist' => $pricelist['id'],
                    'date_upd' => $dateEnd
                ]);
            } else {
                $insertQuery = "
                    INSERT INTO
                        {$pfx}product_tyre_pricelist
                    SET
                        id_t24 = :id_t24,
                        id_distributor = :id_distributor,
                        id_pricelist = :id_pricelist,
                        name = :name,
                        country = :country,
                        country_code = :country_code,
                        type = :type,
                        min_order = :min_order,
                        price_unit = :price_unit,
                        delivery_time = DATE(NOW()),
                        stock = :stock,
                        active = 1,
                        date_add = :date_add,
                        date_upd = :date_upd
                ";

                $connection->executeStatement($insertQuery, [
                    'id_t24' => $productId,
                    'id_distributor' => $distributorData['id'],
                    'id_pricelist' => $pricelist['id'],
                    'name' => $distributorData['name'],
                    'country' => $distributorData['country'],
                    'country_code' => $distributorData['countryCode'],
                    'type' => $pricelist['type'],
                    'min_order' => $pricelist['min_order'],
                    'price_unit' => $pricelist['value'],
                    'stock' => $distributorData['stock'],
                    'date_add' => $dateStart,
                    'date_upd' => $dateEnd,
                ]);
            }

            //Adesso devo aggiornare i prezzi del prodotto con i nuovi prezzi trovati
            $product = new \Product();

            //Se è il listino singolo
            if ($pricelist['min_order'] == 1) {
                $product->price = $pricelist['value'];
                $product->update();
            } else {
                //Devo aggiornare lo specific price per le quantità maggiori di ['min_order']
                \SpecificPrice::deleteByProductId($productId);
                $specificPrice = new \SpecificPrice();
                $specificPrice->id_product = $productId;
                $specificPrice->id_shop = \Context::getContext()->shop->id;
                $specificPrice->id_currency = \Context::getContext()->currency->id;
                $specificPrice->id_country = 0;
                $specificPrice->id_group = 0;
                $specificPrice->price = $pricelist['value'];
                $specificPrice->from_quantity = $pricelist['min_order'];
                $specificPrice->reduction = 0;
                $specificPrice->reduction_tax = 1;
                $specificPrice->reduction_type = 'amount';
                $specificPrice->from = date('Y-m-d H:i:s');
                $specificPrice->to = date('Y-m-d H:i:s', strtotime('+10 days'));
                $specificPrice->add();
            }

            $productUpdated = [
                'id' => $productId,
                'price' => $pricelist['value'],
                'from_quantity' => $pricelist['min_order'],
                'date_upd' => date('Y-m-d H:i:s'),
            ];
        }

        return $productUpdated;
    }

    /**
     * Determina il tipo di veicolo in base al codice prodotto
     * 
     * @param string $productCode
     * @return string
     */
    private function getVehicleTypeFromProductCode($productCode)
    {
        // Implementazione semplice: assegna casualmente un tipo di veicolo
        // In una implementazione reale, si dovrebbe analizzare il codice prodotto
        $types = ['directions_car', 'airpost_shuttle', 'local_shipping'];
        return $types[rand(0, 2)];
    }
}
