/* wk_params safe accessor */
window.wk_params = window.wk_params || { ajax_url: '/wp-admin/admin-ajax.php', nonce: '', shop_url: '/shop', checkout_url: '/checkout' };

/**
 * WhiteKurti — main.js
 * Handles: header scroll, mobile menu, cart drawer, search overlay,
 * filter drawer, product gallery, sticky ATC, AJAX add-to-cart, toast,
 * pincode checker, quantity buttons.
 */
(function ($) {
  'use strict';

  const wkP = window.wk_params || {};

  /* ── Utility ──────────────────────────────────────────────────────────── */
  function openOverlay(el) {
    el.classList.add('is-open');
    el.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    document.body.classList.add('wk-overlay-open');
    /* Hide notification popup when any overlay is open */
    var notif = document.getElementById('wk-fn-popup');
    if (notif) notif.style.display = 'none';
    var fEl = el.querySelector('[id$="-close"], .wk-icon-btn'); if (fEl) fEl.focus();
  }
  function closeOverlay(el) {
    el.classList.remove('is-open');
    el.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    document.body.classList.remove('wk-overlay-open');
  }
  function showToast(msg, duration) {
    duration = duration || 3000;
    const t = document.getElementById('wk-toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('is-visible');
    clearTimeout(t._tid);
    t._tid = setTimeout(function () { t.classList.remove('is-visible'); }, duration);
  }

  /* ── Sticky header ────────────────────────────────────────────────────── */
  var header = document.getElementById('wk-header');
  if (header) {
    window.addEventListener('scroll', function () {
      header.classList.toggle('scrolled', window.scrollY > 10);
    }, { passive: true });
  }

  /* ── Mobile menu ──────────────────────────────────────────────────────── */
  var menuOverlay = document.getElementById('wk-menu-overlay');
  var menuToggle  = document.getElementById('wk-menu-toggle');
  var menuClose   = document.getElementById('wk-menu-close');
  var menuBack    = document.getElementById('wk-menu-backdrop');

  if (menuOverlay) {
    menuToggle && menuToggle.addEventListener('click', function () {
      openOverlay(menuOverlay);
      menuToggle.setAttribute('aria-expanded', 'true');
    });
    function closeMenu() {
      closeOverlay(menuOverlay);
      menuToggle && menuToggle.setAttribute('aria-expanded', 'false');
    }
    menuClose && menuClose.addEventListener('click', closeMenu);
    menuBack  && menuBack.addEventListener('click', closeMenu);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && menuOverlay.classList.contains('is-open')) closeMenu();
    });
  }

  /* ── Cart drawer ──────────────────────────────────────────────────────── */
  var cartOverlay = document.getElementById('wk-cart-overlay');
  var cartToggle  = document.getElementById('wk-cart-toggle');
  var cartClose   = document.getElementById('wk-cart-close');
  var cartBack    = document.getElementById('wk-cart-backdrop');

  if (cartOverlay) {
    function openCart() { openOverlay(cartOverlay); cartToggle && cartToggle.setAttribute('aria-expanded','true'); }
    function closeCart() { closeOverlay(cartOverlay); cartToggle && cartToggle.setAttribute('aria-expanded','false'); }
    cartToggle && cartToggle.addEventListener('click', openCart);
    cartClose  && cartClose.addEventListener('click', closeCart);
    cartBack   && cartBack.addEventListener('click', closeCart);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && cartOverlay.classList.contains('is-open')) closeCart();
    });
    // Open cart after successful add
    $(document.body).on('wc_cart_button_updated added_to_cart', function () {
      openCart();
    });
  }

  /* ── Search overlay ───────────────────────────────────────────────────── */
  var searchOverlay  = document.getElementById('wk-search-overlay');
  var searchToggle   = document.getElementById('wk-search-toggle');
  var searchClose    = document.getElementById('wk-search-close');
  var searchBack     = document.getElementById('wk-search-backdrop');
  var searchInput    = document.getElementById('wk-search-input');

  if (searchOverlay) {
    function openSearch() {
      openOverlay(searchOverlay);
      searchToggle && searchToggle.setAttribute('aria-expanded','true');
      setTimeout(function () { searchInput && searchInput.focus(); }, 100);
    }
    function closeSearch() {
      closeOverlay(searchOverlay);
      searchToggle && searchToggle.setAttribute('aria-expanded','false');
    }
    searchToggle && searchToggle.addEventListener('click', openSearch);
    searchClose  && searchClose.addEventListener('click', closeSearch);
    searchBack   && searchBack.addEventListener('click', closeSearch);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && searchOverlay.classList.contains('is-open')) closeSearch();
    });

    // ── Rich Live Search ─────────────────────────────────────
    if (searchInput) {
      var lsCfg     = window.wk_search_cfg || {};
      var lsEnabled = lsCfg.enabled !== '0';
      var minChars  = parseInt(lsCfg.min_chars, 10) || 2;
      var debounceMs= parseInt(lsCfg.debounce, 10)  || 280;
      var shopUrl   = lsCfg.shop_url || '/shop/';
      var lsTimer;
      var lsFocused = -1;
      var lsResults = document.getElementById('wk-search-results');
      var RECENT_KEY= 'wk_recent_searches';

      function getRecent() {
        try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]'); } catch(e) { return []; }
      }
      function addRecent(q) {
        var rec = getRecent().filter(function(r){ return r !== q; });
        rec.unshift(q);
        try { localStorage.setItem(RECENT_KEY, JSON.stringify(rec.slice(0,8))); } catch(e) {}
      }

      function buildProductHTML(p, showPrice, showCat) {
        var saleTag = p.on_sale ? '<span class="wk-sr-product__sale" style="font-size:10px;background:var(--sale);color:#fff;padding:1px 5px;border-radius:2px;margin-left:4px;">SALE</span>' : '';
        var stock   = !p.in_stock ? '<span class="wk-sr-product__stock-oos">Out of stock</span>' : '';
        return '<a href="' + p.url + '" class="wk-sr-product">'
          + '<img src="' + p.img + '" alt="' + (p.name||'') + '" class="wk-sr-product__img" loading="lazy" />'
          + '<div class="wk-sr-product__info">'
            + '<span class="wk-sr-product__name">' + p.name + saleTag + '</span>'
            + (showCat && p.cat ? '<span class="wk-sr-product__cat">' + p.cat + '</span>' : '')
            + stock
          + '</div>'
          + (showPrice ? '<div class="wk-sr-product__right"><span class="wk-sr-product__price">' + p.price + '</span></div>' : '')
          + '</a>';
      }

      function showDefault() {
        if (!lsResults) return;
        var html = '';
        // Recent searches
        if (lsCfg.show_recent !== '0') {
          var recent = getRecent();
          if (recent.length) {
            html += '<div class="wk-sr-section"><div class="wk-sr-section__label">⏱ Recent</div><div class="wk-sr-tags">';
            recent.slice(0,5).forEach(function(r) {
              html += '<a href="' + shopUrl + '?s=' + encodeURIComponent(r) + '" class="wk-sr-tag">'+r+'</a>';
            });
            html += '</div></div>';
          }
        }
        // Trending
        if (lsCfg.show_trending !== '0' && lsCfg.trending && lsCfg.trending.length) {
          html += '<div class="wk-sr-section"><div class="wk-sr-section__label">🔥 Trending</div><div class="wk-sr-tags">';
          lsCfg.trending.forEach(function(t) {
            if (t) html += '<a href="' + shopUrl + '?s=' + encodeURIComponent(t) + '" class="wk-sr-tag">' + t + '</a>';
          });
          html += '</div></div>';
        }
        lsResults.innerHTML = html || '';
      }

      function doSearch(q) {
        if (!lsResults) return;
        lsResults.innerHTML = '<div class="wk-sr-loading"><div class="wk-sr-spinner"></div></div>';

        $.post(lsCfg.ajax || wkP.ajax_url, {
          action: 'wk_live_search',
          nonce:  lsCfg.nonce,
          q:      q,
        }, function(res) {
          if (!res.success) { lsResults.innerHTML = '<div class="wk-sr-empty"><span class="wk-sr-empty__icon">🔍</span>No results found for "<strong>' + q + '</strong>"</div>'; return; }
          var d   = res.data;
          var html= '';
          var showPrice = lsCfg.show_price !== '0';
          var showCat   = lsCfg.show_cat   !== '0';

          // Products
          if (d.products && d.products.length) {
            html += '<div class="wk-sr-section"><div class="wk-sr-section__label">👗 Products</div>';
            d.products.forEach(function(p) { html += buildProductHTML(p, showPrice, showCat); });
            html += '</div>';
          }
          // Categories
          if (d.categories && d.categories.length) {
            html += '<div class="wk-sr-section"><div class="wk-sr-section__label">📂 Categories</div>';
            d.categories.forEach(function(c) {
              html += '<a href="' + c.url + '" class="wk-sr-cat">'
                + (c.img ? '<img src="'+c.img+'" alt="'+c.name+'" class="wk-sr-cat__img" />' : '')
                + '<span class="wk-sr-cat__name">' + c.name + '</span>'
                + '<span class="wk-sr-cat__count">' + c.count + ' items</span>'
                + '</a>';
            });
            html += '</div>';
          }
          // Pages
          if (d.pages && d.pages.length) {
            html += '<div class="wk-sr-section"><div class="wk-sr-section__label">📄 Pages</div>';
            d.pages.forEach(function(p) {
              html += '<a href="'+p.url+'" class="wk-sr-page"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'+p.name+'</a>';
            });
            html += '</div>';
          }
          // Nothing found
          if (!d.products.length && !d.categories.length && !d.pages.length) {
            html = '<div class="wk-sr-empty"><span class="wk-sr-empty__icon">🔍</span>No results for "<strong>' + q + '</strong>"<br><span style="font-size:12px;opacity:.6">Try a different term or browse all products</span></div>';
          } else {
            html += '<a href="' + shopUrl + '?s=' + encodeURIComponent(q) + '" class="wk-sr-view-all">View all results for "' + q + '" <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>';
          }
          lsResults.innerHTML = html;
          lsFocused = -1;
        }).fail(function() {
          if (lsResults) lsResults.innerHTML = '<div class="wk-sr-empty">Connection error. Please try again.</div>';
        });
      }

      // Input events
      searchInput.addEventListener('focus', function() {
        if (!this.value.trim()) showDefault();
      });
      searchInput.addEventListener('input', function() {
        clearTimeout(lsTimer);
        var q = this.value.trim();
        if (!q || q.length < minChars) {
          if (!q) showDefault();
          else if (lsResults) lsResults.innerHTML = '';
          return;
        }
        lsTimer = setTimeout(function() { doSearch(q); }, debounceMs);
      });

      // Keyboard navigation
      searchInput.addEventListener('keydown', function(e) {
        var items = lsResults ? lsResults.querySelectorAll('.wk-sr-product, .wk-sr-cat, .wk-sr-page, .wk-sr-tag, .wk-sr-view-all') : [];
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          lsFocused = Math.min(lsFocused + 1, items.length - 1);
          items.forEach(function(el, i) { el.classList.toggle('is-focused', i === lsFocused); });
          if (items[lsFocused]) items[lsFocused].scrollIntoView({block:'nearest'});
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          lsFocused = Math.max(lsFocused - 1, -1);
          items.forEach(function(el, i) { el.classList.toggle('is-focused', i === lsFocused); });
        } else if (e.key === 'Enter' && lsFocused >= 0 && items[lsFocused]) {
          e.preventDefault();
          items[lsFocused].click();
        }
      });

      // Save recent on form submit
      searchInput.closest('form') && searchInput.closest('form').addEventListener('submit', function() {
        var q = searchInput.value.trim();
        if (q) addRecent(q);
      });

      // Close on click outside
      document.addEventListener('click', function(e) {
        if (lsResults && !lsResults.contains(e.target) && e.target !== searchInput) {
          lsResults.innerHTML = '';
        }
      });
    }
  }

  /* ── Filter drawer ────────────────────────────────────────────────────── */
  var filterDrawer   = document.getElementById('wk-filter-drawer');
  var filterToggle   = document.getElementById('wk-filter-toggle');
  var filterClose    = document.getElementById('wk-filter-close');
  var filterBack     = document.getElementById('wk-filter-backdrop');

  if (filterDrawer) {
    function openFilter() { filterDrawer.classList.add('is-open'); document.body.style.overflow = 'hidden'; filterToggle && filterToggle.setAttribute('aria-expanded','true'); }
    function closeFilter() { filterDrawer.classList.remove('is-open'); document.body.style.overflow = ''; filterToggle && filterToggle.setAttribute('aria-expanded','false'); }
    filterToggle && filterToggle.addEventListener('click', openFilter);
    filterClose  && filterClose.addEventListener('click', closeFilter);
    filterBack   && filterBack.addEventListener('click', closeFilter);
    var filterApply = document.getElementById('wk-filter-apply');
    filterApply && filterApply.addEventListener('click', closeFilter);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeFilter();
    });
  }

  /* ── Product gallery (PDP) ────────────────────────────────────────────── */
  // Handled by standalone IIFE below (after main IIFE ends)

  /* ── Sticky ATC (PDP, mobile) ─────────────────────────────────────────── */
  var stickyAtc = document.getElementById('wk-sticky-atc');
  // Observe the Buy Now button (which is always in the info section) for sticky ATC visibility
  var atcSection = document.getElementById('wk-buy-now') || document.getElementById('wk-atc-btn') || document.querySelector('.wk-pdp__info');
  if (stickyAtc && atcSection) {
    var stickyObserver = new IntersectionObserver(function (entries) {
      stickyAtc.classList.toggle('is-visible', !entries[0].isIntersecting);
    }, { threshold: 0 });
    stickyObserver.observe(atcSection);

    var stickyBtn = document.getElementById('wk-sticky-atc-btn');
    stickyBtn && stickyBtn.addEventListener('click', function () {
      var mainAtcBtn = document.querySelector('.single_add_to_cart_button');
      mainAtcBtn && mainAtcBtn.click();
    });
  }

  /* ── Quantity buttons ─────────────────────────────────────────────────── */
  $(document.body).on('click', '.qty-btn', function () {
    var $input = $(this).closest('.quantity').find('input.qty');
    var val = parseInt($input.val()) || 1;
    if ($(this).data('dir') === 'up') {
      $input.val(val + 1).trigger('change');
    } else {
      $input.val(Math.max(1, val - 1)).trigger('change');
    }
  });

  /* Inject +/- buttons around qty inputs */
  $(document.body).on('woocommerce_quantity_input_init updated_wc_div', function () {
    $('.quantity').each(function () {
      if (!$(this).find('.qty-btn').length) {
        $(this).find('input.qty').wrap('<div class="qty-inner"></div>');
        $(this).prepend('<button class="qty-btn" data-dir="down" aria-label="Decrease quantity" type="button">−</button>');
        $(this).append('<button class="qty-btn" data-dir="up" aria-label="Increase quantity" type="button">+</button>');
      }
    });
  }).trigger('woocommerce_quantity_input_init');

  /* ── AJAX Add to Cart ─────────────────────────────────────────────────── */
  $(document.body).on('click', '.wk-quick-atc', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var pid  = $btn.data('product-id');
    if (!pid || !wkP.ajax_url) return;
    $btn.prop('disabled', true).text('Adding…');
    $.ajax({
      url:      wkP.ajax_url,
      method:   'POST',
      data: {
        action:     'wk_add_to_cart',
        nonce:      wkP.nonce,
        product_id: pid,
        quantity:   1,
      },
      success: function (res) {
        $btn.prop('disabled', false).text('Added!');
        setTimeout(function () { $btn.text('Add to Cart'); }, 2000);
        if (res.success) {
          if (typeof window.wkPlayAddToCartSound === 'function') window.wkPlayAddToCartSound();
          $(document.body).trigger('added_to_cart', [res.data.fragments, res.data.cart_hash, $btn]);
          // Update cart count
          var count = res.data.cart_count;
          $('.wk-cart-count').text(count).toggleClass('has-items', count > 0);
          showToast(wkP.i18n_added || 'Added to cart');
        } else {
          showToast(wkP.i18n_error || 'Error adding to cart');
        }
      },
      error: function () {
        $btn.prop('disabled', false).text('Add to Cart');
        showToast(wkP.i18n_error || 'Error adding to cart');
      }
    });
  });

  /* ── Pincode checker (PDP) ─────────────────────────────────────────────── */
  var pincodeCheckBtn = document.getElementById('wk-pincode-check');
  var pincodeInput    = document.getElementById('wk-pincode-input');
  var pincodeResult   = document.getElementById('wk-pincode-result');

  if (pincodeCheckBtn && pincodeInput && pincodeResult) {
    pincodeCheckBtn.addEventListener('click', function () {
      var pin = pincodeInput.value.trim();
      if (!/^\d{6}$/.test(pin)) {
        pincodeResult.textContent = 'Please enter a valid 6-digit pincode.';
        pincodeResult.style.color = 'var(--sale)';
        return;
      }
      pincodeCheckBtn.textContent = '…';
      // Simulate check (replace with real Shiprocket API call)
      setTimeout(function () {
        pincodeCheckBtn.textContent = 'Check';
        pincodeResult.style.color = 'var(--accent)';
        pincodeResult.textContent = '✓ Delivery available · Estimated 4–6 business days · COD available';
      }, 600);
    });
    pincodeInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') pincodeCheckBtn.click();
    });
  }

  /* ── Wishlist toggle (UI state only — server state needs YITH plugin) ─── */
  $(document.body).on('click', '.wk-wish-btn', function () {
    var $btn = $(this);
    var productId = $btn.data('product-id') || $btn.attr('data-product-id');
    if (!productId) return;
    var adding = !$btn.hasClass('is-wished');
    $btn.toggleClass('is-wished active', adding);
    showToast(adding ? '❤️ Added to wishlist' : 'Removed from wishlist');

    /* Custom wishlist AJAX (built-in, no YITH needed) */
    var wlCfg = window.wk_wishlist_cfg;
    if (wlCfg && wlCfg.nonce) {
      $.ajax({
        url: wlCfg.ajax || (window.wk_params && window.wk_params.ajax_url) || '/wp-admin/admin-ajax.php',
        method: 'POST',
        data: { action: 'wk_wishlist_toggle', product_id: productId, nonce: wlCfg.nonce }
      });
    }
  });

  /* ── Mini-cart item remove ─────────────────────────────────────────────── */
  $(document.body).on('click', '.wk-mini-cart-item__remove', function (e) {
    e.preventDefault();
    var $item = $(this).closest('.wk-mini-cart-item');
    var cartUrl = $(this).attr('href');
    if (!cartUrl) return;
    $item.css('opacity', '0.4');
    window.location.href = cartUrl; // fallback to full page reload remove
  });

  /* ── Accordion (details/summary) — ensure only one open at a time ──────── */
  document.querySelectorAll('.wk-pdp__accordions').forEach(function (container) {
    container.addEventListener('toggle', function (e) {
      if (e.target.open) {
        container.querySelectorAll('details.wk-accordion').forEach(function (d) {
          if (d !== e.target) d.open = false;
        });
      }
    }, true);
  });

  /* ── Back-to-top (keyboard escape from deep pages) ─────────────────────── */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      // close all open overlays in order of priority
      var order = [cartOverlay, searchOverlay, menuOverlay];
      for (var i = 0; i < order.length; i++) {
        if (order[i] && order[i].classList.contains('is-open')) {
          order[i].classList.remove('is-open');
          order[i].setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
          break;
        }
      }
    }
  });

  /* ── WooCommerce fragments update ─────────────────────────────────────── */
  $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function () {
    var count = parseInt($('.wk-cart-count').text()) || 0;
    $('.wk-cart-count').toggleClass('has-items', count > 0);
  });

  /* ── Auth Tabs (Login/Register) ────────────────────────────────────────── */
  var authTabs = document.querySelectorAll('.wk-auth-tab');
  if (authTabs.length > 0) {
    authTabs.forEach(function(tab) {
      tab.addEventListener('click', function() {
        var target = document.querySelector(this.getAttribute('data-target'));
        if (!target) return;
        
        // Remove active from all tabs and panes
        document.querySelectorAll('.wk-auth-tab').forEach(function(t) { t.classList.remove('is-active'); });
        document.querySelectorAll('.wk-auth-form-pane').forEach(function(p) { p.classList.remove('is-active'); });
        
        // Add active to current
        this.classList.add('is-active');
        target.classList.add('is-active');
      });
    });
  }

  /* ── Variable Swatches Logic ───────────────────────────────────────────── */
  $(document.body).on('click', '.wk-swatch', function() {
    var $swatch = $(this);
    var value = $swatch.data('value');
    var selectId = $swatch.closest('.wk-swatches').data('select-id');
    var $select = $('#' + selectId);
    
    // Update UI
    $swatch.siblings().removeClass('is-selected');
    $swatch.addClass('is-selected');
    
    // Update native select and trigger WooCommerce change event
    $select.val(value).trigger('change');
  });

  // Handle WooCommerce reset variations
  $(document.body).on('reset_data', function() {
    $('.wk-swatch').removeClass('is-selected');
  });

}(jQuery));


/* ══════════════════════════════════════════════════════════════
   PRODUCT GALLERY — Swipe + Thumbnails + Zoom + Lightbox
   ══════════════════════════════════════════════════════════════ */
(function() {
  var track    = document.getElementById('wk-gallery-track');
  var mainEl   = document.getElementById('wk-gallery-main');
  if (!track || !mainEl) return;

  // Height is defined by the image's own aspect-ratio CSS.
  // No JS height setting needed — prevents the '0px height' bug.

  var slides   = Array.prototype.slice.call(track.querySelectorAll('.wk-gallery-slide'));
  var thumbs   = Array.prototype.slice.call(document.querySelectorAll('.wk-gallery-thumb'));
  var dots     = Array.prototype.slice.call(document.querySelectorAll('.wk-gallery-dot'));
  var counter  = document.getElementById('wk-gallery-counter');
  var prevBtn  = document.getElementById('wk-gallery-prev');
  var nextBtn  = document.getElementById('wk-gallery-next');
  var expandBtn= document.getElementById('wk-gallery-expand');
  var total    = slides.length;
  var cur      = 0;
  var isTouch  = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

  /* ── Initialize slide widths explicitly ────────────────────── */
  function _initSlides() {
    // Use rAF to ensure aspect-ratio container has resolved its height first
    requestAnimationFrame(function() {
      var w = mainEl.offsetWidth;
      if (w > 0) {
        slides.forEach(function(sl) {
          sl.style.minWidth = w + 'px';
          sl.style.width    = w + 'px';
        });
        goTo(cur, true);
      }
    });
  }
  /* Run after layout is settled - use two rAF calls for maximum reliability */
  function _scheduleInit() {
    requestAnimationFrame(function() {
      requestAnimationFrame(function() {
        _initSlides();
      });
    });
  }
  if (document.readyState === 'complete') {
    _scheduleInit();
  } else {
    window.addEventListener('load', _scheduleInit);
  }
  /* Also run immediately in case styles are already computed */
  _scheduleInit();
  // ResizeObserver (modern) for accurate gallery resize handling
  if (typeof ResizeObserver !== 'undefined') {
    var _resizeObs = new ResizeObserver(function() {
      _initSlides();
      goTo(cur, true);
    });
    _resizeObs.observe(mainEl);
  } else {
    window.addEventListener('resize', function() { _initSlides(); goTo(cur, true); });
  }

  /* ── Navigate to slide n ────────────────────────────────────── */
  function goTo(n, noAnim) {
    cur = ((n % total) + total) % total;
    if (noAnim) { track.classList.add('no-transition'); }
    // Use px offset for accuracy when slides have explicit px widths
    var slideW = slides.length > 0 ? (slides[0].offsetWidth || mainEl.offsetWidth) : mainEl.offsetWidth;
    track.style.transform = 'translateX(-' + (cur * slideW) + 'px)';
    if (noAnim) { track.offsetHeight; track.classList.remove('no-transition'); }

    if (counter) counter.textContent = (cur + 1) + ' / ' + total;

    thumbs.forEach(function(t, i) { t.classList.toggle('is-active', i === cur); });
    dots.forEach(function(d, i)   { d.classList.toggle('is-active', i === cur); });

    if (thumbs[cur]) {
      thumbs[cur].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    }
    updateZoom();
  }

  /* ── Thumbnail clicks ───────────────────────────────────────── */
  thumbs.forEach(function(t) {
    t.addEventListener('click', function() { goTo(parseInt(this.dataset.index, 10)); });
  });
  dots.forEach(function(d) {
    d.addEventListener('click', function() { goTo(parseInt(this.dataset.index, 10)); });
  });
  if (prevBtn) prevBtn.addEventListener('click', function() { goTo(cur - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function() { goTo(cur + 1); });

  /* ── Keyboard ───────────────────────────────────────────────── */
  document.addEventListener('keydown', function(e) {
    if (document.querySelector('.wk-lightbox')) return;
    if (!document.getElementById('wk-gallery-main')) return;
    if (e.key === 'ArrowLeft')  { e.preventDefault(); goTo(cur - 1); }
    if (e.key === 'ArrowRight') { e.preventDefault(); goTo(cur + 1); }
  });

  /* ── Touch/Mouse swipe ──────────────────────────────────────── */
  var sx = 0, sy = 0, dx = 0, swiping = false, locked = false;

  function dragStart(cx, cy) {
    sx = cx; sy = cy; dx = 0; swiping = true; locked = false;
    track.style.transition = 'none';
  }
  function dragMove(cx, cy) {
    if (!swiping) return;
    dx = cx - sx;
    var dy = cy - sy;
    if (!locked) {
      if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
      if (Math.abs(dy) > Math.abs(dx)) { swiping = false; track.style.transition = ''; return; }
      locked = true;
    }
    var rubber = ((cur === 0 && dx > 0) || (cur === total - 1 && dx < 0)) ? 0.25 : 1;
    var slideW2 = slides.length > 0 ? (slides[0].offsetWidth || mainEl.offsetWidth) : mainEl.offsetWidth;
    track.style.transform = 'translateX(calc(-' + (cur * slideW2) + 'px + ' + (dx * rubber) + 'px))';
  }
  function dragEnd() {
    if (!swiping) return;
    swiping = false; locked = false;
    track.style.transition = '';
    var threshold = mainEl.offsetWidth * 0.2;
    if (dx < -threshold)     goTo(cur + 1);
    else if (dx > threshold) goTo(cur - 1);
    else                     goTo(cur);
  }

  track.addEventListener('touchstart', function(e) { dragStart(e.touches[0].clientX, e.touches[0].clientY); }, { passive: true });
  track.addEventListener('touchmove',  function(e) {
    dragMove(e.touches[0].clientX, e.touches[0].clientY);
    if (locked && e.cancelable) e.preventDefault();
  }, { passive: false });
  track.addEventListener('touchend',   function(e) { dx = e.changedTouches[0].clientX - sx; dragEnd(); }, { passive: true });
  track.addEventListener('touchcancel', dragEnd, { passive: true });

  track.addEventListener('mousedown', function(e) { dragStart(e.clientX, e.clientY); e.preventDefault(); });
  document.addEventListener('mousemove', function(e) { dragMove(e.clientX, e.clientY); });
  document.addEventListener('mouseup',   function(e) { dx = e.clientX - sx; dragEnd(); });

  /* ── Hover zoom (desktop only) ──────────────────────────────── */
  var cfg     = window.wk_zoom_cfg || {};
  var zEnabled= !isTouch && cfg.enabled === '1' && window.innerWidth >= 768;
  var panelEl = document.getElementById('wk-zoom-panel');
  var panelImg= document.getElementById('wk-zoom-img');
  var lensEl  = null;

  function updateZoom() {
    if (!zEnabled || !panelImg) return;
    var slide = slides[cur];
    if (!slide) return;
    var img = slide.querySelector('.wk-gallery-img');
    panelImg.src = (img && img.getAttribute('data-full')) || (img && img.src) || '';
  }

  if (zEnabled && panelEl) {
    var lensSize = parseInt(cfg.lens_size, 10) || 110;
    var magnify  = parseFloat(cfg.magnify)     || 2.5;

    lensEl = document.createElement('div');
    lensEl.className = 'wk-zoom-lens';
    lensEl.style.cssText = 'width:' + lensSize + 'px;height:' + lensSize + 'px;border-radius:50%;position:absolute;pointer-events:none;display:none;';
    mainEl.appendChild(lensEl);
    panelEl.style.display = 'none';

    mainEl.addEventListener('mouseenter', function() {
      updateZoom();
      lensEl.style.display = 'block';
      panelEl.style.display = 'block';
      mainEl.style.cursor = 'crosshair';
    });
    mainEl.addEventListener('mouseleave', function() {
      lensEl.style.display = 'none';
      panelEl.style.display = 'none';
      mainEl.style.cursor = '';
    });
    mainEl.addEventListener('mousemove', function(e) {
      var r = mainEl.getBoundingClientRect();
      var x = e.clientX - r.left, y = e.clientY - r.top;
      var hw = lensSize / 2;
      x = Math.max(hw, Math.min(r.width - hw, x));
      y = Math.max(hw, Math.min(r.height - hw, y));
      lensEl.style.left = (x - hw) + 'px';
      lensEl.style.top  = (y - hw) + 'px';
      if (panelImg && panelImg.src) {
        var pw = panelEl.offsetWidth || 400, ph = panelEl.offsetHeight || 400;
        panelImg.style.cssText = 'position:absolute;width:' + (r.width*magnify) + 'px;height:' + (r.height*magnify) + 'px;' +
          'left:' + -(x*magnify - pw/2) + 'px;top:' + -(y*magnify - ph/2) + 'px;max-width:none;pointer-events:none;';
      }
    });
    updateZoom();
  }

  /* ── Lightbox ───────────────────────────────────────────────── */
  function openLightbox(startIdx) {
    var imgs = slides.map(function(s) {
      var img = s.querySelector('.wk-gallery-img');
      return { src: s.dataset.full || (img && img.src) || '', alt: img ? img.alt : '' };
    }).filter(function(i) { return i.src; });
    if (!imgs.length) return;

    var lbIdx = startIdx != null ? startIdx : cur;
    var lb = document.createElement('div');
    lb.className = 'wk-lightbox';
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');

    var slides_html = imgs.map(function(img, i) {
      return '<div class="wk-lightbox__slide"><img class="wk-lightbox__img" src="' +
        img.src + '" alt="' + img.alt + '" loading="' + (i === lbIdx ? 'eager' : 'lazy') + '"/></div>';
    }).join('');

    lb.innerHTML =
      '<div class="wk-lightbox__track" style="display:flex;width:100%;overflow:hidden;transition:transform .3s ease;" id="wk-lb-track">' +
        slides_html +
      '</div>' +
      '<button class="wk-lightbox__close" aria-label="Close">&times;</button>' +
      (imgs.length > 1 ? '<button class="wk-lightbox__nav wk-lightbox__prev">&#8249;</button><button class="wk-lightbox__nav wk-lightbox__next">&#8250;</button>' : '') +
      '<div class="wk-lightbox__counter">' + (lbIdx+1) + ' / ' + imgs.length + '</div>';

    document.body.appendChild(lb);
    document.body.style.overflow = 'hidden';

    var lbTrack   = lb.querySelector('.wk-lightbox__track');
    var lbCounter = lb.querySelector('.wk-lightbox__counter');

    function lbGoTo(n) {
      lbIdx = ((n % imgs.length) + imgs.length) % imgs.length;
      lbTrack.style.transform = 'translateX(-' + (lbIdx*100) + '%)';
      if (lbCounter) lbCounter.textContent = (lbIdx+1) + ' / ' + imgs.length;
    }
    function closeLb() { lb.remove(); document.body.style.overflow = ''; }

    lb.querySelector('.wk-lightbox__close').addEventListener('click', closeLb);
    lb.addEventListener('click', function(e) { if (e.target === lb) closeLb(); });

    var lbPrev = lb.querySelector('.wk-lightbox__prev');
    var lbNext = lb.querySelector('.wk-lightbox__next');
    if (lbPrev) lbPrev.addEventListener('click', function() { lbGoTo(lbIdx - 1); });
    if (lbNext) lbNext.addEventListener('click', function() { lbGoTo(lbIdx + 1); });

    function lbKey(e) {
      if (e.key === 'Escape') { closeLb(); document.removeEventListener('keydown', lbKey); }
      if (e.key === 'ArrowLeft')  lbGoTo(lbIdx - 1);
      if (e.key === 'ArrowRight') lbGoTo(lbIdx + 1);
    }
    document.addEventListener('keydown', lbKey);

    // Swipe in lightbox
    var lbSx = 0;
    lbTrack.addEventListener('touchstart', function(e) { lbSx = e.touches[0].clientX; }, { passive: true });
    lbTrack.addEventListener('touchend',   function(e) {
      var d = e.changedTouches[0].clientX - lbSx;
      if (Math.abs(d) > 50) d < 0 ? lbGoTo(lbIdx+1) : lbGoTo(lbIdx-1);
    }, { passive: true });
  }

  /* Open lightbox on expand button or desktop click */
  if (expandBtn) expandBtn.addEventListener('click', function(e) { e.stopPropagation(); openLightbox(cur); });
  mainEl.addEventListener('click', function(e) {
    if (Math.abs(dx) > 8) return;
    if (e.target.closest('.wk-gallery-arrow, .wk-gallery-expand, .wk-gallery-dot')) return;
    openLightbox(cur);
  });

  /* Double-tap for mobile lightbox */
  var lastTap = 0;
  mainEl.addEventListener('touchend', function() {
    var now = Date.now();
    if (now - lastTap < 300) openLightbox(cur);
    lastTap = now;
  });

})();

/* ══════════════════════════════════════════════════════════════
   WHITEKURTI PRO — New Features JS
   ══════════════════════════════════════════════════════════════ */

/* ── Countdown Timer Bar ─────────────────────────────────────────────── */
(function() {
  var bar = document.getElementById('wk-timer-bar');
  var cd  = document.getElementById('wk-timer-countdown');
  var closeBtn = document.getElementById('wk-timer-close');
  if (!bar || !cd) return;

  var mode    = cd.getAttribute('data-mode');
  var endTs   = parseInt(cd.getAttribute('data-end'), 10) * 1000;
  var sessMins = parseInt(cd.getAttribute('data-session-mins'), 10) || 30;

  // Session mode: store end time in sessionStorage
  if (mode === 'session') {
    var storedEnd = sessionStorage.getItem('wk_timer_end');
    if (!storedEnd) {
      var newEnd = Date.now() + sessMins * 60 * 1000;
      sessionStorage.setItem('wk_timer_end', newEnd);
      endTs = newEnd;
    } else {
      endTs = parseInt(storedEnd, 10);
    }
  }

  window.wkShowToast = showToast; // expose globally for wishlist, CTL etc.

  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function updateTimer() {
    var now  = Date.now();
    var diff = endTs - now;
    if (diff <= 0) {
      bar.classList.add('is-expired');
      if (mode === 'session') sessionStorage.removeItem('wk_timer_end');
      return;
    }
    var h = Math.floor(diff / 3600000);
    var m = Math.floor((diff % 3600000) / 60000);
    var s = Math.floor((diff % 60000) / 1000);
    var elH = document.getElementById('wk-tc-h');
    var elM = document.getElementById('wk-tc-m');
    var elS = document.getElementById('wk-tc-s');
    if (elH) elH.textContent = pad(h);
    if (elM) elM.textContent = pad(m);
    if (elS) elS.textContent = pad(s);
  }

  updateTimer();
  setInterval(updateTimer, 1000);

  if (closeBtn) {
    closeBtn.addEventListener('click', function() {
      bar.style.display = 'none';
      sessionStorage.setItem('wk_timer_closed', '1');
    });
    if (sessionStorage.getItem('wk_timer_closed') === '1') {
      bar.style.display = 'none';
    }
  }
})();

/* ── Social Proof Notifications ─────────────────────────────────────── */
(function() {
  // Run after DOM + scripts fully loaded
  function initSocialProof() {
    var sp = window.wk_social_proof;
    if (!sp) { return; } // data not localized yet
    if (sp.enabled === '0') return;
    var products  = sp.products || [];
    var interval  = parseInt(sp.interval, 10) || 28000;
    if (!products.length) return;

    var popup    = document.getElementById('wk-sp-popup');
    var spName   = document.getElementById('wk-sp-name');
    var spAction = document.getElementById('wk-sp-action');
    var spTime   = document.getElementById('wk-sp-time');
    var spImg    = document.getElementById('wk-sp-img');
    var spClose  = document.getElementById('wk-sp-close');
    if (!popup || !spName) return;

    var firstNames = ['Priya','Ananya','Sneha','Pooja','Neha','Meera','Kavya','Asha','Sonal','Divya','Riya','Nidhi','Swati','Pallavi','Sunita','Geeta','Rekha','Alka','Seema','Shweta','Preeti','Vandana','Sonam','Anjali','Kirti','Rupali','Mansi','Vidya','Lata','Bindu','Shalini','Pratima','Archana','Nisha','Rashmi','Poonam','Yamini','Shilpa','Madhu','Veena','Usha','Sunanda','Kavita','Hema','Beena','Tara','Jyoti','Komal','Shreya','Payal'];
    var cities = ['Mumbai','Delhi','Jaipur','Surat','Pune','Bengaluru','Hyderabad','Chennai','Kolkata','Ahmedabad','Lucknow','Indore','Bhopal','Nagpur','Chandigarh','Kochi','Coimbatore','Vadodara','Agra','Nashik','Udaipur','Jodhpur','Amritsar','Varanasi','Patna','Guwahati','Bhubaneswar','Vijayawada','Mysuru','Rajkot','Dehradun','Jabalpur','Meerut','Faridabad','Allahabad','Raipur','Ludhiana','Madurai','Visakhapatnam','Ranchi'];

    var usedN = [], usedC = [], usedP = [];

    function getNext(arr, used) {
      if (used.length >= arr.length) used.length = 0;
      var avail = arr.filter(function(x,i){ return used.indexOf(i) === -1; });
      if (!avail.length) { used.length = 0; avail = arr.map(function(_,i){ return i; }); }
      var pick = avail[Math.floor(Math.random() * avail.length)];
      used.push(pick);
      return arr[pick];
    }

    var hideTimer = null;

    function showNotification() {
      var name    = getNext(firstNames, usedN);
      var city    = getNext(cities, usedC);
      var product = getNext(products, usedP);
      var ago     = Math.floor(Math.random() * 28) + 2;

      spName.textContent   = name + ' from ' + city;
      spAction.textContent = 'just bought ' + product.name;
      spTime.textContent   = ago + ' minutes ago';

      var imgWrap = document.getElementById('wk-sp-img-wrap');
      if (spImg && product.img) {
        spImg.src = product.img;
        if (imgWrap) imgWrap.style.display = 'block';
      } else {
        if (imgWrap) imgWrap.style.display = 'none';
      }

      popup.style.display = 'block';
      clearTimeout(hideTimer);

      // Force reflow then animate
      void popup.offsetWidth;
      popup.classList.add('is-visible');

      hideTimer = setTimeout(function() {
        popup.classList.remove('is-visible');
        setTimeout(function(){ if (!popup.classList.contains('is-visible')) popup.style.display = ''; }, 500);
      }, 5500);
    }

    if (spClose) {
      spClose.addEventListener('click', function() {
        clearTimeout(hideTimer);
        popup.classList.remove('is-visible');
        setTimeout(function(){ popup.style.display = ''; }, 400);
      });
    }

    // First popup after 7 seconds, then repeat
    setTimeout(function() {
      showNotification();
      setInterval(showNotification, interval);
    }, 7000);
  }

  // Run when DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSocialProof);
  } else {
    initSocialProof();
  }
  })();

/* ── Reviews Load More ───────────────────────────────────────────────── */
(function($) {
  var $btn = $('#wk-reviews-load-more');
  if (!$btn.length) return;

  $btn.on('click', function() {
    if (typeof wk_params === 'undefined') return;
    var page    = parseInt($btn.attr('data-page'), 10) + 1;
    var product = $btn.attr('data-product') || 0;
    $btn.text('Loading...').prop('disabled', true);

    $.post(wk_params.ajax_url, {
      action:     'wk_load_more_reviews',
      product_id: product,
      page:       page,
      nonce:      wk_params.nonce
    }, function(res) {
      if (res.success && res.data.html) {
        $('.wk-reviews-list').append(res.data.html);
        $btn.attr('data-page', page);
        if (!res.data.has_more) {
          $btn.remove();
        } else {
          $btn.html('Read all reviews <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>').prop('disabled', false);
        }
      } else {
        $btn.remove();
      }
    });
  });
}(jQuery));

/* ── Auth Tabs ─────────────────────────────────────────────────── */
(function() {
  var tabs = document.querySelectorAll('.wk-auth-tab');
  tabs.forEach(function(tab) {
    tab.addEventListener('click', function() {
      var targetId = this.getAttribute('data-target');
      var allPanes = document.querySelectorAll('.wk-auth-pane');
      var allTabs  = document.querySelectorAll('.wk-auth-tab');
      allTabs.forEach(function(t) { t.classList.remove('is-active'); });
      allPanes.forEach(function(p) { p.classList.remove('is-active'); });
      this.classList.add('is-active');
      var pane = document.getElementById(targetId);
      if (pane) pane.classList.add('is-active');
    });
  });
})();

/* ── Password Show/Hide Toggle ─────────────────────────────────── */
(function() {
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.wk-pw-toggle');
    if (!btn) return;
    var targetId = btn.getAttribute('data-target');
    var input    = document.getElementById(targetId);
    if (!input) return;
    var isShowing = input.type === 'text';
    input.type = isShowing ? 'password' : 'text';
    btn.setAttribute('aria-pressed', String(!isShowing));
    var showIcon = btn.querySelector('.wk-eye--open');
    var hideIcon = btn.querySelector('.wk-eye--closed');
    if (showIcon) showIcon.style.display = isShowing ? '' : 'none';
    if (hideIcon) hideIcon.style.display = isShowing ? 'none' : '';
  });
})();

/* ── Fake Purchase Notifications ── */
(function() {
  function init() {
    var cfg = window.wk_fake_notifications;
    if (!cfg || cfg.enabled !== '1') return;

    var popup    = document.getElementById('wk-fn-popup');
    if (!popup) return;

    var elName   = document.getElementById('wk-fn-name');
    var elAction = document.getElementById('wk-fn-action');
    var elTime   = document.getElementById('wk-fn-time');
    var elImg    = document.getElementById('wk-fn-img');
    var elImgWrap= document.getElementById('wk-fn-img-wrap');
    var elClose  = document.getElementById('wk-fn-close');

    var products = cfg.products || [];
    var cities   = cfg.cities   || [];
    var names    = ['Priya','Ananya','Sneha','Pooja','Neha','Meera','Kavya','Asha','Sonal','Divya',
                    'Riya','Nidhi','Swati','Pallavi','Sunita','Geeta','Rekha','Alka','Seema','Shweta',
                    'Preeti','Sonam','Anjali','Kirti','Rupali','Mansi','Vidya','Lata','Shalini','Rashmi',
                    'Poonam','Yamini','Shilpa','Madhu','Komal','Shreya','Payal','Kavita','Hema','Tara'];

    if (!products.length || !cities.length) return;

    /* ── Position the popup ────────────────────────────────────── */
    var pos = cfg.position || 'bottom-left';
    var isMobile = window.innerWidth < 768;

    // Apply base position styles
    popup.style.position   = 'fixed';
    popup.style.zIndex     = '99998';
    popup.style.maxWidth   = isMobile ? 'calc(100vw - 24px)' : '300px';
    popup.style.width      = isMobile ? 'calc(100vw - 24px)' : '300px';
    popup.style.display    = 'none';

    // Clear any previous positioning
    popup.style.top    = '';
    popup.style.bottom = '';
    popup.style.left   = '';
    popup.style.right  = '';
    popup.style.transform = '';

    // On mobile always use bottom-left
    var effectivePos = isMobile ? 'bottom-left' : pos;

    // Apply position
    if (effectivePos === 'bottom-left')   { popup.style.bottom = '80px'; popup.style.left  = '14px'; }
    if (effectivePos === 'bottom-right')  { popup.style.bottom = '80px'; popup.style.right = '14px'; }
    if (effectivePos === 'bottom-center') { popup.style.bottom = '80px'; popup.style.left  = '50%'; popup.style.transform = 'translateX(-50%)'; }
    if (effectivePos === 'top-left')      { popup.style.top    = '80px'; popup.style.left  = '14px'; }
    if (effectivePos === 'top-right')     { popup.style.top    = '80px'; popup.style.right = '14px'; }
    if (effectivePos === 'top-center')    { popup.style.top    = '80px'; popup.style.left  = '50%'; popup.style.transform = 'translateX(-50%)'; }

    // On mobile, position above bottom nav if visible
    var bottomNav = document.getElementById('wk-bottom-nav');
    if (isMobile && bottomNav) {
      popup.style.bottom = (bottomNav.offsetHeight + 12) + 'px';
    }

    /* ── Get product image from current page ────────────────────── */
    function getCurrentPageImage() {
      // On product pages, get the main product image
      var mainImg = document.querySelector('.wk-gallery-img, .woocommerce-product-gallery__image img');
      if (mainImg) return mainImg.src;
      return '';
    }

    /* ── Random helpers ─────────────────────────────────────────── */
    var usedNames = [], usedCities = [], usedProducts = [];
    function pick(arr, used) {
      if (used.length >= arr.length) used.length = 0;
      var avail = [];
      for (var i = 0; i < arr.length; i++) {
        if (used.indexOf(i) === -1) avail.push(i);
      }
      var idx = avail[Math.floor(Math.random() * avail.length)];
      used.push(idx);
      return arr[idx];
    }

    var hideTimer = null;
    var isVisible = false;

    /* ── Show one notification ──────────────────────────────────── */
    function show() {
      if (isVisible) return; // don't stack
      /* Never show when menu, cart, or search overlay is open */
      if (document.body.classList.contains('wk-overlay-open')) return;
      if (document.body.style.overflow === 'hidden') return;

      var name    = pick(names, usedNames);
      var city    = pick(cities, usedCities);
      var product = (cfg.product_specific === '1' && cfg.current_product)
                    ? cfg.current_product
                    : pick(products, usedProducts);
      var minsAgo = Math.floor(Math.random() * 45) + 2;

      if (elName)   elName.textContent   = name + ' from ' + city;
      if (elAction) elAction.textContent = 'just bought ' + product;
      if (elTime)   elTime.textContent   = '● ' + minsAgo + ' min ago';

      // Product image - use current page image if product_specific, else hide
      var imgSrc = '';
      if (cfg.product_specific === '1') {
        imgSrc = getCurrentPageImage();
      }

      // Image / emoji logic for new popup HTML
      var elEmoji = document.getElementById('wk-fn-emoji');
      if (imgSrc && cfg.show_image === '1' && elImg) {
        elImg.src = imgSrc;
        elImg.style.display = 'block';
        if (elEmoji) elEmoji.style.display = 'none';
      } else {
        if (elImg) elImg.style.display = 'none';
        if (elEmoji) elEmoji.style.display = '';
      }

      /* Entrance animation */
      popup.style.display    = 'block';
      popup.style.opacity    = '0';
      popup.style.transition = 'none';

      // Slide direction based on position
      var slideFrom = (effectivePos.indexOf('right') !== -1)
                      ? 'translateX(110%)'
                      : 'translateX(-110%)';
      if (effectivePos.indexOf('center') !== -1) slideFrom = 'translateY(30px)';

      var baseTransform = popup.style.transform || '';
      popup.style.transform = (baseTransform ? baseTransform + ' ' : '') + slideFrom;

      isVisible = true;
      void popup.offsetWidth; // reflow

      popup.style.transition = 'opacity .35s ease, transform .4s cubic-bezier(.34,1.56,.64,1)';
      popup.style.opacity    = '1';
      popup.style.transform  = baseTransform || 'none';

      /* Auto-hide */
      clearTimeout(hideTimer);
      var dur = parseInt(cfg.display_duration, 10) || 5000;
      hideTimer = setTimeout(hide, dur);
    }

    function hide() {
      if (!isVisible) return;
      popup.style.transition = 'opacity .3s ease, transform .3s ease';
      popup.style.opacity    = '0';
      popup.style.transform  = (popup.style.transform || '') + ' translateY(8px)';
      setTimeout(function() {
        popup.style.display = 'none';
        popup.style.transform = popup.style.transform.replace(' translateY(8px)', '');
        isVisible = false;
      }, 320);
    }

    /* Close button */
    if (elClose) {
      elClose.addEventListener('click', function() {
        clearTimeout(hideTimer);
        hide();
      });
    }

    /* Touch swipe to dismiss */
    var swipeStartX = 0;
    popup.addEventListener('touchstart', function(e) {
      swipeStartX = e.touches[0].clientX;
    }, { passive: true });
    popup.addEventListener('touchend', function(e) {
      var diff = e.changedTouches[0].clientX - swipeStartX;
      if (Math.abs(diff) > 50) {
        clearTimeout(hideTimer);
        hide();
      }
    }, { passive: true });

    /* Start loop */
    var firstDelay = parseInt(cfg.first_delay, 10) || 8000;
    var interval   = parseInt(cfg.interval, 10)     || 30000;

    setTimeout(function() {
      show();
      setInterval(show, interval);
    }, firstDelay);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/* old zoom code removed — new gallery handles zoom */


/* ── Exit Popup: Mobile Bottom Sheet — swipe-to-dismiss ── */
(function() {
  var popup = document.getElementById('wk-exit-popup');
  if (!popup) return;
  var modal = popup.querySelector('.wk-exit-popup__modal');
  if (!modal) return;
  if (window.innerWidth > 767) return; // desktop only uses original logic

  // Animated close: play slide-out before hiding
  function closeSheet() {
    modal.classList.add('is-closing');
    setTimeout(function() {
      modal.classList.remove('is-closing');
      popup.hidden = true;
      document.body.style.overflow = '';
    }, 280);
  }

  // Override the existing closePopup with animated version
  // by re-binding the close buttons to use closeSheet
  ['wk-ep-close','wk-ep-overlay','wk-ep-dismiss'].forEach(function(id) {
    var el = document.getElementById(id);
    if (!el) return;
    // Clone to strip old listeners
    var clone = el.cloneNode(true);
    el.parentNode.replaceChild(clone, el);
    clone.addEventListener('click', closeSheet);
  });

  // Swipe-to-dismiss: drag sheet down > 80px to dismiss
  var startY = 0, currentY = 0, dragging = false;

  modal.addEventListener('touchstart', function(e) {
    // Only start drag from top 60px of sheet (handle area)
    var touch = e.touches[0];
    var rect  = modal.getBoundingClientRect();
    if (touch.clientY - rect.top > 80) return; // too far down — user is scrolling content
    startY   = touch.clientY;
    currentY = touch.clientY;
    dragging = true;
    modal.style.transition = 'none';
  }, { passive: true });

  modal.addEventListener('touchmove', function(e) {
    if (!dragging) return;
    currentY = e.touches[0].clientY;
    var delta = currentY - startY;
    if (delta < 0) { delta = 0; } // no upward drag
    modal.style.transform = 'translateY(' + delta + 'px)';
  }, { passive: true });

  modal.addEventListener('touchend', function() {
    if (!dragging) return;
    dragging = false;
    modal.style.transition = '';
    var delta = currentY - startY;
    if (delta > 80) {
      // Dragged far enough — close
      modal.style.transform = '';
      closeSheet();
    } else {
      // Snap back
      modal.style.transform = '';
    }
  }, { passive: true });
})();

/* ══════════════════════════════════════════════════════════════
   PRODUCT PAGE — Size Selector, Buy Now, Add to Cart, Variation Price
   ══════════════════════════════════════════════════════════════ */
(function() {
  var variationsWrap = document.getElementById('wk-variations-wrap');
  var wcForm  = document.querySelector('#wk-wc-form .variations_form, .variations_form');
  var notice  = document.getElementById('wk-variation-notice');
  var buyNow  = document.getElementById('wk-buy-now');
  var atcBtn  = document.getElementById('wk-atc-btn');
  var total   = variationsWrap ? variationsWrap.querySelectorAll('.wk-variation-group').length : 0;
  var selected = {};

  // ── Size/Color pill selection ─────────────────────────────────────────
  if (variationsWrap) {
    var pills = variationsWrap.querySelectorAll('.wk-var-opt');

    pills.forEach(function(pill) {
      pill.addEventListener('click', function() {
        var attr  = this.dataset.attr;
        var value = this.dataset.value;

        // Deselect siblings in same group
        variationsWrap.querySelectorAll('.wk-var-opt[data-attr="' + attr + '"]')
          .forEach(function(p) { p.classList.remove('is-selected'); });

        this.classList.add('is-selected');
        selected[attr] = value;

        // Update label
        var labelEl = document.getElementById('wk-var-selected-' + attr);
        if (labelEl) labelEl.textContent = ': ' + value;

        // Sync WooCommerce hidden select
        if (wcForm) {
          var wcSelect = wcForm.querySelector('select[name="attribute_' + attr + '"], select[name="' + attr + '"]');
          if (wcSelect) {
            wcSelect.value = value;
            wcSelect.dispatchEvent(new Event('change', { bubbles: true }));
          }
        }
        if (notice) notice.hidden = true;
      });
    });
  }

  // ── Check all variants selected ───────────────────────────────────────
  function allSelected() {
    if (!variationsWrap) return true;
    var groups = variationsWrap.querySelectorAll('.wk-variation-group');
    for (var i = 0; i < groups.length; i++) {
      if (!selected[groups[i].dataset.attr]) return false;
    }
    return true;
  }

  function showNotice() {
    if (notice) { notice.hidden = false; }
    if (variationsWrap) {
      variationsWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      variationsWrap.style.animation = 'none';
      variationsWrap.offsetWidth; // reflow
      variationsWrap.style.animation = 'wkShake .4s ease';
      setTimeout(function() { variationsWrap.style.animation = ''; }, 400);
    }
  }

  // ── WooCommerce variation events (jQuery) ─────────────────────────────
  if (window.jQuery && wcForm) {
    jQuery(wcForm).on('found_variation', function(e, variation) {
      // Price update
      var prEl  = document.querySelector('.wk-pdp__price .wk-price');
      var wasEl = document.querySelector('.wk-pdp__price .wk-price-was');
      var pctEl = document.querySelector('.wk-pdp__price .wk-price-save');

      if (prEl && variation.display_price != null) {
        prEl.textContent = '\u20b9' + Number(Math.round(variation.display_price)).toLocaleString('en-IN');
      }
      if (variation.display_regular_price && variation.display_regular_price > variation.display_price) {
        var disc = Math.round((1 - variation.display_price / variation.display_regular_price) * 100);
        if (wasEl) { wasEl.textContent = '\u20b9' + Number(Math.round(variation.display_regular_price)).toLocaleString('en-IN'); wasEl.style.display = ''; }
        if (pctEl) { pctEl.textContent = '(' + disc + '% OFF)'; pctEl.style.display = ''; }
      } else {
        if (wasEl) wasEl.style.display = 'none';
        if (pctEl) pctEl.style.display = 'none';
      }

      // Stock
      if (buyNow) buyNow.disabled = !variation.is_purchasable || !variation.is_in_stock;
      if (atcBtn) {
        atcBtn.disabled = !variation.is_purchasable || !variation.is_in_stock;
        if (atcBtn.disabled) {
          atcBtn.textContent = 'Out of Stock';
        } else if (atcBtn.textContent.trim() === 'Out of Stock') {
          atcBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.99 1.61h9.72a2 2 0 001.99-1.61L23 6H6"/></svg> Add to Cart';
        }
      }
      if (notice) notice.hidden = true;
    });

    jQuery(wcForm).on('reset_data', function() {
      if (buyNow) buyNow.disabled = false;
      if (atcBtn) atcBtn.disabled = false;
    });
  }

  // ── Buy Now ───────────────────────────────────────────────────────────
  if (buyNow) {
    buyNow.addEventListener('click', function() {
      if (variationsWrap && !allSelected()) { showNotice(); return; }

      var productId   = this.dataset.productId;
      var productType = this.dataset.productType;

      if (productType === 'variable' && wcForm) {
        var varIdEl = wcForm.querySelector('.variation_id');
        var varId   = varIdEl ? varIdEl.value : '0';
        if (!varId || varId === '0') { showNotice(); return; }
        // Add to cart then redirect
        var params = window.wk_params || {};
        fetch(params.ajax_url || '/wp-admin/admin-ajax.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=wk_add_to_cart&product_id=' + productId +
                '&variation_id=' + varId + '&quantity=1&nonce=' + (params.nonce || '')
        }).then(function() {
          window.location.href = params.checkout_url || '/checkout';
        }).catch(function() {
          window.location.href = params.checkout_url || '/checkout';
        });
      } else {
        var p2 = window.wk_params || {};
        window.location.href = (p2.checkout_url || '/checkout') + '?add-to-cart=' + productId;
      }
    });
  }

  // ── Add to Cart ───────────────────────────────────────────────────────
  if (atcBtn) {
    atcBtn.addEventListener('click', function() {
      if (variationsWrap && !allSelected()) { showNotice(); return; }

      var wcNativeBtn = document.querySelector('#wk-wc-form .single_add_to_cart_button');
      atcBtn.classList.add('is-loading');
      atcBtn.disabled = true;

      if (wcNativeBtn) {
        // Variable product: click hidden WC button (triggers WC variation JS)
        wcNativeBtn.click();
        if (window.jQuery) {
          jQuery(document.body).one('added_to_cart', function() {
            atcBtn.classList.remove('is-loading');
            atcBtn.disabled = false;
            atcBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Added!';
            setTimeout(function() {
              atcBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.99 1.61h9.72a2 2 0 001.99-1.61L23 6H6"/></svg> Add to Cart';
            }, 2000);
          });
        } else {
          setTimeout(function() { atcBtn.classList.remove('is-loading'); atcBtn.disabled = false; }, 1500);
        }
      } else {
        // Simple product: AJAX
        var productId = atcBtn.dataset.productId;
        var p3 = window.wk_params || {};
        fetch(p3.ajax_url || '/wp-admin/admin-ajax.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=wk_add_to_cart&product_id=' + productId + '&quantity=1&nonce=' + (p3.nonce || '')
        }).then(function(r) { return r.json(); })
        .then(function(data) {
          atcBtn.classList.remove('is-loading');
          atcBtn.disabled = false;
          if (!data.error) {
            atcBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Added!';
            if (window.jQuery) jQuery(document.body).trigger('wc_fragment_refresh');
            setTimeout(function() {
              atcBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.99 1.61h9.72a2 2 0 001.99-1.61L23 6H6"/></svg> Add to Cart';
            }, 2000);
          }
        }).catch(function() {
          atcBtn.classList.remove('is-loading');
          atcBtn.disabled = false;
        });
      }
    });
  }

})();



/* ══════════════════════════════════════════════════════════════
   HERO SECTION v3 "RANG" — Interactive Indian Fashion Hero
   Features: crossfade slides, touch ripple, gold sparkles,
   mouse parallax, gyroscope tilt, particle canvas, auto-advance
   ══════════════════════════════════════════════════════════════ */
(function() {
  'use strict';

  var hero    = document.getElementById('wk-hero');
  var track   = document.getElementById('wk-hero-track');
  var canvas  = document.getElementById('wk-hero-canvas');
  if (!hero || !track || !canvas) return;

  var slides  = Array.prototype.slice.call(track.querySelectorAll('.wk-hero__slide'));
  var dots    = Array.prototype.slice.call(hero.querySelectorAll('.wk-hero__dot'));
  var prevBtn = document.getElementById('wk-hero-prev');
  var nextBtn = document.getElementById('wk-hero-next');
  var curEl   = document.getElementById('wk-hero-cur');
  var total   = slides.length;
  var cur     = 0;
  var timer   = null;
  var INTERVAL = 4500;
  var dragging = false, startX = 0, startY = 0, diffX = 0;

  if (total < 1) return;

  /* ── Canvas setup ── */
  var ctx = canvas.getContext('2d');
  function resizeCanvas() {
    canvas.width  = hero.offsetWidth;
    canvas.height = hero.offsetHeight;
  }
  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);

  /* ── Navigate ── */
  function goTo(n, instant) {
    var prev = cur;
    cur = ((n % total) + total) % total;

    /* Update slides */
    slides.forEach(function(sl, i) {
      sl.classList.toggle('is-active', i === cur);
      sl.setAttribute('aria-hidden', i === cur ? 'false' : 'true');
    });

    /* Reset + trigger text animations on new slide */
    var newSlide = slides[cur];
    var animEls = newSlide.querySelectorAll(
      '.wk-hero__eyebrow, .wk-hero__heading-line, .wk-hero__sub, .wk-hero__actions'
    );
    animEls.forEach(function(el) {
      el.style.animation = 'none';
      el.offsetHeight; /* reflow */
      el.style.animation = '';
    });

    /* Update dots */
    dots.forEach(function(d, i) {
      d.classList.toggle('is-active', i === cur);
      d.setAttribute('aria-selected', i === cur ? 'true' : 'false');
    });

    /* Update counter */
    if (curEl) curEl.textContent = cur + 1;

    /* Restart progress bar animation */
    hero.style.animation = 'none';
    hero.offsetHeight;
    hero.style.animation = '';
  }

  /* ── Auto-advance ── */
  function startAuto() {
    clearInterval(timer);
    hero.classList.remove('is-paused');
    timer = setInterval(function() { goTo(cur + 1); }, INTERVAL);
  }
  function pauseAuto() {
    clearInterval(timer);
    hero.classList.add('is-paused');
  }
  startAuto();

  /* ── Arrow + dot controls ── */
  if (prevBtn) prevBtn.addEventListener('click', function() { goTo(cur - 1); startAuto(); });
  if (nextBtn) nextBtn.addEventListener('click', function() { goTo(cur + 1); startAuto(); });
  dots.forEach(function(dot) {
    dot.addEventListener('click', function() {
      goTo(parseInt(dot.dataset.index, 10) || 0);
      startAuto();
    });
  });

  /* ── Keyboard ── */
  hero.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft')  { goTo(cur - 1); startAuto(); }
    if (e.key === 'ArrowRight') { goTo(cur + 1); startAuto(); }
  });

  /* ── Touch / swipe ── */
  hero.addEventListener('touchstart', function(e) {
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
    diffX = 0;
    dragging = true;
    pauseAuto();
  }, { passive: true });

  hero.addEventListener('touchmove', function(e) {
    if (!dragging) return;
    diffX = e.touches[0].clientX - startX;
  }, { passive: true });

  hero.addEventListener('touchend', function(e) {
    if (!dragging) return;
    dragging = false;
    if (Math.abs(diffX) > 44 && Math.abs(diffX) > Math.abs(e.changedTouches[0].clientY - startY)) {
      goTo(diffX < 0 ? cur + 1 : cur - 1);
    }
    startAuto();
  }, { passive: true });

  /* ── Pause on hover (desktop) ── */
  hero.addEventListener('mouseenter', pauseAuto);
  hero.addEventListener('mouseleave', startAuto);

  /* ══════════════════════════════════════════════════════════
     PARTICLE SYSTEM — Touch ripple + gold sparkles
   ══════════════════════════════════════════════════════════ */
  var particles = [];

  function GoldParticle(x, y, type) {
    this.x  = x + (Math.random() - .5) * 30;
    this.y  = y + (Math.random() - .5) * 30;
    this.vx = (Math.random() - .5) * 3;
    this.vy = -(1.5 + Math.random() * 3.5);
    this.life  = 1;
    this.decay = .025 + Math.random() * .02;
    this.size  = 2 + Math.random() * 4;
    this.type  = type || 'dot';
    this.rot   = Math.random() * Math.PI * 2;
    this.rotV  = (Math.random() - .5) * .15;
    /* Get gold color from active slide theme */
    var activeSlide = slides[cur];
    var style = activeSlide ? window.getComputedStyle(activeSlide) : null;
    this.gold = (style && style.getPropertyValue('--h-gold').trim()) || '#d4a855';
  }
  GoldParticle.prototype.update = function() {
    this.x  += this.vx;
    this.y  += this.vy;
    this.vy += .04;  /* gravity */
    this.life -= this.decay;
    this.rot  += this.rotV;
  };
  GoldParticle.prototype.draw = function(ctx) {
    if (this.life <= 0) return;
    ctx.save();
    ctx.globalAlpha = Math.max(0, this.life);
    ctx.translate(this.x, this.y);
    ctx.rotate(this.rot);
    if (this.type === 'star') {
      /* 4-pointed star */
      ctx.fillStyle = this.gold;
      ctx.shadowBlur = 8;
      ctx.shadowColor = this.gold;
      var s = this.size;
      ctx.beginPath();
      for (var i = 0; i < 8; i++) {
        var a = (i * Math.PI / 4);
        var r = i % 2 === 0 ? s * 1.4 : s * .55;
        ctx.lineTo(Math.cos(a) * r, Math.sin(a) * r);
      }
      ctx.closePath();
      ctx.fill();
    } else if (this.type === 'diamond') {
      ctx.fillStyle = this.gold;
      ctx.shadowBlur = 6;
      ctx.shadowColor = this.gold;
      var d = this.size;
      ctx.beginPath();
      ctx.moveTo(0, -d * 1.4);
      ctx.lineTo(d, 0);
      ctx.lineTo(0, d * 1.4);
      ctx.lineTo(-d, 0);
      ctx.closePath();
      ctx.fill();
    } else {
      ctx.fillStyle = this.gold;
      ctx.shadowBlur = 8;
      ctx.shadowColor = this.gold;
      ctx.beginPath();
      ctx.arc(0, 0, this.size, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.restore();
  };

  /* Ripple ring */
  function RippleRing(x, y) {
    this.x = x; this.y = y;
    this.r = 0;
    this.maxR = 80 + Math.random() * 60;
    this.life = 1;
    this.speed = 2.5 + Math.random() * 2;
  }
  RippleRing.prototype.update = function() {
    this.r    += this.speed;
    this.life  = 1 - (this.r / this.maxR);
  };
  RippleRing.prototype.draw = function(ctx) {
    if (this.life <= 0) return;
    var activeSlide = slides[cur];
    var style = activeSlide ? window.getComputedStyle(activeSlide) : null;
    var gold = (style && style.getPropertyValue('--h-gold').trim()) || '#d4a855';
    ctx.save();
    ctx.globalAlpha = this.life * .5;
    ctx.strokeStyle = gold;
    ctx.lineWidth = 1.5;
    ctx.shadowBlur = 6;
    ctx.shadowColor = gold;
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
    ctx.stroke();
    ctx.restore();
  };

  var rings = [];
  var rafId = null;

  function animateCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    /* Update rings */
    rings = rings.filter(function(r) { r.update(); r.draw(ctx); return r.life > 0; });

    /* Update particles */
    particles = particles.filter(function(p) { p.update(); p.draw(ctx); return p.life > 0; });

    if (rings.length > 0 || particles.length > 0) {
      rafId = requestAnimationFrame(animateCanvas);
    } else {
      rafId = null;
    }
  }

  function spawnTouch(x, y) {
    /* Ripple rings */
    for (var r = 0; r < 3; r++) {
      rings.push(new RippleRing(x, y));
      /* Stagger ring sizes */
      rings[rings.length-1].maxR = 50 + r * 45;
    }
    /* Gold sparkles: mix of stars, diamonds, dots */
    var types = ['star','diamond','dot','star','dot','diamond','dot'];
    for (var p = 0; p < 18; p++) {
      particles.push(new GoldParticle(x, y, types[p % types.length]));
    }
    if (!rafId) animateCanvas();
  }

  /* Attach touch/click handlers */
  hero.addEventListener('touchstart', function(e) {
    var rect = canvas.getBoundingClientRect();
    var t = e.touches[0];
    spawnTouch(t.clientX - rect.left, t.clientY - rect.top);
  }, { passive: true });

  hero.addEventListener('click', function(e) {
    if (Math.abs(diffX) > 10) return; /* Don't spawn on swipe-end */
    var rect = canvas.getBoundingClientRect();
    spawnTouch(e.clientX - rect.left, e.clientY - rect.top);
  });

  /* ══════════════════════════════════════════════════════════
     PARALLAX — Mouse tilt (desktop) + Gyroscope (mobile)
   ══════════════════════════════════════════════════════════ */
  var tiltX = 0, tiltY = 0;
  var targetTX = 0, targetTY = 0;
  var rafTilt = null;

  function applyTilt() {
    /* Lerp to target */
    tiltX += (targetTX - tiltX) * .08;
    tiltY += (targetTY - tiltY) * .08;

    var slide = slides[cur];
    if (slide) {
      var content = slide.querySelector('.wk-hero__content');
      var ornament = slide.querySelector('.wk-hero__ornament');
      var floats = slide.querySelector('.wk-hero__floats');
      var deco = slide.querySelector('.wk-hero__right-deco');

      if (content)  content.style.transform  = 'translate('+(tiltX*-7)+'px,'+(tiltY*-5)+'px)';
      if (ornament) ornament.style.transform = 'translate('+(tiltX*10)+'px,'+(tiltY*8)+'px)';
      if (floats)   floats.style.transform   = 'translate('+(tiltX*16)+'px,'+(tiltY*12)+'px)';
      if (deco)     deco.style.transform     = 'translate('+(tiltX*6)+'px,'+(tiltY*4)+'px)';
    }

    if (Math.abs(tiltX - targetTX) > 0.05 || Math.abs(tiltY - targetTY) > 0.05) {
      rafTilt = requestAnimationFrame(applyTilt);
    } else {
      rafTilt = null;
    }
  }

  function startTiltRaf() {
    if (!rafTilt) rafTilt = requestAnimationFrame(applyTilt);
  }

  hero.addEventListener('mousemove', function(e) {
    var rect = hero.getBoundingClientRect();
    targetTX = (e.clientX - rect.left - rect.width  / 2) / rect.width;
    targetTY = (e.clientY - rect.top  - rect.height / 2) / rect.height;
    startTiltRaf();
  });

  hero.addEventListener('mouseleave', function() {
    targetTX = 0; targetTY = 0;
    startTiltRaf();
  });

  /* Gyroscope for mobile devices */
  var gyroSupported = false;
  if (typeof DeviceOrientationEvent !== 'undefined') {
    /* iOS 13+ requires permission */
    if (typeof DeviceOrientationEvent.requestPermission === 'function') {
      /* Only request on interaction */
      hero.addEventListener('touchend', function requestGyro() {
        DeviceOrientationEvent.requestPermission().then(function(state) {
          if (state === 'granted') gyroSupported = true;
        }).catch(function() {});
        hero.removeEventListener('touchend', requestGyro);
      }, { once: true });
    } else {
      gyroSupported = true;
    }

    window.addEventListener('deviceorientation', function(e) {
      if (!gyroSupported) return;
      targetTX = Math.max(-1, Math.min(1, (e.gamma || 0) / 25));
      targetTY = Math.max(-1, Math.min(1, ((e.beta  || 20) - 20) / 25));
      startTiltRaf();
    });
  }

  /* ══════════════════════════════════════════════════════════
     MOBILE — bottom nav class
   ══════════════════════════════════════════════════════════ */
  if (document.querySelector('.wk-bottom-nav')) {
    document.body.classList.add('has-bottom-nav');
  }

})();
