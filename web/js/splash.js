// Configuration
const CONFIG = {
    CHECK_INTERVAL: 1500,
    RFID_TIMEOUT: 600000,
    AUTO_START_RFID: true,
    REDIRECT_DELAY: 2000,
    MAX_RFID_START_ATTEMPTS: 3,
    REQUIRE_ALL_SYSTEMS: false,
    STATUS_MESSAGES: [
        "Initializing System...",
        "Checking Database Connection...",
        "Starting RFID Listener...",
        "Testing System APIs...",
        "Finalizing Setup...",
        "System Status Check Complete"
    ]
};

// State Management
class SplashScreenManager {
    constructor() {
        this.currentStatus = 0;
        this.statusInterval = null;
        this.systemChecksComplete = false;
        this.rfidStartAttempts = 0;
        this.systemStatus = {
            database: false,
            rfid: false,
            api: false
        };
        this.statusManager = new StatusManager();
        
        this.initializeElements();
        this.setupEventListeners();
    }
    
    initializeElements() {
        this.elements = {
            statusText: document.getElementById('statusText'),
            loadingPercentage: document.getElementById('loadingPercentage'),
            loadingProgress: document.querySelector('.loading-progress'),
            skipBtn: document.getElementById('skipBtn'),
            rfidStatus: document.getElementById('rfidStatus'),
            rfidIndicator: document.getElementById('rfidIndicator'),
            dbStatus: document.getElementById('dbStatus'),
            dbIndicator: document.getElementById('dbIndicator'),
            apiStatus: document.getElementById('apiStatus'),
            apiIndicator: document.getElementById('apiIndicator')
        };
    }
    
    setupEventListeners() {
        this.elements.skipBtn.addEventListener('click', () => this.skipSplash());
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' || e.key === ' ' || e.key === 'Enter') {
                this.skipSplash();
            }
        });
        
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.feature') && 
                !e.target.closest('.system-info') && 
                e.target !== this.elements.skipBtn) {
                this.skipSplash();
            }
        });
    }
    
    updateProgress(percentage) {
        if (this.elements.loadingPercentage) {
            this.elements.loadingPercentage.textContent = `${percentage}%`;
            this.elements.loadingProgress.setAttribute('aria-valuenow', percentage);
        }
    }
    
    updateStatus(indicator, statusElement, status, message) {
        indicator.className = 'status-indicator';
        statusElement.textContent = message;
        
        const statusClass = `status-${status}`;
        indicator.classList.add(statusClass);
    }
    
    async initializeSplash() {
        if (sessionStorage.getItem('splashSeen')) {
            this.redirectToLogin();
            return;
        }
        
        this.setupLogoFallback();
        this.setupStatusManager();
        this.startStatusUpdates();
    }
    
    setupLogoFallback() {
        const logo = document.querySelector('.logo img');
        if (logo) {
            logo.addEventListener('error', function() {
                this.style.display = 'none';
                const fallbackIcon = document.createElement('i');
                fallbackIcon.className = 'fas fa-shield-alt';
                fallbackIcon.style.fontSize = '3rem';
                fallbackIcon.style.color = 'white';
                this.parentNode.appendChild(fallbackIcon);
            });
        }
    }
    
    setupStatusManager() {
        this.statusManager.addCheck('Database', () => this.checkDatabaseConnection());
        this.statusManager.addCheck('RFID', () => this.checkRFIDListener());
        this.statusManager.addCheck('API', () => this.checkSystemAPI());
    }
    
    startStatusUpdates() {
        this.statusInterval = setInterval(async () => {
            if (this.currentStatus < CONFIG.STATUS_MESSAGES.length) {
                const progress = Math.round((this.currentStatus / CONFIG.STATUS_MESSAGES.length) * 100);
                this.updateProgress(progress);
                
                this.elements.statusText.textContent = CONFIG.STATUS_MESSAGES[this.currentStatus];
                
                await this.performStatusChecks();
                this.currentStatus++;
            } else {
                this.finalizeLoading();
            }
        }, CONFIG.CHECK_INTERVAL);
    }
    
    async performStatusChecks() {
        switch(this.currentStatus) {
            case 1: // Database check
                await this.checkDatabaseConnection();
                break;
            case 2: // RFID check
                await this.checkRFIDListener();
                this.startRFIDStatusPolling();
                break;
            case 3: // API check
                await this.checkSystemAPI();
                break;
        }
    }
    
    async finalizeLoading() {
        clearInterval(this.statusInterval);
        this.systemChecksComplete = true;
        
        setTimeout(async () => {
            const finalResults = await this.statusManager.performChecks();
            
            // Update system status based on final checks
            this.systemStatus.database = finalResults.Database?.success || false;
            this.systemStatus.rfid = finalResults.RFID?.success || false;
            this.systemStatus.api = finalResults.API?.success || false;
            
            const allSystemsReady = this.systemStatus.database && this.systemStatus.rfid && this.systemStatus.api;
            const someSystemsReady = this.systemStatus.database || this.systemStatus.rfid || this.systemStatus.api;
            
            this.updateProgress(100);
            
            if (CONFIG.REQUIRE_ALL_SYSTEMS && !allSystemsReady) {
                // Don't redirect - show warning and let user decide
                this.showSystemStatusWarning();
            } else if (allSystemsReady) {
                // All systems ready - auto redirect to LOGIN
                this.elements.statusText.textContent = "All Systems Ready - Redirecting to Login...";
                this.redirectWithDelay();
            } else {
                // Some systems ready - show status and let user proceed
                this.elements.statusText.textContent = "System Check Complete - Some services unavailable";
                this.showSystemStatusWarning();
            }
            
        }, 3000);
    }
    
    showSystemStatusWarning() {
        // Create status overview
        const statusHtml = `
            <div class="system-status-overview">
                <h4>System Status</h4>
                <div class="status-item ${this.systemStatus.database ? 'status-ok' : 'status-error'}">
                    <span class="status-icon">${this.systemStatus.database ? '✓' : '✗'}</span>
                    <span>Database: ${this.systemStatus.database ? 'Connected' : 'Not Connected'}</span>
                </div>
                <div class="status-item ${this.systemStatus.rfid ? 'status-ok' : 'status-error'}">
                    <span class="status-icon">${this.systemStatus.rfid ? '✓' : '✗'}</span>
                    <span>RFID Listener: ${this.systemStatus.rfid ? 'Connected' : 'Not Connected'}</span>
                </div>
                <div class="status-item ${this.systemStatus.api ? 'status-ok' : 'status-error'}">
                    <span class="status-icon">${this.systemStatus.api ? '✓' : '✗'}</span>
                    <span>System API: ${this.systemStatus.api ? 'Connected' : 'Not Connected'}</span>
                </div>
                <div class="warning-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    Some systems are not available. You may experience limited functionality.
                </div>
                <div class="action-buttons">
                    <button class="btn-continue" id="continueBtn">
                        <i class="fas fa-play"></i> Continue to Login
                    </button>
                    <button class="btn-retry" id="retryBtn">
                        <i class="fas fa-redo"></i> Retry System Check
                    </button>
                </div>
            </div>
        `;
        
        // Update the status text area
        this.elements.statusText.innerHTML = statusHtml;
        
        // Add event listeners
        document.getElementById('continueBtn').addEventListener('click', () => {
            this.redirectToLogin();
        });
        
        document.getElementById('retryBtn').addEventListener('click', () => {
            this.retrySystemCheck();
        });
        
        // Also update skip button text
        this.elements.skipBtn.innerHTML = '<i class="fas fa-forward"></i> Continue to Login';
    }
    
    retrySystemCheck() {
        // Reset state
        this.currentStatus = 0;
        this.systemChecksComplete = false;
        this.rfidStartAttempts = 0;
        
        // Reset status indicators
        this.updateStatus(this.elements.dbIndicator, this.elements.dbStatus, 'connecting', 'Checking...');
        this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'connecting', 'Checking...');
        this.updateStatus(this.elements.apiIndicator, this.elements.apiStatus, 'connecting', 'Checking...');
        
        // Reset status text
        this.elements.statusText.textContent = CONFIG.STATUS_MESSAGES[0];
        this.updateProgress(0);
        
        // Restart the status updates
        this.startStatusUpdates();
    }
    
    redirectWithDelay() {
        setTimeout(() => {
            sessionStorage.setItem('splashSeen', 'true');
            this.redirectToLogin();
        }, CONFIG.REDIRECT_DELAY);
    }
    
    // Database connection check
    async checkDatabaseConnection() {
        this.updateStatus(this.elements.dbIndicator, this.elements.dbStatus, 'connecting', 'Testing connection...');
        
        try {
            const response = await fetch('../entrysense_api/database/test_pdo.php', {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (response.ok) {
                const data = await response.text();
                if (data.includes('success') || data.includes('Connected')) {
                    this.updateStatus(this.elements.dbIndicator, this.elements.dbStatus, 'connected', 'Connected');
                    this.systemStatus.database = true;
                    return true;
                } else {
                    this.updateStatus(this.elements.dbIndicator, this.elements.dbStatus, 'warning', 'Connected (No data)');
                    this.systemStatus.database = true;
                    return true;
                }
            } else {
                throw new Error('HTTP ' + response.status);
            }
        } catch (error) {
            console.error('Database check failed:', error);
            this.updateStatus(this.elements.dbIndicator, this.elements.dbStatus, 'error', 'Connection failed');
            this.systemStatus.database = false;
            return false;
        }
    }
    
    // RFID data file check
    async checkRFIDDataFile() {
        try {
            console.log('Checking RFID data file...');
            
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000);
            
            const response = await fetch('../data/last_entry.json?t=' + Date.now(), {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                },
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                console.log('RFID data file not found or inaccessible');
                return { isRunning: false, data: null };
            }
            
            const data = await response.json();
            console.log('RFID data found:', data);
            
            // Check if we have valid data and recent activity
            const hasValidData = data && Object.keys(data).length > 0;
            const isRecent = data.timestamp ? 
                (Date.now() - new Date(data.timestamp).getTime() < CONFIG.RFID_TIMEOUT) : 
                false;
            
            return {
                isRunning: hasValidData && isRecent,
                data: data,
                hasData: hasValidData,
                isRecent: isRecent
            };
            
        } catch (error) {
            if (error.name === 'AbortError') {
                console.log('RFID data file check timed out');
            } else {
                console.log('RFID data file check failed:', error.message);
            }
            return { isRunning: false, data: null, hasData: false, isRecent: false };
        }
    }
    
    // Execute batch file to start RFID listener - CORRECTED PATH
    async executeBatchFile() {
        this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'connecting', 'Starting RFID Listener...');
        
        try {
            console.log('Executing RFID batch file...');
            
            // CORRECTED PATH: PHPFile/start_rfid_listener.php
            const response = await fetch('../entrysense_api/util/start_rfid_listener.php?t=' + Date.now(), {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            console.log('Batch file execution result:', result);
            
            // Log debug information
            if (result.debug) {
                console.log('Debug info:', result.debug);
            }
            
            if (result.success) {
                if (result.already_running) {
                    this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'connected', 'Already Running');
                } else {
                    this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'connecting', 'Listener starting...');
                }
                return { success: true, message: result.message, debug: result.debug };
            } else {
                // Show detailed error message
                const errorMsg = result.debug ? 
                    `Failed: ${result.message} (Check console for details)` : 
                    result.message;
                
                throw new Error(errorMsg);
            }
            
        } catch (error) {
            console.error('Batch file execution failed:', error);
            
            // Show detailed error in status
            const errorMessage = error.message.includes('Check console') ? 
                'Failed to start (see console)' : 
                `Failed: ${error.message}`;
                
            this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'error', errorMessage);
            
            return { 
                success: false, 
                message: error.message,
                fullError: error.toString()
            };
        }
    }
    
    // Check if RFID listener Java process is running - CORRECTED PATH
    async checkRFIDProcess() {
        try {
            // CORRECTED PATH: PHPFile/check_rfid_process.php
            const response = await fetch('../entrysense_.api/util/check_rfid_process.php?t=' + Date.now(), {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                return result;
            }
            return { isRunning: false };
        } catch (error) {
            console.log('RFID process check failed:', error);
            return { isRunning: false };
        }
    }
    
    // Main RFID listener check with batch file execution
    async checkRFIDListener() {
        this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'connecting', 'Checking RFID Listener...');
        
        try {
            // First, check if RFID is already running
            const fileCheck = await this.checkRFIDDataFile();
            const processCheck = await this.checkRFIDProcess();
            
            console.log('RFID Status Check:', { fileCheck, processCheck });
            
            // If already running and receiving data
            if (fileCheck.isRunning && processCheck.isRunning) {
                this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'connected', 'Active & Receiving Data');
                this.systemStatus.rfid = true;
                return true;
            }
            
            // If process is running but no recent data
            if (processCheck.isRunning && !fileCheck.isRunning) {
                this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'warning', 'Running (No recent data)');
                this.systemStatus.rfid = true;
                return true;
            }
            
            // If not running and we should auto-start
            if (CONFIG.AUTO_START_RFID && this.rfidStartAttempts < CONFIG.MAX_RFID_START_ATTEMPTS) {
                this.rfidStartAttempts++;
                
                this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'connecting', `Starting RFID (Attempt ${this.rfidStartAttempts}/${CONFIG.MAX_RFID_START_ATTEMPTS})`);
                
                // Execute the batch file
                const batchResult = await this.executeBatchFile();
                
                if (batchResult.success) {
                    // Wait for Java process to initialize
                    this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'connecting', 'Waiting for RFID to initialize...');
                    
                    // Check multiple times with delays
                    for (let i = 0; i < 5; i++) {
                        await new Promise(resolve => setTimeout(resolve, 2000));
                        
                        const postStartCheck = await this.checkRFIDDataFile();
                        const postProcessCheck = await this.checkRFIDProcess();
                        
                        if (postProcessCheck.isRunning && postStartCheck.hasData) {
                            this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'connected', 'Active & Receiving Data');
                            this.systemStatus.rfid = true;
                            return true;
                        } else if (postProcessCheck.isRunning) {
                            this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'warning', 'Running (Waiting for data)');
                            this.systemStatus.rfid = true;
                            return true;
                        }
                    }
                    
                    this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'warning', 'Started (No data yet)');
                    this.systemStatus.rfid = true;
                    return true;
                } else {
                    this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'error', 'Failed to start');
                    this.systemStatus.rfid = false;
                    return false;
                }
            } else {
                this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'error', 'Not running');
                this.systemStatus.rfid = false;
                return false;
            }
            
        } catch (error) {
            console.error('RFID check failed:', error);
            this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'error', 'Check failed');
            this.systemStatus.rfid = false;
            return false;
        }
    }
    
    // System API check - CORRECTED PATH (this one was already correct)
    async checkSystemAPI() {
        this.updateStatus(this.elements.apiIndicator, this.elements.apiStatus, 'connecting', 'Testing APIs...');
        
        try {
            const response = await fetch('../entrysense_api/api/get_rfid_counts.php?t=' + Date.now(), {
                headers: {
                    'Cache-control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.updateStatus(this.elements.apiIndicator, this.elements.apiStatus, 'connected', 'API Ready');
                this.systemStatus.api = true;
                return true;
            } else {
                throw new Error('API not responding');
            }
        } catch (error) {
            console.error('API check failed:', error);
            this.updateStatus(this.elements.apiIndicator, this.elements.apiStatus, 'error', 'API Error');
            this.systemStatus.api = false;
            return false;
        }
    }
    
    // RFID status polling
    startRFIDStatusPolling() {
        setInterval(async () => {
            try {
                const fileCheck = await this.checkRFIDDataFile();
                const processCheck = await this.checkRFIDProcess();
                
                if (fileCheck.isRunning && processCheck.isRunning) {
                    if (!this.elements.rfidIndicator.classList.contains('status-connected')) {
                        this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'connected', 'Active & Receiving Data');
                    }
                } else if (processCheck.isRunning) {
                    if (!this.elements.rfidIndicator.classList.contains('status-warning')) {
                        this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'warning', 'Running (No recent data)');
                    }
                } else {
                    if (!this.elements.rfidIndicator.classList.contains('status-error')) {
                        this.updateStatus(this.elements.rfidIndicator, this.elements.rfidStatus, 'error', 'Not running');
                    }
                }
            } catch (error) {
                console.log('RFID polling check failed:', error);
            }
        }, 10000);
    }
    
    // UPDATED: Redirect to login instead of dashboard
    redirectToLogin() {
        window.location.href = 'login.php';
    }
    
    skipSplash() {
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
        }
        sessionStorage.setItem('splashSeen', 'true');
        
        // Show warning if systems aren't ready, but allow continue
        const allSystemsReady = this.systemStatus.database && this.systemStatus.rfid && this.systemStatus.api;
        if (!allSystemsReady && !this.systemChecksComplete) {
            this.showSystemStatusWarning();
        } else {
            this.redirectToLogin();
        }
    }
}

// Status Manager Class
class StatusManager {
    constructor() {
        this.checks = [];
        this.maxRetries = 2;
    }
    
    addCheck(name, checkFunction) {
        this.checks.push({ name, func: checkFunction, retries: 0 });
    }
    
    async performChecks() {
        const results = {};
        
        for (let check of this.checks) {
            try {
                results[check.name] = await this.executeWithRetry(check);
            } catch (error) {
                results[check.name] = { success: false, error: error.message };
            }
        }
        
        return results;
    }
    
    async executeWithRetry(check) {
        try {
            const result = await check.func();
            return { success: true, data: result };
        } catch (error) {
            if (check.retries < this.maxRetries) {
                check.retries++;
                await new Promise(resolve => setTimeout(resolve, 1000));
                return await this.executeWithRetry(check);
            }
            throw error;
        }
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    const splashManager = new SplashScreenManager();
    splashManager.initializeSplash();
});