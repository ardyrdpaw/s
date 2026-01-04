<?php include 'layout_header.php'; ?>
<div class="container-fluid mt-4">
  <h1 class="h3 mb-4">Preferences</h1>
  <div class="card">
    <div class="card-body">
      <form id="prefsForm">
        <div class="mb-3">
          <label class="form-label">Theme</label>
          <select id="prefTheme" class="form-select" name="theme">
            <option value="light">Light</option>
            <option value="dark">Dark</option>
          </select>
        </div>
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="prefNotifications" name="notifications">
          <label class="form-check-label" for="prefNotifications">Enable notifications</label>
        </div>
        <button class="btn btn-primary" id="savePrefs">Save Preferences</button>
      </form>
    </div>
    <script>
    $(function(){
      const userId = <?= intval($_SESSION['user_id'] ?? 0) ?>;
      if (!userId) { $('#prefsForm').hide(); $('#prefsForm').before('<div class="alert alert-danger">Not logged in</div>'); return; }
      function load(){
        $.getJSON('php/user_api.php?action=preferences&id=' + userId, function(resp){
          if (resp.data){
            const prefs = resp.data || {};
            $('#prefTheme').val(prefs.theme || 'light');
            $('#prefNotifications').prop('checked', !!prefs.notifications);
          }
        });
      }
      $('#prefsForm').submit(function(e){
        e.preventDefault();
        const prefs = { theme: $('#prefTheme').val(), notifications: $('#prefNotifications').prop('checked') ? 1 : 0 };
        $.post('php/user_api.php?action=preferences', { id: userId, preferences: JSON.stringify(prefs) }, function(resp){
          if (resp.success) {
            // apply theme immediately and cache locally
            if (prefs.theme === 'dark') { document.documentElement.classList.add('theme-dark'); document.body.classList.add('theme-dark'); }
            else { document.documentElement.classList.remove('theme-dark'); document.body.classList.remove('theme-dark'); }
            localStorage.setItem('prefs_theme', prefs.theme);
            $('<div class="alert alert-success mt-2">Preferences saved</div>').insertBefore('#prefsForm').delay(2500).fadeOut(400);
          } else {
            $('<div class="alert alert-danger mt-2">Error: ' + (resp.msg || 'Unknown') + '</div>').insertBefore('#prefsForm').delay(4000).fadeOut(400);
          }
        }, 'json');
      });
      load();
    });
    </script>
  </div>
</div>
<?php include 'layout_footer.php'; ?>