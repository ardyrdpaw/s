<?php include 'layout_header.php'; $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'); ?>
<div class="container-fluid mt-4">
  <h1 class="h3 mb-4">Infrastruktur TI</h1>
  <?php if (!$isAdmin): ?>
    <div class="alert alert-info">You have read-only access to this module.</div>
  <?php endif; ?>
  <div id="infToastPlaceholder"></div>
  <script>const INF_TI_IS_ADMIN = <?= $isAdmin ? '1' : '0' ?>;</script>
  <!-- DataTables CSS/JS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <p class="mb-3">Manajemen Infrastruktur TI</p>

          <div class="d-flex mb-2">
            <div class="me-2">
              <button id="btnExportAll" class="btn btn-sm btn-outline-secondary">Export All CSV</button>
            </div>
            <div class="me-2">
              <button id="btnExportSelected" class="btn btn-sm btn-outline-secondary">Export Selected</button>
            </div>
            <div class="me-2 dropdown">
              <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="exportColsDropdown" data-bs-toggle="dropdown" aria-expanded="false">Export Columns</button>
              <ul class="dropdown-menu p-3" aria-labelledby="exportColsDropdown" style="min-width:240px;">
                <li><label class="form-check"><input class="form-check-input export-col" type="checkbox" value="id" checked> <span class="form-check-label">ID</span></label></li>
                <li><label class="form-check"><input class="form-check-input export-col" type="checkbox" value="category" checked> <span class="form-check-label">Category</span></label></li>
                <li><label class="form-check"><input class="form-check-input export-col" type="checkbox" value="name" checked> <span class="form-check-label">Name</span></label></li>
                <li><label class="form-check"><input class="form-check-input export-col" type="checkbox" value="detail" checked> <span class="form-check-label">Detail</span></label></li>
                <li><label class="form-check"><input class="form-check-input export-col" type="checkbox" value="created_at" checked> <span class="form-check-label">Created At</span></label></li>
                <li><label class="form-check"><input class="form-check-input export-col" type="checkbox" value="updated_at" checked> <span class="form-check-label">Updated At</span></label></li>
                <li class="pt-2"><button id="btnSelectAllCols" class="btn btn-sm btn-outline-secondary me-2">All</button><button id="btnDeselectAllCols" class="btn btn-sm btn-outline-secondary">None</button></li>
              </ul>
            </div>
            <div>
              <?php if ($isAdmin): ?>
                <button id="btnDeleteSelected" class="btn btn-sm btn-danger">Delete Selected</button>
              <?php else: ?>
                <button id="btnDeleteSelected" class="btn btn-sm btn-danger" disabled>Delete Selected</button>
              <?php endif; ?>
            </div>
          </div>

          <!-- Tabs -->
          <ul class="nav nav-tabs" id="infTiTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="tab-komputer-tab" data-bs-toggle="tab" data-bs-target="#tab-komputer" type="button" role="tab" aria-controls="tab-komputer" aria-selected="true">Komputer</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-printer-tab" data-bs-toggle="tab" data-bs-target="#tab-printer" type="button" role="tab" aria-controls="tab-printer" aria-selected="false">Printer</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-jaringan-tab" data-bs-toggle="tab" data-bs-target="#tab-jaringan" type="button" role="tab" aria-controls="tab-jaringan" aria-selected="false">Jaringan</button></li>
          </ul>
          <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="tab-komputer" role="tabpanel" aria-labelledby="tab-komputer-tab">

              <?php if ($isAdmin): ?>
                <div class="d-inline-flex align-items-center mb-2">
                  <button id="addKomputer" class="btn btn-sm btn-primary me-2">Add Komputer</button>
                  <button id="saveOrderKomputer" class="btn btn-sm btn-outline-primary" disabled>Save Order</button>
                </div>
              <?php endif; ?>
              <table class="table table-sm table-striped" id="tblKomputer">
                <thead>
                  <tr><th><input type="checkbox" id="chkAllKomputer"></th><th></th><th>#</th><th>Nama</th><th>Spesifikasi</th><th>Actions</th></tr>
                  <tr class="dt-filters"><th></th><th></th><th></th><th><input class="form-control form-control-sm filter-name" placeholder="Filter Nama"></th><th><input class="form-control form-control-sm filter-detail" placeholder="Filter Spesifikasi"></th><th></th></tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
            <div class="tab-pane fade" id="tab-printer" role="tabpanel" aria-labelledby="tab-printer-tab">

              <?php if ($isAdmin): ?>
                <div class="d-inline-flex align-items-center mb-2">
                  <button id="addPrinter" class="btn btn-sm btn-primary me-2">Add Printer</button>
                  <button id="saveOrderPrinter" class="btn btn-sm btn-outline-primary" disabled>Save Order</button>
                </div>
              <?php endif; ?>
              <table class="table table-sm table-striped" id="tblPrinter">
                <thead>
                  <tr><th><input type="checkbox" id="chkAllPrinter"></th><th></th><th>#</th><th>Nama</th><th>Model</th><th>Actions</th></tr>
                  <tr class="dt-filters"><th></th><th></th><th></th><th><input class="form-control form-control-sm filter-name" placeholder="Filter Nama"></th><th><input class="form-control form-control-sm filter-detail" placeholder="Filter Model"></th><th></th></tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
            <div class="tab-pane fade" id="tab-jaringan" role="tabpanel" aria-labelledby="tab-jaringan-tab">

              <?php if ($isAdmin): ?>
                <div class="d-inline-flex align-items-center mb-2">
                  <button id="addJaringan" class="btn btn-sm btn-primary me-2">Add Jaringan</button>
                  <button id="saveOrderJaringan" class="btn btn-sm btn-outline-primary" disabled>Save Order</button>
                </div>
              <?php endif; ?>
              <table class="table table-sm table-striped" id="tblJaringan">
                <thead>
                  <tr><th><input type="checkbox" id="chkAllJaringan"></th><th></th><th>#</th><th>Nama</th><th>Lokasi</th><th>Actions</th></tr>
                  <tr class="dt-filters"><th></th><th></th><th></th><th><input class="form-control form-control-sm filter-name" placeholder="Filter Nama"></th><th><input class="form-control form-control-sm filter-detail" placeholder="Filter Lokasi"></th><th></th></tr>
                </thead>
                <tbody>
                </tbody>
              </table>

            </div>
          </div>
        </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="infTiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="infTiModalLabel">Add Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="infAlert" class="alert alert-danger d-none" role="alert" aria-live="polite"></div>
        <form id="infTiForm" novalidate>
          <input type="hidden" name="id" id="inf_id" value="">
          <input type="hidden" name="category" id="inf_category" value="">
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input class="form-control" id="inf_name" name="name" required aria-required="true">
            <div class="invalid-feedback">Nama is required.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Detail</label>
            <textarea class="form-control" id="inf_detail" name="detail" rows="3"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="infSave" class="btn btn-primary" disabled>
          <span id="infSaveSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
          <span id="infSaveText">Save</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
$(function(){
  const api = 'php/inf_ti_api.php';
  const modal = new bootstrap.Modal(document.getElementById('infTiModal'));
  let currentCategory = null;
  let editingId = null;

  // DataTable instances
  let dtKomputer = null, dtPrinter = null, dtJaringan = null;

  function initDataTables(){
    dtKomputer = $('#tblKomputer').DataTable({
      paging: true, pageLength: 10, lengthChange: false, searching: true, autoWidth: false, responsive: true,
      columnDefs: [
        { orderable: false, targets: [0,1,2,5] },
        { width: '36px', targets: 1 },
        { width: '40px', targets: 2 },
        { className: 'text-center', targets: [0,1,2] }
      ],
      order: []
    });
    dtPrinter = $('#tblPrinter').DataTable({
      paging: true, pageLength: 10, lengthChange: false, searching: true, autoWidth: false, responsive: true,
      columnDefs: [
        { orderable: false, targets: [0,1,2,5] },
        { width: '36px', targets: 1 },
        { width: '40px', targets: 2 },
        { className: 'text-center', targets: [0,1,2] }
      ],
      order: []
    });
    dtJaringan = $('#tblJaringan').DataTable({
      paging: true, pageLength: 10, lengthChange: false, searching: true, autoWidth: false, responsive: true,
      columnDefs: [
        { orderable: false, targets: [0,1,2,5] },
        { width: '36px', targets: 1 },
        { width: '40px', targets: 2 },
        { className: 'text-center', targets: [0,1,2] }
      ],
      order: []
    });
  }

  function refresh() {
    ['komputer','printer','jaringan'].forEach(cat => {
      $.get(api, {action: 'list', category: cat}, function(resp){
        if (!resp.success) return;
        const rows = [];
        resp.items.forEach((it, idx) => {
          const chk = '<input type="checkbox" class="row-chk" data-id="' + it.id + '">';
          const drag = '<div class="text-center drag-handle" style="width:36px; cursor:grab"><i class="bi bi-list"></i></div>';
          const num = (idx+1);
          const name = escapeHtml(it.name);
          const detail = escapeHtml(it.detail);
          const actions = actionButtons(it.id);
          rows.push([chk, drag, num, name, detail, actions, it.id]); // include id as extra hidden col for mapping
        });

        // update DataTable
        let table, dt;
        if (cat === 'komputer') { dt = dtKomputer; table = '#tblKomputer'; }
        if (cat === 'printer') { dt = dtPrinter; table = '#tblPrinter'; }
        if (cat === 'jaringan') { dt = dtJaringan; table = '#tblJaringan'; }
        if (!dt) return;
        dt.clear();
        rows.forEach(r => dt.row.add(r.slice(0,6)) );
        dt.draw(false);
        // set data-id and draggable on rows in DOM to support drag
        const nodes = $(table + ' tbody tr');
        nodes.each(function(i){
          const id = rows[i][6];
          $(this).attr('data-id', id).attr('draggable', true);
          // ensure checkbox has correct data-id (DataTables may clone)
          $(this).find('.row-chk').attr('data-id', id);
        });
      }, 'json');
    });
  }

  function actionButtons(id) {
    if (!INF_TI_IS_ADMIN) return '<span class="text-muted">No actions</span>';
    return '<button class="btn btn-sm btn-secondary btn-edit me-1" data-id="' + id + '">Edit</button>' +
           '<button class="btn btn-sm btn-danger btn-delete" data-id="' + id + '">Delete</button>';
  }

  function capitalize(s){ return s.charAt(0).toUpperCase() + s.slice(1); }
  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; }); }

  // open modal for add
  $('#addJaringan').on('click', function(){ openModal('jaringan'); });
  $('#addKomputer').on('click', function(){ openModal('komputer'); });
  $('#addPrinter').on('click', function(){ openModal('printer'); });

  function openModal(category, item){
    currentCategory = category;
    editingId = item ? item.id : null;
    $('#inf_category').val(category);
    $('#inf_id').val(editingId || '');
    $('#inf_name').val(item ? item.name : '');
    $('#inf_detail').val(item ? item.detail : '');
    $('#infTiModalLabel').text(editingId ? 'Edit Item' : 'Add Item');
    // reset validation state
    $('#infAlert').addClass('d-none').text('');
    $('#inf_name').removeClass('is-invalid');
    $('#infSave').prop('disabled', !INF_TI_IS_ADMIN || !$('#inf_name').val().trim());
    modal.show();
  }

  // enable focus and validation when shown
  document.getElementById('infTiModal').addEventListener('shown.bs.modal', function(){
    $('#inf_name').trigger('focus');
  });

  // live validation
  $('#inf_name').on('input', function(){
    const valid = $(this).val().trim() !== '';
    $(this).toggleClass('is-invalid', !valid);
    $('#infSave').prop('disabled', !INF_TI_IS_ADMIN || !valid);
  });

  function showToast(message, type='success'){
    const id = 't' + Date.now();
    const el = $('<div class="alert alert-' + type + ' alert-dismissible" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
    $('#infToastPlaceholder').append(el);
    setTimeout(()=> el.alert('close'), 4000);
  }

  // handle save
  $('#infSave').on('click', function(){
    const id = $('#inf_id').val();
    const cat = $('#inf_category').val();
    const name = $('#inf_name').val().trim();
    const detail = $('#inf_detail').val().trim();
    if (!name) { $('#inf_name').addClass('is-invalid'); return; }
    $('#infAlert').addClass('d-none').text('');
    $('#infSaveSpinner').removeClass('d-none');
    $('#infSave').prop('disabled', true);
    const url = api + '?action=' + (id ? 'edit' : 'add');
    const data = id ? {id: id, name: name, detail: detail} : {category: cat, name: name, detail: detail};
    $.post(url, data, function(resp){
      $('#infSaveSpinner').addClass('d-none');
      if (resp.success) {
        modal.hide();
        refresh();
        showToast(id ? 'Item updated' : 'Item added');
      } else {
        $('#infAlert').removeClass('d-none').text(resp.msg || 'Error');
      }
    }, 'json').fail(function(){
      $('#infSaveSpinner').addClass('d-none');
      $('#infAlert').removeClass('d-none').text('Network error');
    }).always(function(){
      $('#infSave').prop('disabled', !INF_TI_IS_ADMIN || !$('#inf_name').val().trim());
    });
  });

  // edit/delete handlers
  $(document).on('click', '.btn-edit', function(){
    if (!INF_TI_IS_ADMIN) return;
    const id = $(this).data('id');
    // fetch item details from list
    $.get(api, {action: 'list'}, function(resp){
      if (!resp.success) return;
      const found = resp.items.find(x => x.id == id);
      if (found) openModal(found.category, found);
    }, 'json');
  });

  $(document).on('click', '.btn-delete', function(){
    if (!INF_TI_IS_ADMIN) return;
    if (!confirm('Delete this item?')) return;
    const id = $(this).data('id');
    $.post(api + '?action=delete', {id: id}, function(resp){
      if (resp.success) { refresh(); showToast('Item deleted','warning'); } else {
        alert(resp.msg || 'Error');
      }
    }, 'json');
  });

  // header checkboxes
  $('#chkAllKomputer').on('change', function(){ $('#tblKomputer tbody .row-chk').prop('checked', $(this).prop('checked')); });
  $('#chkAllPrinter').on('change', function(){ $('#tblPrinter tbody .row-chk').prop('checked', $(this).prop('checked')); });
  $('#chkAllJaringan').on('change', function(){ $('#tblJaringan tbody .row-chk').prop('checked', $(this).prop('checked')); });

  // initialize DataTables after DOM ready
  initDataTables();
  // wire column filters to DataTables
  $('#tblKomputer thead .filter-name').on('input', function(){ dtKomputer.column(3).search(this.value).draw(); });
  $('#tblKomputer thead .filter-detail').on('input', function(){ dtKomputer.column(4).search(this.value).draw(); });
  $('#tblPrinter thead .filter-name').on('input', function(){ dtPrinter.column(3).search(this.value).draw(); });
  $('#tblPrinter thead .filter-detail').on('input', function(){ dtPrinter.column(4).search(this.value).draw(); });
  $('#tblJaringan thead .filter-name').on('input', function(){ dtJaringan.column(3).search(this.value).draw(); });
  $('#tblJaringan thead .filter-detail').on('input', function(){ dtJaringan.column(4).search(this.value).draw(); });

  // call refresh to populate tables
  refresh();

  // redraw DataTables when a tab becomes visible to fix rendering in hidden tabs (use small delay for Bootstrap animation)
  $('button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e){
    const target = $(e.target).attr('data-bs-target');
    setTimeout(function(){
      if (target === '#tab-komputer' && dtKomputer) { dtKomputer.columns.adjust().draw(); }
      if (target === '#tab-printer' && dtPrinter) { dtPrinter.columns.adjust().draw(); }
      if (target === '#tab-jaringan' && dtJaringan) { dtJaringan.columns.adjust().draw(); }
    }, 120);
  });

  // initial adjust for the active tab (delayed)
  setTimeout(function(){
    if (dtKomputer) { dtKomputer.columns.adjust().draw(); }
    if (dtPrinter && $('#tab-printer').hasClass('show')) { dtPrinter.columns.adjust().draw(); }
    if (dtJaringan && $('#tab-jaringan').hasClass('show')) { dtJaringan.columns.adjust().draw(); }
  }, 120);

  function selectedIds(){
    const ids = [];
    $('.row-chk:checked').each(function(){ ids.push($(this).data('id')); });
    return ids;
  }

  // drag-and-drop ordering
  function makeSortable(tbodySelector, saveBtnSelector){
    const tbody = $(tbodySelector);
    tbody.on('dragstart', 'tr', function(e){
      e.originalEvent.dataTransfer.setData('text/plain', $(this).data('id'));
      $(this).addClass('dragging');
    });
    tbody.on('dragover', 'tr', function(e){
      e.preventDefault();
      const dragging = tbody.find('.dragging');
      if (!dragging.length) return;
      const target = $(this);
      if (target.hasClass('dragging')) return;
      // determine whether to insert before or after based on mouse position
      const rect = this.getBoundingClientRect();
      const mid = rect.top + rect.height / 2;
      if (e.originalEvent.clientY < mid) target.before(dragging);
      else target.after(dragging);
    });
    tbody.on('dragend', 'tr', function(){
      $(this).removeClass('dragging');
      // renumber
      tbody.find('tr').each(function(i){ $(this).find('td').eq(2).text(i+1); });
      $(saveBtnSelector).prop('disabled', false);
    });

    // set drag cursor on handle only
    tbody.on('mousedown', '.drag-handle', function(){ $(this).closest('tr').attr('draggable', true); });
    tbody.on('mouseup', '.drag-handle', function(){ $(this).closest('tr').attr('draggable', false); });
  }

  makeSortable('#tblKomputer tbody', '#saveOrderKomputer');
  makeSortable('#tblPrinter tbody', '#saveOrderPrinter');
  makeSortable('#tblJaringan tbody', '#saveOrderJaringan');

  // Save order handlers
  $('#saveOrderKomputer').on('click', function(){ saveOrder('komputer', '#tblKomputer', this); });
  $('#saveOrderPrinter').on('click', function(){ saveOrder('printer', '#tblPrinter', this); });
  $('#saveOrderJaringan').on('click', function(){ saveOrder('jaringan', '#tblJaringan', this); });

  function saveOrder(category, tableSelector, btn){
    const ids = $(tableSelector + ' tbody tr').map(function(){ return $(this).data('id'); }).get();
    if (!ids.length) return;
    $(btn).prop('disabled', true).text('Saving...');
    $.post(api + '?action=save_order', {category: category, ids: ids}, function(resp){
      if (resp.success) { showToast('Order saved'); refresh(); } else { alert(resp.msg || 'Error'); }
      $(btn).prop('disabled', false).text('Save Order');
    }, 'json').fail(function(){ alert('Network error'); $(btn).prop('disabled', false).text('Save Order'); });
  }
  // helper to get selected columns
  function getSelectedCols(){
    return $('.export-col:checked').map(function(){ return $(this).val(); }).get();
  }

  // Export All
  $('#btnExportAll').on('click', function(){
    const cols = getSelectedCols();
    const q = cols.length ? '&cols=' + encodeURIComponent(cols.join(',')) : '';
    window.location = api + '?action=export' + q;
  });

  // Export Selected
  $('#btnExportSelected').on('click', function(){
    const ids = selectedIds();
    if (!ids.length){ alert('No items selected'); return; }
    const cols = getSelectedCols();
    const q = cols.length ? '&cols=' + encodeURIComponent(cols.join(',')) : '';
    window.location = api + '?action=export&ids=' + ids.join(',') + q;
  });

  // Export columns select all/none
  $('#btnSelectAllCols').on('click', function(e){ e.preventDefault(); $('.export-col').prop('checked', true); });
  $('#btnDeselectAllCols').on('click', function(e){ e.preventDefault(); $('.export-col').prop('checked', false); });

  // Delete Selected
  $('#btnDeleteSelected').on('click', function(){
    if (!INF_TI_IS_ADMIN){ alert('Admin only'); return; }
    const ids = selectedIds();
    if (!ids.length){ alert('No items selected'); return; }
    if (!confirm('Delete selected items?')) return;
    $.post(api + '?action=bulk_delete', {ids: ids}, function(resp){
      if (resp.success) { refresh(); showToast(resp.deleted + ' items deleted','warning'); } else {
        alert(resp.msg || 'Error');
      }
    }, 'json');
  });

  refresh();
});
</script>

<?php include 'layout_footer.php'; ?>
