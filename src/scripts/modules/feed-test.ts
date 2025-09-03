/**
 * Feed testing functionality module
 */

import {
  setButtonLoading,
  isButtonLoading,
  ajaxRequest,
  formatBytes,
  showElement,
} from '../utils/helpers';
import type { FeedTestResponse } from '../types/global';

/**
 * Initialize feed test functionality
 */
export function initializeFeedTest(): void {
  const testFeedBtn = document.getElementById(
    'kato-sync-test-feed'
  ) as HTMLButtonElement;

  if (testFeedBtn) {
    testFeedBtn.addEventListener('click', handleFeedTest);
  }
}

/**
 * Handle feed test button click
 */
async function handleFeedTest(event: Event): Promise<void> {
  const button = event.target as HTMLButtonElement;
  const results = document.getElementById(
    'kato-sync-feed-test-results'
  ) as HTMLElement;
  const content = document.querySelector(
    '.kato-sync-feed-test-content'
  ) as HTMLElement;

  if (isButtonLoading(button)) {
    return;
  }

  setButtonLoading(button, true);
  showElement(results);
  content.innerHTML = '<p>Testing feed connectivity...</p>';

  try {
    const response = await ajaxRequest<FeedTestResponse>('kato_sync_test_feed');

    if (response.success) {
      let html = `
        <div class="kato-sync-message kato-sync-message-success">
          <strong>✓ ${response.data.message}</strong><br>
          Response time: ${response.data.response_time}ms
      `;

      if (response.data.content_length) {
        html += `<br>Content length: ${formatBytes(
          response.data.content_length
        )}`;
      }

      html += '</div>';
      content.innerHTML = html;
    } else {
      content.innerHTML = `
        <div class="kato-sync-message kato-sync-message-error">
          <strong>✗ Feed test failed</strong><br>
          ${response.data.message || 'Unknown error'}<br>
          Response time: ${response.data.response_time || 'N/A'}ms
        </div>
      `;
    }
  } catch (error) {
    console.error('Feed test error:', error);
    content.innerHTML = `
      <div class="kato-sync-message kato-sync-message-error">
        <strong>✗ Feed test failed</strong><br>
        Error: ${error instanceof Error ? error.message : 'Unknown error'}
      </div>
    `;
  } finally {
    setButtonLoading(button, false);
  }
}
