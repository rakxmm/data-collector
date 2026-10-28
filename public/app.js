// Timers count from server time, so every device shows the same values.
const clockOffset = Number(document.body.dataset.serverTime) - Date.now() / 1000;

// Same output as format_duration() in PHP.
function formatDuration(seconds) {
  const total = Math.max(0, Math.floor(seconds));
  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  const secs = total % 60;
  const pad = (n) => String(n).padStart(2, '0');
  return hours > 0 ? `${hours}:${pad(minutes)}:${pad(secs)}` : `${pad(minutes)}:${pad(secs)}`;
}

function updateTimers() {
  const now = Date.now() / 1000 + clockOffset;
  for (const element of document.querySelectorAll('[data-since]')) {
    element.textContent = formatDuration(now - Number(element.dataset.since));
  }
}

updateTimers();
setInterval(updateTimers, 500);

document.addEventListener('change', (event) => {
  const field = event.target;

  if (field.matches('[data-autosubmit]')) {
    field.form.requestSubmit();
  }

  // Saves without reloading. getAttribute, because form.action returns the <input name="action">.
  // keepalive lets the request finish even if a button tap reloads the page right after.
  if (field.matches('[data-autosave]')) {
    fetch(field.form.getAttribute('action'), { method: 'POST', body: new FormData(field.form), keepalive: true });
  }
});

document.addEventListener('submit', (event) => {
  const form = event.target;

  if (form.dataset.confirm && !confirm(form.dataset.confirm)) {
    event.preventDefault();
    return;
  }

  // Ignore a second tap while the page is still loading.
  if (form.dataset.sent) {
    event.preventDefault();
    return;
  }
  form.dataset.sent = 'yes';
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Enter' && event.target.matches('input[data-autosave]')) {
    event.preventDefault();
    event.target.blur();
  }
});

const arriveForm = document.getElementById('arrive-form');

if (arriveForm) {
  document.addEventListener('keydown', (event) => {
    const typing = event.target.closest('input, textarea, select');
    if (!typing && event.key.toLowerCase() === 'n' && !event.ctrlKey && !event.metaKey) {
      arriveForm.requestSubmit();
    }
  });

  // Shows changes from other devices, but never while someone is typing or has details open.
  setInterval(() => {
    const typing = document.activeElement.closest('input, textarea, select');
    const detailsOpen = document.querySelector('details[open]');
    if (!typing && !detailsOpen && !document.hidden) {
      location.reload();
    }
  }, 10000);
}

const toast = document.querySelector('.toast');

if (toast) {
  history.replaceState(null, '', location.pathname);
  setTimeout(() => toast.remove(), 8000);
}
