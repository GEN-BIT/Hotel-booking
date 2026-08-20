/**
 * Global UI Enhancements
 * Handles loading states, lazy loading, and common UI patterns
 */

(function () {
  'use strict';

  // ============================================
  // Loading States
  // ============================================

  function showLoading(element) {
    if (!element) return;
    element.classList.add('loading');
    element.disabled = true;
    
    const existingSpinner = element.querySelector('.spinner');
    if (!existingSpinner) {
      const spinner = document.createElement('span');
      spinner.className = 'spinner';
      spinner.innerHTML = ' <span class="spinner-icon">⏳</span>';
      element.appendChild(spinner);
    }
  }

  function hideLoading(element) {
    if (!element) return;
    element.classList.remove('loading');
    element.disabled = false;
    
    const spinner = element.querySelector('.spinner');
    if (spinner) {
      spinner.remove();
    }
  }

  // Auto-hide loading states after page load
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.loading, [data-loading]').forEach(function (el) {
      hideLoading(el);
    });
  });

  // ============================================
  // Form Enhancements
  // ============================================

  function initFormEnhancements() {
    // Add loading state to submit buttons
    document.querySelectorAll('form[data-loading]').forEach(function (form) {
      form.addEventListener('submit', function () {
        const btn = form.querySelector('button[type="submit"]');
        showLoading(btn);
      });
    });

    // Auto-resize textareas
    document.querySelectorAll('textarea[data-auto-resize]').forEach(function (textarea) {
      textarea.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 300) + 'px';
      });
    });

    // Confirm destructive actions
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        const message = this.getAttribute('data-confirm') || 'Are you sure?';
        if (!confirm(message)) {
          e.preventDefault();
        }
      });
    });
  }

  // ============================================
  // Lazy Loading
  // ============================================

  function initLazyLoading() {
    if (!('IntersectionObserver' in window)) {
      return;
    }

    const imageObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          const img = entry.target;
          const src = img.getAttribute('data-src');
          const srcset = img.getAttribute('data-srcset');
          
          if (src) {
            img.src = src;
          }
          if (srcset) {
            img.srcset = srcset;
          }
          
          img.classList.remove('lazy');
          imageObserver.unobserve(img);
        }
      });
    }, {
      rootMargin: '50px 0px',
      threshold: 0.01
    });

    document.querySelectorAll('img.lazy').forEach(function (img) {
      imageObserver.observe(img);
    });
  }

  // ============================================
  // Table Enhancements
  // ============================================

  function initTableEnhancements() {
    // Make tables horizontally scrollable on mobile
    document.querySelectorAll('.data-table').forEach(function (table) {
      const wrapper = document.createElement('div');
      wrapper.className = 'table-responsive';
      wrapper.style.overflowX = 'auto';
      wrapper.style.webkitOverflowScrolling = 'touch';
      table.parentNode.insertBefore(wrapper, table);
      wrapper.appendChild(table);
    });

    // Add row click handlers for action links
    document.querySelectorAll('.data-table tbody tr').forEach(function (row) {
      const firstLink = row.querySelector('td a');
      if (firstLink) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (e) {
          if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
            firstLink.click();
          }
        });
      }
    });
  }

  // ============================================
  // Notification Enhancements
  // ============================================

  function initNotifications() {
    // Auto-dismiss flash messages
    const flashMessages = document.querySelectorAll('.flash-message, .auto-dismiss');
    flashMessages.forEach(function (msg) {
      setTimeout(function () {
        msg.style.transition = 'opacity 0.5s';
        msg.style.opacity = '0';
        setTimeout(function () {
          msg.remove();
        }, 500);
      }, 5000);
    });

    // Mark notifications as read when clicked
    document.querySelectorAll('.notification-item').forEach(function (item) {
      item.addEventListener('click', function () {
        const id = this.getAttribute('data-notification-id');
        if (id) {
          fetch('<?= BASE_URL ?>account/notifications.php?mark_read=' + id, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + id
          });
          this.classList.remove('unread');
        }
      });
    });
  }

  // ============================================
  // Accessibility Enhancements
  // ============================================

  function initAccessibility() {
    // Skip to main content link
    const skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.className = 'skip-link';
    skipLink.textContent = 'Skip to main content';
    skipLink.style.cssText = 'position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;';
    document.body.insertBefore(skipLink, document.body.firstChild);
    
    skipLink.addEventListener('click', function (e) {
      e.preventDefault();
      const main = document.getElementById('main-content') || document.querySelector('main, .site-main, .admin-main');
      if (main) {
        main.setAttribute('tabindex', '-1');
        main.focus();
      }
    });

    // Show skip link on focus
    skipLink.addEventListener('focus', function () {
      this.style.left = '50%';
      this.style.top = '10px';
      this.style.transform = 'translateX(-50%)';
      this.style.width = 'auto';
      this.style.height = 'auto';
      this.style.zIndex = '9999';
      this.style.background = 'var(--color-primary)';
      this.style.color = 'var(--color-primary-contrast)';
      this.style.padding = '0.5rem 1rem';
      this.style.borderRadius = 'var(--radius)';
      this.style.textDecoration = 'none';
    });

    // Live region for dynamic content
    const liveRegion = document.createElement('div');
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    liveRegion.className = 'sr-only';
    liveRegion.style.cssText = 'position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;';
    document.body.appendChild(liveRegion);
  }

  // ============================================
  // Image Optimization
  // ============================================

  function initImageOptimization() {
    // Add loading="lazy" to images below the fold
    document.querySelectorAll('img:not([loading])').forEach(function (img) {
      if (img.complete || img.naturalWidth === 0) {
        return;
      }
      img.loading = 'lazy';
    });

    // Handle image errors
    document.querySelectorAll('img').forEach(function (img) {
      img.addEventListener('error', function () {
        this.src = '<?= BASE_URL ?>assets/images/placeholder.jpg';
        this.alt = 'Image not available';
      });
    });
  }

  // ============================================
  // Breadcrumb System
  // ============================================

  function initBreadcrumbs() {
    const breadcrumbContainer = document.querySelector('.breadcrumb');
    if (!breadcrumbContainer) return;

    const currentUrl = window.location.pathname;
    const parts = currentUrl.split('/').filter(function (part) { return part; });
    
    let breadcrumbHTML = '<a href="<?= BASE_URL ?>">Home</a>';
    let path = '';
    
    const labels = {
      'admin': 'Admin',
      'rooms': 'Rooms',
      'room-types': 'Room Types',
      'bookings': 'Bookings',
      'guests': 'Guests',
      'payments': 'Payments',
      'settings': 'Settings',
      'reports': 'Reports',
      'activity-log': 'Activity Log',
      'staff': 'Staff',
      'services': 'Services',
      'coupons': 'Coupons',
      'reviews': 'Reviews',
      'notifications': 'Notifications',
      'account': 'Account',
      'auth': 'Authentication',
      'login': 'Login',
      'register': 'Register',
      'reservations': 'My Reservations',
      'profile': 'Profile',
      'booking': 'Booking',
      'index': 'List',
      'add': 'Add New',
      'edit': 'Edit',
      'view': 'View',
      'delete': 'Delete',
    };

    parts.forEach(function (part, index) {
      path += '/' + part;
      const isLast = index === parts.length - 1;
      const label = labels[part] || part.replace(/-/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase(); });
      
      if (isLast) {
        breadcrumbHTML += ' <span class="breadcrumb-separator">/</span> <span class="breadcrumb-current">' + label + '</span>';
      } else {
        breadcrumbHTML += ' <span class="breadcrumb-separator">/</span> <a href="' + path + '/">' + label + '</a>';
      }
    });

    breadcrumbContainer.innerHTML = breadcrumbHTML;
  }

  // ============================================
  // Smooth Scrolling
  // ============================================

  function initSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
      anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const target = document.querySelector(targetId);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  }

  // ============================================
  // Initialize Everything
  // ============================================

  function init() {
    initFormEnhancements();
    initLazyLoading();
    initTableEnhancements();
    initNotifications();
    initAccessibility();
    initImageOptimization();
    initBreadcrumbs();
    initSmoothScrolling();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
