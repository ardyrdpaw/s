<?php include 'layout_header.php'; ?>
<!-- User Management Content -->
<div class="container-fluid mt-4">
    <h1 class="h3 mb-4">User Management</h1>
    <div id="crudAlert" style="display:none;"></div>
    <div class="card mt-4">
      <div class="card-header">User List</div>
      <div class="card-body">
        <button class="btn btn-success mb-3" id="addUserBtn">Add User</button>
        <table class="table table-bordered" id="userMgmtTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Username</th>
              <th>Role</th>
              <th>Keterangan</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <!-- Data will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>
    <!-- Add/Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="userModalLabel">Add/Edit User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="userForm">
              <input type="hidden" id="userId" name="id">
              <div class="mb-3">
                <label for="userName" class="form-label">Name</label>
                <input type="text" class="form-control" id="userName" name="name" required>
              </div>
              <div class="mb-3">
                <label for="userUsername" class="form-label">Username</label>
                <input type="text" class="form-control" id="userUsername" name="username" required>
              </div>
              <div class="mb-3">
                <label for="userPassword" class="form-label">Password</label>
                <input type="password" class="form-control" id="userPassword" name="password" placeholder="leave blank to keep current">
              </div>
              <div class="mb-3">
                <label for="userRole" class="form-label">Role</label>
                <select class="form-select" id="userRole" name="role" required>
                  <option value="admin">Admin</option>
                  <option value="user">User</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="userKeterangan" class="form-label">Keterangan</label>
                <textarea class="form-control" id="userKeterangan" name="keterangan" rows="3"></textarea>
              </div>
              <button type="submit" class="btn btn-primary">Save</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <script>
    $(document).ready(function() {
        function loadUsers() {
            $.getJSON('php/user_api.php?action=list', function(response) {
                var rows = '';
                $.each(response.data, function(i, user) {
                    rows += '<tr>' +
                        '<td>' + user.id + '</td>' +
                        '<td>' + user.name + '</td>' +
                        '<td>' + (user.username || '') + '</td>' +
                        '<td>' + (user.role || '') + '</td>' +
                        '<td>' + (user.keterangan || '') + '</td>' +
                        '<td>' +
                        '<button class="btn btn-sm btn-warning editUserBtn" data-id="' + user.id + '">Edit</button> ' +
                        '<button class="btn btn-sm btn-danger deleteUserBtn" data-id="' + user.id + '">Delete</button>' +
                        '</td>' +
                        '</tr>';
                });
                $('#userMgmtTable tbody').html(rows);
            });
        }
        loadUsers();
        // Add User
        $('#addUserBtn').click(function() {
          $('#userForm')[0].reset();
          $('#userId').val('');
          $('#userModal').modal('show');
        });
        // Edit User
        $(document).on('click', '.editUserBtn', function() {
          var id = $(this).data('id');
          $.getJSON('php/user_api.php?action=get&id=' + id, function(response) {
            if(response.data) {
              $('#userId').val(response.data.id);
              $('#userName').val(response.data.name);
              $('#userUsername').val(response.data.username || '');
              $('#userRole').val(response.data.role);
              $('#userKeterangan').val(response.data.keterangan || '');
              $('#userPassword').val('');
              $('#userModal').modal('show');
            }
          });
        });
        // Delete User
        $(document).on('click', '.deleteUserBtn', function() {
          if(confirm('Are you sure you want to delete this user?')) {
            var id = $(this).data('id');
            $.post('php/user_api.php?action=delete', {id: id}, function(response) {
              showCrudAlert('User deleted successfully.', 'success');
              loadUsers();
            }, 'json');
          }
        });
        // Save User
        $('#userForm').submit(function(e) {
          e.preventDefault();
          var id = $('#userId').val();
          var action = id ? 'edit' : 'add';
          var payload = {
            id: id,
            name: $('#userName').val(),
            username: $('#userUsername').val(),
            role: $('#userRole').val(),
            keterangan: $('#userKeterangan').val()
          };
          var pw = $('#userPassword').val();
          if (pw) payload.password = pw;
          $.post('php/user_api.php?action=' + action, payload, function(response) {
            $('#userModal').modal('hide');
            if(action === 'add') {
              showCrudAlert('User added successfully.', 'success');
            } else {
              showCrudAlert('User updated successfully.', 'success');
            }
            loadUsers();
          }, 'json');
        });

        // Show notification
        function showCrudAlert(message, type) {
          var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>';
          $('#crudAlert').html(alertHtml).show();
          setTimeout(function() { $('#crudAlert').fadeOut(); }, 3000);
        }
    });
    </script>
</div>
<?php include 'layout_footer.php'; ?>
