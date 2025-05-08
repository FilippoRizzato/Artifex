-- Database: YourDatabaseName

-- Table: Users
CREATE TABLE Users (
                       UserID INT PRIMARY KEY AUTO_INCREMENT,
                       Username VARCHAR(50) UNIQUE NOT NULL,
                       Email VARCHAR(100) UNIQUE NOT NULL,
                       PasswordHash VARCHAR(255) NOT NULL,
                       FirstName VARCHAR(50),
                       LastName VARCHAR(50),
                       IsAdmin BOOLEAN DEFAULT FALSE,
                       RegistrationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: Events
CREATE TABLE Events (
                        EventID INT PRIMARY KEY AUTO_INCREMENT,
                        Name VARCHAR(255) NOT NULL,
                        Description TEXT,
                        Location VARCHAR(255),
                        GuideID INT,
                        Capacity INT,
                        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (GuideID) REFERENCES Guides(GuideID)
);

-- Table: EventDates
CREATE TABLE EventDates (
                            DateID INT PRIMARY KEY AUTO_INCREMENT,
                            EventID INT NOT NULL,
                            EventDateTime DATETIME NOT NULL,
                            Price DECIMAL(10, 2) NOT NULL,
                            AvailableSeats INT,
                            FOREIGN KEY (EventID) REFERENCES Events(EventID)
);

-- Table: Guides
CREATE TABLE Guides (
                        GuideID INT PRIMARY KEY AUTO_INCREMENT,
                        FirstName VARCHAR(50) NOT NULL,
                        LastName VARCHAR(50) NOT NULL,
                        Bio TEXT
);

-- Table: Bookings
CREATE TABLE Bookings (
                          BookingID INT PRIMARY KEY AUTO_INCREMENT,
                          UserID INT NOT NULL,
                          BookingDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          TotalPrice DECIMAL(10, 2) NOT NULL,
                          PaymentStatus VARCHAR(20) DEFAULT 'Pending',
                          FOREIGN KEY (UserID) REFERENCES Users(UserID)
);

-- Table: BookingDetails (Many-to-many relationship between Bookings and EventDates)
CREATE TABLE BookingDetails (
                                BookingDetailID INT PRIMARY KEY AUTO_INCREMENT,
                                BookingID INT NOT NULL,
                                DateID INT NOT NULL,
                                Quantity INT NOT NULL DEFAULT 1,
                                Subtotal DECIMAL(10, 2) NOT NULL,
                                FOREIGN KEY (BookingID) REFERENCES Bookings(BookingID),
                                FOREIGN KEY (DateID) REFERENCES EventDates(DateID),
                                UNIQUE KEY (BookingID, DateID) -- Ensure one event date per booking
);

-- Sample Admin User (to be inserted directly into the database)
INSERT INTO Users (Username, Email, PasswordHash, FirstName, LastName, IsAdmin)
VALUES
    ('admin', 'pippo@example.com', 'pippo', 'Admin', 'User', TRUE);


-- Table to store generated QR codes (optional, can be generated on the fly)
CREATE TABLE QRCodes (
                         QRCodeID INT PRIMARY KEY AUTO_INCREMENT,
                         Data TEXT NOT NULL,
                         FilePath VARCHAR(255) UNIQUE
);

-- Table to associate QR codes with booking details
CREATE TABLE BookingDetailQRCodes (
                                      BookingDetailQRCodeID INT PRIMARY KEY AUTO_INCREMENT,
                                      BookingDetailID INT NOT NULL,
                                      QRCodeID INT NOT NULL,
                                      FOREIGN KEY (BookingDetailID) REFERENCES BookingDetails(BookingDetailID),
                                      FOREIGN KEY (QRCodeID) REFERENCES QRCodes(QRCodeID),
                                      UNIQUE KEY (BookingDetailID)
);
CREATE TABLE Admins (
                        AdminID INT PRIMARY KEY AUTO_INCREMENT,
                        Username VARCHAR(50) UNIQUE NOT NULL,
                        Password VARCHAR(255) NOT NULL, -- Non hashata per gli admin
                        FirstName VARCHAR(50),
                        LastName VARCHAR(50),
                        Email VARCHAR(100) UNIQUE
);
INSERT INTO Admins (Username, Password, FirstName, LastName, Email) VALUES
                                                                        ('johndoe_admin', 'Admin123!', 'John', 'Doe', 'john.doe@example.com'),
                                                                        ('janesmith_sysadmin', 'SecurePass42', 'Jane', 'Smith', 'jane.smith@example.org'),
                                                                        ('peterparker_webmaster', 'WebMaster2024', 'Peter', 'Parker', 'peter.parker@mycompany.net');


INSERT INTO Guides (FirstName, LastName, Bio) VALUES
                                                  ('Alice', 'Rossi', 'Guida esperta di storia dell\'arte, specializzata nel Rinascimento italiano.'),
                                                  ('Marco', 'Verdi', 'Appassionato di natura e trekking, con una profonda conoscenza delle Alpi.'),
                                                  ('Chiara', 'Bianchi', 'Archeologa e guida turistica, esperta in siti antichi del Mediterraneo.'),
                                                  ('Luca', 'Neri', 'Guida enogastronomica, con un\'ampia conoscenza dei vini e dei prodotti tipici locali.');