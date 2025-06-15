document.addEventListener('DOMContentLoaded', function() {
    // Points edit functionality
    const pointsModal = document.getElementById('pointsModal');
    const qrModal = document.getElementById('qrModal');
    const pointsForm = document.getElementById('pointsForm');
    const uploadForm = document.querySelector('form[action="process_upload.php"]');

    // Handle file upload form submission
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const fileInput = formData.get('csv_file');
            
            if (!fileInput || !fileInput.name) {
                showFlashMessage('Please select a CSV file', 'error');
                return;
            }
            
            if (!fileInput.name.toLowerCase().endsWith('.csv')) {
                showFlashMessage('Only CSV files are allowed', 'error');
                return;
            }
            
            // Submit the form
            this.submit();
        });
    }

    // Edit points button click
    document.querySelectorAll('.edit-points').forEach(button => {
        button.addEventListener('click', function() {
            const patientId = this.dataset.patientId;
            const points = this.parentElement.querySelector('.points').textContent;
            
            document.getElementById('patientId').value = patientId;
            document.getElementById('points').value = points;
            pointsModal.style.display = 'block';
        });
    });

    // QR code button click
    document.querySelectorAll('.qr-code').forEach(button => {
        button.addEventListener('click', function() {
            const patientId = this.dataset.patientId;
            const qrModal = document.getElementById('qrModal');
            const qrCode = document.getElementById('qrCode');
            
            // Show loading state
            qrCode.innerHTML = '<p>Generating QR code...</p>';
            qrModal.style.display = 'block';
            
            fetch(`../../admin/generate_qr.php?patient_id=${patientId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.qr_code_url) {
                        qrCode.innerHTML = `
                            <div class="qr-container">
                                <img src="${data.qr_code_url}" 
                                     alt="Patient QR Code" 
                                     style="max-width: 300px; margin: 20px auto;"
                                     onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(data.url)}'">
                                <div class="qr-info">
                                    <p><strong>Patient ID:</strong> ${data.patient_id}</p>
                                    <p><strong>Name:</strong> ${data.name}</p>
                                    <p><strong>Phone:</strong> ${data.phone}</p>
                                    <p><strong>Login URL:</strong></p>
                                    <p><a href="${data.url}" target="_blank">${data.url}</a></p>
                                    <p class="qr-note">Scan this QR code to automatically log in to the patient dashboard.</p>
                                </div>
                            </div>
                        `;
                    } else {
                        qrCode.innerHTML = `<p class="error">Error: ${data.error || 'Failed to generate QR code'}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    qrCode.innerHTML = '<p class="error">Error generating QR code</p>';
                });
        });
    });

    // Close modals
    document.querySelectorAll('.modal .cancel').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });

    // Handle points form submission
    if (pointsForm) {
        pointsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../../admin/handle_update_points.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update points in the table
                    const pointsElement = document.querySelector(`[data-patient-id="${formData.get('patient_id')}"]`)
                        .parentElement.querySelector('.points');
                    pointsElement.textContent = formData.get('points');
                    
                    // Close modal
                    pointsModal.style.display = 'none';
                    
                    // Show success message
                    showFlashMessage('Points updated successfully', 'success');
                } else {
                    showFlashMessage(data.error || 'Error updating points', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('Error updating points', 'error');
            });
        });
    }
});

// Flash message function
function showFlashMessage(message, type) {
    const flashMessage = document.createElement('div');
    flashMessage.className = `flash-message ${type}`;
    flashMessage.textContent = message;
    
    const main = document.querySelector('main');
    main.insertBefore(flashMessage, main.firstChild);
    
    setTimeout(() => {
        flashMessage.remove();
    }, 5000);
} 