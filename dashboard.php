<?php include 'layout_header.php'; ?>
<!-- Dashboard Content -->
<div class="container-fluid mt-4">
    <h1 class="h3 mb-4">CAT</h1>
    <div class="card mt-4">
        <div class="card-header">Sample CAT Items (AJAX)</div>
        <div class="card-body">
            <table class="table table-bordered" id="catTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
    <script>
    $(document).ready(function() {
        $.getJSON('php/sample_cat.php', function(response) {
            var rows = '';
            $.each(response.data, function(i, item) {
                rows += '<tr>' +
                    '<td>' + item.id + '</td>' +
                    '<td>' + item.name + '</td>' +
                    '<td>' + item.status + '</td>' +
                    '</tr>';
            });
            $('#catTable tbody').html(rows);
        });
    });
    <!-- Dashboard Content ends here -->
<?php include 'layout_footer.php'; ?>
