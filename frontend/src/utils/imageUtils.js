// Image utility functions for Ardent POS

// Import placeholder images
import productPlaceholder from '../assets/images/placeholders/product-placeholder.jpg';
import categoryPlaceholder from '../assets/images/placeholders/category-placeholder.jpg';
import locationPlaceholder from '../assets/images/placeholders/location-placeholder.jpg';
import userPlaceholder from '../assets/images/placeholders/user-placeholder.jpg';
import noImagePlaceholder from '../assets/images/placeholders/no-image.svg';

// Import branding images
import logoPrimary from '../assets/images/branding/logo-primary.png';
import logoWhite from '../assets/images/branding/logo-white.png';
import logoIcon from '../assets/images/branding/logo-icon.png';

/**
 * Get the appropriate placeholder image based on content type
 * @param {string} type - The type of content ('product', 'category', 'location', 'user')
 * @returns {string} - The placeholder image path
 */
export const getPlaceholderImage = (type) => {
  switch (type) {
    case 'product':
      return productPlaceholder;
    case 'category':
      return categoryPlaceholder;
    case 'location':
      return locationPlaceholder;
    case 'user':
      return userPlaceholder;
    default:
      return noImagePlaceholder;
  }
};

/**
 * Get a safe image URL with fallback
 * @param {string} imageUrl - The image URL to check
 * @param {string} type - The type of content for fallback
 * @returns {string} - The safe image URL
 */
export const getSafeImageUrl = (imageUrl, type = 'default') => {
  if (!imageUrl || imageUrl === 'null' || imageUrl === 'undefined') {
    return getPlaceholderImage(type);
  }
  return imageUrl;
};

/**
 * Get logo image based on theme
 * @param {string} theme - The theme ('light', 'dark', 'primary', 'icon')
 * @returns {string} - The logo image path
 */
export const getLogoImage = (theme = 'primary') => {
  switch (theme) {
    case 'white':
      return logoWhite;
    case 'icon':
      return logoIcon;
    case 'primary':
    default:
      return logoPrimary;
  }
};

/**
 * Check if an image URL is valid
 * @param {string} url - The image URL to validate
 * @returns {boolean} - Whether the URL is valid
 */
export const isValidImageUrl = (url) => {
  if (!url) return false;
  
  // Check if it's a data URL
  if (url.startsWith('data:')) return true;
  
  // Check if it's a relative or absolute URL
  if (url.startsWith('/') || url.startsWith('http')) return true;
  
  return false;
};

/**
 * Get image dimensions from URL
 * @param {string} url - The image URL
 * @returns {Promise<{width: number, height: number}>} - Image dimensions
 */
export const getImageDimensions = (url) => {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => {
      resolve({ width: img.width, height: img.height });
    };
    img.onerror = () => {
      reject(new Error('Failed to load image'));
    };
    img.src = url;
  });
};

/**
 * Format file size for display
 * @param {number} bytes - File size in bytes
 * @returns {string} - Formatted file size
 */
export const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

/**
 * Validate image file
 * @param {File} file - The file to validate
 * @param {number} maxSize - Maximum file size in bytes (default: 5MB)
 * @returns {Object} - Validation result
 */
export const validateImageFile = (file, maxSize = 5 * 1024 * 1024) => {
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
  
  if (!file) {
    return { valid: false, error: 'No file selected' };
  }
  
  if (!allowedTypes.includes(file.type)) {
    return { valid: false, error: 'Invalid file type. Please select a JPEG, PNG, GIF, or WebP image.' };
  }
  
  if (file.size > maxSize) {
    return { valid: false, error: `File size too large. Maximum size is ${formatFileSize(maxSize)}.` };
  }
  
  return { valid: true, error: null };
};

// Export placeholder images for direct use
export {
  productPlaceholder,
  categoryPlaceholder,
  locationPlaceholder,
  userPlaceholder,
  noImagePlaceholder,
  logoPrimary,
  logoWhite,
  logoIcon
};
