document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.filter-bar');
  if (!form) return;
  form.querySelectorAll('select').forEach(select => select.addEventListener('change', () => form.requestSubmit()));
});
