<?php
/**
 * Client demo / sample storefront catalog.
 * Preserves the original homepage shop experience at ?p=storefront
 */
$brands = isset($_GET['b']) ? json_decode(urldecode($_GET['b'])) : array();
if (!is_array($brands)) {
    $brands = array();
}
$placeholder_img = base_url . 'dist/img/no-image-available.png';
$store_name = trim((string) $_settings->info('name'));
if ($store_name === '') {
    $store_name = 'Demo Store';
}
?>
<link rel="stylesheet" href="<?php echo base_url ?>assets/css/storefront-home.css">
<section class="storefront-home">
    <div class="container-fluid storefront-home-container">
        <div class="alert alert-light border mb-3 py-2 px-3 small storefront-demo-notice" role="status">
            <i class="fas fa-flask text-muted mr-1"></i>
            <strong>Demo storefront</strong> - sample catalog for platform evaluation.
            Product data is for demonstration only.
            <a href="./" class="ml-1">Back to Kalmoy POS</a>
        </div>
        <button type="button" class="brand-filter-toggle d-lg-none" id="brand-filter-toggle" aria-expanded="false" aria-controls="brand-filter-panel">
            <i class="fas fa-filter"></i> Filter by brand
        </button>
        <div class="row">
            <aside class="col-lg-3 col-xl-2 storefront-brands-panel" id="brand-filter-panel">
                <div class="brand-filter-card">
                    <h2 class="brand-filter-title">Brands</h2>
                    <ul class="list-group brand-filter-list list-group-flush">
                        <a href="" class="list-group-item list-group-item-action">
                            <div class="icheck-primary d-inline">
                                <input type="checkbox" id="brandAll">
                                <label for="brandAll">All brands</label>
                            </div>
                        </a>
                        <?php
                        $qry = $conn->query("SELECT * FROM brands where status =1 order by name asc");
                        while ($row = $qry->fetch_assoc()):
                        ?>
                        <li class="list-group-item list-group-item-action">
                            <div class="icheck-primary d-inline">
                                <input type="checkbox" id="brand-item-<?php echo $row['id'] ?>" <?php echo in_array($row['id'], $brands) ? 'checked' : '' ?> class="brand-item" value="<?php echo $row['id'] ?>">
                                <label for="brand-item-<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['name']) ?></label>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </aside>
            <main class="col-lg-9 col-xl-10 storefront-main">
                <div class="storefront-hero">
                    <?php
                    $upload_path = 'uploads/banner';
                    $banner_files = array();
                    if (is_dir(base_app . $upload_path)) {
                        $file = scandir(base_app . $upload_path);
                        foreach ($file as $img) {
                            if (!in_array($img, array('.', '..'))) {
                                $banner_files[] = $img;
                            }
                        }
                    }
                    if (!empty($banner_files)):
                    ?>
                    <div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
                        <div class="carousel-inner">
                            <?php
                            $_i = 0;
                            foreach ($banner_files as $img):
                                $_i++;
                            ?>
                            <div class="carousel-item h-100 <?php echo $_i == 1 ? 'active' : '' ?>">
                                <img src="<?php echo validate_image($upload_path . '/' . $img) ?>" class="d-block w-100 h-100" alt="<?php echo htmlspecialchars($img) ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($banner_files) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-target="#carouselExampleControls" data-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-target="#carouselExampleControls" data-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="storefront-hero-placeholder">
                        <div>
                            <h2><?php echo htmlspecialchars($store_name) ?></h2>
                            <p>Sample online catalog powered by Kalmoy POS</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php
                $where = '';
                if (count($brands) > 0) {
                    $where = ' and p.brand_id in (' . implode(',', array_map('intval', $brands)) . ') ';
                }
                $products = $conn->query("SELECT p.*,b.name as bname,c.category FROM `products` p inner join brands b on p.brand_id = b.id inner join categories c on p.category_id = c.id where p.status = 1 {$where} order by rand() ");
                $product_count = $products ? $products->num_rows : 0;
                ?>
                <div class="storefront-products">
                    <div class="storefront-products-header">
                        <h2>Products</h2>
                        <span class="text-muted"><?php echo (int) $product_count ?> item<?php echo $product_count === 1 ? '' : 's' ?></span>
                    </div>
                    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4">
                        <?php
                        if ($products):
                        while ($row = $products->fetch_assoc()):
                            $upload_path = base_app . '/uploads/product_' . $row['id'];
                            $img = '';
                            if (is_dir($upload_path)) {
                                $fileO = scandir($upload_path);
                                if (isset($fileO[2])) {
                                    $img = 'uploads/product_' . $row['id'] . '/' . $fileO[2];
                                }
                            }
                            foreach ($row as $k => $v) {
                                $row[$k] = trim(stripslashes($v));
                            }
                            $inventory = $conn->query("SELECT distinct(`price`) FROM inventory where product_id = " . (int) $row['id'] . " order by `price` asc");
                            $inv = array();
                            while ($ir = $inventory->fetch_assoc()) {
                                $inv[] = format_price($ir['price']);
                            }
                            $price = '';
                            if (isset($inv[0])) {
                                $price .= $inv[0];
                            }
                            if (count($inv) > 1) {
                                $price .= ' ~ ' . $inv[count($inv) - 1];
                            }
                            if ($price === '') {
                                $price = 'Price on request';
                            }
                            $has_image = ($img !== '' && is_file(base_app . $img));
                            $img_src = validate_image($img);
                        ?>
                        <div class="col mb-4">
                            <a class="card product-item storefront-product-card text-reset text-decoration-none" href=".?p=view_product&id=<?php echo md5($row['id']) ?>">
                                <div class="storefront-product-image product-holder <?php echo $has_image ? '' : 'is-placeholder' ?>">
                                    <img class="product-cover w-100"
                                         src="<?php echo $img_src ?>"
                                         alt="<?php echo htmlspecialchars($row['name']) ?>"
                                         loading="lazy"
                                         onerror="this.onerror=null;this.src='<?php echo $placeholder_img ?>';this.closest('.storefront-product-image').classList.add('is-placeholder');">
                                    <?php if (!$has_image): ?>
                                    <span class="storefront-no-image-label">No image</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body storefront-product-body">
                                    <h5 class="storefront-product-title"><?php echo htmlspecialchars($row['name']) ?></h5>
                                    <p class="storefront-product-price mb-0"><?php echo $price ?></p>
                                    <div class="storefront-product-meta">
                                        <span class="badge"><?php echo htmlspecialchars($row['bname']) ?></span>
                                        <span class="badge"><?php echo htmlspecialchars($row['category']) ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php
                        endwhile;
                        endif;
                        ?>
                    </div>
                    <?php if ($product_count === 0): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-box-open fa-2x mb-3 d-block"></i>
                        <p class="mb-0">No products match the selected brands.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</section>
<script>
    function _filter(){
        var brands = []
        $('.brand-item:checked').each(function(){
            brands.push($(this).val())
        })
        var checked = $('.brand-item:checked').length
        var total = $('.brand-item').length
        if(checked == total)
            location.href="./?p=storefront";
        else
            location.href="./?p=storefront&b="+encodeURI(JSON.stringify(brands));
    }
    function check_filter(){
        var checked = $('.brand-item:checked').length
        var total = $('.brand-item').length
        if(checked == total){
            $('#brandAll').prop('checked', true)
        }else{
            $('#brandAll').prop('checked', false)
        }
        if('<?php echo isset($_GET['b']) ? '1' : '' ?>' == '')
            $('#brandAll,.brand-item').prop('checked', true)
    }
    $(function(){
        check_filter()
        $('#brandAll').change(function(){
            if($(this).is(':checked') == true){
                $('.brand-item').prop('checked', true)
            }else{
                $('.brand-item').prop('checked', false)
            }
            _filter()
        })
        $('.brand-item').change(function(){
            _filter()
        })
        $('#brand-filter-toggle').on('click', function(){
            var $panel = $('#brand-filter-panel');
            var open = $panel.toggleClass('brand-filter-open').hasClass('brand-filter-open');
            $(this).attr('aria-expanded', open ? 'true' : 'false');
        })
    })
</script>
