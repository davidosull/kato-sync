/**
 * Tools and utility functionality module
 */

import {
  setButtonLoading,
  isButtonLoading,
  ajaxRequest,
  reloadAfterDelay,
} from '../utils/helpers';

/**
 * Initialize tools functionality
 */
export function initializeTools(): void {
  // Cleanup and maintenance tools
  const cleanupLogsBtn = document.getElementById(
    'kato-sync-cleanup-logs'
  ) as HTMLButtonElement;
  const resetSyncBtn = document.getElementById(
    'kato-sync-reset-sync-status'
  ) as HTMLButtonElement;
  const removeAllBtn = document.getElementById(
    'kato-sync-remove-all-properties'
  ) as HTMLButtonElement;
  const removeAllImagesBtn = document.getElementById(
    'kato-sync-remove-all-images'
  ) as HTMLButtonElement;
  const backfillBtn = document.getElementById(
    'kato-sync-backfill-attachment-ids'
  ) as HTMLButtonElement;

  // Settings import/export
  const exportSettingsBtn = document.getElementById(
    'kato-sync-export-settings'
  ) as HTMLButtonElement;
  const importSettingsBtn = document.getElementById(
    'kato-sync-import-settings'
  ) as HTMLButtonElement;

  // Add event listeners
  if (cleanupLogsBtn)
    cleanupLogsBtn.addEventListener('click', handleCleanupLogs);
  if (resetSyncBtn) resetSyncBtn.addEventListener('click', handleResetSync);
  if (removeAllBtn) removeAllBtn.addEventListener('click', handleRemoveAll);
  if (removeAllImagesBtn)
    removeAllImagesBtn.addEventListener('click', handleRemoveAllImages);
  if (exportSettingsBtn)
    exportSettingsBtn.addEventListener('click', handleExportSettings);
  if (importSettingsBtn)
    importSettingsBtn.addEventListener('click', handleImportSettings);
  if (backfillBtn)
    backfillBtn.addEventListener('click', handleBackfillAttachmentIds);
}

/**
 * Generic handler for simple confirmation-based tools
 */
async function handleSimpleTool(
  button: HTMLButtonElement,
  action: string,
  confirmMessage?: string
): Promise<void> {
  if (isButtonLoading(button)) {
    return;
  }

  if (confirmMessage && !confirm(confirmMessage)) {
    return;
  }

  setButtonLoading(button, true);

  try {
    const response = await ajaxRequest(action);

    if (response.success) {
      alert(response.data?.message || response.data);
      reloadAfterDelay(1000);
    } else {
      alert('Error: ' + response.data);
    }
  } catch (error) {
    console.error(`${action} error:`, error);
    alert('Network error occurred.');
  } finally {
    setButtonLoading(button, false);
  }
}

/**
 * Cleanup logs handler
 */
function handleCleanupLogs(event: Event): void {
  const button = event.target as HTMLButtonElement;
  handleSimpleTool(
    button,
    'kato_sync_cleanup_logs',
    window.katoSyncAjax.strings.confirmCleanupLogs
  );
}

/**
 * Reset sync status handler
 */
function handleResetSync(event: Event): void {
  const button = event.target as HTMLButtonElement;
  handleSimpleTool(
    button,
    'kato_sync_reset_sync_status',
    window.katoSyncAjax.strings.confirmResetSync
  );
}

/**
 * Remove all properties handler
 */
function handleRemoveAll(event: Event): void {
  const button = event.target as HTMLButtonElement;
  handleSimpleTool(
    button,
    'kato_sync_remove_all_properties',
    window.katoSyncAjax.strings.confirmRemoveAll
  );
}

/**
 * Remove all images handler
 */
async function handleRemoveAllImages(event: Event): Promise<void> {
  const button = event.target as HTMLButtonElement;

  if (isButtonLoading(button)) {
    return;
  }

  if (
    !confirm(
      'Are you sure you want to remove all imported images? This action cannot be undone.'
    )
  ) {
    return;
  }

  setButtonLoading(button, true);

  try {
    const response = await ajaxRequest('kato_sync_remove_all_images');

    if (response.success) {
      // Show only the clean message, not debug data
      alert(response.data.message);
      reloadAfterDelay(1000);
    } else {
      alert('Error: ' + response.data);
    }
  } catch (error) {
    console.error('Remove all images error:', error);
    alert('Network error occurred.');
  } finally {
    setButtonLoading(button, false);
  }
}

/**
 * Backfill attachment IDs handler
 */
async function handleBackfillAttachmentIds(event: Event): Promise<void> {
  const button = event.target as HTMLButtonElement;

  if (isButtonLoading(button)) {
    return;
  }

  if (
    !confirm(
      'This will update database records for images that have been imported but are still showing as pending. Continue?'
    )
  ) {
    return;
  }

  setButtonLoading(button, true);

  try {
    const response = await ajaxRequest('kato_sync_backfill_attachment_ids');

    if (response.success) {
      alert(response.data.message);
      // Reload after a short delay to see updated status
      reloadAfterDelay(1000);
    } else {
      alert('Error: ' + response.data);
    }
  } catch (error) {
    console.error('Backfill attachment IDs error:', error);
    alert('Network error occurred.');
  } finally {
    setButtonLoading(button, false);
  }
}

/**
 * Export settings handler
 */
function handleExportSettings(event: Event): void {
  const button = event.target as HTMLButtonElement;

  if (isButtonLoading(button)) {
    return;
  }

  setButtonLoading(button, true);

  // Create a temporary form to trigger download
  const form = document.createElement('form');
  form.method = 'post';
  form.action = window.katoSyncAjax.ajaxurl;

  const actionInput = document.createElement('input');
  actionInput.type = 'hidden';
  actionInput.name = 'action';
  actionInput.value = 'kato_sync_export_settings';

  const nonceInput = document.createElement('input');
  nonceInput.type = 'hidden';
  nonceInput.name = 'nonce';
  nonceInput.value = window.katoSyncAjax.nonce;

  form.appendChild(actionInput);
  form.appendChild(nonceInput);
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);

  setTimeout(() => {
    setButtonLoading(button, false);
  }, 1000);
}

/**
 * Import settings handler
 */
async function handleImportSettings(event: Event): Promise<void> {
  const button = event.target as HTMLButtonElement;
  const fileInput = document.getElementById(
    'kato-sync-import-file'
  ) as HTMLInputElement;

  if (isButtonLoading(button)) {
    return;
  }

  if (!fileInput.files?.length) {
    alert('Please select a file to import.');
    return;
  }

  setButtonLoading(button, true);

  try {
    const response = await ajaxRequest('kato_sync_import_settings', {
      import_file: fileInput.files[0],
    });

    if (response.success) {
      alert(response.data);
      reloadAfterDelay(1000);
    } else {
      alert('Error: ' + response.data);
    }
  } catch (error) {
    console.error('Import settings error:', error);
    alert('Network error occurred.');
  } finally {
    setButtonLoading(button, false);
    fileInput.value = '';
  }
}
