/**
 * Modal functionality module
 */

import {
  ajaxRequest,
  showElement,
  hideElement,
  clearElement,
} from '../utils/helpers';
import type { PropertyModalData } from '../types/global';

/**
 * Initialize modal functionality
 */
export function initializeModal(): void {
  const viewDataButtons = document.querySelectorAll(
    '.kato-sync-view-data'
  ) as NodeListOf<HTMLButtonElement>;
  const modal = document.getElementById(
    'kato-sync-property-modal'
  ) as HTMLElement;
  const modalContent = document.getElementById(
    'kato-sync-modal-content'
  ) as HTMLElement;
  const modalTitle = document.getElementById(
    'kato-sync-modal-title'
  ) as HTMLElement;
  const modalLoading = document.getElementById(
    'kato-sync-modal-loading'
  ) as HTMLElement;
  const modalClose = document.querySelector(
    '.kato-sync-modal-close'
  ) as HTMLElement;

  if (viewDataButtons.length > 0 && modal) {
    // View Data button clicks
    viewDataButtons.forEach((button) => {
      button.addEventListener('click', () =>
        handleViewData(button, modal, modalContent, modalTitle, modalLoading)
      );
    });

    // Close modal events
    if (modalClose) {
      modalClose.addEventListener('click', () =>
        closeModal(modal, modalContent, modalTitle)
      );
    }

    // Close modal when clicking outside
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal(modal, modalContent, modalTitle);
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && modal.style.display === 'block') {
        closeModal(modal, modalContent, modalTitle);
      }
    });
  }
}

/**
 * Handle view data button click
 */
async function handleViewData(
  button: HTMLButtonElement,
  modal: HTMLElement,
  modalContent: HTMLElement,
  modalTitle: HTMLElement,
  modalLoading: HTMLElement
): Promise<void> {
  const propertyId = button.getAttribute('data-property-id');

  if (!propertyId) {
    alert('Invalid property ID');
    return;
  }

  // Show modal and loading
  showElement(modal);
  showElement(modalLoading);
  clearElement(modalContent);

  try {
    const response = await ajaxRequest<PropertyModalData>(
      'kato_sync_get_property_data',
      {
        property_id: propertyId,
      }
    );

    hideElement(modalLoading);

    if (response.success) {
      modalTitle.textContent = response.data.title;
      modalContent.innerHTML = response.data.html;
    } else {
      modalContent.innerHTML = `
        <div class="kato-sync-message kato-sync-message-error">
          <strong>Error:</strong> ${
            response.data || 'Failed to load property data'
          }
        </div>
      `;
    }
  } catch (error) {
    hideElement(modalLoading);
    console.error('Modal data error:', error);
    modalContent.innerHTML = `
      <div class="kato-sync-message kato-sync-message-error">
        <strong>Error:</strong> Network error occurred while loading property data.
      </div>
    `;
  }
}

/**
 * Close modal and reset content
 */
function closeModal(
  modal: HTMLElement,
  modalContent: HTMLElement,
  modalTitle: HTMLElement
): void {
  hideElement(modal);
  clearElement(modalContent);
  modalTitle.textContent = 'Property Data';
}
