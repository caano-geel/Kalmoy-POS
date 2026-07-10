<link rel="stylesheet" href="<?php echo base_url ?>admin/inc/sidebar_modern.css">
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-modern elevation-0 sidebar-no-expand">
  <a href="<?php echo base_url ?>admin" class="brand-link sidebar-brand">
    <span class="system-logo-wrapper system-logo-sidebar brand-image">
      <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="Store Logo">
    </span>
    <span class="brand-text-wrap">
      <span class="brand-name"><?php echo htmlspecialchars($_settings->info('short_name') ?: 'Kalmoy') ?></span>
      <span class="brand-sub">POS</span>
    </span>
  </a>
  <div class="sidebar">
    <div class="sidebar-mobile-header d-md-none">
      <span class="sidebar-mobile-title">Navigation</span>
      <button type="button" class="sidebar-mobile-close" id="sidebar-mobile-close" aria-label="Close menu">
        <i class="fas fa-times" aria-hidden="true"></i>
      </button>
    </div>
    <div class="sidebar-nav-scroll">
      <nav class="sidebar-nav">
        <ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="false">
          <?php
          $show_main = admin_cashier_can('dashboard_limited') || admin_cashier_can('pos');
          $show_inventory = admin_cashier_can('products')
              || admin_cashier_can('inventory_view') || admin_cashier_can('inventory_manage');
          $show_sales = admin_cashier_can('orders_view') || admin_cashier_can('orders_manage')
              || admin_cashier_can('sales_report') || admin_cashier_can('expenses')
              || admin_cashier_can('profit_analytics');
          $show_customers = admin_cashier_can('clients');
          $show_administration = admin_is_owner()
              || admin_cashier_can('brands') || admin_cashier_can('categories')
              || admin_cashier_can('settings') || admin_cashier_can('backup_restore')
              || admin_cashier_can('permissions') || admin_cashier_can('users_manage');
          $sidebar_notif_count = notifications_unread_count();
          $sidebar_pending_orders = 0;
          $sidebar_low_stock = 0;
          if (isset($conn) && $conn) {
              $pending_row = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = '0'");
              if ($pending_row) {
                  $sidebar_pending_orders = (int) $pending_row->fetch_assoc()['total'];
              }
          }
          $sidebar_stock_counts = inventory_stock_counts();
          $sidebar_low_stock = (int) $sidebar_stock_counts['low'];
          if (!function_exists('sidebar_nav_badge')) {
              function sidebar_nav_badge($count, $class = 'badge-notify')
              {
                  $count = (int) $count;
                  if ($count <= 0) {
                      return '';
                  }
                  $label = $count > 99 ? '99+' : format_num($count);
                  return '<span class="sidebar-nav-badge ' . $class . '"><span class="badge-num">' . $label . '</span></span>';
              }
          }
          ?>

          <?php if ($show_main): ?>
          <li class="sidenav-section-title"><span>Main</span></li>
          <?php if (admin_cashier_can('dashboard_limited')): ?>
          <li class="nav-item">
            <a href="./" class="nav-link nav-home" data-title="Dashboard">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <span class="nav-link-label">Dashboard</span>
              <?php echo sidebar_nav_badge($sidebar_notif_count, 'badge-notify') ?>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('pos')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=pos" class="nav-link nav-pos" data-title="Point of Sale">
              <i class="nav-icon fas fa-cash-register"></i>
              <span class="nav-link-label">Point of Sale</span>
            </a>
          </li>
          <?php endif; ?>
          <?php endif; ?>

          <?php if ($show_inventory): ?>
          <li class="sidenav-section-title"><span>Inventory</span></li>
          <?php if (admin_cashier_can('products')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=product" class="nav-link nav-product" data-title="Products">
              <i class="nav-icon fas fa-table"></i>
              <span class="nav-link-label">Products</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('inventory_view') || admin_cashier_can('inventory_manage')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=inventory" class="nav-link nav-inventory" data-title="Stock &amp; Inventory">
              <i class="nav-icon fas fa-clipboard-list"></i>
              <span class="nav-link-label">Stock &amp; Inventory</span>
              <?php echo sidebar_nav_badge($sidebar_low_stock, 'badge-danger-soft') ?>
            </a>
          </li>
          <?php endif; ?>
          <?php endif; ?>

          <?php if ($show_sales): ?>
          <li class="sidenav-section-title"><span>Sales</span></li>
          <?php if (admin_cashier_can('orders_view') || admin_cashier_can('orders_manage')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=orders" class="nav-link nav-orders" data-title="Orders">
              <i class="nav-icon fas fa-list"></i>
              <span class="nav-link-label">Orders</span>
              <?php echo sidebar_nav_badge($sidebar_pending_orders, 'badge-warning-soft') ?>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('sales_report')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=sales" class="nav-link nav-sales" data-title="Sales Report">
              <i class="nav-icon fas fa-file-alt"></i>
              <span class="nav-link-label">Sales Report</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('expenses')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=expenses" class="nav-link nav-expenses" data-title="Expenses">
              <i class="nav-icon fas fa-file-invoice-dollar"></i>
              <span class="nav-link-label">Expenses</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('profit_analytics')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=analytics" class="nav-link nav-analytics" data-title="Profit &amp; Loss">
              <i class="nav-icon fas fa-chart-line"></i>
              <span class="nav-link-label">Profit &amp; Loss</span>
            </a>
          </li>
          <?php endif; ?>
          <?php endif; ?>

          <?php if ($show_customers): ?>
          <li class="sidenav-section-title"><span>Customers</span></li>
          <?php if (admin_cashier_can('clients')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=clients" class="nav-link nav-clients" data-title="Customers">
              <i class="nav-icon fas fa-users"></i>
              <span class="nav-link-label">Customers</span>
            </a>
          </li>
          <?php endif; ?>
          <?php endif; ?>

          <?php
          $show_debt = admin_cashier_can('debt_view') || admin_cashier_can('debt_payment') || admin_cashier_can('debt_reports');
          if ($show_debt): ?>
          <li class="sidenav-section-title"><span>Debt / Credit</span></li>
          <?php if (admin_cashier_can('debt_view')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=debt" class="nav-link nav-debt" data-title="Debt Management">
              <i class="nav-icon fas fa-hand-holding-usd"></i>
              <span class="nav-link-label">Debt Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=debt/customers" class="nav-link nav-debt_customers" data-title="Customer Debts">
              <i class="nav-icon fas fa-user-clock"></i>
              <span class="nav-link-label">Customer Debts</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=debt/history" class="nav-link nav-debt_history" data-title="Debt History">
              <i class="nav-icon fas fa-history"></i>
              <span class="nav-link-label">Debt History</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=debt/overdue" class="nav-link nav-debt_overdue" data-title="Overdue Debts">
              <i class="nav-icon fas fa-exclamation-circle"></i>
              <span class="nav-link-label">Overdue Debts</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('debt_payment')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=debt/receive_payment" class="nav-link nav-debt_receive_payment" data-title="Receive Payment">
              <i class="nav-icon fas fa-money-bill-wave"></i>
              <span class="nav-link-label">Receive Payment</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('debt_reports')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=debt/report" class="nav-link nav-debt_report" data-title="Debt Report">
              <i class="nav-icon fas fa-chart-pie"></i>
              <span class="nav-link-label">Debt Report</span>
            </a>
          </li>
          <?php endif; ?>
          <?php endif; ?>

          <?php if ($show_administration): ?>
          <li class="sidenav-section-title"><span>Administration</span></li>
          <?php if (admin_is_owner()): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=users" class="nav-link nav-users" data-title="Users / Staff">
              <i class="nav-icon fas fa-user-friends"></i>
              <span class="nav-link-label">Users / Staff</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('backup_restore')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=backup" class="nav-link nav-backup" data-title="Backup &amp; Restore">
              <i class="nav-icon fas fa-database"></i>
              <span class="nav-link-label">Backup &amp; Restore</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('brands')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=maintenance/brand" class="nav-link nav-maintenance_brand" data-title="Brands">
              <i class="nav-icon fas fa-star"></i>
              <span class="nav-link-label">Brands</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('categories')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=maintenance/category" class="nav-link nav-maintenance_category" data-title="Categories">
              <i class="nav-icon fas fa-th-list"></i>
              <span class="nav-link-label">Categories</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_is_owner()): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=maintenance/permissions" class="nav-link nav-maintenance_permissions" data-title="Role Permissions">
              <i class="nav-icon fas fa-user-shield"></i>
              <span class="nav-link-label">Role Permissions</span>
            </a>
          </li>
          <?php endif; ?>
          <?php if (admin_cashier_can('settings')): ?>
          <li class="nav-item">
            <a href="<?php echo base_url ?>admin/?page=system_info" class="nav-link nav-system_info" data-title="Store Settings">
              <i class="nav-icon fas fa-cogs"></i>
              <span class="nav-link-label">Store Settings</span>
            </a>
          </li>
          <?php endif; ?>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
    <div class="sidebar-footer">
      <div class="sidebar-footer-line"></div>
      <div class="sidebar-footer-title">Kalmoy POS v1.0</div>
      <div class="sidebar-footer-credit">Developed by<br>Abdullahi Omar Salad</div>
    </div>
  </div>
</aside>
<script>
$(document).ready(function () {
  var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
  page = page.replace(/\//g, '_');
  var $active = $('.nav-link.nav-' + page);
  if ($active.length > 0) {
    $active.addClass('sidebar-nav-active');
    if ($active.hasClass('tree-item')) {
      $active.closest('.nav-treeview').siblings('a').addClass('sidebar-nav-active');
      $active.closest('.nav-treeview').parent().addClass('menu-open');
    }
    if ($active.hasClass('nav-is-tree')) {
      $active.parent().addClass('menu-open');
    }
  }
});
</script>
