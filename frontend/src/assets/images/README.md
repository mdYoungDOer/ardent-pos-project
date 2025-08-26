# 📁 Image Organization Guide

## **Where to Place Your Images**

### **1. 🏷️ Branding Images (Logos, Brand Assets)**
**Location:** `frontend/src/assets/images/branding/`

**Files to add here:**
- `logo-primary.png` - Main logo (recommended: 200x60px)
- `logo-secondary.png` - Secondary/alternative logo
- `logo-white.png` - White version for dark backgrounds
- `logo-dark.png` - Dark version for light backgrounds
- `logo-icon.png` - Square icon version (64x64px)
- `logo-favicon.png` - Small icon for favicon (32x32px)

**Usage in code:**
```jsx
import logoPrimary from '../assets/images/branding/logo-primary.png';
import logoWhite from '../assets/images/branding/logo-white.png';
```

### **2. 🎯 Favicon & Browser Icons**
**Location:** `frontend/public/favicon/`

**Files to add here:**
- `favicon.ico` - Main favicon (16x16, 32x32)
- `favicon-16x16.png` - 16x16 favicon
- `favicon-32x32.png` - 32x32 favicon
- `apple-touch-icon.png` - Apple touch icon (180x180px)
- `android-chrome-192x192.png` - Android chrome icon
- `android-chrome-512x512.png` - Android chrome icon (large)

**Usage:** These are automatically served from `/favicon/` path

### **3. 🎨 UI Elements & Icons**
**Location:** `frontend/src/assets/images/ui/`

**Files to add here:**
- `hero-bg.jpg` - Hero section background
- `dashboard-bg.jpg` - Dashboard background
- `login-bg.jpg` - Login page background
- `sidebar-bg.jpg` - Sidebar background
- `card-bg.jpg` - Card backgrounds
- `pattern.svg` - Decorative patterns
- `illustrations/` - Folder for UI illustrations

**Usage in code:**
```jsx
import heroBg from '../assets/images/ui/hero-bg.jpg';
import dashboardBg from '../assets/images/ui/dashboard-bg.jpg';
```

### **4. 🔧 Custom Icons**
**Location:** `frontend/src/assets/images/icons/`

**Files to add here:**
- `custom-icon-1.svg` - Custom SVG icons
- `custom-icon-2.png` - Custom PNG icons
- `payment-methods/` - Payment method icons
- `social-media/` - Social media icons

**Usage in code:**
```jsx
import customIcon from '../assets/images/icons/custom-icon-1.svg';
```

### **5. 🖼️ Placeholder Images**
**Location:** `frontend/src/assets/images/placeholders/`

**Files to add here:**
- `product-placeholder.jpg` - Default product image
- `category-placeholder.jpg` - Default category image
- `location-placeholder.jpg` - Default location image
- `user-placeholder.jpg` - Default user avatar
- `no-image.svg` - Generic no-image placeholder

**Usage in code:**
```jsx
import productPlaceholder from '../assets/images/placeholders/product-placeholder.jpg';
```

### **6. 📱 App-Specific Images**
**Location:** `frontend/src/assets/images/app/`

**Files to add here:**
- `pos-bg.jpg` - POS terminal background
- `receipt-header.png` - Receipt header logo
- `qr-code-template.png` - QR code template
- `barcode-template.png` - Barcode template

## **📋 Image Guidelines**

### **Recommended Formats:**
- **Logos:** PNG (transparent background) or SVG
- **Photos:** JPG for photos, PNG for graphics with transparency
- **Icons:** SVG (scalable) or PNG
- **Favicons:** ICO, PNG (multiple sizes)

### **Recommended Sizes:**
- **Logo Primary:** 200x60px
- **Logo Icon:** 64x64px
- **Favicon:** 32x32px
- **Apple Touch Icon:** 180x180px
- **Hero Images:** 1920x1080px
- **Product Images:** 800x600px
- **Category Images:** 400x300px

### **File Naming Convention:**
- Use kebab-case: `logo-primary.png`
- Include size in filename: `favicon-32x32.png`
- Use descriptive names: `hero-background.jpg`

## **🔧 How to Use Images in Components**

### **Import and Use:**
```jsx
import React from 'react';
import logoPrimary from '../assets/images/branding/logo-primary.png';
import productPlaceholder from '../assets/images/placeholders/product-placeholder.jpg';

const MyComponent = () => {
  return (
    <div>
      <img src={logoPrimary} alt="Ardent POS Logo" />
      <img src={productPlaceholder} alt="Product" />
    </div>
  );
};
```

### **Dynamic Images:**
```jsx
const getImageUrl = (imagePath) => {
  if (!imagePath) {
    return productPlaceholder; // Fallback image
  }
  return imagePath;
};
```

### **CSS Background Images:**
```css
.hero-section {
  background-image: url('../assets/images/ui/hero-bg.jpg');
  background-size: cover;
  background-position: center;
}
```

## **🚀 Quick Start**

1. **Add your logo files** to `frontend/src/assets/images/branding/`
2. **Add favicon files** to `frontend/public/favicon/`
3. **Add UI images** to `frontend/src/assets/images/ui/`
4. **Add placeholder images** to `frontend/src/assets/images/placeholders/`
5. **Update components** to use the new images

## **📝 Next Steps**

After adding your images:
1. Update the favicon in `frontend/index.html`
2. Update logo references in components
3. Test image loading and fallbacks
4. Optimize images for web (compress if needed)
