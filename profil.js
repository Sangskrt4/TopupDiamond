// Profil Page JavaScript - UPDATED

// DOM Elements Profil
const profilMenuBtns = document.querySelectorAll('.profil-menu-btn');
const profilTabs = document.querySelectorAll('.profil-tab');
const newPasswordInput = document.getElementById('newPassword');
const headerProfilMenuBtn = document.getElementById('headerProfilMenuBtn');
const headerProfilDropdown = document.getElementById('headerProfilDropdown');

// Event Listeners
document.addEventListener('DOMContentLoaded', initProfil);

// Fungsi Inisialisasi Profil
function initProfil() {
    // Setup password strength checker
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', checkPasswordStrength);
    }
    
    // Setup tab switching
    setupProfilTabs();
    
    // Setup dropdown untuk header
    setupHeaderDropdown();
    
    // Auto-hide system notification
    autoHideNotification();
    
    // Setup form validation
    setupFormValidation();
    
    // Handle tab from URL parameter
    handleTabFromUrl();
    
    // Setup mobile menu
    setupMobileMenu();
}

// Setup Profil Tabs
function setupProfilTabs() {
    profilMenuBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            
            // Get tab from ?tab=value
            const urlParams = new URLSearchParams(href.split('?')[1]);
            const tabId = urlParams.get('tab');
            
            if (!tabId) return;
            
            // Update menu aktif
            profilMenuBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Tampilkan tab yang dipilih
            profilTabs.forEach(tab => {
                tab.classList.remove('active');
                if (tab.id === tabId) {
                    tab.classList.add('active');
                }
            });
            
            // Update URL tanpa reload page
            const newUrl = window.location.pathname + `?tab=${tabId}`;
            history.pushState(null, null, newUrl);
            
            // Scroll to top of content
            document.querySelector('.profil-content').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    });
}

// Setup Header Dropdown
function setupHeaderDropdown() {
    if (headerProfilMenuBtn && headerProfilDropdown) {
        headerProfilMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle dropdown
            const isActive = headerProfilDropdown.classList.contains('show');
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-content.show').forEach(dropdown => {
                if (dropdown !== headerProfilDropdown) {
                    dropdown.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            headerProfilDropdown.classList.toggle('show');
            
            // Update arrow
            const arrow = this.querySelector('.dropdown-arrow');
            if (arrow) {
                arrow.style.transform = headerProfilDropdown.classList.contains('show') 
                    ? 'rotate(180deg)' 
                    : 'rotate(0deg)';
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown') && 
                !e.target.closest('.dropdown-content')) {
                headerProfilDropdown.classList.remove('show');
                
                // Reset arrow
                const arrow = headerProfilMenuBtn.querySelector('.dropdown-arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            }
        });
        
        // Close dropdown with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && headerProfilDropdown.classList.contains('show')) {
                headerProfilDropdown.classList.remove('show');
                
                // Reset arrow
                const arrow = headerProfilMenuBtn.querySelector('.dropdown-arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            }
        });
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
            if (headerProfilDropdown) {
                headerProfilDropdown.classList.remove('show');
                const arrow = headerProfilMenuBtn?.querySelector('.dropdown-arrow');
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

// Fungsi untuk Cek Kekuatan Password
function checkPasswordStrength() {
    const password = newPasswordInput.value;
    let strength = 0;
    let label = 'Lemah';
    let className = 'weak';
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    if (strength >= 4) {
        label = 'Sangat Kuat';
        className = 'strong';
    } else if (strength >= 3) {
        label = 'Kuat';
        className = 'strong';
    } else if (strength >= 2) {
        label = 'Cukup';
        className = 'medium';
    }
    
    // Update strength bar
    const strengthBar = document.querySelector('.strength-bar');
    const strengthLabel = document.getElementById('strengthLabel');
    
    if (strengthBar && strengthLabel) {
        // Remove all classes
        strengthBar.className = 'strength-bar';
        strengthBar.classList.add(className);
        
        strengthLabel.textContent = label;
        strengthLabel.style.color = getStrengthColor(className);
    }
}

// Get color for strength indicator
function getStrengthColor(className) {
    switch (className) {
        case 'weak': return '#e17055';
        case 'medium': return '#fdcb6e';
        case 'strong': return '#00b894';
        default: return '#b0b0b0';
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

// Setup Form Validation
function setupFormValidation() {
    // Edit profil form validation
    const editProfilForm = document.querySelector('#edit-profil form');
    if (editProfilForm) {
        editProfilForm.addEventListener('submit', function(e) {
            const username = document.getElementById('editUsername');
            const email = document.getElementById('editEmail');
            
            if (!username || !email) return;
            
            if (username.value.length < 3 || username.value.length > 20) {
                e.preventDefault();
                showNotification('Username harus 3-20 karakter!', 'error');
                return;
            }
            
            const usernameRegex = /^[a-zA-Z0-9_]+$/;
            if (!usernameRegex.test(username.value)) {
                e.preventDefault();
                showNotification('Username hanya boleh huruf, angka, dan underscore!', 'error');
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                e.preventDefault();
                showNotification('Format email tidak valid!', 'error');
                return;
            }
        });
    }
    
    // Password change form validation
    const passwordForm = document.querySelector('#ubah-password form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            const currentPassword = document.getElementById('currentPassword');
            
            if (!newPassword || !confirmPassword || !currentPassword) return;
            
            if (currentPassword.value === '') {
                e.preventDefault();
                showNotification('Password saat ini harus diisi!', 'error');
                return;
            }
            
            if (newPassword.value.length < 8) {
                e.preventDefault();
                showNotification('Password minimal 8 karakter!', 'error');
                return;
            }
            
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/;
            if (!passwordRegex.test(newPassword.value)) {
                e.preventDefault();
                showNotification('Password harus mengandung huruf besar, huruf kecil, dan angka!', 'error');
                return;
            }
            
            if (newPassword.value !== confirmPassword.value) {
                e.preventDefault();
                showNotification('Password baru tidak cocok!', 'error');
                return;
            }
        });
    }
}

// Fungsi untuk menghandle tab changes dari URL parameter
function handleTabFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    
    if (tab) {
        // Aktifkan tab yang sesuai
        const tabElement = document.getElementById(tab);
        const menuBtn = document.querySelector(`.profil-menu-btn[href*="${tab}"]`);
        
        if (tabElement && menuBtn) {
            // Nonaktifkan semua tab
            profilTabs.forEach(t => t.classList.remove('active'));
            profilMenuBtns.forEach(b => b.classList.remove('active'));
            
            // Aktifkan tab yang dipilih
            tabElement.classList.add('active');
            menuBtn.classList.add('active');
        }
    }
}

// Handle browser back/forward buttons
window.addEventListener('popstate', function() {
    handleTabFromUrl();
});

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

// Password strength checker real-time
if (newPasswordInput) {
    newPasswordInput.addEventListener('input', function() {
        checkPasswordStrength();
    });
}

// Initialize password strength on page load
document.addEventListener('DOMContentLoaded', function() {
    if (newPasswordInput && newPasswordInput.value) {
        checkPasswordStrength();
    }
});