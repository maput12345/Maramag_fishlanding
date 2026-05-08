/* global __webpack_public_path__ */

if (typeof document !== 'undefined') {
    const currentScript = document.currentScript;
    const scriptSource = currentScript?.src
        || document.getElementsByTagName('script')[document.getElementsByTagName('script').length - 1]?.src;

    if (scriptSource) {
        __webpack_public_path__ = new URL('../', scriptSource).toString();
    }
}

import QrScanner from 'qr-scanner';

class QRScanner {
    constructor() {
        this.scanner = null;
        this.isProcessing = false;
        this.modal = null;
        this.isModalCreated = false;
        this.keepScanning = false;
        this.restartTimeout = null;
        this.lastSuccessfulQrCode = null;
        this.lastSuccessfulAt = null;
    }

    /**
     * Open the QR Scanner modal
     */
    openModal() {
        this.createModal();
        this.keepScanning = true;
        if (this.modal) {
            this.modal.classList.remove('hidden');
        }
    }

    /**
     * Close the QR Scanner modal
     */
    closeModal() {
        this.keepScanning = false;
        this.clearRestartTimeout();
        this.stopScanner();
        if (this.modal) {
            this.modal.classList.add('hidden');
            // Remove modal from DOM
            setTimeout(() => {
                if (this.modal && this.modal.parentNode) {
                    this.modal.parentNode.removeChild(this.modal);
                    this.modal = null;
                    this.isModalCreated = false;
                }
            }, 300);
        }
    }

    /**
     * Create the QR Scanner modal with modern design
     */
    createModal() {
        if (this.isModalCreated) return;

        const modalHTML = `
            <div id="qrScannerModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Backdrop with blur effect -->
                    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <!-- Modern Modal Container -->
                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-200">
                        <!-- Header with gradient -->
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-white bg-opacity-20 backdrop-blur-sm">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-white">QR Code Scanner</h3>
                                        <p class="text-blue-100 text-sm">Point your camera at a QR code</p>
                                    </div>
                                </div>
                                <button id="closeScanner" class="flex-shrink-0 p-2 rounded-full bg-white bg-opacity-20 hover:bg-opacity-30 transition-all duration-200 backdrop-blur-sm">
                                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Scanner Container -->
                        <div class="bg-gray-50 p-6">
                            <!-- Video Container with Modern Frame -->
                            <div class="relative">
                                <!-- Scanner Frame with Corner Indicators -->
                                <div class="relative bg-black rounded-xl overflow-hidden shadow-lg">
                                    <video id="qr-reader" class="w-full h-80 bg-gray-900 object-cover"></video>

                                    <!-- Scanner Overlay -->
                                    <div class="absolute inset-0 pointer-events-none">
                                        <!-- Corner Indicators -->
                                        <div class="absolute top-4 left-4 w-8 h-8 border-l-4 border-t-4 border-blue-400 rounded-tl-lg"></div>
                                        <div class="absolute top-4 right-4 w-8 h-8 border-r-4 border-t-4 border-blue-400 rounded-tr-lg"></div>
                                        <div class="absolute bottom-4 left-4 w-8 h-8 border-l-4 border-b-4 border-blue-400 rounded-bl-lg"></div>
                                        <div class="absolute bottom-4 right-4 w-8 h-8 border-r-4 border-b-4 border-blue-400 rounded-br-lg"></div>

                                        <!-- Static guide line -->
                                        <div id="scanning-line" class="absolute left-4 right-4 top-1/2 h-0.5 bg-gradient-to-r from-transparent via-blue-400 to-transparent opacity-70"></div>

                                        <!-- Center Target -->
                                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-32 h-32 border-2 border-blue-400 rounded-lg opacity-30"></div>
                                    </div>
                                </div>

                                <!-- Status Display -->
                                <div id="qr-status" class="mt-4 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                                        <p class="text-gray-600 font-medium">Initializing camera...</p>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-6 flex space-x-3">
                                    <button id="retry-camera" class="flex-1 inline-flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 hidden">
                                        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Try Again
                                    </button>
                                    <button id="close-scanner-btn" class="flex-1 inline-flex items-center justify-center px-4 py-3 border border-transparent rounded-lg text-sm font-medium text-white bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                                        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Close Scanner
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('qrScannerModal');
        this.isModalCreated = true;
        this.setupModalEventListeners();
    }

    /**
     * Setup modal event listeners
     */
    setupModalEventListeners() {
        // Close buttons
        const closeBtn = document.getElementById('closeScanner');
        const closeScannerBtn = document.getElementById('close-scanner-btn');

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }

        if (closeScannerBtn) {
            closeScannerBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }

        // Retry camera button
        const retryBtn = document.getElementById('retry-camera');
        if (retryBtn) {
            retryBtn.addEventListener('click', () => {
                this.requestCameraPermission();
            });
        }

        // Close modal when clicking outside
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.closeModal();
                }
            });
        }
    }

    /**
     * Start the QR scanner
     */
    async startScanner() {
        if (this.isProcessing || !this.keepScanning) {
            return;
        }

        const videoElement = document.getElementById('qr-reader');
        const statusElement = document.getElementById('qr-status');

        if (!videoElement) {
            return;
        }

        try {
            // Update status with a simple loading spinner
            if (statusElement) {
                statusElement.innerHTML = `
                    <div class="flex items-center justify-center space-x-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                        <p class="text-blue-600 font-medium">Requesting camera permission...</p>
                    </div>
                `;
            }

            // Check camera permissions first
            const hasCamera = await this.checkCameraPermission();
            if (!hasCamera) {
                if (statusElement) {
                    statusElement.innerHTML = `
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-2 mb-2">
                                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <p class="text-red-600 font-medium">Camera access denied</p>
                            </div>
                            <p class="text-gray-500 text-sm">Please allow camera permissions to scan QR codes</p>
                        </div>
                    `;
                }

                // Show retry button
                const retryBtn = document.getElementById('retry-camera');
                if (retryBtn) {
                    retryBtn.classList.remove('hidden');
                }
                return;
            }

            // Update status
            if (statusElement) {
                statusElement.innerHTML = `
                    <div class="flex items-center justify-center space-x-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                        <p class="text-blue-600 font-medium">Starting camera...</p>
                    </div>
                `;
            }

            // Start the scanner
            this.scanner = new QrScanner(videoElement, (result) => {
                this.handleScanResult(result.data);
            }, {
                highlightScanRegion: true,
                highlightCodeOutline: true,
            });

            await this.scanner.start();

            // Show static scan guide
            this.startScanningAnimation();

            // Update status
            if (statusElement) {
                statusElement.innerHTML = `
                    <div class="text-center">
                        <div class="flex items-center justify-center space-x-2 mb-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <p class="text-green-600 font-medium">Camera active</p>
                        </div>
                        <p class="text-gray-600 text-sm">Point your camera at a QR code to scan</p>
                    </div>
                `;
            }

        } catch (error) {
            if (statusElement) {
                statusElement.innerHTML = `
                    <div class="text-center">
                        <p class="text-red-500 mb-2">Camera Permission Required</p>
                        <p class="text-sm text-gray-600">Please allow camera access in your browser settings and try again</p>
                        <button onclick="window.qrScanner.requestCameraPermission()" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded text-sm mr-2">
                            Try Again
                        </button>
                        <button onclick="window.qrScanner.closeModal()" class="mt-2 px-4 py-2 bg-gray-500 text-white rounded text-sm">
                            Close
                        </button>
                    </div>
                `;
            }
        }
    }

    /**
     * Show static scan guide
     */
    startScanningAnimation() {
        const scanningLine = document.getElementById('scanning-line');
        if (scanningLine) {
            scanningLine.style.opacity = '0.7';
        }
    }

    /**
     * Hide static scan guide
     */
    stopScanningAnimation() {
        if (this.scanningInterval) {
            clearInterval(this.scanningInterval);
            this.scanningInterval = null;
        }

        const scanningLine = document.getElementById('scanning-line');
        if (scanningLine) {
            scanningLine.style.opacity = '0';
        }
    }

    /**
     * Check camera permission
     */
    async checkCameraPermission() {
        try {
            // Request camera permission explicitly
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment' // Use back camera on mobile
                }
            });

            // Stop the stream immediately as we just wanted to check permission
            stream.getTracks().forEach(track => track.stop());

            return true;
        } catch (error) {
            return false;
        }
    }

    /**
     * Request camera permission
     */
    async requestCameraPermission() {
        const statusElement = document.getElementById('qr-status');

        if (statusElement) {
            statusElement.innerHTML = `
                <div class="flex items-center justify-center space-x-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                    <p class="text-blue-600 font-medium">Requesting camera permission...</p>
                </div>
            `;
        }

        try {
            const hasPermission = await this.checkCameraPermission();
            if (hasPermission) {
                // Hide retry button
                const retryBtn = document.getElementById('retry-camera');
                if (retryBtn) {
                    retryBtn.classList.add('hidden');
                }

                // Try starting scanner again
                await this.startScanner();
            } else {
                if (statusElement) {
                    statusElement.innerHTML = `
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-2 mb-2">
                                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <p class="text-red-600 font-medium">Camera Permission Denied</p>
                            </div>
                            <p class="text-gray-500 text-sm">Please enable camera access in your browser settings</p>
                        </div>
                    `;
                }

                // Show retry button
                const retryBtn = document.getElementById('retry-camera');
                if (retryBtn) {
                    retryBtn.classList.remove('hidden');
                }
            }
        } catch (error) {
            // Silent error handling
        }
    }

    /**
     * Stop the QR scanner
     */
    stopScanner() {
        // Hide scan guide
        this.stopScanningAnimation();

        if (this.scanner) {
            this.scanner.stop();
            this.scanner.destroy();
            this.scanner = null;
        }
        this.isProcessing = false;
    }

    clearRestartTimeout() {
        if (this.restartTimeout) {
            clearTimeout(this.restartTimeout);
            this.restartTimeout = null;
        }
    }

    isDuplicateSuccessfulScan(qrCode) {
        return this.lastSuccessfulQrCode === qrCode
            && this.lastSuccessfulAt !== null
            && (Date.now() - this.lastSuccessfulAt) < 4000;
    }

    markSuccessfulScan(qrCode) {
        this.lastSuccessfulQrCode = qrCode;
        this.lastSuccessfulAt = Date.now();
    }

    updateStatusMessage(html) {
        const statusElement = document.getElementById('qr-status');
        if (statusElement) {
            statusElement.innerHTML = html;
        }
    }

    restartScannerAfterSuccess(message) {
        if (!this.keepScanning || !this.modal || this.modal.classList.contains('hidden')) {
            return;
        }

        this.updateStatusMessage(`
            <div class="text-center">
                <div class="flex items-center justify-center space-x-2 mb-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <p class="text-green-600 font-medium">Return successful</p>
                </div>
                <p class="text-gray-600 text-sm">${message}</p>
                <p class="mt-1 text-xs text-gray-500">Ready to scan the next fish box...</p>
            </div>
        `);

        this.clearRestartTimeout();
        this.restartTimeout = setTimeout(async () => {
            this.restartTimeout = null;

            if (!this.keepScanning || !this.modal || this.modal.classList.contains('hidden')) {
                return;
            }

            await this.startScanner();
        }, 900);
    }

    /**
     * Handle scan result
     */
    handleScanResult(qrCode) {
        // Prevent multiple processing
        if (this.isProcessing || this.isDuplicateSuccessfulScan(qrCode)) {
            return;
        }

        this.isProcessing = true;
        this.clearRestartTimeout();

        // Stop scanner immediately
        this.stopScanner();

        this.updateStatusMessage(`
            <div class="text-center">
                <div class="flex items-center justify-center space-x-2 mb-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                    <p class="text-blue-600 font-medium">Processing QR Code...</p>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-2">
                    <p class="text-xs text-blue-700 font-mono">${qrCode}</p>
                </div>
            </div>
        `);

        // Call backend handler
        if (window.qrBackendHandler && typeof window.qrBackendHandler.handleQRScanResult === 'function') {
            window.qrBackendHandler.handleQRScanResult(
                qrCode,
                // Success callback
                (result) => {
                    this.markSuccessfulScan(qrCode);
                    this.restartScannerAfterSuccess(result.message || 'Fish box returned successfully.');
                },
                // Error callback
                (error) => {
                    this.closeModal();
                }
            );
        } else {
            this.closeModal();
        }
    }
}

// Make the class available globally
window.QRScanner = QRScanner;
