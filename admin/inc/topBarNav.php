<style>
  .user-img{
        position: absolute;
        height: 27px;
        width: 27px;
        object-fit: cover;
        left: -7%;
        top: -12%;
  }
  .btn-rounded{
        border-radius: 50px;
  }
  /* Notification Center */
  .notif-center-dropdown {
    width: 360px;
    max-width: 92vw;
    padding: 0;
    border: none;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 8px 28px rgba(0,0,0,.14);
    margin-top: .35rem;
  }
  .notif-center-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: #fff;
    padding: .85rem 1rem .75rem;
  }
  .notif-center-title {
    font-weight: 700;
    font-size: .95rem;
    display: flex;
    align-items: center;
    gap: .45rem;
  }
  .notif-center-subtitle {
    font-size: .78rem;
    opacity: .9;
    margin-top: .15rem;
  }
  .notif-center-header-actions {
    display: flex;
    gap: .4rem;
    margin-top: .55rem;
  }
  .notif-center-header-actions .btn {
    font-size: .7rem;
    font-weight: 600;
    padding: .2rem .55rem;
    border-radius: 6px;
    line-height: 1.3;
  }
  .notif-center-header-actions .btn-light {
    color: #1e3a5f;
  }
  .notif-center-header-actions .btn-outline-light {
    border-color: rgba(255,255,255,.45);
    color: #fff;
  }
  .notif-center-header-actions .btn-outline-light:hover {
    background: rgba(255,255,255,.12);
    color: #fff;
  }
  .notif-center-list {
    max-height: 320px;
    overflow-y: auto;
    padding: .5rem;
    background: #f8f9fb;
  }
  .notif-center-empty {
    text-align: center;
    color: #6c757d;
    font-size: .82rem;
    padding: 1.25rem .75rem;
  }
  .notif-card {
    display: flex;
    align-items: flex-start;
    gap: .55rem;
    padding: .55rem .6rem;
    margin-bottom: .4rem;
    background: #fff;
    border: 1px solid rgba(0,0,0,.06);
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
    cursor: pointer;
    position: relative;
    transition: box-shadow .15s ease, transform .15s ease, border-color .15s ease;
  }
  .notif-card:last-child { margin-bottom: 0; }
  .notif-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
    transform: translateY(-1px);
    border-color: rgba(37,99,235,.15);
  }
  .notif-card.notif-unread {
    border-left: 3px solid #2563eb;
    background: #fff;
  }
  .notif-card-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .78rem;
    flex-shrink: 0;
  }
  .notif-icon-info { background: rgba(37,99,235,.12); color: #2563eb; }
  .notif-icon-warning { background: rgba(245,158,11,.14); color: #d97706; }
  .notif-icon-danger { background: rgba(239,68,68,.12); color: #dc2626; }
  .notif-icon-success { background: rgba(22,163,74,.12); color: #16a34a; }
  .notif-card-body { flex: 1; min-width: 0; padding-right: 1rem; }
  .notif-card-title {
    font-size: .8rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
    margin-bottom: .1rem;
  }
  .notif-card.notif-unread .notif-card-title { color: #111827; }
  .notif-card-desc {
    font-size: .72rem;
    color: #6b7280;
    line-height: 1.25;
    margin-bottom: .15rem;
    word-break: break-word;
  }
  .notif-card-time {
    font-size: .68rem;
    color: #9ca3af;
  }
  .notif-dismiss {
    position: absolute;
    top: .35rem;
    right: .35rem;
    width: 22px;
    height: 22px;
    border: none;
    background: #f3f4f6;
    color: #6b7280;
    border-radius: 6px;
    font-size: .65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity .15s ease, background .15s ease, color .15s ease;
    cursor: pointer;
    padding: 0;
  }
  .notif-card:hover .notif-dismiss { opacity: 1; }
  .notif-dismiss:hover {
    background: #fee2e2;
    color: #dc2626;
  }
  .notif-center-footer {
    padding: .6rem .65rem .65rem;
    background: #fff;
    border-top: 1px solid rgba(0,0,0,.06);
    display: flex;
    flex-direction: column;
    gap: .35rem;
  }
  .notif-center-footer .btn {
    font-size: .76rem;
    font-weight: 600;
    border-radius: 8px;
    padding: .35rem .65rem;
  }
</style>
<!-- Navbar -->
      <nav class="main-header navbar navbar-expand navbar-light shadow text-sm">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
          <a class="nav-link sidebar-toggle-btn" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></a>
          </li>
          <li class="nav-item d-md-none">
            <span class="ash-nav-title-mobile"><?php echo htmlspecialchars($_settings->info('name') ?: 'Kalmoy POS') ?> - Admin</span>
          </li>
          <li class="nav-item d-none d-md-inline-block">
            <a href="<?php echo base_url ?>" class="nav-link"><?php echo htmlspecialchars($_settings->info('name')) ?> - Admin</a>
          </li>
        </ul>
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
          <!-- Navbar Search -->
          <!-- <li class="nav-item">
            <a class="nav-link" data-widget="navbar-search" href="#" role="button">
            <i class="fas fa-search"></i>
            </a>
            <div class="navbar-search-block">
              <form class="form-inline">
                <div class="input-group input-group-sm">
                  <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                  <div class="input-group-append">
                    <button class="btn btn-navbar" type="submit">
                    <i class="fas fa-search"></i>
                    </button>
                    <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                    <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </li> -->
          <!-- Messages Dropdown Menu -->
          <!-- Notifications -->
          <li class="nav-item dropdown" id="nav-notifications-wrap">
            <a class="nav-link" data-toggle="dropdown" href="#" id="nav-notifications-toggle">
              <i class="far fa-bell"></i>
              <span class="badge badge-warning navbar-badge" id="nav-notifications-count" style="display:none;">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right notif-center-dropdown" id="nav-notifications-menu">
              <div class="notif-center-header">
                <div class="notif-center-title"><i class="far fa-bell"></i> Notifications</div>
                <div class="notif-center-subtitle" id="nav-notifications-subtitle">Loading...</div>
                <div class="notif-center-header-actions">
                  <button type="button" class="btn btn-sm btn-light" id="nav-mark-all-read-top">Mark all as read</button>
                  <a href="<?php echo base_url ?>admin/?page=notifications" class="btn btn-sm btn-outline-light">View all</a>
                </div>
              </div>
              <div id="nav-notifications-list" class="notif-center-list">
                <div class="notif-center-empty">Loading...</div>
              </div>
              <div class="notif-center-footer">
                <a href="<?php echo base_url ?>admin/?page=notifications" class="btn btn-outline-primary btn-block">View All Notifications</a>
                <button type="button" class="btn btn-outline-secondary btn-block" id="nav-mark-all-read">Mark All Read</button>
              </div>
            </div>
          </li>
          <li class="nav-item">
            <div class="btn-group nav-link">
                  <button type="button" class="btn btn-rounded badge badge-light dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    <span><img src="<?php echo validate_image($_settings->userdata('avatar')) ?>" class="img-circle elevation-2 user-img" alt="User Image"></span>
                    <span class="ml-3"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></span>
                    <span class="sr-only">Toggle Dropdown</span>
                  </button>
                  <div class="dropdown-menu" role="menu">
                    <a class="dropdown-item" href="<?php echo base_url.'admin/?page=user' ?>"><span class="fa fa-user"></span> My Account</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo base_url.'/classes/Login.php?f=logout' ?>"><span class="fas fa-sign-out-alt"></span> Logout</a>
                  </div>
              </div>
          </li>
        </ul>
      </nav>
      <!-- /.navbar -->
      <script>
      $(function(){
        function escapeHtml(str){
          return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
        function parseAppDateTime(dateStr){
          if(!dateStr) return null;
          var s = String(dateStr).trim();
          if(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(s)){
            return new Date(s.replace(' ', 'T') + '+03:00');
          }
          return new Date(s);
        }
        function relativeTime(dateStr){
          if(!dateStr) return '';
          var d = parseAppDateTime(dateStr);
          if(!d || isNaN(d.getTime())) return dateStr;
          var sec = Math.floor((Date.now() - d.getTime()) / 1000);
          if(sec < 45) return 'Just now';
          var min = Math.floor(sec / 60);
          if(min < 60) return min + ' min ago';
          var hr = Math.floor(min / 60);
          if(hr < 24) return hr + ' hour' + (hr > 1 ? 's' : '') + ' ago';
          var days = Math.floor(hr / 24);
          if(days === 1) return 'Yesterday';
          if(days < 7) return days + ' days ago';
          return d.toLocaleDateString(window.APP_LOCALE || 'en-KE', { month: 'short', day: 'numeric', timeZone: window.APP_TIMEZONE || 'Africa/Nairobi' });
        }
        function notifVisual(title, type){
          var t = String(title || '').toLowerCase();
          if(t.indexOf('open order') >= 0 || t.indexOf('pending order') >= 0) return { icon: 'fa-clipboard-list', cls: 'notif-icon-info' };
          if(t.indexOf('low stock') >= 0) return { icon: 'fa-exclamation-triangle', cls: 'notif-icon-warning' };
          if(t.indexOf('out of stock') >= 0) return { icon: 'fa-times-circle', cls: 'notif-icon-danger' };
          if(t.indexOf('expir') >= 0) return { icon: 'fa-hourglass-half', cls: 'notif-icon-warning' };
          if(t.indexOf('backup') >= 0) return { icon: 'fa-database', cls: 'notif-icon-success' };
          if(t.indexOf('sale') >= 0) return { icon: 'fa-coins', cls: 'notif-icon-success' };
          if(t.indexOf('expense') >= 0) return { icon: 'fa-file-invoice-dollar', cls: 'notif-icon-info' };
          if(type === 'success') return { icon: 'fa-check-circle', cls: 'notif-icon-success' };
          if(type === 'warning') return { icon: 'fa-exclamation-triangle', cls: 'notif-icon-warning' };
          if(type === 'danger') return { icon: 'fa-times-circle', cls: 'notif-icon-danger' };
          return { icon: 'fa-info-circle', cls: 'notif-icon-info' };
        }
        function renderNotifications(resp){
          if(!resp || resp.status !== 'success') return;
          var unread = parseInt(resp.unread || 0, 10);
          var $badge = $('#nav-notifications-count');
          if(unread > 0){ $badge.text(unread > 99 ? '99+' : unread).show(); }
          else { $badge.hide(); }
          var sub = unread > 0
            ? unread + ' new alert' + (unread > 1 ? 's' : '')
            : 'You are all caught up';
          $('#nav-notifications-subtitle').text(sub);
          var html = '';
          var items = (resp.items || []).slice(0, 5);
          if(!items.length){
            html = '<div class="notif-center-empty"><i class="far fa-bell mb-2 d-block" style="font-size:1.25rem;opacity:.45;"></i>No notifications yet</div>';
          } else {
            items.forEach(function(n){
              var vis = notifVisual(n.title, n.type);
              var link = n.link ? n.link : '#';
              var unreadCls = n.is_read == 0 ? ' notif-unread' : '';
              html += '<div class="notif-card notification-item'+unreadCls+'" data-id="'+n.id+'" data-link="'+escapeHtml(link)+'">'
                +'<div class="notif-card-icon '+vis.cls+'"><i class="fas '+vis.icon+'"></i></div>'
                +'<div class="notif-card-body">'
                +'<div class="notif-card-title">'+escapeHtml(n.title)+'</div>'
                +'<div class="notif-card-desc">'+escapeHtml(n.message)+'</div>'
                +'<div class="notif-card-time">'+escapeHtml(relativeTime(n.date_created))+'</div>'
                +'</div>'
                +'<button type="button" class="notif-dismiss notif-delete" data-id="'+n.id+'" title="Dismiss" aria-label="Dismiss"><i class="fas fa-times"></i></button>'
                +'</div>';
            });
          }
          $('#nav-notifications-list').html(html);
        }
        function loadNotifications(){
          $.getJSON(_base_url_+'classes/Master.php?f=get_notifications', {limit: 5}, renderNotifications);
        }
        function markAllRead(e){
          if(e) e.preventDefault();
          $.post(_base_url_+'classes/Master.php?f=mark_all_notifications_read', {}, loadNotifications, 'json');
        }
        loadNotifications();
        setInterval(loadNotifications, 60000);
        $(document).on('click', '.notification-item', function(e){
          if($(e.target).closest('.notif-delete').length) return;
          var id = $(this).data('id');
          var link = $(this).data('link');
          $.post(_base_url_+'classes/Master.php?f=mark_notification_read', {id:id}, function(){
            if(link && link !== '#') window.location.href = link;
            else loadNotifications();
          }, 'json');
        });
        $(document).on('click', '.notif-delete', function(e){
          e.stopPropagation();
          e.preventDefault();
          var id = $(this).data('id');
          $.post(_base_url_+'classes/Master.php?f=delete_notification', {id:id}, loadNotifications, 'json');
        });
        $('#nav-mark-all-read, #nav-mark-all-read-top').on('click', markAllRead);
      });
      </script>