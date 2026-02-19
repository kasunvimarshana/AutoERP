import { defineStore } from 'pinia';
import { ref } from 'vue';
import { documentService } from '../services/documentService';

/**
 * Document Store
 * 
 * Manages Document module state (documents, folders, sharing)
 */
export const useDocumentStore = defineStore('document', () => {
    // State
    const documents = ref([]);
    const folders = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Actions - Documents
    async function fetchDocuments(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.getAll(params);
            documents.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function uploadDocument(file, metadata = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.upload(file, metadata);
            documents.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateDocument(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.update(id, data);
            const index = documents.value.findIndex(d => d.id === id);
            if (index !== -1) {
                documents.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteDocument(id) {
        loading.value = true;
        error.value = null;
        try {
            await documentService.delete(id);
            documents.value = documents.value.filter(d => d.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function moveDocument(id, folderId) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.move(id, folderId);
            const index = documents.value.findIndex(d => d.id === id);
            if (index !== -1) {
                documents.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function downloadDocument(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.download(id);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function previewDocument(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.preview(id);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function shareDocument(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.share(id, data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchSharedUsers(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.getSharedUsers(id);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function revokeDocumentShare(id, userId) {
        loading.value = true;
        error.value = null;
        try {
            await documentService.revokeShare(id, userId);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    // Actions - Folders
    async function fetchFolders(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.folders.getAll(params);
            folders.value = response.data;
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createFolder(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.folders.create(data);
            folders.value.push(response.data);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateFolder(id, data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.folders.update(id, data);
            const index = folders.value.findIndex(f => f.id === id);
            if (index !== -1) {
                folders.value[index] = response.data;
            }
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteFolder(id) {
        loading.value = true;
        error.value = null;
        try {
            await documentService.folders.delete(id);
            folders.value = folders.value.filter(f => f.id !== id);
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchFolderContents(id, params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await documentService.folders.getContents(id, params);
            return response;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        // State
        documents,
        folders,
        loading,
        error,

        // Actions - Documents
        fetchDocuments,
        uploadDocument,
        updateDocument,
        deleteDocument,
        moveDocument,
        downloadDocument,
        previewDocument,
        shareDocument,
        fetchSharedUsers,
        revokeDocumentShare,

        // Actions - Folders
        fetchFolders,
        createFolder,
        updateFolder,
        deleteFolder,
        fetchFolderContents,
    };
});
