// Keranjang dikelola oleh server melalui sesi dan database MySQL.
// File ini disediakan untuk interaksi UI keranjang di tahap pengembangan.
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.quantity-form input[type="number"]').forEach(input => {
    input.addEventListener('change', () => {
      const min = Number(input.min || 1), max = Number(input.max || 999);
      input.value = Math.min(max, Math.max(min, Number(input.value || min)));
    });
  });
});
