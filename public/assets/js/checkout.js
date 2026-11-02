document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.checkout-layout form');
  if (!form) return;
  form.addEventListener('submit', event => {
    if (!form.reportValidity()) { event.preventDefault(); return; }
    const button = form.querySelector('button[type="submit"],button:not([type])');
    if (button) { button.disabled = true; button.textContent = 'Memproses pesanan…'; }
  });
});
