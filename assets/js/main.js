// ===== Mobile nav toggle =====
const navToggle = document.getElementById('navToggle');
const navLinks = document.getElementById('navLinks');
if (navToggle) {
  navToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
}

// ===== Dark mode =====
const darkToggle = document.getElementById('darkModeToggle');
function applyDarkPref() {
  const pref = localStorage.getItem('eventpro-theme');
  if (pref === 'dark') document.body.classList.add('dark-mode');
}
applyDarkPref();
if (darkToggle) {
  darkToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('eventpro-theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
  });
}

// ===== Back to top =====
const backToTop = document.getElementById('backToTop');
if (backToTop) {
  window.addEventListener('scroll', () => {
    backToTop.classList.toggle('show', window.scrollY > 400);
  });
  backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

// ===== Flash messages -> SweetAlert2 toasts =====
document.addEventListener('DOMContentLoaded', () => {
  if (window.APP_FLASHES && window.APP_FLASHES.length && window.Swal) {
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true,
    });
    window.APP_FLASHES.forEach(f => {
      Toast.fire({ icon: f.type, title: f.message });
    });
  }
});

// ===== Animated counters =====
function animateCounters() {
  document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseInt(el.getAttribute('data-counter'), 10) || 0;
    let current = 0;
    const step = Math.max(1, Math.ceil(target / 60));
    const tick = () => {
      current += step;
      if (current >= target) { el.textContent = target.toLocaleString(); return; }
      el.textContent = current.toLocaleString();
      requestAnimationFrame(tick);
    };
    tick();
  });
}
document.addEventListener('DOMContentLoaded', animateCounters);
