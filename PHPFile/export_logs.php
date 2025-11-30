<?php
include __DIR__ . '/../Srcipt/access_control.php';
include __DIR__ . '/../Srcipt/db_connect.php';

// Check if user has admin role
if (!$auth->hasRole('admin')) {
    die('Access denied');
}

// Get export parameters
$format = $_GET['format'] ?? 'csv';
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$role_filter = $_GET['role'] ?? '';

// Build query (same as logs.php)
$sql = "SELECT id, rfid_uid, role, status, scan_time FROM logs WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (rfid_uid LIKE ? OR role LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if (!empty($status_filter) && in_array($status_filter, ['Access Granted', 'Access Denied'])) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($role_filter) && in_array($role_filter, ['student', 'employee', 'guest', 'security'])) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

$sql .= " ORDER BY scan_time DESC";

try {
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($format === 'csv') {
        exportCSV($result);
    } elseif ($format === 'pdf') {
        exportPDF($result, $search, $status_filter, $role_filter);
    }
    
} catch (Exception $e) {
    die("Export failed: " . $e->getMessage());
}

function exportCSV($result) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=rfid_logs_' . date('Y-m-d_H-i-s') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Headers
    fputcsv($output, ['ID', 'RFID UID', 'Role', 'Status', 'Scan Time']);
    
    // Data
    while ($row = $result->fetch_assoc()) {
        // Safely handle NULL values
        $id = $row['id'] ?? '';
        $rfid_uid = $row['rfid_uid'] ?? '';
        $role = $row['role'] ?? 'Unknown';
        $status = $row['status'] ?? 'Unknown';
        $scan_time = $row['scan_time'] ?? '';
        
        // Convert role to proper case (handle NULL safely)
        $role_display = ($role && $role !== 'Unknown') ? ucfirst($role) : 'Unknown';
        
        fputcsv($output, [
            $id,
            $rfid_uid,
            $role_display,
            $status,
            $scan_time
        ]);
    }
    
    fclose($output);
    exit;
}

function exportPDF($result, $search, $status_filter, $role_filter) {
    // Correct TCPDF path - adjust based on your actual folder structure
    $tcpdf_path = __DIR__ . '/../tcpdf/TCPDF-main/tcpdf.php';
    
    if (!file_exists($tcpdf_path)) {
        // Try alternative path
        $tcpdf_path = __DIR__ . '/../tcpdf/tcpdf.php';
        if (!file_exists($tcpdf_path)) {
            // Fallback to basic text export if TCPDF not found
            error_log("TCPDF not found at: " . $tcpdf_path);
            exportBasicText($result, $search, $status_filter, $role_filter);
            return;
        }
    }
    
    require_once($tcpdf_path);
    
    // Create new PDF document
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('RSU Security System');
    $pdf->SetAuthor('RSU Security System');
    $pdf->SetTitle('RFID Access Logs');
    $pdf->SetSubject('RFID Access Logs Export');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 16);
    
    // Title
    $pdf->Cell(0, 10, 'RSU Security Management System - RFID Access Logs', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Generated on: ' . date('F j, Y g:i A'), 0, 1, 'C');
    
    // Filters info
    $filters = [];
    if (!empty($search)) $filters[] = "Search: " . $search;
    if (!empty($status_filter)) $filters[] = "Status: " . $status_filter;
    if (!empty($role_filter)) $filters[] = "Role: " . $role_filter;
    
    if (!empty($filters)) {
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(0, 8, 'Filters: ' . implode(', ', $filters), 0, 1);
        $pdf->Ln(5);
    }
    
    // Create table header
    $pdf->SetFont('helvetica', 'B', 10);
    
    // Define column widths for landscape mode
    $widths = array(15, 60, 25, 35, 45); // Total: 180 (good for landscape)
    
    // Header background
    $pdf->SetFillColor(64, 115, 158); // RSU blue color
    $pdf->SetTextColor(255);
    $pdf->SetLineWidth(0.3);
    
    // Header row
    $pdf->Cell($widths[0], 8, 'ID', 1, 0, 'C', true);
    $pdf->Cell($widths[1], 8, 'RFID UID', 1, 0, 'C', true);
    $pdf->Cell($widths[2], 8, 'Role', 1, 0, 'C', true);
    $pdf->Cell($widths[3], 8, 'Status', 1, 0, 'C', true);
    $pdf->Cell($widths[4], 8, 'Scan Time', 1, 1, 'C', true);
    
    // Table content
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0);
    $fill = false;
    $row_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Check if we need a new page (after 25 rows)
        if ($row_count > 0 && $row_count % 25 == 0) {
            $pdf->AddPage();
            
            // Reprint header on new page
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(64, 115, 158);
            $pdf->SetTextColor(255);
            $pdf->Cell($widths[0], 8, 'ID', 1, 0, 'C', true);
            $pdf->Cell($widths[1], 8, 'RFID UID', 1, 0, 'C', true);
            $pdf->Cell($widths[2], 8, 'Role', 1, 0, 'C', true);
            $pdf->Cell($widths[3], 8, 'Status', 1, 0, 'C', true);
            $pdf->Cell($widths[4], 8, 'Scan Time', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(0);
        }
        
        // Alternate row background
        if ($fill) {
            $pdf->SetFillColor(240, 240, 240);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }
        
        // Safely handle NULL values
        $id = $row['id'] ?? '';
        $rfid_uid = $row['rfid_uid'] ?? '';
        $role = $row['role'] ?? 'Unknown';
        $status = $row['status'] ?? 'Unknown';
        $scan_time = $row['scan_time'] ?? '';
        
        // Convert role to proper case (handle NULL safely)
        $role_display = ($role && $role !== 'Unknown') ? ucfirst($role) : 'Unknown';
        
        // Row data
        $pdf->Cell($widths[0], 6, $id, 'LR', 0, 'C', $fill);
        $pdf->Cell($widths[1], 6, $rfid_uid, 'LR', 0, 'L', $fill);
        $pdf->Cell($widths[2], 6, $role_display, 'LR', 0, 'C', $fill);
        
        // Color code status
        if ($status == 'Access Granted') {
            $pdf->SetTextColor(0, 128, 0); // Green for granted
        } else {
            $pdf->SetTextColor(255, 0, 0); // Red for denied
        }
        $pdf->Cell($widths[3], 6, $status, 'LR', 0, 'C', $fill);
        $pdf->SetTextColor(0); // Reset to black
        
        $pdf->Cell($widths[4], 6, $scan_time, 'LR', 0, 'C', $fill);
        $pdf->Ln();
        
        $fill = !$fill;
        $row_count++;
    }
    
    // Closing line
    $pdf->Cell(array_sum($widths), 0, '', 'T');
    
    // Statistics section
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(64, 115, 158);
    $pdf->Cell(0, 8, 'Export Summary', 0, 1);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0);
    $pdf->Cell(0, 6, 'Total Records Exported: ' . $row_count, 0, 1);
    $pdf->Cell(0, 6, 'Generated by: RSU Security Management System', 0, 1);
    $pdf->Cell(0, 6, 'Export Time: ' . date('Y-m-d H:i:s'), 0, 1);
    
    if (!empty($filters)) {
        $pdf->Cell(0, 6, 'Applied Filters: ' . implode(', ', $filters), 0, 1);
    }
    
    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(128);
    $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages(), 0, 0, 'C');
    
    // Close and output PDF document
    $pdf->Output('rfid_logs_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    exit;
}

function exportBasicText($result, $search, $status_filter, $role_filter) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename=rfid_logs_' . date('Y-m-d_H-i-s') . '.txt');
    
    echo "RSU Security Management System\n";
    echo "RFID Access Logs Export\n";
    echo "Generated: " . date('F j, Y g:i A') . "\n";
    
    // Filters info
    $filters = [];
    if (!empty($search)) $filters[] = "Search: " . $search;
    if (!empty($status_filter)) $filters[] = "Status: " . $status_filter;
    if (!empty($role_filter)) $filters[] = "Role: " . $role_filter;
    
    if (!empty($filters)) {
        echo "Filters: " . implode(', ', $filters) . "\n";
    }
    
    echo "==========================================\n\n";
    echo str_pad("ID", 6) . str_pad("RFID UID", 20) . str_pad("Role", 12) . str_pad("Status", 16) . "Scan Time\n";
    echo str_repeat("-", 80) . "\n";
    
    $row_count = 0;
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['id'], 6) . 
             str_pad($row['rfid_uid'], 20) . 
             str_pad(ucfirst($row['role']), 12) . 
             str_pad($row['status'], 16) . 
             $row['scan_time'] . "\n";
        $row_count++;
    }
    
    echo "\n==========================================\n";
    echo "Total Records: " . $row_count . "\n";
    echo "Export Time: " . date('Y-m-d H:i:s') . "\n";
    
    exit;
}
?>