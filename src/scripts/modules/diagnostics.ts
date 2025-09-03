/**
 * Diagnostics and auto-sync functionality module
 */

import {
  setButtonLoading,
  isButtonLoading,
  ajaxRequest,
  showElement,
} from '../utils/helpers';
import type { DiagnosticsResponse } from '../types/global';

/**
 * Initialize diagnostics functionality
 */
export function initializeDiagnostics(): void {
  const runDiagnosticsBtn = document.getElementById(
    'kato-sync-run-auto-sync-diagnostics'
  ) as HTMLButtonElement;

  const clearSyncLocksBtn = document.getElementById(
    'kato-sync-clear-sync-locks'
  ) as HTMLButtonElement;
  const checkAutoSyncBtn = document.getElementById(
    'kato-sync-check-auto-sync'
  ) as HTMLButtonElement;
  const resetAutoSyncBtn = document.getElementById(
    'kato-sync-reset-auto-sync'
  ) as HTMLButtonElement;

  if (runDiagnosticsBtn) {
    runDiagnosticsBtn.addEventListener('click', handleRunDiagnostics);
  }

  if (clearSyncLocksBtn) {
    clearSyncLocksBtn.addEventListener('click', handleClearSyncLocks);
  }

  if (checkAutoSyncBtn) {
    checkAutoSyncBtn.addEventListener('click', handleCheckAutoSync);
  }

  if (resetAutoSyncBtn) {
    resetAutoSyncBtn.addEventListener('click', handleResetAutoSync);
  }
}

/**
 * Handle clear sync locks button click
 */
async function handleClearSyncLocks(event: Event): Promise<void> {
  const button = event.target as HTMLButtonElement;

  if (isButtonLoading(button)) {
    return;
  }

  if (
    !confirm(
      'This will clear any stale sync locks that might be preventing auto-sync from running. Continue?'
    )
  ) {
    return;
  }

  setButtonLoading(button, true);

  try {
    const response = await ajaxRequest<any>('kato_sync_clear_sync_locks');

    if (response.success) {
      alert(response.data);
      // Reload the page after a short delay
      setTimeout(() => {
        window.location.reload();
      }, 2000);
    } else {
      alert('Error: ' + response.data);
    }
  } catch (error) {
    alert('Error: ' + error);
  } finally {
    setButtonLoading(button, false);
  }
}

/**
 * Handle run diagnostics button click
 */
async function handleRunDiagnostics(event: Event): Promise<void> {
  const button = event.target as HTMLButtonElement;
  const resultsDiv = document.getElementById(
    'kato-sync-auto-sync-results'
  ) as HTMLElement;
  const contentDiv = resultsDiv.querySelector(
    '.kato-sync-auto-sync-content'
  ) as HTMLElement;

  if (isButtonLoading(button)) {
    return;
  }

  setButtonLoading(button, true);
  contentDiv.innerHTML =
    '<p>Running comprehensive auto-sync diagnostics...</p>';
  showElement(resultsDiv);

  try {
    const response = await ajaxRequest<DiagnosticsResponse>(
      'kato_sync_run_auto_sync_diagnostics'
    );

    if (response.success) {
      const diagnosis = response.data;
      let html = '<h3>Auto-Sync Diagnostic Results</h3>';
      html += '<table class="form-table">';

      // Basic settings
      html += `<tr><th>Auto-Sync Enabled:</th><td>
        <span class="kato-sync-status kato-sync-status-${
          diagnosis.settings.auto_sync_enabled ? 'success' : 'error'
        }">
        ${diagnosis.settings.auto_sync_enabled ? 'Yes' : 'No'}
        </span></td></tr>`;

      html += `<tr><th>Frequency:</th><td>${
        diagnosis.settings.auto_sync_frequency || 'Not set'
      }</td></tr>`;

      html += `<tr><th>Force Update:</th><td>
        <span class="kato-sync-status kato-sync-status-${
          diagnosis.settings.force_update_all ? 'warning' : 'success'
        }">
        ${diagnosis.settings.force_update_all ? 'Enabled' : 'Disabled'}
        </span></td></tr>`;

      // Cron status
      html += `<tr><th>WP Cron Disabled:</th><td>
        <span class="kato-sync-status kato-sync-status-${
          diagnosis.cron_disabled ? 'error' : 'success'
        }">
        ${diagnosis.cron_disabled ? 'Yes' : 'No'}
        </span></td></tr>`;

      html += `<tr><th>Next Scheduled:</th><td>
        ${
          diagnosis.next_scheduled
            ? new Date(diagnosis.next_scheduled * 1000).toLocaleString()
            : 'Not scheduled'
        }
        </td></tr>`;

      html += `<tr><th>Auto-Sync Cron Jobs:</th><td>${diagnosis.auto_sync_cron_count}</td></tr>`;

      html += `<tr><th>Sync Lock:</th><td>
        <span class="kato-sync-status kato-sync-status-${
          diagnosis.sync_lock ? 'warning' : 'success'
        }">
        ${diagnosis.sync_lock ? 'Exists' : 'None'}
        </span></td></tr>`;

      // Available schedules
      html += `<tr><th>Available Schedules:</th><td>${diagnosis.available_schedules.join(
        ', '
      )}</td></tr>`;

      if (
        diagnosis.missing_intervals &&
        diagnosis.missing_intervals.length > 0
      ) {
        html += `<tr><th>Missing Intervals:</th><td style="color: #dc3232;">${diagnosis.missing_intervals.join(
          ', '
        )}</td></tr>`;
      }

      html += '</table>';

      // Show issues if any
      if (diagnosis.issues && diagnosis.issues.length > 0) {
        html += '<h4>Issues Found:</h4><ul>';
        diagnosis.issues.forEach((issue) => {
          html += `<li style="color: #dc3232;">${issue}</li>`;
        });
        html += '</ul>';
      } else {
        html +=
          '<p style="color: #46b450;"><strong>No issues found!</strong></p>';
      }

      contentDiv.innerHTML = html;
    } else {
      contentDiv.innerHTML = `<p style="color: #dc3232;">Error: ${response.data}</p>`;
    }
  } catch (error) {
    console.error('Diagnostics error:', error);
    contentDiv.innerHTML =
      '<p style="color: #dc3232;">Network error occurred.</p>';
  } finally {
    setButtonLoading(button, false);
  }
}

/**
 * Handle check auto-sync button click
 */
async function handleCheckAutoSync(event: Event): Promise<void> {
  const button = event.target as HTMLButtonElement;
  const resultsDiv = document.getElementById(
    'kato-sync-auto-sync-results'
  ) as HTMLElement;
  const contentDiv = resultsDiv.querySelector(
    '.kato-sync-auto-sync-content'
  ) as HTMLElement;

  if (isButtonLoading(button)) {
    return;
  }

  setButtonLoading(button, true);
  contentDiv.innerHTML = '<p>Checking auto-sync status...</p>';
  showElement(resultsDiv);

  try {
    const response = await ajaxRequest('kato_sync_check_auto_sync');

    if (response.success) {
      const status = response.data;
      let html = '<table class="form-table">';

      // Status indicators
      html += `<tr><th>Auto-Sync Enabled:</th><td>
        <span class="kato-sync-status kato-sync-status-${
          status.auto_sync_enabled ? 'success' : 'error'
        }">
        ${status.auto_sync_enabled ? 'Yes' : 'No'}
        </span></td></tr>`;

      html += `<tr><th>Frequency:</th><td>${status.frequency}</td></tr>`;

      html += `<tr><th>Feed URL Configured:</th><td>
        <span class="kato-sync-status kato-sync-status-${
          status.feed_url_configured ? 'success' : 'error'
        }">
        ${status.feed_url_configured ? 'Yes' : 'No'}
        </span></td></tr>`;

      html += `<tr><th>WP Cron Disabled:</th><td>
        <span class="kato-sync-status kato-sync-status-${
          status.cron_disabled ? 'error' : 'success'
        }">
        ${status.cron_disabled ? 'Yes' : 'No'}
        </span></td></tr>`;

      if (status.next_scheduled) {
        html += `<tr><th>Next Scheduled:</th><td>${new Date(
          status.next_scheduled * 1000
        ).toLocaleString()}</td></tr>`;
      } else {
        html +=
          '<tr><th>Next Scheduled:</th><td><span class="kato-sync-status kato-sync-status-error">Not scheduled</span></td></tr>';
      }

      if (status.last_sync) {
        html += `<tr><th>Last Sync:</th><td>${new Date(
          status.last_sync * 1000
        ).toLocaleString()}</td></tr>`;
      } else {
        html += '<tr><th>Last Sync:</th><td>Never</td></tr>';
      }

      // Show issues if any
      if (status.issues && status.issues.length > 0) {
        html += '<tr><th>Issues:</th><td><ul>';
        status.issues.forEach((issue: string) => {
          html += `<li style="color: #dc3232;">${issue}</li>`;
        });
        html += '</ul></td></tr>';
      }

      html += '</table>';
      contentDiv.innerHTML = html;
    } else {
      contentDiv.innerHTML = `<p style="color: #dc3232;">Error: ${response.data}</p>`;
    }
  } catch (error) {
    console.error('Check auto-sync error:', error);
    contentDiv.innerHTML =
      '<p style="color: #dc3232;">Network error occurred.</p>';
  } finally {
    setButtonLoading(button, false);
  }
}

/**
 * Handle reset auto-sync button click
 */
async function handleResetAutoSync(event: Event): Promise<void> {
  const button = event.target as HTMLButtonElement;
  const resultsDiv = document.getElementById(
    'kato-sync-auto-sync-results'
  ) as HTMLElement;
  const contentDiv = resultsDiv.querySelector(
    '.kato-sync-auto-sync-content'
  ) as HTMLElement;

  if (isButtonLoading(button)) {
    return;
  }

  if (
    !confirm(
      'Are you sure you want to reset the auto-sync settings? This will clear any invalid settings and reschedule the cron job.'
    )
  ) {
    return;
  }

  setButtonLoading(button, true);
  contentDiv.innerHTML = '<p>Resetting auto-sync settings...</p>';
  showElement(resultsDiv);

  try {
    const response = await ajaxRequest('kato_sync_reset_auto_sync_settings');

    if (response.success) {
      const result = response.data;
      let html = '<h3>Auto-Sync Settings Reset</h3>';
      html +=
        '<div style="background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;">';
      html +=
        '<p><strong>Success!</strong> Auto-sync settings have been reset.</p>';
      html += '<ul>';
      html += `<li><strong>Old Frequency:</strong> ${result.old_frequency}</li>`;
      html += `<li><strong>New Frequency:</strong> ${result.new_frequency}</li>`;
      if (result.next_scheduled) {
        html += `<li><strong>Next Scheduled:</strong> ${new Date(
          result.next_scheduled * 1000
        ).toLocaleString()}</li>`;
      }
      html += '</ul>';
      html += '</div>';
      contentDiv.innerHTML = html;
    } else {
      contentDiv.innerHTML = `<p style="color: #dc3232;">Error: ${response.data}</p>`;
    }
  } catch (error) {
    console.error('Reset auto-sync error:', error);
    contentDiv.innerHTML =
      '<p style="color: #dc3232;">Network error occurred.</p>';
  } finally {
    setButtonLoading(button, false);
  }
}
