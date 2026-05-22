/**
 * Portfolio Manager project administration UI.
 *
 * Purpose:
 * - Uses the working admin layout from the Portfolio Manager admin page style.
 * - Keeps Add Project and Edit Project as separate panels so the edit form is hidden
 *   until a project is selected.
 * - Renders technology options grouped by category so languages, frameworks,
 *   runtimes, databases, and tools are not mixed together.
 * - Supports older flat `tech: []` project records and the newer grouped tech object.
 */

let currentId = null;

const osIcons = {
    Web: "src/icons/os/web.png",
    Windows: "src/icons/os/windows.png",
    macOS: "src/icons/os/apple.png",
    Linux: "src/icons/os/linux.png",
    iOS: "src/icons/os/ios.png",
    Android: "src/icons/os/android.svg",
    "Raspberry Pi": "src/icons/os/rpi.png",
};

const techCategoryLabels = window.portfolioManagerTechCategories || {
    misc: "Other / Misc",
};

const mergedTechIcons = {
    ...(typeof techIcons !== "undefined" ? techIcons : {}),
    ...(window.portfolioManagerTechIcons || {}),
};

const mergedTechCategoryMap = {
    ...(window.portfolioManagerTechCategoryMap || {}),
};

/**
 * Returns technology icon entries grouped by the SQLite-backed catalogue.
 *
 * All labels, categories, and icon paths originate from tech-catalogue.js.php,
 * which is generated from the `tech_items` table. This prevents Manage Projects
 * from maintaining a second hardcoded technology list.
 *
 * @returns {Record<string, Array<{name: string, iconPath: string}>>} Grouped tech records.
 */
function groupedTechIcons() {
    const grouped = Object.fromEntries(Object.keys(techCategoryLabels).map(key => [key, []]));

    Object.entries(mergedTechIcons).forEach(([name, iconPath]) => {
        const category = mergedTechCategoryMap[name] || "misc";

        if (!grouped[category]) {
            grouped.misc.push({ name, iconPath });
            return;
        }

        grouped[category].push({ name, iconPath });
    });

    Object.keys(grouped).forEach(category => {
        grouped[category].sort((left, right) => left.name.localeCompare(right.name));
    });

    return grouped;
}

/**
 * Shows the Add Project panel and hides the Edit Project panel.
 *
 * @returns {void}
 */
function showAddProjectPanel() {
    const addPanel = document.getElementById("addProjectPanel");
    const editPanel = document.getElementById("editProjectPanel");

    if (addPanel) {
        addPanel.classList.remove("is-hidden");
        addPanel.classList.add("is-active");
    }

    if (editPanel) {
        editPanel.classList.add("is-hidden");
        editPanel.classList.remove("is-active");
    }
}

/**
 * Shows the Edit Project panel and hides the Add Project panel.
 *
 * @returns {void}
 */
function showEditProjectPanel() {
    const addPanel = document.getElementById("addProjectPanel");
    const editPanel = document.getElementById("editProjectPanel");

    if (addPanel) {
        addPanel.classList.add("is-hidden");
        addPanel.classList.remove("is-active");
    }

    if (editPanel) {
        editPanel.classList.remove("is-hidden");
        editPanel.classList.add("is-active");
    }
}

/**
 * Escapes text before injecting it into template strings.
 *
 * @param {unknown} value - Raw display value.
 * @returns {string} HTML-safe text.
 */
function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, character => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    }[character]));
}

/**
 * Determines whether a project should be visible in the public portfolio.
 *
 * @param {object} project - Project record from projects.json.
 * @returns {boolean} True when the project is public.
 */
function isProjectVisible(project) {
    return project.is_visible !== false;
}

/**
 * Returns a flat selected-technology array from old or grouped project data.
 *
 * @param {unknown} tech - Raw project tech value.
 * @returns {string[]} Selected technology labels.
 */
function normaliseSelectedTech(tech) {
    if (Array.isArray(tech)) {
        return tech.map(String);
    }

    if (tech && typeof tech === "object") {
        return Object.values(tech).flat().map(String);
    }

    return [];
}

/**
 * Renders a grouped clickable technology selector.
 *
 * @param {string} containerId - Target container ID.
 * @param {string[]} selected - Selected technology labels.
 * @returns {void}
 */
function renderTechIcons(containerId, selected = []) {
    const container = document.getElementById(containerId);

    if (!container) {
        return;
    }

    const selectedSet = new Set(selected.map(String));
    const grouped = groupedTechIcons();

    container.innerHTML = "";

    Object.entries(techCategoryLabels).forEach(([categoryKey, categoryLabel]) => {
        const entries = grouped[categoryKey] || [];

        if (entries.length === 0) {
            return;
        }

        const group = document.createElement("section");
        group.className = "tech-selector-group";

        const heading = document.createElement("h5");
        heading.textContent = categoryLabel;
        group.appendChild(heading);

        const grid = document.createElement("div");
        grid.className = "tech-selector-grid";

        entries.forEach(({ name, iconPath }) => {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "tech-choice";
            button.dataset.tech = name;

            if (selectedSet.has(name)) {
                button.classList.add("active");
            }

            const img = document.createElement("img");
            img.src = iconPath;
            img.alt = "";
            img.className = "techimg";

            const label = document.createElement("span");
            label.textContent = name;

            button.appendChild(img);
            button.appendChild(label);

            button.addEventListener("click", () => {
                button.classList.toggle("active");
            });

            grid.appendChild(button);
        });

        group.appendChild(grid);
        container.appendChild(group);
    });
}

/**
 * Renders platform/operating-system selectors.
 *
 * @param {string} containerId - Target container ID.
 * @param {string[]} selected - Selected platform labels.
 * @returns {void}
 */
function renderOsIcons(containerId, selected = []) {
    const container = document.getElementById(containerId);

    if (!container) {
        return;
    }

    const selectedSet = new Set(selected.map(String));
    container.innerHTML = "";

    Object.entries(osIcons).forEach(([name, iconPath]) => {
        const label = document.createElement("label");
        label.classList.add("os-icon-option");

        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.value = name;
        checkbox.checked = selectedSet.has(name);

        const visual = document.createElement("span");

        const img = document.createElement("img");
        img.src = iconPath;
        img.alt = "";

        const text = document.createElement("span");
        text.textContent = name;

        visual.appendChild(img);
        visual.appendChild(text);
        label.appendChild(checkbox);
        label.appendChild(visual);
        container.appendChild(label);
    });
}

/**
 * Reads selected tech values from a selector container.
 *
 * @param {string} containerId - Selector container ID.
 * @returns {string[]} Selected technology labels.
 */
function getSelectedTechs(containerId) {
    const container = document.getElementById(containerId);

    if (!container) {
        return [];
    }

    return Array.from(container.querySelectorAll(".tech-choice.active"))
        .map(button => button.dataset.tech)
        .filter(Boolean);
}

/**
 * Reads selected OS/platform values from a selector container.
 *
 * @param {string} containerId - Selector container ID.
 * @returns {string[]} Selected platform labels.
 */
function getSelectedOs(containerId) {
    const container = document.getElementById(containerId);

    if (!container) {
        return [];
    }

    return Array.from(container.querySelectorAll("input[type='checkbox']:checked"))
        .map(input => input.value);
}

/**
 * Detects a repository host from a repository URL or typed admin preview value.
 *
 * The provider catalogue and detection logic now live in `repo-providers.js` so
 * Manage Projects and the public Portfolio renderer do not maintain separate
 * provider arrays. Admin-side detection uses loose matching so short typed
 * aliases such as "gith" can still preview as GitHub while the user is typing.
 *
 * @param {string} url - Repository URL or typed admin field value.
 * @returns {{key: string, label: string}|null} Provider metadata or null.
 */
function getRepoProvider(url) {
    if (typeof window.pmDetectRepoProvider === "function") {
        return window.pmDetectRepoProvider(url, {
            loose: true,
            returnUnknown: false,
            includeGenericGit: true,
        });
    }

    return null;
}

/**
 * Updates the repository provider preview below a repository URL field.
 *
 * @param {string} inputId - Input field ID.
 * @param {string} previewId - Preview container ID.
 * @returns {void}
 */
function updateRepoProviderPreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!input || !preview) {
        return;
    }

    const provider = getRepoProvider(input.value);

    if (!provider) {
        preview.innerHTML = `<span class="repo-preview-muted">No repository link entered.</span>`;
        return;
    }

    preview.innerHTML = `
        <span class="repo-preview-icon repo-provider-${provider.key}"></span>
        <span>Detected repository provider: ${provider.label}</span>
    `;
}

/**
 * Binds live repository provider detection to a repository URL field.
 *
 * @param {string} inputId - Input field ID.
 * @param {string} previewId - Preview container ID.
 * @returns {void}
 */
function bindRepoProviderPreview(inputId, previewId) {
    const input = document.getElementById(inputId);

    if (!input) {
        return;
    }

    input.addEventListener("input", () => updateRepoProviderPreview(inputId, previewId));
    updateRepoProviderPreview(inputId, previewId);
}

/**
 * Clears the selected project and returns the UI to add mode.
 *
 * @returns {void}
 */
function clearProjectSelection() {
    currentId = null;

    document.querySelectorAll(".project-select-button.is-selected").forEach(button => {
        button.classList.remove("is-selected");
    });

    ["editTitle", "editLinkref", "editGithub", "editDate", "editOverview"].forEach(fieldId => {
        const field = document.getElementById(fieldId);

        if (field) {
            field.value = "";
        }
    });

    const editVisible = document.getElementById("editVisible");

    if (editVisible) {
        editVisible.checked = true;
    }

    renderTechIcons("editTechIcons", []);
    renderOsIcons("editOsIcons", []);
    updateRepoProviderPreview("editGithub", "editRepoProviderPreview");
    showAddProjectPanel();
}

/**
 * Fetches the project JSON file and renders the admin selection list.
 *
 * @returns {void}
 */
function fetchProjects() {
    const projectList = document.getElementById("projectList");

    if (!projectList) {
        return;
    }

    fetch("src/js/projects.json?ts=" + Date.now(), { cache: "no-store" })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to fetch projects: ${response.status}`);
            }

            return response.json();
        })
        .then(data => {
            if (!Array.isArray(data)) {
                throw new Error("projects.json did not return an array.");
            }

            data.sort((a, b) => Number(a.id) - Number(b.id));
            window.projectData = data;

            if (data.length === 0) {
                projectList.innerHTML = `<p class="blog-admin-empty">No projects yet.</p>`;
                return;
            }

            projectList.innerHTML = `
                <ul>
                    ${data.map(project => {
                        const visible = isProjectVisible(project);
                        const visibilityClass = visible ? "project-visible" : "project-hidden";
                        const visibilitySymbol = visible ? "✓" : "✕";
                        const visibilityLabel = visible ? "Public" : "Hidden";

                        return `
                            <li>
                                <button
                                    type="button"
                                    class="project-select-button project-select-with-status"
                                    data-project-id="${Number(project.id)}"
                                    onclick="selectProject(${Number(project.id)})"
                                >
                                    <span class="project-visibility-cell ${visibilityClass}" title="${visibilityLabel}">${visibilitySymbol}</span>
                                    <span class="project-select-title">${escapeHTML(project.title || "Untitled Project")}</span>
                                </button>
                            </li>
                        `;
                    }).join("")}
                </ul>
            `;
        })
        .catch(error => {
            console.error(error);
            projectList.innerHTML = "<p>Could not load projects.</p>";
        });
}

/**
 * Selects a project and loads its values into the edit panel.
 *
 * @param {number|string} id - Project ID.
 * @returns {void}
 */
function selectProject(id) {
    const numericId = Number(id);
    const project = window.projectData?.find(item => Number(item.id) === numericId);

    if (!project) {
        return;
    }

    if (currentId === numericId) {
        clearProjectSelection();
        return;
    }

    currentId = numericId;

    document.querySelectorAll(".project-select-button.is-selected").forEach(button => {
        button.classList.remove("is-selected");
    });

    document.querySelector(`.project-select-button[data-project-id="${numericId}"]`)?.classList.add("is-selected");

    document.getElementById("editTitle").value = project.title || "";
    document.getElementById("editLinkref").value = project.linkref || "";
    document.getElementById("editGithub").value = project.reporef || project.githubref || "";
    document.getElementById("editDate").value = project.date || "";
    document.getElementById("editOverview").value = project.overview || "";

    const editVisible = document.getElementById("editVisible");

    if (editVisible) {
        editVisible.checked = isProjectVisible(project);
    }

    renderTechIcons("editTechIcons", normaliseSelectedTech(project.tech));
    renderOsIcons("editOsIcons", Array.isArray(project.os) ? project.os : []);
    updateRepoProviderPreview("editGithub", "editRepoProviderPreview");
    showEditProjectPanel();
}

/**
 * Sends a project API request to the PHP JSON handler.
 *
 * @param {object} payload - Request payload.
 * @returns {Promise<object>} Parsed JSON response.
 */
function postProjectPayload(payload) {
    return fetch("src/inc/project-api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    }).then(async response => {
        const result = await response.json().catch(() => null);

        if (!response.ok || !result || result.success !== true) {
            throw new Error(result?.message || "Project update failed.");
        }

        return result;
    });
}

/**
 * Builds a project payload from a named Add/Edit field prefix.
 *
 * @param {"new"|"edit"} prefix - Field ID prefix.
 * @returns {object} Project payload.
 */
function readProjectPayload(prefix) {
    return {
        title: document.getElementById(`${prefix}Title`).value.trim(),
        linkref: document.getElementById(`${prefix}Linkref`).value.trim(),
        githubref: document.getElementById(`${prefix}Github`).value.trim(),
        date: document.getElementById(`${prefix}Date`).value,
        overview: document.getElementById(`${prefix}Overview`).value.trim(),
        tech: getSelectedTechs(`${prefix}TechIcons`),
        os: getSelectedOs(`${prefix}OsIcons`),
        is_visible: document.getElementById(`${prefix}Visible`)?.checked ?? true
    };
}

/**
 * Updates the currently selected project.
 *
 * @returns {void}
 */
function updateProject() {
    if (currentId === null) {
        alert("Select a project first.");
        return;
    }

    const updated = readProjectPayload("edit");

    if (updated.title === "") {
        alert("Project name is required.");
        return;
    }

    postProjectPayload({ action: "update", id: currentId, ...updated })
        .then(() => location.reload())
        .catch(error => alert(error.message));
}

/**
 * Deletes the currently selected project after confirmation.
 *
 * @returns {void}
 */
function deleteProject() {
    if (currentId === null) {
        alert("Select a project first.");
        return;
    }

    if (!confirm("Delete this project?")) {
        return;
    }

    postProjectPayload({ action: "delete", id: currentId })
        .then(() => location.reload())
        .catch(error => alert(error.message));
}

/**
 * Adds a new project from the Add Project panel.
 *
 * @returns {void}
 */
function addProject() {
    const newProject = readProjectPayload("new");

    if (newProject.title === "") {
        alert("Project name is required.");
        return;
    }

    postProjectPayload({ action: "add", ...newProject })
        .then(() => location.reload())
        .catch(error => alert(error.message));
}

/**
 * Initialises the project manager once the DOM is ready.
 */
document.addEventListener("DOMContentLoaded", () => {
    if (!document.getElementById("projectList")) {
        return;
    }

    fetchProjects();
    renderTechIcons("newTechIcons", []);
    renderTechIcons("editTechIcons", []);
    renderOsIcons("newOsIcons", []);
    renderOsIcons("editOsIcons", []);
    showAddProjectPanel();
    bindRepoProviderPreview("newGithub", "newRepoProviderPreview");
    bindRepoProviderPreview("editGithub", "editRepoProviderPreview");
});
