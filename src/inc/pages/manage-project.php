<?php
/**
 * Project Management page.
 *
 * This page keeps the two-column administration layout structure that already
 * works well visually, but uses generic Portfolio Manager copy, PM auth helpers,
 * and the Portfolio Manager colour/theme layer.
 */

declare(strict_types=1);

pmRequirePermission('can_manage_projects');
?>

<section class="manage-projects-page">
    <div class="manage-projects-header">
        <div>
            <p class="admin-kicker">Admin Portal</p>
            <h1 class="admin-page-title">Manage Projects</h1>
            <p class="admin-page-subtitle">
                Add, update, organise, and control visibility for the projects displayed on your Portfolio Manager site.
            </p>
        </div>
    </div>

    <div class="manage-projects-layout">
        <aside class="project-admin-sidebar">
            <div class="admin-panel-heading">
                <h2>Project List</h2>
                <p>Select a project to edit it. Click the selected project again to return to Add Project.</p>
            </div>

            <div id="projectList" class="project-admin-list"></div>
        </aside>

        <section class="project-admin-workspace">
            <section id="editProjectPanel" class="project-admin-panel is-hidden">
                <article class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <p class="admin-kicker">Existing Project</p>
                            <h2>Edit Project</h2>
                        </div>
                    </div>

                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>Project Name</span>
                            <input class="textbox" type="text" id="editTitle" placeholder="Project Name">
                        </label>

                        <label class="admin-field">
                            <span>Project Date</span>
                            <input class="textbox" type="date" id="editDate">
                        </label>

                        <label class="admin-field blog-published-toggle">
                            <span>Portfolio Visibility</span>
                            <label class="blog-checkbox-row">
                                <input type="checkbox" id="editVisible" checked>
                                <span>Visible on public portfolio</span>
                            </label>
                        </label>

                        <label class="admin-field admin-field-wide">
                            <span>Project URL</span>
                            <input class="textbox" type="text" id="editLinkref" placeholder="Live website or demo URL">
                        </label>

                        <label class="admin-field admin-field-wide">
                            <span>Repository URL</span>
                            <input class="textbox" type="text" id="editGithub" placeholder="GitHub, GitLab, Bitbucket, Git, etc.">
                        </label>

                        <div id="editRepoProviderPreview" class="repo-provider-preview admin-field-wide"></div>

                        <label class="admin-field admin-field-wide">
                            <span>Project Overview</span>
                            <textarea id="editOverview" placeholder="Project Overview"></textarea>
                        </label>

                        <div class="admin-field admin-field-wide">
                            <span>Operating System / Platform</span>
                            <div id="editOsIcons" class="os-icon-selector"></div>
                        </div>

                        <div class="admin-field admin-field-wide">
                            <span>Tech Stack</span>
                            <div id="editTechIcons" class="tech-icon-selector tech-icon-selector-grouped"></div>
                        </div>
                    </div>

                    <div class="admin-actions">
                        <button type="button" onclick="updateProject()">Update Project</button>
                        <button type="button" class="danger-button" onclick="deleteProject()">Delete Project</button>
                        <button type="button" class="secondary-button" onclick="clearProjectSelection()">Cancel Edit</button>
                    </div>
                </article>
            </section>

            <section id="addProjectPanel" class="project-admin-panel">
                <article class="admin-card">
                    <div class="admin-card-header">
                        <div>
                            <p class="admin-kicker">New Project</p>
                            <h2>Add Project</h2>
                        </div>
                    </div>

                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>Project Name</span>
                            <input class="textbox" type="text" id="newTitle" placeholder="Project Name">
                        </label>

                        <label class="admin-field">
                            <span>Project Date</span>
                            <input class="textbox" type="date" id="newDate">
                        </label>

                        <label class="admin-field blog-published-toggle">
                            <span>Portfolio Visibility</span>
                            <label class="blog-checkbox-row">
                                <input type="checkbox" id="newVisible" checked>
                                <span>Visible on public portfolio</span>
                            </label>
                        </label>

                        <label class="admin-field admin-field-wide">
                            <span>Project URL</span>
                            <input class="textbox" type="text" id="newLinkref" placeholder="Live website or demo URL">
                        </label>

                        <label class="admin-field admin-field-wide">
                            <span>Repository URL</span>
                            <input class="textbox" type="text" id="newGithub" placeholder="GitHub, GitLab, Bitbucket, Git, etc.">
                        </label>

                        <div id="newRepoProviderPreview" class="repo-provider-preview admin-field-wide"></div>

                        <label class="admin-field admin-field-wide">
                            <span>Project Overview</span>
                            <textarea id="newOverview" placeholder="Project Overview"></textarea>
                        </label>

                        <div class="admin-field admin-field-wide">
                            <span>Operating System / Platform</span>
                            <div id="newOsIcons" class="os-icon-selector"></div>
                        </div>

                        <div class="admin-field admin-field-wide">
                            <span>Tech Stack</span>
                            <div id="newTechIcons" class="tech-icon-selector tech-icon-selector-grouped"></div>
                        </div>
                    </div>

                    <div class="admin-actions">
                        <button type="button" onclick="addProject()">Add Project</button>
                    </div>
                </article>
            </section>
        </section>
    </div>
</section>

<script src="src/js/manageprojects.js?v=<?= rawurlencode(PM_VERSION) ?>" defer></script>
