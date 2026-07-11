<?php
if (!isset($is_platform_home)) {
    $is_platform_home = false;
}
if (!isset($is_platform_public)) {
    $is_platform_public = false;
}
$platform_nav_base = $is_platform_home ? '' : './';
?>
<?php if ($is_platform_public): ?>
<div class="platform-header fixed-top">
    <div class="container platform-header-inner">
        <div class="platform-navbar-bar">
            <button type="button" class="platform-menu-toggle" id="platformMenuToggle" aria-label="Open navigation" aria-expanded="false" aria-controls="platformNav">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <a class="platform-brand platform-navbar-brand" href="./">
                <img src="<?php echo base_url ?>assets/img/kalmoy_logo.png" alt="" class="platform-navbar-logo" width="16" height="16" decoding="async">
                <span class="platform-brand-text">
                    <span class="platform-brand-title">Kalmoy POS</span>
                    <span class="platform-brand-sub">Kalmoy Tech Solutions</span>
                </span>
            </a>
        </div>
        <div class="platform-navbar-drawer" id="platformNav" role="navigation" aria-label="Main navigation" aria-hidden="true">
            <div class="platform-drawer-head">
                <span class="platform-drawer-title">Menu</span>
                <button type="button" class="platform-drawer-close" id="platformNavClose" aria-label="Close navigation">&times;</button>
            </div>
            <ul class="platform-navbar-menu">
                <li class="platform-nav-item"><a class="platform-nav-link" href="<?php echo $platform_nav_base ?>#features" data-nav-section="features">Features</a></li>
                <li class="platform-nav-item"><a class="platform-nav-link" href="<?php echo $platform_nav_base ?>#industries" data-nav-section="industries">Industries</a></li>
                <li class="platform-nav-item"><a class="platform-nav-link" href="<?php echo $platform_nav_base ?>#why" data-nav-section="why">Why Kalmoy</a></li>
                <li class="platform-nav-item"><a class="platform-nav-link" href="<?php echo $platform_nav_base ?>#contact" data-nav-section="contact">Contact</a></li>
                <li class="platform-nav-item"><a class="platform-nav-link" href="<?php echo $platform_nav_base ?>#about" data-nav-section="about">About</a></li>
            </ul>
            <div class="platform-nav-ctas">
                <a href="<?php echo base_url ?>admin/login.php" class="platform-btn platform-btn-nav-outline">Client Login</a>
                <a href="<?php echo $platform_nav_base ?>#contact" class="platform-btn platform-btn-nav-getstarted" data-nav-section="contact">Get Started</a>
            </div>
        </div>
    </div>
</div>
<div class="platform-nav-overlay" id="platformNavOverlay" aria-hidden="true"></div>
<div class="platform-navbar-spacer" aria-hidden="true"></div>
<script>
(function () {
    var drawerTransitionMs = 280;

    function initPlatformNav() {
        var navDrawer = document.getElementById('platformNav');
        var menuToggle = document.getElementById('platformMenuToggle');
        var navOverlay = document.getElementById('platformNavOverlay');
        var navClose = document.getElementById('platformNavClose');
        if (!navDrawer || !menuToggle) return;
        if (navDrawer.dataset.platformNavBound === '1') return;
        navDrawer.dataset.platformNavBound = '1';

        var sectionIds = ['features', 'industries', 'why', 'about', 'contact'];
        var sections = sectionIds.map(function (id) {
            return document.getElementById(id);
        }).filter(Boolean);

        function isMobileNav() {
            return window.innerWidth < 992;
        }

        function getHeaderHeight() {
            var header = document.querySelector('.platform-header');
            if (header) {
                return header.getBoundingClientRect().height + 8;
            }
            var navH = getComputedStyle(document.documentElement).getPropertyValue('--kp-nav-h').trim();
            return (parseFloat(navH) || 48) + 8;
        }

        function getHashFromHref(href) {
            if (!href) return null;
            var hashIndex = href.indexOf('#');
            if (hashIndex === -1) return null;
            return href.substring(hashIndex);
        }

        function restorePageScroll() {
            document.body.classList.remove('platform-nav-open');
            document.documentElement.classList.remove('platform-nav-open');
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        }

        function closeDrawer(afterClose) {
            navDrawer.classList.remove('is-open');
            if (navOverlay) {
                navOverlay.classList.remove('is-visible');
                navOverlay.setAttribute('aria-hidden', 'true');
            }
            restorePageScroll();
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'Open navigation');
            navDrawer.setAttribute('aria-hidden', 'true');
            navDrawer.style.pointerEvents = '';

            if (typeof afterClose === 'function') {
                window.setTimeout(afterClose, drawerTransitionMs + 20);
            }
        }

        function openDrawer() {
            if (!isMobileNav()) return;
            navDrawer.classList.add('is-open');
            if (navOverlay) {
                navOverlay.classList.add('is-visible');
                navOverlay.setAttribute('aria-hidden', 'false');
            }
            document.body.classList.add('platform-nav-open');
            menuToggle.setAttribute('aria-expanded', 'true');
            menuToggle.setAttribute('aria-label', 'Close navigation');
            navDrawer.setAttribute('aria-hidden', 'false');
        }

        function scrollToHashTarget(hash) {
            var target = document.querySelector(hash);
            if (!target) return false;

            var headerHeight = getHeaderHeight();
            var targetTop = target.getBoundingClientRect().top + window.pageYOffset - headerHeight;

            window.scrollTo({
                top: Math.max(0, targetTop),
                behavior: 'smooth'
            });

            if (window.history && window.history.pushState) {
                window.history.pushState(null, '', hash);
            } else {
                window.location.hash = hash;
            }

            var sectionId = hash.replace('#', '');
            if (sectionId) {
                setActiveSection(sectionId);
            }

            return true;
        }

        function setActiveSection(id) {
            navDrawer.querySelectorAll('[data-nav-section]').forEach(function (el) {
                el.classList.toggle('is-active', el.getAttribute('data-nav-section') === id);
            });
        }

        menuToggle.addEventListener('click', function () {
            if (!isMobileNav()) return;
            if (navDrawer.classList.contains('is-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });

        if (navClose) {
            navClose.addEventListener('click', function () {
                closeDrawer();
            });
        }

        if (navOverlay) {
            navOverlay.addEventListener('click', function () {
                closeDrawer();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && navDrawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });

        window.addEventListener('resize', function () {
            if (!isMobileNav()) {
                closeDrawer();
            }
        });

        navDrawer.addEventListener('click', function (e) {
            if (!isMobileNav()) return;

            var link = e.target.closest('a[href]');
            if (!link || !navDrawer.contains(link)) return;

            var href = link.getAttribute('href') || '';
            var hash = getHashFromHref(href);

            if (hash) {
                e.preventDefault();

                closeDrawer(function () {
                    if (!scrollToHashTarget(hash)) {
                        window.location.href = href;
                    }
                });
                return;
            }

            if (navDrawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });

        if ('IntersectionObserver' in window && sections.length) {
            var observer = new IntersectionObserver(function (entries) {
                var visible = entries.filter(function (entry) {
                    return entry.isIntersecting;
                }).sort(function (a, b) {
                    return b.intersectionRatio - a.intersectionRatio;
                });
                if (visible.length) {
                    setActiveSection(visible[0].target.id);
                }
            }, { root: null, rootMargin: '-45% 0px -45% 0px', threshold: [0, 0.15, 0.35] });

            sections.forEach(function (section) {
                observer.observe(section);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPlatformNav);
    } else {
        initPlatformNav();
    }
})();
</script>
<?php else: ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-gradient-pink storefront-navbar">
            <div class="container px-4 px-lg-5">
                <a class="navbar-brand d-flex align-items-center order-lg-1" href="./">
                <span class="system-logo-wrapper system-logo-header mr-2">
                <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="<?php echo htmlspecialchars($_settings->info('short_name')) ?>" loading="lazy">
                </span>
                <?php echo $_settings->info('short_name') ?>
                </a>

                <button class="navbar-toggler btn btn-sm order-lg-3 ml-auto" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>

                <form class="form-inline storefront-search order-lg-2 mx-lg-3 flex-grow-1" id="search-form">
                  <div class="input-group w-100">
                    <input class="form-control form-control-sm form" type="text" placeholder="Search name or scan barcode" aria-label="Search" name="search" autocomplete="off" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" aria-describedby="button-addon2">
                    <div class="input-group-append">
                      <button class="btn btn-outline-light btn-sm m-0" type="submit" id="button-addon2"><i class="fa fa-search"></i></button>
                    </div>
                  </div>
                </form>

                <div class="collapse navbar-collapse order-lg-4" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                        <li class="nav-item"><a class="nav-link text-white" href="./">Platform</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="./?p=storefront">Shop</a></li>
                        <?php 
                        $cat_qry = $conn->query("SELECT * FROM categories where status = 1  limit 3");
                        $count_cats =$conn->query("SELECT * FROM categories where status = 1 ")->num_rows;
                        while($crow = $cat_qry->fetch_assoc()):
                        ?>
                        <li class="nav-item"><a class="nav-link text-white" aria-current="page" href="./?p=products&c=<?php echo md5($crow['id']) ?>"><?php echo $crow['category'] ?></a></li>
                        <?php endwhile; ?>
                        <?php if($count_cats > 3): ?>
                        <li class="nav-item"><a class="nav-link text-white" href="./?p=view_categories">All Categories</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link text-white" href="./?p=about">About</a></li>
                    </ul>
                    <div class="d-flex align-items-center">
                      <?php if($_settings->userdata('id') > 0 && $_settings->userdata('login_type') == 2): ?>
                        <a class="text-dark mr-2 nav-link text-white" href="./?p=cart">
                            <i class="bi-cart-fill me-1"></i>
                            Cart
                            <span class="badge bg-dark text-white ms-1 rounded-pill" id="cart-count">
                              <?php 
                                $count = $conn->query("SELECT SUM(quantity) as items from `cart` where client_id =".$_settings->userdata('id'))->fetch_assoc()['items'];
                                echo ($count > 0 ? $count : 0);
                              ?>
                            </span>
                        </a>
                        
                            <a href="./?p=my_account" class="text-dark  nav-link text-white"><b> Hi, <?php echo $_settings->userdata('firstname')?>!</b></a>
                            <a href="logout.php" class="text-dark  nav-link text-white"><i class="fa fa-sign-out-alt"></i></a>
                        <?php else: ?>
                        <button class="btn btn-outline-dark ml-2 storefront-login-btn" id="login-btn" type="button">Login Here</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
<script>
  $(function(){
    $('#login-btn').click(function(){
      uni_modal("","login.php")
    })
    $('#navbarResponsive').on('show.bs.collapse', function () {
        $('#mainNav').addClass('navbar-shrink')
    })
    $('#navbarResponsive').on('hidden.bs.collapse', function () {
        if($('body').offset.top == 0)
          $('#mainNav').removeClass('navbar-shrink')
    })
  })

  $('#search-form').submit(function(e){
    e.preventDefault()
     var sTxt = $('[name="search"]').val().trim()
     if(sTxt != '')
      location.href = './?p=products&search='+encodeURIComponent(sTxt);
  })
  $('[name="search"]').on('keydown', function(e){
    if(e.key === 'Enter'){
      e.preventDefault()
      $('#search-form').submit()
    }
  })
</script>
<?php endif; ?>
