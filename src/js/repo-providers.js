/**
 * Shared repository-provider detection helpers.
 *
 * Manage Projects and the public Portfolio renderer both call this file so the
 * repository provider list and matching behaviour do not drift between pages.
 */
(() => {
  /**
   * Normalises an arbitrary URL or typed value into a searchable host/path.
   *
   * @param {string} value - Raw repository URL or typed admin preview value.
   * @returns {{raw: string, searchable: string}} Normalised search values.
   */
  function normaliseRepoSearchValue(value) {
    const raw = String(value || '').trim().toLowerCase();

    if (raw === '') {
      return { raw, searchable: '' };
    }

    try {
      const normalisedUrl = raw.includes('://') ? raw : `https://${raw}`;
      const parsedUrl = new URL(normalisedUrl);

      return {
        raw,
        searchable: `${parsedUrl.hostname}${parsedUrl.pathname}`.toLowerCase(),
      };
    } catch {
      return { raw, searchable: raw };
    }
  }

  /**
   * Checks whether an admin alias should match the current typed value.
   *
   * Aliases are intentionally limited to loose/admin mode so tiny shortcuts such
   * as "az" do not accidentally match unrelated public repository URLs.
   *
   * @param {string} raw - Raw lowercase user value.
   * @param {string} alias - Provider alias.
   * @returns {boolean} True when the alias is a reasonable admin preview match.
   */
  function aliasMatches(raw, alias) {
    const cleanAlias = String(alias || '').toLowerCase();

    if (cleanAlias === '') {
      return false;
    }

    return raw === cleanAlias || raw.startsWith(cleanAlias);
  }

  /**
   * Detects a repository provider from a URL or admin preview value.
   *
   * Strict mode checks only real provider domains. Loose mode also checks admin
   * aliases so the Manage Projects preview can respond while a user is typing.
   *
   * @param {string} value - Repository URL or typed field value.
   * @param {{loose?: boolean, returnUnknown?: boolean, includeGenericGit?: boolean}} options - Detection options.
   * @returns {{key: string, label: string}|null} Provider metadata, unknown fallback, or null.
   */
  function pmDetectRepoProvider(value, options = {}) {
    const { raw, searchable } = normaliseRepoSearchValue(value);
    const providers = window.portfolioManagerRepoProviders || {};
    const loose = options.loose === true;
    const returnUnknown = options.returnUnknown === true;
    const includeGenericGit = options.includeGenericGit !== false;

    if (searchable === '') {
      return returnUnknown ? { key: 'unknown', label: 'Repository' } : null;
    }

    for (const [key, provider] of Object.entries(providers)) {
      const domains = Array.isArray(provider.domains) ? provider.domains : [];
      const aliases = loose && Array.isArray(provider.aliases) ? provider.aliases : [];
      const domainMatched = domains.some((domain) => searchable.includes(String(domain).toLowerCase()));
      const aliasMatched = aliases.some((alias) => aliasMatches(raw, alias));

      if (domainMatched || aliasMatched) {
        return {
          key,
          label: provider.label || key,
        };
      }
    }

    if (includeGenericGit && searchable.includes('git.')) {
      return { key: 'git', label: 'Git' };
    }

    return returnUnknown ? { key: 'unknown', label: 'Repository' } : null;
  }

  window.pmDetectRepoProvider = pmDetectRepoProvider;
})();
