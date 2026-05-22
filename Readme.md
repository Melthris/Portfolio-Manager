# Portfolio Manager

**Portfolio Manager** is a lightweight, self-hosted portfolio CMS built with **PHP**, **JavaScript**, **CSS**, and **SQLite**.

It is designed for developers, students, freelancers, and technical users who want a customisable portfolio website without needing a large framework, external CMS, or separate database server.

Portfolio Manager provides a public-facing portfolio site and a private management area where the site owner can manage projects, blog posts, qualifications, CV content, technology icons, theme colours, public modules, users, and social media links.

---

## Version

Current release target:

```text
v1.0.0
```

This release is intended to be the first stable public release of Portfolio Manager. The project is primarily forked and retrofitted from my own personal portfolio system.

---

## Table of Contents

- [What Portfolio Manager Does](#what-portfolio-manager-does)
- [Key Features](#key-features)
  - [Portfolio Management](#portfolio-management)
  - [Technology Catalogue](#technology-catalogue)
  - [Operating Systems / Platforms](#operating-systems--platforms)
  - [Blog](#blog)
  - [Contact Me Page](#contact-me-page)
  - [Social Media Links](#social-media-links)
  - [Qualifications](#qualifications)
  - [CV Builder](#cv-builder)
  - [Site Management](#site-management)
  - [User Management](#user-management)
- [Recommended Use Case](#recommended-use-case)
- [Project Status](#project-status)
- [Requirements](#requirements)
- [Installation](#installation)
- [Initial Setup Checklist](#initial-setup-checklist)
- [Planned Future Improvements](#planned-future-improvements)
- [Page-Specific Future Fixes](#page-specific-future-fixes)
- [Notes](#notes)

---

## What Portfolio Manager Does

Portfolio Manager gives you a personal portfolio website with:

- A public Home page
- A public Portfolio page
- A public Blog page
- A public Contact Me page
- A public Qualifications page
- A public CV download/view page
- A private admin login
- Project management
- Blog management
- Contact inbox management
- Qualification management
- CV builder tools
- Site settings
- Theme colour controls
- Technology catalogue management
- Custom technology icon uploads
- Social media link controls
- User management and permissions

The goal is to make Portfolio Manager flexible enough for someone to clone, install, customise, and use as the base for their own portfolio site.

---

## Key Features

### Portfolio Management

Portfolio Manager allows you to create and manage portfolio projects from the admin area.

Each project can include:

- Project title
- Project date
- Project overview
- Public project URL
- Repository URL
- Technology stack
- Operating systems/platforms
- Public/hidden visibility toggle

Repository links can display provider-specific icons, including common providers such as:

- GitHub
- GitLab
- Bitbucket
- Azure Repos
- Codeberg
- Gitea
- SourceHut
- Generic Git fallback

The Portfolio page supports public filtering by:

- Search text
- Year
- Technology

Projects marked as hidden remain stored but are not publicly displayed.

---

### Technology Catalogue

The Technology Catalogue is managed through Site Management.

Technology items are grouped by category, such as:

- Languages
- Frameworks / Libraries
- Game Engines
- Runtimes
- Databases
- Data / Markup / Config
- Tools / Platforms
- Cloud / Hosting
- Design / UI
- Other / Misc

Default technology items are included, but users can:

- Hide technologies they do not use
- Update labels
- Update categories
- Replace icons
- Add custom technologies
- Upload custom SVG or PNG icons

Technology icons are stored alongside the default icon set so the project does not split default and custom technology assets into unrelated folders.

---

### Operating Systems / Platforms

Portfolio projects and blog posts can be tagged with operating systems or platforms.

Examples include:

- Web
- Windows
- macOS
- Linux
- iOS
- Android
- Raspberry Pi

These are separate from the technology stack because they describe where the project runs, rather than what it was built with.

---

### Blog

Portfolio Manager includes a blog system for writing updates, development notes, learning logs, technical posts, and general project commentary.

Blog posts can include:

- Title
- Author display name
- Mood/status
- Rich text content
- Image or YouTube embed support
- Technology tags
- Operating system/platform tags
- Published/draft visibility

The public Blog page displays posts with preview text generated from the start of the blog post content.

---

### Contact Me Page

The Contact Me page includes editable content controlled from Site Management.

The site owner can edit:

- The main Contact Me heading
- The paragraph underneath it
- Up to three small information cards
- Social media links visible on the Contact Me page

The Contact Me form allows visitors to send messages to the site owner. Messages can be reviewed through the admin area.

---

### Social Media Links

Social media links can be managed from Site Management.

The default supported social platforms are:

- LinkedIn
- YouTube
- X / Twitter
- Facebook
- Instagram
- TikTok
- Mastodon
- Bluesky
- Threads
- Discord
- Twitch

Social profile entries can be configured with:

- Label
- Profile URL
- Icon
- Display order
- Footer visibility
- Contact Me page visibility
- White/black SVG filter option
- Active/hidden status

The social media pin preview allows the site owner to rearrange the display order of social media icons.

Social icons can be shown in:

- The footer
- The Contact Me page
- Both
- Neither

The footer icons are intentionally small and sit alongside the Portfolio Manager footer signage.

---

### Qualifications

Portfolio Manager includes a public Qualifications page and an admin management page.

Qualifications can be separated into:

- Formal Qualifications
- Informal Qualifications

Formal qualifications are displayed first, followed by informal qualifications.

Qualification cards support:

- Title
- Provider
- Qualification type
- Description
- Obtained date
- Expiry date
- Credential URL
- Visibility

On desktop, qualifications are displayed in a two-column card layout where possible. On mobile, the cards stack cleanly.

---

### CV Builder

Portfolio Manager includes a CV builder area for storing reusable CV content.

The CV Builder is intended to help the site owner manage the core information they may want to show publicly, export, or reuse across applications.

CV-related content may include:

- Name and professional title
- Profile summary
- Skills
- Education
- Qualifications
- Work history
- Project highlights
- Social/contact links
- Downloadable CV content

---

### Site Management

The Site Management area is used to control public-facing site content and configuration.

Depending on the enabled modules, Site Management can control:

- Public module visibility
- Home page content
- Contact Me page content
- Social media links
- Footer social links
- Technology catalogue entries
- Theme colour variables
- Icon uploads
- Site title and branding options

The goal of Site Management is to allow most public-facing text, links, and presentation options to be adjusted without editing source files directly.

---

### User Management

Portfolio Manager includes user management for the private admin area.

The system is designed to support:

- A primary administrator account
- Additional users
- Role-based access control
- Module-specific permissions
- Protected management pages

This allows the site owner to control who can access portfolio, blog, CV, qualifications, site settings, and other admin features.

---

## Recommended Use Case

Portfolio Manager is best suited for:

- Developers who want a self-hosted portfolio
- Students building a technical portfolio
- Freelancers who want project, blog, and contact functionality
- Technical users who prefer editing and hosting their own PHP-based projects
- Users who want SQLite rather than a separate database server

It is intentionally lightweight and framework-free.

---

## Project Status

Portfolio Manager is currently being prepared for a `v1.0.0` release.

This release focuses on producing a stable, usable baseline with:

- Public portfolio functionality
- Public blog functionality
- Public contact functionality
- Public qualifications and CV sections
- Admin management tools
- SQLite-backed storage
- Customisable visual settings
- User and permission management

---

## Requirements

Portfolio Manager is designed for a standard PHP hosting environment.

Recommended requirements:

- PHP 8.x or newer
- SQLite support enabled for PHP
- A web server such as Apache, Nginx, Laragon, XAMPP, MAMP, or PHP's built-in local development server
- Modern desktop or mobile browser
- File write permissions for any folders that store uploads, icons, database files, or generated assets

Optional, depending on enabled features:

- Mail support for the Contact Me form
- URL rewriting support for cleaner routes
- Image handling support if image upload or manipulation features are enabled

---

## Installation

> Installation details may vary depending on your hosting environment.

A general installation flow is:

1. Clone or download the project.
2. Place the project files inside your local or hosted web directory.
3. Ensure PHP and SQLite are enabled.
4. Confirm the required writable directories have correct permissions.
5. Open the site in a browser.
6. Complete any first-run setup steps.
7. Log in to the admin area.
8. Configure your site title, home page content, portfolio entries, blog posts, qualifications, CV content, social links, and theme settings.

Example local development command:

```bash
php -S localhost:8000
```

Then visit:

```text
http://localhost:8000
```

---

## Initial Setup Checklist

After installation, review the following:

- [ ] Confirm the site loads publicly.
- [ ] Confirm the admin login works.
- [ ] Change the default admin password.
- [ ] Set the site title.
- [ ] Update Home page content.
- [ ] Update Contact Me page content.
- [ ] Add or hide social media links.
- [ ] Add portfolio projects.
- [ ] Add technology tags and icons.
- [ ] Add OS/platform tags where relevant.
- [ ] Add blog posts or hide the Blog module.
- [ ] Add qualifications or hide the Qualifications module.
- [ ] Add CV content or hide the CV module.
- [ ] Test the Contact Me form.
- [ ] Check the site on mobile.
- [ ] Review public/hidden visibility settings.
- [ ] Review user permissions if multiple users are enabled.

---

## Planned Future Improvements

The following items are planned or being considered for future releases:

- Remove remaining JSON-based storage where SQLite now supersedes it.
- Improved Light/Dark Mode. Currently very Dark Mode biased
- Improve commenting throughout project files.
- Add settings to control border radius.
- Standardise iconography for technology types.
- Continue revising the Portfolio Management experience.
- Refactor CSS for improved maintainability.
- Add a Back to Top button.
- Add a burger-menu style interaction for settings/options where appropriate.

---

## Page-Specific Future Fixes

### Site Management

Planned improvements:

- Add/update a dedicated update button under public modules.
- Add/update a dedicated update button under Home Page, Contact Me, and Social Media sections.
- Consolidate colour variables into a dedicated button or grouped area.
- Consolidate the Technology Catalogue into a dedicated button or grouped area.
- Add logo upload functionality for the top-left site logo.

### Blog

Planned improvements:

- Refine the general Blog page layout.
- Improve embedded YouTube and image handling.
- Add the ability to tag a project from a blog post.
- Improve rich text rendering on the main Blog page to address formatting issues.

### CV Builder

Planned improvements:

- Add controls to show/hide social links on the Contact Me page and Footer.
- Fix CSS alignment issues with buttons, OS/platform selectors, and technology stack selectors.

---

## Notes

Portfolio Manager is intended to be practical, customisable, and lightweight. It is not trying to replace large CMS platforms. Instead, it provides a focused portfolio system that can be modified, self-hosted, and extended by technical users.

The `v1.0.0` release should be treated as the first stable public baseline, with future improvements focused on code cleanup, layout refinement, admin usability, and feature polish.
