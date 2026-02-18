import { ref } from 'vue';
import api from '@/api/client';

/**
 * Export/Import Composable
 * 
 * Provides functionality for exporting and importing data in various formats.
 * Supports:
 * - CSV, Excel, JSON exports
 * - Bulk imports with validation
 * - Progress tracking
 * - Error handling
 */
export function useExportImport() {
  const exporting = ref(false);
  const importing = ref(false);
  const progress = ref(0);
  const error = ref<string | null>(null);

  /**
   * Export data to specified format
   */
  const exportData = async (
    endpoint: string,
    format: 'csv' | 'excel' | 'json' | 'pdf' = 'csv',
    filters?: any,
    filename?: string
  ): Promise<void> => {
    exporting.value = true;
    error.value = null;
    progress.value = 0;

    try {
      const response = await api.post(
        endpoint,
        {
          format,
          filters
        },
        {
          responseType: 'blob',
          onDownloadProgress: (progressEvent) => {
            if (progressEvent.total) {
              progress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            }
          }
        }
      );

      // Create download link
      const blob = new Blob([response.data], { 
        type: getContentType(format) 
      });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename || generateFilename(format);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      progress.value = 100;
    } catch (err: any) {
      error.value = err.message || 'Export failed';
      throw err;
    } finally {
      exporting.value = false;
    }
  };

  /**
   * Import data from file
   */
  const importData = async (
    endpoint: string,
    file: File,
    options?: {
      mapping?: Record<string, string>;
      validateOnly?: boolean;
      skipErrors?: boolean;
    }
  ): Promise<any> => {
    importing.value = true;
    error.value = null;
    progress.value = 0;

    try {
      const formData = new FormData();
      formData.append('file', file);
      
      if (options?.mapping) {
        formData.append('mapping', JSON.stringify(options.mapping));
      }
      
      if (options?.validateOnly) {
        formData.append('validate_only', 'true');
      }
      
      if (options?.skipErrors) {
        formData.append('skip_errors', 'true');
      }

      const response = await api.post(endpoint, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        },
        onUploadProgress: (progressEvent) => {
          if (progressEvent.total) {
            progress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          }
        }
      });

      return response.data;
    } catch (err: any) {
      error.value = err.message || 'Import failed';
      throw err;
    } finally {
      importing.value = false;
    }
  };

  /**
   * Parse CSV file to JSON
   */
  const parseCsvFile = async (file: File): Promise<any[]> => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      
      reader.onload = (e) => {
        try {
          const text = e.target?.result as string;
          const rows = text.split('\n').map(row => row.split(','));
          const headers = rows[0];
          const data = rows.slice(1).map(row => {
            const obj: any = {};
            headers.forEach((header, index) => {
              obj[header.trim()] = row[index]?.trim() || '';
            });
            return obj;
          });
          resolve(data);
        } catch (error) {
          reject(error);
        }
      };
      
      reader.onerror = () => reject(reader.error);
      reader.readAsText(file);
    });
  };

  /**
   * Validate import data
   */
  const validateImportData = async (
    endpoint: string,
    data: any[]
  ): Promise<{ valid: boolean; errors: any[] }> => {
    try {
      const response = await api.post(`${endpoint}/validate`, { data });
      return response.data.data;
    } catch (err: any) {
      error.value = err.message || 'Validation failed';
      throw err;
    }
  };

  /**
   * Download template file
   */
  const downloadTemplate = async (
    endpoint: string,
    format: 'csv' | 'excel' = 'csv'
  ): Promise<void> => {
    try {
      const response = await api.get(`${endpoint}/template`, {
        params: { format },
        responseType: 'blob'
      });

      const blob = new Blob([response.data], { 
        type: getContentType(format) 
      });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `import-template.${format}`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (err: any) {
      error.value = err.message || 'Failed to download template';
      throw err;
    }
  };

  /**
   * Get content type for format
   */
  const getContentType = (format: string): string => {
    const types: Record<string, string> = {
      csv: 'text/csv',
      excel: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      json: 'application/json',
      pdf: 'application/pdf'
    };
    return types[format] || 'application/octet-stream';
  };

  /**
   * Generate filename with timestamp
   */
  const generateFilename = (format: string): string => {
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
    return `export-${timestamp}.${format}`;
  };

  /**
   * Reset state
   */
  const reset = () => {
    exporting.value = false;
    importing.value = false;
    progress.value = 0;
    error.value = null;
  };

  return {
    exporting,
    importing,
    progress,
    error,
    exportData,
    importData,
    parseCsvFile,
    validateImportData,
    downloadTemplate,
    reset
  };
}
