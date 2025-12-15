function renderTechIcons(containerId, selected = []) {
    const container = document.getElementById(containerId);
    container.innerHTML = ''; // clear previous

    Object.entries(techIcons).forEach(([name, iconPath]) => {
        const img = document.createElement('img');
        img.src = iconPath;
        img.alt = name;
        img.classList.add('techimg');
        img.dataset.tech = name;

        if (selected.includes(name)) {
            img.classList.add('active'); // visually mark it as selected
        }

        img.addEventListener('click', () => {
            img.classList.toggle('active');
        });

        container.appendChild(img);
    });
}


let currentId = null;

function fetchProjects() {
fetch("src/js/projects.json?ts=" + Date.now(), { cache: "no-store" })
    .then(res => res.json())
    .then(data => {
        data.sort((a, b) => a.id - b.id);
        let list = "<ul>";
        data.forEach(p => {
            list += `<li onclick="selectProject(${p.id})">${p.title}</li>`;
        });
        list += "</ul>";
        document.getElementById("projectList").innerHTML = list;
        window.projectData = data;
    
    });
    
}
function getSelectedTechs(containerId) {
    return Array.from(document.getElementById(containerId).querySelectorAll('img.active'))
                .map(img => img.dataset.tech);
}
    
function selectProject(id) {
    const project = window.projectData.find(p => p.id === id);
    currentId = id;
    document.getElementById("editTitle").value = project.title;
    document.getElementById("editLinkref").value = project.linkref;
    document.getElementById("editGithub").value = project.githubref;
    document.getElementById("editDate").value    = project.date || "";
    document.getElementById("editOverview").value = project.overview;

    renderTechIcons("editTechIcons", project.tech || []);
}

function updateProject() {
    const updated = {
        id: currentId,
        title: document.getElementById("editTitle").value,
        linkref: document.getElementById("editLinkref").value,
        githubref: document.getElementById("editGithub").value,
        date: document.getElementById("editDate").value,
        overview: document.getElementById("editOverview").value,
        tech: getSelectedTechs("editTechIcons")
    };
    fetch("src/inc/project-api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "update", project: updated })
    }).then(() => location.reload());
    
    fetchProjects();
}

function deleteProject() {
    fetch("src/inc/project-api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", id: currentId })
    }).then(() => location.reload());
}

function addProject() {
    const newProject = {
        title: document.getElementById("newTitle").value,
        linkref: document.getElementById("newLinkref").value,
        githubref: document.getElementById("newGithub").value,
        date: document.getElementById("newDate").value,
        overview: document.getElementById("newOverview").value,
        tech: getSelectedTechs("newTechIcons")
        
    };
    fetch("src/inc/project-api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "add", project: newProject })
    }).then(() => location.reload());
}

window.onload = () => {
    fetchProjects();
    renderTechIcons("newTechIcons", []);
};