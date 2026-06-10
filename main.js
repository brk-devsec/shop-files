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
      dropdown.innerHTML = `
        <div class="dropdown-name">${data.username}님</div>
        <a href="/mypage.html">마이페이지</a>
        <a href="/mypage.html?tab=orders">주문목록</a>
        <a href="/mypage.html?tab=wishlist">찜 리스트</a>
        <a href="#" onclick="doLogout();return false;">로그아웃</a>
      `;
    } else {
      dropdown.innerHTML = `
        <a href="/login.html">로그인</a>
        <a href="/login.html">회원가입</a>
      `;
    }

    // hover 대신 클릭으로 토글
    userBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdown.classList.toggle('open');
    });

    // 다른 곳 클릭하면 닫기
    document.addEventListener('click', function() {
      dropdown.classList.remove('open');
    });

    // 드롭다운 안 클릭은 닫히지 않게
    dropdown.addEventListener('click', function(e) {
      e.stopPropagation();
    });

  } catch(e) {}
}

async function doLogout() {
  await fetch('/auth.php?action=logout');
  location.reload();
}

document.addEventListener('DOMContentLoaded', initUserMenu);
