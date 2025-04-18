<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "goldendream";

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
    $logFileName = $logsDir . '/failed_promoter_imports_' . date('Y-m-d_H-i-s') . '.txt';

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
    fwrite($logFile, "Failed Promoter Records Log - " . date('Y-m-d H:i:s') . "\n");
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

        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO Promoters (PromoterUniqueID, Name, Contact, Email, PasswordHash, Address, ProfileImageURL, BankAccountName, BankAccountNumber, IFSCCode, BankName, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Start from row 2 to skip header
        for ($row = 2; $row <= $highestRow; $row++) {
            $promoterUniqueID = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
            $name = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
            $contact = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
            $email = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
            $password = $worksheet->getCellByColumnAndRow(5, $row)->getValue();
            $address = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
            $profileImageURL = $worksheet->getCellByColumnAndRow(7, $row)->getValue();
            $bankAccountName = $worksheet->getCellByColumnAndRow(8, $row)->getValue();
            $bankAccountNumber = $worksheet->getCellByColumnAndRow(9, $row)->getValue();
            $ifscCode = $worksheet->getCellByColumnAndRow(10, $row)->getValue();
            $bankName = $worksheet->getCellByColumnAndRow(11, $row)->getValue();

            // Hash the password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt->execute([$promoterUniqueID, $name, $contact, $email, $passwordHash, $address, $profileImageURL, $bankAccountName, $bankAccountNumber, $ifscCode, $bankName, 'Active']);
                $successCount++;
            } catch (PDOException $e) {
                $errorCount++;
                $errorMessage = "Error in row $row: " . $e->getMessage() . "\n";
                $errorMessage .= "Data: PromoterUniqueID: $promoterUniqueID, Name: $name, Contact: $contact, Email: $email\n";
                $errorMessage .= "----------------------------------------\n";
                fwrite($logFile, $errorMessage);
                echo "Error inserting row $row: " . $e->getMessage() . "<br>";
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
    <title>Import Promoters from Excel/CSV</title>
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
    <h2>Import Promoters from Excel/CSV</h2>

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
                <td>PromoterUniqueID</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>B</td>
                <td>Name</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>C</td>
                <td>Contact</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>D</td>
                <td>Email</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>E</td>
                <td>Password</td>
                <td>Yes</td>
            </tr>
            <tr>
                <td>F</td>
                <td>Address</td>
                <td>No</td>
            </tr>
            <tr>
                <td>G</td>
                <td>ProfileImageURL</td>
                <td>No</td>
            </tr>
            <tr>
                <td>H</td>
                <td>BankAccountName</td>
                <td>No</td>
            </tr>
            <tr>
                <td>I</td>
                <td>BankAccountNumber</td>
                <td>No</td>
            </tr>
            <tr>
                <td>J</td>
                <td>IFSCCode</td>
                <td>No</td>
            </tr>
            <tr>
                <td>K</td>
                <td>BankName</td>
                <td>No</td>
            </tr>
        </table>
        <p><strong>Note:</strong> The first row should contain headers. Data should start from the second row.</p>
    </div>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="excelFile" accept=".xlsx,.xls,.csv" required>
        <button type="submit">Import</button>
    </form>
</body>

</html>