# HTML Documentation

## Overview

This folder contains the HTML version of the Tenant Invitation Feature documentation. All markdown files have been converted to HTML with a beautiful, responsive design.

## Files

- **`index.html`** - Main entry point with navigation
- **`styles.css`** - Stylesheet for all pages
- **`app.js`** - JavaScript for routing and print functionality
- **`convert.php`** - Converter script (run to regenerate HTML from markdown)
- **`00-README.html`** through **`12-troubleshooting.html`** - Converted documentation pages

## Usage

### View Documentation

1. **Open `index.html`** in your web browser
2. Use the sidebar navigation to browse different sections
3. Click the **Print** button (bottom right) to print any page

### Regenerate HTML Files

If you update the markdown files, regenerate HTML:

```bash
php convert.php
```

## Features

✅ **Single-Page Application** - Smooth navigation without page reloads  
✅ **Responsive Design** - Works on desktop, tablet, and mobile  
✅ **Print-Friendly** - Optimized CSS for printing  
✅ **Search-Friendly** - All content is accessible and indexable  
✅ **Navigation** - Easy sidebar navigation between sections  
✅ **Print Button** - Fixed print button on all pages  

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Opera (latest)

## Notes

- All HTML files are self-contained (except CSS/JS)
- Navigation uses hash-based routing (`#route`)
- Print functionality uses browser's native print dialog
- Sidebar is hidden when printing

