-- Library Management System Database Schema
-- Created for Group 6 Project

CREATE DATABASE IF NOT EXISTS library_management;
USE library_management;

-- Table: users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    user_type ENUM('student', 'faculty', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: books
CREATE TABLE books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) UNIQUE,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(100) NOT NULL,
    publisher VARCHAR(100),
    publication_year YEAR,
    category VARCHAR(50),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    shelf_location VARCHAR(50),
    description TEXT,
    cover_image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: book_issues
CREATE TABLE book_issues (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    issue_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    due_date DATETIME NOT NULL,
    return_date DATETIME NULL,
    status ENUM('issued', 'returned', 'overdue') DEFAULT 'issued',
    fine_amount DECIMAL(10, 2) DEFAULT 0.00,
    notes TEXT,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table: categories
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, user_type) 
VALUES ('admin', 'admin@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert sample categories
INSERT INTO categories (category_name, description) VALUES
('Fiction', 'Fictional literature and novels'),
('Non-Fiction', 'Educational and factual books'),
('Science', 'Science and technology books'),
('History', 'Historical books and references'),
('Technology', 'Computer science and IT books'),
('Mathematics', 'Mathematics and statistics'),
('Literature', 'Classic and modern literature');

-- Insert sample books
INSERT INTO books (isbn, title, author, publisher, publication_year, category, total_copies, available_copies, shelf_location) VALUES
('978-0-13-468599-1', 'Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', 2009, 'Technology', 5, 5, 'A-101'),
('978-0-13-235088-4', 'Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, 'Technology', 3, 3, 'A-102'),
('978-0-7432-7356-5', 'To Kill a Mockingbird', 'Harper Lee', 'HarperCollins', 1960, 'Fiction', 4, 4, 'B-201'),
('978-0-545-01022-1', 'Harry Potter and the Deathly Hallows', 'J.K. Rowling', 'Scholastic', 2007, 'Fiction', 6, 6, 'B-202'),
('978-0-393-91257-8', 'Sapiens', 'Yuval Noah Harari', 'Harper', 2015, 'History', 3, 3, 'C-301');

-- Create indexes for better performance
CREATE INDEX idx_book_title ON books(title);
CREATE INDEX idx_book_author ON books(author);
CREATE INDEX idx_issue_status ON book_issues(status);
CREATE INDEX idx_user_email ON users(email);