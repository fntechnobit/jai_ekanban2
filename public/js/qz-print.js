/**
 * QZ-Tray Print Integration
 * Shared functions for printer connection and management
 */

// QZ-Tray Print Helper Functions
var QZPrint = {
    qz: window.qz,
    isConnected: false,
    selectedPrinter: null,
    
    // Initialize QZ-Tray connection
    init: function() {
        if (!this.qz) {
            console.error('QZ-Tray library not loaded');
            return false;
        }
        
        // Set security settings - callback style, NOT promise style
        this.qz.security.setCertificatePromise(function(resolve, reject) {
            resolve();  // Just call resolve directly for unsigned mode
        });
        
        this.qz.security.setSignaturePromise(function(toSign) {
            return function(resolve, reject) {
                resolve();  // Just call resolve directly for unsigned mode
            };
        });
        
        return true;
    },
    
    // Connect to QZ-Tray
    connect: function() {
        var self = this;
        
        if (!this.init()) {
            return Promise.reject('QZ-Tray initialization failed');
        }
        
        return this.qz.websocket.connect().then(function() {
            self.isConnected = true;
            self.updateStatus('Connected', 'success');
            return self.findPrinters();
        }).catch(function(err) {
            self.isConnected = false;
            self.updateStatus('Connection Failed', 'error');
            throw err;
        });
    },
    
    // Disconnect from QZ-Tray
    disconnect: function() {
        var self = this;
        
        return this.qz.websocket.disconnect().then(function() {
            self.isConnected = false;
            self.selectedPrinter = null;
            self.updateStatus('Disconnected', 'info');
            self.clearPrinters();
        });
    },
    
    // Find available printers
    findPrinters: function() {
        var self = this;
        
        return this.qz.printers.find().then(function(printers) {
            console.log('QZ FOUND PRINTERS:', printers);
            console.log('Number of printers found:', printers.length);
            
            // Directly populate the dropdown here
            var $select = $('#printer-select');
            console.log('Printer select element found:', $select.length > 0);
            
            $select.empty();
            $select.append('<option value="">- Select Printer -</option>');
            
            if (printers && printers.length > 0) {
                printers.forEach(function(printer) {
                    console.log('Adding printer to dropdown:', printer);
                    $select.append('<option value="' + printer + '">' + printer + '</option>');
                });
            }
            
            $select.prop('disabled', false);
            console.log('Dropdown is now enabled');
            
            return printers;
        }).catch(function(err) {
            console.error('Error finding printers:', err);
            throw err;
        });
    },
    
    // Update connection status display
    updateStatus: function(message, type) {
        var $status = $('#qz-status');
        var className = '';
        
        switch(type) {
            case 'success':
                className = 'text-success';
                break;
            case 'error':
                className = 'text-danger';
                break;
            case 'warning':
                className = 'text-warning';
                break;
            case 'info':
            default:
                className = '';
                break;
        }
        
        $status.text('QZ: ' + message)
               .removeClass('text-success text-danger text-warning')
               .addClass(className);
    },
    
    // Populate printer select dropdown
    populatePrinterSelect: function(printers) {
        console.log('Populating printer select with printers:', printers);
        var $select = $('#printer-select');
        $select.empty().append('<option value="">- Select Printer -</option>');
        
        printers.forEach(function(printer) {
            $select.append('<option value="' + printer + '">' + printer + '</option>');
        });
        
        console.log('Enabling printer dropdown');
        $select.prop('disabled', false);
        console.log('Printer dropdown enabled:', !$select.prop('disabled'));
    },
    
    // Clear printer select dropdown
    clearPrinters: function() {
        $('#printer-select')
            .empty()
            .append('<option>- Choose Printer -</option>')
            .prop('disabled', true);
    },
    
    // Print data to selected printer
    print: function(data, printer) {
        var self = this;
        
        if (!this.isConnected) {
            return Promise.reject('QZ-Tray is not connected');
        }
        
        printer = printer || this.selectedPrinter || $('#printer-select').val();
        
        if (!printer) {
            return Promise.reject('No printer selected');
        }
        
        var config = this.qz.configs.create(printer);
        
        return this.qz.print(config, data).then(function() {
            return true;
        }).catch(function(err) {
            throw new Error('Print failed: ' + err.toString());
        });
    },
    
    // Generate simple text label
    generateTextLabel: function(content) {
        return {
            type: 'raw',
            format: 'plain',
            data: content
        };
    },
    
    // Check if QZ-Tray is active
    isActive: function() {
        return this.qz && this.qz.websocket.isActive();
    }
};

// Global functions for backward compatibility
function connectQZ() {
    QZPrint.connect().catch(function(err) {
        console.error('QZ connection error:', err);
    });
}

function disconnectQZ() {
    QZPrint.disconnect().catch(function(err) {
        console.error('QZ disconnection error:', err);
    });
}

// Manual connect only - no auto-connect
// Use connectQZ() function or QZPrint.connect() to connect manually

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = QZPrint;
}