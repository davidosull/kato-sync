// Global types for Kato Sync admin

interface KatoSyncAjax {
  ajaxurl: string;
  nonce: string;
  strings: {
    confirmCleanupLogs: string;
    confirmResetSync: string;
    confirmRemoveAll: string;
  };
}

interface SyncResponse {
  success: boolean;
  data: {
    added: number;
    updated: number;
    skipped: number;
    duration: number;
    message?: string;
  };
}

interface FeedTestResponse {
  success: boolean;
  data: {
    message: string;
    response_time: number;
    content_length?: number;
  };
}

interface DiagnosticsResponse {
  success: boolean;
  data: {
    settings: {
      auto_sync_enabled: boolean;
      auto_sync_frequency: string;
      force_update_all: boolean;
    };
    cron_disabled: boolean;
    next_scheduled: number | null;
    auto_sync_cron_count: number;
    sync_lock: boolean;
    available_schedules: string[];
    missing_intervals?: string[];
    issues?: string[];
  };
}

interface PropertyModalData {
  success: boolean;
  data: {
    title: string;
    html: string;
  };
}

// Extend the global Window interface
declare global {
  interface Window {
    katoSyncAjax: KatoSyncAjax;
  }
}

export type {
  KatoSyncAjax,
  SyncResponse,
  FeedTestResponse,
  DiagnosticsResponse,
  PropertyModalData,
};
