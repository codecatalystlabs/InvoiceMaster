/**
 * Code Catalyst Labs - Custom JavaScript
 */

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Confirmation for delete actions
document.addEventListener('DOMContentLoaded', function() {
    const deleteLinks = document.querySelectorAll('a[href*="delete"]');
    
    deleteLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});

// Form validation enhancement
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
    });
});

// Format currency inputs
function formatCurrencyInput(input) {
    let value = input.value.replace(/[^0-9.]/g, '');
    
    // Ensure only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    // Limit to 2 decimal places
    if (parts[1] && parts[1].length > 2) {
        value = parseFloat(value).toFixed(2);
    }
    
    input.value = value;
}

// Add event listeners for currency inputs
document.addEventListener('DOMContentLoaded', function() {
    const currencyInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    
    currencyInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });
});

// Tooltips initialization
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Popovers initialization
document.addEventListener('DOMContentLoaded', function() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Print function
function printPage() {
    window.print();
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(function(row) {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        
        cols.forEach(function(col) {
            csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
        });
        
        csv.push(csvRow.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', filename + '.csv');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// Search debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Live search for tables
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('input[name="search"]');
    
    searchInputs.forEach(function(input) {
        const debouncedSearch = debounce(function() {
            // Auto-submit search form after typing stops
            const form = input.closest('form');
            if (form && input.value.length >= 3) {
                // Optional: Auto-submit (uncomment if needed)
                // form.submit();
            }
        }, 500);
        
        input.addEventListener('input', debouncedSearch);
    });
});

// Number animation for statistics
function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        element.innerHTML = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Animate statistics on dashboard
document.addEventListener('DOMContentLoaded', function() {
    const statElements = document.querySelectorAll('.card h3, .card h4');
    
    statElements.forEach(function(element) {
        const value = parseInt(element.innerText.replace(/[^0-9]/g, ''));
        if (value && !isNaN(value)) {
            element.innerText = '0';
            animateValue(element, 0, value, 1000);
        }
    });
});

// Date range picker enhancement
document.addEventListener('DOMContentLoaded', function() {
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    
    if (dateFromInput && dateToInput) {
        dateFromInput.addEventListener('change', function() {
            dateToInput.min = this.value;
        });
        
        dateToInput.addEventListener('change', function() {
            dateFromInput.max = this.value;
        });
    }
});

// Copy to clipboard function
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'absolute';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    // Show notification
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
    alert.style.zIndex = '9999';
    alert.innerHTML = 'Copied to clipboard!';
    document.body.appendChild(alert);
    
    setTimeout(function() {
        alert.remove();
    }, 2000);
}

// Back to top button
document.addEventListener('DOMContentLoaded', function() {
    const backToTop = document.createElement('button');
    backToTop.innerHTML = '<i class="bi bi-arrow-up"></i>';
    backToTop.className = 'btn btn-primary position-fixed bottom-0 end-0 m-3';
    backToTop.style.display = 'none';
    backToTop.style.zIndex = '9999';
    backToTop.style.borderRadius = '50%';
    backToTop.style.width = '50px';
    backToTop.style.height = '50px';
    
    document.body.appendChild(backToTop);
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    });
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

// Console branding
console.log('%cCode Catalyst Labs', 'color: #0d6efd; font-size: 24px; font-weight: bold;');
console.log('%cInvoice Management System v1.0', 'color: #6c757d; font-size: 14px;');
console.log('%c© 2025 All Rights Reserved', 'color: #6c757d; font-size: 12px;');

