import axios from 'axios';
import { useUnauthorizedModal } from '@/Composables/useUnauthorizedModal';

// Create a global error handler for unauthorized access
export const setupGlobalErrorHandler = () => {
  const unauthorizedModal = useUnauthorizedModal();

  // Handle axios response errors
  axios.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 403) {
        // Extract error details from the response
        const errorData = error.response.data;
        const title = errorData?.title || 'Access Denied';
        const message = errorData?.message || 'You do not have permission to access this resource.';
        const requiredRole = errorData?.required_role || '';
        const resource = errorData?.resource || '';

        // Show the unauthorized modal
        unauthorizedModal.showUnauthorizedModal({
          title,
          message,
          requiredRole,
          resource
        });

        // Prevent the error from propagating further
        return Promise.reject(new Error('Unauthorized access - modal displayed'));
      }

      // For other errors, let them propagate normally
      return Promise.reject(error);
    }
  );

  // Also handle Inertia responses
  if (typeof window !== 'undefined' && (window as any).Inertia) {
    // Listen for Inertia page errors
    document.addEventListener('inertia:error', (event: any) => {
      if (event.detail.response?.status === 403) {
        const errorData = event.detail.response.data;
        const title = errorData?.title || 'Access Denied';
        const message = errorData?.message || 'You do not have permission to access this resource.';
        const requiredRole = errorData?.required_role || '';
        const resource = errorData?.resource || '';

        unauthorizedModal.showUnauthorizedModal({
          title,
          message,
          requiredRole,
          resource
        });
      }
    });
  }
};
