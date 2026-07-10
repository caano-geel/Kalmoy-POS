<?php
require_once __DIR__.'/../../config.php';
if(!isset($_SESSION['userdata']) || (int)$_SESSION['userdata']['login_type'] !== 1){
    echo '<div class="container-fluid"><p class="text-danger p-3">Access denied.</p></div>';
    return;
}
if(!admin_cashier_can('inventory_manage')){
    echo '<div class="container-fluid"><p class="text-danger p-3">Access denied.</p></div>';
    return;
}
$can_cost = admin_can_view_profit();
$export_url = app_export_url('inventory', 'xlsx');
$template_url = app_export_url('inventory_template', 'xlsx');
?>
<style>
#uni_modal.import-excel-modal .modal-dialog {
    max-width: 96vw;
    width: 1100px;
}
#uni_modal.import-excel-modal .modal-body {
    max-height: calc(100vh - 170px);
    overflow-y: auto;
    padding-top: 0.75rem;
    padding-bottom: 0.5rem;
}
.import-stats-strip {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 6px;
    margin-bottom: 10px;
}
.import-stat-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 6px 8px;
    text-align: center;
}
.import-stat-card .num {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.2;
}
.import-stat-card .lbl {
    font-size: 0.62rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.import-stat-card.stat-read .num { color: #495057; }
.import-stat-card.stat-create .num { color: #28a745; }
.import-stat-card.stat-update .num { color: #007bff; }
.import-stat-card.stat-skip .num { color: #dc3545; }
.import-stat-card.stat-brand .num,
.import-stat-card.stat-category .num { color: #fd7e14; }
.import-upload-box {
    background: #f8f9fa;
    border: 1px dashed #ced4da;
    border-radius: 6px;
    padding: 10px 12px;
    margin-bottom: 10px;
}
.import-upload-box .form-group { margin-bottom: 0; }
.import-upload-hint {
    font-size: 0.78rem;
    color: #6c757d;
    margin: 0;
}
.import-preview-scroll {
    overflow-x: auto;
    overflow-y: auto;
    max-height: 240px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    margin-bottom: 10px;
}
.import-preview-table {
    width: 100%;
    min-width: 1060px;
    table-layout: fixed;
    margin-bottom: 0 !important;
}
.import-preview-table th,
.import-preview-table td {
    vertical-align: middle !important;
    padding: 6px 8px !important;
    font-size: 0.82rem;
}
.import-preview-table .col-row { width: 42px; }
.import-preview-table .col-action { width: 130px; }
.import-preview-table .col-product { width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.import-preview-table .col-barcode { width: 120px; white-space: nowrap; }
.import-preview-table .col-brand { width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.import-preview-table .col-category { width: 130px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.import-preview-table .col-variant { width: 72px; white-space: nowrap; }
.import-preview-table .col-qty,
.import-preview-table .col-price,
.import-preview-table .col-cost { width: 68px; white-space: nowrap; }
.import-preview-table .col-changes { width: 180px; }
.import-action-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.7rem;
    padding: 3px 7px;
    border-radius: 4px;
    white-space: nowrap;
}
.import-action-create { background: #d4edda; color: #155724; }
.import-action-update { background: #cce5ff; color: #004085; }
.import-action-warning { background: #fff3cd; color: #856404; }
.import-change-stack {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.import-change-chip {
    display: flex;
    flex-direction: column;
    gap: 1px;
    padding: 4px 7px;
    border-radius: 4px;
    font-size: 0.7rem;
    background: #f1f3f5;
    border: 1px solid #dee2e6;
    line-height: 1.25;
}
.import-change-chip .chip-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.import-change-chip .chip-values {
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}
.import-change-chip .from { color: #868e96; text-decoration: line-through; }
.import-change-chip .arrow { color: #adb5bd; font-size: 0.6rem; }
.import-change-chip .to { font-weight: 700; color: #212529; }
.import-change-new {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    background: #d4edda;
    color: #155724;
    font-size: 0.72rem;
    white-space: nowrap;
}
.import-skipped-card {
    padding: 10px 12px;
    margin-bottom: 8px;
    background: #fff5f5;
    border: 1px solid #f5c6cb;
    border-left: 4px solid #dc3545;
    border-radius: 6px;
}
.import-skipped-card .skip-row-title {
    font-weight: 700;
    color: #721c24;
    margin-bottom: 4px;
    font-size: 0.85rem;
}
.import-skipped-card .skip-field {
    font-size: 0.8rem;
    margin-bottom: 2px;
}
.import-skipped-card .skip-reason {
    font-size: 0.8rem;
    margin: 6px 0 3px;
    color: #495057;
}
.import-skipped-card .skip-actions {
    font-size: 0.75rem;
    color: #6c757d;
    margin: 0;
    padding-left: 1.1rem;
}
.import-empty-card {
    text-align: center;
    padding: 20px 16px;
    background: #f8f9fa;
    border: 1px dashed #ced4da;
    border-radius: 6px;
    margin-bottom: 10px;
}
.import-empty-card .empty-icon { font-size: 1.6rem; color: #adb5bd; margin-bottom: 6px; }
.import-empty-card h6 { font-weight: 600; margin-bottom: 4px; font-size: 0.95rem; }
.import-empty-card p { margin-bottom: 3px; color: #6c757d; font-size: 0.82rem; }
.import-barcode-warn { color: #856404; font-size: 0.7rem; margin-top: 2px; }
.import-preview-summary {
    font-size: 0.8rem;
    padding: 6px 10px !important;
    margin-bottom: 8px !important;
}
#uni_modal.import-excel-modal .modal-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 6px;
    padding: 0.5rem 0.75rem;
}
#uni_modal.import-excel-modal .modal-footer .btn,
#uni_modal.import-excel-modal .modal-footer a.btn { margin: 0; }
</style>
<div class="container-fluid px-0" id="inventory-import-root">
    <div id="import-stats-strip" class="import-stats-strip"></div>
    <div id="import-step-upload" class="import-upload-box">
        <div class="form-group">
            <label for="inventory-import-file" class="font-weight-bold mb-1">Select Excel or CSV file</label>
            <input type="file" id="inventory-import-file" class="form-control-file form-control-sm" accept=".csv,.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv">
        </div>
        <p class="import-upload-hint mb-0">
            <i class="fas fa-info-circle"></i> Quantity = available stock. Barcode column must be TEXT in Excel.
        </p>
    </div>
    <div id="import-step-preview" style="display:none;">
        <div id="import-preview-summary" class="alert alert-light border import-preview-summary mb-2">
            <i class="fas fa-search text-muted"></i> Review rows below before confirming.
        </div>
        <div id="import-empty-card" class="import-empty-card" style="display:none;"></div>
        <div id="import-preview-table-wrap" class="import-preview-scroll">
            <table class="table table-sm table-bordered import-preview-table" id="import-preview-table">
                <thead class="thead-light">
                    <tr>
                        <th class="col-row">Row</th>
                        <th class="col-action">Action</th>
                        <th class="col-product">Product</th>
                        <th class="col-barcode">Barcode</th>
                        <th class="col-brand">Brand</th>
                        <th class="col-category">Category</th>
                        <th class="col-variant">Variant</th>
                        <th class="col-qty text-right">Qty</th>
                        <th class="col-price text-right">Price</th>
                        <?php if($can_cost): ?><th class="col-cost text-right">Cost</th><?php endif; ?>
                        <th class="col-changes">Changes</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="import-skipped-wrap" style="display:none;">
            <h6 class="text-danger font-weight-bold mb-2" style="font-size:0.85rem;"><i class="fas fa-times-circle"></i> Skipped rows</h6>
            <div id="import-skipped-list"></div>
        </div>
    </div>
</div>
<script>
(function(){
    var importToken = '';
    var lastPreviewRows = [];
    var exportUrl = <?php echo json_encode($export_url); ?>;
    var templateUrl = <?php echo json_encode($template_url); ?>;

    function escHtml(s){
        return $('<div>').text(s == null ? '' : s).html();
    }

    function closeImportModal(){
        $('#uni_modal').modal('hide');
        $('#confirm_modal').modal('hide');
    }

    function setupImportModal(){
        $('#uni_modal').addClass('import-excel-modal');
        renderStats({});
        renderImportFooter('upload');
    }

    function renderImportFooter(step){
        var html = '';
        html += '<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>';
        html += '<a href="'+exportUrl+'" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener"><i class="fas fa-download"></i> Download Existing Products</a>';
        html += '<a href="'+templateUrl+'" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener"><i class="fas fa-file-download"></i> Download Simple Template</a>';
        if(step === 'preview'){
            html += '<button type="button" class="btn btn-secondary btn-sm" id="inventory-import-back-btn"><i class="fas fa-arrow-left"></i> Back</button>';
            html += '<button type="button" class="btn btn-success btn-sm" id="inventory-import-confirm-btn"><i class="fas fa-check"></i> Confirm Import</button>';
        }else{
            html += '<button type="button" class="btn btn-primary btn-sm" id="inventory-import-upload-btn"><i class="fas fa-file-upload"></i> Upload &amp; Preview</button>';
        }
        $('#uni_modal .modal-footer').html(html).show();
    }

    function actionBadge(row){
        var type = row.action_type || (row.product_action === 'create' ? 'create' : 'update');
        var icons = { create: 'fa-plus-circle', update: 'fa-sync', warning: 'fa-exclamation-triangle', skip: 'fa-times-circle' };
        var labels = { create: 'import-action-create', update: 'import-action-update', warning: 'import-action-warning', skip: 'import-action-skip' };
        return '<span class="import-action-badge '+(labels[type] || 'import-action-update')+'"><i class="fas '+(icons[type] || 'fa-circle')+'"></i> '+escHtml(row.action)+'</span>';
    }

    function renderChanges(changes){
        if(!changes || !changes.length){
            return '<span class="import-change-new"><i class="fas fa-star"></i> New record</span>';
        }
        return '<div class="import-change-stack">' + changes.map(function(c){
            return '<div class="import-change-chip">' +
                '<span class="chip-label">'+escHtml(c.field)+'</span>' +
                '<span class="chip-values"><span class="from">'+escHtml(c.from)+'</span><span class="arrow">→</span><span class="to">'+escHtml(c.to)+'</span></span>' +
                '</div>';
        }).join('') + '</div>';
    }

    function renderStats(stats){
        stats = stats || {};
        var cards = [
            { cls: 'stat-read', num: stats.rows_read || 0, lbl: 'Rows Read' },
            { cls: 'stat-create', num: stats.new_products || 0, lbl: 'New Products' },
            { cls: 'stat-update', num: stats.updated_products || 0, lbl: 'Updated Products' },
            { cls: 'stat-skip', num: stats.skipped_rows || 0, lbl: 'Skipped Rows' },
            { cls: 'stat-brand', num: stats.new_brands || 0, lbl: 'New Brands' },
            { cls: 'stat-category', num: stats.new_categories || 0, lbl: 'New Categories' }
        ];
        $('#import-stats-strip').html(cards.map(function(c){
            return '<div class="import-stat-card '+c.cls+'"><div class="num">'+c.num+'</div><div class="lbl">'+c.lbl+'</div></div>';
        }).join(''));
    }

    function parseSkippedReason(s){
        var reason = (s.reason || '').trim();
        var product = s.product_name || '';
        var barcode = (s.imported_barcode && s.imported_barcode !== '—') ? s.imported_barcode : '';
        var existingProduct = s.existing_product || '';
        var existingBarcode = (s.existing_barcode && s.existing_barcode !== '(none)') ? s.existing_barcode : '';
        var reasonText = reason.replace(/^Skipped:\s*/i, '');
        var suggested = ['Use the existing product name', 'Or assign a new barcode'];
        var m1 = reason.match(/imported barcode "([^"]+)" already belongs to "([^"]+)"/i);
        if(m1){
            reasonText = 'This barcode already belongs to:';
            existingProduct = m1[2];
            existingBarcode = m1[1];
            barcode = m1[1];
        }
        var m2 = reason.match(/product name "([^"]+)" already exists with barcode ([^.]+)/i);
        if(m2){
            reasonText = 'This product name already exists with a different barcode:';
            existingProduct = m2[1];
            existingBarcode = m2[2].trim();
            suggested = ['Use the existing barcode for this product', 'Or use a different product name'];
        }
        if(reason && reason.indexOf('required') !== -1) suggested = ['Fix the missing or invalid field and re-upload'];
        if(reason && reason.indexOf('Duplicate barcode in file') !== -1) suggested = ['Remove duplicate barcodes from the file'];
        return { product: product, barcode: barcode, reasonText: reasonText, existingProduct: existingProduct, existingBarcode: existingBarcode, suggested: suggested };
    }

    function renderSkippedCard(s){
        var info = parseSkippedReason(s);
        var existingLine = '';
        if(info.existingProduct){
            existingLine = escHtml(info.existingProduct);
            if(info.existingBarcode) existingLine += ' ('+escHtml(info.existingBarcode)+')';
        }
        return '<div class="import-skipped-card">' +
            '<div class="skip-row-title"><i class="fas fa-times-circle text-danger"></i> Row '+s.row+'</div>' +
            '<div class="skip-field"><strong>Product:</strong> '+escHtml(info.product || '—')+'</div>' +
            '<div class="skip-field"><strong>Barcode:</strong> '+escHtml(info.barcode || '—')+'</div>' +
            '<div class="skip-reason"><strong>Reason:</strong> '+escHtml(info.reasonText)+'</div>' +
            (existingLine ? '<div class="skip-field">'+existingLine+'</div>' : '') +
            '<div class="skip-reason"><strong>Suggested action:</strong></div>' +
            '<ul class="skip-actions">'+info.suggested.map(function(a){ return '<li>'+escHtml(a)+'</li>'; }).join('')+'</ul>' +
            '</div>';
    }

    function showEmptyPreview(skippedCount){
        var rowWord = skippedCount === 1 ? 'row was' : 'rows were';
        $('#import-empty-card').html(
            '<div class="empty-icon"><i class="fas fa-inbox"></i></div>' +
            '<h6>No products available for import.</h6>' +
            '<p>'+skippedCount+' '+rowWord+' skipped.</p>' +
            '<p>Review skipped rows below.</p>'
        ).show();
        $('#import-preview-table-wrap').hide();
    }

    function showImportSuccessToast(summary){
        summary = summary || {};
        var created = summary.products_created || 0;
        var updated = summary.products_updated || 0;
        var skipped = summary.rows_skipped || 0;
        var html = 'Created: <strong>'+created+'</strong><br>Updated: <strong>'+updated+'</strong><br>Skipped: <strong>'+skipped+'</strong>';
        if(typeof Swal !== 'undefined'){
            Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true }).fire({
                icon: 'success',
                title: 'Import successful',
                html: html
            });
        }else if(typeof alert_toast === 'function'){
            alert_toast('Import successful. Created: '+created+', Updated: '+updated+', Skipped: '+skipped, 'success');
        }
    }

    function storeImportHighlight(rows){
        if(!rows || !rows.length) return;
        sessionStorage.setItem('inventory_import_highlight', JSON.stringify({
            names: rows.map(function(r){ return r.product_name; }),
            barcodes: rows.map(function(r){ return r.barcode || r.barcode_display || ''; }),
            scrollToFirst: true
        }));
    }

    function resetToUpload(){
        importToken = '';
        lastPreviewRows = [];
        $('#import-step-preview').hide();
        $('#import-step-upload').show();
        $('#import-empty-card').hide();
        $('#import-preview-table-wrap').show();
        $('#import-skipped-wrap').hide();
        renderStats({});
        renderImportFooter('upload');
    }

    $(document).on('click', '#inventory-import-upload-btn', function(){
        var fileInput = document.getElementById('inventory-import-file');
        if(!fileInput || !fileInput.files.length){
            alert_toast('Please choose a file first.','error');
            return;
        }
        var fd = new FormData();
        fd.append('import_file', fileInput.files[0]);
        start_loader();
        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=inventory_import_preview',
            data: fd, cache: false, contentType: false, processData: false, method: 'POST', dataType: 'json',
            error: function(){ alert_toast('Upload failed.','error'); end_loader(); },
            success: function(resp){
                end_loader();
                if(resp.status !== 'success'){
                    alert_toast(resp.msg || 'Validation failed.','error');
                    return;
                }
                importToken = resp.token;
                lastPreviewRows = resp.preview || [];
                renderStats(resp.stats);
                var skipped = resp.skipped ? resp.skipped.length : 0;
                var ready = resp.stats ? resp.stats.ready_to_import : 0;
                $('#import-preview-summary').html(
                    '<i class="fas fa-clipboard-check text-success"></i> <strong>Preview ready</strong> — ' +
                    ready + ' row(s) will import.' +
                    (skipped > 0 ? ' <span class="text-danger"><i class="fas fa-exclamation-circle"></i> '+skipped+' skipped.</span>' : '')
                );
                var $tb = $('#import-preview-table tbody').empty();
                if(!lastPreviewRows.length){
                    showEmptyPreview(skipped);
                }else{
                    $('#import-empty-card').hide();
                    $('#import-preview-table-wrap').show();
                    lastPreviewRows.forEach(function(row){
                        var bc = escHtml(row.barcode_display || row.barcode || '—');
                        if(row.barcode_warning){
                            bc += '<div class="import-barcode-warn"><i class="fas fa-exclamation-triangle"></i> '+escHtml(row.barcode_warning)+'</div>';
                        }
                        $tb.append('<tr>' +
                            '<td>'+row.row+'</td>' +
                            '<td>'+actionBadge(row)+'</td>' +
                            '<td class="col-product" title="'+escHtml(row.product_name)+'">'+escHtml(row.product_name)+'</td>' +
                            '<td class="col-barcode">'+bc+'</td>' +
                            '<td class="col-brand" title="'+escHtml(row.brand)+'">'+escHtml(row.brand)+'</td>' +
                            '<td class="col-category" title="'+escHtml(row.category)+'">'+escHtml(row.category)+'</td>' +
                            '<td>'+escHtml(row.variant)+'</td>' +
                            '<td class="text-right">'+escHtml(row.quantity)+'</td>' +
                            '<td class="text-right">'+escHtml(row.retail_price)+'</td>' +
                            <?php if($can_cost): ?>'<td class="text-right">'+escHtml(row.unit_cost)+'</td>' +<?php endif; ?>
                            '<td>'+renderChanges(row.changes)+'</td>' +
                            '</tr>');
                    });
                }
                if(skipped > 0){
                    $('#import-skipped-list').empty();
                    resp.skipped.forEach(function(s){ $('#import-skipped-list').append(renderSkippedCard(s)); });
                    $('#import-skipped-wrap').show();
                }else{
                    $('#import-skipped-wrap').hide();
                }
                $('#import-step-upload').hide();
                $('#import-step-preview').show();
                renderImportFooter('preview');
            }
        });
    });

    $(document).on('click', '#inventory-import-back-btn', resetToUpload);

    function runImportCommit(){
        if(!importToken){ alert_toast('Import session expired. Upload again.','error'); return; }
        var $btn = $('#inventory-import-confirm-btn');
        if($btn.data('importing')) return;
        $btn.data('importing', true).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing&hellip;');
        start_loader();
        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=inventory_import_commit',
            method: 'POST', data: { token: importToken }, dataType: 'json',
            error: function(){
                end_loader();
                $btn.data('importing', false).prop('disabled', false).html('<i class="fas fa-check"></i> Confirm Import');
                alert_toast('Import failed. Please try again.','error');
            },
            success: function(resp){
                end_loader();
                if(!(resp && (resp.success === true || resp.status === 'success'))){
                    $btn.data('importing', false).prop('disabled', false).html('<i class="fas fa-check"></i> Confirm Import');
                    alert_toast((resp && (resp.message || resp.msg)) || 'Import failed.','error');
                    return;
                }
                storeImportHighlight(lastPreviewRows);
                importToken = '';
                closeImportModal();
                showImportSuccessToast(resp.summary || {});
                setTimeout(function(){
                    window.location.href = _base_url_ + (resp.redirect || 'admin/?page=inventory').replace(/^\//, '');
                }, 500);
            }
        });
    }

    $(document).on('click', '#inventory-import-confirm-btn', function(e){ e.preventDefault(); runImportCommit(); });

    setupImportModal();
    $('#uni_modal').on('hidden.bs.modal', function(){
        if(!$('#uni_modal').hasClass('import-excel-modal')) return;
        $('#uni_modal').removeClass('import-excel-modal');
        $('#uni_modal .modal-dialog').removeClass('modal-xl');
        $('#uni_modal .modal-footer').html(
            '<button type="button" class="btn btn-primary" id="submit" onclick="$(\'#uni_modal form\').submit()">Save</button>' +
            '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>'
        ).show();
    });
})();
</script>
