document.getElementById('checkAll').addEventListener('change', function () {
  const checkboxes = document.querySelectorAll('.table input[type="checkbox"]');
  checkboxes.forEach(cb => cb.checked = this.checked);
});
