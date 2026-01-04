<?php include 'layout_header.php'; ?>
<div class="container-fluid mt-4">
  <h1 class="h3 mb-4">Profile</h1>
  <div class="card">
    <div class="card-body">
      <form id="profileForm">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" id="profileName" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" id="profileEmail" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password (leave blank to keep current)</label>
          <input type="password" id="profilePassword" name="password" class="form-control">
        </div>
        <button class="btn btn-primary" id="saveProfile">Save Profile</button>
      </form>
    </div>
    <script>
    $(function(){
      const userId = <?= intval($_SESSION['user_id'] ?? 0) ?>;
      if (!userId) { $('#profileForm').hide(); $('#profileForm').before('<div class="alert alert-danger">Not logged in</div>'); return; }
      function load(){
        $.getJSON('php/user_api.php?action=get&id=' + userId, function(resp){
          if (resp.data){
            $('#profileName').val(resp.data.name || '');
            $('#profileEmail').val(resp.data.email || '');
          }
        });
      }
      $('#profileForm').submit(function(e){
        e.preventDefault();
        const payload = { id: userId, name: $('#profileName').val(), email: $('#profileEmail').val() };
        const pw = $('#profilePassword').val();
        if (pw) payload.password = pw;
        $.post('php/user_api.php?action=edit', payload, function(resp){
          if (resp.success) {
            $('<div class="alert alert-success mt-2">Profile saved</div>').insertBefore('#profileForm').delay(2000).fadeOut(400);
            $('#profilePassword').val('');
          } else {
            $('<div class="alert alert-danger mt-2">Error: ' + (resp.msg || 'Unknown') + '</div>').insertBefore('#profileForm').delay(4500).fadeOut(400);
          }
        }, 'json');
      });
      load();
    });
    </script>
  </div>
</div>
<?php include 'layout_footer.php'; ?>