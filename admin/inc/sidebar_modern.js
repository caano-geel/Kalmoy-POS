(function ($) {
    'use strict';

    var STORAGE_KEY = 'cbpos_sidebar_collapsed';
    var $tooltip = null;

    function isMobile() {
        return window.innerWidth < 768;
    }

    function isTablet() {
        return window.innerWidth >= 768 && window.innerWidth <= 1024;
    }

    function getDefaultCollapsed() {
        if (isMobile()) return true;
        if (isTablet()) return true;
        return false;
    }

    function applyStoredState() {
        var saved = localStorage.getItem(STORAGE_KEY);
        var collapsed = saved !== null ? saved === '1' : getDefaultCollapsed();
        var $body = $('body');

        if (isMobile()) {
            $body.removeClass('sidebar-open sidebar-closed');
            return;
        }

        if (collapsed) {
            $body.addClass('sidebar-collapse');
        } else {
            $body.removeClass('sidebar-collapse');
        }
        $body.removeClass('sidebar-open sidebar-closed');
    }

    function persistState() {
        if (isMobile()) {
            localStorage.setItem(
                STORAGE_KEY,
                $('body').hasClass('sidebar-open') ? '0' : '1'
            );
            return;
        }
        localStorage.setItem(
            STORAGE_KEY,
            $('body').hasClass('sidebar-collapse') ? '1' : '0'
        );
    }

    function initTooltip() {
        if (!$tooltip || !$tooltip.length) {
            $tooltip = $('<div class="sidebar-nav-tooltip" role="tooltip"></div>').appendTo('body');
        }
    }

    function hideTooltip() {
        if ($tooltip) {
            $tooltip.removeClass('visible');
        }
    }

    function isCollapsedRail() {
        return window.innerWidth >= 768 && $('body').hasClass('sidebar-collapse');
    }

    function showTooltip($link) {
        if (!isCollapsedRail()) {
            hideTooltip();
            return;
        }

        var title = $link.attr('data-title') || $.trim($link.find('.nav-link-label').text());
        if (!title) return;

        initTooltip();
        $tooltip.text(title);

        var rect = $link[0].getBoundingClientRect();
        var left = rect.right + 10;
        var top = rect.top + (rect.height / 2) - ($tooltip.outerHeight() / 2);

        $tooltip.css({ left: left + 'px', top: top + 'px' }).addClass('visible');
    }

    function bindTooltips() {
        $(document)
            .off('mouseenter.sidebarTip', '.sidebar-modern .nav-sidebar .nav-link')
            .on('mouseenter.sidebarTip', '.sidebar-modern .nav-sidebar .nav-link', function () {
                showTooltip($(this));
            })
            .off('mouseleave.sidebarTip', '.sidebar-modern .nav-sidebar .nav-link')
            .on('mouseleave.sidebarTip', '.sidebar-modern .nav-sidebar .nav-link', hideTooltip);
    }

    function initSidebarModern() {
        $('body').addClass('sidebar-modern-ui');
        bindTooltips();

        $(document).on('collapsed.lte.pushmenu shown.lte.pushmenu', function () {
            if (!isMobile()) {
                $('body').removeClass('sidebar-open sidebar-closed');
            }
            persistState();
        });

        var resizeTimer;
        $(window).on('resize.sidebarModern', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                applyStoredState();
                hideTooltip();
            }, 200);
        });
    }

    $(function () {
        $('body').addClass('sidebar-modern-ui');
        bindTooltips();
    });

    $(window).on('load', function () {
        initSidebarModern();
        setTimeout(function () {
            applyStoredState();
        }, 100);
    });
})(jQuery);
