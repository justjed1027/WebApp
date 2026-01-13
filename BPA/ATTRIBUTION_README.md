# SkillSwap Attribution & Licensing

This directory contains comprehensive attribution and licensing documentation for all external resources used in the SkillSwap web application.

## 📄 Attribution Documents

### 1. **ATTRIBUTION.html** ⭐ (Comprehensive Attribution Document)
A complete, professionally formatted HTML document documenting all external resources used in SkillSwap.

**Contents:**
- External Fonts & Typography (Google Fonts: Inter, League Spartan)
- SVG Icons & Iconography (Bootstrap Icons style)
- Core Technologies & Frameworks (PHP, MySQL, JavaScript, CSS3, HTML5)
- Images & Media Assets (with reference to IMAGE_CREDITS.md)
- Acknowledgments and license summary
- Legal compliance notes

**How to Use:**
- **View in Browser:** Open `ATTRIBUTION.html` in any web browser
- **Print to PDF:** Use your browser's Print function (Ctrl+P / Cmd+P) and select "Save as PDF"
- **Share:** Can be shared with legal teams, compliance officers, or included in distribution packages

**Best Practices:**
- Keep this file up-to-date whenever new external resources are added
- Share with stakeholders during licensing reviews
- Include in project documentation and README files
- Use for compliance audits

---

### 2. **IMAGE_CREDITS.md** (Image Asset Attribution)
Detailed documentation of all images used in SkillSwap with sources and licensing information.

**Contents:**
- Local images (logos, landing page images, authentication images)
- External images (with license verification warnings)
- SVG icons in CSS
- Placeholder/missing images tracking
- User-uploaded content guidelines
- Recommended free image sources
- Action items for image licensing

**Location:** `/BPA/IMAGE_CREDITS.md`

**How to Use:**
- Refer to this file before adding new images
- Check existing image licenses before modifications
- Use as a checklist for image licensing compliance
- Update this file whenever images are added/removed

---

## 🔍 Quick Resource Summary

### External Dependencies

| Resource | Type | License | Usage | Link |
|----------|------|---------|-------|------|
| **Inter** | Font | OFL 1.1 | Sign-up, Login, General UI | [Google Fonts](https://fonts.google.com/specimen/Inter) |
| **League Spartan** | Font | OFL 1.1 | Landing Page Display Text | [Google Fonts](https://fonts.google.com/specimen/League+Spartan) |
| **Bootstrap Icons** | SVG Icons | MIT | UI Icons throughout app | [icons.getbootstrap.com](https://icons.getbootstrap.com/) |
| **PHP** | Language | PHP License v3 | Backend scripting | [php.net](https://www.php.net/) |
| **MySQL** | Database | GPL v2 | Data storage | [mysql.com](https://www.mysql.com/) |

### Internal Resources (No External Attribution Required)
- Vanilla JavaScript (ECMAScript standard)
- CSS3 (W3C standard)
- HTML5 (W3C standard)
- Browser APIs (LocalStorage, Fetch, etc.)
- Custom SVG elements created for SkillSwap

---

## 📋 License Types Used

### Open Font License (OFL) 1.1
**Used by:** Inter, League Spartan fonts
- ✅ Can use commercially
- ✅ Can modify and distribute modified versions
- ✅ Can bundle with applications
- ✅ No attribution required in code
- 📄 [Full OFL License](https://scripts.sil.org/cms/scripts/page.php?site_id=nrsi&id=OFL)

### MIT License
**Used by:** Bootstrap Icons
- ✅ Can use commercially
- ✅ Can modify and use privately
- ✅ Can use in closed-source projects
- ⚠️ Must include copyright notice and license
- 📄 Full text included in resource documentation

### GNU GPL v2
**Used by:** MySQL
- ✅ Can use commercially
- ✅ Can modify code
- ⚠️ Must share modifications under same license
- ⚠️ Source code must be made available
- 📄 [Full GPL License](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

### PHP License v3.01
**Used by:** PHP
- ✅ Can use and modify freely
- ⚠️ Specific terms in license
- 📄 [Full PHP License](https://www.php.net/license/)

---

## 🚀 Adding New External Resources

When adding new fonts, icons, libraries, or images to SkillSwap:

### Checklist
- [ ] Verify the resource has a clear, permissive open-source license
- [ ] Document the license name and version
- [ ] Note the official source URL
- [ ] Add entry to `ATTRIBUTION.html` with proper details
- [ ] Update `IMAGE_CREDITS.md` if images are involved
- [ ] Test resource loading and functionality
- [ ] Add inline comments in code referencing the license
- [ ] Include license information in package.json or requirements file if applicable

### Recommended Free Resources
**Fonts:**
- [Google Fonts](https://fonts.google.com/) - Free, open-source typefaces
- [Font Awesome](https://fontawesome.com/) - Icon fonts (MIT License)

**Icons:**
- [Bootstrap Icons](https://icons.getbootstrap.com/) - SVG icons (MIT License)
- [Heroicons](https://heroicons.com/) - SVG icons (MIT License)
- [Feather Icons](https://feathericons.com/) - Minimal SVG icons (MIT License)

**Images:**
- [Unsplash](https://unsplash.com/) - Free photos (Unsplash License)
- [Pexels](https://www.pexels.com/) - Free stock photos
- [Pixabay](https://pixabay.com/) - Free images
- [unDraw](https://undraw.co/) - Open-source illustrations (MIT License)

---

## 🛡️ Compliance & Legal

### License Compliance Status
✅ **All resources are compliant with open-source licensing requirements**

**Key Points:**
- No GPL-licensed frontend code (PHP is backend only)
- All fonts are OFL-licensed (no attribution needed in code)
- All used icons are MIT-licensed
- No proprietary or restricted-use resources
- No CDN tracking or analytics libraries requiring opt-out

### Audit Trail
- **Last Updated:** January 2026
- **Reviewed By:** [Add your name/team]
- **Next Review:** [Add planned review date]

### When to Update
- ✅ When adding new external libraries/fonts/icons
- ✅ When upgrading versions of external resources
- ✅ When modifying licensing terms (check each library's updates)
- ✅ Before major releases/deployments
- ✅ Before entering new markets/jurisdictions

---

## 📞 Maintenance & Questions

### If You Need to...

**Add a new font:**
1. Check its license on Google Fonts or Font Awesome
2. Verify it's OFL or another permissive license
3. Update `ATTRIBUTION.html` with the font details
4. Add the font import link to the appropriate PHP file

**Add a new icon library:**
1. Verify the license (prefer MIT or OFL)
2. Check compatibility with our current icons
3. Document all icons used in `ATTRIBUTION.html`
4. Update any inline SVG references

**Add an image:**
1. Use resources from the recommended sources list
2. Document the image in `IMAGE_CREDITS.md`
3. Verify license and attribution requirements
4. Create a local copy if needed for performance

**Audit current resources:**
1. Review `ATTRIBUTION.html` for completeness
2. Check each URL in the document for validity
3. Verify license versions haven't changed
4. Review `IMAGE_CREDITS.md` for outdated entries

---

## 📞 Contact & Support

For questions about licensing, attribution, or compliance:
- Review the relevant documentation file
- Check the official source websites linked in the documents
- Consult with your legal/compliance team if needed
- Contact the SkillSwap development team for internal questions

---

## 📄 File Structure

```
/BPA/
├── ATTRIBUTION.html          ← Main attribution document (HTML, printable to PDF)
├── ATTRIBUTION_README.md     ← This file
├── IMAGE_CREDITS.md          ← Image asset attribution and guidelines
└── ... (other application files)
```

---

**Last Updated:** January 2026
**Version:** 1.0
**Status:** ✅ Compliant with open-source licensing requirements

---

*This documentation ensures SkillSwap remains transparent about external dependencies and complies with all applicable open-source licenses.*
