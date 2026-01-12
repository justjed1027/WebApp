# Image Credits and Attribution

This document provides a comprehensive list of all images used in the SkillSwap web application and their sources.

## Table of Contents
- [Local Images](#local-images)
- [External Images](#external-images)
- [SVG Icons](#svg-icons)
- [Guidelines for Adding New Images](#guidelines-for-adding-new-images)

---

## Local Images

### Logo Files
Location: `/BPA/images/`

1. **skillswaplogowhite.png**
   - Description: SkillSwap logo in white
   - Usage: Primary logo for dark backgrounds
   - Used in: Navigation bars, headers
   - License: [Add your license information]
   - Creator: [Add creator/designer name]

2. **skillswaplogotrans.png**
   - Description: SkillSwap logo with transparent background
   - Usage: Primary logo for various backgrounds
   - Used in: Navigation bars, notifications
   - License: [Add your license information]
   - Creator: [Add creator/designer name]

3. **header1.svg**
   - Description: Header graphic SVG
   - Usage: Decorative header element
   - Used in: Various header sections
   - License: [Add your license information]
   - Creator: [Add creator/designer name]

### Landing Page Images
Location: `/BPA/landing/img/`

1. **landing_header_img1.png**
   - Description: Landing page header image 1
   - Usage: Hero section and feature showcases
   - License: [Specify license - e.g., CC0, purchased stock, custom created]
   - Source: [Add source if stock image, or "Custom created"]
   - Creator/Copyright: [Add information]

2. **landing_header_img2.png**
   - Description: Landing page header image 2
   - Usage: Hero section and feature showcases
   - License: [Specify license]
   - Source: [Add source]
   - Creator/Copyright: [Add information]

3. **landing_header_img3.png**
   - Description: Landing page header image 3
   - Usage: Hero section and feature showcases
   - License: [Specify license]
   - Source: [Add source]
   - Creator/Copyright: [Add information]

4. **landing_header_img4.png**
   - Description: Landing page header image 4
   - Usage: Hero section and feature showcases
   - License: [Specify license]
   - Source: [Add source]
   - Creator/Copyright: [Add information]

5. **landing_header_img5.png**
   - Description: Landing page header image 5
   - Usage: Hero section and feature showcases
   - License: [Specify license]
   - Source: [Add source]
   - Creator/Copyright: [Add information]

6. **landing_section2_img1.png**
   - Description: Landing page section 2 image 1
   - Usage: Feature section illustrations
   - License: [Specify license]
   - Source: [Add source]
   - Creator/Copyright: [Add information]

7. **landing_section2_img2.png**
   - Description: Landing page section 2 image 2
   - Usage: Feature section illustrations
   - License: [Specify license]
   - Source: [Add source]
   - Creator/Copyright: [Add information]

8. **landing_section2_img3.png**
   - Description: Landing page section 2 image 3
   - Usage: Feature section illustrations
   - License: [Specify license]
   - Source: [Add source]
   - Creator/Copyright: [Add information]

### Authentication Pages
Location: `/BPA/login/images/`

1. **login.png**
   - Description: Background image for login and signup pages
   - Usage: Right panel background on login/signup pages
   - Used in: 
     - `/BPA/login/` (login.css line 258)
     - `/BPA/signup/` (signup.css line 31)
     - `/BPA/forgot/` (forgot.css line 23)
   - License: [Specify license]
   - Source: [Add source]
   - Creator/Copyright: [Add information]

---

## External Images

### Landing Page Background
Location: Referenced in `/BPA/landing/landing.css`

1. **Emaze User Content Image**
   - URL: `https://userscontent2.emaze.com/images/f9538183-0ff9-478f-b964-c8ab90421e3b/3d28e192fda5c17250f96a2779c84475.jpg`
   - Usage: Background image for card flip animations (lines 396, 404)
   - **⚠️ WARNING**: This is an external hosted image that may not be properly licensed
   - **ACTION REQUIRED**: 
     - Verify usage rights for this image
     - Consider replacing with properly licensed alternative
     - Download and host locally if licensed
     - Remove if not properly licensed

---

## SVG Icons

### Inline SVG Data URIs

The following SVG icons are embedded directly in CSS as data URIs:

1. **User/Person Icon**
   - Location: `/BPA/connections/connections.css` (line 165)
   - Description: Person silhouette icon
   - Usage: User avatars/profile placeholders
   - License: Open standard SVG icon (no attribution required)

2. **Dropdown Arrow Icon**
   - Locations: 
     - `/BPA/setup/style.css` (lines 540, 575)
   - Description: Chevron down arrow
   - Usage: Select dropdown indicators
   - License: Open standard SVG icon (no attribution required)

---

## Placeholder Images (Not Found)

The following images are referenced in code but files were not found:

### Review Section
- **course-img.jpg** (referenced in `/BPA/review/index.html` line 13)
  - Status: ⚠️ Missing file
  - Action: Replace with actual image or remove reference

### Events Section
The following images are referenced in `/BPA/events/index.html` but not found:
- **featured-event.jpg** (line 25) - ⚠️ Missing
- **career-fair.jpg** (line 52) - ⚠️ Missing
- **ml-workshop.jpg** (line 67) - ⚠️ Missing
- **design-hackathon.jpg** (line 83) - ⚠️ Missing

**Action Required**: Add these event images or update to use dynamic/database-driven images

---

## User-Uploaded Images

### Post Attachments
Location: `/BPA/post/uploads/` (dynamically generated)
- Description: Images uploaded by users as post attachments
- Usage: Display in post content
- License: User-generated content
- Note: Ensure Terms of Service covers user-uploaded content rights

---

## Guidelines for Adding New Images

When adding new images to the project:

1. **Check License**: Ensure you have proper rights to use the image
2. **Document Source**: Add entry to this file with:
   - File name and location
   - Description and usage
   - License type
   - Source/creator information
   - Date added
3. **Optimize**: Compress images appropriately
4. **Naming**: Use descriptive, lowercase names with hyphens
5. **Alt Text**: Always include descriptive alt text in HTML

### Recommended Free Image Sources
- [Unsplash](https://unsplash.com/) - Free high-quality photos (Unsplash License)
- [Pexels](https://www.pexels.com/) - Free stock photos (Pexels License)
- [Pixabay](https://pixabay.com/) - Free images (Pixabay License)
- [unDraw](https://undraw.co/) - Open-source illustrations (MIT License)
- [Heroicons](https://heroicons.com/) - Free SVG icons (MIT License)

---

## Action Items

### High Priority
- [ ] **Verify license for Emaze external image** and replace if necessary
- [ ] Add missing event images or update HTML references
- [ ] Add missing course-img.jpg or update review page
- [ ] Complete license information for all local images
- [ ] Add creator/designer attribution for logo files

### Medium Priority
- [ ] Document source for all landing page images
- [ ] Document source for login.png background image
- [ ] Review user upload content policy

### Low Priority
- [ ] Consider hosting all images locally instead of external URLs
- [ ] Optimize image file sizes for web performance
- [ ] Create consistent naming convention for all images

---

## Footer Attribution (Add to Website Footer)

If using free stock images with attribution requirements, add appropriate credits:

```html
<!-- Image Credits -->
<div class="image-credits">
  <p>Images sourced from:</p>
  <ul>
    <li>[Image description] - Photo by [Photographer] on [Source]</li>
    <!-- Add more as needed -->
  </ul>
</div>
```

---

## Last Updated
January 12, 2026

## Maintained By
[Add maintainer name/team]

---

**Note**: This document should be updated whenever new images are added to the project.
