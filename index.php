<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golden Excel - Import Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .dashboard-card {
            transition: transform 0.2s;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }

        .header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 1rem 0;
            margin-top: 2rem;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>

<body>
    <div class="header text-center">
        <div class="container">
            <h1 class="display-4">Golden Excel</h1>
            <p class="lead">Data Import Dashboard</p>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 mb-4">
                <div class="card dashboard-card h-100">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3 class="card-title">Import Promoters</h3>
                        <p class="card-text">Import promoter data from Excel or CSV files. Includes validation and error logging.</p>
                        <a href="import_promoters.php" class="btn btn-primary">Go to Promoter Import</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card dashboard-card h-100">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="card-title">Import Customers</h3>
                        <p class="card-text">Import customer data from Excel or CSV files. Includes validation and error logging.</p>
                        <a href="import_customers.php" class="btn btn-primary">Go to Customer Import</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Instructions</h4>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">1. Download the sample templates to understand the required format</li>
                            <li class="list-group-item">2. Prepare your data according to the template format</li>
                            <li class="list-group-item">3. Use the import pages to upload your data</li>
                            <li class="list-group-item">4. Check the logs for any errors and fix them accordingly</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer text-center">
        <div class="container">
            <p class="mb-0">Golden Excel &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>

</html>