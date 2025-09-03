/**
 * Kato Sync Admin JavaScript - Main entry point
 *
 * Modern TypeScript implementation with modular architecture
 */

// Import all modules
import { initializeImport } from './modules/import';
import { initializeFeedTest } from './modules/feed-test';
import { initializeModal } from './modules/modal';
import { initializeDiagnostics } from './modules/diagnostics';
import { initializeTools } from './modules/tools';

/**
 * Initialize all admin functionality when DOM is ready
 */
function initializeAdmin(): void {
  // Import functionality
  initializeImport();

  // Feed testing
  initializeFeedTest();

  // Property data modal
  initializeModal();

  // Auto-sync diagnostics
  initializeDiagnostics();

  // Tools and utilities
  initializeTools();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeAdmin);
} else {
  // DOM is already loaded
  initializeAdmin();
}
