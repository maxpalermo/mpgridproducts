<div class="product-info-container">
    <div class="container-fluid p-0">
        <div class="row">
            <!-- Colonna immagine prodotto -->
            <div class="col-md-4">
                <div class="product-image-container">
                    <img src="{$image_url}" alt="{$name}" class="img-fluid rounded shadow product-image">
                </div>
            </div>

            <!-- Colonna dettagli prodotto -->
            <div class="col-md-8">
                <div class="product-details">
                    <h2 class="product-title">{$name}</h2>

                    <div class="product-meta">
                        {if $reference}
                            <div class="meta-item">
                                <span class="meta-label">Riferimento:</span>
                                <span class="meta-value">{$reference}</span>
                            </div>
                        {/if}

                        {if $ean13}
                            <div class="meta-item">
                                <span class="meta-label">EAN-13:</span>
                                <span class="meta-value">{$ean13}</span>
                            </div>
                        {/if}

                        <div class="meta-item">
                            <span class="meta-label">Prezzo:</span>
                            <span class="meta-value price">{Tools::displayPrice($price)}</span>
                        </div>
                    </div>
                </div>

                <!-- Caratteristiche del prodotto -->
                {if $features && count($features) > 0}
                    <div class="product-features mt-4">
                        <h3 class="section-title">Caratteristiche</h3>
                        <div class="features-list">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tbody>
                                        {foreach from=$features item=feature}
                                            <tr>
                                                <td class="feature-name">{$feature.name}</td>
                                                <td class="feature-value">{$feature.value}</td>
                                            </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                {/if}
            </div>
        </div>

        <!-- Listini prezzi -->
        {if isset($pricesList) && $pricesList}
            <div class="row mt-4">
                <div class="col-12">
                    <div class="price-lists">
                        <h3 class="section-title">Listini Prezzi Disponibili</h3>

                        <div class="price-lists-container">
                            <div class="accordion" id="priceListsAccordion">
                                {foreach from=$pricesList key=distributorId item=distributor name=distributors}
                                    <div class="card" data-distributor-id="{$distributor.id}">
                                        <div class="card-header" id="heading{$distributor.id}">
                                            <h5 class="mb-0">
                                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse{$distributor.id}" aria-expanded="{if $smarty.foreach.distributors.first}true{else}false{/if}" aria-controls="collapse{$distributor.id}">
                                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                                        <div class="distributor-info">
                                                            <span class="flag-icon flag-icon-{strtolower($distributor.countryCode)}"></span>
                                                            {$distributor.name} ({$distributor.country})
                                                        </div>
                                                        <div class="distributor-stock">
                                                            <table class="table table-condensed">
                                                                <tr style="background-color: #606060; color: #f0f0f0;">
                                                                    <td>Quantità disponibile</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="d-flex justify-content-center">
                                                                        <span class="btn-circle-sm">{$distributor.stock}</span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </button>
                                            </h5>
                                        </div>

                                        <div id="collapse{$distributor.id}" class="collapse {if $smarty.foreach.distributors.first}show{/if}" aria-labelledby="heading{$distributor.id}" data-parent="#priceListsAccordion">
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Listino</th>
                                                                <th>Minimo ordine</th>
                                                                <th>Prezzo Unitario</th>
                                                                <th>Azioni</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {foreach from=$distributor.priceList item=price}
                                                                <tr>
                                                                    <td>{$price.type}</td>
                                                                    <td>{$price.min_order}</td>
                                                                    <td>{Tools::displayPrice($price.value)}</td>
                                                                    <td>
                                                                        <button class="btn btn-sm btn-primary add-to-cart" data-distributor-id="{$distributorId}" data-price-id="{$price@index}" data-quantity="{$distributor.stock}">
                                                                            <i class="material-icons">shopping_cart</i> Aggiungi
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            {/foreach}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/if}
    </div>
</div>

<style>
    
</style>

<script>
    $(document).ready(function() {
        // Gestione click sul pulsante "Aggiungi al carrello"
        $('.add-to-cart').click(function() {
            var distributorId = $(this).data('distributor-id');
            var priceId = $(this).data('price-id');
            var quantity = $(this).data('quantity');

            // Qui puoi implementare la logica per aggiungere il prodotto al carrello
            console.log('Aggiunto al carrello:', {
                distributorId: distributorId,
                priceId: priceId,
                quantity: quantity
            });

            // Mostra un messaggio di conferma
            alert('Prodotto aggiunto al carrello!');
        });
    });
</script>