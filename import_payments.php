<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Database connection
$servername = "srv1752.hstgr.io";
$username = "u229215627_GoldenDreamSQL";
$password = "Azl@n2002";
$dbname = "u229215627_goldenDreamSQL";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

// Process uploaded file
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["excelFile"])) {
    $file = $_FILES["excelFile"]["tmp_name"];
    $fileType = $_FILES["excelFile"]["type"];
    $fileName = $_FILES["excelFile"]["name"];

    // Create logs directory if it doesn't exist
    $logsDir = __DIR__ . '/logs';
    if (!file_exists($logsDir)) {
        if (!mkdir($logsDir, 0777, true)) {
            die("Failed to create logs directory. Please check permissions. Directory path: " . $logsDir);
        }
    }

    // Create log file with current date
    $logFileName = $logsDir . '/failed_payment_imports_' . date('Y-m-d_H-i-s') . '.txt';

    // Debug information
    echo "Attempting to create log file at: " . $logFileName . "<br>";
    echo "Directory exists: " . (file_exists($logsDir) ? 'Yes' : 'No') . "<br>";
    echo "Directory is writable: " . (is_writable($logsDir) ? 'Yes' : 'No') . "<br>";

    $logFile = @fopen($logFileName, 'w');

    if ($logFile === false) {
        $error = error_get_last();
        die("Failed to create log file. Error: " . $error['message'] . "<br>Please check directory permissions. Path: " . $logFileName);
    }

    // Write header to log file
    fwrite($logFile, "Failed Payment Records Log - " . date('Y-m-d H:i:s') . "\n");
    fwrite($logFile, "==========================================\n\n");
    fwrite($logFile, "Original File: " . $fileName . "\n\n");

    try {
        // Check file type
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            throw new Exception("Invalid file type. Please upload an Excel (.xlsx, .xls) or CSV file.");
        }

        // Load the file based on extension
        if ($extension === 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $spreadsheet = $reader->load($file);
        } else {
            $spreadsheet = IOFactory::load($file);
        }

        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        $successCount = 0;
        $errorCount = 0;

        // Hardcoded values
        $schemeID = 5;
        $installmentID = 22;
        $amount = 1000;
        $paymentStatus = 'Verified';
        $screenshotURL = 'uploads/payments/fromExcel.png';
        $verifierRemark = 'Verified via Excel automation';
        $payerRemark = 'Payment added from Excel automation';

        // Prepare the SQL statement for getting CustomerID
        $customerStmt = $conn->prepare("SELECT CustomerID FROM Customers WHERE CustomerUniqueID = ?");

        // Prepare the SQL statement for inserting payment
        $paymentStmt = $conn->prepare("INSERT INTO Payments (CustomerID, SchemeID, InstallmentID, Amount, Status, SubmittedAt, VerifiedAt, ScreenshotURL, VerifierRemark, PayerRemark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Start from row 2 to skip header
        for ($row = 2; $row <= $highestRow; $row++) {
            $customerUniqueID = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
            $submittedDate = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
            $today = date('Y-m-d'); // Today's date for VerifiedAt

            try {
                // Convert date from DD-MM-YYYY to YYYY-MM-DD
                if ($submittedDate) {
                    $dateObj = DateTime::createFromFormat('d-m-Y', $submittedDate);
                    if ($dateObj) {
                        $submittedDate = $dateObj->format('Y-m-d');
                    } else {
                        throw new Exception("Invalid date format. Expected DD-MM-YYYY, got: " . $submittedDate);
                    }
                } else {
                    $submittedDate = date('Y-m-d'); // Use current date if no date provided
                }

                // Get CustomerID from CustomerUniqueID
                $customerStmt->execute([$customerUniqueID]);
                $customerResult = $customerStmt->fetch(PDO::FETCH_ASSOC);

                if (!$customerResult) {
                    throw new Exception("Customer not found with UniqueID: " . $customerUniqueID);
                }

                $customerID = $customerResult['CustomerID'];

                // Insert payment record
                $paymentStmt->execute([$customerID, $schemeID, $installmentID, $amount, $paymentStatus, $submittedDate, $today, $screenshotURL, $verifierRemark, $payerRemark]);
                $successCount++;
            } catch (Exception $e) {
                $errorCount++;
                $errorMessage = "Error in row $row: " . $e->getMessage() . "\n";
                $errorMessage .= "Data: CustomerUniqueID: $customerUniqueID, SubmittedDate: $submittedDate, VerifiedAt: $today\n";
                $errorMessage .= "----------------------------------------\n";
                fwrite($logFile, $errorMessage);
                echo "Error processing row $row: " . $e->getMessage() . "<br>";
            }
        }

        fclose($logFile);

        if ($errorCount > 0) {
            echo "Import completed with some errors.<br>";
            echo "Successfully imported: $successCount records<br>";
            echo "Failed records: $errorCount<br>";
            echo "Failed records have been logged to: <a href='$logFileName' target='_blank'>$logFileName</a><br>";
            echo "Log file path: " . realpath($logFileName);
        } else {
            echo "Import completed successfully. All $successCount records were imported.";
        }
    } catch (Exception $e) {
        echo "Error processing file: " . $e->getMessage();
        if (isset($logFile) && $logFile !== false) {
            fclose($logFile);
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Import Payments from Excel/CSV</title>
    <style>
        .format-info {
            margin: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .error-message {
            color: red;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid red;
            background-color: #ffebee;
        }
    </style>
</head>

<body>
    <h2>Import Payments from Excel/CSV</h2>

    <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="format-info">
        <h3>Expected File Format</h3>
        <p>Your Excel/CSV file should have the following columns in order:</p>
        <table>
            <tr>
                <th>Column</th>
                <th>Description</th>
                <th>Required</th>
            </tr>
            <tr>
                <td>A</td>
                <td>CustomerUniqueID</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>B</td>
                <td>SubmittedDate (DD-MM-YYYY)</td>
                <td>No</td>
            </tr>
        </table>
        <p><strong>Note:</strong> The first row should contain headers. Data should start from the second row.</p>
        <p><strong>Additional Information:</strong></p>
        <ul>
            <li>SchemeID is set to: 5</li>
            <li>InstallmentID is set to: 22</li>
            <li>Amount is set to: 1000</li>
            <li>Payment Status is set to: Verified</li>
            <li>If no date is provided, current date will be used for SubmittedAt</li>
            <li>VerifiedAt will be set to today's date</li>
            <li>Screenshot URL is set to: payments/fromExcel.png</li>
            <li>Verifier Remark is set to: Verified via Excel automation</li>
            <li>Payer Remark is set to: Payment added from Excel automation</li>
        </ul>
    </div>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="excelFile" accept=".xlsx,.xls,.csv" required>
        <button type="submit">Import</button>
    </form>
</body>

</html>