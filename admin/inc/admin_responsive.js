(function ($) {
    'use strict';

    function debounce(fn, wait) {
        var t;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    function isMobile() {
        return window.innerWidth < 768;
    }

    function ashWrapTables() {
        $('.content-wrapper table.table').each(function () {
            var $table = $(this);
            if ($table.hasClass('rc-table') || $table.closest('#receipt-print').length) {
                return;
            }
            if ($table.closest('.ash-table-wrap, .mod-table-wrap, .table-responsive, .ash-table-scroll, .dataTables_wrapper').length) {
                if (!$table.hasClass('ash-table')) {
                    $table.addClass('ash-table');
                }
                return;
            }
            $table.addClass('ash-table').wrap('<div class="table-responsive ash-table-wrap ash-table-scroll"></div>');
        });

        $('.content-wrapper .dataTables_wrapper').each(function () {
            var $wrap = $(this);
            if ($wrap.parent().hasClass('ash-table-wrap') || $wrap.parent().hasClass('ash-table-scroll')) {
                return;
            }
            $wrap.wrap('<div class="ash-table-wrap ash-table-scroll"></div>');
        });
    }

    function ashResizeCharts() {
        if (typeof Chart === 'undefined') {
            return;
        }
        try {
            if (Chart.instances) {
                Object.keys(Chart.instances).forEach(function (key) {
                    var chart = Chart.instances[key];
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                });
            }
        } catch (e) { /* ignore */ }

        $('canvas').each(function () {
            var chart = this.chart || $(this).data('chart');
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }

    function ashSyncMobileSidebarState() {
        var $body = $('body');
        if (!isMobile()) {
            $body.removeClass('ash-sidebar-scroll-lock');
            return;
        }
        if ($body.hasClass('sidebar-open')) {
            $body.addClass('ash-sidebar-scroll-lock');
        } else {
            $body.removeClass('ash-sidebar-scroll-lock');
        }
    }

    function ashCloseMobileSidebar() {
        if (!isMobile()) {
            return;
        }
        var $body = $('body');
        if (!$body.hasClass('sidebar-open')) {
            ashSyncMobileSidebarState();
            return;
        }
        $body.removeClass('sidebar-open');
        ashSyncMobileSidebarState();
    }

    function ashInitMobileSidebar() {
        var $body = $('body');

        $(document).off('click.ashSidebarClose', '#sidebar-mobile-close, .sidebar-mobile-close')
            .on('click.ashSidebarClose', '#sidebar-mobile-close, .sidebar-mobile-close', function (e) {
                e.preventDefault();
                e.stopPropagation();
                ashCloseMobileSidebar();
            });

        $(document).off('click.ashOverlay', '#sidebar-overlay')
            .on('click.ashOverlay', '#sidebar-overlay', function (e) {
                if (!isMobile()) {
                    return;
                }
                e.preventDefault();
                ashCloseMobileSidebar();
            });

        $('.main-sidebar.sidebar-modern .nav-link').off('click.ashClose').on('click.ashClose', function () {
            if (isMobile()) {
                setTimeout(ashCloseMobileSidebar, 120);
            }
        });

        $('.main-sidebar.sidebar-modern .brand-link').off('click.ashClose').on('click.ashClose', function () {
            if (isMobile()) {
                setTimeout(ashCloseMobileSidebar, 120);
            }
        });

        $(document).off('keydown.ashSidebar').on('keydown.ashSidebar', function (e) {
            if (e.key === 'Escape' && isMobile() && $body.hasClass('sidebar-open')) {
                e.preventDefault();
                ashCloseMobileSidebar();
            }
        });

        $(document).off('shown.lte.pushmenu.ash collapsed.lte.pushmenu.ash')
            .on('shown.lte.pushmenu.ash collapsed.lte.pushmenu.ash', function () {
                ashSyncMobileSidebarState();
            });

        $('[data-widget="pushmenu"], .sidebar-toggle-btn').off('click.ashSync')
            .on('click.ashSync', function () {
                setTimeout(ashSyncMobileSidebarState, 80);
            });
    }

    function ashOnResize() {
        if (!isMobile()) {
            $('body').removeClass('sidebar-open ash-sidebar-scroll-lock');
        } else {
            ashSyncMobileSidebarState();
        }
        ashResizeCharts();
    }

    $(function () {
        ashWrapTables();
        ashInitMobileSidebar();
        ashSyncMobileSidebarState();

        setTimeout(ashWrapTables, 400);
        setTimeout(ashWrapTables, 1200);

        $(document).on('init.dt draw.dt', function () {
            setTimeout(ashWrapTables, 50);
        });

        $(window).on('resize.ashAdmin orientationchange.ashAdmin', debounce(ashOnResize, 200));
        $(window).on('load', function () {
            ashWrapTables();
            ashResizeCharts();
            ashSyncMobileSidebarState();
        });
    });
})(jQuery);
