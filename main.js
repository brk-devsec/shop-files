let cartCount = 0;

function addCart() {
  cartCount++;
  const badge = document.getElementById('cartBadge');
  badge.style.display = 'flex';
  badge.textContent = cartCount;

  const toast = document.getElementById('toast');
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2000);
}
