import axios from 'axios'

// Dynamic BASE_URL - supports both localhost and production (boganto.com)
export const BASE_URL = (() => {
  // Use environment variable if set
  if (typeof window !== 'undefined' && process.env.NEXT_PUBLIC_API_BASE_URL) {
    return process.env.NEXT_PUBLIC_API_BASE_URL;
  }
  
  // In browser environment, detect if localhost or production
  if (typeof window !== 'undefined') {
    const hostname = window.location.hostname;
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
      return 'http://localhost:8000';
    }
    // Production domain
    return 'https://boganto.com';
  }
  
  // Server-side fallback
  return process.env.NEXT_PUBLIC_API_BASE_URL || "https://boganto.com";
})();

// Fallback for API_BASE_URL (for backward compatibility)
const API_BASE_URL = BASE_URL;

// Log the API base URL in development
if (process.env.NODE_ENV !== 'production') {
  // Development mode active
}

// Create axios instance
const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  withCredentials: true, // Enable credentials for session cookies
  headers: {
    'Content-Type': 'application/json',
  }
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    return response.data
  },
  (error) => {
    // Handle auth errors
    if (error.response?.status === 401) {
      // Clear any stored auth state and redirect to login
      import('./auth').then(({ authManager }) => {
        authManager.clearUser()
      })
    }
    
    return Promise.reject(error.response?.data || error)
  }
)

// Blog API functions
export const blogAPI = {
  // Get all blogs with optional filters
  getBlogs: (params = {}) => {
    return api.get('/api/blogs', { params })
  },
  
  // Get single blog by slug
  getBlogBySlug: (slug) => {
    return api.get(`/api/blogs/slug/${slug}`)
  },
  
  // Get single blog by ID
  getBlogById: (id) => {
    const url = `/api/blogs/${id}`;
    return api.get(url).then(response => {
      if (!response || !response.blog) {
        throw new Error('Invalid blog response format');
      }
      return response;
    }).catch(error => {
      throw error;
    });
  },
  
  // Create new blog (admin)
  createBlog: (formData) => {
    return api.post('/api/admin/blogs', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },
  
  // Update blog (admin)
  updateBlog: (formData) => {
    return api.put('/api/admin/blogs', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },
  
  // Delete blog (admin)
  deleteBlog: (id) => {
    return api.delete(`/api/admin/blogs?id=${id}`)
  }
}

// Category API functions
export const categoryAPI = {
  // Get all categories
  getCategories: (params = {}) => {
    return api.get('/api/categories', { params })
  }
}

// Banner API functions
export const bannerAPI = {
  // Get banner images (public)
  getBanners: () => {
    return api.get('/api/banner')
  },
  
  // Admin banner functions
  getAdminBanners: () => {
    return api.get('/api/admin/banners')
  },
  
  createBanner: (formData) => {
    return api.post('/api/admin/banners', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },
  
  updateBanner: (formData) => {
    return api.put('/api/admin/banners', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },
  
  deleteBanner: (id) => {
    return api.delete(`/api/admin/banners?id=${id}`)
  }
}

// Utility functions
export const utils = {
  // Get full image URL using dynamic BASE_URL
  getImageUrl: (imagePath) => {
    if (!imagePath) return '';
    if (imagePath.startsWith('http')) return imagePath;
    
    // Remove leading slash if present
    const cleanPath = imagePath.startsWith('/') ? imagePath.slice(1) : imagePath;
    
    // If already starts with uploads/, just prepend BASE_URL
    if (cleanPath.startsWith('uploads/')) {
      return `${BASE_URL}/${cleanPath}`;
    }
    
    // Otherwise prepend uploads/
    return `${BASE_URL}/uploads/${cleanPath}`;
  },

  // Format date
  formatDate: (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  },
  
  // Generate excerpt
  generateExcerpt: (content, maxLength = 150) => {
    const text = content.replace(/<[^>]*>/g, '') // Remove HTML tags
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
  },
  
  // Generate slug
  generateSlug: (text) => {
    return text
      .toLowerCase()
      .replace(/[^a-z0-9 -]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
  }
}

export default api