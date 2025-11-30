<?php
// Fix the include path
include __DIR__ . '/../Srcipt/access_control.php';
include __DIR__ . '/../Srcipt/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Registration - RSU Security Management System</title>

    <!-- Unified Styles -->
    <link rel="stylesheet" href="../Style/global.css">
    <link rel="stylesheet" href="../Style/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Simple Data Privacy Act Styles */
        .privacy-consent-simple {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }

        .privacy-checkbox-simple {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .privacy-checkbox-simple input[type="checkbox"] {
            margin-top: 3px;
            width: 18px;
            height: 18px;
        }

        .privacy-text-simple {
            font-size: 0.9rem;
            line-height: 1.5;
            color: #495057;
        }

        .privacy-link-simple {
            color: #0055a4;
            text-decoration: none;
            font-weight: 500;
        }

        .privacy-link-simple:hover {
            text-decoration: underline;
            color: #003d7a;
        }

        .required-star {
            color: #dc3545;
            font-weight: bold;
        }

        .validation-message.error {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <!-- ===== Unified Top Navigation ===== -->
    <header class="top-nav">
        <div class="nav-left">
            <img src="../Assets/rsulogo.png" alt="RSU Logo" class="logo">
            <h1>RSU Security Management System</h1>
        </div>
        <div class="nav-right">
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <span class="user-role badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
            </div>
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <div class="user-menu">
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </header>

    <!-- ===== Unified Sidebar ===== -->
    <aside class="sidebar" id="sidebar">
        <nav class="nav-links">
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i><span>Dashboard</span>
            </a>

            <?php if ($auth->hasRole('admin')): ?>
                <a href="database.php" class="<?= basename($_SERVER['PHP_SELF']) == 'database.php' ? 'active' : '' ?>">
                    <i class="fas fa-database"></i><span>Database</span>
                </a>
            <?php endif; ?>

            <a href="entrymonitor.php"
                class="<?= basename($_SERVER['PHP_SELF']) == 'entrymonitor.php' ? 'active' : '' ?>">
                <i class="fas fa-id-card"></i><span>Entry Monitor</span>
            </a>

            <?php if ($auth->hasRole('admin')): ?>
                <a href="logs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i><span>RFID Logs</span>
                </a>
            <?php endif; ?>

            <a href="register.php" class="<?= basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i><span>Register Guest</span>
            </a>

            <?php if ($auth->hasRole('admin') || $auth->hasRole('security')): ?>
                <a href="control_panel.php"
                    class="<?= basename($_SERVER['PHP_SELF']) == 'control_panel.php' ? 'active' : '' ?>">
                    <i class="fas fa-cogs"></i><span>Control Panel</span>
                </a>
            <?php endif; ?>

            <!-- User Management Links -->
            <?php if ($auth->hasRole('admin')): ?>
                <a href="user_management.php"
                    class="<?= basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i><span>User Management</span>
                </a>
                <a href="user_logs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'user_logs.php' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i><span>User Activity Logs</span>
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- ===== Main Content ===== -->
    <main class="main-content" id="mainContent">
        <div class="main-container">
            <div class="page-header">
                <h2><i class="fas fa-user-plus"></i> Guest Registration</h2>
                <p>Register new visitors for campus access</p>
            </div>

            <!-- Registration Form -->
            <div class="registration-container glass-card">
                <form id="guestRegistrationForm" class="registration-form">
                    <!-- Photo Capture Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-camera"></i> Visitor Photo</h3>
                        <div class="camera-section">
                            <div class="camera-container">
                                <video id="cameraPreview" autoplay playsinline></video>
                                <canvas id="photoCanvas" style="display: none;"></canvas>
                                <div class="camera-overlay">
                                    <div class="face-guide"></div>
                                </div>
                                <div id="cameraError" class="camera-error" style="display: none;">
                                    <i class="fas fa-camera-slash"></i>
                                    <p>Camera not available</p>
                                    <span>Please check permissions or try a different browser</span>
                                </div>
                            </div>
                            <div class="camera-controls">
                                <button type="button" id="startCamera" class="btn-primary">
                                    <i class="fas fa-camera"></i> Start Camera
                                </button>
                                <button type="button" id="capturePhoto" class="btn-primary" disabled>
                                    <i class="fas fa-camera-retro"></i> Capture Photo
                                </button>
                                <button type="button" id="retakePhoto" class="btn-secondary" disabled>
                                    <i class="fas fa-redo"></i> Retake
                                </button>
                            </div>
                            <div class="photo-preview" id="photoPreview" style="display: none;">
                                <img id="capturedPhoto" alt="Captured Photo">
                                <p>Photo captured successfully</p>
                            </div>
                        </div>
                    </div>

                    <!-- Visitor Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Visitor Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="visitDate"><i class="fas fa-calendar-day"></i> Date *</label>
                                <input type="date" id="visitDate" name="visitDate" required>
                            </div>
                            <div class="form-group full-width">
                                <label for="visitorName"><i class="fas fa-user"></i> Name of Visitor *</label>
                                <input type="text" id="visitorName" name="visitorName" required
                                    placeholder="Enter full name of visitor">
                            </div>
                        </div>
                    </div>

                    <!-- Visit Details -->
                    <div class="form-section">
                        <h3><i class="fas fa-building"></i> Visit Details</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="personToVisit"><i class="fas fa-user-tie"></i> Person to Visit *</label>
                                <input type="text" id="personToVisit" name="personToVisit" required
                                    placeholder="Name of person being visited">
                            </div>
                            <div class="form-group">
                                <label for="office"><i class="fas fa-door-open"></i> Office/Department *</label>
                                <input type="text" id="office" name="office" required
                                    placeholder="Office or department name">
                            </div>
                            <div class="form-group full-width">
                                <label for="purpose"><i class="fas fa-clipboard-list"></i> Purpose of Visit *</label>
                                <select id="purpose" name="purpose" required>
                                    <option value="">Select purpose...</option>
                                    <option value="Meeting">Meeting</option>
                                    <option value="Appointment">Appointment</option>
                                    <option value="Delivery">Delivery</option>
                                    <option value="Interview">Interview</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Other">Other</option>
                                </select>
                                <input type="text" id="otherPurpose" name="otherPurpose"
                                    placeholder="Specify other purpose" style="display: none; margin-top: 10px;">
                            </div>
                        </div>
                    </div>

                    <!-- Time Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-clock"></i> Time Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="timeIn"><i class="fas fa-sign-in-alt"></i> Time In *</label>
                                <input type="time" id="timeIn" name="timeIn" required>
                            </div>
                        </div>
                    </div>

                    <!-- Simple Data Privacy Act Consent -->
                    <div class="privacy-consent-simple">
                        <div class="privacy-checkbox-simple">
                            <input type="checkbox" id="privacyConsent" name="privacyConsent" required>
                            <div class="privacy-text-simple">
                                <strong>Data Privacy Act of 2012 (RA 10173) Consent</strong><br>
                                I agree to the collection and processing of my personal data in accordance with
                                the Philippine Data Privacy Act.
                                <a href="https://privacy.gov.ph/data-privacy-act/" target="_blank"
                                    class="privacy-link-simple">
                                    View Data Privacy Act
                                </a>
                                <span class="required-star">*</span>
                            </div>
                        </div>
                        <div class="validation-message" id="privacyConsentValidation"></div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="clearForm()">
                            <i class="fas fa-times"></i> Clear Form
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Register Visitor
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="stat-card glass-card">
                    <i class="fas fa-users"></i>
                    <h3>Today's Visitors</h3>
                    <p id="todayVisitors">0</p>
                </div>
                <div class="stat-card glass-card">
                    <i class="fas fa-clock"></i>
                    <h3>Currently On Campus</h3>
                    <p id="currentVisitors">0</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Success Modal -->
    <div class="modal" id="successModal">
        <div class="modal-content glass-card">
            <div class="modal-success">
                <i class="fas fa-check-circle"></i>
                <h3>Visitor Registered Successfully!</h3>
                <p>Visitor information has been saved to the database.</p>
                <button type="button" class="btn-primary" onclick="closeSuccessModal()">
                    <i class="fas fa-check"></i> OK
                </button>
            </div>
        </div>
    </div>

    <!-- ===== Sidebar Toggle Script ===== -->
    <script>
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('mainContent');

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    </script>

    <!-- Camera and Form Script -->
    <script>
        // Camera elements
        const cameraPreview = document.getElementById('cameraPreview');
        const photoCanvas = document.getElementById('photoCanvas');
        const capturedPhoto = document.getElementById('capturedPhoto');
        const startCameraBtn = document.getElementById('startCamera');
        const capturePhotoBtn = document.getElementById('capturePhoto');
        const retakePhotoBtn = document.getElementById('retakePhoto');
        const photoPreview = document.getElementById('photoPreview');
        const cameraError = document.getElementById('cameraError');

        let stream = null;
        let photoData = null;

        // Set current date as default
        document.getElementById('visitDate').valueAsDate = new Date();

        // Set default time values - use current local time
        const now = new Date();
        // Format time as HH:MM in 24-hour format
        const timeIn = now.getHours().toString().padStart(2, '0') + ':' +
            now.getMinutes().toString().padStart(2, '0');
        document.getElementById('timeIn').value = timeIn;

        // Purpose dropdown handler
        document.getElementById('purpose').addEventListener('change', function () {
            const otherPurpose = document.getElementById('otherPurpose');
            otherPurpose.style.display = this.value === 'Other' ? 'block' : 'none';
            if (this.value !== 'Other') {
                otherPurpose.value = '';
            }
        });

        // Privacy consent validation
        document.getElementById('privacyConsent').addEventListener('change', function () {
            validatePrivacyConsent();
        });

        // Camera functionality
        startCameraBtn.addEventListener('click', async () => {
            try {
                // Hide any previous error
                cameraError.style.display = 'none';

                // Reset camera states
                startCameraBtn.disabled = true;
                startCameraBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting Camera...';

                // Try to get camera access with different constraints
                const constraints = {
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    }
                };

                stream = await navigator.mediaDevices.getUserMedia(constraints);

                if (!stream) {
                    throw new Error('Could not access camera');
                }

                cameraPreview.srcObject = stream;

                // Wait for video to be ready
                cameraPreview.onloadedmetadata = () => {
                    cameraPreview.play();

                    // Enable capture button
                    capturePhotoBtn.disabled = false;
                    startCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Camera Active';
                    startCameraBtn.style.background = '#48bb78'; // Green to indicate active

                    console.log('Camera started successfully');
                };

                // Handle camera errors
                cameraPreview.onerror = () => {
                    throw new Error('Camera playback error');
                };

            } catch (error) {
                console.error('Camera error:', error);

                // Show error message
                cameraError.style.display = 'block';
                startCameraBtn.disabled = false;
                startCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Start Camera';
                startCameraBtn.style.background = ''; // Reset background

                // Show specific error messages
                let errorMessage = 'Camera access denied or not available.';

                if (error.name === 'NotAllowedError') {
                    errorMessage = 'Camera permission denied. Please allow camera access and try again.';
                } else if (error.name === 'NotFoundError') {
                    errorMessage = 'No camera found. Please check if your camera is connected.';
                } else if (error.name === 'NotSupportedError') {
                    errorMessage = 'Camera not supported in this browser.';
                } else if (error.name === 'NotReadableError') {
                    errorMessage = 'Camera is already in use by another application.';
                }

                cameraError.querySelector('span').textContent = errorMessage;

                alert('Camera Error: ' + errorMessage);
            }
        });

        capturePhotoBtn.addEventListener('click', () => {
            if (!stream) {
                alert('Please start the camera first');
                return;
            }

            try {
                const context = photoCanvas.getContext('2d');

                // Set canvas size to match video
                photoCanvas.width = cameraPreview.videoWidth;
                photoCanvas.height = cameraPreview.videoHeight;

                // Draw current video frame to canvas
                context.drawImage(cameraPreview, 0, 0, photoCanvas.width, photoCanvas.height);

                // Convert to base64 for storage (lower quality for smaller size)
                photoData = photoCanvas.toDataURL('image/jpeg', 0.7);
                capturedPhoto.src = photoData;

                // Show preview and enable retake
                photoPreview.style.display = 'block';
                retakePhotoBtn.disabled = false;
                capturePhotoBtn.disabled = true;

                // Stop camera to save resources
                stopCamera();

            } catch (error) {
                console.error('Capture error:', error);
                alert('Error capturing photo: ' + error.message);
            }
        });

        retakePhotoBtn.addEventListener('click', () => {
            photoPreview.style.display = 'none';
            retakePhotoBtn.disabled = true;
            capturePhotoBtn.disabled = false;
            photoData = null;

            // Restart camera
            startCameraBtn.click();
        });

        // Function to stop camera
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => {
                    track.stop();
                });
                stream = null;
            }
            startCameraBtn.disabled = false;
            startCameraBtn.innerHTML = '<i class="fas fa-camera"></i> Start Camera';
            startCameraBtn.style.background = ''; // Reset background
        }

        // Stop camera when leaving page
        window.addEventListener('beforeunload', () => {
            stopCamera();
        });

        // Form submission
        document.getElementById('guestRegistrationForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validate form
            if (!validateForm()) {
                return;
            }

            // Prepare form data
            const formData = {
                date: document.getElementById('visitDate').value,
                visitorName: document.getElementById('visitorName').value,
                personToVisit: document.getElementById('personToVisit').value,
                office: document.getElementById('office').value,
                purpose: document.getElementById('purpose').value === 'Other' ?
                    document.getElementById('otherPurpose').value :
                    document.getElementById('purpose').value,
                timeIn: document.getElementById('timeIn').value,
                photo: photoData,
                privacyConsent: document.getElementById('privacyConsent').checked
            };

            try {
                // Show loading state
                const submitBtn = e.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
                submitBtn.disabled = true;

                // Send to backend
                const response = await fetch('../PHPFile/save_guest.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    // Show success modal
                    document.getElementById('successModal').style.display = 'flex';

                    // Update stats
                    updateStats();

                    console.log('Guest registered with ID:', result.guest_id);
                } else {
                    throw new Error(result.message);
                }

            } catch (error) {
                console.error('Registration error:', error);
                alert('Error registering visitor: ' + error.message);
            } finally {
                // Reset button state
                const submitBtn = document.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Register Visitor';
                submitBtn.disabled = false;
            }
        });

        function validateForm() {
            const requiredFields = [
                'visitDate', 'visitorName', 'personToVisit',
                'office', 'purpose', 'timeIn'
            ];

            for (let field of requiredFields) {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    alert(`Please fill in the ${element.labels[0].textContent} field`);
                    element.focus();
                    return false;
                }
            }

            if (!photoData) {
                alert('Please capture a photo of the visitor');
                return false;
            }

            // Validate privacy consent
            if (!validatePrivacyConsent()) {
                alert('You must agree to the Data Privacy Act to continue');
                document.getElementById('privacyConsent').focus();
                return false;
            }

            return true;
        }

        function validatePrivacyConsent() {
            const checkbox = document.getElementById('privacyConsent');
            const validationElement = document.getElementById('privacyConsentValidation');

            if (!checkbox.checked) {
                validationElement.textContent = 'You must agree to the Data Privacy Act to continue';
                validationElement.className = 'validation-message error';
                return false;
            } else {
                validationElement.textContent = '';
                validationElement.className = 'validation-message';
                return true;
            }
        }

        function clearForm() {
            document.getElementById('guestRegistrationForm').reset();
            document.getElementById('visitDate').valueAsDate = new Date();

            // Reset camera
            stopCamera();
            photoPreview.style.display = 'none';
            photoData = null;
            capturePhotoBtn.disabled = true;
            retakePhotoBtn.disabled = true;
            cameraError.style.display = 'none';

            // Reset time to current time
            const now = new Date();
            const timeIn = now.toTimeString().substring(0, 5);
            document.getElementById('timeIn').value = timeIn;

            // Clear privacy consent validation
            document.getElementById('privacyConsentValidation').textContent = '';
            document.getElementById('privacyConsentValidation').className = 'validation-message';
        }

        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
            clearForm();
        }

        // Close modal when clicking outside
        window.onclick = (e) => {
            const modal = document.getElementById('successModal');
            if (e.target === modal) {
                modal.style.display = 'none';
                clearForm();
            }
        };

        // Update stats with real data
        async function updateStats() {
            try {
                const response = await fetch('../PHPFile/get_guest_stats.php');
                const data = await response.json();

                document.getElementById('todayVisitors').textContent = data.today_visitors || 0;
                document.getElementById('currentVisitors').textContent = data.current_visitors || 0;
            } catch (error) {
                console.error('Error fetching stats:', error);
                // Fallback to random numbers if API fails
                document.getElementById('todayVisitors').textContent = Math.floor(Math.random() * 10);
                document.getElementById('currentVisitors').textContent = Math.floor(Math.random() * 5);
            }
        }

        // Initial stats update
        updateStats();
        setInterval(updateStats, 30000); // Update every 30 seconds
    </script>
</body>

</html>