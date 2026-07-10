(function ($) {
    'use strict';

    function ashEnhanceTables($root) {
        var $scope = $root && $root.length ? $root : $('.content-wrapper');
        $scope.find('table.table').add($root && $root.is('table.table') ? $root : []).each(function () {
            var $table = $(this);
            if ($table.hasClass('rc-table') || $table.closest('#receipt-print').length) {
                return;
            }
            if (!$table.hasClass('ash-table')) {
                $table.addClass('ash-table');
            }
            if ($table.closest('.ash-table-wrap, .mod-table-wrap, .ash-table-scroll, .table-responsive').length === 0) {
                $table.wrap('<div class="ash-table-wrap"></div>');
            } else {
                var $parent = $table.parent();
                if ($parent.hasClass('table-responsive') || $parent.hasClass('ash-table-scroll')) {
                    $parent.addClass('ash-table-wrap');
                }
                if ($parent.hasClass('mod-table-wrap')) {
                    $parent.addClass('ash-table-wrap');
                }
            }
        });
    }

    function ashInitDataTablesDefaults() {
        if (!$.fn.dataTable) {
            return;
        }
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                emptyTable: 'No records found for the current filters.',
                zeroRecords: 'No matching records found.',
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'No entries to show',
                infoFiltered: '(filtered from _MAX_ total entries)',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Prev'
                }
            },
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            autoWidth: false,
            responsive: false
        });
    }

    $(function () {
        ashInitDataTablesDefaults();
        ashEnhanceTables();

        setTimeout(ashEnhanceTables, 300);
        setTimeout(ashEnhanceTables, 1000);

        $(document).on('init.dt draw.dt', function () {
            setTimeout(function () { ashEnhanceTables(); }, 30);
        });

        $(document).on('shown.bs.modal', '#uni_modal, .modal', function () {
            setTimeout(function () { ashEnhanceTables($(this).find('.modal-body')); }, 50);
        });

        $('[data-toggle="tooltip"]').tooltip();
        $(document).ajaxComplete(function(){
            $('[data-toggle="tooltip"]').tooltip();
        });
    });
})(jQuery);
