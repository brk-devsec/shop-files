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

// 로그인 상태 확인 후 사람 아이콘 토글 변경
async function initUserMenu() {
  try {
    const res = await fetch('/auth.php?action=check');
    const data = await res.json();
    const userBtn = document.getElementById('user-btn');
    const dropdown = document.getElementById('user-dropdown');
    if (!userBtn || !dropdown) return;

    if (data.success) {
      // 로그인 상태 - 드롭다운 메뉴 표시
      dropdown.innerHTML = `
        <div class="dropdown-name">${data.username}님</div>
        <a href="/mypage.html">마이페이지</a>
        <a href="/mypage.html" onclick="sessionStorage.setItem('tab','orders');return true;">주문목록</a>
        <a href="/mypage.html" onclick="sessionStorage.setItem('tab','wishlist');return true;">찜 리스트</a>
        <a href="#" onclick="doLogout()">로그아웃</a>
      `;
    } else {
      // 비로그인 상태
      dropdown.innerHTML = `
        <a href="/login.html">로그인</a>
        <a href="/login.html">회원가입</a>
      `;
    }
  } catch(e) {}
}

async function doLogout() {
  await fetch('/auth.php?action=logout');
  location.reload();
}

document.addEventListener('DOMContentLoaded', initUserMenu);
