// Control Panel JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // RFID Listener Controls
    const startListenerBtn = document.getElementById('startListenerBtn');
    const stopListenerBtn = document.getElementById('stopListenerBtn');
    const rfidStatusText = document.getElementById('rfidStatusText');
    const rfidStatusDetail = document.getElementById('rfidStatusDetail');
    const processStatus = document.getElementById('processStatus');

    // Gate Controls
    const openGateBtn = document.getElementById('openGateBtn');
    const closeGateBtn = document.getElementById('closeGateBtn');
    const gateStatusText = document.getElementById('gateStatusText');
    const gateStatusDetail = document.getElementById('gateStatusDetail');

    // Emergency Controls
    const emergencyStopBtn = document.getElementById('emergencyStopBtn');
    const lockSystemBtn = document.getElementById('lockSystemBtn');

    // Modal Elements
    const modal = document.getElementById('confirmationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalConfirm = document.getElementById('modalConfirm');
    const modalCancel = document.getElementById('modalCancel');
    const closeModal = document.querySelector('.close-modal');

    // ===== RFID LISTENER CONTROLS =====
    startListenerBtn.addEventListener('click', function() {
        showConfirmationModal(
            'Start RFID Listener',
            'Are you sure you want to start the RFID listener? This will begin scanning for RFID cards.',
            function() {
                startRFIDListener();
            }
        );
    });

    stopListenerBtn.addEventListener('click', function() {
        showConfirmationModal(
            'Stop RFID Listener',
            'Are you sure you want to stop the RFID listener? This will halt all RFID scanning.',
            function() {
                stopRFIDListener();
            }
        );
    });

    function startRFIDListener() {
        fetch('../PHPFile/start_rfid_listener.php')
            .then(response => response.json())
            .then(data => {
                console.log('Start response:', data);
                if (data.success) {
                    showNotification('RFID Listener started successfully', 'success');
                    updateRFIDStatus(true);
                    addToControlLog('RFID Listener started', 'success');
                } else {
                    showNotification('Failed to start RFID Listener: ' + data.message, 'error');
                    addToControlLog('Failed to start RFID Listener', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error starting RFID Listener', 'error');
                addToControlLog('Error starting RFID Listener', 'error');
            });
    }

    function stopRFIDListener() {
        fetch('../PHPFile/stop_rfid_listener.php')
            .then(response => response.json())
            .then(data => {
                console.log('Stop response:', data);
                if (data.success) {
                    showNotification('RFID Listener stopped successfully', 'success');
                    updateRFIDStatus(false);
                    addToControlLog('RFID Listener stopped', 'success');
                } else {
                    showNotification('Failed to stop RFID Listener: ' + data.message, 'error');
                    addToControlLog('Failed to stop RFID Listener', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error stopping RFID Listener', 'error');
                addToControlLog('Error stopping RFID Listener', 'error');
            });
    }

    function updateRFIDStatus(isRunning) {
        const statusText = isRunning ? 'Running' : 'Stopped';
        const statusDetail = isRunning ? 'Active and scanning' : 'Not running';
        
        rfidStatusText.textContent = statusText;
        rfidStatusDetail.textContent = statusDetail;
        processStatus.textContent = statusText;

        // Update buttons
        startListenerBtn.disabled = isRunning;
        startListenerBtn.classList.toggle('disabled', isRunning);
        stopListenerBtn.disabled = !isRunning;
        stopListenerBtn.classList.toggle('disabled', !isRunning);

        // Update status icon
        const statusIcon = document.querySelector('.status-card .status-icon');
        statusIcon.className = `status-icon ${isRunning ? 'running' : 'stopped'}`;
    }

    // ===== GATE CONTROLS =====
    openGateBtn.addEventListener('click', function() {
        showConfirmationModal(
            'Open Gate',
            'Are you sure you want to manually open the gate? The gate will stay open until manually closed.',
            function() {
                openGate();
            }
        );
    });

    closeGateBtn.addEventListener('click', function() {
        showConfirmationModal(
            'Close Gate',
            'Are you sure you want to manually close the gate?',
            function() {
                closeGate();
            }
        );
    });

    function openGate() {
        fetch('../PHPFile/control_gate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=open'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Gate open response:', data);
            if (data.success) {
                updateGateStatus('Open', 'Gate manually opened - Close manually when needed', 'open');
                addToControlLog('Gate manually opened', 'success');
                showNotification('Gate opened successfully - Close manually when needed', 'success');
            } else {
                showNotification('Failed to open gate: ' + data.message, 'error');
                addToControlLog('Failed to open gate', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error opening gate', 'error');
            addToControlLog('Error opening gate', 'error');
        });
    }

    function closeGate() {
        fetch('../PHPFile/control_gate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=close'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Gate close response:', data);
            if (data.success) {
                updateGateStatus('Closed', 'Gate manually closed', 'closed');
                addToControlLog('Gate manually closed', 'success');
                showNotification('Gate closed successfully', 'success');
            } else {
                showNotification('Failed to close gate: ' + data.message, 'error');
                addToControlLog('Failed to close gate', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error closing gate', 'error');
            addToControlLog('Error closing gate', 'error');
        });
    }

    function updateGateStatus(status, detail, type) {
        gateStatusText.textContent = status;
        gateStatusDetail.textContent = detail;
        
        const statusIcon = document.querySelectorAll('.status-card .status-icon')[1];
        statusIcon.className = `status-icon ${type}`;
    }

    // ===== EMERGENCY CONTROLS =====
    emergencyStopBtn.addEventListener('click', function() {
        showConfirmationModal(
            'Emergency Stop',
            '⚠️ EMERGENCY STOP - This will immediately stop ALL systems including RFID listening and gate control. Are you absolutely sure?',
            function() {
                emergencyStop();
            },
            'emergency'
        );
    });

    lockSystemBtn.addEventListener('click', function() {
        showConfirmationModal(
            'Lock System',
            'This will lock down the entire system and disable all access. Are you sure?',
            function() {
                lockSystem();
            }
        );
    });

    function emergencyStop() {
        // Stop RFID listener first
        stopRFIDListener();
        
        // Close gate
        closeGate();
        
        // Additional emergency actions
        showNotification('EMERGENCY STOP ACTIVATED - All systems halted', 'emergency');
        addToControlLog('EMERGENCY STOP ACTIVATED', 'emergency');
        
        // Disable all controls temporarily
        disableAllControls(true);
        setTimeout(() => disableAllControls(false), 10000);
    }

    function lockSystem() {
        // Stop RFID listener
        stopRFIDListener();
        
        // Close and lock gate
        closeGate();
        updateGateStatus('Locked', 'System locked down', 'stopped');
        
        showNotification('System locked down - All access disabled', 'warning');
        addToControlLog('System locked down', 'warning');
    }

    function disableAllControls(disabled) {
        const controls = document.querySelectorAll('.control-btn');
        controls.forEach(btn => {
            btn.disabled = disabled;
            btn.classList.toggle('disabled', disabled);
        });
    }

    // ===== MODAL CONTROLS =====
    function showConfirmationModal(title, message, confirmCallback, type = 'normal') {
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        
        // Style based on type
        if (type === 'emergency') {
            modalConfirm.className = 'btn-primary emergency';
            modalMessage.style.color = '#ff4444';
        } else {
            modalConfirm.className = 'btn-primary';
            modalMessage.style.color = '';
        }
        
        modal.style.display = 'flex';
        
        // Set up confirm action
        modalConfirm.onclick = function() {
            modal.style.display = 'none';
            confirmCallback();
        };
    }

    modalCancel.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // ===== UTILITY FUNCTIONS =====
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="close-notification">&times;</button>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
        
        // Close button
        notification.querySelector('.close-notification').addEventListener('click', function() {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }

    function getNotificationIcon(type) {
        switch(type) {
            case 'success': return 'check-circle';
            case 'error': return 'exclamation-circle';
            case 'warning': return 'exclamation-triangle';
            case 'emergency': return 'radiation';
            default: return 'info-circle';
        }
    }

    function addToControlLog(action, type) {
        const controlLog = document.getElementById('controlLog');
        const logEmpty = controlLog.querySelector('.log-empty');
        
        // Remove empty state if it exists
        if (logEmpty) {
            logEmpty.remove();
        }
        
        // Create log entry
        const logEntry = document.createElement('div');
        logEntry.className = `log-entry ${type}`;
        
        const timestamp = new Date().toLocaleTimeString();
        logEntry.innerHTML = `
            <div class="log-icon">
                <i class="fas fa-${getLogIcon(type)}"></i>
            </div>
            <div class="log-content">
                <div class="log-action">${action}</div>
                <div class="log-time">${timestamp}</div>
            </div>
        `;
        
        // Add to top of log
        controlLog.insertBefore(logEntry, controlLog.firstChild);
        
        // Limit log entries to 10
        const entries = controlLog.querySelectorAll('.log-entry');
        if (entries.length > 10) {
            entries[entries.length - 1].remove();
        }
    }

    function getLogIcon(type) {
        switch(type) {
            case 'success': return 'check-circle';
            case 'error': return 'times-circle';
            case 'warning': return 'exclamation-triangle';
            case 'emergency': return 'radiation';
            case 'info': return 'info-circle';
            default: return 'circle';
        }
    }

    // ===== INITIAL STATUS CHECK =====
    function checkInitialStatus() {
        fetch('../PHPFile/check_rfid_process.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateRFIDStatus(data.rfidRunning);
                }
            })
            .catch(error => {
                console.error('Error checking RFID status:', error);
            });
    }

    // Check status on page load
    checkInitialStatus();
    
    // Check status every 10 seconds
    setInterval(checkInitialStatus, 10000);
});