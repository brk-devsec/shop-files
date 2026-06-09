function switchTab(name) {
  const tabs = ['login', 'register', 'find'];
  document.querySelectorAll('.tab').forEach((t, i) => {
    t.classList.toggle('active', tabs[i] === name);
  });
  document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  clearAlerts();
}

function clearAlerts() {
  document.querySelectorAll('.alert').forEach(a => {
    a.className = 'alert';
    a.textContent = '';
  });
}

function showAlert(id, type, msg) {
  const el = document.getElementById(id);
  el.className = 'alert ' + type;
  el.textContent = msg;
}

// 로그인 - WordPress wp-login.php POST
function doLogin() {
  const id = document.getElementById('login-id').value.trim();
  const pw = document.getElementById('login-pw').value;
  if (!id || !pw) {
    showAlert('login-alert', 'error', '아이디와 비밀번호를 입력해주세요.');
    return;
  }
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/wp-login.php';
  const fields = {
    log: id,
    pwd: pw,
    'wp-submit': '로그인',
    redirect_to: '/index.html',
    testcookie: '1'
  };
  Object.entries(fields).forEach(([k, v]) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = k;
    input.value = v;
    form.appendChild(input);
  });
  document.body.appendChild(form);
  form.submit();
}

// 회원가입 - WordPress wp-login.php?action=register POST
// WordPress가 wp_users 테이블에 자동 저장
function doRegister() {
  const id = document.getElementById('reg-id').value.trim();
  const email = document.getElementById('reg-email').value.trim();
  const pw = document.getElementById('reg-pw').value;
  const pw2 = document.getElementById('reg-pw2').value;

  if (!id || !email || !pw || !pw2) {
    showAlert('reg-alert', 'error', '모든 항목을 입력해주세요.');
    return;
  }
  if (pw !== pw2) {
    showAlert('reg-alert', 'error', '비밀번호가 일치하지 않습니다.');
    return;
  }
  if (pw.length < 6) {
    showAlert('reg-alert', 'error', '비밀번호는 6자 이상이어야 합니다.');
    return;
  }

  // WordPress 회원가입 API 호출
  // 가입 정보는 wordpress DB의 wp_users 테이블에 저장됨
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/wp-login.php?action=register';
  const fields = {
    user_login: id,
    user_email: email,
    'wp-submit': '등록'
  };
  Object.entries(fields).forEach(([k, v]) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = k;
    input.value = v;
    form.appendChild(input);
  });
  document.body.appendChild(form);
  form.submit();
}

// 계정 찾기 - WordPress lostpassword
function doFind() {
  const email = document.getElementById('find-email').value.trim();
  if (!email) {
    showAlert('find-alert', 'error', '이메일을 입력해주세요.');
    return;
  }
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/wp-login.php?action=lostpassword';
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'user_login';
  input.value = email;
  form.appendChild(input);
  document.body.appendChild(form);
  form.submit();
}

// 엔터키 지원
document.addEventListener('keydown', function(e) {
  if (e.key !== 'Enter') return;
  const active = document.querySelector('.form-section.active');
  if (!active) return;
  const id = active.id;
  if (id === 'tab-login') doLogin();
  else if (id === 'tab-register') doRegister();
  else if (id === 'tab-find') doFind();
});
