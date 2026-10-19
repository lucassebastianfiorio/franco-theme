/* ==========================================================================
   FRANCO DEPORTES - THEME INTERACTIVE ENGINE (app.js)
   ========================================================================== */

// Keep only positive integer ids, without duplicates (max 50).
function sanitizeWishlist(list) {
  if (!Array.isArray(list)) return [];
  const clean = [];
  list.forEach((value) => {
    const id = parseInt(value, 10);
    if (Number.isInteger(id) && id > 0 && !clean.includes(id)) {
      clean.push(id);
    }
  });
  return clean.slice(0, 50);
}

function readStoredWishlist() {
  let fromCookie = null;
  try {
    fromCookie = getWishlistCookie();
  } catch (e) {
    fromCookie = null;
  }
  if (Array.isArray(fromCookie)) return sanitizeWishlist(fromCookie);

  try {
    return sanitizeWishlist(JSON.parse(localStorage.getItem('franco_wishlist')));
  } catch (e) {
    return [];
  }
}

function persistWishlist(list) {
  setWishlistCookie(list);
  try {
    localStorage.setItem('franco_wishlist', JSON.stringify(list));
  } catch (e) {
    // localStorage may be unavailable (private mode / quota); the cookie still works.
  }
}

function getWishlistCookie() {
  const nameEQ = "franco_wishlist=";
  const ca = document.cookie.split(';');
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) === 0) {
      try {
        return JSON.parse(decodeURIComponent(c.substring(nameEQ.length, c.length)));
      } catch (e) {
        return null;
      }
    }
  }
  return null;
}

function setWishlistCookie(val, days = 30) {
  const date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
  const expires = "; expires=" + date.toUTCString();
  document.cookie = "franco_wishlist=" + encodeURIComponent(JSON.stringify(val)) + expires + "; path=/";
}

let wishlist = readStoredWishlist();
persistWishlist(wishlist);

document.addEventListener('DOMContentLoaded', () => {
  setupUIEventListeners();
  updateWishlistUI();
});

function setupUIEventListeners() {
  // Cart Drawer Toggles
  const cartBtn = document.getElementById('cart-btn');
  const cartClose = document.getElementById('cart-close');
  const cartBackdrop = document.getElementById('cart-drawer-backdrop');

  if (cartBtn) cartBtn.addEventListener('click', openCartDrawer);
  if (cartClose) cartClose.addEventListener('click', closeCartDrawer);
  if (cartBackdrop) {
    cartBackdrop.addEventListener('click', (e) => {
      if (e.target === cartBackdrop) closeCartDrawer();
    });
  }

  // Mobile Search Modal Toggles
  const mobileSearchBtn = document.getElementById('mobile-search-btn');
  const mobileSearchClose = document.getElementById('search-modal-close');
  const mobileSearchBackdrop = document.getElementById('search-modal-backdrop');

  if (mobileSearchBtn) mobileSearchBtn.addEventListener('click', openSearchModal);
  if (mobileSearchClose) mobileSearchClose.addEventListener('click', closeSearchModal);
  if (mobileSearchBackdrop) {
    mobileSearchBackdrop.addEventListener('click', (e) => {
      if (e.target === mobileSearchBackdrop) closeSearchModal();
    });
  }

  // Quick View Modal Close
  const modalClose = document.getElementById('modal-close');
  const modalBackdrop = document.getElementById('modal-backdrop');
  if (modalClose) modalClose.addEventListener('click', closeModal);
  if (modalBackdrop) {
    modalBackdrop.addEventListener('click', (e) => {
      if (e.target === modalBackdrop) closeModal();
    });
  }

  // Mobile Menu Toggles
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileMenuClose = document.getElementById('mobile-menu-close');
  const mobileNavBackdrop = document.getElementById('mobile-nav-backdrop');

  if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMobileMenu);
  if (mobileMenuClose) mobileMenuClose.addEventListener('click', closeMobileMenu);
  if (mobileNavBackdrop) mobileNavBackdrop.addEventListener('click', closeMobileMenu);

  // Mobile Filter Drawer Toggles (Store Page)
  const mobileFilterBtn = document.getElementById('mobile-filter-btn');
  const mobileFilterClose = document.getElementById('mobile-filter-close');
  const mobileFilterBackdrop = document.getElementById('mobile-filter-backdrop');

  if (mobileFilterBtn) mobileFilterBtn.addEventListener('click', openMobileFilter);
  if (mobileFilterClose) mobileFilterClose.addEventListener('click', closeMobileFilter);
  if (mobileFilterBackdrop) mobileFilterBackdrop.addEventListener('click', closeMobileFilter);

  // Price Slider input handling
  const priceInput = document.getElementById('price-range');
  const priceLabel = document.getElementById('price-max-label');
  if (priceInput && priceLabel) {
    priceInput.addEventListener('input', (e) => {
      const val = parseInt(e.target.value);
      priceLabel.textContent = `$${val.toLocaleString()}`;
    });
  }
}

// Drawer Helper Functions
function openCartDrawer() {
  const backdrop = document.getElementById('cart-drawer-backdrop');
  if (backdrop) backdrop.classList.add('open');
  if (typeof window.francoFetchCart === 'function') {
    window.francoFetchCart();
  }
}

function closeCartDrawer() {
  const backdrop = document.getElementById('cart-drawer-backdrop');
  if (backdrop) backdrop.classList.remove('open');
}

function openSearchModal() {
  const backdrop = document.getElementById('search-modal-backdrop');
  const input = document.getElementById('mobile-search-input');
  if (backdrop) backdrop.classList.add('open');
  document.body.style.overflow = 'hidden';
  if (input) {
    input.value = '';
    setTimeout(() => input.focus(), 100);
  }
}

function closeSearchModal() {
  const backdrop = document.getElementById('search-modal-backdrop');
  if (backdrop) backdrop.classList.remove('open');
  document.body.style.overflow = '';
}

function openMobileMenu() {
  const menu = document.getElementById('nav-menu');
  const backdrop = document.getElementById('mobile-nav-backdrop');
  if (menu) menu.classList.add('open');
  if (backdrop) backdrop.classList.add('open');
}

function closeMobileMenu() {
  const menu = document.getElementById('nav-menu');
  const backdrop = document.getElementById('mobile-nav-backdrop');
  if (menu) menu.classList.remove('open');
  if (backdrop) backdrop.classList.remove('open');
}

function openMobileFilter() {
  const sidebar = document.querySelector('.filter-sidebar');
  const backdrop = document.getElementById('mobile-filter-backdrop');
  if (sidebar) sidebar.classList.add('open');
  if (backdrop) backdrop.classList.add('open');
}

function closeMobileFilter() {
  const sidebar = document.querySelector('.filter-sidebar');
  const backdrop = document.getElementById('mobile-filter-backdrop');
  if (sidebar) sidebar.classList.remove('open');
  if (backdrop) backdrop.classList.remove('open');
}

function closeModal() {
  const modalBackdrop = document.getElementById('modal-backdrop');
  if (modalBackdrop) modalBackdrop.classList.remove('open');
}

// Wishlist Helpers & Cookie Synchronization
function toggleWishlist(productId) {
  const pId = parseInt(productId, 10);
  if (!Number.isInteger(pId) || pId <= 0) return;
  const index = wishlist.indexOf(pId);

  if (index > -1) {
    wishlist.splice(index, 1);
    showToast("Producto eliminado de favoritos");
  } else {
    if (wishlist.length >= 50) {
      showToast("Llegaste al máximo de 50 favoritos");
      return;
    }
    wishlist.push(pId);
    showToast("¡Producto guardado en tus favoritos!");
  }

  persistWishlist(wishlist);
  updateWishlistUI();

  if (typeof francoRenderWishlistPage === 'function' && document.getElementById('wishlist-products-grid')) {
    francoRenderWishlistPage();
  }
}

function clearFullWishlist() {
  wishlist = [];
  persistWishlist(wishlist);
  updateWishlistUI();
  showToast("Lista de favoritos vaciada");

  if (typeof francoRenderWishlistPage === 'function' && document.getElementById('wishlist-products-grid')) {
    francoRenderWishlistPage();
  }
}

function updateWishlistUI() {
  // Highlight red heart icons on visible product cards
  const cards = document.querySelectorAll('.product-card');
  cards.forEach(card => {
    const cardId = parseInt(card.getAttribute('data-product-id'));
    const favBtn = card.querySelector('.fav-btn');
    if (favBtn) {
      const icon = favBtn.querySelector('i');
      if (wishlist.includes(cardId)) {
        favBtn.classList.add('active', 'in-wishlist');
        if (icon) {
          icon.classList.remove('fa-regular');
          icon.classList.add('fa-solid');
        }
      } else {
        favBtn.classList.remove('active', 'in-wishlist');
        if (icon) {
          icon.classList.remove('fa-solid');
          icon.classList.add('fa-regular');
        }
      }
    }
  });

  // Update navbar wishlist badges synchronously from local wishlist
  const wishBadge = document.getElementById('fav-badge-count');
  if (wishBadge) {
    const count = String(wishlist.length);
    wishBadge.textContent = count;
    const label = document.getElementById('wishlist-count-label');
    if (label) label.textContent = count;
  }
}

// Toast Notifications System
function showToast(message) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = 'toast';
  const icon = document.createElement('i');
  icon.className = 'fa-solid fa-circle-check';
  icon.style.cssText = 'color: var(--color-primary); font-size: 1.1rem;';
  const text = document.createElement('span');
  text.textContent = String(message === null || message === undefined ? '' : message);
  toast.appendChild(icon);
  toast.appendChild(text);

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(-100%)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}
