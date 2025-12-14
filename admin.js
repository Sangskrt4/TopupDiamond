// Admin Panel JavaScript - UPDATED

// DOM Elements
const adminMenuBtn = document.getElementById('adminMenuBtn');
const adminDropdown = document.getElementById('adminDropdown');
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

// Event Listeners
document.addEventListener('DOMContentLoaded', initAdmin);

// Fungsi Inisialisasi Admin
function initAdmin() {
    // Setup dropdown untuk admin
    setupAdminDropdown();
    
    // Setup hamburger menu
    setupHamburgerMenu();
    
    // Auto-hide system notification
    autoHideNotification();
    
    // Setup form validation
    setupFormValidation();
    
    // Setup mobile menu
    setupMobileMenu();
    
    // Initialize tooltips
    initializeTooltips();
}

// Setup Admin Dropdown
function setupAdminDropdown() {
    if (adminMenuBtn && adminDropdown) {
        adminMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle dropdown
            const isActive = adminDropdown.classList.contains('show');
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-content.show').forEach(dropdown => {
                if (dropdown !== adminDropdown) {
                    dropdown.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            adminDropdown.classList.toggle('show');
            
            // Update arrow
            const arrow = this.querySelector('.dropdown-arrow');
            if (arrow) {
                arrow.style.transform = adminDropdown.classList.contains('show') 
                    ? 'rotate(180deg)' 
                    : 'rotate(0deg)';
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown') && 
                !e.target.closest('.dropdown-content')) {
                adminDropdown.classList.remove('show');
                
                // Reset arrow
                const arrow = adminMenuBtn.querySelector('.dropdown-arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            }
        });
        
        // Close dropdown with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && adminDropdown.classList.contains('show')) {
                adminDropdown.classList.remove('show');
                
                // Reset arrow
                const arrow = adminMenuBtn.querySelector('.dropdown-arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            }
        });
    }
}

// Setup Hamburger Menu
function setupHamburgerMenu() {
    if (hamburger) {
        hamburger.addEventListener('click', mobileMenu);
    }
}

// Mobile Menu Function
function mobileMenu() {
    if (hamburger && navMenu) {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
        
        // Tutup dropdown saat mobile menu dibuka/tutup
        if (adminDropdown) {
            adminDropdown.classList.remove('show');
            const arrow = adminMenuBtn?.querySelector('.dropdown-arrow');
            if (arrow) {
                arrow.style.transform = 'rotate(0deg)';
            }
        }
    }
}

// Setup Mobile Menu
function setupMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Close dropdown when mobile menu opens
            if (adminDropdown) {
                adminDropdown.classList.remove('show');
                const arrow = adminMenuBtn?.querySelector('.dropdown-arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            }
        });
        
        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });
    }
}

// Setup Form Validation
function setupFormValidation() {
    // Validation for edit transaction form
    const editForms = document.querySelectorAll('form');
    editForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const textareas = this.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                if (textarea.hasAttribute('required') && !textarea.value.trim()) {
                    e.preventDefault();
                    showNotification('Harap isi semua field yang diperlukan!', 'error');
                    textarea.focus();
                    return;
                }
            });
        });
    });
}

// Initialize Tooltips
function initializeTooltips() {
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            const action = getActionType(this);
            this.setAttribute('title', getTooltipText(action));
        });
    });
}

// Get action type for tooltip
function getActionType(button) {
    if (button.classList.contains('view-btn')) return 'view';
    if (button.classList.contains('edit-btn')) return 'edit';
    if (button.classList.contains('success-btn')) return 'success';
    if (button.classList.contains('failed-btn')) return 'failed';
    if (button.classList.contains('delete-btn')) return 'delete';
    return '';
}

// Get tooltip text
function getTooltipText(action) {
    switch (action) {
        case 'view': return 'Lihat Detail';
        case 'edit': return 'Edit Status';
        case 'success': return 'Setujui';
        case 'failed': return 'Tolak';
        case 'delete': return 'Hapus';
        default: return '';
    }
}

// Auto-hide system notification
function autoHideNotification() {
    const notification = document.getElementById('systemNotification');
    if (notification) {
        // Auto-hide after 4 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 300);
        }, 4000);
        
        // Hide when clicked
        notification.addEventListener('click', () => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 300);
        });
    }
}

// Fungsi untuk Filter Transactions
function filterTransactions() {
    const status = document.getElementById('statusFilter')?.value || 'all';
    const game = document.getElementById('gameFilter')?.value || 'all';
    const search = document.getElementById('searchInput')?.value || '';
    
    let url = `admin.php?tab=transactions&status=${status}&game=${game}`;
    if (search) {
        url += `&search=${encodeURIComponent(search)}`;
    }
    
    window.location.href = url;
}

// Fungsi untuk Search Transactions
function searchTransactions(e) {
    if (e.key === 'Enter') {
        filterTransactions();
    }
}

function resetFilters() {
    window.location.href = 'admin.php?tab=transactions';
}

// Fungsi untuk View Transaction Details
function viewTransaction(id) {
    const modalContent = `
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-eye"></i> Detail Transaksi #${id}</h2>
            <div style="padding: 20px 0;">
                <p>Loading detail transaksi...</p>
                <p><small>Sedang mengambil data transaksi...</small></p>
                <div class="loading-spinner" style="text-align: center; margin: 20px 0;">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--dark-border);">
                <button class="btn-secondary" onclick="closeModal()">Tutup</button>
            </div>
        </div>
    `;
    
    showModal(modalContent);
    
    // Simulate loading data
    setTimeout(() => {
        updateTransactionDetails(id);
    }, 500);
}

// Update transaction details (simulated)
function updateTransactionDetails(id) {
    const modalContent = document.querySelector('.modal-content');
    if (modalContent) {
        modalContent.innerHTML = `
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-eye"></i> Detail Transaksi #${id}</h2>
            <div style="padding: 20px 0;">
                <div class="transaction-details">
                    <div class="detail-item">
                        <strong><i class="fas fa-user"></i> User:</strong>
                        <span>User${id}</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-gamepad"></i> Game:</strong>
                        <span>Mobile Legends</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-box"></i> Paket:</strong>
                        <span>86 Diamond</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-id-card"></i> ID Player:</strong>
                        <span>123456789</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-user"></i> Nama Player:</strong>
                        <span>Player${id}</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-money-bill-wave"></i> Harga:</strong>
                        <span class="price">Rp 25.000</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-info-circle"></i> Status:</strong>
                        <span class="status-badge pending">Pending</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-calendar"></i> Tanggal:</strong>
                        <span>${new Date().toLocaleDateString('id-ID')}</span>
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--dark-border);">
                <button class="btn-secondary" onclick="closeModal()">Tutup</button>
            </div>
        `;
    }
}

// Fungsi untuk Open Edit Modal
function openEditModal(id) {
    const modalContent = `
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Status Transaksi #${id}</h2>
            <form method="POST" action="" id="editTransactionForm">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="transaction_id" value="${id}">
                <div class="form-group">
                    <label for="status"><i class="fas fa-info-circle"></i> Status</label>
                    <select id="status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="success">Berhasil</option>
                        <option value="failed">Gagal</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="admin_notes"><i class="fas fa-sticky-note"></i> Catatan Admin (Opsional)</label>
                    <textarea id="admin_notes" name="admin_notes" rows="4" placeholder="Tambahkan catatan untuk transaksi ini"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    `;
    
    showModal(modalContent);
    
    // Setup form submission
    const form = document.getElementById('editTransactionForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            submitBtn.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                this.submit();
            }, 1000);
        });
    }
}

// Fungsi untuk Approve Transaction (Quick Action)
function approveTransaction(id) {
    if (confirm('Setujui transaksi ini?\n\nTransaksi akan ditandai sebagai BERHASIL.')) {
        submitForm('update_status', id, 'success');
    }
}

// Fungsi untuk Reject Transaction (Quick Action)
function rejectTransaction(id) {
    if (confirm('Tolak transaksi ini?\n\nTransaksi akan ditandai sebagai GAGAL.')) {
        submitForm('update_status', id, 'failed');
    }
}

// Fungsi untuk Delete Transaction (Admin)
function deleteTransactionAdmin(id) {
    if (confirm('Apakah Anda yakin ingin menghapus transaksi ini?\n\nTindakan ini tidak dapat dibatalkan.')) {
        submitForm('delete_transaction', id);
    }
}

// Fungsi untuk View User Details
function viewUser(userId) {
    const modalContent = `
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-user"></i> Detail User</h2>
            <div style="padding: 20px 0;">
                <p>Loading detail user...</p>
                <p><small>Sedang mengambil data user...</small></p>
                <div class="loading-spinner" style="text-align: center; margin: 20px 0;">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--dark-border);">
                <button class="btn-secondary" onclick="closeModal()">Tutup</button>
            </div>
        </div>
    `;
    
    showModal(modalContent);
    
    // Simulate loading data
    setTimeout(() => {
        updateUserDetails(userId);
    }, 500);
}

// Update user details (simulated)
function updateUserDetails(userId) {
    const modalContent = document.querySelector('.modal-content');
    if (modalContent) {
        modalContent.innerHTML = `
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-user"></i> Detail User #${userId}</h2>
            <div style="padding: 20px 0;">
                <div class="user-details">
                    <div class="detail-item">
                        <strong><i class="fas fa-user"></i> Username:</strong>
                        <span>user${userId}</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-envelope"></i> Email:</strong>
                        <span>user${userId}@example.com</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-calendar"></i> Bergabung:</strong>
                        <span>${new Date().toLocaleDateString('id-ID')}</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-exchange-alt"></i> Total Transaksi:</strong>
                        <span>5 transaksi</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-money-bill-wave"></i> Total Belanja:</strong>
                        <span class="price">Rp 125.000</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="fas fa-clock"></i> Terakhir Login:</strong>
                        <span>${new Date().toLocaleString('id-ID')}</span>
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--dark-border);">
                <button class="btn-secondary" onclick="closeModal()">Tutup</button>
            </div>
        `;
    }
}

// Fungsi untuk Delete User
function deleteUser(userId) {
    if (confirm('Apakah Anda yakin ingin menghapus user ini?\n\nSemua transaksi user ini juga akan dihapus.\n\nTindakan ini tidak dapat dibatalkan.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const input1 = document.createElement('input');
        input1.type = 'hidden';
        input1.name = 'delete_user';
        input1.value = '1';
        
        const input2 = document.createElement('input');
        input2.type = 'hidden';
        input2.name = 'user_id';
        input2.value = userId;
        
        form.appendChild(input1);
        form.appendChild(input2);
        document.body.appendChild(form);
        
        // Show loading
        showNotification('Menghapus user...', 'info');
        
        form.submit();
    }
}

// Helper function untuk submit form
function submitForm(action, id, status = null) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    if (action === 'update_status') {
        const input1 = document.createElement('input');
        input1.type = 'hidden';
        input1.name = 'update_status';
        input1.value = '1';
        
        const input2 = document.createElement('input');
        input2.type = 'hidden';
        input2.name = 'transaction_id';
        input2.value = id;
        
        const input3 = document.createElement('input');
        input3.type = 'hidden';
        input3.name = 'status';
        input3.value = status;
        
        form.appendChild(input1);
        form.appendChild(input2);
        form.appendChild(input3);
    } else if (action === 'delete_transaction') {
        const input1 = document.createElement('input');
        input1.type = 'hidden';
        input1.name = 'delete_transaction';
        input1.value = '1';
        
        const input2 = document.createElement('input');
        input2.type = 'hidden';
        input2.name = 'transaction_id';
        input2.value = id;
        
        form.appendChild(input1);
        form.appendChild(input2);
    }
    
    // Show loading notification
    showNotification('Memproses...', 'info');
    
    document.body.appendChild(form);
    form.submit();
}

// Fungsi untuk Show Modal
function showModal(content) {
    // Remove existing modal
    const existingModal = document.getElementById('editTransactionModal');
    if (existingModal) {
        existingModal.innerHTML = content;
    } else {
        const modal = document.createElement('div');
        modal.id = 'editTransactionModal';
        modal.className = 'modal';
        modal.innerHTML = content;
        document.body.appendChild(modal);
    }
    
    const modal = document.getElementById('editTransactionModal');
    modal.style.display = 'block';
    
    // Close modal with ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

// Fungsi untuk Close Modal
function closeModal() {
    const modal = document.getElementById('editTransactionModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editTransactionModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Fungsi untuk Menampilkan Notifikasi
function showNotification(message, type) {
    // Cek jika ada notifikasi sebelumnya, hapus
    const existingNotification = document.querySelector('.custom-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `custom-notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        max-width: 300px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        transform: translateX(400px);
        transition: transform 0.3s ease;
        cursor: pointer;
    `;
    
    if (type === 'success') {
        notification.style.backgroundColor = '#00b894';
    } else if (type === 'error') {
        notification.style.backgroundColor = '#e17055';
    } else if (type === 'info') {
        notification.style.backgroundColor = '#6c5ce7';
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-hide when clicked
    notification.addEventListener('click', () => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    });
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }
    }, 5000);
}

// Helper function untuk format price
function formatPrice(price) {
    if (!price) return 'Rp 0';
    return 'Rp ' + parseInt(price).toLocaleString('id-ID');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide system notification
    const systemNotification = document.getElementById('systemNotification');
    if (systemNotification) {
        setTimeout(() => {
            systemNotification.style.opacity = '0';
            systemNotification.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                systemNotification.style.display = 'none';
            }, 300);
        }, 4000);
        
        systemNotification.addEventListener('click', () => {
            systemNotification.style.opacity = '0';
            systemNotification.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                systemNotification.style.display = 'none';
            }, 300);
        });
    }
    
    // Add active state to current tab
    const currentUrl = window.location.href;
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (currentUrl.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
});