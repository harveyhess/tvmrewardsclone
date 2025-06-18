<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../src/assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Patients</h1>
            <nav>
                <a href="../../admin/dashboard.php">Dashboard</a>
                <a href="../../admin/patients.php" class="active">Patients</a>
                <a href="../../admin/upload.php">Upload CSV</a>
                <a href="../../admin/logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <section class="patients-section">
                <div class="actions">
                    <button class="button" id="openRegisterModal">Register New Patient</button>
                </div>

                <?php if (empty($patients)): ?>
                    <p>No patients found.</p>
                <?php else: ?>
                    <table class="patients-table">
                        <thead>
                            <tr>
                                <th>UHID</th>
                                <th>Name</th>
                                <th>UHID</th>
                                <th>Total Points</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($patient['UHID']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['name']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['UHID']); ?></td>
                                    <td>
                                        <span class="points"><?php echo $patient['total_points']; ?></span>
                                       
                                    </td>
                                    <td>
                                        <button class="qr-code" data-UHID="<?php echo htmlspecialchars($patient['UHID']); ?>" onclick="console.log('QR button clicked for UHID:', '<?php echo htmlspecialchars($patient['UHID']); ?>')">
                                            QR Code
                                        </button>
                                        <a href="transactions.php?UHID=<?php echo htmlspecialchars($patient['UHID']); ?>" class="button">View Transactions</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" 
                                   class="<?php echo $i === $currentPage ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <!-- Points Edit Modal 
            <div id="pointsModal" class="modal">
                <div class="modal-content">
                    <h2>Edit Points</h2>
                    <form id="pointsForm">
                        <input type="hidden" id="UHID" name="UHID">
                        <div class="form-group">
                            <label for="points">Points:</label>
                            <input type="number" id="points" name="points" required min="0">
                        </div>
                        <button type="submit" class="button">Save</button>
                        <button type="button" class="button cancel">Cancel</button>
                    </form>
                </div>
            </div>-->

            <!-- QR Code Modal -->
            <div id="qrModal" class="modal">
                <div class="modal-content">
                    <h2>Patient QR Code</h2>
                    <div id="qrCode"></div>
                    <button type="button" class="button cancel">Close</button>
                </div>
            </div>

            <!-- Register Patient Modal -->
            <div id="registerPatientModal" class="modal" style="display:none;">
                <div class="modal-content">
                    <h2>Register New Patient</h2>
                    <form id="registerPatientForm" autocomplete="off">
                        <div class="form-group">
                            <label for="reg_name">Patient Name</label>
                            <input type="text" id="reg_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="reg_uhid">UHID</label>
                            <input type="text" id="reg_uhid" name="uhid" required>
                        </div>
                        <div id="registerPatientMsg"></div>
                        <button type="submit" class="button">Register</button>
                        <button type="button" class="button cancel" id="closeRegisterModal">Cancel</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../../src/assets/js/admin.js"></script>
    <script>
    // Modal open/close logic
    document.getElementById('openRegisterModal').onclick = function() {
        document.getElementById('registerPatientModal').style.display = 'block';
    };
    document.getElementById('closeRegisterModal').onclick = function() {
        document.getElementById('registerPatientModal').style.display = 'none';
        document.getElementById('registerPatientForm').reset();
        document.getElementById('registerPatientMsg').innerHTML = '';
    };
    // AJAX form submit
    document.getElementById('registerPatientForm').onsubmit = function(e) {
        e.preventDefault();
        var form = this;
        var msgDiv = document.getElementById('registerPatientMsg');
        msgDiv.innerHTML = '';
        var data = new FormData(form);
        fetch('../../admin/process_register_patient.php', {
            method: 'POST',
            body: data
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                msgDiv.innerHTML = '<div class="success">' + res.success + '</div>';
                form.reset();
                setTimeout(() => { window.location.reload(); }, 1000);
            } else {
                msgDiv.innerHTML = '<div class="error">' + (res.error || 'Failed to register') + '</div>';
            }
        })
        .catch(() => {
            msgDiv.innerHTML = '<div class="error">Network error</div>';
        });
    };
    </script>
</body>
</html>