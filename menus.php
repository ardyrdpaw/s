<?php include 'layout_header.php'; ?>
<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    echo '<div class="container-fluid mt-4"><div class="alert alert-danger">Unauthorized</div></div>';
    include 'layout_footer.php';
    exit;
}
?>
<div class="container-fluid mt-4">
  <h1 class="h3 mb-4">Manage Menus</h1>
  <div id="crudAlert" style="display:none;"></div>
  <div class="card mt-4">
    <div class="card-header">Menus</div>
    <div class="card-body">
      <button id="addMenuBtn" class="btn btn-success mb-3">Add Menu</button>
      <table class="table table-bordered" id="menusTable">
        <thead>
          <tr>
            <th>Label</th>
            <th>Key</th>
            <th class="text-center">Protected</th>
            <th>Sort</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="menuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="menuModalLabel">Add/Edit Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="menuForm">
          <input type="hidden" id="menuId" name="id">
          <div class="mb-3">
            <label class="form-label">Label</label>
            <input id="menuLabel" name="label" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Key (unique)</label>
            <input id="menuKey" name="menu_key" class="form-control" required>
            <small class="text-muted">Example: dashboard, signage, data_kepegawaian</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Sort Order</label>
            <input type="number" id="menuSort" name="sort_order" class="form-control" value="0">
          </div>
          <div class="mb-3 form-check form-switch">
            <input type="hidden" name="protected" value="0">
            <input type="checkbox" class="form-check-input" id="menuProtected" name="protected" value="1">
            <label class="form-check-label" for="menuProtected">Protected (prevents hiding for users)</label>
          </div>
          <button class="btn btn-primary" type="submit">Save</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
$(function(){
  function loadMenus(){
    $.getJSON('php/menus_api.php?action=list', function(resp){
      var rows = '';
      resp.data.forEach(function(m){
        rows += '<tr data-id="'+m.id+'">';
        rows += '<td>'+m.label+'</td>';
        rows += '<td>'+m.menu_key+'</td>';
        rows += '<td class="text-center">'+(m.protected ? '<span class="badge bg-danger">Yes</span>' : '')+'</td>';
        rows += '<td>'+m.sort_order+'</td>';
        rows += '<td><button class="btn btn-sm btn-secondary upMenuBtn" data-id="'+m.id+'" title="Move up">↑</button> <button class="btn btn-sm btn-secondary downMenuBtn" data-id="'+m.id+'" title="Move down">↓</button> <button class="btn btn-sm btn-warning editMenuBtn" data-id="'+m.id+'">Edit</button> <button class="btn btn-sm btn-danger deleteMenuBtn" data-id="'+m.id+'">Delete</button></td>';
        rows += '</tr>';
      });
      $('#menusTable tbody').html(rows);
    });
  }

  $('#addMenuBtn').click(function(){
    $('#menuForm')[0].reset();
    $('#menuId').val('');
    $('#menuKey').prop('readonly', false);
    $('#menuModal').modal('show');
  });

  $(document).on('click', '.editMenuBtn', function(){
    var id = $(this).data('id');
    $.getJSON('php/menus_api.php?action=list', function(resp){
      var found = null;
      resp.data.forEach(function(m){ if(m.id == id) found = m; });
      if(found){
        $('#menuId').val(found.id);
        $('#menuLabel').val(found.label);
        $('#menuKey').val(found.menu_key).prop('readonly', true);
        $('#menuSort').val(found.sort_order);
        // set protected state
        $('#menuProtected').prop('checked', !!found.protected);
        $('#menuModal').modal('show');
      }
    });
  });

  $(document).on('click', '.deleteMenuBtn', function(){
    if(!confirm('Delete this menu and remove associated access entries?')) return;
    var id = $(this).data('id');
    $.post('php/menus_api.php?action=delete', {id: id}, function(resp){
      if(resp.success) { showCrudAlert('Deleted', 'success'); location.reload(); }
      else showCrudAlert(resp.error || 'Failed', 'danger');
    }, 'json');
  });

  // move up/down handlers
  $(document).on('click', '.upMenuBtn', function(){
    var id = $(this).data('id');
    var row = $('#menusTable tbody tr[data-id="'+id+'"]');
    var prev = row.prev('tr');
    if(!prev.length) return;
    var id2 = prev.data('id');
    var sort1 = parseInt(row.find('td').eq(2).text(),10) || 0;
    var sort2 = parseInt(prev.find('td').eq(2).text(),10) || 0;
    var label1 = row.find('td').eq(0).text();
    var label2 = prev.find('td').eq(0).text();
    // swap sorts
    $.post('php/menus_api.php?action=edit', {id: id, label: label1, sort_order: sort2}, function(){
      $.post('php/menus_api.php?action=edit', {id: id2, label: label2, sort_order: sort1}, function(){ location.reload(); });
    }, 'json');
  });
  $(document).on('click', '.downMenuBtn', function(){
    var id = $(this).data('id');
    var row = $('#menusTable tbody tr[data-id="'+id+'"]');
    var next = row.next('tr');
    if(!next.length) return;
    var id2 = next.data('id');
    var sort1 = parseInt(row.find('td').eq(2).text(),10) || 0;
    var sort2 = parseInt(next.find('td').eq(2).text(),10) || 0;
    var label1 = row.find('td').eq(0).text();
    var label2 = next.find('td').eq(0).text();
    // swap sorts
    $.post('php/menus_api.php?action=edit', {id: id, label: label1, sort_order: sort2}, function(){
      $.post('php/menus_api.php?action=edit', {id: id2, label: label2, sort_order: sort1}, function(){ location.reload(); });
    }, 'json');
  });

  $('#menuForm').submit(function(e){
    e.preventDefault();
    var id = $('#menuId').val();
    var action = id ? 'edit' : 'add';
    var fd = $(this).serialize();
    $.post('php/menus_api.php?action='+action, fd, function(resp){
      if(resp.success){ $('#menuModal').modal('hide'); showCrudAlert('Saved', 'success'); location.reload(); }
      else showCrudAlert(resp.error || 'Failed', 'danger');
    }, 'json');
  });

  function showCrudAlert(message, type){
    var html = '<div class="alert alert-'+type+' alert-dismissible fade show" role="alert">'+message+'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    $('#crudAlert').html(html).show(); setTimeout(function(){ $('#crudAlert').fadeOut(); }, 3000);
  }

  loadMenus();
});
</script>

<?php include 'layout_footer.php'; ?>