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

async function doLogin() {
  const username = document.getElementById('login-id').value.trim();
  const password = document.getElementById('login-pw').value;
  if (!username || !password) {
    showAlert('login-alert', 'error', '아이디와 비밀번호를 입력해주세요.');
    return;
  }
  const form = new FormData();
  form.append('action', 'login');
  form.append('username', username);
  form.append('password', password);

  const res = await fetch('/auth.php', { method: 'POST', body: form });
  const data = await res.json();
  if (data.success) {
    showAlert('login-alert', 'success', data.message);
    setTimeout(() => location.href = '/index.html', 1000);
  } else {
    showAlert('login-alert', 'error', data.message);
  }
}

async function doRegister() {
  const username = document.getElementById('reg-id').value.trim();
  const email    = document.getElementById('reg-email').value.trim();
  const password = document.getElementById('reg-pw').value;
  const password2 = document.getElementById('reg-pw2').value;

  if (!username || !email || !password || !password2) {
    showAlert('reg-alert', 'error', '모든 항목을 입력해주세요.');
    return;
  }
  if (password !== password2) {
    showAlert('reg-alert', 'error', '비밀번호가 일치하지 않습니다.');
    return;
  }

  const form = new FormData();
  form.append('action', 'register');
  form.append('username', username);
  form.append('email', email);
  form.append('password', password);

  const res = await fetch('/auth.php', { method: 'POST', body: form });
  const data = await res.json();
  if (data.success) {
    showAlert('reg-alert', 'success', data.message);
    setTimeout(() => switchTab('login'), 1500);
  } else {
    showAlert('reg-alert', 'error', data.message);
  }
}

async function doFind() {
  const email = document.getElementById('find-email').value.trim();
  if (!email) {
    showAlert('find-alert', 'error', '이메일을 입력해주세요.');
    return;
  }

  const form = new FormData();
  form.append('action', 'find');
  form.append('email', email);

  const res = await fetch('/auth.php', { method: 'POST', body: form });
  const data = await res.json();
  const type = data.success ? 'success' : 'error';
  showAlert('find-alert', type, data.message);
}

document.addEventListener('keydown', function(e) {
  if (e.key !== 'Enter') return;
  const active = document.querySelector('.form-section.active');
  if (!active) return;
  const id = active.id;
  if (id === 'tab-login') doLogin();
  else if (id === 'tab-register') doRegister();
  else if (id === 'tab-find') doFind();
});
