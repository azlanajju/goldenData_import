
-- 1. Admins Table (Parent Table)
CREATE TABLE Admins (
    AdminID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Email VARCHAR(255) UNIQUE NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    Role ENUM('SuperAdmin', 'Verifier') DEFAULT 'Verifier',
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Schemes Table (Stores types of schemes)
CREATE TABLE Schemes (
    SchemeID INT AUTO_INCREMENT PRIMARY KEY,
    SchemeName VARCHAR(255) UNIQUE NOT NULL,
    Description TEXT,
    MonthlyPayment DECIMAL(10,2) NOT NULL,
    TotalPayments INT NOT NULL DEFAULT 1,
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE Installments (
    InstallmentID INT AUTO_INCREMENT PRIMARY KEY,
    SchemeID INT NOT NULL,
    InstallmentName VARCHAR(100), 
    InstallmentNumber INT NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    DrawDate DATE NOT NULL,
    Benefits TEXT,
    ImageURL VARCHAR(255), 
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SchemeID) REFERENCES Schemes(SchemeID) ON DELETE CASCADE
);


-- 3. Promoters Table (Parent Table)
CREATE TABLE Promoters (
    PromoterID INT AUTO_INCREMENT PRIMARY KEY,
    PromoterUniqueID VARCHAR(50) UNIQUE NOT NULL,
    CustomerID INT UNIQUE,
    Name VARCHAR(255) NOT NULL,
    Contact VARCHAR(50) UNIQUE NOT NULL,
    Email VARCHAR(255) UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Address TEXT,
    ProfileImageURL VARCHAR(255),
    BankAccountName VARCHAR(255),
    BankAccountNumber VARCHAR(50),
    IFSCCode VARCHAR(20),
    BankName VARCHAR(255),
    PaymentCodeCounter INT DEFAULT 0,
    ParentPromoterID  INT DEFAULT NULL,
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- 4. Customers Table (Child of Promoters)
CREATE TABLE Customers (
    CustomerID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerUniqueID VARCHAR(50) UNIQUE NOT NULL,
    Name VARCHAR(255) NOT NULL,
    Contact VARCHAR(50) UNIQUE NOT NULL,
    Email VARCHAR(255) UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Address TEXT,
    ProfileImageURL VARCHAR(255),
    BankAccountName VARCHAR(255),
    BankAccountNumber VARCHAR(50),
    IFSCCode VARCHAR(20),
    BankName VARCHAR(255),
    PromoterID INT,
    ReferredBy  VARCHAR(50), 
    Status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (PromoterID) REFERENCES Promoters(PromoterID) ON DELETE SET NULL
);

-- 5. Payments Table (Child of Customers and Promoters)
CREATE TABLE Payments (
    PaymentID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT,
    PromoterID INT,
    AdminID INT DEFAULT NULL,
    SchemeID INT,
    InstallmentID INT,

    Amount DECIMAL(10,2) NOT NULL,
    PaymentCodeValue INT DEFAULT 0,
    ScreenshotURL VARCHAR(255) NOT NULL,
    Status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    VerifiedAt TIMESTAMP NULL,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE CASCADE,
    FOREIGN KEY (PromoterID) REFERENCES Promoters(PromoterID) ON DELETE CASCADE,
    FOREIGN KEY (AdminID) REFERENCES Admins(AdminID) ON DELETE SET NULL,
    FOREIGN KEY (SchemeID) REFERENCES Schemes(SchemeID) ON DELETE SET NULL,
        FOREIGN KEY (InstallmentID) REFERENCES Installments(InstallmentID) ON DELETE SET NULL

);


-- 6. Payment Code Transactions (Child of Payments)
CREATE TABLE PaymentCodeTransactions (
    TransactionID INT AUTO_INCREMENT PRIMARY KEY,
    PromoterID INT,
    AdminID INT DEFAULT NULL,
    PaymentCodeChange INT NOT NULL,
    TransactionType ENUM('Addition', 'Correction', 'Deduction') DEFAULT 'Addition',
    Remarks TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (PromoterID) REFERENCES Promoters(PromoterID) ON DELETE CASCADE,
    FOREIGN KEY (AdminID) REFERENCES Admins(AdminID) ON DELETE SET NULL
);

-- 7. Payment Codes Per Month Table (For Promoters)
CREATE TABLE PaymentCodesPerMonth (
    RecordID INT AUTO_INCREMENT PRIMARY KEY,
    PromoterID INT,
    MonthYear DATE NOT NULL,
    PaymentCodes INT DEFAULT 0,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (PromoterID) REFERENCES Promoters(PromoterID) ON DELETE CASCADE
);

-- 8. Notifications Table (Linked to all users)
CREATE TABLE Notifications (
    NotificationID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    UserType ENUM('Customer', 'Promoter', 'Admin') NOT NULL,
    Message TEXT NOT NULL,
    IsRead BOOLEAN DEFAULT FALSE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 9. Activity Logs Table (Logs actions from Admins and Promoters)
CREATE TABLE ActivityLogs (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    UserType ENUM('Admin', 'Promoter') NOT NULL,
    Action TEXT NOT NULL,
    IPAddress VARCHAR(50),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 11. Subscriptions Table (Tracks customer subscriptions)
CREATE TABLE Subscriptions (
    SubscriptionID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL,
    SchemeID INT NOT NULL,
    StartDate DATE NOT NULL,
    EndDate DATE NOT NULL,
    RenewalStatus ENUM('Active', 'Expired', 'Cancelled') DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE CASCADE,
    FOREIGN KEY (SchemeID) REFERENCES Schemes(SchemeID) ON DELETE CASCADE
);

-- 10. Payment QR Table (Stores payment details for customers)
CREATE TABLE PaymentQR (
    QRID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT,
    UPIQRImageURL VARCHAR(255) NOT NULL,
    BankAccountName VARCHAR(255) NOT NULL,
    BankAccountNumber VARCHAR(50) NOT NULL,
    IFSCCode VARCHAR(20) NOT NULL,
    BankName VARCHAR(255) NOT NULL,
    BankBranch VARCHAR(255) NOT NULL,
    BankAddress TEXT NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE CASCADE
);


CREATE TABLE Withdrawals (
    WithdrawalID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    UserType ENUM('Customer', 'Promoter') NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    RequestedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ProcessedAt TIMESTAMP NULL,
    AdminID INT DEFAULT NULL,
    Remarks TEXT
);

CREATE TABLE Winners (
    WinnerID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    UserType ENUM('Customer', 'Promoter') NOT NULL,
    PrizeType ENUM('Surprise Prize', 'Bumper Prize', 'Gift Hamper', 'Education Scholarship', 'Other') NOT NULL,
    WinningDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('Pending', 'Claimed', 'Expired') DEFAULT 'Pending',
    AdminID INT DEFAULT NULL,
    Remarks TEXT,
    FOREIGN KEY (AdminID) REFERENCES Admins(AdminID) ON DELETE SET NULL
);

CREATE TABLE Teams (
    TeamID INT AUTO_INCREMENT PRIMARY KEY,
    TeamUniqueID VARCHAR(50) UNIQUE NOT NULL,
    TeamName VARCHAR(255) UNIQUE NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 12. KYC Table (Stores KYC details for Customers and Promoters)
CREATE TABLE KYC (
    KYCID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    UserType ENUM('Customer', 'Promoter') NOT NULL,
    AadharNumber VARCHAR(20) UNIQUE NOT NULL,
    PANNumber VARCHAR(10) UNIQUE,
    IDProofType ENUM('Aadhar', 'PAN', 'Voter ID', 'Passport', 'Driving License') NOT NULL,
    IDProofImageURL VARCHAR(255) NOT NULL,
    AddressProofType ENUM('Aadhar', 'Voter ID', 'Utility Bill', 'Bank Statement', 'Ration Card') NOT NULL,
    AddressProofImageURL VARCHAR(255) NOT NULL,
    Status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
    SubmittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    VerifiedAt TIMESTAMP NULL,
    AdminID INT DEFAULT NULL,
    Remarks TEXT,
    KYCStatus VARCHAR(50),
    FOREIGN KEY (UserID) REFERENCES Customers(CustomerID) ON DELETE CASCADE,
    FOREIGN KEY (AdminID) REFERENCES Admins(AdminID) ON DELETE SET NULL
);

CREATE TABLE Balances (
    BalanceID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    UserType ENUM('Customer', 'Promoter') NOT NULL,
    SchemeID INT NOT NULL,
    BalanceAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    LastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Customers(CustomerID) ON DELETE CASCADE,
    FOREIGN KEY (SchemeID) REFERENCES Schemes(SchemeID) ON DELETE CASCADE
);
