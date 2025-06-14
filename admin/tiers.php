<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$controller = new AdminController();
$tiers = $controller->getTiers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tier Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../src/assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Tier Management</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="patients.php">Patients</a>
                <a href="tiers.php" class="active">Tiers</a>
                <a href="rewards.php">Rewards</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <section class="tiers-section">
                <div class="section-header">
                    <h2>Loyalty Tiers</h2>
                    <button class="button" onclick="showAddTierModal()">Add New Tier</button>
                </div>

                <?php if (empty($tiers)): ?>
                    <p>No tiers defined yet.</p>
                <?php else: ?>
                    <table class="tiers-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Min Points</th>
                                <th>Max Points</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tiers as $tier): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tier['name']); ?></td>
                                    <td><?php echo $tier['min_points']; ?></td>
                                    <td><?php echo $tier['max_points'] ?: 'No limit'; ?></td>
                                    <td><?php echo htmlspecialchars($tier['description']); ?></td>
                                    <td>
                                        <button class="edit-tier" data-id="<?php echo $tier['id']; ?>">Edit</button>
                                        <button class="delete-tier" data-id="<?php echo $tier['id']; ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Add/Edit Tier Modal -->
    <div id="tierModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Add New Tier</h2>
            <form id="tierForm">
                <input type="hidden" id="tierId" name="tier_id">
                <div class="form-group">
                    <label for="tierName">Tier Name:</label>
                    <input type="text" id="tierName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="minPoints">Minimum Points:</label>
                    <input type="number" id="minPoints" name="min_points" required min="0">
                </div>
                <div class="form-group">
                    <label for="maxPoints">Maximum Points (optional):</label>
                    <input type="number" id="maxPoints" name="max_points" min="0">
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                <button type="submit" class="button">Save Tier</button>
            </form>
        </div>
    </div>

    <script>
        const tierModal = document.getElementById('tierModal');
        const tierForm = document.getElementById('tierForm');
        const modalTitle = document.getElementById('modalTitle');
        const closeBtn = document.querySelector('.close');

        function showAddTierModal() {
            modalTitle.textContent = 'Add New Tier';
            tierForm.reset();
            document.getElementById('tierId').value = '';
            tierModal.style.display = 'block';
        }

        document.querySelectorAll('.edit-tier').forEach(button => {
            button.addEventListener('click', function() {
                const tierId = this.dataset.id;
                fetch(`get_tier.php?id=${tierId}`)
                    .then(response => response.json())
                    .then(tier => {
                        modalTitle.textContent = 'Edit Tier';
                        document.getElementById('tierId').value = tier.id;
                        document.getElementById('tierName').value = tier.name;
                        document.getElementById('minPoints').value = tier.min_points;
                        document.getElementById('maxPoints').value = tier.max_points || '';
                        document.getElementById('description').value = tier.description;
                        tierModal.style.display = 'block';
                    });
            });
        });

        document.querySelectorAll('.delete-tier').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this tier?')) {
                    const tierId = this.dataset.id;
                    fetch('delete_tier.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: tierId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting tier: ' + data.error);
                        }
                    });
                }
            });
        });

        tierForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            fetch('save_tier.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error saving tier: ' + data.error);
                }
            });
        });

        closeBtn.onclick = function() {
            tierModal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == tierModal) {
                tierModal.style.display = 'none';
            }
        }
    </script>
</body>
</html> 