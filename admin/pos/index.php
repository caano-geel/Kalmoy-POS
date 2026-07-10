<?php
$pos_registered_clients = CustomerDebtService::registered_clients($conn);
$pos_can_debt_sale = debt_can_sale();
?>
<style>
    #pos-search-results .pos-result-item { cursor: pointer; transition: background .15s; }
    #pos-search-results .pos-result-item:hover { background: #f4f6f9; }
    #pos-search-results .pos-result-item:last-child { border-bottom: 0 !important; }
    #pos-search-results .pos-result-meta { font-size: .85rem; color: #6c757d; }
    #pos-cart-table td, #pos-cart-table th { vertical-align: middle !important; }
    #receipt-print { display: none; }
</style>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cash-register mr-1"></i> Point of Sale</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12 col-lg-5 mb-3 mb-lg-0">
                <div class="form-group">
                    <label>Barcode / Product Search</label>
                    <div class="input-group">
                        <input type="text" id="pos-search" class="form-control" placeholder="Scan barcode or type product name..." autocomplete="off" autofocus>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default" id="pos-search-btn"><i class="fa fa-search"></i></button>
                            <button type="button" class="btn btn-info" id="start-pos-scan"><i class="fa fa-barcode"></i> Scan</button>
                        </div>
                    </div>
                </div>
                <div id="barcode-scanner-wrap" class="mb-3" style="display:none;">
                    <div id="barcode-camera-select-wrap" class="mb-2" style="display:none; max-width:420px;">
                        <label for="barcode-camera-select" class="small mb-1 d-block">Select Camera</label>
                        <select id="barcode-camera-select" class="form-control form-control-sm"></select>
                    </div>
                    <p class="small text-muted mb-2">Hold barcode straight, close, and well-lit.</p>
                    <div id="barcode-scanner-reader" style="max-width:420px;"></div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="stop-pos-scan">Stop Scan</button>
                </div>
                <div id="pos-search-results" class="border rounded" style="max-height:320px; overflow-y:auto;">
                    <div class="text-muted text-center py-4">Search or scan to find products</div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Transaction Cart</h5>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="pos-clear-cart"><i class="fa fa-trash"></i> Clear Cart</button>
                </div>
                <div class="table-responsive ash-table-wrap">
                    <table class="table table-bordered table-sm ash-table" id="pos-cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th width="90">Price</th>
                                <th width="80">Qty</th>
                                <th width="90">Total</th>
                                <th width="40"></th>
                            </tr>
                        </thead>
                        <tbody id="pos-cart-body">
                            <tr id="pos-cart-empty">
                                <td colspan="5" class="text-center text-muted">No items in cart</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Subtotal</th>
                                <th colspan="2" id="pos-subtotal">Ksh 0</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-right">Discount</th>
                                <th colspan="2" id="pos-discount-total">Ksh 0</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-right">Amount Due</th>
                                <th colspan="2" id="pos-cart-total">Ksh 0</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="row mt-2">
                    <div class="form-group col-12 col-md-6 mb-2" id="pos-walkin-wrap">
                        <label for="pos-customer-name">Walk-in Name <small class="text-muted">(optional)</small></label>
                        <input type="text" id="pos-customer-name" class="form-control form-control-sm" placeholder="Walk-in Customer" autocomplete="off">
                    </div>
                    <div class="form-group col-12 col-md-6 mb-2" id="pos-client-wrap">
                        <label for="pos-client-id">Registered Customer <span id="pos-client-required" class="text-danger" style="display:none">*</span></label>
                        <select id="pos-client-id" class="form-control form-control-sm">
                            <option value="">— Walk-in / none —</option>
                            <?php foreach($pos_registered_clients as $pc): ?>
                            <option value="<?php echo (int)$pc['id'] ?>"><?php echo htmlspecialchars($pc['fullname'].' — '.$pc['contact']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-3 mb-2">
                        <label for="pos-discount-pct">Discount (%)</label>
                        <input type="number" id="pos-discount-pct" class="form-control form-control-sm" value="0" min="0" max="100" step="0.01">
                    </div>
                    <div class="form-group col-12 col-md-3 mb-2">
                        <label for="pos-discount-ksh">Discount (Ksh)</label>
                        <input type="number" id="pos-discount-ksh" class="form-control form-control-sm" value="0" min="0" step="0.01">
                    </div>
                </div>
                <div class="row align-items-end mt-1 pos-action-row">
                    <div class="form-group col-12 col-md-4 mb-2 mb-md-0">
                        <label>Payment Method</label>
                        <select id="pos-payment" class="form-control">
                            <option value="Cash">Cash</option>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Mixed">Mixed Payment</option>
                            <?php if($pos_can_debt_sale): ?>
                            <option value="Debt">Debt (Credit)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group col-6 col-md-4 mb-2 mb-md-0" id="pos-mixed-cash-wrap" style="display:none">
                        <label for="pos-cash-amount">Cash Amount</label>
                        <input type="number" id="pos-cash-amount" class="form-control form-control-sm" min="0" step="0.01" value="0">
                    </div>
                    <div class="form-group col-6 col-md-4 mb-2 mb-md-0" id="pos-mixed-mpesa-wrap" style="display:none">
                        <label for="pos-mpesa-amount">M-Pesa Amount</label>
                        <input type="number" id="pos-mpesa-amount" class="form-control form-control-sm" min="0" step="0.01" value="0">
                    </div>
                    <div class="form-group col-12 col-md-4 mb-2 mb-md-0" id="pos-due-date-wrap" style="display:none">
                        <label for="pos-due-date">Due Date <small class="text-muted">(optional)</small></label>
                        <input type="date" id="pos-due-date" class="form-control form-control-sm">
                    </div>
                    <div class="form-group col-12 col-md-8 mb-0">
                        <div class="btn-group btn-block">
                            <button type="button" class="btn btn-warning" id="pos-hold-sale" title="Suspend current sale">
                                <i class="fa fa-pause"></i> Suspend Sale
                            </button>
                            <button type="button" class="btn btn-secondary" id="pos-resume-sale" title="Resume suspended sale" disabled>
                                <i class="fa fa-play"></i> Resume Sale
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="pos-clear-held" title="Clear suspended sale" disabled>
                                <i class="fa fa-times"></i> Clear Suspended
                            </button>
                            <button type="button" class="btn btn-primary" id="pos-complete-sale" disabled title="F4">
                                <i class="fa fa-check"></i> Complete Transaction
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">Shortcuts: F2 search · F4 complete · Esc clear search · Ctrl+Backspace clear cart</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="receipt-print">
    <div class="rc-header">
        <div class="rc-logo"><img src="<?php echo validate_image($_settings->info('logo')) ?>" alt=""></div>
        <div class="rc-store"><?php echo $_settings->info('name') ?></div>
        <div class="rc-title">SALES RECEIPT</div>
    </div>
    <div class="rc-line"></div>
    <div class="rc-meta">
        <div class="rc-row"><span>Receipt No:</span><span id="rc-ref"></span></div>
        <div class="rc-row"><span>Date:</span><span id="rc-date"></span></div>
        <div class="rc-row"><span>Cashier:</span><span id="rc-cashier"><?php echo htmlspecialchars(trim($_settings->userdata('firstname').' '.$_settings->userdata('lastname'))) ?></span></div>
        <div class="rc-row"><span>Customer:</span><span id="rc-customer">Walk-in Customer</span></div>
        <div class="rc-row"><span>Payment:</span><span id="rc-payment"></span></div>
        <div class="rc-row" id="rc-debt-banner-row" style="display:none"><span colspan="2" style="width:100%;text-align:center;font-weight:bold">*** SOLD ON CREDIT ***</span></div>
        <div class="rc-row" id="rc-outstanding-row" style="display:none"><span>Outstanding Balance:</span><span id="rc-outstanding"></span></div>
        <div class="rc-row" id="rc-due-row" style="display:none"><span>Due Date:</span><span id="rc-due"></span></div>
        <div class="rc-row"><span>Status:</span><span id="rc-status">PAID</span></div>
    </div>
    <div class="rc-line"></div>
    <table class="rc-table">
        <thead>
            <tr>
                <th class="rc-col-item">Item</th>
                <th class="rc-col-qty">Qty</th>
                <th class="rc-col-price">Price</th>
                <th class="rc-col-total">Total</th>
            </tr>
        </thead>
        <tbody id="rc-items"></tbody>
    </table>
    <div class="rc-line"></div>
    <div class="rc-summary">
        <div class="rc-row"><span>Subtotal:</span><span id="rc-subtotal"></span></div>
        <div class="rc-row" id="rc-discount-row" style="display:none"><span>Discount:</span><span id="rc-discount"></span></div>
    </div>
    <div class="rc-line"></div>
    <div class="rc-grand">
        <span>GRAND TOTAL</span>
        <span id="rc-total"></span>
    </div>
    <div class="rc-line"></div>
    <div class="rc-footer">
        <div>Thank you for your purchase!</div>
        <div class="rc-footer-sub">Please come again</div>
    </div>
</div>

<script src="<?php echo base_url ?>dist/js/html5-qrcode.min.js"></script>
<script>
(function(){
    var POS_STATE_KEY = 'cbpos_admin_pos_state';
    var POS_HELD_KEY = 'cbpos_admin_pos_held';
    var POS_CART_KEY = 'cbpos_admin_pos_cart';
    var posCart = [];
    var lastSearchResults = [];
    var searchDebounceTimer = null;
    var barcodeScanner = null;
    var barcodeScanning = false;
    var availableCameras = [];

    function defaultPosState(){
        return { cart: [], customer_name: '', client_id: '', discount_percent: 0, discount_ksh: 0, payment_method: 'Cash', cash_amount: 0, mpesa_amount: 0, due_date: '' };
    }

    function getPosStateFromForm(){
        return {
            cart: posCart,
            customer_name: $('#pos-customer-name').val().trim(),
            client_id: $('#pos-client-id').val(),
            discount_percent: parseFloat($('#pos-discount-pct').val()) || 0,
            discount_ksh: parseFloat($('#pos-discount-ksh').val()) || 0,
            payment_method: $('#pos-payment').val(),
            cash_amount: parseFloat($('#pos-cash-amount').val()) || 0,
            mpesa_amount: parseFloat($('#pos-mpesa-amount').val()) || 0,
            due_date: $('#pos-due-date').val()
        };
    }

    function applyPosState(state){
        posCart = Array.isArray(state.cart) ? state.cart : [];
        $('#pos-customer-name').val(state.customer_name || '');
        $('#pos-client-id').val(state.client_id || '');
        $('#pos-discount-pct').val(state.discount_percent || 0);
        $('#pos-discount-ksh').val(state.discount_ksh || 0);
        $('#pos-payment').val(state.payment_method || 'Cash');
        $('#pos-cash-amount').val(state.cash_amount || 0);
        $('#pos-mpesa-amount').val(state.mpesa_amount || 0);
        $('#pos-due-date').val(state.due_date || '');
        updatePaymentFields();
    }

    function savePosState(){
        sessionStorage.setItem(POS_STATE_KEY, JSON.stringify(getPosStateFromForm()));
    }

    function loadPosState(){
        try {
            var stored = sessionStorage.getItem(POS_STATE_KEY);
            if(stored){
                applyPosState(JSON.parse(stored));
                return;
            }
            var legacy = sessionStorage.getItem(POS_CART_KEY);
            if(legacy){
                posCart = JSON.parse(legacy);
                if(!Array.isArray(posCart)) posCart = [];
                savePosState();
                sessionStorage.removeItem(POS_CART_KEY);
            }
        } catch(e){
            posCart = [];
        }
    }

    function resetPosFormFields(){
        $('#pos-customer-name').val('');
        $('#pos-client-id').val('');
        $('#pos-discount-pct').val(0);
        $('#pos-discount-ksh').val(0);
        $('#pos-payment').val('Cash');
        $('#pos-cash-amount').val(0);
        $('#pos-mpesa-amount').val(0);
        $('#pos-due-date').val('');
        updatePaymentFields();
    }

    function updatePaymentFields(){
        var method = $('#pos-payment').val();
        var isDebt = method === 'Debt';
        var isMixed = method === 'Mixed';
        $('#pos-mixed-cash-wrap, #pos-mixed-mpesa-wrap').toggle(isMixed);
        $('#pos-due-date-wrap').toggle(isDebt);
        $('#pos-client-required').toggle(isDebt);
        $('#pos-walkin-wrap').toggle(!isDebt);
        if(isDebt){
            $('#pos-client-id').prop('required', true);
        } else {
            $('#pos-client-id').prop('required', false);
        }
    }

    function hasHeldSale(){
        return !!sessionStorage.getItem(POS_HELD_KEY);
    }

    function updateHeldButtons(){
        var held = hasHeldSale();
        $('#pos-resume-sale').prop('disabled', !held);
        $('#pos-clear-held').prop('disabled', !held);
    }

    function clearSearchResults(){
        lastSearchResults = [];
        $('#pos-search-results').html('<div class="text-muted text-center py-4">Search or scan to find products</div>');
    }

    function formatPrice(n){
        n = parseFloat(n) || 0;
        return 'Ksh ' + n.toLocaleString(window.APP_LOCALE || 'en-KE', {minimumFractionDigits: 0, maximumFractionDigits: 2});
    }

    function cartSubtotal(){
        var t = 0;
        posCart.forEach(function(item){ t += item.price * item.quantity; });
        return t;
    }

    function getDiscountAmount(){
        var sub = cartSubtotal();
        var pct = parseFloat($('#pos-discount-pct').val()) || 0;
        var ksh = parseFloat($('#pos-discount-ksh').val()) || 0;
        if(pct < 0) pct = 0;
        if(pct > 100) pct = 100;
        if(ksh < 0) ksh = 0;
        return Math.min(sub, (sub * pct / 100) + ksh);
    }

    function getGrandTotal(){
        return Math.max(0, cartSubtotal() - getDiscountAmount());
    }

    function updateTotalsDisplay(){
        $('#pos-subtotal').text(formatPrice(cartSubtotal()));
        $('#pos-discount-total').text(formatPrice(getDiscountAmount()));
        $('#pos-cart-total').text(formatPrice(getGrandTotal()));
        savePosState();
    }

    function renderCart(){
        var $body = $('#pos-cart-body');
        $body.empty();
        if(posCart.length === 0){
            $body.append('<tr id="pos-cart-empty"><td colspan="5" class="text-center text-muted">No items in cart</td></tr>');
            $('#pos-complete-sale').prop('disabled', true);
            $('#pos-hold-sale').prop('disabled', true);
        } else {
            posCart.forEach(function(item, idx){
                var line = item.price * item.quantity;
                var stockHint = item.stock !== undefined ? ' max="'+Math.floor(item.stock)+'"' : '';
                $body.append(
                    '<tr data-idx="'+idx+'">'+
                    '<td><b>'+escapeHtml(item.name)+'</b><br><small>'+escapeHtml(item.variant)+' &middot; '+escapeHtml(item.bname || '')+'</small></td>'+
                    '<td><input type="number" class="form-control form-control-sm pos-price-input" value="'+item.price+'" min="0" step="0.01" data-idx="'+idx+'"></td>'+
                    '<td><input type="number" class="form-control form-control-sm pos-qty-input" value="'+item.quantity+'" min="1"'+stockHint+' data-idx="'+idx+'"></td>'+
                    '<td class="pos-line-total">'+formatPrice(line)+'</td>'+
                    '<td><button type="button" class="btn btn-xs btn-danger pos-remove-item" data-idx="'+idx+'"><i class="fa fa-times"></i></button></td>'+
                    '</tr>'
                );
            });
            $('#pos-complete-sale').prop('disabled', false);
            $('#pos-hold-sale').prop('disabled', false);
        }
        updateTotalsDisplay();
    }

    function updateCartRowTotals(idx){
        if(!posCart[idx]) return;
        var line = posCart[idx].price * posCart[idx].quantity;
        $('tr[data-idx="'+idx+'"] .pos-line-total').text(formatPrice(line));
        updateTotalsDisplay();
    }

    function escapeHtml(str){
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function addToCart(product){
        if(!product || !product.inventory_id) return;
        if(product.stock <= 0){
            alert_toast('Out of stock','error');
            return;
        }
        var found = -1;
        for(var i = 0; i < posCart.length; i++){
            if(posCart[i].inventory_id == product.inventory_id){
                found = i;
                break;
            }
        }
        if(found >= 0){
            var newQty = posCart[found].quantity + 1;
            var maxStock = parseFloat(product.stock);
            if(newQty > maxStock){
                alert_toast('Only '+Math.floor(maxStock)+' available in stock','error');
                return;
            }
            posCart[found].quantity = newQty;
            posCart[found].stock = maxStock;
        } else {
            posCart.push({
                inventory_id: product.inventory_id,
                name: product.name,
                variant: product.variant,
                bname: product.bname,
                price: parseFloat(product.price),
                quantity: 1,
                stock: parseFloat(product.stock)
            });
        }
        renderCart();
        alert_toast('Added to cart','success');
        $('#pos-search').val('');
        clearSearchResults();
        $('#pos-search').focus();
    }

    function holdSale(){
        if(posCart.length === 0){
            alert_toast('Cart is empty','error');
            return;
        }
        sessionStorage.setItem(POS_HELD_KEY, JSON.stringify(getPosStateFromForm()));
        posCart = [];
        resetPosFormFields();
        renderCart();
        updateHeldButtons();
        alert_toast('Sale held','success');
        $('#pos-search').focus();
    }

    function resumeSaleConfirmed(){
        try {
            var held = JSON.parse(sessionStorage.getItem(POS_HELD_KEY));
            applyPosState(held);
            renderCart();
            alert_toast('Held sale restored','success');
            $('#pos-search').focus();
        } catch(e){
            alert_toast('Could not restore held sale','error');
        }
        $('#confirm_modal').modal('hide');
    }

    function resumeSale(){
        if(!hasHeldSale()){
            alert_toast('No held sale','error');
            return;
        }
        if(posCart.length > 0){
            _conf('Replace current cart with held sale?','posResumeSaleConfirmed',[]);
            return;
        }
        resumeSaleConfirmed();
    }

    function clearHeldSale(){
        if(!hasHeldSale()) return;
        _conf('Clear the held sale permanently?','posClearHeldConfirmed',[]);
    }

    function renderSearchResults(items){
        lastSearchResults = items || [];
        var $box = $('#pos-search-results');
        $box.empty();
        if(lastSearchResults.length === 0){
            $box.html('<div class="text-muted text-center py-4">No products found</div>');
            return;
        }
        lastSearchResults.forEach(function(item, idx){
            var stockLabel = item.stock > 0 ? Math.floor(item.stock)+' available' : 'Out of stock';
            var stockClass = item.stock > 0 ? 'text-success' : 'text-danger';
            var barcodeLine = item.barcode ? 'Barcode: '+escapeHtml(item.barcode) : '';
            $box.append(
                '<div class="pos-result-item border-bottom p-2'+(item.stock <= 0 ? ' opacity-50':'')+'" data-idx="'+idx+'">'+
                '<div class="d-flex justify-content-between align-items-start">'+
                '<div class="flex-grow-1 pr-2">'+
                '<div><b>'+escapeHtml(item.name)+'</b></div>'+
                '<div class="pos-result-meta">Variant: '+escapeHtml(item.variant)+'</div>'+
                '<div class="pos-result-meta">Price: '+formatPrice(item.price)+'</div>'+
                '<div class="pos-result-meta '+stockClass+'">Stock: '+stockLabel+'</div>'+
                (barcodeLine ? '<div class="pos-result-meta">'+barcodeLine+'</div>' : '')+
                '</div>'+
                '<span class="badge badge-light border"><i class="fa fa-plus"></i></span>'+
                '</div></div>'
            );
        });
    }

    function fetchProducts(q, callback){
        $.ajax({
            url: _base_url_+'classes/Master.php?f=pos_search_product',
            method: 'POST',
            data: { q: q },
            dataType: 'json',
            error: function(){
                callback([], 'Search failed');
            },
            success: function(resp){
                if(!resp || resp.status !== 'success'){
                    callback([], (resp && resp.msg) ? resp.msg : 'Search failed');
                    return;
                }
                callback(resp.items || [], null);
            }
        });
    }

    function doLiveSearch(){
        var q = $('#pos-search').val().trim();
        if(q.length < 2){
            clearSearchResults();
            return;
        }
        fetchProducts(q, function(items, err){
            if(err){
                $('#pos-search-results').html('<div class="text-muted text-center py-4">'+escapeHtml(err)+'</div>');
                return;
            }
            renderSearchResults(items);
        });
    }

    function submitSearch(){
        var q = $('#pos-search').val().trim();
        if(!q){
            alert_toast('Enter a barcode or product name','error');
            return;
        }
        fetchProducts(q, function(items, err){
            if(err){
                alert_toast(err,'error');
                return;
            }
            var exactBarcode = null;
            for(var i = 0; i < items.length; i++){
                if(items[i].barcode && String(items[i].barcode) === q){
                    exactBarcode = items[i];
                    break;
                }
            }
            if(exactBarcode){
                if(exactBarcode.stock > 0){
                    addToCart(exactBarcode);
                } else {
                    alert_toast('Out of stock','error');
                    renderSearchResults(items);
                }
                return;
            }
            renderSearchResults(items);
            if(items.length === 0){
                alert_toast('No products found','error');
            }
        });
    }

    function receiptVariantLine(variant){
        if(!variant || String(variant).trim().toLowerCase() === 'default') return '';
        return '<br><small>'+escapeHtml(variant)+'</small>';
    }

    function ashReceiptFilename(ref){
        ref = String(ref || '').replace(/[^A-Za-z0-9._-]+/g, '_').replace(/^_+|_+$/g, '');
        if(!ref) ref = 'UNKNOWN';
        return 'Kalmoy_Receipt_' + ref + '.pdf';
    }

    function printReceipt(data){
        $('#rc-ref').text(data.ref_code);
        $('#rc-date').text(data.date_created);
        $('#rc-payment').text(data.payment_method);
        $('#rc-customer').text(data.customer_name || 'Walk-in Customer');
        if(data.is_debt){
            $('#rc-status').text('CREDIT / UNPAID');
            $('#rc-debt-banner-row').show();
            $('#rc-outstanding-row').show();
            $('#rc-outstanding').text(formatPrice(data.outstanding_balance || 0));
            if(data.due_date){
                $('#rc-due-row').show();
                $('#rc-due').text(data.due_date);
            } else {
                $('#rc-due-row').hide();
            }
        } else {
            $('#rc-status').text('PAID');
            $('#rc-debt-banner-row, #rc-outstanding-row, #rc-due-row').hide();
        }
        $('#rc-subtotal').text(formatPrice(data.subtotal || data.amount));
        if((data.discount_total || 0) > 0){
            $('#rc-discount-row').show();
            $('#rc-discount').text('-'+formatPrice(data.discount_total));
        } else {
            $('#rc-discount-row').hide();
        }
        $('#rc-total').text(formatPrice(data.amount));
        var rows = '';
        (data.items || []).forEach(function(item){
            rows += '<tr>'+
                '<td class="rc-col-item">'+escapeHtml(item.name)+receiptVariantLine(item.variant)+'</td>'+
                '<td class="rc-col-qty">'+item.quantity+'</td>'+
                '<td class="rc-col-price">'+formatPrice(item.price)+'</td>'+
                '<td class="rc-col-total">'+formatPrice(item.total)+'</td>'+
                '</tr>';
        });
        $('#rc-items').html(rows);
        var rep = $('#receipt-print').clone();
        rep.attr('id', 'receipt-print-clone').show();
        var receiptStyles = [
            '*{box-sizing:border-box;margin:0;padding:0}',
            'body{font-family:"Courier New",Courier,monospace;font-size:12px;color:#000;background:#fff;padding:8px}',
            '.pos-receipt,#receipt-print-clone{width:80mm;max-width:80mm;margin:0 auto}',
            '.rc-header{text-align:center;padding:4px 0 8px}',
            '.rc-logo{text-align:center;margin-bottom:6px}',
            '.rc-logo img{width:70px;max-width:80px;height:auto;display:inline-block;object-fit:contain;background:transparent}',
            '.rc-store{font-size:14px;font-weight:bold;margin-top:4px;text-transform:uppercase}',
            '.rc-title{font-size:11px;letter-spacing:1px;margin-top:2px}',
            '.rc-line{border-top:1px dashed #000;margin:8px 0}',
            '.rc-meta{font-size:11px}',
            '.rc-row{display:flex;justify-content:space-between;gap:8px;padding:2px 0}',
            '.rc-row span:last-child{text-align:right;max-width:55%}',
            '.rc-table{width:100%;border-collapse:collapse;font-size:11px}',
            '.rc-table th{border-bottom:1px dashed #000;padding:4px 2px;text-align:left;font-weight:bold}',
            '.rc-table td{padding:4px 2px;vertical-align:top;border-bottom:1px dotted #ccc}',
            '.rc-table tr:last-child td{border-bottom:0}',
            '.rc-col-item{width:42%}',
            '.rc-col-qty{width:12%;text-align:center}',
            '.rc-col-price{width:23%;text-align:right}',
            '.rc-col-total{width:23%;text-align:right}',
            '.rc-table small{color:#333}',
            '.rc-grand{display:flex;justify-content:space-between;font-size:13px;font-weight:bold;padding:4px 0}',
            '.rc-summary{font-size:11px}',
            '.rc-footer{text-align:center;padding:10px 0 4px;font-size:11px}',
            '.rc-footer-sub{font-size:10px;margin-top:4px;color:#333}',
            '@media print{body{padding:0}.pos-receipt,#receipt-print-clone{width:80mm}}'
        ].join('');
        var nw = window.open('', '_blank', 'width=360,height=640');
        var receiptTitle = ashReceiptFilename(data.ref_code);
        nw.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>'+escapeHtml(receiptTitle)+'</title>');
        nw.document.write('<style>'+receiptStyles+'</style></head><body>');
        nw.document.write('<div class="pos-receipt">'+rep.html()+'</div>');
        nw.document.write('<script>window.onload=function(){window.focus();window.print();setTimeout(function(){window.close();},2000);};<\/script>');
        nw.document.write('</body></html>');
        nw.document.close();
    }

    function resetBarcodeCameraSelect(){
        $('#barcode-camera-select-wrap').hide();
        $('#barcode-camera-select').empty();
        availableCameras = [];
    }

    function getPreferredCameraId(cameras){
        for(var i = 0; i < cameras.length; i++){
            var label = (cameras[i].label || '').toLowerCase();
            if(label.indexOf('irium') !== -1 || label.indexOf('external') !== -1 || label.indexOf('usb') !== -1){
                return cameras[i].id;
            }
        }
        return cameras[cameras.length - 1].id;
    }

    function populateCameraSelect(cameras){
        var $select = $('#barcode-camera-select');
        $select.empty();
        cameras.forEach(function(camera, index){
            var label = camera.label && camera.label.trim() ? camera.label : ('Camera ' + (index + 1));
            $select.append($('<option>', { value: camera.id, text: label }));
        });
        if(cameras.length > 1){
            $('#barcode-camera-select-wrap').show();
            $select.val(getPreferredCameraId(cameras));
        } else {
            $('#barcode-camera-select-wrap').hide();
        }
    }

    function stopPosScan(showMsg){
        var finish = function(){
            barcodeScanning = false;
            barcodeScanner = null;
            $('#barcode-scanner-wrap').hide();
            resetBarcodeCameraSelect();
            if(showMsg) alert_toast('Scanner stopped','info');
        };
        if(barcodeScanner && barcodeScanning){
            barcodeScanner.stop().then(function(){ barcodeScanner.clear(); }).catch(function(){}).finally(finish);
        } else {
            finish();
        }
    }

    function onBarcodeScanned(decodedText){
        playScannerSound();
        $('#pos-search').val(decodedText);
        stopPosScan(false);
        submitSearch();
    }

    function startBarcodeScan(cameraId){
        var formatsToSupport = [
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E
        ];
        barcodeScanner = new Html5Qrcode('barcode-scanner-reader', { formatsToSupport: formatsToSupport, verbose: false });
        return barcodeScanner.start(cameraId, { fps: 30, qrbox: { width: 420, height: 160 } }, onBarcodeScanned, function(){});
    }

    $(function(){
        loadPosState();
        renderCart();
        updateHeldButtons();

        $('#pos-discount-pct, #pos-discount-ksh').on('input change', function(){
            updateTotalsDisplay();
        });
        $('#pos-customer-name, #pos-payment, #pos-client-id, #pos-cash-amount, #pos-mpesa-amount, #pos-due-date').on('change input', function(){
            savePosState();
        });
        $('#pos-payment').on('change', updatePaymentFields);
        updatePaymentFields();

        $('#pos-hold-sale').click(holdSale);
        $('#pos-resume-sale').click(resumeSale);
        $('#pos-clear-held').click(clearHeldSale);
        window.posResumeSaleConfirmed = resumeSaleConfirmed;
        window.posClearHeldConfirmed = function(){
            sessionStorage.removeItem(POS_HELD_KEY);
            updateHeldButtons();
            $('#confirm_modal').modal('hide');
            alert_toast('Held sale cleared','info');
        };

        function posModalOpen(){
            return $('.modal.show').length > 0;
        }

        function posKeyMatch(e, name, keyCode){
            return e.key === name || e.code === name || e.keyCode === keyCode;
        }

        function clearPosSearch(){
            $('#pos-search').val('');
            clearSearchResults();
        }

        document.addEventListener('keydown', function(e){
            if(posKeyMatch(e, 'F2', 113)){
                e.preventDefault();
                e.stopPropagation();
                var searchEl = document.getElementById('pos-search');
                if(searchEl){
                    searchEl.focus();
                    if(typeof searchEl.select === 'function') searchEl.select();
                }
                return;
            }

            if(posKeyMatch(e, 'F4', 115)){
                if(posModalOpen()) return;
                e.preventDefault();
                e.stopPropagation();
                if(posCart.length > 0 && !$('#pos-complete-sale').prop('disabled')){
                    $('#pos-complete-sale').trigger('click');
                }
                return;
            }

            if(posKeyMatch(e, 'Escape', 27)){
                if(posModalOpen()) return;
                e.preventDefault();
                e.stopPropagation();
                clearPosSearch();
                return;
            }

            if(e.ctrlKey && !e.altKey && !e.metaKey && !e.shiftKey && posKeyMatch(e, 'Backspace', 8)){
                if(posModalOpen()) return;
                if(posCart.length === 0) return;
                e.preventDefault();
                e.stopPropagation();
                $('#pos-clear-cart').trigger('click');
            }
        }, true);

        $('#pos-search-btn').click(function(){
            if($('#pos-search').val().trim().length >= 2){
                doLiveSearch();
            } else {
                submitSearch();
            }
        });
        $('#pos-search').on('input', function(){
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(doLiveSearch, 300);
        });
        $('#pos-search').on('keydown', function(e){
            if(e.key === 'Enter'){
                e.preventDefault();
                clearTimeout(searchDebounceTimer);
                submitSearch();
            }
        });

        $(document).on('click', '.pos-result-item', function(){
            if($(this).hasClass('opacity-50')) return;
            var idx = parseInt($(this).data('idx'), 10);
            if(lastSearchResults[idx]){
                addToCart(lastSearchResults[idx]);
                clearSearchResults();
            }
        });

        $(document).on('change input', '.pos-price-input', function(){
            var idx = parseInt($(this).data('idx'), 10);
            var price = parseFloat($(this).val());
            if(isNaN(price) || price < 0){
                price = 0;
                $(this).val(0);
            }
            if(posCart[idx]){
                posCart[idx].price = price;
                updateCartRowTotals(idx);
            }
        });

        $(document).on('change', '.pos-qty-input', function(){
            var idx = parseInt($(this).data('idx'), 10);
            var qty = parseInt($(this).val(), 10);
            if(isNaN(qty) || qty < 1){
                qty = 1;
                $(this).val(1);
            }
            if(posCart[idx] && qty > posCart[idx].stock){
                qty = Math.floor(posCart[idx].stock);
                $(this).val(qty);
                alert_toast('Only '+qty+' available in stock','error');
            }
            if(posCart[idx]){
                posCart[idx].quantity = qty;
                updateCartRowTotals(idx);
            }
        });

        $(document).on('click', '.pos-remove-item', function(){
            var idx = parseInt($(this).data('idx'), 10);
            posCart.splice(idx, 1);
            renderCart();
        });

        $('#pos-clear-cart').click(function(){
            if(posCart.length === 0) return;
            _conf('Clear all items from the cart?','posClearCart',[]);
        });

        window.posClearCart = function(){
            posCart = [];
            renderCart();
            $('#confirm_modal').modal('hide');
        };

        $('#pos-complete-sale').click(function(){
            if(posCart.length === 0) return;
            _conf('Complete this sale for '+formatPrice(getGrandTotal())+'?','posCompleteSale',[]);
        });

        window.posCompleteSale = function(){
            $('#confirm_modal').modal('hide');
            var method = $('#pos-payment').val();
            if(method === 'Debt' && !$('#pos-client-id').val()){
                alert_toast('Select a registered customer for credit sales','error');
                return;
            }
            if(method === 'Mixed'){
                var total = getGrandTotal();
                var cash = parseFloat($('#pos-cash-amount').val()) || 0;
                var mpesa = parseFloat($('#pos-mpesa-amount').val()) || 0;
                if(Math.abs((cash + mpesa) - total) > 0.01){
                    alert_toast('Mixed payment must equal '+formatPrice(total),'error');
                    return;
                }
            }
            start_loader();
            $.ajax({
                url: _base_url_+'classes/Master.php?f=pos_complete_sale',
                method: 'POST',
                data: {
                    items: JSON.stringify(posCart.map(function(i){
                        return { inventory_id: i.inventory_id, quantity: i.quantity, price: i.price };
                    })),
                    payment_method: method,
                    client_id: $('#pos-client-id').val(),
                    customer_name: $('#pos-customer-name').val().trim(),
                    cash_amount: $('#pos-cash-amount').val(),
                    mpesa_amount: $('#pos-mpesa-amount').val(),
                    due_date: $('#pos-due-date').val(),
                    subtotal: cartSubtotal(),
                    discount_percent: parseFloat($('#pos-discount-pct').val()) || 0,
                    discount_ksh: parseFloat($('#pos-discount-ksh').val()) || 0,
                    amount: getGrandTotal()
                },
                dataType: 'json',
                error: function(){
                    alert_toast('Sale failed','error');
                    end_loader();
                },
                success: function(resp){
                    end_loader();
                    if(!resp || resp.status !== 'success'){
                        alert_toast((resp && resp.msg) ? resp.msg : 'Sale failed','error');
                        return;
                    }
                    posCart = [];
                    resetPosFormFields();
                    renderCart();
                    alert_toast('Sale completed successfully','success');
                    printReceipt(resp);
                }
            });
        };

        $('#start-pos-scan').click(function(){
            if(typeof Html5Qrcode === 'undefined'){
                alert_toast('Barcode scanner library not loaded','error');
                return;
            }
            $('#barcode-scanner-wrap').show();
            Html5Qrcode.getCameras().then(function(cameras){
                if(!cameras || cameras.length === 0){
                    alert_toast('No camera found','error');
                    return;
                }
                availableCameras = cameras;
                populateCameraSelect(cameras);
                var cameraId = cameras.length > 1 ? getPreferredCameraId(cameras) : cameras[0].id;
                return startBarcodeScan(cameraId);
            }).then(function(){
                barcodeScanning = true;
            }).catch(function(err){
                alert_toast('Could not start scanner','error');
                console.log(err);
            });
        });

        $('#stop-pos-scan').click(function(){ stopPosScan(true); });

        $('#barcode-camera-select').change(function(){
            if(!$('#barcode-scanner-wrap').is(':visible')) return;
            var cameraId = $(this).val();
            if(barcodeScanner && barcodeScanning){
                barcodeScanner.stop().then(function(){
                    barcodeScanner.clear();
                    barcodeScanning = false;
                    return startBarcodeScan(cameraId);
                }).then(function(){ barcodeScanning = true; });
            }
        });
    });
})();
</script>
