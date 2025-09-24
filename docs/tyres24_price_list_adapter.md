# Tyres24 Price List Adapter - Documentazione

## Introduzione

L'adapter `PriceListAdapter` è un componente che permette di interfacciarsi con l'API di Tyres24 per recuperare informazioni sui listini prezzi, dettagli dei prodotti e disponibilità. Questo documento spiega come configurare e utilizzare l'adapter all'interno del modulo mpgridproducts o in altri contesti.

## Configurazione

### Parametri di configurazione

L'adapter utilizza le seguenti configurazioni di PrestaShop:

| Parametro                            | Descrizione                             | Valore di default                                |
| ------------------------------------ | --------------------------------------- | ------------------------------------------------ |
| `MPGRIDPRODUCTS_TYRES24_API_URL`     | URL base dell'API Tyres24               | `https://tyre24.alzura.com/it/it/rest/V14/tyres` |
| `MPGRIDPRODUCTS_TYRES24_API_KEY`     | Chiave API per l'autenticazione         | `""` (vuoto)                                     |
| `MPGRIDPRODUCTS_TYRES24_API_TIMEOUT` | Timeout per le chiamate curl in secondi | `30`                                             |

### Configurazione tramite l'interfaccia di amministrazione

È possibile configurare l'adapter direttamente dall'interfaccia di amministrazione di PrestaShop:

1. Vai a **Moduli > Gestione Moduli**
2. Trova il modulo **MP Grid Products** e clicca su **Configura**
3. Nella sezione **Tyres24 API Settings** puoi configurare:
    - **API URL**: l'URL base dell'API Tyres24
    - **API Key**: la chiave API per l'autenticazione
    - **API Timeout**: il timeout per le chiamate curl in secondi
4. Clicca su **Salva** per salvare le impostazioni

### Impostare i parametri di configurazione via codice

Per impostare questi parametri programmaticamente, puoi utilizzare il metodo `Configuration::updateValue()` di PrestaShop:

```php
// Nel tuo modulo o script di installazione
Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_API_URL', 'https://api.tyres24.com/v1');
Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_API_KEY', 'la_tua_chiave_api');
Configuration::updateValue('MPGRIDPRODUCTS_TYRES24_API_TIMEOUT', 30);
```

## Utilizzo dell'adapter

### Inizializzazione

Per utilizzare l'adapter, devi prima includere la classe e creare un'istanza:

```php
// Includi la classe (se non stai utilizzando l'autoloader)
require_once _PS_MODULE_DIR_ . 'mpgridproducts/src/Adapter/PriceListAdapter.php';

// Crea un'istanza dell'adapter
$priceListAdapter = new \MpSoft\MpGridProducts\Adapter\PriceListAdapter();
```

### Recuperare il listino prezzi

Per recuperare il listino prezzi di un prodotto specifico:

```php
try {
    // Recupera il listino prezzi per un codice prodotto
    $productCode = 'ABC123'; // Sostituisci con il codice prodotto reale
    $priceList = $priceListAdapter->getPriceList($productCode);

    // Ora $priceList contiene i dati del listino prezzi
    // Puoi elaborarli come necessario
    print_r($priceList);
} catch (Exception $e) {
    // Gestisci l'errore
    echo 'Errore: ' . $e->getMessage();
}
```

### Recuperare i dettagli del prodotto

Per recuperare informazioni dettagliate su un prodotto:

```php
try {
    $productCode = 'ABC123'; // Sostituisci con il codice prodotto reale
    $productDetails = $priceListAdapter->getProductDetails($productCode);

    // Ora $productDetails contiene i dettagli del prodotto
    print_r($productDetails);
} catch (Exception $e) {
    // Gestisci l'errore
    echo 'Errore: ' . $e->getMessage();
}
```

### Verificare la disponibilità

Per verificare la disponibilità di un prodotto:

```php
try {
    $productCode = 'ABC123'; // Sostituisci con il codice prodotto reale
    $availability = $priceListAdapter->checkAvailability($productCode);

    // Ora $availability contiene i dati sulla disponibilità
    print_r($availability);
} catch (Exception $e) {
    // Gestisci l'errore
    echo 'Errore: ' . $e->getMessage();
}
```

### Effettuare un ordine

Per effettuare un ordine per un prodotto:

```php
try {
    $productCode = 'ABC123'; // Sostituisci con il codice prodotto reale
    $quantity = 2; // Quantità da ordinare

    // Dettagli aggiuntivi dell'ordine (opzionali)
    $orderDetails = [
        'customer_reference' => 'ORD-12345',
        'shipping_address' => [
            'name' => 'Mario Rossi',
            'address' => 'Via Roma 123',
            'city' => 'Milano',
            'postal_code' => '20100',
            'country' => 'IT'
        ]
    ];

    $orderConfirmation = $priceListAdapter->placeOrder($productCode, $quantity, $orderDetails);

    // Ora $orderConfirmation contiene i dati di conferma dell'ordine
    print_r($orderConfirmation);
} catch (Exception $e) {
    // Gestisci l'errore
    echo 'Errore: ' . $e->getMessage();
}
```

## Integrazione con AJAX

L'adapter è già integrato nel controller AJAX del modulo mpgridproducts. Puoi utilizzarlo tramite chiamate AJAX come segue:

### Esempio di chiamata AJAX

```javascript
// URL del controller AJAX
const ajaxUrl = prestashop.urls.base_url + "modules/mpgridproducts/controllers/front/ajax";

// Parametri della richiesta
const data = new FormData();
data.append("action", "getTyres24PriceList");
data.append("product_code", "ABC123"); // Sostituisci con il codice prodotto reale
data.append("ajax", 1);

// Esegui la richiesta AJAX
fetch(ajaxUrl, {
    method: "POST",
    body: data,
})
    .then((response) => response.json())
    .then((data) => {
        // Elabora i dati ricevuti
        console.log("Dati ricevuti:", data);

        if (data.error) {
            console.error("Errore:", data.message);
        } else {
            // Utilizza i dati del listino prezzi
            console.log("Listino prezzi:", data.price_list);

            // Se disponibili, utilizza anche i dettagli del prodotto
            if (data.product_details) {
                console.log("Dettagli prodotto:", data.product_details);
            }

            // Se disponibili, utilizza anche i dati sulla disponibilità
            if (data.availability) {
                console.log("Disponibilità:", data.availability);
            }
        }
    })
    .catch((error) => {
        console.error("Errore nella richiesta AJAX:", error);
    });
```

### Esempio con jQuery

Se preferisci utilizzare jQuery per le chiamate AJAX:

```javascript
$.ajax({
    url: prestashop.urls.base_url + "modules/mpgridproducts/controllers/front/ajax",
    type: "POST",
    data: {
        action: "getTyres24PriceList",
        product_code: "ABC123", // Sostituisci con il codice prodotto reale
        ajax: 1,
    },
    dataType: "json",
    success: function (data) {
        // Elabora i dati ricevuti
        console.log("Dati ricevuti:", data);

        if (data.error) {
            console.error("Errore:", data.message);
        } else {
            // Utilizza i dati del listino prezzi
            console.log("Listino prezzi:", data.price_list);

            // Se disponibili, utilizza anche i dettagli del prodotto
            if (data.product_details) {
                console.log("Dettagli prodotto:", data.product_details);
            }

            // Se disponibili, utilizza anche i dati sulla disponibilità
            if (data.availability) {
                console.log("Disponibilità:", data.availability);
            }
        }
    },
    error: function (xhr, status, error) {
        console.error("Errore nella richiesta AJAX:", error);
    },
});
```

## Integrazione in altri contesti

### Utilizzo in un controller PrestaShop

Per utilizzare l'adapter in un controller PrestaShop:

```php
class MyModuleController extends ModuleControllerCore
{
    public function initContent()
    {
        parent::initContent();

        // Includi l'adapter
        require_once _PS_MODULE_DIR_ . 'mpgridproducts/src/Adapter/PriceListAdapter.php';

        try {
            // Crea un'istanza dell'adapter
            $priceListAdapter = new \MpSoft\MpGridProducts\Adapter\PriceListAdapter();

            // Recupera il listino prezzi
            $productCode = Tools::getValue('product_code', '');
            $priceList = $priceListAdapter->getPriceList($productCode);

            // Assegna i dati al template
            $this->context->smarty->assign([
                'price_list' => $priceList,
                'product_code' => $productCode
            ]);

            // Renderizza il template
            $this->setTemplate('module:mymodule/views/templates/front/price_list.tpl');
        } catch (Exception $e) {
            // Gestisci l'errore
            $this->context->smarty->assign([
                'error' => true,
                'error_message' => $e->getMessage()
            ]);

            // Renderizza il template di errore
            $this->setTemplate('module:mymodule/views/templates/front/error.tpl');
        }
    }
}
```

### Utilizzo in un hook

Per utilizzare l'adapter in un hook:

```php
public function hookDisplayProductAdditionalInfo($params)
{
    // Ottieni il prodotto corrente
    $product = $params['product'];

    // Ottieni il codice prodotto (ad esempio dalla reference)
    $productCode = $product->reference;

    if (empty($productCode)) {
        return '';
    }

    try {
        // Includi l'adapter
        require_once _PS_MODULE_DIR_ . 'mpgridproducts/src/Adapter/PriceListAdapter.php';

        // Crea un'istanza dell'adapter
        $priceListAdapter = new \MpSoft\MpGridProducts\Adapter\PriceListAdapter();

        // Recupera la disponibilità
        $availability = $priceListAdapter->checkAvailability($productCode);

        // Assegna i dati al template
        $this->context->smarty->assign([
            'tyres24_availability' => $availability,
            'product_code' => $productCode
        ]);

        // Renderizza il template
        return $this->display(__FILE__, 'views/templates/hook/product_availability.tpl');
    } catch (Exception $e) {
        // In caso di errore, non mostrare nulla o mostrare un messaggio di errore
        return '';
    }
}
```

## Gestione degli errori

L'adapter include una gestione completa degli errori. Tutte le eccezioni vengono registrate nel log di PrestaShop tramite `PrestaShopLogger::addLog()`. È consigliabile utilizzare sempre i blocchi try-catch quando si utilizza l'adapter per gestire correttamente gli errori.

## Considerazioni sulla sicurezza

-   La chiave API è un dato sensibile. Assicurati di non esporla nel frontend o in file pubblicamente accessibili.
-   L'adapter disabilita la verifica SSL per semplificare lo sviluppo. In un ambiente di produzione, è consigliabile abilitare la verifica SSL impostando `CURLOPT_SSL_VERIFYPEER` a `true`.
-   Assicurati di validare sempre i dati di input prima di passarli all'adapter, specialmente se provengono da input utente.

## Risoluzione dei problemi

### Errori comuni

1. **Errore di connessione**: Verifica che l'URL dell'API sia corretto e che il server sia raggiungibile.
2. **Errore di autenticazione**: Verifica che la chiave API sia corretta.
3. **Timeout**: Se le richieste impiegano troppo tempo, prova ad aumentare il valore del timeout.

### Debug

Per facilitare il debug, puoi aggiungere log aggiuntivi:

```php
PrestaShopLogger::addLog(
    'Debug Tyres24 API: ' . json_encode($data),
    1, // Livello di log (1 = info)
    null,
    'PriceListAdapter',
    null,
    true
);
```

## Conclusione

L'adapter `PriceListAdapter` fornisce un'interfaccia semplice e robusta per interagire con l'API di Tyres24. Seguendo questa documentazione, dovresti essere in grado di integrare facilmente le funzionalità di Tyres24 nel tuo progetto PrestaShop.
