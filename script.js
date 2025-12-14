// Data dan State Aplikasi
let currentUser = null;
let selectedPackage = null;
let selectedGame = 'ml';

// DOM Elements
const userMenuBtn = document.getElementById('userMenuBtn');
const dropdownMenu = document.getElementById('dropdownMenu');
const loginModal = document.getElementById('loginModal');
const closeModal = document.querySelector('.close');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const showRegister = document.getElementById('showRegister');
const showLogin = document.getElementById('showLogin');
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');
const paymentSection = document.getElementById('payment');
const paymentForm = document.getElementById('paymentForm');
const historyBody = document.getElementById('history-body');
const filterButtons = document.querySelectorAll('.filter-btn');

// Event Listeners
document.addEventListener('DOMContentLoaded', initApp);

// Fungsi Inisialisasi Aplikasi
function initApp() {
    // Setup event listeners untuk dropdown - FIXED
    setupDropdownEvents();
    
    // Setup hamburger menu
    setupHamburgerMenu();
    
    // Setup game selection baru
    setupNewGameSelection();
    
    // Setup package selection baru
    setupNewPackageSelection();
    
    // Setup payment form
    setupPaymentForm();
    
    // Setup filter buttons (hanya untuk admin di halaman admin)
    setupFilterButtons();
    
    // Setup modal
    setupModal();
    
    // Setup smooth scroll untuk anchor links - FIXED
    setupSmoothScroll();
    
    // Check if payment section should be shown
    checkPaymentSection();
    
    // Initialize game selection dari URL parameter
    initializeGameFromURL();
    
    // Setup navigation active states - FIXED
    setupNavigationActiveStates();
}

// Setup Dropdown Events - FIXED VERSION
function setupDropdownEvents() {
    if (userMenuBtn) {
        userMenuBtn.addEventListener('click', toggleUserMenu);
    }
    
    // Event listener untuk tombol login di dropdown
    const loginLink = document.getElementById('loginLink');
    const registerLink = document.getElementById('registerLink');
    
    if (loginLink) {
        loginLink.addEventListener('click', (e) => {
            e.preventDefault();
            openLoginModal(e);
        });
    }
    
    if (registerLink) {
        registerLink.addEventListener('click', (e) => {
            e.preventDefault();
            openLoginModal(e);
            showRegisterForm(e);
        });
    }
    
    // FIX: Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (dropdownMenu && !e.target.closest('.user-dropdown')) {
            dropdownMenu.classList.remove('show');
            const arrow = userMenuBtn?.querySelector('.dropdown-arrow');
            if (arrow) {
                arrow.style.transform = 'rotate(0deg)';
            }
        }
    });
}

// Setup Navigation Active States - FIXED
function setupNavigationActiveStates() {
    // Get current hash from URL
    const currentHash = window.location.hash || '#home';
    
    // Update active nav link
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentHash) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
    
    // Listen for hash changes
    window.addEventListener('hashchange', () => {
        const newHash = window.location.hash || '#home';
        navLinks.forEach(link => {
            if (link.getAttribute('href') === newHash) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    });
}

// Setup Hamburger Menu
function setupHamburgerMenu() {
    if (hamburger) {
        hamburger.addEventListener('click', mobileMenu);
    }
}

// Setup New Game Selection (untuk desain baru)
function setupNewGameSelection() {
    // Event listener untuk game cards di section games
    document.querySelectorAll('.btn-select-game').forEach(button => {
        button.addEventListener('click', function() {
            const game = this.getAttribute('data-game');
            selectNewGame(game);
        });
    });

    // Event listener untuk game options di topup section
    document.querySelectorAll('.game-option-new').forEach(option => {
        option.addEventListener('click', function() {
            const game = this.getAttribute('data-game');
            selectNewGame(game);
        });
    });
}

// Setup New Package Selection (untuk desain baru)
function setupNewPackageSelection() {
    document.querySelectorAll('.select-package-new').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const packageId = this.getAttribute('data-package-id');
            const packageName = this.getAttribute('data-package-name');
            const price = this.getAttribute('data-price');
            const game = this.getAttribute('data-game');
            
            selectNewPackage(packageId, packageName, price, game);
        });
    });
    
    // Back to packages button
    const backBtn = document.getElementById('btn-back-to-packages');
    if (backBtn) {
        backBtn.addEventListener('click', function() {
            document.getElementById('payment-preview').classList.add('hidden');
        });
    }
    
    // Close preview button
    const closePreviewBtn = document.querySelector('.btn-close-preview');
    if (closePreviewBtn) {
        closePreviewBtn.addEventListener('click', function() {
            document.getElementById('payment-preview').classList.add('hidden');
        });
    }
    
    // Proceed to payment button
    const proceedBtn = document.getElementById('btn-proceed-to-payment');
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function() {
            if (window.selectedPackage) {
                proceedToPayment(window.selectedPackage.id);
            }
        });
    }
}

// Setup Payment Form
function setupPaymentForm() {
    if (paymentForm) {
        paymentForm.addEventListener('submit', processPayment);
    }
}

// Setup Filter Buttons (untuk admin di halaman admin)
function setupFilterButtons() {
    filterButtons.forEach(button => {
        button.addEventListener('click', filterHistory);
    });
}

// Setup Modal
function setupModal() {
    if (closeModal) {
        closeModal.addEventListener('click', closeLoginModal);
    }
    
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    if (showRegister) {
        showRegister.addEventListener('click', showRegisterForm);
    }
    
    if (showLogin) {
        showLogin.addEventListener('click', showLoginForm);
    }
    
    // Close modal with ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && loginModal && loginModal.style.display === 'block') {
            closeLoginModal();
        }
    });
    
    // Event listener untuk footer buttons
    const footerLoginBtn = document.getElementById('footerLoginBtn');
    const footerRegisterBtn = document.getElementById('footerRegisterBtn');
    
    if (footerLoginBtn) {
        footerLoginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openLoginModal(e);
        });
    }
    
    if (footerRegisterBtn) {
        footerRegisterBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openLoginModal(e);
            showRegisterForm(e);
        });
    }
}

// Setup Smooth Scroll - FIXED VERSION
function setupSmoothScroll() {
    // Smooth scroll untuk semua anchor links - FIXED untuk history section
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            
            // Skip jika hanya hash atau link ke modal
            if (targetId === '#' || targetId.includes('javascript:')) return;
            
            // Handle filter links di history section (tanpa scroll ke atas)
            if (targetId.includes('#history') && targetId.includes('filter=')) {
                // Biarkan link berfungsi normal untuk filter
                return;
            }
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                
                // Jika ini adalah link ke payment section dan ada package yang dipilih
                if (targetId === '#payment' && document.querySelector('#selectedPackageId') && document.querySelector('#selectedPackageId').value) {
                    targetElement.classList.remove('hidden');
                }
                
                // Smooth scroll ke element
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
                
                // Update URL hash tanpa trigger scroll
                history.pushState(null, null, targetId);
                
                // Update active nav link
                updateActiveNavLink(targetId);
            }
        });
    });
}

// Update active nav link
function updateActiveNavLink(hash) {
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === hash) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Check if payment section should be shown
function checkPaymentSection() {
    // Check URL for package_id parameter
    const urlParams = new URLSearchParams(window.location.search);
    const packageId = urlParams.get('package_id');
    
    // Or check if there's a selected package in the form
    const selectedPackageInput = document.querySelector('#selectedPackageId');
    if (selectedPackageInput && selectedPackageInput.value) {
        showPaymentSection();
    } else if (packageId) {
        showPaymentSection();
    }
}

// Initialize game dari URL parameter
function initializeGameFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const game = urlParams.get('game');
    
    if (game && ['ml', 'roblox', 'freefire'].includes(game)) {
        selectNewGame(game);
    }
}

// Fungsi untuk memilih game (desain baru)
function selectNewGame(game) {
    selectedGame = game;
    
    // Update title dan subtitle
    const gameNames = {
        'ml': 'Mobile Legends',
        'roblox': 'Roblox',
        'freefire': 'Free Fire'
    };
    
    const topupTitle = document.getElementById('topup-title');
    const topupSubtitle = document.getElementById('topup-subtitle');
    
    if (topupTitle) {
        topupTitle.textContent = `Paket Top Up ${gameNames[game]}`;
    }
    
    if (topupSubtitle) {
        topupSubtitle.textContent = `Pilih nominal top up untuk ${gameNames[game]}`;
    }
    
    // Update game selection
    document.querySelectorAll('.game-option-new').forEach(option => {
        option.classList.remove('active');
        if (option.getAttribute('data-game') === game) {
            option.classList.add('active');
        }
    });
    
    // Show corresponding packages
    document.querySelectorAll('.package-category-new').forEach(category => {
        category.classList.remove('active');
        if (category.id === `${game}-packages-new`) {
            category.classList.add('active');
            
            // Smooth scroll ke packages section
            const topupSection = document.querySelector('.topup-section-new');
            if (topupSection) {
                setTimeout(() => {
                    window.scrollTo({
                        top: topupSection.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }, 300);
            }
        }
    });
    
    // Hide payment preview jika ada
    const paymentPreview = document.getElementById('payment-preview');
    if (paymentPreview) {
        paymentPreview.classList.add('hidden');
    }
    
    // Update URL tanpa reload
    const url = new URL(window.location);
    url.searchParams.set('game', game);
    history.replaceState(null, '', url);
}

// Fungsi untuk memilih paket (desain baru)
function selectNewPackage(packageId, packageName, price, game) {
    // Update payment preview
    const gameNames = {
        'ml': 'Mobile Legends',
        'roblox': 'Roblox',
        'freefire': 'Free Fire'
    };
    
    const previewGame = document.getElementById('preview-game');
    const previewPackage = document.getElementById('preview-package');
    const previewPrice = document.getElementById('preview-price');
    
    if (previewGame) {
        previewGame.textContent = gameNames[game];
    }
    
    if (previewPackage) {
        previewPackage.textContent = packageName;
    }
    
    if (previewPrice) {
        previewPrice.textContent = 'Rp ' + parseInt(price).toLocaleString('id-ID');
    }
    
    // Show payment preview
    const paymentPreview = document.getElementById('payment-preview');
    if (paymentPreview) {
        paymentPreview.classList.remove('hidden');
        
        // Smooth scroll ke payment preview
        setTimeout(() => {
            window.scrollTo({
                top: paymentPreview.offsetTop - 80,
                behavior: 'smooth'
            });
        }, 300);
    }
    
    // Store selected package data
    window.selectedPackage = {
        id: packageId,
        name: packageName,
        price: price,
        game: game
    };
}

// Fungsi untuk melanjutkan ke pembayaran
function proceedToPayment(packageId) {
    // Check if user is logged in
    const userAvatar = document.querySelector('.user-avatar');
    const isLoggedIn = userAvatar ? true : false;
    
    if (!isLoggedIn) {
        showNotification('Silakan login terlebih dahulu!', 'error');
        openLoginModal();
        return;
    }
    
    // Submit form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'package_id';
    input.value = packageId;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

// Fungsi untuk Dropdown User Menu - FIXED
function toggleUserMenu(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    const dropdown = document.getElementById('dropdownMenu');
    const arrow = userMenuBtn?.querySelector('.dropdown-arrow');
    
    if (dropdown) {
        const isActive = dropdown.classList.contains('show');
        
        // Close all other dropdowns
        document.querySelectorAll('.dropdown-content.show').forEach(d => {
            if (d !== dropdown) {
                d.classList.remove('show');
            }
        });
        
        // Toggle current dropdown
        dropdown.classList.toggle('show');
        
        // Update arrow
        if (arrow) {
            arrow.style.transform = dropdown.classList.contains('show') 
                ? 'rotate(180deg)' 
                : 'rotate(0deg)';
        }
    }
}

// Fungsi untuk Modal Login
function openLoginModal(e) {
    if (e) e.preventDefault();
    if (loginModal) {
        loginModal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
        
        // Tutup dropdown ketika modal dibuka
        if (dropdownMenu) {
            dropdownMenu.classList.remove('show');
            const arrow = userMenuBtn?.querySelector('.dropdown-arrow');
            if (arrow) {
                arrow.style.transform = 'rotate(0deg)';
            }
        }
        
        // Reset ke form login saat modal dibuka
        if (loginForm && registerForm) {
            registerForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
        }
    }
}

function closeLoginModal() {
    if (loginModal) {
        loginModal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
        
        // Reset form login jika ada
        if (loginForm) loginForm.reset();
        if (registerForm) registerForm.reset();
    }
}

function showRegisterForm(e) {
    if (e) e.preventDefault();
    if (loginForm && registerForm) {
        loginForm.classList.add('hidden');
        registerForm.classList.remove('hidden');
    }
}

function showLoginForm(e) {
    if (e) e.preventDefault();
    if (loginForm && registerForm) {
        registerForm.classList.add('hidden');
        loginForm.classList.remove('hidden');
    }
}

// Fungsi untuk Navigasi Mobile
function mobileMenu() {
    if (hamburger && navMenu) {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
        
        // Tutup dropdown saat mobile menu dibuka/tutup
        if (dropdownMenu) {
            dropdownMenu.classList.remove('show');
            const arrow = userMenuBtn?.querySelector('.dropdown-arrow');
            if (arrow) {
                arrow.style.transform = 'rotate(0deg)';
            }
        }
    }
}

// Fungsi untuk show payment section
function showPaymentSection() {
    const paymentSection = document.getElementById('payment');
    if (paymentSection) {
        paymentSection.classList.remove('hidden');
        
        // Smooth scroll to payment section
        setTimeout(() => {
            window.scrollTo({
                top: paymentSection.offsetTop - 80,
                behavior: 'smooth'
            });
            
            // Auto focus on first input
            const playerIdInput = document.getElementById('playerId');
            if (playerIdInput) {
                setTimeout(() => {
                    playerIdInput.focus();
                }, 300);
            }
        }, 100);
    }
}

// Fungsi untuk Payment Processing
function processPayment(e) {
    e.preventDefault();
    
    // Validasi form
    const playerId = document.getElementById('playerId');
    const playerName = document.getElementById('playerName');
    const paymentMethod = document.getElementById('paymentMethod');
    
    if (!playerId || !playerName || !paymentMethod) {
        showNotification('Form tidak lengkap!', 'error');
        return;
    }
    
    if (!playerId.value || !playerName.value || !paymentMethod.value) {
        showNotification('Harap isi semua field pembayaran!', 'error');
        return;
    }
    
    // Submit form
    paymentForm.submit();
}

// Fungsi untuk Filter History (untuk admin di halaman admin)
function filterHistory(e) {
    e.preventDefault();
    
    // FIXED: Tidak scroll ke atas, tetap di history section
    const filter = this.getAttribute('href').split('=')[1];
    const currentUrl = window.location.href.split('?')[0];
    const newUrl = `${currentUrl}?filter=${filter}#history`;
    
    window.location.href = newUrl;
}

// Fungsi untuk Menampilkan Notifikasi
function showNotification(message, type) {
    // Cek jika ada notifikasi sebelumnya, hapus
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
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
    
    // Auto-hide ketika di-klik
    notification.addEventListener('click', () => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    });
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// Fungsi untuk Login
function handleLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('username')?.value;
    const password = document.getElementById('password')?.value;
    
    // Validasi sederhana
    if (!username || !password) {
        showNotification('Harap isi username dan password!', 'error');
        return;
    }
    
    // Submit form asli (PHP akan menangani)
    loginForm.submit();
}

// Fungsi untuk Register
function handleRegister(e) {
    e.preventDefault();
    
    const username = document.getElementById('regUsername')?.value;
    const email = document.getElementById('regEmail')?.value;
    const password = document.getElementById('regPassword')?.value;
    
    // Validasi sederhana
    if (!username || !email || !password) {
        showNotification('Harap isi semua field!', 'error');
        return;
    }
    
    if (password.length < 6) {
        showNotification('Password minimal 6 karakter!', 'error');
        return;
    }
    
    // Submit form asli (PHP akan menangani)
    registerForm.submit();
}

// Fungsi Bantuan
function getGameName(gameCode) {
    const games = {
        'ml': 'Mobile Legends',
        'roblox': 'Roblox',
        'freefire': 'Free Fire'
    };
    return games[gameCode] || gameCode;
}

function formatPrice(price) {
    if (!price) return 'Rp 0';
    return 'Rp ' + parseInt(price).toLocaleString('id-ID');
}

// Auto-hide system notification dan setup payment section
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide system notification
    setTimeout(() => {
        const notification = document.getElementById('systemNotification');
        if (notification) {
            notification.style.display = 'none';
        }
    }, 3000);
    
    // Check if payment section should be shown (check for package_id in form)
    const selectedPackageInput = document.querySelector('#selectedPackageId');
    if (selectedPackageInput && selectedPackageInput.value) {
        setTimeout(function() {
            const paymentSection = document.getElementById('payment');
            if (paymentSection) {
                paymentSection.classList.remove('hidden');
                
                // Smooth scroll to payment section
                window.scrollTo({
                    top: paymentSection.offsetTop - 80,
                    behavior: 'smooth'
                });
                
                // Auto focus on first input
                const playerIdInput = document.getElementById('playerId');
                if (playerIdInput) {
                    setTimeout(() => {
                        playerIdInput.focus();
                    }, 300);
                }
            }
        }, 100);
    }
});