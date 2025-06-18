<?php
// This is a shared loading module that can be included in all pages
?>
<div id="loading" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="spinner"></div>
        <div class="loading-text">Loading...</div>
    </div>
</div>

<style>
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #2ecc71;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 10px;
}

.loading-text {
    color: #333;
    font-size: 14px;
    margin-top: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Show loading overlay
function showLoading() {
    document.getElementById('loading').style.display = 'flex';
}

// Hide loading overlay
function hideLoading() {
    document.getElementById('loading').style.display = 'none';
}

// Add loading state to all links and forms
document.addEventListener('DOMContentLoaded', function() {
    // Add loading state to all links
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.getAttribute('href').startsWith('#')) {
                showLoading();
            }
        });
    });

    // Add loading state to all forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            showLoading();
        });
    });

    // Hide loading when page is fully loaded
    window.addEventListener('load', function() {
        hideLoading();
    });
});
</script> 