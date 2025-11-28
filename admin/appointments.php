<?php
$currentPage = 'appointments';
require_once 'header.php';
?>

<div class="section-header">
    <h2 class="section-title">Appointment Management</h2>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px;">
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
        <div>
            <label for="filter-status" style="display: block; font-size: 13px; margin-bottom: 6px; color: var(--text-light);">Status</label>
            <select id="filter-status" class="form-control">
                <option value="">All Statuses</option>
                <option value="pending" selected>Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="archived">Archived</option>
            </select>
        </div>
        <div>
            <label for="filter-reschedule" style="display: block; font-size: 13px; margin-bottom: 6px; color: var(--text-light);">Reschedule Status</label>
            <select id="filter-reschedule" class="form-control">
                <option value="">All</option>
                <option value="pending">Pending Reschedules</option>
                <option value="approved">Approved Reschedules</option>
                <option value="declined">Declined Reschedules</option>
            </select>
        </div>
        <div>
            <label for="filter-date" style="display: block; font-size: 13px; margin-bottom: 6px; color: var(--text-light);">Date</label>
            <input type="date" id="filter-date" class="form-control" value="">
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

<!-- Appointment Details Modal -->
<div id="appointmentDetailsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 20px; font-weight: 600;">Appointment Details</h3>
                <button onclick="closeAppointmentDetails()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; padding: 0; width: 32px; height: 32px;">×</button>
            </div>
        </div>
        <div id="appointmentDetailsContent" style="padding: 24px;">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Decline Reason Modal -->
<div id="declineReasonModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 10001; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 24px; max-width: 400px; width: 90%; animation: scaleIn 0.2s ease;">
        <div style="width: 48px; height: 48px; border-radius: 50%; background: #fef3c7; color: #f59e0b; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 24px;">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none"><path d="M24 16v8m0 8h.02M19.237 8.486a5 5 0 019.526 0l13.5 36A5 5 0 0137.5 52h-27a5 5 0 01-4.763-7.514l13.5-36z" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h3 style="font-size: 18px; font-weight: 600; color: #1f2937; text-align: center; margin-bottom: 8px;">Decline Reschedule Request</h3>
        <p style="font-size: 14px; color: #6b7280; text-align: center; line-height: 1.5; margin-bottom: 16px;">Please provide a reason for declining this reschedule request:</p>
        <textarea id="declineReasonInput" rows="4" placeholder="Enter your reason here..." style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; font-family: inherit; resize: vertical; margin-bottom: 20px; box-sizing: border-box;"></textarea>
        <div style="display: flex; gap: 12px;">
            <button id="declineReasonCancel" class="btn btn-secondary" style="flex: 1;">Cancel</button>
            <button id="declineReasonSubmit" class="btn btn-primary" style="flex: 1;">Submit</button>
        </div>
    </div>
</div>

<script>
// Pagination state
let currentAppointmentPage = 1;
const appointmentsPerPage = 5;
let allAppointmentsData = [];

// Load all appointments
function loadAllAppointments() {
    const statusFilter = document.getElementById('filter-status');
    const rescheduleFilter = document.getElementById('filter-reschedule');
    const dateFilter = document.getElementById('filter-date');
    
    if (!statusFilter || !dateFilter || !rescheduleFilter) {
        console.error('Filter elements not found');
        return;
    }
    
    const status = statusFilter.value;
    const reschedule = rescheduleFilter.value;
    const date = dateFilter.value;
    
    console.log('Loading appointments - Status:', status, 'Reschedule:', reschedule, 'Date:', date); // Debug
    
    let url = '../api/admin/appointments.php?action=getAll';
    if (status && status !== '') url += `&status=${status}`;
    if (date && date !== '') url += `&date=${date}`;
    
    console.log('Fetching from URL:', url); // Debug
    
    fetch(url)
        .then(r => {
            if (!r.ok) {
                throw new Error('Network response was not ok');
            }
            return r.text();
        })
        .then(text => {
            console.log('API Response:', text.substring(0, 200)); // Debug
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    console.log('Appointments loaded:', data.appointments.length); // Debug
                    let appointments = data.appointments;
                    
                    // Apply reschedule filter
                    if (reschedule) {
                        appointments = appointments.filter(apt => {
                            if (reschedule === 'pending') {
                                return apt.reschedule_request == 1 && apt.reschedule_status === 'pending';
                            } else if (reschedule === 'approved') {
                                return apt.reschedule_status === 'approved';
                            } else if (reschedule === 'declined') {
                                return apt.reschedule_status === 'declined';
                            }
                            return true;
                        });
                    }
                    
                    allAppointmentsData = appointments;
                    currentAppointmentPage = 1;
                    renderAppointmentsWithPagination();
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

// Render appointments with pagination
function renderAppointmentsWithPagination() {
    const totalPages = Math.ceil(allAppointmentsData.length / appointmentsPerPage);
    const startIndex = (currentAppointmentPage - 1) * appointmentsPerPage;
    const endIndex = startIndex + appointmentsPerPage;
    const paginatedAppointments = allAppointmentsData.slice(startIndex, endIndex);
    
    renderAppointments(paginatedAppointments);
    renderPagination(totalPages, currentAppointmentPage);
}

// Render appointments
function renderAppointments(appointments) {
    const container = document.getElementById('appointments-list');
    
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
                        ${apt.service_name ? '🔬 ' + apt.service_name : (apt.doctor_name ? '👨‍⚕️ Dr. ' + apt.doctor_name : 'N/A')}
                    </div>
                    <div style="font-size: 13px; color: var(--text-light);">
                        📅 ${formatDate(apt.appointment_date)} at ${apt.appointment_time}
                    </div>
                    ${apt.patient_email ? `<div style="font-size: 12px; color: var(--text-light); margin-top: 4px;">📧 ${apt.patient_email}</div>` : ''}
                    ${apt.reschedule_request == 1 && apt.reschedule_status === 'pending' ? `
                        <div style="margin-top: 10px; padding: 12px; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #f59e0b; border-radius: 6px; box-shadow: 0 1px 3px rgba(245, 158, 11, 0.1);">
                            <div style="font-size: 11px; font-weight: 700; color: #b45309; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 4px;">
                                <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                Reschedule Request
                            </div>
                            <div style="display: grid; gap: 6px;">
                                <div style="font-size: 12px; color: #78350f; display: flex; align-items: center; gap: 6px;">
                                    <span style="font-weight: 600; min-width: 65px;">Original:</span>
                                    <span style="text-decoration: line-through; opacity: 0.7;">${formatDate(apt.appointment_date)} at ${apt.appointment_time}</span>
                                </div>
                                <div style="font-size: 12px; color: #92400e; display: flex; align-items: center; gap: 6px;">
                                    <span style="font-weight: 600; min-width: 65px;">Requested:</span>
                                    <span style="font-weight: 600; color: #b45309;">${formatDate(apt.requested_date)} at ${apt.requested_time}</span>
                                </div>
                            </div>
                            ${apt.reschedule_reason ? `
                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(245, 158, 11, 0.2);">
                                    <div style="font-size: 11px; color: #78350f; line-height: 1.5;">
                                        <span style="font-weight: 600;">Reason:</span> ${apt.reschedule_reason}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    ` : ''}
                </div>
                <div style="text-align: right;">
                    <span class="badge badge-${getStatusColor(apt.status)}">${apt.status}</span>
                    ${apt.reschedule_request == 1 && apt.reschedule_status === 'pending' ? `
                        <span class="badge badge-orange" style="margin-left: 6px;">Reschedule</span>
                    ` : ''}
                    ${apt.status !== 'archived' && apt.status !== 'cancelled' && apt.status !== 'completed' ? `
                        <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                            ${apt.reschedule_request == 1 && apt.reschedule_status === 'pending' ? `
                                <button data-action="approve-reschedule" data-id="${apt.id}" class="btn btn-sm btn-success appointment-action-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Approve
                                </button>
                                <button data-action="decline-reschedule" data-id="${apt.id}" class="btn btn-sm btn-danger appointment-action-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                    Decline
                                </button>
                                <button data-action="view" data-id="${apt.id}" class="btn btn-sm btn-info appointment-action-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    View
                                </button>
                            ` : `
                                <button data-action="view" data-id="${apt.id}" class="btn btn-sm btn-info appointment-action-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    View
                                </button>
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
                                ${apt.status !== 'completed' && apt.status !== 'cancelled' ? `
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
                    ` : apt.status === 'completed' || apt.status === 'cancelled' ? `
                        <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                            <button data-action="view" data-id="${apt.id}" class="btn btn-sm btn-info appointment-action-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                View
                            </button>
                            <button data-action="archive" data-id="${apt.id}" class="btn btn-sm btn-archive appointment-action-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="21 8 21 21 3 21 3 8"></polyline>
                                    <rect x="1" y="3" width="22" height="5"></rect>
                                    <line x1="10" y1="12" x2="14" y2="12"></line>
                                </svg>
                                Archive
                            </button>
                        </div>
                    ` : apt.status === 'archived' ? `
                        <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                            <button data-action="view" data-id="${apt.id}" class="btn btn-sm btn-info appointment-action-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                View
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

// Render pagination
function renderPagination(totalPages, currentPage) {
    const container = document.getElementById('appointments-pagination');
    
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let paginationHTML = `
        <button class="btn btn-sm btn-secondary" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
            Previous
        </button>
        <span style="padding: 0 16px;">Page ${currentPage} of ${totalPages}</span>
        <button class="btn btn-sm btn-secondary" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
            Next
        </button>
    `;
    
    container.innerHTML = paginationHTML;
}

// Page change handler
function changePage(page) {
    currentAppointmentPage = page;
    renderAppointmentsWithPagination();
}

// Update appointment status
function updateAppointmentStatus(id, status) {
    showConfirm('info', `${status.charAt(0).toUpperCase() + status.slice(1)} Appointment?`, `Are you sure you want to ${status} this appointment?`, () => {
        const formData = new FormData();
        formData.append('action', 'updateStatus');
        formData.append('id', id);
        formData.append('status', status);
        
        fetch('../api/admin/appointments.php', {
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

// Archive appointment
function archiveAppointment(id) {
    showConfirm('warning', 'Archive Appointment?', 'This appointment will be moved to archives.', () => {
        const formData = new FormData();
        formData.append('action', 'archive');
        formData.append('id', id);
        
        fetch('../api/admin/appointments.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Archived', 'Appointment archived successfully!');
                loadAllAppointments();
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

// View appointment details
function viewAppointmentDetails(id) {
    // Find the appointment in our data
    const appointment = allAppointmentsData.find(apt => apt.id == id);
    
    if (!appointment) {
        showToast('error', 'Error', 'Appointment not found');
        return;
    }
    
    const content = document.getElementById('appointmentDetailsContent');
    content.innerHTML = `
        <div style="display: grid; gap: 20px;">
            <div>
                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Status</div>
                <span class="badge badge-${getStatusColor(appointment.status)}" style="display: inline-block;">${appointment.status}</span>
            </div>
            
            <div>
                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Patient Information</div>
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">${appointment.patient_name}</div>
                ${appointment.patient_email ? `<div style="display: flex; align-items: center; gap: 8px; color: #374151; margin-bottom: 4px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    ${appointment.patient_email}
                </div>` : ''}
                ${appointment.patient_phone ? `<div style="display: flex; align-items: center; gap: 8px; color: #374151;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    ${appointment.patient_phone}
                </div>` : ''}
            </div>
            
            <div>
                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Appointment Details</div>
                <div style="display: flex; align-items: center; gap: 8px; color: #374151; margin-bottom: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <strong>${formatDate(appointment.appointment_date)}</strong> at <strong>${appointment.appointment_time}</strong>
                </div>
                ${appointment.service_name ? `<div style="display: flex; align-items: center; gap: 8px; color: #374151; margin-bottom: 4px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    Service: ${appointment.service_name}
                </div>` : ''}
                ${appointment.doctor_name ? `<div style="display: flex; align-items: center; gap: 8px; color: #374151;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Doctor: Dr. ${appointment.doctor_name}
                </div>` : ''}
            </div>
            
            ${appointment.appointment_purpose ? `
            <div>
                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Purpose</div>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; color: #374151;">
                    ${appointment.appointment_purpose}
                </div>
            </div>
            ` : ''}
            
            ${appointment.notes ? `
            <div>
                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Notes</div>
                <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; color: #374151;">
                    ${appointment.notes}
                </div>
            </div>
            ` : ''}
            
            <div>
                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Created</div>
                <div style="color: #374151;">${formatDateTime(appointment.created_at)}</div>
            </div>
        </div>
    `;
    
    document.getElementById('appointmentDetailsModal').style.display = 'flex';
}

function closeAppointmentDetails() {
    document.getElementById('appointmentDetailsModal').style.display = 'none';
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

// Helper functions
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
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

// Approve reschedule request
function approveReschedule(id) {
    const appointment = allAppointmentsData.find(apt => apt.id == id);
    if (!appointment) {
        showToast('error', 'Error', 'Appointment not found');
        return;
    }
    
    const message = `Approve reschedule from ${formatDate(appointment.appointment_date)} ${appointment.appointment_time} to ${formatDate(appointment.requested_date)} ${appointment.requested_time}?`;
    
    showConfirm('success', 'Approve Reschedule?', message, () => {
        const formData = new FormData();
        formData.append('action', 'approveReschedule');
        formData.append('id', id);
        
        fetch('../api/admin/appointments.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Approved', 'Reschedule request approved successfully!');
                loadAllAppointments();
            } else {
                showToast('error', 'Error', data.message || 'Failed to approve reschedule');
            }
        })
        .catch(err => {
            console.error('Approve reschedule error:', err);
            showToast('error', 'Error', 'An error occurred while approving the reschedule.');
        });
    });
}

// Decline reschedule request
function declineReschedule(id) {
    const appointment = allAppointmentsData.find(apt => apt.id == id);
    if (!appointment) {
        showToast('error', 'Error', 'Appointment not found');
        return;
    }
    
    // Show custom modal for decline reason
    showDeclineReasonModal(id, appointment);
}

// Show custom decline reason modal
function showDeclineReasonModal(id, appointment) {
    const modal = document.getElementById('declineReasonModal');
    const input = document.getElementById('declineReasonInput');
    const cancelBtn = document.getElementById('declineReasonCancel');
    const submitBtn = document.getElementById('declineReasonSubmit');
    
    // Clear previous input
    input.value = '';
    
    // Show modal
    modal.style.display = 'flex';
    input.focus();
    
    // Cancel button handler
    const cancelHandler = () => {
        modal.style.display = 'none';
        cancelBtn.removeEventListener('click', cancelHandler);
        submitBtn.removeEventListener('click', submitHandler);
    };
    
    // Submit button handler
    const submitHandler = () => {
        const reason = input.value.trim();
        
        if (!reason) {
            showToast('error', 'Required', 'Please provide a reason for declining');
            input.focus();
            return;
        }
        
        // Close modal
        modal.style.display = 'none';
        cancelBtn.removeEventListener('click', cancelHandler);
        submitBtn.removeEventListener('click', submitHandler);
        
        // Show confirmation dialog
        showConfirm('warning', 'Decline Reschedule?', `Keep original date ${formatDate(appointment.appointment_date)} ${appointment.appointment_time}?`, () => {
            const formData = new FormData();
            formData.append('action', 'declineReschedule');
            formData.append('id', id);
            formData.append('decline_reason', reason);
            
            fetch('../api/admin/appointments.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Declined', 'Reschedule request declined successfully!');
                    loadAllAppointments();
                } else {
                    showToast('error', 'Error', data.message || 'Failed to decline reschedule');
                }
            })
            .catch(err => {
                console.error('Decline reschedule error:', err);
                showToast('error', 'Error', 'An error occurred while declining the reschedule.');
            });
        });
    };
    
    // Attach event listeners
    cancelBtn.addEventListener('click', cancelHandler);
    submitBtn.addEventListener('click', submitHandler);
    
    // Allow Enter key to submit (Shift+Enter for new line)
    input.addEventListener('keydown', function enterHandler(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            submitHandler();
            input.removeEventListener('keydown', enterHandler);
        }
    });
}

// Event delegation for appointment action buttons
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.appointment-action-btn');
    if (!btn) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    const action = btn.dataset.action;
    const id = btn.dataset.id;
    
    console.log('Button clicked:', action, id); // Debug log
    
    if (action === 'view') {
        viewAppointmentDetails(id);
    } else if (action === 'archive') {
        archiveAppointment(id);
    } else if (action === 'confirm') {
        updateAppointmentStatus(id, 'confirmed');
    } else if (action === 'complete') {
        updateAppointmentStatus(id, 'completed');
    } else if (action === 'cancel') {
        updateAppointmentStatus(id, 'cancelled');
    } else if (action === 'approve-reschedule') {
        approveReschedule(id);
    } else if (action === 'decline-reschedule') {
        declineReschedule(id);
    }
});

// Filter listeners - Ensure they're attached after DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('filter-status');
    const rescheduleFilter = document.getElementById('filter-reschedule');
    const dateFilter = document.getElementById('filter-date');
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            console.log('Status filter changed to:', this.value);
            currentAppointmentPage = 1; // Reset to first page
            loadAllAppointments();
        });
    }
    
    if (rescheduleFilter) {
        rescheduleFilter.addEventListener('change', function() {
            console.log('Reschedule filter changed to:', this.value);
            currentAppointmentPage = 1; // Reset to first page
            loadAllAppointments();
        });
    }
    
    if (dateFilter) {
        // Clear button functionality
        const clearDateBtn = document.createElement('button');
        clearDateBtn.textContent = '✕ Clear';
        clearDateBtn.className = 'btn btn-sm btn-secondary';
        clearDateBtn.style.cssText = 'margin-left: 8px; padding: 4px 12px; font-size: 12px;';
        clearDateBtn.onclick = function() {
            dateFilter.value = '';
            console.log('Date filter cleared');
            currentAppointmentPage = 1;
            loadAllAppointments();
        };
        dateFilter.parentElement.style.display = 'flex';
        dateFilter.parentElement.style.alignItems = 'flex-end';
        dateFilter.parentElement.appendChild(clearDateBtn);
        
        dateFilter.addEventListener('change', function() {
            console.log('Date filter changed to:', this.value);
            currentAppointmentPage = 1; // Reset to first page
            loadAllAppointments();
        });
        
        dateFilter.addEventListener('input', function() {
            console.log('Date filter input:', this.value);
            if (this.value) {
                currentAppointmentPage = 1;
                loadAllAppointments();
            }
        });
    }
    
    // Load appointments initially
    loadAllAppointments();
});
</script>

<?php require_once 'footer.php'; ?>
