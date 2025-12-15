<?php
/**
 * COMPLETE HEALTHCARE WEBSITE - SINGLE FILE
 * Save as: index.php in C:\xampp\htdocs\healthcare\
 */

// Start session
session_start();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'healthcare_db';

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    // Create services table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50),
            price DECIMAL(10,2),
            category VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create reviews table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_name VARCHAR(100) NOT NULL,
            rating INT(1) CHECK (rating BETWEEN 1 AND 5),
            comment TEXT,
            date_posted DATE,
            status ENUM('pending', 'approved') DEFAULT 'pending'
        )
    ");
    
    // Create appointments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            service_id INT,
            appointment_date DATE,
            appointment_time TIME,
            message TEXT,
            status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
        )
    ");
    
    // Create admin table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create contacts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            subject VARCHAR(200),
            message TEXT,
            status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default admin if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)")
            ->execute(['admin', $hashed_password]);
    }
    
    // Insert sample services if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        $services = [
            ['General Consultation', 'Complete health checkup and consultation with our expert doctors', 'fa-stethoscope', 50.00, 'Consultation'],
            ['Dental Care', 'Professional dental cleaning, filling, and oral health checkup', 'fa-tooth', 80.00, 'Dental'],
            ['Cardiology', 'Heart health evaluation and cardiovascular screening', 'fa-heart', 120.00, 'Specialist'],
            ['Pediatrics', 'Child healthcare and immunization services', 'fa-child', 60.00, 'Pediatrics'],
            ['Dermatology', 'Skin treatment and cosmetic procedures', 'fa-allergies', 90.00, 'Specialist'],
            ['Eye Care', 'Vision testing and eye disease treatment', 'fa-eye', 70.00, 'Specialist'],
            ['Orthopedics', 'Bone and joint treatment and rehabilitation', 'fa-bone', 110.00, 'Specialist'],
            ['Emergency Care', '24/7 emergency medical services', 'fa-ambulance', 150.00, 'Emergency'],
            ['Lab Tests', 'Complete blood work and diagnostic tests', 'fa-flask', 40.00, 'Diagnostics'],
            ['Physiotherapy', 'Physical therapy and rehabilitation services', 'fa-hands-helping', 65.00, 'Therapy'],
            ['Mental Health', 'Counseling and psychiatric consultations', 'fa-brain', 85.00, 'Mental Health'],
            ['Vaccination', 'All types of vaccinations and immunizations', 'fa-syringe', 45.00, 'Preventive']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO services (title, description, icon, price, category) VALUES (?, ?, ?, ?, ?)");
        foreach ($services as $service) {
            $stmt->execute($service);
        }
    }
    
    // Insert sample reviews if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM reviews");
    if ($stmt->fetchColumn() == 0) {
        $reviews = [
            ['John Smith', 5, 'Excellent service! The doctors were very professional and caring.', '2024-01-15', 'approved'],
            ['Sarah Johnson', 4, 'Clean facility and friendly staff. Waiting time was minimal.', '2024-01-20', 'approved'],
            ['Michael Brown', 5, 'Best healthcare experience. Highly recommended to everyone!', '2024-02-05', 'approved'],
            ['Emily Davis', 4, 'Very efficient service. Will definitely come back.', '2024-02-12', 'approved'],
            ['Robert Wilson', 5, 'Emergency care saved my life. Thank you to the medical team!', '2024-02-18', 'approved'],
            ['Jennifer Lee', 4, 'Professional staff and good facilities. Satisfied with the treatment.', '2024-02-25', 'approved']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO reviews (patient_name, rating, comment, date_posted, status) VALUES (?, ?, ?, ?, ?)");
        foreach ($reviews as $review) {
            $stmt->execute($review);
        }
    }
    
} catch(PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Helper Functions
function getServices() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM services ORDER BY category, title");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getApprovedReviews($limit = 6) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE status = 'approved' ORDER BY date_posted DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getServiceCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT DISTINCT category FROM services ORDER BY category");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getStats() {
    global $pdo;
    $stats = [];
    
    $stats['total_services'] = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $stats['total_reviews'] = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    $stats['total_appointments'] = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    $stats['pending_reviews'] = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
    $stats['pending_appointments'] = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
    
    return $stats;
}

// Process Form Submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'book_appointment':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO appointments 
                        (patient_name, email, phone, service_id, appointment_date, appointment_time, message) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $success = $stmt->execute([
                        $_POST['name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['service'],
                        $_POST['date'],
                        $_POST['time'],
                        $_POST['message']
                    ]);
                    
                    if ($success) {
                        $message = 'Appointment booked successfully! We will contact you soon.';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to book appointment. Please try again.';
                        $message_type = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'error';
                }
                break;
                
            case 'submit_review':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO reviews 
                        (patient_name, rating, comment, date_posted) 
                        VALUES (?, ?, ?, CURDATE())
                    ");
                    
                    $success = $stmt->execute([
                        $_POST['name'],
                        $_POST['rating'],
                        $_POST['comment']
                    ]);
                    
                    if ($success) {
                        $message = 'Review submitted successfully! Thank you for your feedback.';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to submit review. Please try again.';
                        $message_type = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'error';
                }
                break;
                
            case 'contact_form':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO contacts 
                        (name, email, subject, message) 
                        VALUES (?, ?, ?, ?)
                    ");
                    
                    $success = $stmt->execute([
                        $_POST['contact_name'],
                        $_POST['contact_email'],
                        $_POST['contact_subject'],
                        $_POST['contact_message']
                    ]);
                    
                    if ($success) {
                        $message = 'Message sent successfully! We will get back to you soon.';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to send message. Please try again.';
                        $message_type = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'error';
                }
                break;
                
            case 'admin_login':
                $username = $_POST['username'];
                $password = $_POST['password'];
                
                $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password_hash'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_id'] = $admin['id'];
                    header('Location: ?page=admin');
                    exit();
                } else {
                    $message = 'Invalid username or password!';
                    $message_type = 'error';
                }
                break;
                
            case 'approve_review':
                if (isset($_SESSION['admin_logged_in'])) {
                    $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
                    $stmt->execute([$_POST['review_id']]);
                    $message = 'Review approved successfully!';
                    $message_type = 'success';
                }
                break;
                
            case 'delete_review':
                if (isset($_SESSION['admin_logged_in'])) {
                    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
                    $stmt->execute([$_POST['review_id']]);
                    $message = 'Review deleted successfully!';
                    $message_type = 'success';
                }
                break;
                
            case 'confirm_appointment':
                if (isset($_SESSION['admin_logged_in'])) {
                    $stmt = $pdo->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?");
                    $stmt->execute([$_POST['appointment_id']]);
                    $message = 'Appointment confirmed!';
                    $message_type = 'success';
                }
                break;
                
            case 'complete_appointment':
                if (isset($_SESSION['admin_logged_in'])) {
                    $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
                    $stmt->execute([$_POST['appointment_id']]);
                    $message = 'Appointment marked as completed!';
                    $message_type = 'success';
                }
                break;
                
            case 'cancel_appointment':
                if (isset($_SESSION['admin_logged_in'])) {
                    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
                    $stmt->execute([$_POST['appointment_id']]);
                    $message = 'Appointment cancelled!';
                    $message_type = 'success';
                }
                break;
        }
    }
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?page=home');
    exit();
}

// Determine current page
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php 
        if ($page == 'admin' && $is_admin) echo 'Admin Panel - MediCare';
        elseif ($page == 'services') echo 'Our Services - MediCare';
        elseif ($page == 'about') echo 'About Us - MediCare';
        elseif ($page == 'contact') echo 'Contact Us - MediCare';
        else echo 'MediCare Health Center';
        ?>
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* ========== CSS RESET & BASE ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2a9d8f;
            --primary-dark: #21867a;
            --secondary: #264653;
            --accent: #e76f51;
            --light: #f8f9fa;
            --dark: #2d3748;
            --gray: #718096;
            --light-gray: #edf2f7;
            --success: #38a169;
            --warning: #d69e2e;
            --danger: #e53e3e;
            --info: #3182ce;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 8px;
            --transition: all 0.3s ease;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: #f7fafc;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            line-height: 1.3;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ========== UTILITY CLASSES ========== */
        .text-center { text-align: center; }
        .text-primary { color: var(--primary); }
        .text-success { color: var(--success); }
        .text-warning { color: var(--warning); }
        .text-danger { color: var(--danger); }
        .bg-light { background-color: var(--light); }
        .bg-primary { background-color: var(--primary); color: white; }
        .mb-1 { margin-bottom: 0.5rem; }
        .mb-2 { margin-bottom: 1rem; }
        .mb-3 { margin-bottom: 1.5rem; }
        .mb-4 { margin-bottom: 2rem; }
        .mt-4 { margin-top: 2rem; }
        .p-3 { padding: 1.5rem; }
        .d-flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .align-center { align-items: center; }
        .gap-2 { gap: 1rem; }

        /* ========== NAVIGATION ========== */
        .navbar {
            background: white;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 1rem 0;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 2rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            margin: 0;
            padding: 0;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            padding: 0.5rem 0;
            position: relative;
            transition: var(--transition);
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--primary);
        }

        .nav-menu a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-admin {
            background: var(--secondary);
        }

        .btn-admin:hover {
            background: #1a252f;
        }

        /* ========== HERO SECTION ========== */
        .hero {
            background: linear-gradient(135deg, rgba(42, 157, 143, 0.9) 0%, rgba(38, 70, 83, 0.9) 100%), url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 6rem 0;
            text-align: center;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto 2.5rem;
            opacity: 0.9;
        }

        /* ========== SECTIONS ========== */
        section {
            padding: 5rem 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }

        .section-title p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
            font-size: 1.1rem;
        }

        /* ========== SERVICES ========== */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .service-card {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-top: 4px solid var(--primary);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .service-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .service-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .service-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--secondary);
        }

        .service-card p {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--light-gray);
            color: var(--secondary);
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        /* ========== REVIEWS ========== */
        .reviews-section {
            background: var(--light-gray);
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .review-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .rating {
            color: #fbbf24;
            margin-bottom: 1rem;
        }

        .rating i {
            margin-right: 2px;
        }

        .review-card p {
            font-style: italic;
            color: var(--dark);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .review-author {
            font-weight: 600;
            color: var(--secondary);
        }

        .review-date {
            color: var(--gray);
            font-size: 0.875rem;
        }

        /* ========== FORMS ========== */
        .form-section {
            background: white;
        }

        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--light);
            padding: 3rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Roboto', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        /* ========== MESSAGES ========== */
        .message {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .message.success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .message.error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        /* ========== ABOUT SECTION ========== */
        .about-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            align-items: center;
        }

        .about-text h2 {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 1.5rem;
        }

        .about-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .feature i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        /* ========== CONTACT INFO ========== */
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .contact-item i {
            font-size: 1.5rem;
            color: var(--primary);
            margin-top: 0.25rem;
        }

        .contact-details h4 {
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }

        .contact-details p {
            color: var(--gray);
        }

        /* ========== FOOTER ========== */
        .footer {
            background: var(--secondary);
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: white;
        }

        .footer-section p {
            opacity: 0.8;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-icons a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }

        .social-icons a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
            font-size: 0.9rem;
        }

        /* ========== ADMIN STYLES ========== */
        .admin-section {
            min-height: 100vh;
            background: #f7fafc;
        }

        .admin-nav {
            background: var(--secondary);
            padding: 1rem 0;
            color: white;
            margin-bottom: 2rem;
        }

        .admin-content {
            padding: 2rem 0;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .admin-table {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: var(--light-gray);
            font-weight: 600;
            color: var(--secondary);
        }

        tr:hover {
            background: #f7fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .action-btn {
            padding: 0.25rem 0.75rem;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            transition: var(--transition);
        }

        .action-btn:hover {
            opacity: 0.9;
        }

        .btn-approve { background: var(--success); }
        .btn-delete { background: var(--danger); }
        .btn-confirm { background: var(--info); }
        .btn-complete { background: var(--success); }
        .btn-cancel { background: var(--danger); }

        /* ========== RESPONSIVE DESIGN ========== */
        @media (max-width: 768px) {
            .navbar .container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-menu {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            section {
                padding: 3rem 0;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .services-grid,
            .reviews-grid {
                grid-template-columns: 1fr;
            }

            .form-container {
                padding: 2rem;
            }

            .admin-stats {
                grid-template-columns: 1fr 1fr;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 480px) {
            .admin-stats {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }
        }

        /* ========== ANIMATIONS ========== */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        /* ========== LOADING ========== */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php if ($page == 'admin' && !$is_admin): ?>
        <!-- Admin Login Page -->
        <div class="form-section">
            <div class="container">
                <div class="section-title">
                    <h2><i class="fas fa-lock"></i> Admin Login</h2>
                    <p>Access the administrative dashboard</p>
                </div>
                <div class="form-container">
                    <?php if ($message): ?>
                        <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="admin_login">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-user"></i> Username</label>
                            <input type="text" name="username" class="form-control" required placeholder="Enter username">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-key"></i> Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Enter password">
                        </div>
                        <button type="submit" class="btn" style="width: 100%;">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                    <div class="text-center mt-4">
                        <a href="?page=home" class="text-primary">
                            <i class="fas fa-arrow-left"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($page == 'admin' && $is_admin): ?>
        <!-- Admin Dashboard -->
        <div class="admin-section">
            <nav class="admin-nav">
                <div class="container d-flex justify-between align-center">
                    <div class="logo" style="color: white;">
                        <i class="fas fa-cogs"></i> Admin Panel
                    </div>
                    <div class="d-flex align-center gap-2">
                        <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
                        <a href="?page=home" class="btn btn-outline" style="color: white; border-color: white;">View Site</a>
                        <a href="?logout=1" class="btn" style="background: var(--danger);">Logout</a>
                    </div>
                </div>
            </nav>

            <div class="admin-content">
                <div class="container">
                    <?php if ($message): ?>
                        <div class="message <?php echo $message_type; ?> mb-4"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <h2 class="mb-4">Dashboard Overview</h2>
                    
                    <!-- Statistics -->
                    <div class="admin-stats">
                        <?php $stats = getStats(); ?>
                        <div class="stat-card fade-in">
                            <h3>Total Services</h3>
                            <div class="stat-number"><?php echo $stats['total_services']; ?></div>
                        </div>
                        <div class="stat-card fade-in">
                            <h3>Total Reviews</h3>
                            <div class="stat-number"><?php echo $stats['total_reviews']; ?></div>
                        </div>
                        <div class="stat-card fade-in">
                            <h3>Appointments</h3>
                            <div class="stat-number"><?php echo $stats['total_appointments']; ?></div>
                        </div>
                        <div class="stat-card fade-in">
                            <h3>Pending Reviews</h3>
                            <div class="stat-number"><?php echo $stats['pending_reviews']; ?></div>
                        </div>
                    </div>

                    <!-- Pending Reviews -->
                    <h3 class="mb-3">Pending Reviews</h3>
                    <div class="admin-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient Name</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT * FROM reviews WHERE status = 'pending' ORDER BY date_posted DESC");
                                while($review = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td>#<?php echo $review['id']; ?></td>
                                    <td><?php echo htmlspecialchars($review['patient_name']); ?></td>
                                    <td>
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-gray'; ?>"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td><?php echo substr(htmlspecialchars($review['comment']), 0, 50); ?>...</td>
                                    <td><?php echo $review['date_posted']; ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="approve_review">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="action-btn btn-approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_review">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="action-btn btn-delete" onclick="return confirm('Are you sure?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Recent Appointments -->
                    <h3 class="mb-3">Recent Appointments</h3>
                    <div class="admin-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("
                                    SELECT a.*, s.title as service_name 
                                    FROM appointments a 
                                    LEFT JOIN services s ON a.service_id = s.id 
                                    ORDER BY a.created_at DESC 
                                    LIMIT 15
                                ");
                                while($appointment = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td>#<?php echo $appointment['id']; ?></td>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($appointment['patient_name']); ?></strong></div>
                                        <small><?php echo htmlspecialchars($appointment['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['service_name'] ?? 'Not specified'); ?></td>
                                    <td>
                                        <div><?php echo $appointment['appointment_date']; ?></div>
                                        <small><?php echo $appointment['appointment_time']; ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($appointment['status'] == 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="confirm_appointment">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" class="action-btn btn-confirm">
                                                    <i class="fas fa-check-circle"></i> Confirm
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($appointment['status'] == 'confirmed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="complete_appointment">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" class="action-btn btn-complete">
                                                    <i class="fas fa-check-double"></i> Complete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($appointment['status'], ['pending', 'confirmed'])): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="cancel_appointment">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <button type="submit" class="action-btn btn-cancel" onclick="return confirm('Cancel this appointment?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Main Website -->
        <!-- Navigation -->
        <nav class="navbar">
            <div class="container">
                <a href="?page=home" class="logo">
                    <i class="fas fa-heartbeat"></i> MediCare
                </a>
                <ul class="nav-menu">
                    <li><a href="?page=home" class="<?php echo $page == 'home' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="?page=services" class="<?php echo $page == 'services' ? 'active' : ''; ?>">Services</a></li>
                    <li><a href="?page=about" class="<?php echo $page == 'about' ? 'active' : ''; ?>">About</a></li>
                    <li><a href="?page=contact" class="<?php echo $page == 'contact' ? 'active' : ''; ?>">Contact</a></li>
                    <li><a href="?page=admin" class="btn btn-admin">
                        <i class="fas fa-user-shield"></i> Admin
                    </a></li>
                </ul>
            </div>
        </nav>

        <!-- Display Messages -->
        <?php if ($message): ?>
            <div class="container mt-3">
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($page == 'home' || $page == ''): ?>
            <!-- HOME PAGE -->
            <?php
            $services = getServices();
            $reviews = getApprovedReviews();
            $categories = getServiceCategories();
            ?>
            
            <!-- Hero Section -->
            <section class="hero">
                <div class="container">
                    <h1>Your Health, Our Priority</h1>
                    <p>Experience world-class healthcare services with our team of expert medical professionals. We provide personalized care for you and your family.</p>
                    <a href="#appointment" class="btn" style="font-size: 1.1rem; padding: 1rem 2rem;">
                        <i class="fas fa-calendar-check"></i> Book Appointment
                    </a>
                </div>
            </section>

            <!-- Services Preview -->
            <section id="services-preview">
                <div class="container">
                    <div class="section-title">
                        <h2>Our Medical Services</h2>
                        <p>Comprehensive healthcare solutions for all your needs</p>
                    </div>
                    <div class="services-grid">
                        <?php foreach(array_slice($services, 0, 6) as $service): ?>
                        <div class="service-card fade-in">
                            <div class="service-icon">
                                <i class="fas <?php echo htmlspecialchars($service['icon']); ?>"></i>
                            </div>
                            <span class="category-badge"><?php echo htmlspecialchars($service['category']); ?></span>
                            <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                            <p><?php echo htmlspecialchars($service['description']); ?></p>
                            <div class="price">$<?php echo number_format($service['price'], 2); ?></div>
                            <a href="?page=services#service-<?php echo $service['id']; ?>" class="btn btn-outline">Learn More</a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="?page=services" class="btn">
                            <i class="fas fa-arrow-right"></i> View All Services
                        </a>
                    </div>
                </div>
            </section>

            <!-- About Preview -->
            <section class="bg-light">
                <div class="container">
                    <div class="about-content">
                        <div class="about-text">
                            <h2>Why Choose MediCare?</h2>
                            <p>With over 20 years of experience, we provide exceptional medical care using state-of-the-art technology and compassionate service.</p>
                            <p>Our team of certified doctors and healthcare professionals are dedicated to your well-being.</p>
                            <div class="about-features">
                                <div class="feature">
                                    <i class="fas fa-user-md"></i>
                                    <h4>Expert Doctors</h4>
                                    <p>Certified specialists</p>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-clock"></i>
                                    <h4>24/7 Service</h4>
                                    <p>Always available</p>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-award"></i>
                                    <h4>Quality Care</h4>
                                    <p>Patient-centered approach</p>
                                </div>
                            </div>
                        </div>
                        <div class="about-image">
                            <img src="https://images.unsplash.com/photo-1582750433449-648ed127bb54?ixlib=rb-4.0.3&auto=format&fit=crop&w=700&q=80" alt="Medical Team" style="width: 100%; border-radius: var(--radius);">
                        </div>
                    </div>
                </div>
            </section>

            <!-- Reviews -->
            <section class="reviews-section">
                <div class="container">
                    <div class="section-title">
                        <h2>Patient Testimonials</h2>
                        <p>What our patients say about their experience</p>
                    </div>
                    <div class="reviews-grid">
                        <?php foreach($reviews as $review): ?>
                        <div class="review-card fade-in">
                            <div class="rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-gray'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p>"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                            <div>
                                <span class="review-author">- <?php echo htmlspecialchars($review['patient_name']); ?></span>
                                <span class="review-date"><?php echo $review['date_posted']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Appointment Form -->
            <section id="appointment" class="form-section">
                <div class="container">
                    <div class="section-title">
                        <h2>Book an Appointment</h2>
                        <p>Schedule your visit with our medical specialists</p>
                    </div>
                    <div class="form-container">
                        <form method="POST">
                            <input type="hidden" name="action" value="book_appointment">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="John Doe">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" required placeholder="john@example.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" required placeholder="(123) 456-7890">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Select Service *</label>
                                <select name="service" class="form-control" required>
                                    <option value="">Choose a service</option>
                                    <?php foreach($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>">
                                        <?php echo htmlspecialchars($service['title']); ?> ($<?php echo number_format($service['price'], 2); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Preferred Date *</label>
                                <input type="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Preferred Time *</label>
                                <input type="time" name="time" class="form-control" required min="08:00" max="18:00">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Additional Message</label>
                                <textarea name="message" class="form-control" placeholder="Any special requirements or symptoms..."></textarea>
                            </div>
                            <button type="submit" class="btn" style="width: 100%; font-size: 1.1rem;">
                                <i class="fas fa-paper-plane"></i> Book Appointment
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Review Form -->
            <section class="bg-light">
                <div class="container">
                    <div class="section-title">
                        <h2>Share Your Experience</h2>
                        <p>We value your feedback to improve our services</p>
                    </div>
                    <div class="form-container">
                        <form method="POST">
                            <input type="hidden" name="action" value="submit_review">
                            <div class="form-group">
                                <label class="form-label">Your Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="Enter your name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rating *</label>
                                <select name="rating" class="form-control" required>
                                    <option value="">Select rating</option>
                                    <option value="5">★★★★★ - Excellent</option>
                                    <option value="4">★★★★☆ - Very Good</option>
                                    <option value="3">★★★☆☆ - Good</option>
                                    <option value="2">★★☆☆☆ - Fair</option>
                                    <option value="1">★☆☆☆☆ - Poor</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Your Review *</label>
                                <textarea name="comment" class="form-control" required rows="4" placeholder="Share your experience with us..."></textarea>
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">
                                <i class="fas fa-star"></i> Submit Review
                            </button>
                        </form>
                    </div>
                </div>
            </section>

        <?php elseif ($page == 'services'): ?>
            <!-- SERVICES PAGE -->
            <?php
            $services = getServices();
            $categories = getServiceCategories();
            ?>
            
            <section class="hero" style="background: linear-gradient(135deg, rgba(42, 157, 143, 0.9) 0%, rgba(38, 70, 83, 0.9) 100%), url('https://images.unsplash.com/photo-1516549655669-df6654e435f6?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');">
                <div class="container">
                    <h1>Our Medical Services</h1>
                    <p>Explore our comprehensive range of healthcare services designed to meet all your medical needs.</p>
                </div>
            </section>

            <section>
                <div class="container">
                    <div class="section-title">
                        <h2>All Healthcare Services</h2>
                        <p>Professional medical care across various specialties</p>
                    </div>
                    
                    <!-- Service Categories -->
                    <div class="mb-4">
                        <h3 class="mb-3">Service Categories</h3>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-outline category-filter active" data-category="all">All Services</button>
                            <?php foreach($categories as $category): ?>
                            <button class="btn btn-outline category-filter" data-category="<?php echo htmlspecialchars($category); ?>">
                                <?php echo htmlspecialchars($category); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Services Grid -->
                    <div class="services-grid" id="services-container">
                        <?php foreach($services as $service): ?>
                        <div class="service-card fade-in" data-category="<?php echo htmlspecialchars($service['category']); ?>" id="service-<?php echo $service['id']; ?>">
                            <div class="service-icon">
                                <i class="fas <?php echo htmlspecialchars($service['icon']); ?>"></i>
                            </div>
                            <span class="category-badge"><?php echo htmlspecialchars($service['category']); ?></span>
                            <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                            <p><?php echo htmlspecialchars($service['description']); ?></p>
                            <div class="price">$<?php echo number_format($service['price'], 2); ?></div>
                            <a href="#appointment" class="btn" onclick="setService(<?php echo $service['id']; ?>)">
                                <i class="fas fa-calendar-plus"></i> Book This Service
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- No services message -->
                    <div id="no-services" class="text-center mt-4" style="display: none;">
                        <h3>No services found in this category</h3>
                        <p>Please select another category.</p>
                    </div>
                </div>
            </section>

            <section id="appointment" class="form-section bg-light">
                <div class="container">
                    <div class="section-title">
                        <h2>Ready to Book?</h2>
                        <p>Select a service and schedule your appointment</p>
                    </div>
                    <div class="form-container">
                        <form method="POST">
                            <input type="hidden" name="action" value="book_appointment">
                            <div class="form-group">
                                <label class="form-label">Select Service *</label>
                                <select name="service" id="service-select" class="form-control" required>
                                    <option value="">Choose a service</option>
                                    <?php foreach($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>">
                                        <?php echo htmlspecialchars($service['title']); ?> ($<?php echo number_format($service['price'], 2); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone *</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Preferred Date *</label>
                                <input type="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Preferred Time *</label>
                                <input type="time" name="time" class="form-control" required min="08:00" max="18:00">
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">
                                <i class="fas fa-calendar-check"></i> Book Appointment
                            </button>
                        </form>
                    </div>
                </div>
            </section>

        <?php elseif ($page == 'about'): ?>
            <!-- ABOUT PAGE -->
            <section class="hero" style="background: linear-gradient(135deg, rgba(42, 157, 143, 0.9) 0%, rgba(38, 70, 83, 0.9) 100%), url('https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');">
                <div class="container">
                    <h1>About MediCare</h1>
                    <p>Committed to excellence in healthcare for over two decades</p>
                </div>
            </section>

            <section>
                <div class="container">
                    <div class="about-content">
                        <div class="about-text">
                            <h2>Our Mission</h2>
                            <p>At MediCare, our mission is to provide accessible, high-quality healthcare services to our community. We believe in treating every patient with compassion, dignity, and respect.</p>
                            <p>Founded in 2000, we have grown to become one of the most trusted healthcare providers in the region, serving thousands of patients annually.</p>
                            
                            <h3 class="mt-4">Our Values</h3>
                            <div class="about-features">
                                <div class="feature">
                                    <i class="fas fa-heart"></i>
                                    <h4>Compassion</h4>
                                    <p>Patient-centered care</p>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-shield-alt"></i>
                                    <h4>Safety</h4>
                                    <p>Highest safety standards</p>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-users"></i>
                                    <h4>Teamwork</h4>
                                    <p>Collaborative approach</p>
                                </div>
                            </div>
                        </div>
                        <div class="about-image">
                            <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?ixlib=rb-4.0.3&auto=format&fit=crop&w=700&q=80" alt="Medical Team" style="width: 100%; border-radius: var(--radius);">
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-light">
                <div class="container">
                    <div class="section-title">
                        <h2>Our Medical Team</h2>
                        <p>Experienced professionals dedicated to your health</p>
                    </div>
                    <div class="services-grid">
                        <div class="service-card text-center">
                            <img src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" alt="Dr. John Smith" style="width: 120px; height: 120px; border-radius: 50%; margin: 0 auto 1rem;">
                            <h3>Dr. John Smith</h3>
                            <p class="category-badge">Cardiologist</p>
                            <p>20+ years of experience in cardiology and heart surgery.</p>
                        </div>
                        <div class="service-card text-center">
                            <img src="https://images.unsplash.com/photo-1594824434340-7e7dfc37cabb?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" alt="Dr. Sarah Johnson" style="width: 120px; height: 120px; border-radius: 50%; margin: 0 auto 1rem;">
                            <h3>Dr. Sarah Johnson</h3>
                            <p class="category-badge">Pediatrician</p>
                            <p>Specialized in child healthcare with 15 years of experience.</p>
                        </div>
                        <div class="service-card text-center">
                            <img src="https://images.unsplash.com/photo-1537368910025-700350fe46c7?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" alt="Dr. Michael Brown" style="width: 120px; height: 120px; border-radius: 50%; margin: 0 auto 1rem;">
                            <h3>Dr. Michael Brown</h3>
                            <p class="category-badge">Orthopedic Surgeon</p>
                            <p>Expert in bone and joint surgeries with 18 years of practice.</p>
                        </div>
                    </div>
                </div>
            </section>

        <?php elseif ($page == 'contact'): ?>
            <!-- CONTACT PAGE -->
            <section class="hero" style="background: linear-gradient(135deg, rgba(42, 157, 143, 0.9) 0%, rgba(38, 70, 83, 0.9) 100%), url('https://images.unsplash.com/photo-1586773860418-dc22f8b874bc?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');">
                <div class="container">
                    <h1>Contact Us</h1>
                    <p>Get in touch with our healthcare team</p>
                </div>
            </section>

            <section>
                <div class="container">
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="contact-details">
                                <h4>Our Location</h4>
                                <p>123 Health Street<br>Medical City, MC 12345</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div class="contact-details">
                                <h4>Phone Number</h4>
                                <p>(123) 456-7890<br>Emergency: (123) 456-7891</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div class="contact-details">
                                <h4>Email Address</h4>
                                <p>info@medicare.com<br>support@medicare.com</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div class="contact-details">
                                <h4>Working Hours</h4>
                                <p>Mon-Fri: 8:00 AM - 8:00 PM<br>Sat-Sun: 9:00 AM - 6:00 PM</p>
                            </div>
                        </div>
                    </div>

                    <div class="section-title">
                        <h2>Send Us a Message</h2>
                        <p>Have questions? We're here to help!</p>
                    </div>

                    <div class="form-container">
                        <form method="POST">
                            <input type="hidden" name="action" value="contact_form">
                            <div class="form-group">
                                <label class="form-label">Your Name *</label>
                                <input type="text" name="contact_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="contact_email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Subject *</label>
                                <input type="text" name="contact_subject" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Message *</label>
                                <textarea name="contact_message" class="form-control" required rows="5"></textarea>
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Map Section -->
            <section class="bg-light">
                <div class="container">
                    <div class="section-title">
                        <h2>Find Us</h2>
                        <p>Visit our healthcare center</p>
                    </div>
                    <div style="border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);">
                        <!-- Placeholder for Google Map -->
                        <div style="background: #e2e8f0; height: 400px; display: flex; align-items: center; justify-content: center;">
                            <div class="text-center">
                                <i class="fas fa-map-marked-alt" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                                <h3>Location Map</h3>
                                <p>123 Health Street, Medical City</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3><i class="fas fa-heartbeat"></i> MediCare</h3>
                        <p>Your trusted partner in healthcare. We provide comprehensive medical services with compassion and excellence.</p>
                        <div class="social-icons">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div class="footer-section">
                        <h3>Quick Links</h3>
                        <ul class="footer-links">
                            <li><a href="?page=home">Home</a></li>
                            <li><a href="?page=services">Services</a></li>
                            <li><a href="?page=about">About Us</a></li>
                            <li><a href="?page=contact">Contact</a></li>
                            <li><a href="?page=admin">Admin</a></li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h3>Our Services</h3>
                        <ul class="footer-links">
                            <li><a href="?page=services">General Consultation</a></li>
                            <li><a href="?page=services">Emergency Care</a></li>
                            <li><a href="?page=services">Lab Tests</a></li>
                            <li><a href="?page=services">Specialist Care</a></li>
                            <li><a href="?page=services">Vaccination</a></li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h3>Emergency Contact</h3>
                        <p><i class="fas fa-phone"></i> (123) 456-7890</p>
                        <p><i class="fas fa-ambulance"></i> Emergency: (123) 456-7891</p>
                        <p><i class="fas fa-envelope"></i> emergency@medicare.com</p>
                        <p><i class="fas fa-clock"></i> 24/7 Emergency Services</p>
                    </div>
                </div>
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> MediCare Health Center. All rights reserved.</p>
                    <p>Designed with <i class="fas fa-heart" style="color: #e76f51;"></i> for better healthcare</p>
                </div>
            </div>
        </footer>
    <?php endif; ?>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const dateInput = this.querySelector('input[type="date"]');
                    if (dateInput) {
                        const selectedDate = new Date(dateInput.value);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        
                        if (selectedDate < today) {
                            e.preventDefault();
                            alert('Please select a future date for your appointment.');
                            dateInput.focus();
                            return;
                        }
                    }
                    
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<span class="loading"></span> Processing...';
                        submitBtn.disabled = true;
                        
                        // Restore button after 3 seconds if form doesn't submit
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 3000);
                    }
                });
            });
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href !== '#') {
                        e.preventDefault();
                        const target = document.querySelector(href);
                        if (target) {
                            window.scrollTo({
                                top: target.offsetTop - 100,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });
            
            // Category filtering for services page
            const categoryFilters = document.querySelectorAll('.category-filter');
            const serviceCards = document.querySelectorAll('.service-card[data-category]');
            const noServicesMsg = document.getElementById('no-services');
            
            if (categoryFilters.length > 0) {
                categoryFilters.forEach(filter => {
                    filter.addEventListener('click', function() {
                        // Update active state
                        categoryFilters.forEach(f => f.classList.remove('active'));
                        this.classList.add('active');
                        
                        const category = this.getAttribute('data-category');
                        let visibleCount = 0;
                        
                        // Show/hide services based on category
                        serviceCards.forEach(card => {
                            if (category === 'all' || card.getAttribute('data-category') === category) {
                                card.style.display = 'block';
                                visibleCount++;
                                setTimeout(() => {
                                    card.classList.add('fade-in');
                                }, 50);
                            } else {
                                card.style.display = 'none';
                                card.classList.remove('fade-in');
                            }
                        });
                        
                        // Show/hide no services message
                        if (visibleCount === 0) {
                            noServicesMsg.style.display = 'block';
                        } else {
                            noServicesMsg.style.display = 'none';
                        }
                    });
                });
            }
            
            // Set service in appointment form
            window.setService = function(serviceId) {
                const serviceSelect = document.getElementById('service-select');
                if (serviceSelect) {
                    serviceSelect.value = serviceId;
                    window.scrollTo({
                        top: document.getElementById('appointment').offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            };
            
            // Set minimum time for appointment time input
            const timeInputs = document.querySelectorAll('input[type="time"]');
            timeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const time = this.value;
                    const [hours, minutes] = time.split(':').map(Number);
                    
                    if (hours < 8 || hours > 18 || (hours === 18 && minutes > 0)) {
                        alert('Please select a time between 8:00 AM and 6:00 PM');
                        this.value = '';
                    }
                });
            });
            
            // Auto-hide messages after 5 seconds
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        if (message.parentNode) {
                            message.parentNode.removeChild(message);
                        }
                    }, 500);
                }, 5000);
            });
            
            // Mobile menu toggle (simplified)
            const navMenu = document.querySelector('.nav-menu');
            if (window.innerWidth <= 768 && navMenu) {
                // Create mobile menu toggle button
                const menuToggle = document.createElement('button');
                menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                menuToggle.className = 'btn';
                menuToggle.style.marginLeft = 'auto';
                menuToggle.style.display = 'block';
                
                const navContainer = document.querySelector('.navbar .container');
                if (navContainer) {
                    navContainer.appendChild(menuToggle);
                    
                    navMenu.style.display = 'none';
                    navMenu.style.flexDirection = 'column';
                    navMenu.style.position = 'absolute';
                    navMenu.style.top = '100%';
                    navMenu.style.left = '0';
                    navMenu.style.right = '0';
                    navMenu.style.background = 'white';
                    navMenu.style.padding = '1rem';
                    navMenu.style.boxShadow = 'var(--shadow)';
                    
                    menuToggle.addEventListener('click', function() {
                        if (navMenu.style.display === 'none') {
                            navMenu.style.display = 'flex';
                        } else {
                            navMenu.style.display = 'none';
                        }
                    });
                    
                    // Close menu when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!navMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                            navMenu.style.display = 'none';
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>