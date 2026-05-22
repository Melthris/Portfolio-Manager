/**
 * Site Management live-preview behaviour.
 *
 * The page uses normal form submission for persistence, while this file makes
 * colour changes appear instantly so users can see how a colour variable affects
 * the current page before saving it.
 */
(function () {
  'use strict';

  /**
   * Applies a CSS variable value to the current document immediately.
   *
   * @param {HTMLInputElement} input - Colour picker linked to a CSS variable.
   * @returns {void}
   */
  function applyThemeInput(input) {
    const variableName = input.dataset.themeVariable;

    if (!variableName) {
      return;
    }

    document.documentElement.style.setProperty(variableName, input.value);

    if (variableName === '--border-color') {
      applyBorderDerivedColours(input.value);
    }
  }

  /**
   * Updates border-related variables that are derived from the main border colour.
   *
   * @param {string} hexColour - Six-digit colour selected in Site Management.
   * @returns {void}
   */
  function applyBorderDerivedColours(hexColour) {
    if (!/^#[0-9a-fA-F]{6}$/.test(hexColour)) {
      return;
    }

    document.documentElement.style.setProperty('--border-accent-mid', `${hexColour}26`);
    document.documentElement.style.setProperty('--border-accent-strong', `${hexColour}98`);
    document.documentElement.style.setProperty('--focus-ring', `${hexColour}26`);
  }

  /**
   * Restores the visible form controls to Portfolio Manager defaults.
   *
   * This is only a visual preview step. The server-side handler performs the
   * real reset after the restore button submits the form.
   *
   * @returns {void}
   */
  function previewDefaultSettings() {
    document.querySelectorAll('[data-theme-variable]').forEach((control) => {
      if (!(control instanceof HTMLInputElement)) {
        return;
      }

      const fallback = control.dataset.themeDefault;

      if (fallback) {
        control.value = fallback;
        applyThemeInput(control);
      }
    });

    document.querySelectorAll('[data-module-default="checked"]').forEach((control) => {
      if (control instanceof HTMLInputElement) {
        control.checked = true;
      }
    });

    const siteTitleInput = document.querySelector('[data-site-title-input]');

    if (siteTitleInput instanceof HTMLInputElement) {
      siteTitleInput.value = 'Portfolio Manager';
    }

    const homeSubheadingInput = document.querySelector('[data-home-subheading-input]');

    if (homeSubheadingInput instanceof HTMLInputElement && homeSubheadingInput.dataset.homeSubheadingDefault) {
      homeSubheadingInput.value = homeSubheadingInput.dataset.homeSubheadingDefault;
    }

    const homeBodyInput = document.querySelector('[data-home-body-input]');

    if (homeBodyInput instanceof HTMLTextAreaElement && homeBodyInput.dataset.homeBodyDefault) {
      homeBodyInput.value = homeBodyInput.dataset.homeBodyDefault;
    }

    const contactHeadingInput = document.querySelector('[data-contact-heading-default]');

    if (contactHeadingInput instanceof HTMLInputElement) {
      contactHeadingInput.value = contactHeadingInput.dataset.contactHeadingDefault || '';
    }

    const contactBodyInput = document.querySelector('[data-contact-body-default]');

    if (contactBodyInput instanceof HTMLTextAreaElement) {
      contactBodyInput.value = contactBodyInput.dataset.contactBodyDefault || '';
    }

    document.querySelectorAll('[data-contact-card-enabled-default]').forEach((control) => {
      if (control instanceof HTMLInputElement) {
        control.checked = control.dataset.contactCardEnabledDefault === 'checked';
      }
    });

    document.querySelectorAll('[data-contact-card-label-default]').forEach((control) => {
      if (control instanceof HTMLInputElement) {
        control.value = control.dataset.contactCardLabelDefault || '';
      }
    });

    document.querySelectorAll('[data-contact-card-text-default]').forEach((control) => {
      if (control instanceof HTMLInputElement) {
        control.value = control.dataset.contactCardTextDefault || '';
      }
    });
  }

  /**
   * Updates the hidden action field before form submission.
   *
   * @param {string} action - Action value sent to the server.
   * @returns {void}
   */
  function setFormAction(action) {
    const actionInput = document.querySelector('[data-site-settings-action]');

    if (actionInput instanceof HTMLInputElement) {
      actionInput.value = action;
    }
  }



  /**
   * Shows one technology catalogue category at a time.
   *
   * The catalogue can become long once the shipped defaults and custom items are
   * combined. Filtering the editable list by category keeps Site Management
   * readable without changing the underlying shared catalogue source.
   *
   * @param {HTMLSelectElement} select - Category selector used by the editor.
   * @returns {void}
   */
  function applyTechnologyCategoryFilter(select) {
    const selectedCategory = select.value;

    document.querySelectorAll('[data-tech-category-section]').forEach((section) => {
      if (!(section instanceof HTMLElement)) {
        return;
      }

      const isSelected = section.dataset.techCategorySection === selectedCategory;
      section.classList.toggle('is-active-category', isSelected);
      section.hidden = !isSelected;
    });
  }

  /**
   * Wires the Technology Catalogue category dropdown.
   *
   * @returns {void}
   */
  function initialiseTechnologyCatalogueFilter() {
    const filter = document.querySelector('[data-tech-category-filter]');

    if (!(filter instanceof HTMLSelectElement)) {
      return;
    }

    filter.addEventListener('change', () => applyTechnologyCategoryFilter(filter));
    applyTechnologyCategoryFilter(filter);
  }

  /**
   * Wires the live-preview controls once the page has loaded.
   *
   * @returns {void}
   */
  function initialiseSiteManagementPreview() {
    document.querySelectorAll('[data-theme-variable]').forEach((control) => {
      if (!(control instanceof HTMLInputElement)) {
        return;
      }

      control.addEventListener('input', () => applyThemeInput(control));
      control.addEventListener('change', () => applyThemeInput(control));
    });

    const saveButton = document.querySelector('[data-site-save-button]');
    const restoreButton = document.querySelector('[data-site-restore-button]');

    if (saveButton instanceof HTMLButtonElement) {
      saveButton.addEventListener('click', () => setFormAction('save'));
    }

    if (restoreButton instanceof HTMLButtonElement) {
      restoreButton.addEventListener('click', () => {
        setFormAction('restore_defaults');
        previewDefaultSettings();
      });
    }
  }

  initialiseSiteManagementPreview();
  initialiseTechnologyCatalogueFilter();
}());

/**
 * Adds tabbed navigation to the Site Management content editor.
 *
 * Keeping Home Page and Contact Me editing inside one window prevents the
 * shorter Home editor from being stretched by the longer Contact editor, while
 * still keeping the available content sections obvious to the user.
 */
(function () {
  'use strict';

  /**
   * Shows the selected content editor panel and hides the others.
   *
   * @param {HTMLButtonElement} activeTab - Tab button selected by the user.
   * @returns {void}
   */
  function activateSiteContentTab(activeTab) {
    const target = activeTab.dataset.siteContentTab;

    if (!target) {
      return;
    }

    document.querySelectorAll('[data-site-content-tab]').forEach((tab) => {
      if (!(tab instanceof HTMLButtonElement)) {
        return;
      }

      const isActive = tab === activeTab;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    document.querySelectorAll('[data-site-content-panel]').forEach((panel) => {
      if (!(panel instanceof HTMLElement)) {
        return;
      }

      const isActive = panel.dataset.siteContentPanel === target;
      panel.classList.toggle('is-active', isActive);
      panel.hidden = !isActive;
    });
  }

  /**
   * Wires Site Management content tab buttons.
   *
   * @returns {void}
   */
  function initialiseSiteContentTabs() {
    document.querySelectorAll('[data-site-content-tab]').forEach((tab) => {
      if (!(tab instanceof HTMLButtonElement)) {
        return;
      }

      tab.addEventListener('click', () => activateSiteContentTab(tab));
    });
  }

  initialiseSiteContentTabs();
}());

/**
 * Adds image previews to Technology Catalogue icon uploads.
 *
 * Existing catalogue rows temporarily swap the current icon for the selected
 * replacement file before save. The Add New Item form shows a small preview
 * panel beneath its upload control. Object URLs are revoked when replaced to
 * avoid leaking browser memory during repeated selections.
 */
(function () {
  'use strict';

  /**
   * Revokes a previously created object URL assigned to a preview image.
   *
   * @param {HTMLImageElement} image - Preview image that may hold an object URL.
   * @returns {void}
   */
  function revokePreviousPreview(image) {
    const previousUrl = image.dataset.previewObjectUrl;

    if (previousUrl) {
      URL.revokeObjectURL(previousUrl);
      delete image.dataset.previewObjectUrl;
    }
  }

  /**
   * Locates the preview image controlled by a Technology Catalogue file input.
   *
   * @param {HTMLInputElement} input - File input used for a custom/replacement icon.
   * @returns {HTMLImageElement|null}
   */
  function findPreviewImage(input) {
    const existingSummary = input.closest('.tech-catalogue-summary');

    if (existingSummary instanceof HTMLElement) {
      const existingImage = existingSummary.querySelector('[data-tech-icon-preview-image]');
      return existingImage instanceof HTMLImageElement ? existingImage : null;
    }

    const addForm = input.closest('.tech-catalogue-add-card');

    if (addForm instanceof HTMLElement) {
      const preview = addForm.querySelector('[data-tech-icon-preview]');
      const previewImage = preview ? preview.querySelector('img') : null;

      if (preview instanceof HTMLElement) {
        preview.hidden = false;
      }

      return previewImage instanceof HTMLImageElement ? previewImage : null;
    }

    return null;
  }

  /**
   * Shows the selected PNG/SVG file as an immediate icon preview.
   *
   * @param {HTMLInputElement} input - File input that triggered the preview.
   * @returns {void}
   */
  function previewSelectedIcon(input) {
    const file = input.files && input.files.length > 0 ? input.files[0] : null;
    const previewImage = findPreviewImage(input);

    if (!(previewImage instanceof HTMLImageElement)) {
      return;
    }

    revokePreviousPreview(previewImage);

    if (!file) {
      previewImage.classList.remove('is-previewing-upload');
      return;
    }

    const previewUrl = URL.createObjectURL(file);
    previewImage.src = previewUrl;
    previewImage.dataset.previewObjectUrl = previewUrl;
    previewImage.classList.add('is-previewing-upload');
  }

  /**
   * Wires all Technology Catalogue icon upload previews.
   *
   * @returns {void}
   */
  function initialiseTechnologyIconPreviews() {
    document.querySelectorAll('[data-tech-icon-preview-input]').forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }

      input.addEventListener('change', () => previewSelectedIcon(input));
    });
  }

  initialiseTechnologyIconPreviews();
}());

/**
 * Enables drag-and-drop ordering for the Social Media pin preview.
 *
 * This is a bit clunky at the moment so I'll need to review this further in a future release
 * as the drag and drop feels slightly off.
 * 
 * The preview writes its new order into hidden `social_order[]` inputs so the
 * PHP handler can persist the order without needing a separate JSON endpoint.
 */
(function () {
  'use strict';

  let draggedItem = null;

  /**
   * Rewrites hidden order inputs to match the current visual pin order.
   *
   * @param {HTMLElement} list - Social pin preview list.
   * @returns {void}
   */
  function refreshSocialOrderInputs(list) {
    list.querySelectorAll('[data-social-order-item]').forEach((item) => {
      if (!(item instanceof HTMLElement)) {
        return;
      }

      const input = item.querySelector('input[name="social_order[]"]');

      if (input instanceof HTMLInputElement) {
        input.value = item.dataset.socialOrderId || input.value;
      }
    });
  }

  /**
   * Returns the preview item immediately after the pointer position.
   *
   * @param {HTMLElement} list - Social pin preview list.
   * @param {number} x - Pointer X coordinate.
   * @param {number} y - Pointer Y coordinate.
   * @returns {HTMLElement|null} Item that should follow the dragged item.
   */
  function getDragAfterElement(list, x, y) {
    const items = [...list.querySelectorAll('[data-social-order-item]:not(.is-dragging)')]
      .filter((item) => item instanceof HTMLElement);

    return items.reduce((closest, item) => {
      const box = item.getBoundingClientRect();
      const offsetY = y - box.top - box.height / 2;
      const offsetX = x - box.left - box.width / 2;
      const distance = Math.hypot(offsetX, offsetY);

      if (offsetY < 0 && distance < closest.distance) {
        return { distance, element: item };
      }

      return closest;
    }, { distance: Number.POSITIVE_INFINITY, element: null }).element;
  }

  /**
   * Wires draggable social media pin ordering.
   *
   * @returns {void}
   */
  function initialiseSocialPinOrdering() {
    const list = document.querySelector('[data-social-order-list]');

    if (!(list instanceof HTMLElement)) {
      return;
    }

    list.querySelectorAll('[data-social-order-item]').forEach((item) => {
      if (!(item instanceof HTMLElement)) {
        return;
      }

      item.addEventListener('dragstart', () => {
        draggedItem = item;
        item.classList.add('is-dragging');
      });

      item.addEventListener('dragend', () => {
        item.classList.remove('is-dragging');
        draggedItem = null;
        refreshSocialOrderInputs(list);
      });
    });

    list.addEventListener('dragover', (event) => {
      event.preventDefault();

      if (!(draggedItem instanceof HTMLElement)) {
        return;
      }

      const afterElement = getDragAfterElement(list, event.clientX, event.clientY);

      if (afterElement === null) {
        list.appendChild(draggedItem);
      } else {
        list.insertBefore(draggedItem, afterElement);
      }
    });
  }

  initialiseSocialPinOrdering();
}());

/**
 * Keeps Social Media management cards and the draggable pin preview in sync.
 *
 * The saved database values remain the source of truth, but the admin screen
 * should immediately show what the user is changing before they click Save.
 * This includes label changes, icon uploads, visibility toggles, and the
 * white/black SVG filter switch.
 */
(function () {
  'use strict';

  /**
   * Applies the configured SVG colour filter class to a social icon element.
   *
   * @param {HTMLElement|null} element - Icon or preview pin element.
   * @param {boolean} useBlack - Whether the black SVG filter should be used.
   * @returns {void}
   */
  function applySocialIconFilter(element, useBlack) {
    if (!(element instanceof HTMLElement)) {
      return;
    }

    element.classList.toggle('social-icon-filter-black', useBlack);
    element.classList.toggle('social-icon-filter-white', !useBlack);
  }


  /**
   * Reads the selected white/black icon filter from a radio toggle group.
   *
   * The form submits the selected radio value to PHP, while this helper keeps
   * the admin preview in sync before the user saves the social profile.
   *
   * @param {ParentNode} root - Form or container holding the filter radios.
   * @param {string} selector - Radio selector to inspect.
   * @returns {boolean} True when the black filter is selected.
   */
  function isBlackSocialFilterSelected(root, selector) {
    const selected = root.querySelector(`${selector}:checked`);

    return selected instanceof HTMLInputElement && selected.value === 'black';
  }

  /**
   * Creates a temporary browser URL for a selected icon file.
   *
   * @param {HTMLInputElement|null} input - File input containing the icon.
   * @returns {string} Temporary object URL, or an empty string when unavailable.
   */
  function selectedSocialIconUrl(input) {
    if (!(input instanceof HTMLInputElement) || !input.files || input.files.length === 0) {
      return '';
    }

    const file = input.files[0];

    if (!file || !['image/svg+xml', 'image/png'].includes(file.type)) {
      return '';
    }

    return URL.createObjectURL(file);
  }

  /**
   * Updates the matching draggable preview pin for an existing social profile.
   *
   * @param {HTMLFormElement} form - Existing social profile edit form.
   * @returns {void}
   */
  function updateExistingSocialPreview(form) {
    const socialId = form.dataset.socialEditId || '';
    const preview = document.querySelector(`[data-social-preview-pin="${CSS.escape(socialId)}"]`);

    if (!(preview instanceof HTMLElement)) {
      return;
    }

    const labelInput = form.querySelector('[data-social-label-input]');
    const cardLabel = form.querySelector('[data-social-card-label]');
    const previewLabel = preview.querySelector('[data-social-preview-label]');
    const cardIcon = form.querySelector('[data-social-card-icon]');
    const previewIcon = preview.querySelector('[data-social-preview-icon]');
    const iconInput = form.querySelector('[data-social-icon-input]');
    const visibleToggle = form.querySelector('[data-social-visible-toggle]');

    const label = labelInput instanceof HTMLInputElement ? labelInput.value.trim() : '';
    const fallbackLabel = cardLabel instanceof HTMLElement ? cardLabel.textContent || 'Social profile' : 'Social profile';
    const displayLabel = label || fallbackLabel;

    if (cardLabel instanceof HTMLElement) {
      cardLabel.textContent = displayLabel;
    }

    if (previewLabel instanceof HTMLElement) {
      previewLabel.textContent = displayLabel;
    }

    preview.setAttribute('title', displayLabel);

    const iconUrl = selectedSocialIconUrl(iconInput instanceof HTMLInputElement ? iconInput : null);

    if (iconUrl) {
      if (cardIcon instanceof HTMLImageElement) {
        cardIcon.src = iconUrl;
      }

      if (previewIcon instanceof HTMLImageElement) {
        previewIcon.src = iconUrl;
      }
    }

    const useBlack = isBlackSocialFilterSelected(form, '[data-social-filter-toggle]');
    applySocialIconFilter(cardIcon instanceof HTMLElement ? cardIcon : null, useBlack);
    applySocialIconFilter(preview, useBlack);

    if (visibleToggle instanceof HTMLInputElement) {
      preview.classList.toggle('is-hidden-preview', !visibleToggle.checked);
      preview.classList.toggle('is-muted', !visibleToggle.checked);
    }
  }

  /**
   * Wires live updates for every existing social profile card.
   *
   * @returns {void}
   */
  function initialiseExistingSocialLivePreview() {
    document.querySelectorAll('[data-social-edit-form]').forEach((form) => {
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      form.querySelectorAll('input').forEach((input) => {
        input.addEventListener('input', () => updateExistingSocialPreview(form));
        input.addEventListener('change', () => updateExistingSocialPreview(form));
      });

      updateExistingSocialPreview(form);
    });
  }

  /**
   * Wires live icon/filter preview for the Add Social Profile form.
   *
   * New rows do not appear in the saved pin-order preview until they are saved,
   * but the uploaded icon preview still updates so the user can confirm the file.
   *
   * @returns {void}
   */
  function initialiseAddSocialIconPreview() {
    const form = document.querySelector('[data-social-add-form]');

    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    const iconInput = form.querySelector('[data-social-add-icon-input]');
    const iconPreview = form.querySelector('[data-social-add-icon-preview]');
    const filterToggles = form.querySelectorAll('[data-social-add-filter-toggle]');

    const update = () => {
      const iconUrl = selectedSocialIconUrl(iconInput instanceof HTMLInputElement ? iconInput : null);

      if (iconUrl && iconPreview instanceof HTMLImageElement) {
        iconPreview.src = iconUrl;
      }

      const useBlack = isBlackSocialFilterSelected(form, '[data-social-filter-toggle]');
      applySocialIconFilter(iconPreview instanceof HTMLElement ? iconPreview : null, useBlack);
    };

    iconInput?.addEventListener('change', update);
    filterToggles.forEach((toggle) => toggle.addEventListener('change', update));
    update();
  }

  initialiseExistingSocialLivePreview();
  initialiseAddSocialIconPreview();
}());

/**
 * Saves Social Media and Technology Catalogue forms without leaving the current
 * Site Management tab or scroll position.
 *
 * These forms still point at normal PHP handlers so the page continues to work
 * without JavaScript. When JavaScript is available, the submission is sent with
 * fetch/FormData and the admin stays in place for faster bulk editing.
 */
(function () {
  'use strict';

  /**
   * Returns true when a form should be saved in-place by this helper.
   *
   * @param {HTMLFormElement} form - Candidate form.
   * @returns {boolean} True when the form belongs to the social/tech handlers.
   */
  function isInlineManagedForm(form) {
    const action = String(form.getAttribute('action') || '');

    return action.includes('social-links-handler.php') || action.includes('tech-catalogue-handler.php');
  }

  /**
   * Displays a small save message inside the relevant management card.
   *
   * @param {HTMLFormElement} form - Form that was saved.
   * @param {string} message - Message to show.
   * @param {boolean} isError - Whether the message should be styled as an error.
   * @returns {void}
   */
  function showInlineSaveStatus(form, message, isError = false) {
    const host = form.closest('.social-profile-card, .custom-tech-row, .tech-catalogue-add-card, .social-pin-order-card, .site-management-card') || form.parentElement;

    if (!(host instanceof HTMLElement)) {
      return;
    }

    let status = host.querySelector('[data-inline-save-status]');

    if (!(status instanceof HTMLElement)) {
      status = document.createElement('p');
      status.dataset.inlineSaveStatus = 'true';
      status.className = 'inline-save-status';
      host.appendChild(status);
    }

    status.textContent = message;
    status.classList.toggle('is-error', isError);

    window.setTimeout(() => {
      if (status instanceof HTMLElement && status.isConnected) {
        status.textContent = '';
        status.classList.remove('is-error');
      }
    }, 3500);
  }

  /**
   * Enables/disables submit buttons attached to a form.
   *
   * This also catches buttons outside the form that use the HTML `form` attribute.
   *
   * @param {HTMLFormElement} form - Form being submitted.
   * @param {boolean} disabled - Disabled state.
   * @returns {void}
   */
  function setFormButtonsDisabled(form, disabled) {
    const formId = form.id ? CSS.escape(form.id) : '';
    const buttons = [
      ...form.querySelectorAll('button[type="submit"], input[type="submit"]'),
      ...(formId ? document.querySelectorAll(`[form="${formId}"]`) : []),
    ];

    buttons.forEach((button) => {
      if (button instanceof HTMLButtonElement || button instanceof HTMLInputElement) {
        button.disabled = disabled;
      }
    });
  }

  /**
   * Updates the current UI after a no-refresh save.
   *
   * @param {HTMLFormElement} form - Saved form.
   * @param {FormData} formData - Submitted form data.
   * @returns {void}
   */
  function applyInlinePostSaveUi(form, formData) {
    const socialAction = String(formData.get('social_action') || '');
    const techAction = String(formData.get('tech_action') || '');

    if (socialAction === 'delete_social_link') {
      const id = String(formData.get('social_id') || '');
      document.querySelector(`[data-social-card-id="${CSS.escape(id)}"]`)?.classList.add('is-muted');
      document.querySelector(`[data-social-preview-pin="${CSS.escape(id)}"]`)?.classList.add('is-hidden-preview');
    }

    if (techAction === 'delete_tech_item' || techAction === 'delete_custom_tech') {
      form.closest('.custom-tech-row, .tech-catalogue-row')?.classList.add('is-muted');
    }
  }

  /**
   * Submits a social/technology form in the background.
   *
   * @param {SubmitEvent} event - Submit event.
   * @returns {void}
   */
  async function submitInlineManagedForm(event) {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !isInlineManagedForm(form)) {
      return;
    }

    event.preventDefault();

    const formData = new FormData(form);
    setFormButtonsDisabled(form, true);

    try {
      const response = await fetch(form.action, {
        method: form.method || 'POST',
        body: formData,
        headers: {
          'X-Portfolio-Manager-Ajax': '1',
        },
        credentials: 'same-origin',
      });

      if (!response.ok) {
        throw new Error(`Save failed (${response.status}).`);
      }

      applyInlinePostSaveUi(form, formData);
      showInlineSaveStatus(form, 'Saved without refreshing the page.');
    } catch (error) {
      console.error(error);
      showInlineSaveStatus(form, error instanceof Error ? error.message : 'Save failed.', true);
    } finally {
      setFormButtonsDisabled(form, false);
    }
  }

  document.addEventListener('submit', submitInlineManagedForm);
}());

/**
 * Mirrors Social Media changes into the footer preview on the current Site
 * Management page.
 *
 * The database is still saved by the PHP handler. This helper prevents the
 * footer preview from feeling stale when saves happen without a full page reload.
 */
(function () {
  'use strict';

  /**
   * Normalises a social profile URL for a footer preview href.
   *
   * THIS NEEDS TO BE REFACTORED AND AMENDED FOR OTHER MEDIA TYPES, NOT JUST HTTPS BASED
   * 
   * @param {string} value - Raw profile URL from the Site Management form.
   * @returns {string} Safe preview href, or an empty string.
   */
  function normalisePreviewSocialHref(value) {
    const raw = String(value || '').trim();

    if (!raw) {
      return '';
    }

    const candidate = /^[a-z][a-z0-9+.-]*:/i.test(raw) ? raw : `https://${raw}`;

    try {
      const parsed = new URL(candidate);

      if (!['http:', 'https:', 'mailto:', 'tel:'].includes(parsed.protocol)) {
        return '';
      }

      return parsed.href;
    } catch {
      return '';
    }
  }

  /**
   * Rebuilds footer social icons from the currently visible social edit forms.
   *
   * @returns {void}
   */
  function refreshFooterSocialPreview() {
    const footer = document.querySelector('.footer');

    if (!(footer instanceof HTMLElement)) {
      return;
    }

    let nav = footer.querySelector('.footer-social-links');
    const heading = footer.querySelector('h6');

    if (!(nav instanceof HTMLElement)) {
      nav = document.createElement('nav');
      nav.className = 'footer-social-links';
      nav.setAttribute('aria-label', 'Footer social media links');
      footer.insertBefore(nav, heading || footer.firstChild);
    }

    const links = [];

    document.querySelectorAll('[data-social-edit-form]').forEach((form) => {
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const active = form.querySelector('input[name="is_active"]');
      const showFooter = form.querySelector('input[name="show_in_footer"]');
      const urlInput = form.querySelector('input[name="profile_url"]');
      const labelInput = form.querySelector('[data-social-label-input]');
      const icon = form.querySelector('[data-social-card-icon]');
      const blackFilter = form.querySelector('[data-social-filter-toggle][value="black"]');

      if (!(active instanceof HTMLInputElement) || !active.checked) {
        return;
      }

      if (!(showFooter instanceof HTMLInputElement) || !showFooter.checked) {
        return;
      }

      const href = normalisePreviewSocialHref(urlInput instanceof HTMLInputElement ? urlInput.value : '');

      if (!href || !(icon instanceof HTMLImageElement)) {
        return;
      }

      const label = labelInput instanceof HTMLInputElement && labelInput.value.trim() !== ''
        ? labelInput.value.trim()
        : 'Social profile';

      links.push({
        href,
        label,
        src: icon.src,
        filter: blackFilter instanceof HTMLInputElement && blackFilter.checked ? 'black' : 'white',
      });
    });

    nav.innerHTML = links.map((link) => `
      <a class="footer-social-link social-icon-filter-${link.filter}" href="${link.href}" target="_blank" rel="noopener noreferrer" aria-label="${link.label}" title="${link.label}">
        <img src="${link.src}" alt="">
      </a>
    `).join('');

    nav.hidden = links.length === 0;
  }

  document.addEventListener('input', (event) => {
    if (event.target instanceof HTMLElement && event.target.closest('[data-social-edit-form]')) {
      refreshFooterSocialPreview();
    }
  });

  document.addEventListener('change', (event) => {
    if (event.target instanceof HTMLElement && event.target.closest('[data-social-edit-form]')) {
      refreshFooterSocialPreview();
    }
  });

  document.addEventListener('DOMContentLoaded', refreshFooterSocialPreview);
  refreshFooterSocialPreview();
}());
