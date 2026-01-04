<?php include 'layout_header.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<!-- Signage Content -->
<div class="container-fluid mt-4">
    <h1 class="h3 mb-4">Signage</h1>
    <div id="crudAlert" style="display:none;"></div>
    <div class="card mt-4">
        <div class="card-header">Signage Items</div>
        <div class="card-body">
            <div id="clockControls">
            <div class="mb-3 row align-items-center">
                <label class="col-auto col-form-label">Footer Clock Format</label>
                <div class="col-auto">
                    <select id="clockFormatSelect" class="form-select"></select>
                </div>
                <div class="col-auto">
                    <button id="saveClockFormat" class="btn btn-primary">Save</button>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-auto col-form-label">Add Custom Format</label>
                <div class="col-4">
                    <input id="customFormatLabel" class="form-control" placeholder="Label (e.g. My Format)">
                </div>
                <div class="col-5">
                    <input id="customFormatPattern" class="form-control" placeholder="ICU pattern (e.g. EEEE, d MMMM yyyy HH:mm:ss)">
                </div>
                <div class="col-auto">
                    <button id="addCustomFormat" class="btn btn-outline-secondary">Add</button>
                </div>
            </div>
            </div>
            <div class="mb-3">
                <button class="btn btn-success" id="addSignageBtn">Add Item</button>
                <a class="btn btn-outline-primary ms-2" id="previewSignageBtn" href="saview.php" target="_blank" rel="noopener noreferrer" title="Open signage viewer in a new tab"><i class="bi bi-eye"></i> Preview</a>
            </div>
            <!-- Category Tabs -->
            <ul class="nav nav-tabs mb-3" id="signageTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" data-cat="Video" type="button">Video</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-cat="Galeri" type="button">Galeri</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-cat="Kegiatan" type="button">Kegiatan</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-cat="Agenda" type="button">Agenda</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-cat="Text" type="button">Text</button></li>
            </ul>

            <table class="table table-bordered" id="signageMgmtTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Content</th>
                        <th>Autoplay</th>
                        <th>Loop</th>
                        <th>Muted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
        <!-- Add/Edit Signage Modal -->
        <div class="modal fade" id="signageModal" tabindex="-1" aria-labelledby="signageModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="signageModalLabel">Add/Edit Signage Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="signageForm">
                            <input type="hidden" id="signageId" name="id">
                            <div class="mb-3">
                                <label for="signageName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="signageName" name="name" required>
                            </div>
                            <div class="mb-3 row">
                                <div class="col-md-6">
                                    <label for="signageType" class="form-label">Type</label>
                                    <select class="form-select" id="signageType" name="type" required>
                                        <option value="Text">Text</option>
                                        <option value="Video">Video</option>
                                        <option value="Images">Images</option>
                                        <option value="Table">Table</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="signageCategory" class="form-label">Category</label>
                                    <select class="form-select" id="signageCategory" name="category" required>
                                        <option value="Video">Video</option>
                                        <option value="Galeri">Galeri</option>
                                        <option value="Kegiatan">Kegiatan</option>
                                        <option value="Agenda">Agenda</option>
                                        <option value="Text">Text</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="signageContent" class="form-label">Content (URL or text)</label>
                                <textarea class="form-control" id="signageContent" name="content" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="signageFile" class="form-label">Upload Media File</label>
                                <input type="file" class="form-control" id="signageFile" name="file" accept="image/*,video/*">
                                <small class="text-muted">Upload image or video file (optional)</small>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="signageAutoplay" name="autoplay" value="1">
                                    <label class="form-check-label" for="signageAutoplay">
                                        Autoplay (for Video type)
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="signageLoop" name="loop" value="1">
                                    <label class="form-check-label" for="signageLoop">
                                        Loop (for Video type)
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="signageMuted" name="muted" value="1" checked>
                                    <label class="form-check-label" for="signageMuted">
                                        Muted by default (recommended for autoplay video)
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <script>
    $(document).ready(function() {
        let isWysiwygEnabled = false;

        function enableWysiwyg() {
            if (isWysiwygEnabled) return;
            $('#signageContent').summernote({
                placeholder: 'Content (text / HTML)',
                height: 260,
                styleTags: ['p', 'blockquote', 'pre', 'h1', 'h2', 'h3', 'h4'],
                lineHeights: ['1.0', '1.2', '1.4', '1.6', '2.0'],
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                    ['fontname', ['fontname']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['height', ['height']],
                    ['insert', ['link', 'hr', 'table']],
                    ['view', ['codeview', 'undo', 'redo']]
                ],
                fontNames: ['Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Georgia', 'Verdana'],
                fontSizes: ['10', '12', '14', '16', '18', '20', '24', '28', '32'],
                shortcuts: true
            });
            isWysiwygEnabled = true;
        }

        function disableWysiwyg() {
            if (!isWysiwygEnabled) return;
            const html = $('#signageContent').summernote('code');
            $('#signageContent').summernote('destroy');
            $('#signageContent').val(html);
            isWysiwygEnabled = false;
        }

        function toggleEditorByType(type) {
            if (type === 'Text' || type === 'Table') {
                enableWysiwyg();
            } else {
                disableWysiwyg();
            }
        }

        var __allSignageItems = [];
        var __activeSignageCategory = 'Video';
        var SIGNAGE_CATEGORIES = ['Video','Galeri','Kegiatan','Agenda','Text'];

        function deriveCategoryFromItem(item) {
            if (item.category && item.category.trim() !== '') return item.category;
            var t = (item.type || '').toLowerCase();
            if (t === 'video') return 'Video';
            if (t === 'images') return 'Galeri';
            return 'Text';
        }

        function renderSignageTableForCategory(cat) {
            var rows = '';
            $.each(__allSignageItems, function(i, item) {
                var category = deriveCategoryFromItem(item);
                if (category !== cat) return;
                var autoplay = Number(item.autoplay) === 1;
                var loop = Number(item.loop) === 1;
                var muted = Number(item.muted) === 1;
                rows += '<tr>' +
                    '<td>' + item.name + '</td>' +
                    '<td>' + item.type + '</td>' +
                    '<td>' + category + '</td>' +
                    '<td>' + (item.content ? item.content : '') + '</td>' +
                    '<td>' + (autoplay ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>') + '</td>' +
                    '<td>' + (loop ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>') + '</td>' +
                    '<td>' + (muted ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>') + '</td>' +
                    '<td>' +
                    '<button class="btn btn-sm btn-warning editSignageBtn" data-id="' + item.id + '">Edit</button> ' +
                    '<button class="btn btn-sm btn-danger deleteSignageBtn" data-id="' + item.id + '">Delete</button>' +
                    '</td>' +
                    '</tr>';
            });
            $('#signageMgmtTable tbody').html(rows);
        }

        function renderTabs() {
            $('#signageTabs button').removeClass('active');
            $('#signageTabs button[data-cat="' + __activeSignageCategory + '"]').addClass('active');
        }

        function loadSignage() {
            $.getJSON('php/signage_api.php?action=list', function(response) {
                __allSignageItems = response.data || [];
                renderTabs();
                renderSignageTableForCategory(__activeSignageCategory);
            });
        }
        loadSignage();

        // Tab click handler
        $('#signageTabs').on('click', 'button', function() {
            var cat = $(this).data('cat');
            if (!cat) return;
            __activeSignageCategory = cat;
            renderTabs();
            renderSignageTableForCategory(cat);
        });
        // load clock format
        function loadClockFormat() {
            $.getJSON('php/signage_api.php?action=get_clock_format', function(resp) {
                if (!resp) return;
                $('#clockFormatSelect').empty();
                if (Array.isArray(resp.formats)) {
                    resp.formats.forEach(function(f) {
                        var key = f.key || ('k_'+Date.now());
                        var opt = $('<option>').val(key).text(f.label);
                        $('#clockFormatSelect').append(opt);
                    });
                    // render preview table
                    renderFormatsPreview(resp.formats);
                }
                if (resp.selected) $('#clockFormatSelect').val(resp.selected);
            });
        }
        loadClockFormat();

        $('#saveClockFormat').click(function(){
            var fmt = $('#clockFormatSelect').val();
            $.post('php/signage_api.php?action=set_clock_format', {format: fmt}, function(r){
                showCrudAlert('Clock format saved.', 'success');
                loadClockFormat();
            }, 'json');
        });

        // render preview table for formats
        function renderFormatsPreview(formats) {
            $('#clockFormatsPreview').remove();
            var tbl = $('<table id="clockFormatsPreview" class="table table-sm">');
            tbl.append('<thead><tr><th>Label</th><th>Preview</th><th></th></tr></thead>');
            var body = $('<tbody>');
            formats.forEach(function(f){
                var key = f.key || ('k_'+Date.now());
                var row = $('<tr>');
                row.append($('<td>').text(f.label));
                var previewCell = $('<td>').text('Loading...');
                row.append(previewCell);
                var btn = $('<button class="btn btn-sm btn-outline-primary">Select</button>');
                btn.on('click', function(){
                    $('#clockFormatSelect').val(key);
                    $('#saveClockFormat').click();
                });
                row.append($('<td>').append(btn));
                body.append(row);
                // fetch preview from server
                $.getJSON('php/signage_api.php?action=get_server_time&format=' + encodeURIComponent(key), function(r){
                    if (r && r.time) previewCell.text(r.time);
                }).fail(function(){ previewCell.text('N/A'); });
            });
            tbl.append(body);
            $('.card-body').first().append(tbl);
        }

        // Add custom format
        $('#addCustomFormat').click(function(e){
            e.preventDefault();
            var label = $('#customFormatLabel').val().trim();
            var pattern = $('#customFormatPattern').val().trim();
            if (!label || !pattern) { showCrudAlert('Provide label and pattern for custom format.', 'warning'); return; }
            $.getJSON('php/signage_api.php?action=get_clock_format', function(resp){
                var formats = resp.formats || [];
                // generate unique key
                var key = 'custom_' + Date.now();
                formats.push({key: key, label: label + ' — ' + pattern, type: 'custom', pattern: pattern});
                var payload = {formats: JSON.stringify(formats), selected: key};
                $.post('php/signage_api.php?action=set_clock_format', payload, function(r){
                    showCrudAlert('Custom format added and selected.', 'success');
                    loadClockFormat();
                    $('#customFormatLabel').val(''); $('#customFormatPattern').val('');
                }, 'json');
            });
        });
        // Add Signage
        $('#addSignageBtn').click(function() {
            $('#signageForm')[0].reset();
            $('#signageId').val('');
            $('#signageType').val('Text');
            $('#signageCategory').val(__activeSignageCategory || 'Text');
            toggleEditorByType('Text');
            $('#signageModal').modal('show');
        });
        $('#signageType').on('change', function() {
            toggleEditorByType($(this).val());
        });
        // Edit Signage
        $(document).on('click', '.editSignageBtn', function() {
            var id = $(this).data('id');
            $.getJSON('php/signage_api.php?action=get&id=' + id, function(response) {
                if(response.data) {
                    $('#signageId').val(response.data.id);
                    $('#signageName').val(response.data.name);
                    $('#signageType').val(response.data.type);
                    // populate category (fallback to derive)
                    $('#signageCategory').val(response.data.category || deriveCategoryFromItem(response.data));
                    toggleEditorByType(response.data.type);
                    if (isWysiwygEnabled) {
                        $('#signageContent').summernote('code', response.data.content || '');
                    } else {
                        $('#signageContent').val(response.data.content);
                    }
                    $('#signageAutoplay').prop('checked', Number(response.data.autoplay) === 1);
                    $('#signageLoop').prop('checked', Number(response.data.loop) === 1);
                    $('#signageMuted').prop('checked', Number(response.data.muted) === 1);
                    $('#signageModal').modal('show');
                }
            });
        });
        // Delete Signage
        $(document).on('click', '.deleteSignageBtn', function() {
            if(confirm('Are you sure you want to delete this item?')) {
                var id = $(this).data('id');
                $.post('php/signage_api.php?action=delete', {id: id}, function(response) {
                    showCrudAlert('Signage item deleted successfully.', 'success');
                    loadSignage();
                }, 'json');
            }
        });
        // Save Signage
        $('#signageForm').submit(function(e) {
            e.preventDefault();
            if (isWysiwygEnabled) {
                $('#signageContent').val($('#signageContent').summernote('code'));
            }
            var formData = new FormData(this);
            var action = $('#signageId').val() ? 'edit' : 'add';
            $.ajax({
                url: 'php/signage_api.php?action=' + action,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    $('#signageModal').modal('hide');
                    if(action === 'add') {
                        showCrudAlert('Signage item added successfully.', 'success');
                    } else {
                        showCrudAlert('Signage item updated successfully.', 'success');
                    }
                    loadSignage();
                }
            });
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
    <!-- Signage Content ends here -->
<?php include 'layout_footer.php'; ?>
