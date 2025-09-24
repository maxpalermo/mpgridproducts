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

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the grid products
    const mpGridProducts = {
        // DOM elements
        grid: document.getElementById('mp-grid-products'),
        table: document.querySelector('.mp-grid-table'),
        tableBody: document.querySelector('.mp-grid-table tbody'),
        template: document.getElementById('mp-grid-product-template'),
        pagination: document.querySelector('.mp-grid-pagination ul'),
        limitSelect: document.getElementById('mp-grid-limit'),
        fetchProductInfoURL: document.getElementById('mp-grid-fetch-product-info-url').value,
        inputQuantity: document.querySelector('.stock-input'),

        // State
        currentPage: 1,
        limit: 10,
        totalItems: 0,
        totalPages: 0,
        categoryId: 0,
        searchQuery: '',
        ajaxUrl: '',
        orderBy: 'position',
        orderWay: 'asc',
        filters: {},

        /**
         * Initialize the grid
         */
        init: function() {
            if (!this.grid) {
                return;
            }

            // Get data attributes
            this.ajaxUrl = this.grid.dataset.ajaxUrl;
            this.categoryId = parseInt(this.grid.dataset.categoryId || 0);
            this.searchQuery = this.grid.dataset.searchQuery || '';
            this.limit = parseInt(this.grid.dataset.itemsPerPage || 10);
            this.orderBy = this.grid.dataset.orderBy || 'position';
            this.orderWay = this.grid.dataset.orderWay || 'asc';

            // Update limit select
            if (this.limitSelect) {
                this.limitSelect.value = this.limit;
            }

            // Initialize event listeners
            this.initEventListeners();

            // Load products
            this.loadProducts();

            // Listen for faceted search events
            document.addEventListener('updateProductList', (event) => {
                console.log('updateProductList event received', event);
                if (event.detail && event.detail.filters) {
                    this.filters = event.detail.filters;
                    this.currentPage = 1;
                    this.loadProducts();
                }
            });

            // Listen for prestashop facetedsearch events
            if (typeof prestashop !== 'undefined') {
                prestashop.on('updateFacets', (param) => {
                    console.log('updateFacets event received', param);

                    // Mostra lo stato di caricamento
                    this.tableBody.innerHTML = `
                        <tr class="mp-grid-loading">
                            <td colspan="6" class="text-center">
                                <div class="mp-grid-loader">
                                    <i class="material-icons">cached</i> Loading products...
                                </div>
                            </td>
                        </tr>
                    `;

                    // Salva il riferimento a this per usarlo nelle callback
                    const self = this;

                    // Intercetta la risposta AJAX di FacetedSearch
                    const originalXHROpen = window.XMLHttpRequest.prototype.open;
                    const originalXHRSend = window.XMLHttpRequest.prototype.send;

                    window.XMLHttpRequest.prototype.open = function() {
                        const xmlHTTP = this;
                        xmlHTTP.addEventListener('load', function() {
                            if (xmlHTTP.readyState === 4 && xmlHTTP.status === 200) {
                                console.clear();
                                try {
                                    const json = JSON.parse(xmlHTTP.responseText);
                                    const url = xmlHTTP.responseURL;

                                    // Verifica se json contiene current_url, pagination, products e rendered_facets
                                    const keys = ['current_url', 'pagination', 'products', 'rendered_facets'];
                                    if (keys.every(key => json.hasOwnProperty(key))) {
                                        console.log('Intercepted FacetedSearch response:', json);

                                        // Verifica se la risposta contiene i prodotti
                                        if (json.products && Array.isArray(json.products)) {
                                            // Formatta i prodotti nel formato atteso dal nostro modulo
                                            const formattedProducts = json.products.map(product => {
                                                return {
                                                    id: product.id_product || '',
                                                    name: product.name || 'Prodotto senza nome',
                                                    description_short: product.description_short || '',
                                                    price: typeof product.price_amount !== 'undefined' ? product.price_amount : 0,
                                                    price_formatted: product.price || '0,00 €',
                                                    reference: product.reference || '',
                                                    manufacturer: product.manufacturer_name || '',
                                                    url: product.url || '#',
                                                    add_to_cart_url: product.add_to_cart_url || '#',
                                                    image: product.cover && product.cover.bySize && product.cover.bySize.home_default ?
                                                        product.cover.bySize.home_default.url : ''
                                                };
                                            }).filter(product => product !== null && product.id);

                                            console.log('Formatted products:', formattedProducts);

                                            // Importante: assicuriamoci che la tabella sia aggiornata dopo che il DOM è stato aggiornato da FacetedSearch
                                            setTimeout(() => {
                                                // Forza l'aggiornamento della tabella
                                                const table = document.querySelector(".mp-grid-table");
                                                if (table) {
                                                    const tableBody = table.querySelector("tbody");
                                                    if (tableBody) {
                                                        console.log("Aggiornamento forzato della tabella", tableBody);
                                                        // Aggiorna il riferimento al tableBody
                                                        self.tableBody = tableBody;

                                                        // Aggiorna i prodotti
                                                        self.renderProducts(formattedProducts);

                                                        // Aggiorna la paginazione se disponibile
                                                        if (json.pagination) {
                                                            const paginationData = {
                                                                total_items: json.pagination.total_items || 0,
                                                                items_shown_from: json.pagination.items_shown_from || 1,
                                                                items_shown_to: json.pagination.items_shown_to || formattedProducts.length,
                                                                current_page: json.pagination.current_page || 1,
                                                                pages_count: json.pagination.pages_count || 1
                                                            };

                                                            self.renderPagination(paginationData);
                                                            self.updatePaginationInfo(paginationData);
                                                        }
                                                    }
                                                }
                                            }, 100); // Piccolo ritardo per assicurarsi che il DOM sia stato aggiornato
                                        }
                                    }
                                } catch (error) {
                                    console.error('Error parsing FacetedSearch response:', error);
                                    // Fallback: carica i prodotti normalmente
                                    self.loadProducts();
                                }
                            }
                        });
                        originalXHROpen.apply(xmlHTTP, arguments);
                    };

                    window.XMLHttpRequest.prototype.send = function() {
                        originalXHRSend.apply(this, arguments);
                    };

                    // Ripristina i metodi originali dopo 5 secondi per evitare problemi
                    setTimeout(() => {
                        window.XMLHttpRequest.prototype.open = originalXHROpen;
                        window.XMLHttpRequest.prototype.send = originalXHRSend;

                        // Se dopo 5 secondi siamo ancora in stato di caricamento, carica i prodotti normalmente
                        if (self.tableBody.querySelector('.mp-grid-loading')) {
                            console.log('Timeout reached, loading products normally');
                            self.loadProducts();
                        }
                    }, 5000);
                });

                // Intercetta anche l'evento di errore del facetedsearch
                prestashop.on('updateProductListError', (error) => {
                    console.clear();
                    console.error('FacetedSearch error:', error);
                    // Mostra un messaggio di errore
                    this.tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="alert alert-danger">
                                    Error loading products. Please try again.
                                </div>
                            </td>
                        </tr>
                    `;
                    pendingRequest = false;
                });
            }
        },

        showProductInfo: async (id_product, tr) => {
            const self = this;
            const url = document.getElementById("mp-grid-fetch-product-info-url").value;
            console.log('showProductInfo', id_product, "url", url);
            const nextTr = tr.nextElementSibling;
            if (nextTr && nextTr.classList.contains('mp-grid-product-info')) {
                nextTr.remove();
            } else {
                const template = document.getElementById('mp-grid-product-info-template');
                const clone = document.importNode(template.content, true);
                const newTr = clone.querySelector('.mp-grid-product-info');
                newTr.dataset.idProduct = id_product;
                tr.parentNode.insertBefore(newTr, tr.nextSibling);
                const td = newTr.querySelector("td");
                const formData = new FormData();
                formData.append('action', 'fetchProductInfo');
                formData.append('id_product', id_product);
                formData.append('ajax', 1);

                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                console.log("Received:", data);
                td.innerHTML = data.page;

            }
        },

        /**
         * Initialize event listeners
         */
        initEventListeners: function() {
            // Pagination click
            this.pagination.addEventListener('click', (event) => {
                event.preventDefault();

                const target = event.target.closest('a');
                if (!target) return;

                const li = target.closest('li');
                if (li.classList.contains('disabled') || li.classList.contains('active')) {
                    return;
                }

                if (li.classList.contains('mp-grid-prev')) {
                    this.currentPage--;
                } else if (li.classList.contains('mp-grid-next')) {
                    this.currentPage++;
                } else {
                    this.currentPage = parseInt(target.textContent);
                }

                this.loadProducts();
            });

            // Items per page change
            this.limitSelect.addEventListener('change', () => {
                this.limit = parseInt(this.limitSelect.value);
                this.currentPage = 1;
                this.loadProducts();
            });

            // Add to cart button click
            this.tableBody.addEventListener('click', async (event) => {
                const addToCartBtn = event.target.closest('.mp-grid-add-to-cart');
                
                if (addToCartBtn) {
                    event.preventDefault();
                    const url = addToCartBtn.dataset.url;
                    const id_product = addToCartBtn.dataset.id_product;
                    const id_product_attribute = addToCartBtn.dataset.id_product_attribute;
                    const quantity = addToCartBtn.dataset.quantity;

                    // Add loading state
                    addToCartBtn.classList.add('loading');
                    const originalText = addToCartBtn.innerHTML;
                    addToCartBtn.innerHTML = '<i class="material-icons">cached</i>';

                    // Send AJAX request to add product to cart
                    const tokenEl = document.getElementById("token");
                    const formData = new FormData();
                    formData.append("action", "update");
                    formData.append("add", 1);
                    formData.append("ajax", 1);
                    formData.append("token", tokenEl ? tokenEl.value : "");
                    formData.append("id_product", id_product);
                    formData.append("id_product_attribute", id_product_attribute);
                    formData.append("qty", quantity);

                    try {
                        const response = await fetch(url, {
                                method: 'POST',
                                body: formData
                            });
    
                        const resp = await response.json();
                        resp.id_product = id_product;
                        resp.id_product_attribute = id_product_attribute;
                        resp.id_customization = 0;
                        resp.quantity = quantity;

                        /* const link_add_to_cart = json.link;
                        const link_show_modal = json.showModalUrl
                            
                        //await fetch(link_add_to_cart);
                        const modal = await fetch(link_show_modal);
                        const json_modal = await modal.json();
                        const preview = json_modal.preview; */
                        prestashop.emit("updateCart", {
                            resp: resp,
                            reason: {
                                cart: resp.cart,
                                idProduct: id_product,
                                idProductAttribute: id_product_attribute,
                                idCustomization: 0,
                                linkAction: 'add-to-cart',
                            }
                        });

                        addToCartBtn.innerHTML = originalText;
                        addToCartBtn.classList.remove('loading');
                    } catch (error) {
                        console.error('Error adding product to cart:', error);
                        addToCartBtn.innerHTML = originalText;
                        addToCartBtn.classList.remove('loading');
                    }
                }
            });
        },

        /**
         * Load products via AJAX
         */
        loadProducts: function() {
            // Variabile per tenere traccia delle richieste in corso
            if (typeof this.pendingRequest === 'undefined') {
                this.pendingRequest = false;
            }

            // Se c'è già una richiesta in corso, non ne avviamo un'altra
            if (this.pendingRequest) {
                console.log('Request already in progress, skipping...');
                return;
            }

            // Imposta la richiesta come in corso
            this.pendingRequest = true;

            // Show loading state
            this.tableBody.innerHTML = `
                <tr class="mp-grid-loading">
                    <td colspan="6" class="text-center">
                        <div class="mp-grid-loader">
                            <i class="material-icons">cached</i> Loading products...
                        </div>
                    </td>
                </tr>
            `;

            // Prepare request parameters
            const params = new URLSearchParams({
                action: 'getProducts',
                page: this.currentPage,
                limit: this.limit,
                category_id: this.categoryId,
                search_query: this.searchQuery,
                order_by: this.orderBy,
                order_way: this.orderWay,
                ajax: 1
            });

            // Add filters from faceted search
            if (Object.keys(this.filters).length > 0) {
                params.append('filters', JSON.stringify(this.filters));
            }

            // Send AJAX request
            fetch(`${this.ajaxUrl}?${params.toString()}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    this.renderProducts(data.products);
                    this.renderPagination(data.pagination);
                    this.updatePaginationInfo(data.pagination);
                    // Richiesta completata
                    this.pendingRequest = false;
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    this.tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="alert alert-danger">
                                    Error loading products. Please try again.
                                </div>
                            </td>
                        </tr>
                    `;
                    // Richiesta completata (con errore)
                    this.pendingRequest = false;
                });
        },

        /**
         * Render products in the table
         * @param {Array} products 
         */
        renderProducts: function(products) {
            const self = this;
            const table = document.querySelector(".mp-grid-table");
            if (table) {
                console.log("set tablebody", table);
                self.tableBody = table.querySelector("tbody");
                if (products.length) {
                    console.log("clear table");
                    self.tableBody.innerHTML = "";
                }
            }

            if (products.length === 0) {
                self.tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="alert alert-info">
                                No products found.
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            // Verifica se il template esiste
            self.template = document.getElementById('mp-grid-product-template');
            console.log("template", self.template);

            if (!self.template) {
                console.error('Template not found: mp-grid-product-template');

                // Fallback: crea una riga di prodotto manualmente
                products.forEach(product => {
                    const row = document.createElement('tr');
                    row.className = 'mp-grid-product';
                    row.innerHTML = `
                        <td class="mp-grid-col-image">
                            <a href="${product.url}" class="mp-grid-product-image">
                                <img src="${product.image}" alt="${product.name}" class="img-fluid">
                            </a>
                        </td>
                        <td class="mp-grid-col-name">
                            <a href="${product.url}" class="mp-grid-product-name">
                                ${product.name}
                            </a>
                            <div class="mp-grid-product-desc">
                                ${product.description_short}
                            </div>
                        </td>
                        <td class="mp-grid-col-reference">
                            ${product.reference}
                        </td>
                        <td class="mp-grid-col-manufacturer">
                            ${product.manufacturer}
                        </td>
                        <td class="mp-grid-col-price">
                            ${product.price_formatted}
                        </td>
                        <td class="mp-grid-col-actions">
                            <a href="${product.url}" class="btn btn-primary btn-sm mp-grid-view">
                                <i class="material-icons">visibility</i> View
                            </a>
                            <a href="${product.add_to_cart_url}" class="btn btn-success btn-sm mp-grid-add-to-cart">
                                <i class="material-icons">shopping_cart</i> Add
                            </a>
                        </td>
                    `;

                    self.tableBody.appendChild(row);
                });
                return;
            }

            // Clone template and add products
            try {
                products.forEach(product => {
                    const template = this.template.content.cloneNode(true);

                    // Replace template variables with actual data
                    const row = template.querySelector('tr');
                    if (!row) {
                        console.error('Row not found in template');
                        return;
                    }

                    const html = row.outerHTML;
                    const productHtml = html.replace(/\{\{(\w+)\}\}/g, (match, key) => {
                        return product[key] || '';
                    });

                    // Add to table
                    self.tableBody.insertAdjacentHTML('beforeend', productHtml);
                });
                const tBody = self.tableBody;
                console.log("tBody", tBody);

                //Aggiorno il listener per info row
                const infoBtns = document.querySelectorAll(".mp-grid-info-row");

                const inputQuantity = document.querySelectorAll(".stock-input");
                //Input quantity
                inputQuantity.forEach((input) =>{
                    input.addEventListener('focus', () => {
                        input.select();
                    });
                });
                inputQuantity.forEach((input) => {
                    input.addEventListener('input', () => {
                        const value = parseInt(input.value);       // Ora 'this' si riferisce all'elemento input
                        if (value < 1) {
                            input.value = 1;
                        }
                        if (value > input.max) {
                            input.value = input.max;
                        }

                        const btnAddToCart = input.closest("tr").querySelector(".mp-grid-add-to-cart");
                        btnAddToCart.dataset.quantity = input.value;
                    });
                });
                
                infoBtns.forEach((btn) => {
                    btn.addEventListener("click", (event) => {
                        event.preventDefault();
                        const row = event.target.closest("tr");
                        const productId = row.dataset.idProduct;
                        self.showProductInfo(productId, row);
                    });
                });

            } catch (error) {
                console.error('Error rendering products:', error);

                // Fallback: mostra un messaggio di errore
                self.tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="alert alert-danger">
                                Error rendering products. Please try again.
                            </div>
                        </td>
                    </tr>
                `;
            }
        },

        /**
         * Render pagination
         * @param {Object} pagination 
         */
        renderPagination: function(pagination) {
            this.totalItems = pagination.total_items;
            this.totalPages = pagination.pages_count;

            // Clear pagination
            this.pagination.innerHTML = '';

            // Se non ci sono pagine o solo una pagina, non mostrare la paginazione
            if (this.totalPages <= 1) {
                return;
            }

            // First page button
            if (this.totalPages > 3) {
                const firstLi = document.createElement('li');
                firstLi.className = `page-item mp-grid-page-first ${this.currentPage <= 1 ? 'disabled' : ''}`;
                firstLi.innerHTML = `
                    <a class="page-link" href="#" aria-label="First">
                        <span aria-hidden="true">&laquo;&laquo;</span>
                        <span class="sr-only">First</span>
                    </a>
                `;
                this.pagination.appendChild(firstLi);
            }

            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item mp-grid-prev ${this.currentPage <= 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `
                <a class="page-link" href="#" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                    <span class="sr-only">Previous</span>
                </a>
            `;
            this.pagination.appendChild(prevLi);

            // Calcola le pagine da mostrare
            let maxVisiblePages = window.innerWidth < 768 ? 3 : 5; // Responsive: meno pagine su mobile
            let startPage, endPage;

            if (this.totalPages <= maxVisiblePages) {
                // Se il numero totale di pagine è minore o uguale al massimo visibile, mostra tutte
                startPage = 1;
                endPage = this.totalPages;
            } else {
                // Calcola l'intervallo di pagine da mostrare
                const halfVisible = Math.floor(maxVisiblePages / 2);

                if (this.currentPage <= halfVisible + 1) {
                    // Vicino all'inizio
                    startPage = 1;
                    endPage = maxVisiblePages;
                } else if (this.currentPage >= this.totalPages - halfVisible) {
                    // Vicino alla fine
                    startPage = this.totalPages - maxVisiblePages + 1;
                    endPage = this.totalPages;
                } else {
                    // Nel mezzo
                    startPage = this.currentPage - halfVisible;
                    endPage = this.currentPage + halfVisible;
                }
            }

            // Aggiungi ellipsis all'inizio se necessario
            if (startPage > 1) {
                const firstPageLi = document.createElement('li');
                firstPageLi.className = 'page-item mp-grid-page-1';
                firstPageLi.innerHTML = `<a class="page-link" href="#">1</a>`;
                this.pagination.appendChild(firstPageLi);

                if (startPage > 2) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    ellipsisLi.innerHTML = `<span class="page-link">...</span>`;
                    this.pagination.appendChild(ellipsisLi);
                }
            }

            // Aggiungi le pagine numerate
            for (let i = startPage; i <= endPage; i++) {
                const pageLi = document.createElement('li');
                pageLi.className = `page-item mp-grid-page-${i} ${i === this.currentPage ? 'active' : ''}`;
                pageLi.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                this.pagination.appendChild(pageLi);
            }

            // Aggiungi ellipsis alla fine se necessario
            if (endPage < this.totalPages) {
                if (endPage < this.totalPages - 1) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    ellipsisLi.innerHTML = `<span class="page-link">...</span>`;
                    this.pagination.appendChild(ellipsisLi);
                }

                const lastPageLi = document.createElement('li');
                lastPageLi.className = `page-item mp-grid-page-${this.totalPages}`;
                lastPageLi.innerHTML = `<a class="page-link" href="#">${this.totalPages}</a>`;
                this.pagination.appendChild(lastPageLi);
            }

            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item mp-grid-next ${this.currentPage >= this.totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `
                <a class="page-link" href="#" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                    <span class="sr-only">Next</span>
                </a>
            `;
            this.pagination.appendChild(nextLi);

            // Last page button
            if (this.totalPages > 3) {
                const lastLi = document.createElement('li');
                lastLi.className = `page-item mp-grid-page-last ${this.currentPage >= this.totalPages ? 'disabled' : ''}`;
                lastLi.innerHTML = `
                    <a class="page-link" href="#" aria-label="Last">
                        <span aria-hidden="true">&raquo;&raquo;</span>
                        <span class="sr-only">Last</span>
                    </a>
                `;
                this.pagination.appendChild(lastLi);
            }
        },

        /**
         * Update pagination info text
         * @param {Object} pagination 
         */
        updatePaginationInfo: function(pagination) {
            const fromEl = this.grid.querySelector('.mp-grid-from');
            const toEl = this.grid.querySelector('.mp-grid-to');
            const totalEl = this.grid.querySelector('.mp-grid-total');

            if (fromEl) fromEl.textContent = pagination.items_shown_from;
            if (toEl) toEl.textContent = pagination.items_shown_to;
            if (totalEl) totalEl.textContent = pagination.total_items;
        }
    };

    // Initialize the grid
    mpGridProducts.init();
});