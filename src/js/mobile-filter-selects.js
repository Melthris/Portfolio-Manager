/**
 * Mobile filter select enhancement for Portfolio Manager.
 *
 * Mobile browser emulation can render native <select> option popups at a tiny
 * scale or detach them from the control. This script keeps the real select in
 * the DOM for forms and existing JavaScript, then adds a mobile-only custom
 * dropdown that mirrors the select options at a readable size.
 */
(() => {
  const mobileQuery = window.matchMedia('(max-width: 700px)');
  const enhancedSelects = new WeakMap();

  /**
   * Returns the visible label for a select's currently selected option.
   *
   * @param {HTMLSelectElement} select The native select being mirrored.
   * @returns {string} The selected option text.
   */
  function getSelectedText(select) {
    return select.options[select.selectedIndex]?.textContent?.trim() || 'Select option';
  }

  /**
   * Closes every open custom mobile dropdown except the optional active one.
   *
   * @param {HTMLElement|null} activeWrapper Wrapper that should remain open.
   * @returns {void}
   */
  function closeOtherDropdowns(activeWrapper = null) {
    document.querySelectorAll('.mobile-filter-select.is-open').forEach((wrapper) => {
      if (wrapper !== activeWrapper) {
        wrapper.classList.remove('is-open');
        wrapper.querySelector('.mobile-filter-select-button')?.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /**
   * Builds a custom option button for the supplied native option.
   *
   * @param {HTMLOptionElement} option Native select option.
   * @param {HTMLSelectElement} select Native select that receives the value.
   * @param {HTMLButtonElement} trigger Button displaying the selected label.
   * @param {HTMLElement} wrapper Custom dropdown wrapper.
   * @returns {HTMLButtonElement} Rendered custom option button.
   */
  function buildOptionButton(option, select, trigger, wrapper) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'mobile-filter-select-option';
    button.textContent = option.textContent || option.value;
    button.dataset.value = option.value;
    button.setAttribute('role', 'option');
    button.setAttribute('aria-selected', option.selected ? 'true' : 'false');

    button.addEventListener('click', () => {
      select.value = option.value;
      trigger.textContent = getSelectedText(select);
      wrapper.classList.remove('is-open');
      trigger.setAttribute('aria-expanded', 'false');
      refreshCustomOptions(select);
      select.dispatchEvent(new Event('change', { bubbles: true }));
    });

    return button;
  }

  /**
   * Rebuilds the visible option list so dynamically populated selects stay in sync.
   *
   * @param {HTMLSelectElement} select Native select being mirrored.
   * @returns {void}
   */
  function refreshCustomOptions(select) {
    const enhanced = enhancedSelects.get(select);

    if (!enhanced) {
      return;
    }

    enhanced.trigger.textContent = getSelectedText(select);
    enhanced.list.innerHTML = '';

    [...select.options].forEach((option) => {
      enhanced.list.appendChild(buildOptionButton(option, select, enhanced.trigger, enhanced.wrapper));
    });
  }

  /**
   * Enhances one select with a mobile-readable custom dropdown.
   *
   * The real select remains present and continues to drive forms and existing
   * event listeners. CSS hides it visually only on mobile once enhanced.
   *
   * @param {HTMLSelectElement} select Native filter select.
   * @returns {void}
   */
  function enhanceSelect(select) {
    if (enhancedSelects.has(select)) {
      return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'mobile-filter-select';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'mobile-filter-select-button';
    trigger.textContent = getSelectedText(select);
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const list = document.createElement('div');
    list.className = 'mobile-filter-select-list';
    list.setAttribute('role', 'listbox');

    select.insertAdjacentElement('afterend', wrapper);
    wrapper.appendChild(trigger);
    wrapper.appendChild(list);
    select.classList.add('has-mobile-filter-select');

    enhancedSelects.set(select, { wrapper, trigger, list });

    trigger.addEventListener('click', () => {
      const isOpen = wrapper.classList.toggle('is-open');
      closeOtherDropdowns(isOpen ? wrapper : null);
      trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    select.addEventListener('change', () => refreshCustomOptions(select));

    const observer = new MutationObserver(() => refreshCustomOptions(select));
    observer.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['selected'] });

    refreshCustomOptions(select);
  }

  /**
   * Finds the Portfolio and Blog filter selects and enhances them where needed.
   *
   * @returns {void}
   */
  function initialiseMobileFilterSelects() {
    if (!mobileQuery.matches) {
      closeOtherDropdowns();
      return;
    }

    document
      .querySelectorAll('.portfolio-filter-panel select.portfolio-filter-select, .blog-filter-panel select.portfolio-filter-select')
      .forEach((select) => enhanceSelect(select));
  }

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.mobile-filter-select')) {
      closeOtherDropdowns();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeOtherDropdowns();
    }
  });

  document.addEventListener('DOMContentLoaded', initialiseMobileFilterSelects);
  mobileQuery.addEventListener?.('change', initialiseMobileFilterSelects);
})();
