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


        