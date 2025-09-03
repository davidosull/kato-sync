/**
 * Import functionality module
 */

import {
  setButtonLoading,
  isButtonLoading,
  ajaxRequest,
  formatDuration,
  reloadAfterDelay,
} from '../utils/helpers';
import type { SyncResponse } from '../types/global';

/**
 * Initialize manual import functionality
 */
export function initializeImport(): void {
  const manualImportBtn = document.getElementById(
    'kato-sync-manual-import'
  ) as HTMLButtonElement;

  if (manualImportBtn) {
    manualImportBtn.addEventListener('click', handleManualImport);
  }
}

/**
 * Handle manual import button click
 */
async function handleManualImport(event: Event): Promise<void> {
  const button = event.target as HTMLButtonElement;
  const progress = document.getElementById(
    'kato-sync-import-progress'
  ) as HTMLElement;
  const status = document.getElementById(
    'kato-sync-import-status'
  ) as HTMLElement;

  if (isButtonLoading(button)) {
    return;
  }

  setButtonLoading(button, true);
  progress.style.display = 'block';
  status.textContent = 'Starting import...';

  try {
    const response = await ajaxRequest<SyncResponse>('kato_sync_manual_sync');

    if (response.success) {
      status.innerHTML = `
        <div class="kato-sync-message kato-sync-message-success">
          <div style="font-weight: 600; margin-bottom: 8px;">✓ Import completed successfully!</div>
          <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 14px;">
            <span><strong>Added:</strong> ${response.data.added}</span>
            <span><strong>Updated:</strong> ${response.data.updated}</span>
            <span><strong>Skipped:</strong> ${response.data.skipped}</span>
            <span><strong>Duration:</strong> ${formatDuration(
              response.data.duration
            )}</span>
          </div>
        </div>
      `;

      // Refresh the page after a short delay
      reloadAfterDelay();
    } else {
      status.innerHTML = `
        <div class="kato-sync-message kato-sync-message-error">
          <div style="font-weight: 600;">✗ Import failed</div>
          <div style="margin-top: 4px;">${
            response.data.message || 'Unknown error'
          }</div>
        </div>
      `;
    }
  } catch (error) {
    console.error('Import error:', error);
    status.innerHTML = `
      <div class="kato-sync-message kato-sync-message-error">
        Import failed: Network error occurred.
      </div>
    `;
  } finally {
    setButtonLoading(button, false);
  }
}
