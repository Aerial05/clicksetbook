<?php 
require_once 'includes/auth.php';

// Require login and admin role
requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Get statistics
$stats = [
    'total_users' => 0,
    'total_doctors' => 0,
    'total_services' => 0,
    'pending_appointments' => 0,
    'today_appointments' => 0,
    'total_appointments' => 0
];

// Get total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'");
$stats['total_users'] = $stmt->fetch()['count'];

// Get total doctors
$stmt = $pdo->query("SELECT COUNT(*) as count FROM doctors");
$stats['total_doctors'] = $stmt->fetch()['count'];

// Get total services
$stmt = $pdo->query("SELECT COUNT(*) as count FROM services");
$stats['total_services'] = $stmt->fetch()['count'];

// Get pending appointments
$stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
$stats['pending_appointments'] = $stmt->fetch()['count'];

// Get today's appointments
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()");
$stmt->execute();
$stats['today_appointments'] = $stmt->fetch()['count'];

// Get total appointments
$stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments");
$stats['total_appointments'] = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f9fafb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 0 !important;
            padding-left: 0 !important;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.sidebar-active {
            margin-left: 280px;
        }

        html {
            margin: 0;
            padding: 0;
        }

        /* Admin-specific styles */
        .admin-container {
            max-width: 100%;
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
            background: #f9fafb;
        }

        /* Top Navigation Bar */
        .admin-topnav {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            padding: 16px 24px;
            margin: 0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 0;
            transition: margin-left 0.3s ease;
            width: 100%;
        }

        .topnav-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
        }

        .topnav-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-menu {
            position: relative;
        }

        .user-menu-button {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .user-menu-button:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .user-menu-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 12px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            display: none;
            z-index: 1001;
            overflow: hidden;
        }

        .user-menu-dropdown.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .user-menu-dropdown a,
        .user-menu-dropdown button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            color: #374151;
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .user-menu-dropdown a:hover,
        .user-menu-dropdown button:hover {
            background: #f0f4ff;
            color: #1e3a8a;
        }

        .user-menu-dropdown button:last-child {
            color: #ef4444;
            border-top: 1px solid #e5e7eb;
        }

        .user-menu-dropdown button:last-child:hover {
            background: #fef2f2;
        }

        .admin-topnav.sidebar-active {
            margin-left: 280px;
        }

        .hamburger-menu {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: all 0.3s ease;
            line-height: 1;
        }

        .hamburger-menu:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .topnav-title h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .topnav-title p {
            font-size: 13px;
            opacity: 0.9;
            margin: 2px 0 0 0;
            font-weight: 400;
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 999;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Side Navigation */
        .admin-sidenav {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: white;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            overflow-x: hidden;
            transform: translateX(calc(-100% + 70px));
            border-right: 1px solid #e2e8f0;
        }

        .admin-sidenav::-webkit-scrollbar {
            width: 6px;
        }

        .admin-sidenav::-webkit-scrollbar-track {
            background: transparent;
        }

        .admin-sidenav::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .admin-sidenav::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .admin-sidenav.active {
            transform: translateX(0);
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.15);
        }

        /* Hover trigger zone */
        .sidebar-hover-trigger {
            position: fixed;
            top: 0;
            left: 0;
            width: 70px;
            height: 100vh;
            z-index: 998;
            background: transparent;
            pointer-events: none;
        }

        .sidenav-header {
            background: #f8fafc;
            padding: 20px 16px;
            color: #1e293b;
            position: relative;
            min-height: 76px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-right: 70px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .admin-sidenav.active .sidenav-header {
            margin-right: 0;
            padding-left: 20px;
            padding-right: 20px;
        }

        .sidenav-header h2 {
            font-size: 17px;
            font-weight: 700;
            margin: 0 0 3px 0;
            letter-spacing: -0.3px;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) 0.05s;
            color: #1e293b;
        }

        .admin-sidenav.active .sidenav-header h2 {
            opacity: 1;
            transform: translateX(0);
        }

        .sidenav-header p {
            font-size: 11.5px;
            opacity: 0;
            margin: 0;
            font-weight: 400;
            transform: translateX(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) 0.1s;
            color: #64748b;
        }

        .admin-sidenav.active .sidenav-header p {
            opacity: 1;
            transform: translateX(0);
        }

        /* Logo/Icon in header when collapsed */
        .sidenav-header::before {
            content: '⚕️';
            position: absolute;
            right: -50px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 28px;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .admin-sidenav.active .sidenav-header::before {
            opacity: 0;
        }

        .close-sidebar {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 6px 10px;
            line-height: 1;
            border-radius: 8px;
            transition: all 0.2s ease;
            opacity: 0;
            transform: scale(0.8);
        }

        .admin-sidenav.active .close-sidebar {
            opacity: 1;
            transform: scale(1);
        }

        .close-sidebar:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
        }

        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 8px 0;
            position: relative;
            z-index: 1;
        }

        .admin-nav-item {
            padding: 13px 0 13px 20px;
            background: transparent;
            border: none;
            border-radius: 0;
            font-size: 14.5px;
            font-weight: 500;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: relative;
            overflow: visible;
            padding-right: 0;
        }

        .admin-nav-item .admin-nav-icon {
            font-size: 21px;
            flex-shrink: 0;
            width: 70px;
            text-align: center;
            transition: all 0.2s ease;
            opacity: 1;
            color: #64748b;
        }

        .admin-nav-item span:not(.admin-nav-icon) {
            white-space: nowrap;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) 0.05s;
            color: #1e293b;
            font-weight: 600;
            flex: 1;
        }

        .admin-sidenav.active .admin-nav-item span:not(.admin-nav-icon) {
            opacity: 1;
            transform: translateX(0);
        }

        .admin-nav-item:hover {
            background: rgba(59, 130, 246, 0.08);
        }

        .admin-nav-item:hover .admin-nav-icon {
            transform: scale(1.15);
            color: #3b82f6;
        }

        .admin-nav-item.active {
            background: rgba(59, 130, 246, 0.1);
        }

        .admin-nav-item.active::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 3px 0 0 3px;
        }

        .admin-nav-item.active .admin-nav-icon {
            transform: scale(1.08);
            color: #3b82f6;
        }

        .admin-sidenav.active .admin-nav-item {
            padding-right: 16px;
            margin: 0 10px;
            border-radius: 10px;
        }

        .admin-sidenav.active .admin-nav-item.active::before {
            display: none;
        }

        .admin-sidenav.active .admin-nav-item.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .admin-sidenav.active .admin-nav-item.active span:not(.admin-nav-icon) {
            color: white;
        }

        .admin-sidenav.active .admin-nav-item.active .admin-nav-icon {
            color: white;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
            padding: 12px 16px;
            max-width: 1600px;
            margin-left: auto;
            margin-right: auto;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border-radius: 10px;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            border-color: #d1d5db;
        }

        .stat-card-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-card-icon.blue {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .stat-card-icon.green {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .stat-card-icon.orange {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .stat-card-icon.purple {
            background: linear-gradient(135deg, #a855f7, #9333ea);
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);
        }

        .stat-card-content {
            flex: 1;
            min-width: 0;
        }

        .stat-card-value {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            line-height: 1;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }

        .stat-card-label {
            font-size: 11px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.2;
        }

        /* Content Sections */
        .admin-section {
            display: none;
            padding: 20px 24px;
            max-width: 1600px;
            margin-left: auto;
            margin-right: auto;
            transition: margin-left 0.3s ease;
        }

        .admin-section.sidebar-active {
            margin-left: 280px;
        }

        .admin-section.active {
            display: block;
        }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-color);
            letter-spacing: -0.5px;
        }

        /* Form Controls */
        .form-control, select, input[type="text"], input[type="date"], input[type="time"], input[type="number"], input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            background: white;
            transition: all 0.3s ease;
        }

        .form-control:focus, select:focus, input:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.1), 0 0 0 1px #3b82f6;
        }

        /* Buttons - Minimalist Design System */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn:hover {
            opacity: 0.85;
        }

        .btn:active {
            transform: scale(0.98);
        }

        /* Primary Button - Blue */
        .btn-primary {
            background: #60a5fa;
            color: white;
        }

        /* Success Button - Green */
        .btn-success {
            background: #4ade80;
            color: white;
        }

        /* Edit Button - Purple */
        .btn-edit {
            background: #a78bfa;
            color: white;
        }

        /* Archive Button - Orange */
        .btn-archive {
            background: #fbbf24;
            color: white;
        }

        /* Danger Button - Red */
        .btn-danger {
            background: #f87171;
            color: white;
        }

        /* Secondary Button - Gray */
        .btn-secondary {
            background: #9ca3af;
            color: white;
        }

        /* Info Button - Cyan */
        .btn-info {
            background: #22d3ee;
            color: white;
        }

        /* Button Sizes */
        .btn-sm {
            padding: 7px 14px;
            font-size: 13px;
            border-radius: 6px;
        }

        .btn-lg {
            padding: 12px 24px;
            font-size: 15px;
            border-radius: 10px;
        }

        .btn-block {
            width: 100%;
        }

        /* Button with Icons */
        .btn svg {
            width: 16px;
            height: 16px;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
            letter-spacing: 0.3px;
        }

        .badge-blue {
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(59, 130, 246, 0.1));
            color: #1e3a8a;
            border: 1px solid rgba(30, 58, 138, 0.2);
        }

        .badge-green {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(74, 222, 128, 0.1));
            color: #15803d;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .badge-orange {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(251, 146, 60, 0.1));
            color: #c2410c;
            border: 1px solid rgba(249, 115, 22, 0.2);
        }

        .badge-gray {
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.1), rgba(156, 163, 175, 0.1));
            color: #4b5563;
            border: 1px solid rgba(107, 114, 128, 0.2);
        }

        .badge-secondary {
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.1), rgba(156, 163, 175, 0.1));
            color: #4b5563;
            border: 1px solid rgba(107, 114, 128, 0.2);
        }

        /* Card */
        .card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
            border-color: #d1d5db;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            color: #9ca3af;
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            border-radius: 14px;
            border: 1.5px dashed #e5e7eb;
        }

        .empty-state p {
            font-size: 16px;
            margin: 0;
            font-weight: 500;
        }

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 80px 40px;
            color: #9ca3af;
        }

        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 3px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-state p {
            font-size: 14px;
            margin: 0;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e5e7eb;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 24px;
        }

        .tab-button {
            padding: 14px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: #6b7280;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            margin-bottom: -2px;
        }

        .tab-button:hover {
            color: #1f2937;
            border-bottom-color: #d1d5db;
        }

        .tab-button.active {
            color: #1e3a8a;
            border-bottom-color: #3b82f6;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Form Labels */
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
            letter-spacing: 0.2px;
        }

        /* Checkboxes */
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            margin: 0;
            accent-color: #3b82f6;
            transition: all 0.2s ease;
        }

        input[type="checkbox"]:hover {
            border-color: #9ca3af;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.2s ease;
            backdrop-filter: blur(4px);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1.5px solid #f3f4f6;
        }

        .modal-header h2 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: #1f2937;
        }

        .close-modal {
            background: #f3f4f6;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .close-modal:hover {
            background: #e5e7eb;
            color: #1f2937;
        }

        .modal-content form {
            padding: 24px;
        }

        /* Filter Controls */
        .filter-controls {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            padding: 0;
        }

        .filter-controls > div {
            flex: 1;
            min-width: 200px;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #f9fafb;
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #374151;
            border-bottom: 1.5px solid #e5e7eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            color: #374151;
        }

        tr:hover {
            background: #f9fafb;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .admin-container {
                padding-left: 0;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                padding: 16px;
            }

            .admin-topnav {
                padding: 14px 16px;
            }

            .topnav-title h1 {
                font-size: 18px;
            }

            .section-title {
                font-size: 20px;
            }

            .filter-controls {
                flex-direction: column;
            }

            .filter-controls > div {
                min-width: auto;
            }

            .admin-section {
                padding: 16px;
            }

            .card {
                padding: 16px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }
        }

        @media (min-width: 768px) {
            .topnav-title h1 {
                font-size: 22px;
            }

            .topnav-title p {
                font-size: 14px;
            }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }

        @media (min-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(6, 1fr) !important;
            }
        }

        @media (min-width: 1280px) {
            .admin-container {
                max-width: 100%;
            }
        }

        /* Custom Toast Notification */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 24px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }

        .toast {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            min-width: 320px;
            max-width: 400px;
            display: flex;
            align-items: center;
            gap: 12px;
            pointer-events: all;
            animation: slideInRight 0.3s ease;
            border-left: 4px solid;
        }

        .toast.success { border-left-color: #4ade80; }
        .toast.error { border-left-color: #f87171; }
        .toast.warning { border-left-color: #fbbf24; }
        .toast.info { border-left-color: #60a5fa; }

        .toast-icon { width: 24px; height: 24px; flex-shrink: 0; }
        .toast-content { flex: 1; }
        .toast-title { font-weight: 600; font-size: 14px; color: #1f2937; margin-bottom: 2px; }
        .toast-message { font-size: 13px; color: #6b7280; line-height: 1.4; }

        .toast-close {
            background: none; border: none; color: #9ca3af; cursor: pointer;
            padding: 4px; display: flex; align-items: center; justify-content: center;
            border-radius: 4px; transition: all 0.2s ease;
        }
        .toast-close:hover { background: #f3f4f6; color: #374151; }

        @keyframes slideInRight { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }
        .toast.hiding { animation: slideOutRight 0.3s ease forwards; }

        /* Custom Confirm Dialog */
        .confirm-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.4); display: none;
            align-items: center; justify-content: center; z-index: 10001;
            animation: fadeIn 0.2s ease;
        }
        .confirm-overlay.active { display: flex; }

        .confirm-dialog {
            background: white; border-radius: 16px; padding: 24px;
            max-width: 400px; width: 90%; animation: scaleIn 0.2s ease;
        }

        .confirm-icon {
            width: 48px; height: 48px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px; font-size: 24px;
        }
        .confirm-icon.warning { background: #fef3c7; color: #f59e0b; }
        .confirm-icon.danger { background: #fee2e2; color: #ef4444; }
        .confirm-icon.info { background: #dbeafe; color: #3b82f6; }
        .confirm-icon.success { background: #d1fae5; color: #22c55e; }

        .confirm-title { font-size: 18px; font-weight: 600; color: #1f2937; text-align: center; margin-bottom: 8px; }
        .confirm-message { font-size: 14px; color: #6b7280; text-align: center; line-height: 1.5; margin-bottom: 24px; }
        .confirm-buttons { display: flex; gap: 12px; }
        .confirm-buttons button { flex: 1; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Confirm Dialog -->
    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-dialog">
            <div class="confirm-icon" id="confirmIcon"></div>
            <h3 class="confirm-title" id="confirmTitle"></h3>
            <p class="confirm-message" id="confirmMessage"></p>
            <div class="confirm-buttons">
                <button class="btn btn-secondary confirm-btn-cancel" id="confirmCancel">Cancel</button>
                <button class="btn btn-primary confirm-btn-ok" id="confirmOk">OK</button>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Hover trigger for sidebar -->
    <div class="sidebar-hover-trigger" id="sidebarHoverTrigger"></div>

    <!-- Side Navigation -->
    <aside class="admin-sidenav" id="adminSidenav">
        <div class="sidenav-header">
            <button class="close-sidebar" id="closeSidebar">×</button>
            <h2>Admin Panel</h2>
            <p>Navigation Menu</p>
        </div>
        <nav class="admin-nav">
            <a href="#" class="admin-nav-item active" data-section="overview">
                <span>Overview</span>
                <span class="admin-nav-icon">📊</span>
            </a>
            <a href="#" class="admin-nav-item" data-section="appointments">
                <span>Appointments</span>
                <span class="admin-nav-icon">📅</span>
            </a>
            <a href="#" class="admin-nav-item" data-section="users">
                <span>Users</span>
                <span class="admin-nav-icon">👥</span>
            </a>
            <a href="#" class="admin-nav-item" data-section="doctors">
                <span>Doctors</span>
                <span class="admin-nav-icon">👨‍⚕️</span>
            </a>
            <a href="#" class="admin-nav-item" data-section="services">
                <span>Services</span>
                <span class="admin-nav-icon">🔬</span>
            </a>
            <a href="#" class="admin-nav-item" data-section="settings">
                <span>Settings</span>
                <span class="admin-nav-icon">⚙️</span>
            </a>
            <a href="#" class="admin-nav-item" data-section="logs">
                <span>History & Logs</span>
                <span class="admin-nav-icon">📋</span>
            </a>
        </nav>
    </aside>

    <div class="admin-container">
        <!-- Top Navigation Bar -->
        <div class="admin-topnav" id="adminTopnav">
            <div class="topnav-left">
                <button class="hamburger-menu" id="hamburgerMenu">
                    <span>☰</span>
                </button>
                <div class="topnav-title">
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</p>
                </div>
            </div>
            <div class="topnav-right">
                <div class="user-menu">
                    <button class="user-menu-button" id="userMenuButton">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($currentUser['first_name'], 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($currentUser['first_name']); ?></span>
                        <span>▾</span>
                    </button>
                    <div class="user-menu-dropdown" id="userMenuDropdown">
                        <a href="#" onclick="showProfileSection(); return false;">
                            <span>👤</span>
                            <span>My Profile</span>
                        </a>
                        <a href="#" data-section="settings">
                            <span>⚙️</span>
                            <span>Settings</span>
                        </a>
                        <button onclick="confirmLogout()">
                            <span>🚪</span>
                            <span>Logout</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Content -->
        <div class="admin-content">
            <!-- Overview Section -->
            <section id="overview-section" class="admin-section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-icon blue">👥</div>
                        <div class="stat-card-content">
                            <div class="stat-card-value"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-card-label">Total Users</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon green">👨‍⚕️</div>
                        <div class="stat-card-content">
                            <div class="stat-card-value"><?php echo $stats['total_doctors']; ?></div>
                            <div class="stat-card-label">Doctors</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon purple">🔬</div>
                        <div class="stat-card-content">
                            <div class="stat-card-value"><?php echo $stats['total_services']; ?></div>
                            <div class="stat-card-label">Services</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon orange">⏳</div>
                        <div class="stat-card-content">
                            <div class="stat-card-value"><?php echo $stats['pending_appointments']; ?></div>
                            <div class="stat-card-label">Pending</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon blue">📅</div>
                        <div class="stat-card-content">
                            <div class="stat-card-value"><?php echo $stats['today_appointments']; ?></div>
                            <div class="stat-card-label">Today</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon green">✅</div>
                        <div class="stat-card-content">
                            <div class="stat-card-value"><?php echo $stats['total_appointments']; ?></div>
                            <div class="stat-card-label">Total Bookings</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="section-header">
                        <h2 class="section-title">Recent Appointments</h2>
                        <a href="#" class="admin-nav-item manage-appointment-btn" data-section="appointments" style="padding: 10px 22px; font-size: 14px; font-weight: 600; background: var(--primary-color); color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(30,58,138,0.08); border: none; text-decoration: none; transition: background 0.2s;">Manage Appointment</a>
                    </div>
                    <div id="recent-appointments">
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Loading appointments...</p>
                        </div>
                    </div>
                    
                    <!-- Recent Appointments Pagination -->
                    <div id="recent-appointments-pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 20px; flex-wrap: wrap;"></div>
                </div>
            </section>

            <!-- Appointments Section -->
            <section id="appointments-section" class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">Appointment Management</h2>
                </div>
                
                <!-- Filters -->
                <div class="card" style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div>
                            <label for="filter-status">Status</label>
                            <select id="filter-status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div>
                            <label for="filter-date">Date</label>
                            <input type="date" id="filter-date" class="form-control">
                        </div>
                        <div>
                            <label for="filter-sort">Sort By</label>
                            <select id="filter-sort" class="form-control">
                                <option value="latest">Latest First</option>
                                <option value="oldest">Oldest First</option>
                                <option value="date-asc">Date (Earliest)</option>
                                <option value="date-desc">Date (Latest)</option>
                            </select>
                        </div>
                        <div>
                            <label for="filter-search">Search</label>
                            <input type="text" id="filter-search" class="form-control" placeholder="Patient name, email...">
                        </div>
                    </div>
                </div>

                <!-- Appointments List -->
                <div id="appointments-list">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Loading appointments...</p>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div id="appointments-pagination" style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px;">
                </div>
            </section>

            <!-- Users Section -->
            <section id="users-section" class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">User Management</h2>
                    <button class="btn btn-primary" onclick="showAddUserModal()">
                        <span>+</span>
                        <span>Add User</span>
                    </button>
                </div>
                
                <!-- Search -->
                <div class="card" style="margin-bottom: 20px;">
                    <input type="text" id="search-users" class="form-control" placeholder="Search users by name or email...">
                </div>

                <!-- Users List -->
                <div id="users-list">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Loading users...</p>
                    </div>
                </div>
                
                <!-- Users Pagination -->
                <div id="users-pagination" style="display: flex; justify-content: center; gap: 8px; margin-top: 20px; flex-wrap: wrap;"></div>
            </section>

            <!-- Doctors Section -->
            <section id="doctors-section" class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">Doctors Management</h2>
                    <button class="btn btn-primary" onclick="showAddDoctorModal()">
                        <span>+</span>
                        <span>Add Doctor</span>
                    </button>
                </div>

                <!-- Doctors List -->
                <div id="doctors-list">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Loading doctors...</p>
                    </div>
                </div>
            </section>

            <!-- Services Section -->
            <section id="services-section" class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">Services Management</h2>
                    <button class="btn btn-primary" onclick="showAddServiceModal()">
                        <span>+</span>
                        <span>Add Service</span>
                    </button>
                </div>

                <!-- Services List -->
                <div id="services-list">
                    <div class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Loading services...</p>
                    </div>
                </div>
            </section>

            <!-- Add Doctor Modal -->
            <div id="addDoctorModal" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2 style="margin: 0; font-size: 20px; font-weight: 600;">Add New Doctor</h2>
                        <button class="close-modal" onclick="closeAddDoctorModal()">&times;</button>
                    </div>
                    <form id="addDoctorForm">
                        <div style="display: grid; gap: 16px;">
                            <!-- Profile Image Upload -->
                            <div>
                                <label class="form-label">Profile Picture</label>
                                <div style="display: flex; align-items: center; gap: 16px;">
                                    <div id="add_doctor_image_preview" style="width: 80px; height: 80px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                        <span style="font-size: 32px; color: #9ca3af;">DR</span>
                                    </div>
                                    <div style="flex: 1;">
                                        <input type="file" id="add_doctor_image_input" accept="image/*" style="display: none;">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('add_doctor_image_input').click()">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                                <circle cx="12" cy="13" r="4"></circle>
                                            </svg>
                                            Select Photo
                                        </button>
                                        <p style="font-size: 12px; color: #6b7280; margin-top: 4px;">JPG, PNG, GIF or WebP (Max 5MB)</p>
                                    </div>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                                <div>
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div>
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div>
                                <label class="form-label">License Number *</label>
                                <input type="text" name="license_number" class="form-control" required>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">Specialty *</label>
                                    <input type="text" name="specialty" class="form-control" placeholder="e.g., Cardiology" required>
                                </div>
                                <div>
                                    <label class="form-label">Department</label>
                                    <input type="text" name="department" class="form-control" placeholder="e.g., Surgery">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">Experience (Years) *</label>
                                    <input type="number" name="experience_years" class="form-control" min="0" required>
                                </div>
                                <div>
                                    <label class="form-label">Consultation Fee *</label>
                                    <input type="number" name="consultation_fee" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Qualification</label>
                                <textarea name="qualification" class="form-control" rows="2" placeholder="e.g., MD, MBBS, Specialty Certifications"></textarea>
                            </div>
                            <div>
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-control" rows="3" placeholder="Brief professional biography"></textarea>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="is_available" value="1" checked>
                                    <span>Available for appointments</span>
                                </label>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="closeAddDoctorModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Doctor</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Doctor Modal -->
            <div id="editDoctorModal" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2 style="margin: 0; font-size: 20px; font-weight: 600;">Edit Doctor</h2>
                        <button class="close-modal" onclick="closeEditDoctorModal()">&times;</button>
                    </div>
                    <form id="editDoctorForm">
                        <input type="hidden" id="edit_doctor_id" name="id">
                        <div style="display: grid; gap: 16px;">
                            <!-- Profile Image Upload -->
                            <div>
                                <label class="form-label">Profile Picture</label>
                                <div style="display: flex; align-items: center; gap: 16px;">
                                    <div id="edit_doctor_image_preview" style="width: 80px; height: 80px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                        <span style="font-size: 32px; color: #9ca3af;">DR</span>
                                    </div>
                                    <div style="flex: 1;">
                                        <input type="file" id="edit_doctor_image_input" accept="image/*" style="display: none;">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('edit_doctor_image_input').click()">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                                <circle cx="12" cy="13" r="4"></circle>
                                            </svg>
                                            Change Photo
                                        </button>
                                        <p style="font-size: 12px; color: #6b7280; margin-top: 4px;">JPG, PNG, GIF or WebP (Max 5MB)</p>
                                    </div>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">First Name *</label>
                                    <input type="text" id="edit_first_name" name="first_name" class="form-control" required>
                                </div>
                                <div>
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" id="edit_last_name" name="last_name" class="form-control" required>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">Email *</label>
                                    <input type="email" id="edit_email" name="email" class="form-control" required>
                                </div>
                                <div>
                                    <label class="form-label">Phone *</label>
                                    <input type="tel" id="edit_phone" name="phone" class="form-control" required>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">License Number *</label>
                                    <input type="text" id="edit_license_number" name="license_number" class="form-control" required>
                                </div>
                                <div>
                                    <label class="form-label">Specialty *</label>
                                    <input type="text" id="edit_specialty" name="specialty" class="form-control" placeholder="e.g., Cardiologist" required>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">Department</label>
                                    <input type="text" id="edit_department" name="department" class="form-control" placeholder="e.g., Cardiology">
                                </div>
                                <div>
                                    <label class="form-label">Experience (Years)</label>
                                    <input type="number" id="edit_experience_years" name="experience_years" class="form-control" min="0" value="0">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Consultation Fee (₱)</label>
                                <input type="number" id="edit_consultation_fee" name="consultation_fee" class="form-control" step="0.01" min="0">
                            </div>
                            <div>
                                <label class="form-label">Qualification</label>
                                <textarea id="edit_qualification" name="qualification" class="form-control" rows="2" placeholder="e.g., MD, FACC"></textarea>
                            </div>
                            <div>
                                <label class="form-label">Bio</label>
                                <textarea id="edit_bio" name="bio" class="form-control" rows="3" placeholder="Brief professional bio"></textarea>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="edit_is_available" name="is_available" value="1">
                                    <span>Available for appointments</span>
                                </label>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="closeEditDoctorModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Doctor</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Service Modal -->
            <div id="addServiceModal" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2 style="margin: 0; font-size: 20px; font-weight: 600;">Add New Service</h2>
                        <button class="close-modal" onclick="closeAddServiceModal()">&times;</button>
                    </div>
                    <form id="addServiceForm">
                        <div style="display: grid; gap: 16px;">
                            <div>
                                <label class="form-label">Service Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., Blood Test - Complete Panel" required>
                            </div>
                            <div>
                                <label class="form-label">Description *</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Detailed description of the service" required></textarea>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">Category *</label>
                                    <select name="category" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <option value="consultation">Consultation</option>
                                        <option value="laboratory">Laboratory</option>
                                        <option value="radiology">Radiology</option>
                                        <option value="physiotherapy">Physiotherapy</option>
                                        <option value="surgery">Surgery</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Duration (Minutes) *</label>
                                    <input type="number" name="duration_minutes" class="form-control" min="5" step="5" value="30" required>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">Base Cost (₱) *</label>
                                    <input type="number" name="base_cost" class="form-control" step="0.01" min="0" required>
                                </div>
                                <div style="display: flex; align-items: flex-end;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding-bottom: 8px;">
                                        <input type="checkbox" name="requires_doctor" value="1">
                                        <span>Requires Doctor</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Preparation Instructions</label>
                                <textarea name="preparation_instructions" class="form-control" rows="3" placeholder="Any preparation instructions for patients (optional)"></textarea>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    <span>Active (Available for booking)</span>
                                </label>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="closeAddServiceModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Service</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Service Modal -->
            <div id="editServiceModal" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2 style="margin: 0; font-size: 20px; font-weight: 600;">Edit Service</h2>
                        <button class="close-modal" onclick="closeEditServiceModal()">&times;</button>
                    </div>
                    <form id="editServiceForm">
                        <input type="hidden" name="id" id="editServiceId">
                        <div style="display: grid; gap: 16px;">
                            <div>
                                <label class="form-label">Service Name *</label>
                                <input type="text" name="name" id="editServiceName" class="form-control" placeholder="e.g., Blood Test - Complete Panel" required>
                            </div>
                            <div>
                                <label class="form-label">Description *</label>
                                <textarea name="description" id="editServiceDescription" class="form-control" rows="3" placeholder="Detailed description of the service" required></textarea>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">Category *</label>
                                    <select name="category" id="editServiceCategory" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <option value="consultation">Consultation</option>
                                        <option value="laboratory">Laboratory</option>
                                        <option value="radiology">Radiology</option>
                                        <option value="physiotherapy">Physiotherapy</option>
                                        <option value="surgery">Surgery</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Duration (Minutes) *</label>
                                    <input type="number" name="duration_minutes" id="editServiceDuration" class="form-control" min="5" step="5" value="30" required>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">Base Cost (₱) *</label>
                                    <input type="number" name="base_cost" id="editServiceCost" class="form-control" step="0.01" min="0" required>
                                </div>
                                <div style="display: flex; align-items: flex-end;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding-bottom: 8px;">
                                        <input type="checkbox" name="requires_doctor" id="editServiceRequiresDoctor" value="1">
                                        <span>Requires Doctor</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Preparation Instructions</label>
                                <textarea name="preparation_instructions" id="editServicePreparation" class="form-control" rows="3" placeholder="Any preparation instructions for patients (optional)"></textarea>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="is_active" id="editServiceActive" value="1" checked>
                                    <span>Active (Available for booking)</span>
                                </label>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="closeEditServiceModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Service</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add User Modal -->
            <div id="addUserModal" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-header">
                        <h2 style="margin: 0; font-size: 20px; font-weight: 600;">Add New User</h2>
                        <button class="close-modal" onclick="closeAddUserModal()">&times;</button>
                    </div>
                    <form id="addUserForm">
                        <div style="display: grid; gap: 16px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                                <div>
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" placeholder="Unique username" required>
                            </div>
                            <div>
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div>
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" minlength="8" placeholder="Minimum 8 characters" required>
                            </div>
                            <div>
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control" placeholder="e.g., +1234567890">
                            </div>
                            <div>
                                <label class="form-label">Role *</label>
                                <select name="role" class="form-control" required>
                                    <option value="">Select Role</option>
                                    <option value="patient">Patient</option>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control">
                            </div>
                            <div>
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Full address (optional)"></textarea>
                            </div>
                            <div>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    <span>Active Account</span>
                                </label>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add User</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Role Modal -->
            <!-- Edit User Modal -->
            <div id="editUserModal" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 500px;">
                    <div class="modal-header">
                        <h2 style="margin: 0; font-size: 20px; font-weight: 600;">Edit User</h2>
                        <button class="close-modal" onclick="closeEditUserModal()">&times;</button>
                    </div>
                    <div id="editUserFormContainer" style="padding: 24px;">
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Loading...</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="changeRoleModal" class="modal" style="display: none;">
                <div class="modal-content" style="max-width: 400px;">
                    <div class="modal-header">
                        <h2 style="margin: 0; font-size: 20px; font-weight: 600;">Change User Role</h2>
                        <button class="close-modal" onclick="closeChangeRoleModal()">&times;</button>
                    </div>
                    <form id="changeRoleForm">
                        <input type="hidden" id="changeRoleUserId" name="userId">
                        <div style="display: grid; gap: 16px;">
                            <div>
                                <label class="form-label">User</label>
                                <div id="changeRoleUserName" style="font-weight: 600; color: var(--primary-color); padding: 8px; background: #f3f4f6; border-radius: 6px;"></div>
                            </div>
                            <div>
                                <label class="form-label">Current Role</label>
                                <div id="changeRoleCurrentRole" style="padding: 8px; background: #fef3c7; border-radius: 6px; font-weight: 500;"></div>
                            </div>
                            <div>
                                <label class="form-label">New Role *</label>
                                <select name="newRole" id="changeRoleNewRole" class="form-control" required>
                                    <option value="">Select new role</option>
                                    <option value="patient">Patient</option>
                                    <option value="doctor">Doctor</option>
                                    <option value="admin">Admin</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div style="padding: 12px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 6px; font-size: 14px;">
                                ⚠️ <strong>Warning:</strong> Changing a user's role will affect their permissions and access to the system.
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="closeChangeRoleModal()">Cancel</button>
                            <button type="submit" class="btn btn-info">Change Role</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Settings Section -->
            <section id="settings-section" class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">System Settings</h2>
                </div>

                <!-- Two Column Layout for Settings -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                    
                    <!-- Business Hours Card -->
                    <div class="card">
                        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 20px; color: var(--text-primary);">
                            ⏰ Business Hours
                        </h3>
                        <form id="business-hours-form">
                            <div style="display: grid; gap: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <label for="opening_time" style="font-size: 13px; color: var(--text-light); margin-bottom: 6px; display: block;">Opening Time</label>
                                        <input type="time" name="opening_time" id="opening_time" class="form-control" value="08:00">
                                    </div>
                                    <div>
                                        <label for="closing_time" style="font-size: 13px; color: var(--text-light); margin-bottom: 6px; display: block;">Closing Time</label>
                                        <input type="time" name="closing_time" id="closing_time" class="form-control" value="18:00">
                                    </div>
                                </div>
                                <div>
                                    <label for="default_duration" style="font-size: 13px; color: var(--text-light); margin-bottom: 6px; display: block;">Default Appointment Duration</label>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <input type="number" name="default_duration" id="default_duration" class="form-control" value="30" min="15" step="15" style="flex: 1;">
                                        <span style="font-size: 14px; color: var(--text-light);">minutes</span>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: fit-content; padding: 10px 24px; margin-top: 8px;">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Notification Settings Card -->
                    <div class="card">
                        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 20px; color: var(--text-primary);">
                            🔔 Notification Settings
                        </h3>
                        <form id="notification-settings-form">
                            <div style="display: grid; gap: 16px;">
                                <div style="padding: 12px; background: var(--bg-secondary); border-radius: 8px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <input type="checkbox" id="email-notifications" name="email_notifications" checked style="cursor: pointer;">
                                        <label for="email-notifications" style="margin: 0; cursor: pointer; flex: 1;">
                                            <div style="font-weight: 500; font-size: 14px; margin-bottom: 2px;">Email Notifications</div>
                                            <div style="font-size: 12px; color: var(--text-light);">Send email notifications to users</div>
                                        </label>
                                    </div>
                                </div>
                                <div style="padding: 12px; background: var(--bg-secondary); border-radius: 8px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <input type="checkbox" id="admin-notifications" name="admin_notifications" checked style="cursor: pointer;">
                                        <label for="admin-notifications" style="margin: 0; cursor: pointer; flex: 1;">
                                            <div style="font-weight: 500; font-size: 14px; margin-bottom: 2px;">Admin Notifications</div>
                                            <div style="font-size: 12px; color: var(--text-light);">Receive admin notifications</div>
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: fit-content; padding: 10px 24px; margin-top: 8px;">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

                <!-- System Information Card - Full Width -->
                <div class="card" style="margin-top: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-primary);">
                        💻 System Information
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                        <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border-left: 4px solid var(--primary-color);">
                            <div style="font-size: 12px; color: var(--text-light); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">System Version</div>
                            <div style="font-size: 18px; font-weight: 600; color: var(--text-primary);">1.0.0</div>
                        </div>
                        <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border-left: 4px solid #22c55e;">
                            <div style="font-size: 12px; color: var(--text-light); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Database Status</div>
                            <div style="font-size: 18px; font-weight: 600; color: #22c55e;">
                                <svg style="width: 16px; height: 16px; display: inline; margin-right: 4px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                Connected
                            </div>
                        </div>
                        <div style="padding: 16px; background: var(--bg-secondary); border-radius: 12px; border-left: 4px solid #f59e0b;">
                            <div style="font-size: 12px; color: var(--text-light); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Last Updated</div>
                            <div style="font-size: 18px; font-weight: 600; color: var(--text-primary);">Nov 22, 2025</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- My Profile Section -->
            <section id="profile-section" class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">My Profile</h2>
                </div>

                <div class="card">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 20px;">Personal Information</h3>
                    <form id="profile-form">
                        <div style="display: grid; gap: 16px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($currentUser['first_name']); ?>" required>
                                </div>
                                <div>
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($currentUser['last_name']); ?>" required>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
                                <small style="color: var(--text-light); font-size: 12px;">Username cannot be changed</small>
                            </div>
                            <div>
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                            </div>
                            <div>
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($currentUser['role']); ?>" disabled>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-secondary" onclick="resetProfileForm()">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Card -->
                <div class="card" style="margin-top: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 20px;">Change Password</h3>
                    <form id="change-password-form">
                        <div style="display: grid; gap: 16px;">
                            <div>
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div>
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="8">
                                <small style="color: var(--text-light); font-size: 12px;">Minimum 8 characters</small>
                            </div>
                            <div>
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary">Update Password</button>
                            <button type="button" class="btn btn-secondary" onclick="resetPasswordForm()">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Account Information -->
                <div class="card" style="margin-top: 20px;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Account Information</h3>
                    <div style="display: grid; gap: 12px; color: var(--text-light); font-size: 14px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Account Status:</span>
                            <span class="badge badge-green">Active</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Email Verified:</span>
                            <span class="badge <?php echo (!empty($currentUser['email_verified']) && $currentUser['email_verified']) ? 'badge-green' : 'badge-orange'; ?>">
                                <?php echo (!empty($currentUser['email_verified']) && $currentUser['email_verified']) ? 'Verified' : 'Not Verified'; ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Member Since:</span>
                            <strong style="color: var(--text-color);">
                                <?php 
                                    if (!empty($currentUser['created_at'])) {
                                        echo date('F j, Y', strtotime($currentUser['created_at']));
                                    } else {
                                        echo 'N/A';
                                    }
                                ?>
                            </strong>
                        </div>
                        <?php if (!empty($currentUser['last_login'])): ?>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Last Login:</span>
                            <strong style="color: var(--text-color);"><?php echo date('M j, Y g:i A', strtotime($currentUser['last_login'])); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- History & Logs Section -->
            <section id="logs-section" class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">History & Logs</h2>
                </div>

                <!-- Date Range Filter -->
                <div class="card" style="margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label for="log-from-date">From Date</label>
                            <input type="date" id="log-from-date" class="form-control">
                        </div>
                        <div>
                            <label for="log-to-date">To Date</label>
                            <input type="date" id="log-to-date" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Logs Tabs -->
                <div class="tabs" style="margin-bottom: 20px;">
                    <button class="tab-button active" data-tab="appointment-history">Appointments</button>
                    <button class="tab-button" data-tab="user-activity">User Activity</button>
                    <button class="tab-button" data-tab="system-changes">System Changes</button>
                </div>

                <!-- Logs Content -->
                <div id="appointment-history" class="tab-content active">
                    <div id="appointment-history-list">
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Loading appointment history...</p>
                        </div>
                    </div>
                </div>

                <div id="user-activity" class="tab-content">
                    <div id="user-activity-list">
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Loading user activity...</p>
                        </div>
                    </div>
                </div>

                <div id="system-changes" class="tab-content">
                    <div id="system-changes-list">
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Loading system changes...</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality with hover
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const adminSidenav = document.getElementById('adminSidenav');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebarHoverTrigger = document.getElementById('sidebarHoverTrigger');
        
        let sidebarTimeout;

        function openSidebar() {
            clearTimeout(sidebarTimeout);
            adminSidenav.classList.add('active');
            document.body.classList.add('sidebar-active');
        }

        function closeSidebarFunc() {
            sidebarTimeout = setTimeout(() => {
                adminSidenav.classList.remove('active');
                document.body.classList.remove('sidebar-active');
            }, 300);
        }
        
        // Hover to open sidebar
        adminSidenav.addEventListener('mouseenter', () => {
            openSidebar();
        });
        
        // Close sidebar when mouse leaves with delay
        adminSidenav.addEventListener('mouseleave', () => {
            closeSidebarFunc();
        });
        
        // Hamburger menu click functionality
        hamburgerMenu.addEventListener('click', () => {
            openSidebar();
            sidebarOverlay.classList.add('active');
        });
        
        // Close button functionality
        closeSidebar.addEventListener('click', () => {
            clearTimeout(sidebarTimeout);
            adminSidenav.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-active');
        });
        
        // Click overlay to close
        sidebarOverlay.addEventListener('click', () => {
            clearTimeout(sidebarTimeout);
            adminSidenav.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-active');
        });

        // Navigation handling
        document.querySelectorAll('.admin-nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.dataset.section;
                
                // Update active nav item
                document.querySelectorAll('.admin-nav-item').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding section
                document.querySelectorAll('.admin-section').forEach(sec => sec.classList.remove('active'));
                document.getElementById(section + '-section').classList.add('active');
                
                // Don't close sidebar when clicking - let hover out handle it
                // Sidebar will close when mouse leaves
                
                // Load section data
                loadSectionData(section);
            });
        });

        // Logs tabs handling
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const tab = this.dataset.tab;
                
                // Update active tab button
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding tab content
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById(tab).classList.add('active');
                
                // Load tab data
                loadLogData(tab);
            });
        });

        // Load section data
        function loadSectionData(section) {
            switch(section) {
                case 'overview':
                    loadRecentAppointments();
                    break;
                case 'appointments':
                    loadAllAppointments();
                    break;
                case 'users':
                    loadUsers();
                    break;
                case 'doctors':
                    loadDoctors();
                    break;
                case 'services':
                    loadServices();
                    break;
                case 'logs':
                    loadLogData('appointment-history');
                    break;
            }
        }

        // Pagination state for recent appointments
        let currentRecentPage = 1;
        const recentAppointmentsPerPage = 4;
        let allRecentAppointments = [];

        // Load recent appointments for overview
        function loadRecentAppointments() {
            fetch('api/admin/get-appointments.php?limit=100&recent=true')
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return r.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            allRecentAppointments = data.appointments;
                            currentRecentPage = 1;
                            renderRecentAppointmentsWithPagination();
                        } else {
                            document.getElementById('recent-appointments').innerHTML = 
                                '<div class="empty-state"><p>Error: ' + (data.message || 'Failed to load appointments') + '</p></div>';
                            document.getElementById('recent-appointments-pagination').innerHTML = '';
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        document.getElementById('recent-appointments').innerHTML = 
                            '<div class="empty-state"><p>Error loading appointments. Check console for details.</p></div>';
                        document.getElementById('recent-appointments-pagination').innerHTML = '';
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    document.getElementById('recent-appointments').innerHTML = 
                        '<div class="empty-state"><p>Error: ' + err.message + '</p></div>';
                    document.getElementById('recent-appointments-pagination').innerHTML = '';
                });
        }
        
        function renderRecentAppointmentsWithPagination() {
            const container = document.getElementById('recent-appointments');
            const totalPages = Math.ceil(allRecentAppointments.length / recentAppointmentsPerPage);
            const startIndex = (currentRecentPage - 1) * recentAppointmentsPerPage;
            const endIndex = startIndex + recentAppointmentsPerPage;
            const paginatedAppointments = allRecentAppointments.slice(startIndex, endIndex);
            
            if (paginatedAppointments.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>No recent appointments</p></div>';
                document.getElementById('recent-appointments-pagination').innerHTML = '';
                return;
            }
            
            renderAppointments(paginatedAppointments, 'recent-appointments', true);
            
            // Render pagination controls
            const paginationContainer = document.getElementById('recent-appointments-pagination');
            if (totalPages > 1) {
                let paginationHTML = '';
                
                // Previous button
                paginationHTML += `
                    <button onclick="changeRecentPage(${currentRecentPage - 1})" 
                            class="btn btn-sm" 
                            ${currentRecentPage === 1 ? 'disabled' : ''}
                            style="padding: 8px 12px; ${currentRecentPage === 1 ? 'opacity: 0.5; cursor: not-allowed;' : ''}">
                        ← Previous
                    </button>
                `;
                
                // Page numbers
                for (let i = 1; i <= totalPages; i++) {
                    paginationHTML += `
                        <button onclick="changeRecentPage(${i})" 
                                class="btn btn-sm ${i === currentRecentPage ? 'btn-primary' : ''}" 
                                style="padding: 8px 12px; min-width: 40px;">
                            ${i}
                        </button>
                    `;
                }
                
                // Next button
                paginationHTML += `
                    <button onclick="changeRecentPage(${currentRecentPage + 1})" 
                            class="btn btn-sm" 
                            ${currentRecentPage === totalPages ? 'disabled' : ''}
                            style="padding: 8px 12px; ${currentRecentPage === totalPages ? 'opacity: 0.5; cursor: not-allowed;' : ''}">
                        Next →
                    </button>
                `;
                
                paginationContainer.innerHTML = paginationHTML;
            } else {
                paginationContainer.innerHTML = '';
            }
        }
        
        function changeRecentPage(page) {
            const totalPages = Math.ceil(allRecentAppointments.length / recentAppointmentsPerPage);
            if (page < 1 || page > totalPages) return;
            currentRecentPage = page;
            renderRecentAppointmentsWithPagination();
        }

        // Alias for consistency with delete functions
        function loadAppointments() {
            loadAllAppointments();
        }

        // Pagination state
        let currentAppointmentPage = 1;
        const appointmentsPerPage = 4;

        // Load all appointments
        function loadAllAppointments() {
            const status = document.getElementById('filter-status').value;
            const date = document.getElementById('filter-date').value;
            const sort = document.getElementById('filter-sort').value;
            const search = document.getElementById('filter-search').value.toLowerCase();
            
            let url = 'api/admin/appointments.php?action=getAll';
            if (status) url += `&status=${status}`;
            if (date) url += `&date=${date}`;
            
            fetch(url)
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return r.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            let appointments = data.appointments;
                            
                            // Apply search filter
                            if (search) {
                                appointments = appointments.filter(apt => {
                                    return (apt.patient_name && apt.patient_name.toLowerCase().includes(search)) ||
                                           (apt.patient_email && apt.patient_email.toLowerCase().includes(search)) ||
                                           (apt.patient_phone && apt.patient_phone.toLowerCase().includes(search)) ||
                                           (apt.service_name && apt.service_name.toLowerCase().includes(search)) ||
                                           (apt.doctor_name && apt.doctor_name.toLowerCase().includes(search));
                                });
                            }
                            
                            // Apply sorting
                            appointments = sortAppointments(appointments, sort);
                            
                            // Reset to first page when filters change
                            currentAppointmentPage = 1;
                            
                            renderAppointmentsWithPagination(appointments);
                        } else {
                            document.getElementById('appointments-list').innerHTML = 
                                '<div class="empty-state"><p>Error: ' + (data.message || 'Failed to load appointments') + '</p></div>';
                            document.getElementById('appointments-pagination').innerHTML = '';
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        document.getElementById('appointments-list').innerHTML = 
                            '<div class="empty-state"><p>Error loading appointments. Check console for details.</p></div>';
                        document.getElementById('appointments-pagination').innerHTML = '';
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    document.getElementById('appointments-list').innerHTML = 
                        '<div class="empty-state"><p>Error: ' + err.message + '</p></div>';
                    document.getElementById('appointments-pagination').innerHTML = '';
                });
        }
        
        // Sort appointments based on selected option
        function sortAppointments(appointments, sortType) {
            const sorted = [...appointments];
            
            switch(sortType) {
                case 'latest':
                    // Sort by created_at descending (newest first)
                    sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                    break;
                case 'oldest':
                    // Sort by created_at ascending (oldest first)
                    sorted.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                    break;
                case 'date-asc':
                    // Sort by appointment_date ascending (earliest first)
                    sorted.sort((a, b) => {
                        const dateA = new Date(a.appointment_date + ' ' + a.appointment_time);
                        const dateB = new Date(b.appointment_date + ' ' + b.appointment_time);
                        return dateA - dateB;
                    });
                    break;
                case 'date-desc':
                    // Sort by appointment_date descending (latest first)
                    sorted.sort((a, b) => {
                        const dateA = new Date(a.appointment_date + ' ' + a.appointment_time);
                        const dateB = new Date(b.appointment_date + ' ' + b.appointment_time);
                        return dateB - dateA;
                    });
                    break;
                default:
                    // Default: latest first
                    sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            }
            
            return sorted;
        }

        // Render appointments with pagination
        function renderAppointmentsWithPagination(allAppointments) {
            const totalPages = Math.ceil(allAppointments.length / appointmentsPerPage);
            const startIndex = (currentAppointmentPage - 1) * appointmentsPerPage;
            const endIndex = startIndex + appointmentsPerPage;
            const paginatedAppointments = allAppointments.slice(startIndex, endIndex);
            
            renderAppointments(paginatedAppointments, 'appointments-list', false);
            renderPagination(totalPages, currentAppointmentPage, 'appointments-pagination', (page) => {
                currentAppointmentPage = page;
                renderAppointmentsWithPagination(allAppointments);
            });
        }

        // Generic pagination renderer
        function renderPagination(totalPages, currentPage, containerId, onPageChange) {
            const container = document.getElementById(containerId);
            
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let paginationHTML = `
                <button class="btn btn-sm btn-secondary" ${currentPage === 1 ? 'disabled' : ''} 
                    onclick="changePage(${currentPage - 1}, '${containerId}')">
                    Previous
                </button>
                <span style="padding: 0 16px;">Page ${currentPage} of ${totalPages}</span>
                <button class="btn btn-sm btn-secondary" ${currentPage === totalPages ? 'disabled' : ''} 
                    onclick="changePage(${currentPage + 1}, '${containerId}')">
                    Next
                </button>
            `;
            
            container.innerHTML = paginationHTML;
            
            // Store the callback for this pagination
            window[`pageChangeCallback_${containerId}`] = onPageChange;
        }

        // Page change handler
        function changePage(page, containerId) {
            const callback = window[`pageChangeCallback_${containerId}`];
            if (callback) {
                callback(page);
            }
        }

        // Render appointments
        function renderAppointments(appointments, containerId, isCompact) {
            const container = document.getElementById(containerId);
            
            if (appointments.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>No appointments found</p></div>';
                return;
            }
            
            container.innerHTML = appointments.map(apt => `
                <div class="card" style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 12px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px;">${apt.patient_name}</div>
                            <div style="font-size: 14px; color: var(--text-light); margin-bottom: 4px;">
                                ${apt.service_name ? '� ' + apt.service_name : (apt.doctor_name ? '�‍⚕️ Dr. ' + apt.doctor_name : 'N/A')}
                            </div>
                            <div style="font-size: 13px; color: var(--text-light);">
                                📅 ${formatDate(apt.appointment_date)} at ${apt.appointment_time}
                            </div>
                            ${apt.patient_email ? `<div style="font-size: 12px; color: var(--text-light); margin-top: 4px;">📧 ${apt.patient_email}</div>` : ''}
                            ${apt.status === 'cancelled' && apt.cancel_reason ? `
                                <div style="margin-top: 8px; padding: 8px; background: #fef2f2; border-left: 3px solid #ef4444; border-radius: 4px;">
                                    <div style="font-size: 12px; font-weight: 600; color: #dc2626; margin-bottom: 4px;">Cancellation Reason:</div>
                                    <div style="font-size: 12px; color: #991b1b;">${apt.cancel_reason}</div>
                                    ${apt.cancel_details ? `<div style="font-size: 11px; color: #7f1d1d; margin-top: 4px; font-style: italic;">"${apt.cancel_details}"</div>` : ''}
                                </div>
                            ` : ''}
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-${getStatusColor(apt.status)}">${apt.status}</span>
                            ${!isCompact && apt.status !== 'archived' ? `
                                <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                    ${apt.status === 'cancelled' ? `
                                        <button data-action="archive" data-id="${apt.id}" class="btn btn-sm btn-archive appointment-action-btn">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="21 8 21 21 3 21 3 8"></polyline>
                                                <rect x="1" y="3" width="22" height="5"></rect>
                                                <line x1="10" y1="12" x2="14" y2="12"></line>
                                            </svg>
                                            Archive
                                        </button>
                                    ` : `
                                        ${apt.status === 'pending' ? `
                                            <button data-action="confirm" data-id="${apt.id}" class="btn btn-sm btn-success appointment-action-btn">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                Confirm
                                            </button>
                                        ` : ''}
                                        ${apt.status === 'confirmed' ? `
                                            <button data-action="complete" data-id="${apt.id}" class="btn btn-sm btn-primary appointment-action-btn">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                                </svg>
                                                Complete
                                            </button>
                                        ` : ''}
                                        ${apt.status !== 'completed' ? `
                                            <button data-action="cancel" data-id="${apt.id}" class="btn btn-sm btn-danger appointment-action-btn">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                                </svg>
                                                Cancel
                                            </button>
                                        ` : ''}
                                        <button data-action="archive" data-id="${apt.id}" class="btn btn-sm btn-archive appointment-action-btn">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="21 8 21 21 3 21 3 8"></polyline>
                                                <rect x="1" y="3" width="22" height="5"></rect>
                                                <line x1="10" y1="12" x2="14" y2="12"></line>
                                            </svg>
                                            Archive
                                        </button>
                                    `}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Pagination state for users
        let currentUserPage = 1;
        const usersPerPage = 5;

        // Load users
        function loadUsers() {
            fetch('api/admin/users.php?action=getAll')
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return r.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            allUsers = data.users; // Store globally for pagination
                            currentUserPage = 1; // Reset to first page
                            renderUsers(data.users);
                        } else {
                            document.getElementById('users-list').innerHTML = 
                                '<div class="empty-state"><p>Error: ' + (data.message || 'Failed to load users') + '</p></div>';
                            document.getElementById('users-pagination').innerHTML = '';
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        document.getElementById('users-list').innerHTML = 
                            '<div class="empty-state"><p>Error loading users. Check console for details.</p></div>';
                        document.getElementById('users-pagination').innerHTML = '';
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    document.getElementById('users-list').innerHTML = 
                        '<div class="empty-state"><p>Error: ' + err.message + '</p></div>';
                    document.getElementById('users-pagination').innerHTML = '';
                });
        }

        // Render users with pagination
        function renderUsers(users) {
            const container = document.getElementById('users-list');
            
            if (users.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>No users found</p></div>';
                document.getElementById('users-pagination').innerHTML = '';
                return;
            }
            
            renderUsersWithPagination(users);
        }
        
        function renderUsersWithPagination(users) {
            const container = document.getElementById('users-list');
            const totalPages = Math.ceil(users.length / usersPerPage);
            const startIndex = (currentUserPage - 1) * usersPerPage;
            const endIndex = startIndex + usersPerPage;
            const paginatedUsers = users.slice(startIndex, endIndex);
            
            container.innerHTML = paginatedUsers.map(user => `
                <div class="card" style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px;">${user.first_name} ${user.last_name}</div>
                            <div style="font-size: 14px; color: var(--text-light); margin-bottom: 2px;">📧 ${user.email}</div>
                            <div style="font-size: 14px; color: var(--text-light);">📱 ${user.phone || 'N/A'}</div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-blue">${user.role}</span>
                            <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                <button onclick="changeUserRole(${user.id}, '${user.role}', '${user.first_name} ${user.last_name}')" class="btn btn-sm btn-info">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="8.5" cy="7" r="4"></circle>
                                        <polyline points="17 11 19 13 23 9"></polyline>
                                    </svg>
                                    Role
                                </button>
                                <button onclick="editUser(${user.id})" class="btn btn-sm btn-edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    Edit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Render pagination controls
            const paginationContainer = document.getElementById('users-pagination');
            if (totalPages > 1) {
                let paginationHTML = '';
                
                // Previous button
                paginationHTML += `
                    <button onclick="changeUserPage(${currentUserPage - 1})" 
                            class="btn btn-sm" 
                            ${currentUserPage === 1 ? 'disabled' : ''}
                            style="padding: 8px 12px; ${currentUserPage === 1 ? 'opacity: 0.5; cursor: not-allowed;' : ''}">
                        ← Previous
                    </button>
                `;
                
                // Page numbers
                for (let i = 1; i <= totalPages; i++) {
                    paginationHTML += `
                        <button onclick="changeUserPage(${i})" 
                                class="btn btn-sm ${i === currentUserPage ? 'btn-primary' : ''}" 
                                style="padding: 8px 12px; min-width: 40px;">
                            ${i}
                        </button>
                    `;
                }
                
                // Next button
                paginationHTML += `
                    <button onclick="changeUserPage(${currentUserPage + 1})" 
                            class="btn btn-sm" 
                            ${currentUserPage === totalPages ? 'disabled' : ''}
                            style="padding: 8px 12px; ${currentUserPage === totalPages ? 'opacity: 0.5; cursor: not-allowed;' : ''}">
                        Next →
                    </button>
                `;
                
                paginationContainer.innerHTML = paginationHTML;
            } else {
                paginationContainer.innerHTML = '';
            }
        }
        
        function changeUserPage(page) {
            const totalPages = Math.ceil(allUsers.length / usersPerPage);
            if (page < 1 || page > totalPages) return;
            currentUserPage = page;
            renderUsersWithPagination(allUsers);
        }
        
        let allUsers = [];

        // Load doctors
        function loadDoctors() {
            fetch('api/admin/doctors.php?action=getAll')
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return r.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            renderDoctors(data.doctors);
                        } else {
                            document.getElementById('doctors-list').innerHTML = 
                                '<div class="empty-state"><p>Error: ' + (data.message || 'Failed to load doctors') + '</p></div>';
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        document.getElementById('doctors-list').innerHTML = 
                            '<div class="empty-state"><p>Error loading doctors. Check console for details.</p></div>';
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    document.getElementById('doctors-list').innerHTML = 
                        '<div class="empty-state"><p>Error: ' + err.message + '</p></div>';
                });
        }

        // Render doctors
        function renderDoctors(doctors) {
            const container = document.getElementById('doctors-list');
            
            if (doctors.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <p style="font-size: 16px; margin-bottom: 8px;">No doctors found</p>
                        <p style="font-size: 14px; color: var(--text-light); margin-bottom: 16px;">Get started by adding your first doctor to the system</p>
                        <button class="btn btn-primary" onclick="showAddDoctorModal()">
                            <span>+</span>
                            <span>Add First Doctor</span>
                        </button>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = doctors.map(doctor => {
                const doctorName = doctor.first_name && doctor.last_name ? `Dr. ${doctor.first_name} ${doctor.last_name}` : (doctor.first_name ? `Dr. ${doctor.first_name}` : (doctor.last_name ? `Dr. ${doctor.last_name}` : `Dr. ${doctor.specialty || 'Unknown'}`));
                const initials = doctor.first_name && doctor.last_name ? doctor.first_name[0] + doctor.last_name[0] : 'DR';
                
                return `
                <div class="card" style="margin-bottom: 12px;">
                    <div style="display: flex; gap: 16px; align-items: start;">
                        <!-- Doctor Profile Image -->
                        <div style="position: relative; flex-shrink: 0;">
                            ${doctor.profile_image ? 
                                `<img src="${doctor.profile_image}?v=${Date.now()}" alt="${doctorName}" 
                                     style="width: 80px; height: 80px; border-radius: 12px; object-fit: cover; border: 2px solid #e5e7eb;"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                 <div style="display: none; width: 80px; height: 80px; border-radius: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                                           align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 24px; border: 2px solid #e5e7eb;">
                                    ${initials}
                                 </div>` 
                                : 
                                `<div style="width: 80px; height: 80px; border-radius: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                                           display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 24px; border: 2px solid #e5e7eb;">
                                    ${initials}
                                 </div>`
                            }
                            <input type="file" id="doctorImage_${doctor.id}" accept="image/*" style="display: none;" 
                                   onchange="uploadDoctorImage(${doctor.id}, this)">
                            <button onclick="document.getElementById('doctorImage_${doctor.id}').click()" 
                                    class="btn btn-sm" 
                                    style="position: absolute; bottom: -8px; right: -8px; width: 32px; height: 32px; padding: 0; 
                                           background: white; border: 2px solid #e5e7eb; border-radius: 50%; font-size: 16px;"
                                    title="Upload photo">
                                📷
                            </button>
                        </div>
                        
                        <!-- Doctor Info -->
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px;">
                                ${doctorName}
                            </div>
                            <div style="font-size: 14px; color: var(--text-light); margin-bottom: 2px;">🏥 ${doctor.specialty}</div>
                            <div style="font-size: 14px; color: var(--text-light); margin-bottom: 2px;">📍 ${doctor.department || 'N/A'}</div>
                            <div style="font-size: 13px; color: var(--text-light);">
                                ${doctor.experience_years ? doctor.experience_years + ' years experience' : ''} 
                                ${doctor.consultation_fee ? '• ₱' + doctor.consultation_fee : ''}
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div style="text-align: right;">
                            <div style="font-size: 14px; color: ${doctor.is_available == 1 ? '#22c55e' : '#6b7280'}; font-weight: 600; margin-bottom: 4px;">
                                ${doctor.is_available == 1 ? '✓ Available' : '✗ Unavailable'}
                            </div>
                            <div style="font-size: 13px; color: var(--text-light); margin-bottom: 8px;">
                                ${doctor.total_appointments} bookings
                            </div>
                            <div style="display: flex; gap: 6px;">
                                <button onclick="editDoctor(${doctor.id})" class="btn btn-sm btn-edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    Edit
                                </button>
                                <button onclick="archiveDoctor(${doctor.id})" class="btn btn-sm btn-archive">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="21 8 21 21 3 21 3 8"></polyline>
                                        <rect x="1" y="3" width="22" height="5"></rect>
                                        <line x1="10" y1="12" x2="14" y2="12"></line>
                                    </svg>
                                    Archive
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `}).join('');
        }
        
        // Upload doctor image
        async function uploadDoctorImage(doctorId, input) {
            if (!input.files || !input.files[0]) return;
            
            const file = input.files[0];
            const formData = new FormData();
            formData.append('doctor_image', file);
            formData.append('doctor_id', doctorId);
            
            try {
                const response = await fetch('api/admin/upload-doctor-image.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', 'Success', 'Doctor photo updated successfully!');
                    loadDoctors(); // Reload to show new image
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                console.error('Upload error:', error);
                showToast('error', 'Error', 'Failed to upload photo. Please try again.');
            }
        }

        // Load services
        function loadServices() {
            fetch('api/admin/services.php?action=getAll')
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return r.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            renderServices(data.services);
                        } else {
                            document.getElementById('services-list').innerHTML = 
                                '<div class="empty-state"><p>Error: ' + (data.message || 'Failed to load services') + '</p></div>';
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        document.getElementById('services-list').innerHTML = 
                            '<div class="empty-state"><p>Error loading services. Check console for details.</p></div>';
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    document.getElementById('services-list').innerHTML = 
                        '<div class="empty-state"><p>Error: ' + err.message + '</p></div>';
                });
        }

        // Render services
        function renderServices(services) {
            const container = document.getElementById('services-list');
            
            if (services.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>No services found</p></div>';
                return;
            }
            
            container.innerHTML = services.map(service => `
                <div class="card" style="margin-bottom: 16px; padding: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <h3 style="font-weight: 600; font-size: 16px; margin: 0; color: #1f2937;">${service.name}</h3>
                                <span class="badge badge-${service.is_active == 1 ? 'green' : 'gray'}" style="font-size: 11px;">
                                    ${service.is_active == 1 ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 6px;">
                                <span style="font-size: 13px; color: #6b7280;">
                                    <span style="font-weight: 500;">📂</span> ${service.category}
                                </span>
                                <span style="font-size: 13px; color: #6b7280;">
                                    <span style="font-weight: 500;">⏱️</span> ${service.duration_minutes} min
                                </span>
                                ${service.requires_doctor == 1 ? '<span style="font-size: 13px; color: #6b7280;">👨‍⚕️ Requires Doctor</span>' : ''}
                            </div>
                            <div style="font-size: 12px; color: #9ca3af;">
                                📊 ${service.total_bookings || 0} bookings
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px; min-width: 180px;">
                            <div style="font-size: 20px; font-weight: 700; color: var(--primary-color);">
                                ₱${parseFloat(service.base_cost).toFixed(2)}
                            </div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                <button onclick="toggleServiceActive(${service.id})" 
                                        class="btn btn-sm ${service.is_active == 1 ? 'btn-archive' : 'btn-success'}"
                                        title="${service.is_active == 1 ? 'Deactivate' : 'Activate'}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;">
                                        ${service.is_active == 1 ? '<path d="M5 13l4 4L19 7"></path>' : '<path d="M18 6L6 18M6 6l12 12"></path>'}
                                    </svg>
                                    ${service.is_active == 1 ? 'Active' : 'Inactive'}
                                </button>
                                <button onclick="editService(${service.id})" class="btn btn-sm btn-edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    Edit
                                </button>
                                <button onclick="archiveService(${service.id})" class="btn btn-sm btn-archive">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="21 8 21 21 3 21 3 8"></polyline>
                                        <rect x="1" y="3" width="22" height="5"></rect>
                                        <line x1="10" y1="12" x2="14" y2="12"></line>
                                    </svg>
                                    Archive
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Load log data
        function loadLogData(tab) {
            const fromDate = document.getElementById('log-from-date').value;
            const toDate = document.getElementById('log-to-date').value;
            
            let url = `api/admin/get-logs.php?type=${tab}`;
            if (fromDate) url += `&from=${fromDate}`;
            if (toDate) url += `&to=${toDate}`;
            
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        renderLogs(data.logs, tab);
                    }
                });
        }

        // Render logs
        function renderLogs(logs, tab) {
            const container = document.getElementById(tab + '-list');
            
            if (logs.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>No logs found</p></div>';
                return;
            }
            
            container.innerHTML = logs.map(log => `
                <div class="card" style="margin-bottom: 12px;">
                    <div style="font-size: 14px; color: var(--text-light); margin-bottom: 4px;">
                        ${formatDateTime(log.created_at)}
                    </div>
                    <div style="font-weight: 500; margin-bottom: 2px;">${log.action}</div>
                    <div style="font-size: 14px; color: var(--text-light);">${log.details}</div>
                </div>
            `).join('');
        }

        // Update appointment status
        function updateAppointmentStatus(id, status) {
            showConfirm('info', `${status.charAt(0).toUpperCase() + status.slice(1)} Appointment?`, `Are you sure you want to ${status} this appointment?`, () => {
                const formData = new FormData();
                formData.append('action', 'updateStatus');
                formData.append('id', id);
                formData.append('status', status);
                
                fetch('api/admin/appointments.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadAllAppointments();
                        showToast('success', 'Success', 'Appointment updated successfully!');
                    } else {
                        showToast('error', 'Error', data.message || 'Failed to update appointment');
                    }
                })
                .catch(err => {
                    console.error('Update appointment error:', err);
                    showToast('error', 'Error', 'An error occurred while updating the appointment.');
                });
            });
        }

        // Helper functions
        function formatDate(date) {
            return new Date(date).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }

        function formatDateTime(datetime) {
            return new Date(datetime).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function getStatusColor(status) {
            const colors = {
                'pending': 'orange',
                'confirmed': 'blue',
                'completed': 'green',
                'cancelled': 'gray',
                'archived': 'secondary'
            };
            return colors[status] || 'gray';
        }

        // Event delegation for appointment action buttons
        document.getElementById('appointments-list').addEventListener('click', function(e) {
            const btn = e.target.closest('.appointment-action-btn');
            if (!btn) return;
            
            e.preventDefault(); // Prevent any default behavior
            
            const action = btn.dataset.action;
            const id = btn.dataset.id;
            
            console.log('Appointment action clicked:', action, 'ID:', id); // Debug logging
            
            if (action === 'archive') {
                archiveAppointment(id);
            } else if (action === 'confirm') {
                updateAppointmentStatus(id, 'confirmed');
            } else if (action === 'complete') {
                updateAppointmentStatus(id, 'completed');
            } else if (action === 'cancel') {
                updateAppointmentStatus(id, 'cancelled');
            }
        });

        // Filter listeners
        document.getElementById('filter-status').addEventListener('change', loadAllAppointments);
        document.getElementById('filter-date').addEventListener('change', loadAllAppointments);
        document.getElementById('filter-sort').addEventListener('change', loadAllAppointments);
        
        // Search filter with debounce
        let appointmentSearchTimeout;
        document.getElementById('filter-search').addEventListener('input', function() {
            clearTimeout(appointmentSearchTimeout);
            appointmentSearchTimeout = setTimeout(() => {
                loadAllAppointments();
            }, 300);
        });
        
        document.getElementById('log-from-date').addEventListener('change', () => loadLogData('appointment-history'));
        document.getElementById('log-to-date').addEventListener('change', () => loadLogData('appointment-history'));

        // Search users
        let searchTimeout;
        document.getElementById('search-users').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = this.value.toLowerCase();
                const users = document.querySelectorAll('#users-list .card');
                users.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    card.style.display = text.includes(query) ? 'block' : 'none';
                });
            }, 300);
        });

        // Form submissions
        document.getElementById('business-hours-form').addEventListener('submit', function(e) {
            e.preventDefault();
            showToast('success', 'Saved', 'Business hours settings saved!');
        });

        document.getElementById('notification-settings-form').addEventListener('submit', function(e) {
            e.preventDefault();
            showToast('success', 'Saved', 'Notification settings saved!');
        });

        // User Management Functions
        function showAddUserModal() { 
            document.getElementById('addUserModal').style.display = 'flex';
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
            document.getElementById('addUserForm').reset();
        }
        
        function editUser(id) {
            // Show loading modal
            const modal = document.getElementById('editUserModal');
            const formContainer = document.getElementById('editUserFormContainer');
            formContainer.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div><p>Loading user data...</p></div>';
            modal.style.display = 'flex';
            
            // Fetch user data from the getAll endpoint and find the user
            fetch(`api/admin/users.php?action=getAll&limit=1000`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.users) {
                        const user = data.users.find(u => u.id == id);
                        if (user) {
                            formContainer.innerHTML = `
                                <form id="editUserForm" onsubmit="submitEditUser(event, ${id})">
                                    <div style="display: grid; gap: 16px;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                            <div>
                                                <label class="form-label">First Name *</label>
                                                <input type="text" name="first_name" class="form-control" value="${user.first_name || ''}" required>
                                            </div>
                                            <div>
                                                <label class="form-label">Last Name *</label>
                                                <input type="text" name="last_name" class="form-control" value="${user.last_name || ''}" required>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label">Email *</label>
                                            <input type="email" name="email" class="form-control" value="${user.email || ''}" required>
                                        </div>
                                        <div>
                                            <label class="form-label">Phone</label>
                                            <input type="text" name="phone" class="form-control" value="${user.phone || ''}">
                                        </div>
                                        <div style="display: flex; gap: 12px; margin-top: 12px;">
                                            <button type="submit" class="btn btn-primary" style="flex: 1;">Update User</button>
                                            <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeEditUserModal()">Cancel</button>
                                        </div>
                                    </div>
                                </form>
                            `;
                        } else {
                            formContainer.innerHTML = '<div class="empty-state"><p>User not found</p></div>';
                        }
                    } else {
                        formContainer.innerHTML = '<div class="empty-state"><p>Error loading user data</p></div>';
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    formContainer.innerHTML = '<div class="empty-state"><p>Error loading user data</p></div>';
                });
        }
        
        function submitEditUser(event, userId) {
            event.preventDefault();
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);
            formData.append('action', 'update');
            formData.append('id', userId);
            
            fetch('api/admin/users.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Success', 'User updated successfully!');
                    closeEditUserModal();
                    loadUsers();
                } else {
                    showToast('error', 'Error', data.message || 'Failed to update user');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('error', 'Error', 'An error occurred while updating the user.');
            });
        }
        
        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }
        
        function archiveUser(id) {
            showConfirm('warning', 'Archive User?', 'They will no longer be able to access their account.', () => {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('api/admin/users.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Archived', 'User archived successfully!');
                        loadUsers(); // Reload users list
                    } else {
                        showToast('error', 'Error', data.message || 'Failed to archive user');
                    }
                })
                .catch(err => {
                    console.error('Archive user error:', err);
                    showToast('error', 'Error', 'An error occurred while archiving the user.');
                });
            });
        }

        function changeUserRole(userId, currentRole, userName) {
            // Populate modal with user data
            document.getElementById('changeRoleUserId').value = userId;
            document.getElementById('changeRoleUserName').textContent = userName;
            document.getElementById('changeRoleCurrentRole').textContent = currentRole.charAt(0).toUpperCase() + currentRole.slice(1);
            
            // Set dropdown options (remove current role from selection or disable it)
            const dropdown = document.getElementById('changeRoleNewRole');
            dropdown.value = ''; // Reset selection
            
            // Optionally disable the current role option
            Array.from(dropdown.options).forEach(option => {
                if (option.value === currentRole) {
                    option.disabled = true;
                    option.text = option.text.replace(' (Current)', '') + ' (Current)';
                } else {
                    option.disabled = false;
                    option.text = option.text.replace(' (Current)', '');
                }
            });
            
            // Show modal
            document.getElementById('changeRoleModal').style.display = 'flex';
        }

        function closeChangeRoleModal() {
            document.getElementById('changeRoleModal').style.display = 'none';
            document.getElementById('changeRoleForm').reset();
        }
        
        // Doctor Management Functions
        function showAddDoctorModal() {
            document.getElementById('addDoctorModal').style.display = 'flex';
        }
        
        function closeAddDoctorModal() {
            document.getElementById('addDoctorModal').style.display = 'none';
            document.getElementById('addDoctorForm').reset();
            // Reset image preview
            document.getElementById('add_doctor_image_preview').innerHTML = '<span style="font-size: 32px; color: #9ca3af;">DR</span>';
        }
        
        function editDoctor(id) {
            // Fetch doctor data and populate edit modal
            fetch(`api/admin/doctors.php?action=getAll&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.doctors && data.doctors.length > 0) {
                        const doctor = data.doctors[0];
                        
                        // Populate form fields
                        document.getElementById('edit_doctor_id').value = doctor.id;
                        document.getElementById('edit_first_name').value = doctor.first_name || '';
                        document.getElementById('edit_last_name').value = doctor.last_name || '';
                        document.getElementById('edit_email').value = doctor.email || '';
                        document.getElementById('edit_phone').value = doctor.phone || '';
                        document.getElementById('edit_license_number').value = doctor.license_number || '';
                        document.getElementById('edit_specialty').value = doctor.specialty || '';
                        document.getElementById('edit_department').value = doctor.department || '';
                        document.getElementById('edit_experience_years').value = doctor.experience_years || 0;
                        document.getElementById('edit_consultation_fee').value = doctor.consultation_fee || '';
                        document.getElementById('edit_qualification').value = doctor.qualification || '';
                        document.getElementById('edit_bio').value = doctor.bio || '';
                        document.getElementById('edit_is_available').checked = doctor.is_available == 1;
                        
                        // Load profile image
                        const imagePreview = document.getElementById('edit_doctor_image_preview');
                        if (doctor.profile_image && doctor.profile_image !== 'null' && doctor.profile_image !== '') {
                            imagePreview.innerHTML = `<img src="${doctor.profile_image}" style="width: 100%; height: 100%; object-fit: cover;">`;
                        } else {
                            const initials = ((doctor.first_name || '').charAt(0) + (doctor.last_name || '').charAt(0)).toUpperCase() || 'DR';
                            imagePreview.innerHTML = `<span style="font-size: 32px; color: #9ca3af;">${initials}</span>`;
                        }
                        
                        // Show modal
                        document.getElementById('editDoctorModal').style.display = 'flex';
                    } else {
                        showToast('error', 'Error', 'Could not load doctor data');
                    }
                })
                .catch(err => {
                    console.error('Error loading doctor:', err);
                    showToast('error', 'Error', 'An error occurred while loading doctor data.');
                });
        }

        function closeEditDoctorModal() {
            document.getElementById('editDoctorModal').style.display = 'none';
            document.getElementById('editDoctorForm').reset();
        }
        
        function archiveDoctor(id) {
            showConfirm('warning', 'Archive Doctor?', 'This will disable their account and they will no longer appear in active listings.', () => {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('api/admin/doctors.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Archived', 'Doctor archived successfully!');
                        loadDoctors(); // Reload doctors list
                    } else {
                        showToast('error', 'Error', data.message || 'Failed to archive doctor');
                    }
                })
                .catch(err => {
                    console.error('Archive doctor error:', err);
                    showToast('error', 'Error', 'An error occurred while archiving the doctor.');
                });
            });
        }
        
        // Service Management Functions
        function showAddServiceModal() { 
            document.getElementById('addServiceModal').style.display = 'flex';
        }

        function closeAddServiceModal() {
            document.getElementById('addServiceModal').style.display = 'none';
            document.getElementById('addServiceForm').reset();
        }
        
        function editService(id) {
            // Fetch service data
            fetch(`api/admin/services.php?action=getById&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const service = data.service;
                        
                        // Populate form fields
                        document.getElementById('editServiceId').value = service.id;
                        document.getElementById('editServiceName').value = service.name;
                        document.getElementById('editServiceDescription').value = service.description;
                        document.getElementById('editServiceCategory').value = service.category;
                        document.getElementById('editServiceDuration').value = service.duration_minutes;
                        document.getElementById('editServiceCost').value = service.base_cost;
                        document.getElementById('editServiceRequiresDoctor').checked = service.requires_doctor == 1;
                        document.getElementById('editServicePreparation').value = service.preparation_instructions || '';
                        document.getElementById('editServiceActive').checked = service.is_active == 1;
                        
                        // Show modal
                        document.getElementById('editServiceModal').style.display = 'flex';
                    } else {
                        showToast('error', 'Error', data.message || 'Failed to load service');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Error', 'Failed to load service');
                });
        }

        function closeEditServiceModal() {
            document.getElementById('editServiceModal').style.display = 'none';
            document.getElementById('editServiceForm').reset();
        }
        
        function archiveService(id) {
            showConfirm('warning', 'Archive Service?', 'It will no longer be available for new bookings.', () => {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('api/admin/services.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Archived', 'Service archived successfully!');
                        loadServices(); // Reload services list
                    } else {
                        showToast('error', 'Error', data.message || 'Failed to archive service');
                    }
                })
                .catch(err => {
                    console.error('Archive service error:', err);
                    showToast('error', 'Error', 'An error occurred while archiving the service.');
                });
            });
        }
        
        function toggleServiceActive(id) {
            showConfirm('info', 'Toggle Service Status?', 'This will change the service availability.', () => {
                const formData = new FormData();
                formData.append('action', 'toggleActive');
                formData.append('id', id);
                
                fetch('api/admin/services.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Success', 'Service status updated successfully!');
                        loadServices(); // Reload services list
                    } else {
                        showToast('error', 'Error', data.message || 'Failed to update service status');
                    }
                })
                .catch(err => {
                    console.error('Toggle service error:', err);
                    showToast('error', 'Error', 'An error occurred while updating the service status.');
                });
            });
        }
        
        // Appointment Management Functions
        function archiveAppointment(id) {
            showConfirm('warning', 'Archive Appointment?', 'This appointment will be moved to archives.', () => {
                const formData = new FormData();
                formData.append('action', 'archive');
                formData.append('id', id);
                
                fetch('api/admin/appointments.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Archived', 'Appointment archived successfully!');
                        loadAppointments(); // Reload appointments list
                    } else {
                        showToast('error', 'Error', data.message || 'Failed to archive appointment');
                    }
                })
                .catch(err => {
                    console.error('Archive appointment error:', err);
                    showToast('error', 'Error', 'An error occurred while archiving the appointment.');
                });
            });
        }

        // User Menu Dropdown
        const userMenuButton = document.getElementById('userMenuButton');
        const userMenuDropdown = document.getElementById('userMenuDropdown');

        userMenuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenuDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenuDropdown.contains(e.target) && !userMenuButton.contains(e.target)) {
                userMenuDropdown.classList.remove('active');
            }
        });

        // Handle dropdown menu item clicks (for settings link)
        userMenuDropdown.querySelectorAll('a[data-section]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.dataset.section;
                
                userMenuDropdown.classList.remove('active');
                document.querySelectorAll('.admin-nav-item').forEach(nav => nav.classList.remove('active'));
                document.querySelectorAll('.admin-section').forEach(sec => sec.classList.remove('active'));
                
                // Update active nav item
                document.querySelector(`.admin-nav-item[data-section="${section}"]`).classList.add('active');
                document.getElementById(section + '-section').classList.add('active');
                
                closeSidebarFunc();
            });
        });

        // Show Profile Section
        function showProfileSection() {
            userMenuDropdown.classList.remove('active');
            document.querySelectorAll('.admin-nav-item').forEach(nav => nav.classList.remove('active'));
            document.querySelectorAll('.admin-section').forEach(sec => sec.classList.remove('active'));
            document.getElementById('profile-section').classList.add('active');
            closeSidebarFunc();
        }

        // Profile Form Submission
        document.getElementById('profile-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
            
            try {
                const response = await fetch('api/update-profile.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', 'Success', 'Profile updated successfully!');
                    // Update the display name in the topnav
                    location.reload();
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                showToast('error', 'Error', 'An error occurred while updating your profile. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });

        // Change Password Form Submission
        document.getElementById('change-password-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Validate passwords match
            if (formData.get('new_password') !== formData.get('confirm_password')) {
                showToast('error', 'Error', 'New passwords do not match!');
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';
            
            try {
                const response = await fetch('api/change-password.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('success', 'Success', 'Password updated successfully!');
                    this.reset();
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                console.error('Error changing password:', error);
                showToast('error', 'Error', 'An error occurred while changing your password. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });

        // Reset Profile Form
        function resetProfileForm() {
            document.getElementById('profile-form').reset();
        }

        // Reset Password Form
        function resetPasswordForm() {
            document.getElementById('change-password-form').reset();
        }

        // Logout Confirmation
        function confirmLogout() {
            showConfirm('info', 'Logout?', 'Are you sure you want to logout?', () => {
                window.location.href = 'logout.php';
            });
        }

        // Add Doctor Form Submission
        document.getElementById('addDoctorForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';
            
            try {
                const response = await fetch('api/admin/doctors.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const doctorName = formData.get('first_name') + ' ' + formData.get('last_name');
                    
                    // Upload profile image if selected
                    const imageInput = document.getElementById('add_doctor_image_input');
                    const doctorId = result.doctor_id || result.id; // Get the newly created doctor ID
                    
                    if (imageInput.files && imageInput.files[0] && doctorId) {
                        const imageFormData = new FormData();
                        imageFormData.append('doctor_image', imageInput.files[0]);
                        imageFormData.append('doctor_id', doctorId);
                        
                        const imageResponse = await fetch('api/admin/upload-doctor-image.php', {
                            method: 'POST',
                            body: imageFormData
                        });
                        
                        const imageResult = await imageResponse.json();
                        if (!imageResult.success) {
                            console.error('Image upload failed:', imageResult.message);
                        }
                    }
                    
                    const message = result.message || `Doctor ${doctorName} added successfully!`;
                    showToast('success', 'Success', message);
                    closeAddDoctorModal();
                    loadDoctors(); // Reload doctors list
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                console.error('Error adding doctor:', error);
                showToast('error', 'Error', 'An error occurred while adding the doctor. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Add Doctor';
            }
        });

        // Edit Doctor Form Handler
        document.getElementById('editDoctorForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';
            
            try {
                // First update doctor info
                const response = await fetch('api/admin/doctors.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Then upload image if selected
                    const imageInput = document.getElementById('edit_doctor_image_input');
                    const doctorId = document.getElementById('edit_doctor_id').value;
                    
                    if (imageInput.files && imageInput.files[0]) {
                        const imageFormData = new FormData();
                        imageFormData.append('doctor_image', imageInput.files[0]);
                        imageFormData.append('doctor_id', doctorId);
                        
                        const imageResponse = await fetch('api/admin/upload-doctor-image.php', {
                            method: 'POST',
                            body: imageFormData
                        });
                        
                        const imageResult = await imageResponse.json();
                        if (!imageResult.success) {
                            console.error('Image upload failed:', imageResult.message);
                        }
                    }
                    
                    showToast('success', 'Success', result.message || 'Doctor updated successfully!');
                    closeEditDoctorModal();
                    loadDoctors(); // Reload doctors list
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                console.error('Error updating doctor:', error);
                showToast('error', 'Error', 'An error occurred while updating the doctor. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Update Doctor';
            }
        });

        // Add Service Form Handler
        document.getElementById('addServiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';
            
            try {
                const response = await fetch('api/admin/services.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const serviceName = formData.get('name');
                    showToast('success', 'Success', `Service "${serviceName}" added successfully!`);
                    closeAddServiceModal();
                    loadServices(); // Reload services list
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                console.error('Error adding service:', error);
                showToast('error', 'Error', 'An error occurred while adding the service. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Add Service';
            }
        });

        // Edit Service Form Handler
        document.getElementById('editServiceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';
            
            try {
                const response = await fetch('api/admin/services.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const serviceName = formData.get('name');
                    showToast('success', 'Success', `Service "${serviceName}" updated successfully!`);
                    closeEditServiceModal();
                    loadServices(); // Reload services list
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                console.error('Error updating service:', error);
                showToast('error', 'Error', 'An error occurred while updating the service. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Update Service';
            }
        });

        // Add User Form Handler
        document.getElementById('addUserForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';
            
            try {
                const response = await fetch('api/admin/users.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const username = formData.get('username');
                    showToast('success', 'Success', `User "${username}" added successfully!`);
                    closeAddUserModal();
                    loadUsers(); // Reload users list
                } else {
                    showToast('error', 'Error', result.message);
                }
            } catch (error) {
                console.error('Error adding user:', error);
                showToast('error', 'Error', 'An error occurred while adding the user. Please try again.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Add User';
            }
        });

        // Change Role Form Handler
        document.getElementById('changeRoleForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const userId = document.getElementById('changeRoleUserId').value;
            const newRole = document.getElementById('changeRoleNewRole').value;
            const userName = document.getElementById('changeRoleUserName').textContent;
            const currentRole = document.getElementById('changeRoleCurrentRole').textContent.toLowerCase();
            
            if (!newRole) {
                showToast('warning', 'Warning', 'Please select a new role');
                return;
            }
            
            if (newRole === currentRole) {
                showToast('info', 'Info', 'User already has this role.');
                return;
            }
            
            showConfirm('info', 'Change Role?', `Are you sure you want to change ${userName}'s role to "${newRole}"?`, () => {
                (async () => {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Changing...';
                    
                    try {
                        const formData = new FormData();
                        formData.append('action', 'changeRole');
                        formData.append('id', userId);
                        formData.append('role', newRole);
                        
                        const response = await fetch('api/admin/users.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            showToast('success', 'Success', result.message || 'User role changed successfully!');
                            closeChangeRoleModal();
                            loadUsers(); // Reload users list
                        } else {
                            showToast('error', 'Error', result.message);
                        }
                    } catch (error) {
                        console.error('Error changing role:', error);
                        showToast('error', 'Error', 'An error occurred while changing the user role. Please try again.');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Change Role';
                    }
                })();
            });
        });

        // Image preview for edit doctor modal
        document.getElementById('edit_doctor_image_input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('edit_doctor_image_preview').innerHTML = 
                        `<img src="${event.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Image preview for add doctor modal
        document.getElementById('add_doctor_image_input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('add_doctor_image_preview').innerHTML = 
                        `<img src="${event.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Custom Toast Notification System
        function showToast(type, title, message = '', duration = 3000) {
            const container = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icons = {
                success: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M16.667 5L7.5 14.167 3.333 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                error: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M15 5L5 15M5 5l10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                warning: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 6v4m0 4h.01M8.618 2.243a1.5 1.5 0 012.764 0l7.5 16A1.5 1.5 0 0117.5 21h-15a1.5 1.5 0 01-1.382-2.757l7.5-16z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                info: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2"/><path d="M10 10v4m0-8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
            };
            
            toast.innerHTML = `
                <div class="toast-icon">${icons[type]}</div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    ${message ? `<div class="toast-message">${message}</div>` : ''}
                </div>
                <button class="toast-close">×</button>
            `;
            
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Close button handler
            const closeBtn = toast.querySelector('.toast-close');
            closeBtn.addEventListener('click', () => hideToast(toast));
            
            // Auto-hide after duration
            if (duration > 0) {
                setTimeout(() => hideToast(toast), duration);
            }
            
            return toast;
        }

        function hideToast(toastElement) {
            toastElement.classList.remove('show');
            setTimeout(() => toastElement.remove(), 300);
        }

        // Custom Confirm Dialog System
        function showConfirm(type, title, message, onConfirm, onCancel = null) {
            console.log('showConfirm called:', type, title); // Debug logging
            
            const overlay = document.getElementById('confirmOverlay');
            const dialog = overlay.querySelector('.confirm-dialog');
            
            const icons = {
                warning: '<svg width="48" height="48" viewBox="0 0 48 48" fill="none"><path d="M24 16v8m0 8h.02M19.237 8.486a5 5 0 019.526 0l13.5 36A5 5 0 0137.5 52h-27a5 5 0 01-4.763-7.514l13.5-36z" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                danger: '<svg width="48" height="48" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="3"/><path d="M30 18L18 30M18 18l12 12" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>',
                info: '<svg width="48" height="48" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="3"/><path d="M24 24v8m0-16h.02" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>',
                success: '<svg width="48" height="48" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="3"/><path d="M32 18L20 30l-6-6" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            };
            
            // Update content
            document.getElementById('confirmIcon').className = `confirm-icon ${type}`;
            document.getElementById('confirmIcon').innerHTML = icons[type];
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmMessage').textContent = message;
            
            // Setup button handlers
            const cancelBtn = document.getElementById('confirmCancel');
            const okBtn = document.getElementById('confirmOk');
            
            // Remove old listeners by cloning
            const newCancelBtn = cancelBtn.cloneNode(true);
            const newOkBtn = okBtn.cloneNode(true);
            cancelBtn.replaceWith(newCancelBtn);
            okBtn.replaceWith(newOkBtn);
            
            newCancelBtn.addEventListener('click', () => {
                hideConfirm();
                if (onCancel) onCancel();
            });
            
            newOkBtn.addEventListener('click', () => {
                hideConfirm();
                if (onConfirm) onConfirm();
            });
            
            // Show overlay and dialog
            overlay.classList.add('active');
            
            // Close on overlay click
            const overlayClickHandler = (e) => {
                if (e.target === overlay) {
                    hideConfirm();
                    if (onCancel) onCancel();
                    overlay.removeEventListener('click', overlayClickHandler);
                }
            };
            overlay.addEventListener('click', overlayClickHandler);
        }

        function hideConfirm() {
            const overlay = document.getElementById('confirmOverlay');
            overlay.classList.remove('active');
        }

        // Load initial data
        loadRecentAppointments();
    </script>
</body>
</html>
