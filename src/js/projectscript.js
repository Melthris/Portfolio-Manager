 
  // Load the projects JSON and render to the DOM
  fetch('src/js/projects.json')
    .then(response => response.json())
    .then(projects => {
      // Sort by year most recent to last
      projects.sort((a, b) => b.year - a.year);
  
      const container = document.getElementById('projects');
  
      projects.forEach(project => {
        const div = document.createElement('div');
        div.classList.add('project');
  
        const techImages = project.tech.map(tech => {
          const src = techIcons[tech] || '../icons/unknown.svg'; // fallback
          return `<img class="techimg" src="${src}" title="${tech}" >`;
        }).join('');
  
        const githubLinkHTML = project.githubref
        ? `<a class="project-github" href="${project.githubref}" target="_blank"></a>`
        : '';
        const externalLinkHTML = project.linkref
        ? `<a class="site-link" href="${project.linkref}" target="_blank"></a>`
        : '';


        div.innerHTML = `
        <div class="project-box">
        <div class="project-container"></div>  
         <div class="project-details"> 
          <div class="project-name">${project.title}</div>
          <div class="project-year">(${project.year})</div>
         </div>
         <div class="project-links">
         ${externalLinkHTML}
         ${githubLinkHTML}
         </div>
         <div class="project-overview">${project.overview}</div>
         <h5>Tech Stack</h5> 
         <div class="stack">${techImages}</div>
        </div>
          `;
  
        container.appendChild(div);
      });
    })
    .catch(err => {
      console.error("Failed to load projects.json:", err);
    });