<?php
$header['pageTitle'] = "KPI Review Management";
$header['securityModuleName'] = 'report_scorecard';
require("../includes/header.inc.php");
require("../includes/functions.inc.php");
?>

<div class="p-md-2 m-md-2 bg-white">
<div class="container">
<h3 class="border-bottom mb-4"> <span class="bi bi-graph-up" style="vertical-align: middle;"> Manager KPI Review Management</h3>

<?php
// Handle draft deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_draft') {
    $conn = new mysqli($config['dbServer'], $config['dbUser'], $config['dbPassword'], $config['dbName']);
    
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'error' => 'Database connection failed']));
    }
    
    $draftId = intval($_POST['draft_id']);
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    
    // Verify draft belongs to current user and is a draft
        // Check for both 'guest' and 'Guest User' to handle old entries
        $usernameEscaped = $conn->real_escape_string($username);
        $checkSql = "SELECT id FROM kpiReview WHERE id = $draftId AND status = 'DRAFT' AND (submitted_by = '$usernameEscaped' OR submitted_by = 'Guest User') LIMIT 1";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult && $checkResult->num_rows > 0) {
        $deleteSql = "DELETE FROM kpiReview WHERE id = $draftId";
        if ($conn->query($deleteSql) === TRUE) {
            echo json_encode(['success' => true, 'message' => 'Draft deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error deleting draft: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Draft not found or you do not have permission to delete it']);
    }
    
    $conn->close();
    exit;
}

// Check for user's drafts
$userDrafts = [];
if ($conn && !$conn->connect_error && isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
        // Check for both 'guest' and 'Guest User' to handle old entries
        $usernameEscaped = $conn->real_escape_string($username);
        $draftSql = "SELECT id, month, year, location_name, location_number, created_at, updated_at 
                     FROM kpiReview 
                     WHERE (submitted_by = '$usernameEscaped' OR submitted_by = 'Guest User')
                     AND status = 'DRAFT' 
                     ORDER BY updated_at DESC, year DESC, month DESC";
    $draftResult = $conn->query($draftSql);
    if ($draftResult && $draftResult->num_rows > 0) {
        while($draftRow = $draftResult->fetch_assoc()) {
            $userDrafts[] = $draftRow;
        }
    }
}

// Show "View Drafts" button if user has drafts
if (!empty($userDrafts)) {
    $draftCount = count($userDrafts);
    echo '<div class="mb-3 text-end">';
    echo '<button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#draftsModalManage">';
    echo '<span class="bi bi-file-earmark-text"></span> View Drafts <span class="badge bg-dark">' . $draftCount . '</span>';
    echo '</button>';
    echo '</div>';
}
?>

<!-- Search/Filter Section -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><span class="bi bi-funnel"></span> Filters</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label for="filterLocation" class="form-label"><b>Location:</b></label>
                <select id="filterLocation" class="form-control">
                    <option value="">All Locations</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filterMonth" class="form-label"><b>Month:</b></label>
                <select id="filterMonth" class="form-control">
                    <option value="">All Months</option>
                    <option value="January">January</option>
                    <option value="February">February</option>
                    <option value="March">March</option>
                    <option value="April">April</option>
                    <option value="May">May</option>
                    <option value="June">June</option>
                    <option value="July">July</option>
                    <option value="August">August</option>
                    <option value="September">September</option>
                    <option value="October">October</option>
                    <option value="November">November</option>
                    <option value="December">December</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filterYear" class="form-label"><b>Year:</b></label>
                <input type="number" id="filterYear" class="form-control" placeholder="e.g., 2025" min="2020" max="2099">
            </div>
            <div class="col-md-2">
                <label for="filterSubmittedBy" class="form-label"><b>Submitted By:</b></label>
                <input type="text" id="filterSubmittedBy" class="form-control" placeholder="Search by name">
            </div>
        <!-- Status filter removed - only showing published entries -->
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <button type="button" id="clearFilters" class="btn btn-secondary btn-sm">Clear Filters</button>
            </div>
        </div>
    </div>
</div>

<?php
// Get user's accessible locations from userLocationAccess table
$conn = new mysqli($config['dbServer'], $config['dbUser'], $config['dbPassword'], $config['dbName']);
if ($conn->connect_error) {
    echo '<div class="alert alert-danger">Database connection failed: ' . htmlspecialchars($conn->connect_error) . '</div>';
    $conn = null; // Set to null so we can check later
}

// Get locations user has access to (including their primary location)
$accessibleLocations = [];
if ($conn && !$conn->connect_error) {
    $sql = "SELECT DISTINCT l.locationNumber, l.name 
            FROM locations l
            INNER JOIN userLocationAccess u ON l.locationNumber = u.locationId 
            WHERE u.username = '" . $conn->real_escape_string($_SESSION['username']) . "'
            UNION
            SELECT locationNumber, name FROM locations WHERE locationNumber = '" . $conn->real_escape_string($_SESSION['locID'] ?? '') . "'
            ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $accessibleLocations[] = $row;
        }
    }
}

// If user has admin access, show all locations
$isAdmin = false;
if ($conn && !$conn->connect_error && function_exists('getAccess')) {
    $isAdmin = getAccess($_SESSION['username'], 'admin') == 1;
}
if ($isAdmin && $conn && !$conn->connect_error) {
    $sql = "SELECT locationNumber, name FROM locations ORDER BY name ASC";
    $result = $conn->query($sql);
    $accessibleLocations = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $accessibleLocations[] = $row;
        }
    }
}

// Build location filter for SQL query
// TEMPORARILY: For testing, show all entries if user has no location access
$locationFilter = "";
if ($conn && !$conn->connect_error) {
    if (!$isAdmin && !empty($accessibleLocations)) {
        $locationNumbers = array_column($accessibleLocations, 'locationNumber');
        if (!empty($locationNumbers)) {
            $locationFilter = " AND location_number IN ('" . implode("','", array_map(function($loc) use ($conn) {
                return $conn->real_escape_string($loc);
            }, $locationNumbers)) . "')";
        }
    }
    // If no accessible locations and not admin, show all for testing (remove this later)
    if (!$isAdmin && empty($accessibleLocations)) {
        $locationFilter = ""; // Show all entries for testing
    }
}

// Fetch KPI Review entries
$allRows = [];
if ($conn && !$conn->connect_error) {
    // Default to showing only PUBLISHED entries, unless filter says otherwise
    // Only show published entries
    $statusFilter = " AND status = 'PUBLISHED'";
    
    $sql = "SELECT * FROM kpiReview WHERE 1=1" . $locationFilter . $statusFilter . " ORDER BY created_at DESC, year DESC, month DESC";
    $result = $conn->query($sql);
    
    // Debug: Check if query worked
    if ($result === false) {
        echo '<div class="alert alert-danger">Database query error: ' . htmlspecialchars($conn->error) . '</div>';
    } else {
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $allRows[] = $row;
            }
        }
    }
} else {
    echo '<div class="alert alert-danger">Cannot connect to database. Please check your connection settings.</div>';
}

// Get unique locations for filter dropdown
$uniqueLocations = [];
foreach ($allRows as $row) {
    if (!isset($uniqueLocations[$row['location_number']])) {
        $uniqueLocations[$row['location_number']] = $row['location_name'];
    }
}

?>

<!-- Data Table -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0"><span class="bi bi-table"></span> KPI Review Submissions</h5>
    </div>
    <div class="card-body">
        <table id="kpiReviewTable" class="table table-striped table-bordered table-hover" style="width:100%; table-layout: auto;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Location</th>
                    <th>Month</th>
                    <th>Year</th>
                    <th>Branch Manager</th>
                    <th>Submitted By</th>
                    <th>Status</th>
                    <th>Submitted Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($allRows as $row) {
                        $createdDate = !empty($row['created_at']) ? date('Y-m-d H:i', strtotime($row['created_at'])) : 'N/A';
                        $status = $row['status'] ?? 'PUBLISHED';
                        $statusBadge = ($status == 'DRAFT') ? '<span class="badge bg-warning">DRAFT</span>' : '<span class="badge bg-success">PUBLISHED</span>';
                        $isDraft = ($status == 'DRAFT');
                        $rowClass = $isDraft ? 'table-warning' : '';
                        
                        echo '<tr class="' . $rowClass . '">';
                        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['location_name']) . ' (#' . htmlspecialchars($row['location_number']) . ')</td>';
                        echo '<td>' . htmlspecialchars($row['month']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['year']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['branch_manager'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['submitted_by']) . '</td>';
                        echo '<td>' . $statusBadge . '</td>';
                        echo '<td>' . $createdDate . '</td>';
                        echo '<td>';
                        
                        if ($isDraft) {
                            // For drafts, show Edit and Delete buttons
                            echo '<a href="KPIReview.php?draft=' . $row['id'] . '" class="btn btn-sm btn-warning me-1" title="Edit Draft"><span class="bi bi-pencil"></span> Edit</a>';
                            // Only show delete if it's the current user's draft
                            if (isset($_SESSION['username']) && $row['submitted_by'] == $_SESSION['username']) {
                                echo '<button type="button" class="btn btn-sm btn-danger delete-draft me-1" data-draft-id="' . $row['id'] . '" data-draft-name="' . htmlspecialchars($row['location_name'] . ' - ' . $row['month'] . ' ' . $row['year']) . '" title="Delete Draft"><span class="bi bi-trash"></span></button>';
                            }
                        }
                        
                        echo '<button class="btn btn-sm btn-info view-entry me-1" data-id="' . $row['id'] . '" title="View Details"><span class="bi bi-eye"></span></button>';
                        echo '<a href="KPIReviewView.php?id=' . $row['id'] . '" target="_blank" class="btn btn-sm btn-secondary" title="Open in New Tab"><span class="bi bi-box-arrow-up-right"></span></a>';
                        echo '</td>';
                        echo '</tr>';
                }
                if (empty($allRows)) {
                    echo '<tr><td colspan="9" class="text-center">No KPI Review submissions found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$conn->close();
?>

<!-- View Entry Modal -->
<div class="modal fade" id="viewEntryModal" tabindex="-1" aria-labelledby="viewEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewEntryModalLabel">KPI Review Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewEntryContent">
                <p>Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

</div>
</div>

<script>
$(document).ready(function() {
    // Populate location filter dropdown
    var locations = <?php echo json_encode($accessibleLocations); ?>;
    var locationSelect = $('#filterLocation');
    locations.forEach(function(loc) {
        locationSelect.append('<option value="' + loc.locationNumber + '">' + loc.name + ' (#' + loc.locationNumber + ')</option>');
    });
    
    // Initialize DataTable - explicitly define columns to avoid count mismatch
    // Remove empty message row if it exists before initializing
    $('#kpiReviewTable tbody tr:has(td[colspan])').remove();
    
    var table = $('#kpiReviewTable').DataTable({
        "order": [[7, "desc"]], // Sort by submitted date descending (column 7)
        "pageLength": 25,
        "responsive": false, // Disable responsive to avoid column detection issues
        "autoWidth": false,
        "columnDefs": [
            { "orderable": false, "targets": [8] }, // Disable sorting on Actions column
            { "width": "5%", "targets": [0] }, // ID column
            { "width": "20%", "targets": [1] }, // Location column
            { "width": "10%", "targets": [2, 3] }, // Month and Year columns
            { "width": "15%", "targets": [4, 5] }, // Branch Manager and Submitted By columns
            { "width": "8%", "targets": [6, 7] }, // Status and Submitted Date columns
            { "width": "15%", "targets": [8] } // Actions column
        ],
        "language": {
            "emptyTable": "No KPI Review submissions found."
        }
    });
    
    // Filter by location
    
    $('#filterLocation').on('change', function() {
        var locationNum = this.value;
        if (locationNum) {
            table.column(1).search('#' + locationNum, true, false).draw();
        } else {
            table.column(1).search('').draw();
        }
    });
    
    // Filter by month
    $('#filterMonth').on('change', function() {
        table.column(2).search(this.value).draw();
    });
    
    // Filter by year
    $('#filterYear').on('input', function() {
        table.column(3).search(this.value).draw();
    });
    
    // Filter by submitted by
    $('#filterSubmittedBy').on('keyup', function() {
        table.column(5).search(this.value).draw();
    });
    
    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#filterLocation').val('');
        $('#filterMonth').val('');
        $('#filterYear').val('');
        $('#filterSubmittedBy').val('');
        // Status filter removed
        window.location.href = window.location.pathname; // Reload with default status
    });
    
    // View entry details
    $(document).on('click', '.view-entry', function() {
        var entryId = $(this).data('id');
        $('#viewEntryModal').modal('show');
        $('#viewEntryContent').html('<p>Loading...</p>');
        
        // Fetch entry details via AJAX
        $.ajax({
            url: 'KPIReviewView.php',
            method: 'GET',
            data: { id: entryId },
            success: function(response) {
                $('#viewEntryContent').html(response);
            },
            error: function() {
                $('#viewEntryContent').html('<div class="alert alert-danger">Error loading entry details.</div>');
            }
        });
    });
    
    // Delete draft with confirmation
    $(document).on('click', '.delete-draft', function() {
        var draftId = $(this).data('draft-id');
        var draftName = $(this).data('draft-name');
        var button = $(this);
        
        if (confirm('Are you sure you want to delete the draft for "' + draftName + '"?\n\nThis action cannot be undone.')) {
            // Show loading state
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            
            $.ajax({
                url: 'KPIReviewManage.php',
                method: 'POST',
                data: {
                    action: 'delete_draft',
                    draft_id: draftId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove the draft row or reload page
                        button.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            // Reload page to refresh "My Drafts" section
                            location.reload();
                        });
                    } else {
                        alert('Error deleting draft: ' + (response.error || 'Unknown error'));
                        button.prop('disabled', false).html('<span class="bi bi-trash"></span>');
                    }
                },
                error: function() {
                    alert('Error deleting draft. Please try again.');
                    button.prop('disabled', false).html('<span class="bi bi-trash"></span>');
                }
            });
        }
    });
});
</script>

<?php
// Drafts Modal for Management Page
if (!empty($userDrafts)) {
    echo '<div class="modal fade" id="draftsModalManage" tabindex="-1" aria-labelledby="draftsModalManageLabel" aria-hidden="true">';
    echo '<div class="modal-dialog modal-lg">';
    echo '<div class="modal-content">';
    echo '<div class="modal-header bg-warning bg-opacity-10">';
    echo '<h5 class="modal-title" id="draftsModalManageLabel"><span class="bi bi-file-earmark-text"></span> My Drafts</h5>';
    echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
    echo '</div>';
    echo '<div class="modal-body">';
    echo '<p class="text-muted mb-3">You have <strong>' . count($userDrafts) . '</strong> saved draft(s). Click on a draft to continue editing:</p>';
    echo '<div class="list-group">';
    foreach ($userDrafts as $draft) {
        $draftUrl = 'KPIReview.php?draft=' . $draft['id'];
        $lastUpdated = date('m/d/Y g:i A', strtotime($draft['updated_at']));
        echo '<div class="list-group-item">';
        echo '<div class="d-flex w-100 justify-content-between align-items-center">';
        echo '<div class="flex-grow-1">';
        echo '<a href="' . htmlspecialchars($draftUrl) . '" class="text-decoration-none">';
        echo '<h6 class="mb-1"><span class="bi bi-file-earmark-text text-warning"></span> ' . htmlspecialchars($draft['location_name']) . ' - ' . htmlspecialchars($draft['month']) . ' ' . htmlspecialchars($draft['year']) . '</h6>';
        echo '</a>';
        echo '<small class="text-muted">LOC #' . htmlspecialchars($draft['location_number']) . ' | Last updated: ' . $lastUpdated . '</small>';
        echo '</div>';
        echo '<div class="ms-3">';
        echo '<a href="' . htmlspecialchars($draftUrl) . '" class="btn btn-sm btn-warning me-2"><span class="bi bi-pencil"></span> Edit</a>';
        echo '<button type="button" class="btn btn-sm btn-danger delete-draft" data-draft-id="' . $draft['id'] . '" data-draft-name="' . htmlspecialchars($draft['location_name'] . ' - ' . $draft['month'] . ' ' . $draft['year']) . '"><span class="bi bi-trash"></span> Delete</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
    echo '<div class="modal-footer">';
    echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
?>

  </body>
</html>
<?php
// TODO: Re-enable footer include once module is set up
// require("../includes/footer.inc.php");
?>

