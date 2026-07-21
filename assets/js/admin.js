document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('adminNavToggle');
  const sidebar = document.querySelector('.admin-sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  }

  // Initialize DataTables on any table with .dt-table class
  if (window.jQuery && jQuery.fn.DataTable) {
    jQuery('.dt-table').DataTable({
      pageLength: 10,
      lengthChange: false,
      language: { search: '', searchPlaceholder: 'Search...' }
    });
  }
});

// Reusable SweetAlert2 confirm for delete/destructive actions
function confirmAction(formEl, title, text) {
  event.preventDefault();
  Swal.fire({
    title: title || 'Are you sure?',
    text: text || 'This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF4444',
    cancelButtonColor: '#64748B',
    confirmButtonText: 'Yes, proceed'
  }).then((result) => {
    if (result.isConfirmed) formEl.submit();
  });
  return false;
}
