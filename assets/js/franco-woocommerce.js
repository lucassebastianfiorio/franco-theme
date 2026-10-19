/* ==========================================================================
   FRANCO DEPORTES - WOOCOMMERCE AJAX ENGINE (franco-woocommerce.js)
   Version: 2.2.0
   ========================================================================== */

(function($) {
  'use strict';

  let activeCategory = 'all';
  let activeSubcategory = 'all';
  let activeBrand = 'all';
  let activeTalle = 'all';
  let activeSexo = 'all';
  let activeColor = 'all';
  let activeMinPrice = 0;
  let activeMaxPrice = 250000;
  let defaultMinPrice = 0;
  let defaultMaxPrice = 250000;
  let activeSortBy = 'popular';
  let activeSearchTerm = '';
  let currentPage = 1;
  let isLoadingProducts = false;
  let hasMoreProducts = true;
  let storeSearchTimeout = null;
  let priceInputTimeout = null;
  let liveSearchTimeout = null;
  let liveSearchXhr = null;
  let infiniteObserver = null;
  let infiniteSentinel = null;

  /**
   * Escape a value for safe interpolation into HTML text or attribute values.
   */
  function escHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
  const escAttr = escHtml;

  /**
   * Normalize product size (talle) format.
   * Converts 3-digit formats (e.g. 400 -> 40, 405 -> 40.5, 355 -> 35.5, 380 -> 38) to standard format.
   */
  function normalizeTalle(val) {
    if (!val) return '';
    let str = String(val).trim().replace(',', '.');
    if (/^\d+$/.test(str)) {
      const num = parseInt(str, 10);
      if (num >= 150 && num <= 600) {
        const norm = num / 10;
        return Number.isInteger(norm) ? String(norm) : norm.toFixed(1);
      }
    }
    return str;
  }

  /**
   * Whether a shipping rate id is a local pickup rate.
   */
  function isPickupRate(rateId) {
    rateId = String(rateId || '');
    return rateId.indexOf('local_pickup') === 0 || rateId.indexOf('pickup_location') === 0;
  }

  function francoHasCartItems() {
    const match = document.cookie.match(/(?:^|;\s*)woocommerce_items_in_cart=([^;]*)/);
    if (match && parseInt(match[1], 10) > 0) return true;
    const badgeText = $('#cart-badge-count').text().trim();
    return badgeText !== '' && parseInt(badgeText, 10) > 0;
  }

  $(document).ready(function() {
    // Sincronizar carrito solo si hay items o estamos en la página de checkout
    if ($('#custom-franco-checkout-form').length > 0 || francoHasCartItems()) {
      francoFetchCart();
    }

    // Al abrir el drawer del carrito manualmente, asegurar datos frescos
    $(document).on('click', '#cart-btn', function() {
      francoFetchCart();
    });

    // Checkout: shipping method behaviour
    if ($('#custom-franco-checkout-form').length > 0) {
      francoApplyPickupState();
    }

    // If on Wishlist page, render wishlist products
    if ($('#wishlist-products-grid').length > 0) {
      francoRenderWishlistPage();
    }

    // Checkout: highlight the selected choice card (fallback for browsers without :has())
    $(document).on('change', '#custom-franco-checkout-form .choice-radio', function() {
      const $list = $(this).closest('.choice-list');
      $list.find('.checkout-choice-card').removeClass('active');
      $(this).closest('.checkout-choice-card').addClass('active');
    });

    // Checkout: toggle payment gateway details box safely
    $(document).on('change', '#custom-franco-checkout-form input[name="payment_method"]', function() {
      const method = $(this).val();
      if (!method) return;
      $('#custom-franco-checkout-form .payment_box').hide();
      $('#custom-franco-checkout-form .payment_box.payment_method_' + method).show();
    });

    // Checkout: shipping method change -> live shipping total
    $(document).on('change', '#custom-franco-checkout-form input[name^="shipping_method"]', function() {
      francoApplyPickupState();
      francoSetShippingMethod($(this).val(), $(this).attr('data-package') || 0);
    });

    // Cart drawer / summary actions (delegated, data-* driven)
    $(document).on('click', '[data-franco-cart-action]', function(e) {
      e.preventDefault();
      const $btn = $(this);
      const key = $btn.attr('data-key');
      const action = $btn.attr('data-franco-cart-action');
      const qty = parseInt($btn.attr('data-qty'), 10);

      if (action === 'remove') {
        francoRemoveCartItem(key);
      } else if (action === 'qty') {
        francoUpdateCartQty(key, isNaN(qty) ? 0 : qty);
      }
    });

    // Quick view: size selection and add to cart
    $(document).on('click', '#modal-size-selector .size-btn', function() {
      $('#modal-size-selector .size-btn').removeClass('active');
      $(this).addClass('active');
      window.currentModalSize = $(this).attr('data-size') || '';
    });

    $(document).on('click', '.quick-view-add-btn', function() {
      const productId = parseInt($(this).attr('data-product-id'), 10);
      if (!productId) return;
      francoAddToCart(productId, window.currentModalSize || '', 1, window.currentModalColor || '');
      if (typeof closeModal === 'function') closeModal();
    });

    // Sync client state with the server-rendered page (shop / category archives, possibly /page/N/)
    initGridStateFromServer();

    // Read initial dynamic min & max prices from filter sidebar
    const $sidebar = $('.filter-sidebar');
    if ($sidebar.length) {
      const minP = parseFloat($sidebar.attr('data-min-price'));
      const maxP = parseFloat($sidebar.attr('data-max-price'));
      if (!isNaN(minP)) {
        activeMinPrice = minP;
        defaultMinPrice = minP;
      }
      if (!isNaN(maxP)) {
        activeMaxPrice = maxP;
        defaultMaxPrice = maxP;
      }
    }

    // Helper: update active red fill bar on dual range slider track
    function updatePriceTrack() {
      const $track = $('#dual-range-track');
      if (!$track.length) return;
      const $minSlider = $('#price-min-range');
      const $maxSlider = $('#price-max-range');
      if (!$minSlider.length || !$maxSlider.length) return;

      const sliderMin = parseFloat($minSlider.attr('min')) || 0;
      const sliderMax = parseFloat($minSlider.attr('max')) || 500000;
      const currentMin = parseFloat($minSlider.val()) || 0;
      const currentMax = parseFloat($maxSlider.val()) || 500000;

      const percent1 = Math.max(0, Math.min(100, ((currentMin - sliderMin) / (sliderMax - sliderMin)) * 100));
      const percent2 = Math.max(0, Math.min(100, ((currentMax - sliderMin) / (sliderMax - sliderMin)) * 100));

      $track.css('background', `linear-gradient(to right, var(--border-color, #e2e8f0) 0%, var(--border-color, #e2e8f0) ${percent1}%, var(--color-primary, #e63946) ${percent1}%, var(--color-primary, #e63946) ${percent2}%, var(--border-color, #e2e8f0) ${percent2}%, var(--border-color, #e2e8f0) 100%)`);
    }

    /**
     * Helper to fetch subcategories via AJAX and update subcategory dropdown
     */
    function fetchAndRenderSubcategories(parentCat, targetSelectId, targetWrapId, selectedSubcat) {
      const $wrap = $(targetWrapId);
      const $select = $(targetSelectId);

      if (!selectedSubcat) {
        activeSubcategory = 'all';
      }

      if (!parentCat || parentCat === 'all' || parentCat === 'ofertas') {
        if ($wrap.length) $wrap.hide();
        if ($select.length) $select.html('<option value="all">Todas las Subcategorías</option>').val('all');
        return;
      }

      $.ajax({
        url: franco_wc_params.ajax_url,
        type: 'POST',
        data: {
          action: 'franco_get_subcategories',
          parent_category: parentCat
        },
        success: function(response) {
          if (response && response.success && response.data.subcategories && response.data.subcategories.length > 0) {
            let optionsHtml = '<option value="all">Todas las Subcategorías</option>';
            response.data.subcategories.forEach(function(sc) {
              optionsHtml += `<option value="${escAttr(sc.slug)}">${escHtml(sc.name)}</option>`;
            });
            $select.html(optionsHtml);
            if (selectedSubcat) {
              $select.val(selectedSubcat);
              activeSubcategory = selectedSubcat;
            } else {
              $select.val('all');
            }
            $wrap.css('display', 'block').show();
          } else {
            if ($wrap.length) $wrap.hide();
            if ($select.length) $select.html('<option value="all">Todas las Subcategorías</option>').val('all');
          }
        },
        error: function() {
          if ($wrap.length) $wrap.hide();
        }
      });
    }

    /**
     * Sync active filter state to browser URL query string
     */
    /**
     * Store active filter state in sessionStorage without polluting the browser URL
     */
    window.updateFilterUrl = function(pushHistory = true) {
      if ($('#inicio').length > 0) return;

      const params = new URLSearchParams();

      if (activeCategory && activeCategory !== 'all') params.set('category', activeCategory);
      if (activeSubcategory && activeSubcategory !== 'all') params.set('subcategory', activeSubcategory);
      if (activeBrand && activeBrand !== 'all') params.set('brand', activeBrand);
      if (activeTalle && activeTalle !== 'all') params.set('talle', activeTalle);
      if (activeSexo && activeSexo !== 'all') params.set('sexo', activeSexo);
      if (activeColor && activeColor !== 'all') params.set('color', activeColor);
      if (activeMinPrice > defaultMinPrice) params.set('min_price', activeMinPrice);
      if (activeMaxPrice < defaultMaxPrice) params.set('max_price', activeMaxPrice);
      if (activeSortBy && activeSortBy !== 'popular') params.set('sort_by', activeSortBy);
      if (activeSearchTerm) params.set('search', activeSearchTerm);

      const queryString = params.toString();
      if (queryString) {
        try { sessionStorage.setItem('franco_active_filters', queryString); } catch(e) {}
      } else {
        try { sessionStorage.removeItem('franco_active_filters'); } catch(e) {}
      }

      // Clean address bar URL (no visible ?brand=reebok parameters)
      if (window.location.search && window.history.replaceState) {
        window.history.replaceState(null, '', window.location.pathname);
      }
    };

    /**
     * Restore filter state from sessionStorage (keeping address bar URL clean)
     */
    function restoreFiltersFromUrl() {
      if ($('#inicio').length > 0) return false;

      let searchParams = new URLSearchParams(window.location.search);
      const filterKeys = ['category', 'subcategory', 'brand', 'talle', 'sexo', 'color', 'min_price', 'max_price', 'sort_by', 'search'];
      let hasParams = Array.from(searchParams.keys()).some(function(k) {
        return filterKeys.indexOf(k) !== -1;
      });

      // Check sessionStorage for active filter state
      try {
        const savedQuery = sessionStorage.getItem('franco_active_filters');
        if (savedQuery) {
          searchParams = new URLSearchParams(savedQuery);
          hasParams = Array.from(searchParams.keys()).some(function(k) {
            return filterKeys.indexOf(k) !== -1;
          });
        }
      } catch(e) {}

      // Keep address bar clean without query parameters
      if (window.location.search && window.history.replaceState) {
        window.history.replaceState(null, '', window.location.pathname);
      }

      if (!hasParams) return false;

      activeCategory = searchParams.get('category') || 'all';
      activeSubcategory = searchParams.get('subcategory') || 'all';
      activeBrand = searchParams.get('brand') || 'all';
      activeTalle = searchParams.get('talle') || 'all';
      activeSexo = searchParams.get('sexo') || 'all';
      activeColor = 'all'; // Filtro de color deshabilitado en el front.

      const minP = parseFloat(searchParams.get('min_price'));
      const maxP = parseFloat(searchParams.get('max_price'));
      if (!isNaN(minP)) activeMinPrice = minP;
      if (!isNaN(maxP)) activeMaxPrice = maxP;

      activeSortBy = searchParams.get('sort_by') || 'popular';
      activeSearchTerm = searchParams.get('search') || '';

      if ($('#cat-select').length) $('#cat-select').val(activeCategory);
      if ($('#brand-select').length) $('#brand-select').val(activeBrand);
      if ($('#talle-select').length) $('#talle-select').val(activeTalle);
      if ($('#sexo-select').length) $('#sexo-select').val(activeSexo);
      if ($('#color-select').length) $('#color-select').val(activeColor);

      if ($('#price-min-input').length) $('#price-min-input').val(activeMinPrice);
      if ($('#price-max-input').length) $('#price-max-input').val(activeMaxPrice);
      if ($('#price-min-range').length) $('#price-min-range').val(activeMinPrice);
      if ($('#price-max-range').length) $('#price-max-range').val(activeMaxPrice);
      if ($('#price-min-label').length) $('#price-min-label').text('$' + parseInt(activeMinPrice).toLocaleString('es-AR'));
      if ($('#price-max-label').length) $('#price-max-label').text('$' + parseInt(activeMaxPrice).toLocaleString('es-AR'));
      updatePriceTrack();

      if ($('#sort-select').length) $('#sort-select').val(activeSortBy);
      if ($('#store-search-input').length) $('#store-search-input').val(activeSearchTerm);

      if (activeCategory && activeCategory !== 'all') {
        fetchAndRenderSubcategories(activeCategory, '#subcat-select', '#subcat-select-wrap', activeSubcategory);
      } else {
        $('#subcat-select-wrap').hide();
      }

      return true;
    }

    // Restore active filters if URL search params exist (e.g. Back button / direct link)
    if (restoreFiltersFromUrl()) {
      francoFilterProducts(false);
    }

    // Handle browser Back / Forward navigation (popstate)
    $(window).on('popstate', function() {
      if (restoreFiltersFromUrl()) {
        currentPage = 1;
        francoFilterProducts(false);
      }
    });

    // Initialize price track background
    updatePriceTrack();

    // Toggle Collapsible Filter Groups
    $(document).on('click', '.filter-group-header', function(e) {
      e.preventDefault();
      const $group = $(this).closest('.filter-group');
      const isCollapsed = $group.hasClass('collapsed');

      if (isCollapsed) {
        $group.removeClass('collapsed');
        $(this).attr('aria-expanded', 'true');
      } else {
        $group.addClass('collapsed');
        $(this).attr('aria-expanded', 'false');
      }
    });

    // Bind Category Select Dropdown (Shop Catalog)
    $(document).on('change', '#cat-select', function() {
      activeCategory = $(this).val() || 'all';
      activeSubcategory = 'all';
      currentPage = 1;
      fetchAndRenderSubcategories(activeCategory, '#subcat-select', '#subcat-select-wrap');
      francoFilterProducts(false);
    });

    // Bind Subcategory Select Dropdown (Shop Catalog)
    $(document).on('change', '#subcat-select', function() {
      activeSubcategory = $(this).val() || 'all';
      currentPage = 1;
      francoFilterProducts(false);
    });

    // Bind Homepage Category Filter Pills
    $(document).on('click', '.home-category-pills .filter-pill-btn, .filter-pills-bar .filter-pill-btn', function(e) {
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.which === 2) return;
      e.preventDefault();
      $('.home-category-pills .filter-pill-btn, .filter-pills-bar .filter-pill-btn').removeClass('active');
      $(this).addClass('active');
      activeCategory = $(this).attr('data-category') || 'all';
      activeSubcategory = 'all';
      activeBrand = 'all';
      activeMinPrice = defaultMinPrice;
      activeMaxPrice = defaultMaxPrice;
      activeSearchTerm = '';
      currentPage = 1;

      francoFilterProducts(false);
    });

    // Bind Brand Select Dropdown (AJAX Filter)
    $(document).on('change', '#brand-select', function() {
      activeBrand = $(this).val() || 'all';
      currentPage = 1;
      francoFilterProducts(false);
    });

    // Bind Talle Select Dropdown (AJAX Filter)
    $(document).on('change', '#talle-select', function() {
      activeTalle = $(this).val() || 'all';
      currentPage = 1;
      francoFilterProducts(false);
    });

    // Bind Sexo Select Dropdown (AJAX Filter)
    $(document).on('change', '#sexo-select', function() {
      activeSexo = $(this).val() || 'all';
      currentPage = 1;
      francoFilterProducts(false);
    });

    // Bind Color Select Dropdown (AJAX Filter)
    $(document).on('change', '#color-select', function() {
      activeColor = $(this).val() || 'all';
      currentPage = 1;
      francoFilterProducts(false);
    });

    // Bind Clear Filters Button
    $(document).on('click', '#clear-filters-btn', function(e) {
      e.preventDefault();
      activeCategory = 'all';
      activeSubcategory = 'all';
      activeBrand = 'all';
      activeTalle = 'all';
      activeSexo = 'all';
      activeColor = 'all';
      activeMinPrice = defaultMinPrice;
      activeMaxPrice = defaultMaxPrice;
      activeSortBy = 'popular';
      activeSearchTerm = '';

      $('#cat-select').val('all');
      $('#subcat-select-wrap').hide();
      $('#subcat-select').html('<option value="all">Todas las Subcategorías</option>').val('all');
      $('#home-subcat-wrap').hide();
      $('#home-subcat-select').html('<option value="all">Todas las Subcategorías</option>').val('all');
      $('#brand-select').val('all');
      $('#talle-select').val('all');
      $('#sexo-select').val('all');
      $('#color-select').val('all');
      $('#sort-select').val('popular');
      $('#store-search-input').val('');
      $('#price-min-input').val(defaultMinPrice);
      $('#price-max-input').val(defaultMaxPrice);
      $('#price-min-range').val(defaultMinPrice);
      $('#price-max-range').val(defaultMaxPrice);
      $('#price-min-label').text('$' + parseInt(defaultMinPrice).toLocaleString('es-AR'));
      $('#price-max-label').text('$' + parseInt(defaultMaxPrice).toLocaleString('es-AR'));
      updatePriceTrack();

      try { sessionStorage.removeItem('franco_active_filters'); } catch(e) {}

      if ($('#inicio').length === 0) {
        window.history.pushState(null, '', window.location.pathname);
      }

      currentPage = 1;
      francoFilterProducts(false);
    });

    // Bind Store Search Input (Debounced)
    $(document).on('input', '#store-search-input', function() {
      clearTimeout(storeSearchTimeout);
      const val = $(this).val().trim();
      storeSearchTimeout = setTimeout(function() {
        activeSearchTerm = val;
        currentPage = 1;
        francoFilterProducts(false);
      }, 350);
    });

    // Bind Dual Price Range Sliders
    $(document).on('input change', '#price-min-range', function() {
      let minVal = parseFloat($(this).val()) || 0;
      let maxVal = parseFloat($('#price-max-range').val()) || 500000;
      if (minVal > maxVal) {
        minVal = maxVal;
        $(this).val(minVal);
      }
      activeMinPrice = minVal;
      $('#price-min-input').val(minVal);
      $('#price-min-label').text('$' + parseInt(minVal).toLocaleString('es-AR'));
      updatePriceTrack();
      currentPage = 1;
      francoFilterProducts(false);
    });

    $(document).on('input change', '#price-max-range', function() {
      let minVal = parseFloat($('#price-min-range').val()) || 0;
      let maxVal = parseFloat($(this).val()) || 500000;
      if (maxVal < minVal) {
        maxVal = minVal;
        $(this).val(maxVal);
      }
      activeMaxPrice = maxVal;
      $('#price-max-input').val(maxVal);
      $('#price-max-label').text('$' + parseInt(maxVal).toLocaleString('es-AR'));
      updatePriceTrack();
      currentPage = 1;
      francoFilterProducts(false);
    });

    // Bind Min & Max Price Input Fields (Syncs to sliders & vice versa)
    $(document).on('input', '#price-min-input', function() {
      clearTimeout(priceInputTimeout);
      const rawVal = $(this).val();
      if (rawVal === '') return;
      const val = parseFloat(rawVal);
      if (isNaN(val)) return;

      activeMinPrice = Math.max(0, val);
      $('#price-min-range').val(activeMinPrice);
      $('#price-min-label').text('$' + parseInt(activeMinPrice).toLocaleString('es-AR'));
      updatePriceTrack();

      priceInputTimeout = setTimeout(function() {
        currentPage = 1;
        francoFilterProducts(false);
      }, 600);
    });

    $(document).on('change', '#price-min-input', function() {
      clearTimeout(priceInputTimeout);
      let val = parseFloat($(this).val());
      const maxVal = parseFloat($('#price-max-range').val()) || 500000;
      if (isNaN(val) || val < 0) val = 0;
      if (val > maxVal) val = maxVal;

      $(this).val(val);
      activeMinPrice = val;
      $('#price-min-range').val(val);
      $('#price-min-label').text('$' + parseInt(val).toLocaleString('es-AR'));
      updatePriceTrack();

      currentPage = 1;
      francoFilterProducts(false);
    });

    $(document).on('input', '#price-max-input', function() {
      clearTimeout(priceInputTimeout);
      const rawVal = $(this).val();
      if (rawVal === '') return;
      const val = parseFloat(rawVal);
      if (isNaN(val)) return;

      activeMaxPrice = val;
      $('#price-max-range').val(activeMaxPrice);
      $('#price-max-label').text('$' + parseInt(activeMaxPrice).toLocaleString('es-AR'));
      updatePriceTrack();

      priceInputTimeout = setTimeout(function() {
        currentPage = 1;
        francoFilterProducts(false);
      }, 600);
    });

    $(document).on('change', '#price-max-input', function() {
      clearTimeout(priceInputTimeout);
      let val = parseFloat($(this).val());
      const minVal = parseFloat($('#price-min-range').val()) || 0;
      const sliderMax = parseFloat($('#price-max-range').attr('max')) || 500000;

      if (isNaN(val)) val = sliderMax;
      if (val < minVal) val = minVal;
      if (val > sliderMax) val = sliderMax;

      $(this).val(val);
      activeMaxPrice = val;
      $('#price-max-range').val(val);
      $('#price-max-label').text('$' + parseInt(val).toLocaleString('es-AR'));
      updatePriceTrack();

      currentPage = 1;
      francoFilterProducts(false);
    });

    // Bind Sorting Dropdown
    $(document).on('change', '#sort-select', function() {
      activeSortBy = $(this).val();
      currentPage = 1;
      francoFilterProducts(false);
    });

    // Live search input handling (Header modal, debounced)
    $('#mobile-search-input').on('input', function() {
      const query = $(this).val().trim();
      clearTimeout(liveSearchTimeout);
      liveSearchTimeout = setTimeout(function() {
        francoLiveSearch(query);
      }, 250);
    });

    // Infinite Scroll (Only on Shop Catalog, Disabled on Homepage)
    initInfiniteScroll();
  });

  /**
   * Read the server-rendered pagination state from #products-grid so infinite scroll
   * continues from the current page and filters start from the current category.
   */
  function initGridStateFromServer() {
    const $grid = $('#products-grid');
    if (!$grid.length || $('#inicio').length > 0) return;

    const serverPage = parseInt($grid.attr('data-current-page'), 10);
    const serverMax = parseInt($grid.attr('data-max-pages'), 10);
    const serverCategory = $grid.attr('data-category');

    if (serverPage > 0) {
      currentPage = serverPage;
      hasMoreProducts = serverMax > 0 ? serverPage < serverMax : false;
    }
    if (serverCategory) {
      activeCategory = serverCategory;
    }
  }

  /**
   * Infinite scroll: IntersectionObserver on a sentinel placed after the product grid,
   * falling back to a throttled scroll handler.
   */
  function initInfiniteScroll() {
    if ($('#inicio').length > 0) return; // Disable infinite scroll on homepage

    const $grid = $('#products-grid');
    if (!$grid.length) return;

    // Archives the AJAX endpoint cannot reproduce (tags, attributes, search) keep plain pagination
    if ($grid.attr('data-infinite') === '0') return;

    // Infinite scroll takes over: hide the crawlable pagination (links stay in the HTML)
    $('#shop-pagination').addClass('is-infinite');

    function loadNextPage() {
      if (isLoadingProducts || !hasMoreProducts) return;
      currentPage++;
      francoFilterProducts(true);
    }

    if ('IntersectionObserver' in window) {
      infiniteSentinel = document.createElement('div');
      infiniteSentinel.id = 'infinite-scroll-sentinel';
      infiniteSentinel.setAttribute('aria-hidden', 'true');
      infiniteSentinel.style.cssText = 'width: 100%; height: 1px;';
      $grid.after(infiniteSentinel);

      infiniteObserver = new IntersectionObserver(function(entries) {
        if (entries.some(function(entry) { return entry.isIntersecting; })) {
          loadNextPage();
        }
      }, { rootMargin: '0px 0px 350px 0px' });

      infiniteObserver.observe(infiniteSentinel);
    } else {
      let scrollTicking = false;
      $(window).on('scroll', function() {
        if (scrollTicking) return;
        scrollTicking = true;
        setTimeout(function() {
          scrollTicking = false;
          const scrollThreshold = $(document).height() - $(window).height() - 350;
          if ($(window).scrollTop() >= scrollThreshold) {
            loadNextPage();
          }
        }, 150);
      });
    }
  }

  /**
   * Re-arm the sentinel so the observer fires again if it is still visible after new content loads.
   */
  function rearmInfiniteScroll() {
    if (infiniteObserver && infiniteSentinel && hasMoreProducts) {
      infiniteObserver.unobserve(infiniteSentinel);
      infiniteObserver.observe(infiniteSentinel);
    }
  }

  /**
   * Price Formatter Helper (prices are already plain text from the server)
   */
  function cleanPriceString(str) {
    if (!str) return '$0';
    return String(str).replace(/\xA0/g, ' ').replace(/&nbsp;/g, ' ').trim();
  }

  /**
   * 1. Fetch Cart Data from WooCommerce AJAX Endpoint
   */
  window.francoFetchCart = function() {
    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      data: {
        action: 'franco_get_cart'
      },
      success: function(response) {
        if (response && response.success) {
          if (response.nonce) {
            franco_wc_params.nonce = response.nonce;
          }
          francoRenderCartUI(response);
          francoRenderCheckoutSummaryUI(response);
        }
      }
    });
  };

  /**
   * Render Slide-over Cart Drawer UI
   */
  function francoRenderCartUI(cartData) {
    const $cartBody = $('#cart-body');
    const $cartBadge = $('#cart-badge-count');
    const $cartTotal = $('#cart-total-price');
    const $cartFooter = $('#cart-footer');
    const $checkoutBtn = $('#cart-checkout-btn');

    if ($cartBadge.length) $cartBadge.text(cartData.count || 0);
    if ($cartTotal.length) $cartTotal.text(cleanPriceString(cartData.total) || '$0');

    if (!$cartBody.length) return;

    if (!cartData.items || cartData.items.length === 0) {
      if ($cartFooter.length) $cartFooter.css('display', 'none');
      if ($checkoutBtn.length) $checkoutBtn.css('display', 'none');

      $cartBody.html(`
        <div class="cart-empty">
          <i class="fa-solid fa-basket-shopping cart-empty-icon"></i>
          <p>${escHtml(franco_wc_params.i18n.cart_empty)}</p>
          <a href="${escAttr(franco_wc_params.shop_url)}" class="btn-primary cart-empty-cta" onclick="closeCartDrawer()"><i class="fa-solid fa-shop"></i> Explorar Productos</a>
        </div>
      `);
      return;
    }

    if ($cartFooter.length) $cartFooter.css('display', 'flex');
    if ($checkoutBtn.length) $checkoutBtn.css('display', 'inline-flex');

    let itemsHTML = '';
    cartData.items.forEach(function(item) {
      const qty = parseInt(item.qty, 10) || 0;
      itemsHTML += `
        <div class="cart-item" data-key="${escAttr(item.key)}">
          <img src="${escAttr(item.image)}" alt="${escAttr(item.name)}" width="60" height="60" loading="lazy" class="cart-item-img">
          <div class="cart-item-info">
            <h4 class="cart-item-title">${escHtml(item.name)}</h4>
            ${item.item_code ? `<span class="line-item-meta">Item N°: <strong>${escHtml(item.item_code)}</strong></span>` : ''}
            <span class="line-item-meta">Talle: <strong>${escHtml(normalizeTalle(item.size))}</strong></span>
            <div class="cart-item-price">${escHtml(cleanPriceString(item.line_total_html))}</div>
            <div class="cart-item-qty">
              <button type="button" class="qty-btn" data-franco-cart-action="qty" data-key="${escAttr(item.key)}" data-qty="${qty - 1}">-</button>
              <span>${qty}</span>
              <button type="button" class="qty-btn" data-franco-cart-action="qty" data-key="${escAttr(item.key)}" data-qty="${qty + 1}">+</button>
              <button type="button" class="qty-btn cart-item-remove" data-franco-cart-action="remove" data-key="${escAttr(item.key)}" title="Eliminar">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </div>
        </div>
      `;
    });

    $cartBody.html(itemsHTML);
  }

  /**
   * Render Checkout Page Order Summary UI
   */
  function francoRenderCheckoutSummaryUI(cartData) {
    const $summaryContainer = $('#checkout-cart-items');
    const $subtotalEl = $('#checkout-subtotal');
    const $shippingEl = $('#checkout-shipping');
    const $totalEl = $('#checkout-total');
    const $clearBtn = $('#checkout-clear-cart-btn');

    if (!$summaryContainer.length) return;

    if ($subtotalEl.length) {
      const formattedSubtotal = cleanPriceString(cartData.subtotal) || '$0';
      $subtotalEl.html(`<span class="woocommerce-Price-amount amount">${escHtml(formattedSubtotal)}</span>`);
    }
    if ($shippingEl.length && cartData.shipping_label) $shippingEl.text(cartData.shipping_label);
    if ($totalEl.length) {
      const formattedTotal = cleanPriceString(cartData.order_total || cartData.total) || '$0';
      $totalEl.html(`<span class="woocommerce-Price-amount amount">${escHtml(formattedTotal)}</span>`);
    }

    if (!cartData.items || cartData.items.length === 0) {
      $summaryContainer.html(`
        <div class="checkout-empty-cart-box">
          <i class="fa-solid fa-basket-shopping checkout-empty-icon"></i>
          <p class="text-muted">Tu carrito está vacío.</p>
          <a href="${escAttr(franco_wc_params.shop_url)}" class="btn-primary" style="margin-top: 10px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-shop"></i> Ir a la Tienda
          </a>
        </div>
      `);
      if ($clearBtn.length) $clearBtn.hide();
      return;
    }

    if ($clearBtn.length) $clearBtn.show();

    let summaryHTML = '';
    cartData.items.forEach(function(item) {
      const qty = parseInt(item.qty, 10) || 1;
      summaryHTML += `
        <div class="checkout-summary-item" data-cart-key="${escAttr(item.key)}">
          <img src="${escAttr(item.image)}" alt="${escAttr(item.name)}" width="50" height="50" loading="lazy" class="checkout-summary-img">
          <div class="checkout-summary-info">
            <strong class="checkout-summary-name">${escHtml(item.name)}</strong>
            <div class="checkout-summary-meta">
              ${item.item_code ? `<span>Item N°: <strong>${escHtml(item.item_code)}</strong></span>` : ''}
              <span>Talle: <strong>${escHtml(normalizeTalle(item.size))}</strong></span>
              <div class="checkout-qty-mini-controls">
                <button type="button" class="qty-mini-btn" onclick="francoUpdateCartQty('${escAttr(item.key)}', ${qty - 1})" title="Reducir cantidad">-</button>
                <span class="qty-mini-val">${qty}</span>
                <button type="button" class="qty-mini-btn" onclick="francoUpdateCartQty('${escAttr(item.key)}', ${qty + 1})" title="Aumentar cantidad">+</button>
              </div>
            </div>
          </div>
          <div class="checkout-summary-right">
            <span class="checkout-summary-price">${escHtml(cleanPriceString(item.line_total_html))}</span>
            <button type="button" class="checkout-remove-item-btn" onclick="francoRemoveCartItem('${escAttr(item.key)}')" title="Eliminar producto de la compra">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </div>
        </div>
      `;
    });

    $summaryContainer.html(summaryHTML);
  }

  /**
   * 2. Add Product to WooCommerce Cart
   *    selectedSize is a pa_talle term slug (variable products only); simple products ignore it.
   */
  window.francoAddToCart = function(productId, selectedSize, quantity, selectedColor) {
    quantity = quantity || 1;

    const payload = {
      action: 'franco_add_to_cart',
      product_id: productId,
      quantity: quantity,
      nonce: franco_wc_params.nonce
    };
    if (selectedSize) {
      payload.size = selectedSize;
    }
    if (selectedColor) {
      payload.color = selectedColor;
    }

    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      data: payload,
      success: function(response) {
        if (response && response.success) {
          francoRenderCartUI(response.data);
          francoRenderCheckoutSummaryUI(response.data);
          showToast(`¡${response.data.added_item_name} agregado al carrito!`);
          openCartDrawer();
        } else {
          showToast(response && response.data && response.data.message ? response.data.message : franco_wc_params.i18n.error_occurred);
        }
      },
      error: function() {
        showToast(franco_wc_params.i18n.error_occurred);
      }
    });
  };

  /**
   * 3. Update Cart Line Item Quantity
   */
  window.francoUpdateCartQty = function(cartItemKey, newQty) {
    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      data: {
        action: 'franco_update_cart_qty',
        cart_item_key: cartItemKey,
        quantity: newQty,
        nonce: franco_wc_params.nonce
      },
      success: function(response) {
        if (response && response.success) {
          francoRenderCartUI(response.data);
          francoRenderCheckoutSummaryUI(response.data);
        } else if (response && response.data && response.data.message) {
          showToast(response.data.message);
        }
      }
    });
  };

  /**
   * 4. Remove Item from WooCommerce Cart
   */
  window.francoRemoveCartItem = function(cartItemKey) {
    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      data: {
        action: 'franco_remove_cart_item',
        cart_item_key: cartItemKey,
        nonce: franco_wc_params.nonce
      },
      success: function(response) {
        if (response && response.success) {
          francoRenderCartUI(response.data);
          francoRenderCheckoutSummaryUI(response.data);
          showToast("Producto eliminado del carrito");
        }
      }
    });
  };

  /**
   * 4b. Clear Entire WooCommerce Cart
   */
  window.francoClearCart = function() {
    if (!confirm('¿Estás seguro de que deseas vaciar tu carrito de compras?')) {
      return;
    }

    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      data: {
        action: 'franco_clear_cart',
        nonce: franco_wc_params.nonce
      },
      success: function(response) {
        if (response && response.success) {
          francoRenderCartUI(response.data);
          francoRenderCheckoutSummaryUI(response.data);
          showToast("Carrito vaciado correctamente");
        } else {
          showToast(response && response.data && response.data.message ? response.data.message : franco_wc_params.i18n.error_occurred);
        }
      },
      error: function() {
        showToast(franco_wc_params.i18n.error_occurred);
      }
    });
  };

  /**
   * 5. Dynamic AJAX Product Filtering & Infinite Scroll
   */
  window.francoFilterProducts = function(isAppend = false) {
    const $grid = $('#products-grid');
    if (!$grid.length || isLoadingProducts) return;

    isLoadingProducts = true;

    if (!isAppend) {
      currentPage = 1;
      $grid.css('opacity', '0.5');
      if (typeof updateFilterUrl === 'function') {
        updateFilterUrl(true);
      }
    } else {
      $('#infinite-scroll-loader').show();
    }

    const isHome = $('#inicio').length > 0;

    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      data: {
        action: 'franco_filter_products',
        category: activeCategory,
        subcategory: activeSubcategory,
        brand: activeBrand,
        talle: activeTalle,
        sexo: activeSexo,
        color: activeColor,
        min_price: activeMinPrice,
        max_price: activeMaxPrice,
        sort_by: activeSortBy,
        search: activeSearchTerm,
        page: currentPage,
        context: isHome ? 'home' : 'shop'
      },
      success: function(response) {
        $grid.css('opacity', '1');
        $('#infinite-scroll-loader').hide();
        isLoadingProducts = false;

        if (response && response.success) {
          if (isAppend) {
            $grid.append(response.data.html);
          } else {
            $grid.html(response.data.html);
          }
          hasMoreProducts = !!response.data.has_more;
          if (response.data.count) {
            $('#result-count').text(response.data.count);
          }
          if (typeof updateWishlistUI === 'function') {
            updateWishlistUI();
          }
          rearmInfiniteScroll();
        }
      },
      error: function() {
        $grid.css('opacity', '1');
        $('#infinite-scroll-loader').hide();
        isLoadingProducts = false;
        if (isAppend && currentPage > 1) {
          currentPage--;
        }
      }
    });
  };

  /**
   * 6. Quick View Modal AJAX Handler & Carousel Functions
   */
  window.moveQuickViewSlide = function(delta) {
    if (!window.qvImages || window.qvImages.length <= 1) return;
    window.qvCurrentIndex = (window.qvCurrentIndex + delta + window.qvImages.length) % window.qvImages.length;
    window.updateQuickViewCarousel();
  };

  window.goToQuickViewSlide = function(index) {
    window.qvCurrentIndex = index;
    window.updateQuickViewCarousel();
  };

  window.updateQuickViewCarousel = function() {
    const track = document.getElementById('quick-view-track');
    const dotsContainer = document.getElementById('quick-view-dots');
    if (track) {
      track.style.transform = 'translateX(-' + (window.qvCurrentIndex * 100) + '%)';
    }
    if (dotsContainer) {
      const dots = dotsContainer.querySelectorAll('.qv-dot');
      dots.forEach(function(dot, idx) {
        if (idx === window.qvCurrentIndex) dot.classList.add('active');
        else dot.classList.remove('active');
      });
    }
  };

  window.selectModalColor = function(btn, colorName) {
    $('#modal-color-selector .color-btn').removeClass('active');
    $(btn).addClass('active');
    $('#modal-selected-color').text(colorName);
    window.currentModalColor = $(btn).attr('data-color') || colorName;
  };

  window.openQuickView = function(productId) {
    const $modalBackdrop = $('#modal-backdrop');
    const $modalBody = $('#modal-body');
    if (!$modalBackdrop.length || !$modalBody.length) return;

    $modalBody.html(`
      <div class="centered-box">
        <i class="fa-solid fa-spinner fa-spin modal-loading-icon"></i>
        <p class="text-muted">Cargando producto...</p>
      </div>
    `);
    $modalBackdrop.addClass('open');

    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      data: {
        action: 'franco_get_quick_view_product',
        product_id: productId
      },
      success: function(response) {
        if (response && response.success) {
          const p = response.data;
          const sizes = Array.isArray(p.sizes) ? p.sizes : [];
          window.currentModalSize = sizes.length ? sizes[0].slug : '';

          let sizesHTML = '';
          if (sizes.length > 0) {
            sizesHTML = sizes.map((s, idx) => `
              <button type="button" class="size-btn ${idx === 0 ? 'active' : ''}" data-size="${escAttr(s.slug)}">${escHtml(s.name)}</button>
            `).join('');
          }

          const galleryImages = (Array.isArray(p.gallery) && p.gallery.length > 0) ? p.gallery : [p.image];
          window.qvCurrentIndex = 0;
          window.qvImages = galleryImages;

          const galleryHTML = `
            <div class="quick-view-carousel-wrap">
              <div class="quick-view-carousel-viewport">
                <div class="quick-view-carousel-track" id="quick-view-track" style="transform: translateX(0%);">
                  ${galleryImages.map(img => `
                    <div class="quick-view-slide">
                      <img src="${escAttr(img)}" alt="${escAttr(p.name)}">
                    </div>
                  `).join('')}
                </div>
              </div>
              ${galleryImages.length > 1 ? `
                <button type="button" class="qv-nav-btn prev" onclick="moveQuickViewSlide(-1)" aria-label="Anterior">
                  <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button type="button" class="qv-nav-btn next" onclick="moveQuickViewSlide(1)" aria-label="Siguiente">
                  <i class="fa-solid fa-chevron-right"></i>
                </button>
                <div class="qv-dots" id="quick-view-dots">
                  ${galleryImages.map((_, i) => `
                    <span class="qv-dot ${i === 0 ? 'active' : ''}" onclick="goToQuickViewSlide(${i})"></span>
                  `).join('')}
                </div>
              ` : ''}
            </div>
          `;

          // Color no se muestra en el front.
          window.currentModalColor = '';
          const colorsHTML = '';

          $modalBody.html(`
            <div class="quick-view-grid">
              ${galleryHTML}
              <div class="quick-view-details">
                <span class="product-brand">${escHtml(p.brand)}</span>
                <h2 class="product-name">${escHtml(p.name)}</h2>
                ${p.item_code ? `<div class="product-detail-item-code">Item N° ${escHtml(p.item_code)}</div>` : ''}
                <div class="product-price-row">
                  ${p.price_old ? `<span class="price-old">${escHtml(cleanPriceString(p.price_old))}</span>` : ''}
                  <span class="price-current">${escHtml(cleanPriceString(p.price))}</span>
                </div>
                ${p.description ? `<p class="quick-view-desc">${escHtml(p.description)}</p>` : ''}

                ${colorsHTML}

                ${sizesHTML ? `
                  <div style="margin-bottom: 14px;">
                    <label class="filter-label">Seleccionar Talle:</label>
                    <div class="size-selector" id="modal-size-selector">
                      ${sizesHTML}
                    </div>
                  </div>
                ` : ''}

                <div class="quick-view-actions">
                  <button type="button" class="btn-primary quick-view-add-btn" data-product-id="${escAttr(p.id)}">
                    <i class="fa-solid fa-cart-shopping"></i> Agregar al Carrito
                  </button>
                  <a href="${escAttr(p.permalink)}" class="quick-view-detail-link">
                    <i class="fa-solid fa-eye"></i> Ver Detalle
                  </a>
                </div>
              </div>
            </div>
          `);
        } else {
          $modalBody.html('<div class="modal-error">Error al cargar producto.</div>');
        }
      },
      error: function() {
        $modalBody.html('<div class="modal-error">Error de conexión.</div>');
      }
    });
  };

  /**
   * 7. Wishlist Page AJAX Loader (Fixes Wishlist Page)
   */
  window.francoRenderWishlistPage = function() {
    const $grid = $('#wishlist-products-grid');
    if (!$grid.length) return;

    let wishlistIds = [];
    if (typeof readStoredWishlist === 'function') {
      wishlistIds = readStoredWishlist();
    }

    if (wishlistIds.length === 0) {
      $grid.html(`
        <div class="products-empty">
          <i class="fa-regular fa-heart products-empty-icon-block"></i>
          <h3 class="products-empty-title">Tu lista de favoritos está vacía</h3>
          <p class="products-empty-text">Explora la tienda y presiona el ícono de corazón para guardar tus productos favoritos.</p>
          <a href="${escAttr(franco_wc_params.shop_url)}" class="btn-primary products-empty-btn">
            <i class="fa-solid fa-shop"></i> Explorar Tienda
          </a>
        </div>
      `);
      return;
    }

    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      data: {
        action: 'franco_get_wishlist_products',
        product_ids: wishlistIds
      },
      success: function(response) {
        if (response && response.success && response.data.html) {
          $grid.html(response.data.html);
          if (typeof updateWishlistUI === 'function') {
            updateWishlistUI();
          }
        } else {
          $grid.html(`
            <div class="products-empty">
              <p class="text-muted">No se encontraron productos favoritos guardados.</p>
            </div>
          `);
        }
      }
    });
  };

  /**
   * 8. Checkout: live shipping total
   */
  function francoSetShippingMethod(rateId, packageIndex) {
    if (!rateId) return;

    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      data: {
        action: 'franco_set_shipping_method',
        shipping_method: rateId,
        package: packageIndex,
        nonce: franco_wc_params.nonce
      },
      success: function(response) {
        if (response && response.success) {
          francoRenderCheckoutSummaryUI(response.data);
          francoRenderCartUI(response.data);
        } else if (response && response.data && response.data.message) {
          showToast(response.data.message);
        }
      }
    });
  }

  /**
   * Checkout: hide the delivery address box when a pickup rate is selected.
   */
  function francoApplyPickupState() {
    const $checked = $('#custom-franco-checkout-form input[name^="shipping_method"]:checked');
    if (!$checked.length) return;
    $('#address-fields').toggle(!isPickupRate($checked.val()));
  }

  /**
   * Multi-Step Checkout Navigation & Validation Engine
   */
  let currentCheckoutStep = 1;
  const completedCheckoutSteps = new Set();

  window.nextCheckoutStep = function(step) {
    if (!validateCheckoutStep(step)) return;

    completedCheckoutSteps.add(step);
    goToCheckoutStep(step + 1);
  };

  window.prevCheckoutStep = function(step) {
    goToCheckoutStep(step - 1);
  };

  window.goToCheckoutStep = function(targetStep) {
    if (targetStep < 1 || targetStep > 4) return;

    if (targetStep > currentCheckoutStep) {
      for (let s = 1; s < targetStep; s++) {
        if (!completedCheckoutSteps.has(s) && !validateCheckoutStep(s)) {
          return;
        }
      }
    }

    currentCheckoutStep = targetStep;

    $('.checkout-step-pane').hide();
    $('#checkout-step-pane-' + targetStep).fadeIn(200);

    $('#checkout-steps-bar .step-indicator').each(function() {
      const stepNum = parseInt($(this).attr('data-step'));
      $(this).removeClass('active completed disabled');
      if (stepNum === currentCheckoutStep) {
        $(this).addClass('active');
      } else if (completedCheckoutSteps.has(stepNum)) {
        $(this).addClass('completed');
      } else {
        $(this).addClass('disabled');
      }
    });

    const $layout = $('#checkout-layout');
    if ($layout.length) {
      $('html, body').animate({ scrollTop: $layout.offset().top - 100 }, 250);
    }
  };

  function validateCheckoutStep(step) {
    $('#checkout-error-notice').hide().empty();
    let isValid = true;
    let errorMsg = '';

    if (step === 1) {
      // Step 1: Order Summary review - always valid
      return true;
    } else if (step === 2) {
      // Step 2: Personal data
      const firstName = $('input[name="billing_first_name"]').val() ? $('input[name="billing_first_name"]').val().trim() : '';
      const dni       = $('input[name="billing_dni"]').val() ? $('input[name="billing_dni"]').val().trim() : '';
      const email     = $('input[name="billing_email"]').val() ? $('input[name="billing_email"]').val().trim() : '';
      const phone     = $('input[name="billing_phone"]').val() ? $('input[name="billing_phone"]').val().trim() : '';

      if (!firstName) {
        errorMsg = 'Por favor ingresá tu nombre y apellido completo.';
        isValid = false;
        $('input[name="billing_first_name"]').focus();
      } else if (!dni) {
        errorMsg = 'Por favor ingresá tu DNI / CUIT (Obligatorio para la factura).';
        isValid = false;
        $('input[name="billing_dni"]').focus();
      } else if (dni.replace(/\D/g, '').length < 6 || (dni.replace(/\D/g, '').length > 8 && dni.replace(/\D/g, '').length !== 11)) {
        errorMsg = 'Por favor ingresá un DNI (7-8 dígitos) o CUIT (11 dígitos) válido.';
        isValid = false;
        $('input[name="billing_dni"]').focus();
      } else if (!email || !email.includes('@')) {
        errorMsg = 'Por favor ingresá un correo electrónico válido.';
        isValid = false;
        $('input[name="billing_email"]').focus();
      } else if (!phone) {
        errorMsg = 'Por favor ingresá un número de teléfono o WhatsApp de contacto.';
        isValid = false;
        $('input[name="billing_phone"]').focus();
      }
    } else if (step === 3) {
      // Step 3: Delivery options & Address
      const $shippingRadios = $('#custom-franco-checkout-form input[name^="shipping_method"]');
      const shippingMethod = $shippingRadios.filter(':checked').val();

      if ($('#dynamic-shipping-methods-container .checkout-alert-error').length) {
        errorMsg = 'No hay métodos de envío disponibles para tu zona. Contactanos por WhatsApp.';
        isValid = false;
      } else if ($shippingRadios.length && !shippingMethod) {
        errorMsg = 'Por favor seleccioná un método de envío.';
        isValid = false;
      } else if (!isPickupRate(shippingMethod)) {
        const address  = $('input[name="billing_address_1"]').val() ? $('input[name="billing_address_1"]').val().trim() : '';
        const city     = $('input[name="billing_city"]').val() ? $('input[name="billing_city"]').val().trim() : '';
        const state    = $('select[name="billing_state"]').val() || '';
        const postcode = $('input[name="billing_postcode"]').val() ? $('input[name="billing_postcode"]').val().trim() : '';

        if (!address) {
          errorMsg = 'Por favor ingresá tu dirección de envío (calle, número y depto).';
          isValid = false;
          $('input[name="billing_address_1"]').focus();
        } else if (!city) {
          errorMsg = 'Por favor ingresá la localidad o ciudad de entrega.';
          isValid = false;
          $('input[name="billing_city"]').focus();
        } else if (!state) {
          errorMsg = 'Por favor seleccioná tu provincia.';
          isValid = false;
          $('select[name="billing_state"]').focus();
        } else if (!postcode) {
          errorMsg = 'Por favor ingresá el código postal.';
          isValid = false;
          $('input[name="billing_postcode"]').focus();
        }
      }
    } else if (step === 4) {
      // Step 4: Payment method selection check
      const $paymentRadios = $('#custom-franco-checkout-form input[name="payment_method"]');
      const paymentMethod = $paymentRadios.filter(':checked').val();
      if ($paymentRadios.length && !paymentMethod) {
        errorMsg = 'Por favor seleccioná un método de pago para abonar tu pedido.';
        isValid = false;
      }
    }

    if (!isValid) {
      $('#checkout-error-notice').html('<i class="fa-solid fa-triangle-exclamation"></i> ' + escHtml(errorMsg)).slideDown(200);
      return false;
    }

    return true;
  }

  /**
   * 9. Submit Custom Checkout Form through WooCommerce's native checkout endpoint
   */
  window.handleFrancoCheckoutSubmit = function(event) {
    event.preventDefault();

    if (!validateCheckoutStep(2)) {
      goToCheckoutStep(2);
      return;
    }
    if (!validateCheckoutStep(3)) {
      goToCheckoutStep(3);
      return;
    }
    if (!validateCheckoutStep(4)) {
      goToCheckoutStep(4);
      return;
    }

    const $form = $('#custom-franco-checkout-form');
    const $submitBtn = $('#checkout-submit-btn');
    const $errorNotice = $('#checkout-error-notice');
    const submitLabel = '<i class="fa-solid fa-circle-check"></i> Confirmar y Finalizar Pedido';

    $errorNotice.hide().empty();
    $submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Procesando Pedido...');

    function showCheckoutError(html) {
      $submitBtn.prop('disabled', false).html(submitLabel);
      $errorNotice.html(html).slideDown(200);
      const top = $errorNotice.offset();
      if (top) {
        $('html, body').animate({ scrollTop: top.top - 120 }, 250);
      }
    }

    $.ajax({
      url: franco_wc_params.checkout_ajax_url,
      type: 'POST',
      dataType: 'json',
      data: $form.serialize(),
      success: function(result) {
        if (result && result.result === 'success' && result.redirect) {
          showToast("¡Pedido realizado con éxito!");
          window.location.href = result.redirect;
          return;
        }

        if (result && result.reload) {
          window.location.reload();
          return;
        }

        if (result && result.messages) {
          // Notice markup generated by WooCommerce (trusted, already escaped server-side).
          showCheckoutError(result.messages);
        } else {
          showCheckoutError('<i class="fa-solid fa-triangle-exclamation"></i> ' + escHtml(franco_wc_params.i18n.error_occurred));
        }
      },
      error: function() {
        showCheckoutError('<i class="fa-solid fa-triangle-exclamation"></i> ' + escHtml(franco_wc_params.i18n.error_occurred));
      }
    });
  };

  /**
   * 10. Live Product Search Handler
   */
  function francoLiveSearch(query) {
    const $resultsContainer = $('#mobile-search-results');
    if (!$resultsContainer.length) return;

    if (liveSearchXhr) {
      liveSearchXhr.abort();
      liveSearchXhr = null;
    }

    if (query.length < 2) {
      $resultsContainer.html(`
        <div class="search-empty-state">
          <i class="fa-solid fa-magnifying-glass search-empty-icon"></i>
          Escribe para buscar productos...
        </div>
      `);
      return;
    }

    liveSearchXhr = $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'GET',
      data: {
        action: 'franco_live_search',
        q: query.substring(0, 100)
      },
      success: function(response) {
        if (response && response.success && response.data.results.length > 0) {
          let html = '';
          response.data.results.forEach(function(item) {
            let priceDisplay = `<span class="live-price">${escHtml(cleanPriceString(item.price))}</span>`;
            if (item.price_old) {
              priceDisplay = `
                <span class="live-price-old">${escHtml(cleanPriceString(item.price_old))}</span>
                <span class="live-price">${escHtml(cleanPriceString(item.price))}</span>
              `;
            }
            html += `
              <a class="live-search-item live-search-link" href="${escAttr(item.permalink)}">
                <img src="${escAttr(item.image)}" alt="${escAttr(item.name)}" width="45" height="45" loading="lazy" class="live-search-thumb">
                <div class="flex-grow-1">
                  <div class="live-search-name">${escHtml(item.name)}</div>
                  <div class="live-search-prices">${priceDisplay}</div>
                </div>
              </a>
            `;
          });
          $resultsContainer.html(html);
        } else {
          $resultsContainer.html(`
            <div class="search-no-results">
              No se encontraron productos para "${escHtml(query)}"
            </div>
          `);
        }
      }
    });
  }

  /* Stat Card Counter Animation */
  function initStatCardCounters() {
    const statEls = document.querySelectorAll('.stat-card, .about-stat-card, .stat-number');
    if (!statEls.length) return;

    const observer = new IntersectionObserver(function(entries, obs) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          const el = entry.target;
          const numberEl = el.querySelector('h3, h4, .stat-number') || el;
          if (numberEl && !numberEl.dataset.animated) {
            numberEl.dataset.animated = 'true';
            animateStatCounter(numberEl);
          }
          obs.unobserve(el);
        }
      });
    }, { threshold: 0.1 });

    statEls.forEach(function(el) { observer.observe(el); });
  }

  function animateStatCounter(el) {
    const text = el.textContent.trim();
    const rawTarget = el.dataset.count ? parseFloat(el.dataset.count) : null;
    let prefix = el.dataset.prefix || (text.match(/^[^\d]+/) ? text.match(/^[^\d]+/)[0] : '');
    let suffix = el.dataset.suffix || (text.match(/[^\d]+$/) ? text.match(/[^\d]+$/)[0] : '');
    let targetNum = rawTarget !== null ? rawTarget : parseFloat(text.replace(/[^\d.]/g, '')) || 0;

    if (isNaN(targetNum)) return;

    let isK = suffix.toUpperCase().includes('K') || text.includes('K');
    if (isK && targetNum > 1000) {
      targetNum = targetNum / 1000;
    }

    const duration = 1500;
    const startTime = performance.now();

    function step(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const easeProgress = 1 - Math.pow(1 - progress, 3);
      const currentVal = Math.floor(easeProgress * targetNum);

      el.textContent = prefix + currentVal + suffix;

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = text;
      }
    }

    requestAnimationFrame(step);
  }

  /* High-Performance Scroll Reveal Section Entrance Animations */
  function initScrollRevealAnimations() {
    const revealEls = document.querySelectorAll('.section, .home-trust-section, .category-cards-grid, .about-grid, .contacto-grid, .checkout-card, .reveal-on-scroll');
    if (!revealEls.length) return;

    const observer = new IntersectionObserver(function(entries, obs) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });

    revealEls.forEach(function(el) {
      el.classList.add('reveal-on-scroll');
      observer.observe(el);
    });
  }

  /* --------------------------------------------------------------------------
     Postal Code & Dynamic Shipping Calculator Handlers
     -------------------------------------------------------------------------- */

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return '';
  }

  function setCookie(name, value, days = 30) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${encodeURIComponent(value)};expires=${d.toUTCString()};path=/`;
  }

  function getSavedPostcode() {
    return (window.franco_wc_params && franco_wc_params.customer_postcode) ||
           localStorage.getItem('franco_postcode') ||
           getCookie('franco_postcode') || '';
  }

  function syncPostcodeEverywhere(postcode) {
    if (!postcode) return;
    postcode = postcode.toString().trim();
    localStorage.setItem('franco_postcode', postcode);
    setCookie('franco_postcode', postcode, 30);
    if (window.franco_wc_params) {
      franco_wc_params.customer_postcode = postcode;
    }

    // Update Header label
    const $headerLabel = $('#header-location-text');
    if ($headerLabel.length) {
      $headerLabel.text('CP ' + postcode);
    }
    const $headerInput = $('#header-location-input');
    if ($headerInput.length && !$headerInput.is(':focus')) {
      $headerInput.val(postcode);
    }

    // Update Checkout Step 1
    const $step1Status = $('#checkout-step1-cp-status');
    if ($step1Status.length) {
      $step1Status.html(`Envío cotizado para CP <strong>${escHtml(postcode)}</strong>`);
    }
    const $step1Input = $('#checkout-step1-postcode-input');
    if ($step1Input.length && !$step1Input.is(':focus')) {
      $step1Input.val(postcode);
    }
    const $step1Wrap = $('#checkout-step1-cp-wrap');
    if ($step1Wrap.length) {
      $step1Wrap.addClass('is-hidden');
    }
    const $step1Toggle = $('#checkout-step1-cp-toggle-btn');
    if ($step1Toggle.length) {
      $step1Toggle.text('Cambiar');
    }

    // Update Checkout Step 3
    const $step3Input = $('input[name="billing_postcode"]');
    if ($step3Input.length && !$step3Input.val()) {
      $step3Input.val(postcode);
    }

    // Update Product page calculator input
    const $detailInput = $('#detail-shipping-postcode');
    if ($detailInput.length && !$detailInput.val()) {
      $detailInput.val(postcode);
    }
  }

  // Toggle Header Location Popover
  window.francoToggleLocationPopover = function(e) {
    if (e) e.stopPropagation();
    const $popover = $('#header-location-popover');
    if ($popover.length) {
      $popover.toggleClass('is-hidden');
      if (!$popover.hasClass('is-hidden')) {
        setTimeout(function() {
          $('#header-location-input').focus().select();
        }, 50);
      }
    }
  };

  // Close Popover when clicking outside
  $(document).on('click', function(e) {
    if (!$(e.target).closest('#header-location-wrap').length) {
      $('#header-location-popover').addClass('is-hidden');
    }
  });

  // Save Postcode from Header Popover
  window.francoSaveHeaderLocation = function(e) {
    if (e) e.preventDefault();
    const cp = $('#header-location-input').val().trim();
    if (!cp) return;

    const $btn = $('#header-location-popover button[type="submit"]');
    $btn.prop('disabled', true).text('Guardando...');

    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'franco_set_customer_postcode',
        postcode: cp,
        nonce: franco_wc_params.nonce,
      },
      success: function(res) {
        $btn.prop('disabled', false).text('Guardar');
        if (res && res.success) {
          syncPostcodeEverywhere(cp);
          $('#header-location-popover').addClass('is-hidden');
          showToast(`Ubicación actualizada: CP ${cp}`);
          if ($('#dynamic-shipping-methods-container').length && res.data.shipping_methods_html) {
            $('#dynamic-shipping-methods-container').html(res.data.shipping_methods_html);
          }
          if ($('#checkout-shipping').length && res.data.shipping_label) {
            $('#checkout-shipping').text(res.data.shipping_label);
          }
          if ($('#checkout-total').length && res.data.cart_total) {
            $('#checkout-total').html(`<span class="woocommerce-Price-amount amount">${escHtml(cleanPriceString(res.data.cart_total))}</span>`);
          }
        }
      },
      error: function() {
        $btn.prop('disabled', false).text('Guardar');
        showToast('Error al guardar el código postal.');
      }
    });
  };

  // ------------------------------------------------------------------------
  // USER ACCOUNT & AUTHENTICATION HELPERS
  // ------------------------------------------------------------------------

  // Toggle Header User Dropdown
  window.francoToggleUserDropdown = function(e) {
    if (e) e.stopPropagation();
    const $dropdown = $('#header-user-dropdown');
    const $btn = $('#header-user-btn');
    if ($dropdown.length) {
      const willOpen = $dropdown.hasClass('is-hidden');
      $dropdown.toggleClass('is-hidden');
      $btn.attr('aria-expanded', willOpen ? 'true' : 'false');
      $('#header-location-popover').addClass('is-hidden');
    }
  };

  // Close Popovers when clicking outside or pressing Escape
  $(document).on('click', function(e) {
    if (!$(e.target).closest('#header-user-wrap').length) {
      $('#header-user-dropdown').addClass('is-hidden');
      $('#header-user-btn').attr('aria-expanded', 'false');
    }
  });

  $(document).on('keydown', function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      $('#header-user-dropdown').addClass('is-hidden');
      $('#header-user-btn').attr('aria-expanded', 'false');
      $('#header-location-popover').addClass('is-hidden');
    }
  });

  // Switch between Login and Register tabs on form-login.php
  window.francoSwitchAuthTab = function(tab) {
    const $tabLogin = $('#tab-btn-login');
    const $tabReg = $('#tab-btn-register');
    const $paneLogin = $('#pane-login');
    const $paneReg = $('#pane-register');
    const $title = $('#auth-title');
    const $sub = $('#auth-subtitle');

    if (tab === 'register') {
      $tabLogin.removeClass('active').attr('aria-selected', 'false');
      $tabReg.addClass('active').attr('aria-selected', 'true');
      $paneLogin.addClass('is-hidden');
      $paneReg.removeClass('is-hidden');
      if ($title.length) $title.text('Crear una Cuenta');
      if ($sub.length) $sub.text('Registrate para gestionar tus pedidos y envíos con facilidad.');
      if (window.history && window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.set('action', 'register');
        window.history.replaceState({}, '', url);
      }
    } else {
      $tabReg.removeClass('active').attr('aria-selected', 'false');
      $tabLogin.addClass('active').attr('aria-selected', 'true');
      $paneReg.addClass('is-hidden');
      $paneLogin.removeClass('is-hidden');
      if ($title.length) $title.text('Acceder a tu Cuenta');
      if ($sub.length) $sub.text('Ingresá con tus datos para ver tus compras y direcciones.');
      if (window.history && window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('action');
        window.history.replaceState({}, '', url);
      }
    }
  };

  // Password visibility toggle
  window.francoTogglePasswordVisibility = function(btn) {
    const $btn = $(btn);
    const $wrapper = $btn.closest('.franco-input-wrapper, .input-with-icon');
    const $input = $wrapper.find('input');
    const $icon = $btn.find('i');

    if ($input.length) {
      if ($input.attr('type') === 'password') {
        $input.attr('type', 'text');
        $icon.removeClass('fa-eye far fa-eye').addClass('fa-eye-slash far fa-eye-slash');
      } else {
        $input.attr('type', 'password');
        $icon.removeClass('fa-eye-slash far fa-eye-slash').addClass('fa-eye far fa-eye');
      }
    }
  };

  // Non-Invasive Postcode Toast Handlers
  window.francoSubmitPostcodeToast = function(e) {
    if (e) e.preventDefault();
    const cp = $('#toast-postcode-field').val().trim();
    if (!cp) return;

    const $btn = $('#franco-postcode-toast button[type="submit"]');
    $btn.prop('disabled', true).text('...');

    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'franco_set_customer_postcode',
        postcode: cp,
        nonce: franco_wc_params.nonce,
      },
      success: function(res) {
        if (res && res.success) {
          syncPostcodeEverywhere(cp);
          $('#postcode-toast-content').html(`
            <div style="color: var(--color-primary, #e60000); font-weight: 800; font-size: 0.92rem;">
              <i class="fa-solid fa-circle-check"></i> ¡Código Postal ${escHtml(cp)} guardado!
            </div>
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
              Tus opciones de envío se cotizarán automáticamente.
            </div>
          `);
          setTimeout(function() {
            $('#franco-postcode-toast').removeClass('is-visible');
          }, 1800);
        }
      },
      error: function() {
        $btn.prop('disabled', false).text('Guardar');
      }
    });
  };

  window.francoDismissPostcodeToast = function() {
    $('#franco-postcode-toast').removeClass('is-visible');
    sessionStorage.setItem('franco_postcode_toast_dismissed', '1');
  };

  // Checkout Step 1 Handlers
  window.francoToggleStep1PostcodeEdit = function() {
    const $wrap = $('#checkout-step1-cp-wrap');
    if ($wrap.length) {
      $wrap.toggleClass('is-hidden');
      if (!$wrap.hasClass('is-hidden')) {
        $('#checkout-step1-postcode-input').focus().select();
      }
    }
  };

  window.francoApplyStep1Postcode = function() {
    const cp = $('#checkout-step1-postcode-input').val().trim();
    if (!cp) {
      showToast('Por favor ingresá un código postal.');
      return;
    }

    const $btn = $('#checkout-step1-postcode-btn');
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'franco_set_customer_postcode',
        postcode: cp,
        nonce: franco_wc_params.nonce,
      },
      success: function(res) {
        $btn.prop('disabled', false).text('Calcular');
        if (res && res.success) {
          syncPostcodeEverywhere(cp);
          showToast(`Envío calculado para CP ${cp}`);

          if (res.data.shipping_label) {
            $('#checkout-shipping').text(res.data.shipping_label);
          }
          if (res.data.cart_total) {
            $('#checkout-total').html(`<span class="woocommerce-Price-amount amount">${escHtml(cleanPriceString(res.data.cart_total))}</span>`);
          }
          if ($('#dynamic-shipping-methods-container').length && res.data.shipping_methods_html) {
            $('#dynamic-shipping-methods-container').html(res.data.shipping_methods_html);
          }
        } else {
          showToast(res && res.data && res.data.message ? res.data.message : 'Error al calcular envío.');
        }
      },
      error: function() {
        $btn.prop('disabled', false).text('Calcular');
        showToast('Error al conectar con el servidor.');
      }
    });
  };

  // Product Page Shipping Calculator Handler
  window.francoCalculateProductShipping = function(productId) {
    const cp = $('#detail-shipping-postcode').val().trim();
    if (!cp) {
      showToast('Por favor ingresá tu código postal.');
      return;
    }

    const $btn = $('#detail-shipping-calc-btn');
    const $results = $('#detail-shipping-results');
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
    $results.html('<div style="color: var(--text-muted); font-size: 0.82rem; padding: 6px 0;"><i class="fa-solid fa-spinner fa-spin"></i> Cotizando opciones con Andreani y Correo...</div>');

    $.ajax({
      url: franco_wc_params.ajax_url,
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'franco_calculate_product_shipping',
        product_id: productId,
        postcode: cp,
        nonce: franco_wc_params.nonce,
      },
      success: function(res) {
        $btn.prop('disabled', false).text('Calcular');
        if (res && res.success) {
          syncPostcodeEverywhere(cp);
          if (res.data.rates && res.data.rates.length) {
            let ratesHtml = '';
            res.data.rates.forEach(function(r) {
              const icon = r.is_pickup ? 'fa-store' : 'fa-truck-fast';
              ratesHtml += `
                <div class="shipping-calc-rate-item">
                  <span class="shipping-calc-rate-label">
                    <i class="fa-solid ${icon} text-primary"></i> ${escHtml(r.label)}
                  </span>
                  <span class="shipping-calc-rate-cost">${escHtml(r.cost_html)}</span>
                </div>
              `;
            });
            $results.html(ratesHtml);
          } else {
            $results.html('<div style="color: var(--text-muted); font-size: 0.82rem; padding: 6px 0;"><i class="fa-solid fa-circle-info"></i> No se encontraron métodos para este código postal. Consultanos por WhatsApp.</div>');
          }
        } else {
          $results.html(`<div style="color: var(--color-primary); font-size: 0.82rem; padding: 6px 0;">${escHtml(res && res.data && res.data.message ? res.data.message : 'Error al cotizar envío.')}</div>`);
        }
      },
      error: function() {
        $btn.prop('disabled', false).text('Calcular');
        $results.html('<div style="color: var(--color-primary); font-size: 0.82rem; padding: 6px 0;">Error de conexión. Intentá nuevamente.</div>');
      }
    });
  };

  // Checkout Step 3: Debounced recalculation on address postcode/state change
  let shippingRecalcTimer = null;
  $(document).on('input change', 'input[name="billing_postcode"], select[name="billing_state"]', function() {
    clearTimeout(shippingRecalcTimer);
    shippingRecalcTimer = setTimeout(function() {
      const cp = $('input[name="billing_postcode"]').val() ? $('input[name="billing_postcode"]').val().trim() : '';
      const st = $('select[name="billing_state"]').val() || '';
      if (cp.length >= 4) {
        $.ajax({
          url: franco_wc_params.ajax_url,
          type: 'POST',
          dataType: 'json',
          data: {
            action: 'franco_set_customer_postcode',
            postcode: cp,
            state: st,
            nonce: franco_wc_params.nonce,
          },
          success: function(res) {
            if (res && res.success) {
              syncPostcodeEverywhere(cp);
              if ($('#dynamic-shipping-methods-container').length && res.data.shipping_methods_html) {
                $('#dynamic-shipping-methods-container').html(res.data.shipping_methods_html);
              }
              if ($('#checkout-shipping').length && res.data.shipping_label) {
                $('#checkout-shipping').text(res.data.shipping_label);
              }
              if ($('#checkout-total').length && res.data.cart_total) {
                $('#checkout-total').html(`<span class="woocommerce-Price-amount amount">${escHtml(cleanPriceString(res.data.cart_total))}</span>`);
              }
            }
          }
        });
      }
    }, 600);
  });

  $(document).ready(function() {
    initStatCardCounters();
    initScrollRevealAnimations();

    // Sync postal code everywhere on load
    const savedCp = getSavedPostcode();
    if (savedCp) {
      syncPostcodeEverywhere(savedCp);
    } else if (!sessionStorage.getItem('franco_postcode_toast_dismissed') && !$('body').hasClass('woocommerce-checkout')) {
      // Show non-invasive toast prompt after 1.5s
      setTimeout(function() {
        $('#franco-postcode-toast').addClass('is-visible');
      }, 1500);
    }
  });

})(jQuery);
