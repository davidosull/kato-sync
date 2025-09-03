/**
 * Utility functions for Kato Sync admin
 */

/**
 * Format duration in seconds to human-readable string
 */
export function formatDuration(seconds: number): string {
  if (seconds < 60) {
    return `${seconds} sec`;
  }

  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;

  if (remainingSeconds === 0) {
    return `${minutes} min`;
  }

  return `${minutes} min ${remainingSeconds} sec`;
}

/**
 * Format bytes to human-readable string
 */
export function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 Bytes';

  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Add loading state to a button
 */
export function setButtonLoading(
  button: HTMLButtonElement,
  loading: boolean
): void {
  if (loading) {
    button.classList.add('kato-sync-loading');
    button.disabled = true;
  } else {
    button.classList.remove('kato-sync-loading');
    button.disabled = false;
  }
}

/**
 * Check if button is already loading
 */
export function isButtonLoading(button: HTMLButtonElement): boolean {
  return button.classList.contains('kato-sync-loading');
}

/**
 * Generic AJAX request helper
 */
export async function ajaxRequest<T = any>(
  action: string,
  data: Record<string, any> = {}
): Promise<T> {
  const formData = new FormData();
  formData.append('action', action);
  formData.append('nonce', window.katoSyncAjax.nonce);

  // Add additional data
  Object.entries(data).forEach(([key, value]) => {
    if (value instanceof File) {
      formData.append(key, value);
    } else {
      formData.append(key, String(value));
    }
  });

  const response = await fetch(window.katoSyncAjax.ajaxurl, {
    method: 'POST',
    body: formData,
  });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Show element with display block
 */
export function showElement(element: HTMLElement): void {
  element.style.display = 'block';
}

/**
 * Hide element
 */
export function hideElement(element: HTMLElement): void {
  element.style.display = 'none';
}

/**
 * Clear element content
 */
export function clearElement(element: HTMLElement): void {
  element.innerHTML = '';
}

/**
 * Reload page after delay
 */
export function reloadAfterDelay(delay: number = 3000): void {
  setTimeout(() => {
    location.reload();
  }, delay);
}
