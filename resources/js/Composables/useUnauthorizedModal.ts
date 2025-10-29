import { ref, computed } from 'vue';

interface UnauthorizedError {
  title?: string;
  message?: string;
  requiredRole?: string;
  resource?: string;
}

const isModalOpen = ref(false);
const errorDetails = ref<UnauthorizedError>({});

// Show the unauthorized modal with custom error details
const showUnauthorizedModal = (error: UnauthorizedError = {}) => {
  errorDetails.value = {
    title: error.title || 'Access Denied',
    message: error.message || 'You do not have permission to access this page.',
    requiredRole: error.requiredRole || '',
    resource: error.resource || ''
  };
  isModalOpen.value = true;
};

// Hide the unauthorized modal
const hideUnauthorizedModal = () => {
  isModalOpen.value = false;
  errorDetails.value = {};
};

export const useUnauthorizedModal = () => {
  return {
    isModalOpen: computed(() => isModalOpen.value),
    errorDetails: computed(() => errorDetails.value),
    showUnauthorizedModal,
    hideUnauthorizedModal
  };
};
